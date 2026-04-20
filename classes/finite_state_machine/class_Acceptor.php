<?php

namespace Finite\Elements;

/**
 * Regex-driven finite-state acceptor / transducer driver.
 *
 * Works with yohang/finite 2.0:
 *   - States are PHP backed enums implementing \Finite\State.
 *   - Transitions are \Finite\Transition\Transition objects whose $properties
 *     array carries the Acceptor-specific config: reg, mode, move, transduce.
 *   - The yohang StateMachine is NOT used for driving — the Acceptor manages
 *     its own state pointer directly so it stays generic across any state enum.
 *   - MermaidDumper works independently of the StateMachine and is exposed via
 *     dumpGraph() for free graph visualisation.
 *
 * Usage pattern:
 *
 *   $acceptor = new Acceptor(MyState::START, $transducer);
 *   $acceptor->setStringToCheck($input);
 *   $acceptor->initialize();
 *   echo $acceptor->dumpGraph();   // Mermaid stateDiagram-v2
 *
 * @author Stefan Wegerhoff
 */
class Acceptor
{
    /**
     * Current parser state — always a backed enum implementing \Finite\State.
     * Typed as \BackedEnum so ->value is available without casting.
     *
     * @var \BackedEnum&\Finite\State
     */
    private \BackedEnum $state;

    /** The string being parsed. */
    private string $command = '';

    /** Current read position within $command. */
    private int $offset = 0;

    /** The last regex capture group match, forwarded to the Transducer. */
    private string $result = '';

    /** Optional output transducer — builds the node tree from parser events. */
    private ?Transducer $transducer;


    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    /**
     * @param \BackedEnum&\Finite\State $initialState  Starting state of the FSM.
     * @param Transducer|null           $trans          Optional transducer for output.
     */
    public function __construct(\BackedEnum $initialState, ?Transducer $trans = null)
    {
        $this->state      = $initialState;
        $this->transducer = $trans;
    }


    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Returns the string value of the current state (for the Transducer callback).
     */
    public function getFiniteState(): string
    {
        return (string) $this->state->value;
    }

    /**
     * Sets the input string and resets the read position.
     */
    public function setStringToCheck(string $command): void
    {
        $this->command = $command;
        $this->offset  = 0;
        $this->result  = '';
    }

    /**
     * Kicks off the parsing run from the current state.
     * Call after setStringToCheck().
     */
    public function initialize(): void
    {
        $this->runTransitions();
    }

    /**
     * Returns a Mermaid stateDiagram-v2 block for the FSM.
     * Paste into any Markdown renderer that supports Mermaid.
     */
    public function dumpGraph(): string
    {
        $dumper = new \Finite\Dumper\MermaidDumper();
        return $dumper->dump($this->state::class);
    }

    /**
     * Factory helper — builds the $properties array for a \Finite\Transition\Transition.
     *
     * Example:
     *   new Transition('to_cmd', [MyState::START], MyState::CMD,
     *       Acceptor::props('/^(\w+)/', TransitionType::PassByHit, 1,
     *           Transducer::createTransduce(TransduceProjectionBehavior::StartsNode, ...)))
     *
     * @param  string          $reg       Regex pattern (capture group 1 = matched token).
     * @param  TransitionType  $mode      Matching strategy.
     * @param  int             $move      Extra offset advance after the match (e.g. 1 to skip delimiter).
     * @param  array|null      $transduce Output from Transducer::createTransduce(), or null.
     * @return array           Ready to pass as the 4th argument to new Transition(…).
     */
    public static function props(
        string $reg,
        TransitionType $mode,
        int $move = 0,
        ?array $transduce = null
    ): array {
        $p = ['reg' => $reg, 'mode' => $mode, 'move' => $move];
        if ($transduce !== null) {
            $p['transduce'] = $transduce;
        }
        return $p;
    }

    /**
     * Returns the captured string, optionally post-processed.
     * Called by Transducer::callTransducer() to harvest data.
     *
     * @param  TransduceInformationHarvest $type
     * @param  array                       $modifier  ['reg'=>…, 'move'=>…] for ProcessResult.
     */
    public function givesResult(TransduceInformationHarvest $type, array $modifier): string
    {
        return match ($type) {
            TransduceInformationHarvest::ReturnsResult => $this->result,
            TransduceInformationHarvest::NoResult      => '',
            TransduceInformationHarvest::ProcessResult => $this->extractFromResult($modifier),
        };
    }


    // -------------------------------------------------------------------------
    // Core parsing engine
    // -------------------------------------------------------------------------

    /**
     * Main driver loop.
     *
     * 1. Collects all transitions whose source-states include the current state.
     * 2. Sorts them by priority (ApplyResult → PassByHit → PassByDefault).
     * 3. Tries each in order; the first one that fires ends this call.
     * 4. No match on a non-final state → RuntimeException.
     */
    private function runTransitions(): void
    {
        $available = array_values(array_filter(
            $this->state::getTransitions(),
            fn($t) => in_array($this->state, $t->getSourceStates(), true)
        ));

        // No outgoing transitions → final/accepting state, done.
        if (empty($available)) {
            return;
        }

        usort($available, function ($a, $b) {
            $pri = fn(TransitionType $m): int => match ($m) {
                TransitionType::ApplyResult   => 1,
                TransitionType::PassByHit     => 2,
                TransitionType::PassByDefault => 3,
            };
            return $pri($a->getPropertyValue('mode')) <=> $pri($b->getPropertyValue('mode'));
        });

        foreach ($available as $t) {
            $applied = match ($t->getPropertyValue('mode')) {
                TransitionType::ApplyResult   => $this->doApplyResult($t),
                TransitionType::PassByHit     => $this->doPassByHit($t),
                TransitionType::PassByDefault => $this->doPassByDefault($t),
            };
            if ($applied) {
                return;
            }
        }

        // Transitions existed but none fired — input doesn't match any pattern.
        throw new \RuntimeException(
            'Acceptor: state "' . $this->state->value . '" has no matching transition for input at offset ' . $this->offset
        );
    }

    /**
     * Tries to match the transition's regex at the current offset.
     * On success: advances the offset, stores the result, fires the transducer,
     * moves to the target state, and recurses.
     */
    private function doPassByHit(\Finite\Transition\Transition $t): bool
    {
        $reg = $t->getPropertyValue('reg');
        preg_match($reg, $this->command, $results, PREG_OFFSET_CAPTURE, $this->offset);

        if (empty($results)) {
            return false;
        }

        $this->offset += strlen($results[1][0]) + $t->getPropertyValue('move');
        $this->result  = $results[1][0];

        $this->fireTransducer($t);
        $this->state = $t->getTargetState();
        $this->runTransitions();

        return true;
    }

    /**
     * ε-transition: always fires. Fires transducer, advances state, recurses.
     */
    private function doPassByDefault(\Finite\Transition\Transition $t): bool
    {
        $this->fireTransducer($t);
        $this->state = $t->getTargetState();
        $this->runTransitions();
        return true;
    }

    /**
     * ApplyResult: fires only when the previous result itself matches a sub-pattern.
     * Stub — implement when needed.
     */
    private function doApplyResult(\Finite\Transition\Transition $t): bool
    {
        return false;
    }

    /**
     * Calls the transducer if present and the transition carries transduce config.
     */
    private function fireTransducer(\Finite\Transition\Transition $t): void
    {
        if ($this->transducer !== null && $t->hasProperty('transduce')) {
            $this->transducer->callTransducer(
                $this,
                (string) $this->state->value,
                $t->getPropertyValue('transduce')
            );
        }
    }

    /**
     * Applies an additional regex to $this->result (for ProcessResult harvest).
     */
    private function extractFromResult(array $modifier): string
    {
        preg_match($modifier['reg'], $this->result, $results, PREG_OFFSET_CAPTURE, $modifier['move']);
        if (!empty($results)) {
            return $results[1][0];
        }
        throw new \RuntimeException("Acceptor: ProcessResult regex didn't match the captured result");
    }

    public function __debugInfo(): array
    {
        return [
            'state'  => $this->state->value,
            'offset' => $this->offset,
            'result' => $this->result,
        ];
    }
}
