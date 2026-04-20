<?php

use Finite\Elements\{Acceptor, TransitionType, Transducer, TransduceProjectionBehavior, TransduceInformationHarvest};

// =============================================================================
// QpWorkflowState — FSM definition for the qPortal string-command syntax
// =============================================================================
//
// Parses the legacy string notation:
//   <namespace>?<Command>(<param>=<value>,…)=<value>
//
// Graph (render with dumpGraph()):
//
//   Start → Identifire → Command ──────┬─→ Bracet → Parameters → Parameter ─┐
//                │                     ├─→ Value ──────────────────────────→ Command
//                └──────→ EOF ←────────┴────────────────────────────────────┘
//
// Registered in yohang/finite 2.0 as a backed string enum implementing \Finite\State.
// The 4th argument of each Transition carries Acceptor/Transducer config:
//   Acceptor::props($regex, $mode, $move, Transducer::createTransduce(…))
//
// =============================================================================

enum QpWorkflowState: string implements \Finite\State
{
    case START       = 'Start';
    case IDENTIFIRE  = 'Identifire';
    case COMMAND     = 'Command';
    case BRACET      = 'Bracet';
    case PARAMETERS  = 'Parameters';
    case PARAMETER   = 'Parameter';
    case VALUE       = 'Value';
    case EOF         = 'EOF';

    /** @return \Finite\Transition\TransitionInterface[] */
    public static function getTransitions(): array
    {
        return [
            // ── Start ──────────────────────────────────────────────────────────
            // ε-transition: immediately open the Identifire node and advance.
            new \Finite\Transition\Transition(
                'to_identifire',
                [self::START], self::IDENTIFIRE,
                Acceptor::props('', TransitionType::PassByDefault, 0,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::StartsNode,
                        TransduceInformationHarvest::NoResult,
                        ['node_name' => 'Identifire']
                    )
                )
            ),

            // ── Identifire ────────────────────────────────────────────────────
            // Consume everything up to '?', close Identifire, open Command node.
            new \Finite\Transition\Transition(
                'to_questionmark',
                [self::IDENTIFIRE], self::COMMAND,
                Acceptor::props('/^(.*?)(?=\?)/', TransitionType::PassByHit, 1,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::NextNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Command']
                    )
                )
            ),
            // No '?' found — everything is the Identifire, input ends here.
            new \Finite\Transition\Transition(
                'to_eof_id',
                [self::IDENTIFIRE], self::EOF,
                Acceptor::props('/^(.*?)(?=$)/', TransitionType::PassByHit, 0,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::EndsNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Command']
                    )
                )
            ),

            // ── Command ───────────────────────────────────────────────────────
            // Command name followed by '(' → enter parameter list.
            new \Finite\Transition\Transition(
                'to_open_bracet',
                [self::COMMAND], self::BRACET,
                Acceptor::props('/([A-Za-z0-9_]+)(?=\()/', TransitionType::PassByHit, 1,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::ContinuesNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Command']
                    )
                )
            ),
            // Command name followed by '=' → simple value assignment.
            new \Finite\Transition\Transition(
                'to_equal',
                [self::COMMAND], self::VALUE,
                Acceptor::props('/([A-Za-z0-9_\s]*)(?==)/', TransitionType::PassByHit, 1,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::ContinuesNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Command']
                    )
                )
            ),
            // Nothing left — close the Command node and accept.
            new \Finite\Transition\Transition(
                'to_eof_cmd',
                [self::COMMAND], self::EOF,
                Acceptor::props('/(.*?)(?=$)/', TransitionType::PassByHit, 0,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::EndsNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Command']
                    )
                )
            ),

            // ── Bracet ────────────────────────────────────────────────────────
            // ε-transition: open the first Parameter node.
            new \Finite\Transition\Transition(
                'to_parameters',
                [self::BRACET], self::PARAMETERS,
                Acceptor::props('', TransitionType::PassByDefault, 0,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::StartsNode,
                        TransduceInformationHarvest::NoResult,
                        ['node_name' => 'Parameter']
                    )
                )
            ),

            // ── Parameters ───────────────────────────────────────────────────
            // Read parameter key (up to '=').
            new \Finite\Transition\Transition(
                'to_param_equal',
                [self::PARAMETERS], self::PARAMETER,
                Acceptor::props('/([A-Za-z0-9_]+)(?==)/', TransitionType::PassByHit, 1,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::ContinuesNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Parameter']
                    )
                )
            ),

            // ── Parameter ────────────────────────────────────────────────────
            // Parameter value followed by ',' → close, open next Parameter.
            new \Finite\Transition\Transition(
                'to_param_comma',
                [self::PARAMETER], self::PARAMETERS,
                Acceptor::props("/([A-Za-z0-9'_=]+)(?=,)/", TransitionType::PassByHit, 1,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::NextNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Parameter']
                    )
                )
            ),
            // Parameter value followed by ')' → close Parameter, back to Command.
            new \Finite\Transition\Transition(
                'to_close_bracet',
                [self::PARAMETER], self::COMMAND,
                Acceptor::props("/([A-Za-z0-9'_=]+)(?=\))/", TransitionType::PassByHit, 1,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::EndsNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Parameter']
                    )
                )
            ),

            // ── Value ─────────────────────────────────────────────────────────
            // Value followed by '&' or ',' → close Value, open next Command.
            new \Finite\Transition\Transition(
                'to_comma',
                [self::VALUE], self::COMMAND,
                Acceptor::props('/(.*?)(?=&|,)/', TransitionType::PassByHit, 1,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::NextNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Command']
                    )
                )
            ),
            // End of input — close Value node and accept.
            new \Finite\Transition\Transition(
                'to_eof_val',
                [self::VALUE], self::EOF,
                Acceptor::props('/(.*?)(?=$)/', TransitionType::PassByHit, 0,
                    Transducer::createTransduce(
                        TransduceProjectionBehavior::EndsNode,
                        TransduceInformationHarvest::ReturnsResult,
                        ['node_name' => 'Command']
                    )
                )
            ),

            // ── EOF ───────────────────────────────────────────────────────────
            // No outgoing transitions — Acceptor::runTransitions() returns here.
        ];
    }
}


// =============================================================================
// qp_workflow — Transducer that fills $listOfInformation from parser events
// =============================================================================

class qp_workflow extends Transducer
{
    /**
     * Parsed command structure, compatible with Command_Object::get_Result_Array().
     *
     * Shape:
     *   ["Identifire" => string,
     *    "Command"    => ["Name" => string|null, "Attribute" => array, "Value" => string|null]]
     */
    protected $listOfInformation = [
        'Identifire' => '',
        'Command'    => ['Name' => null, 'Attribute' => [], 'Value' => null],
    ];

    /** Accumulates the two pieces (key + value) of the current parameter. */
    private array $attributePieces = [];

    /** Acceptor instance — kept for dumpGraph() access if needed. */
    private Acceptor $acceptor;


    public function __construct(string $command)
    {
        $this->acceptor = new Acceptor(QpWorkflowState::START, $this);
        $this->acceptor->setStringToCheck($command);
        $this->acceptor->initialize();
    }

    /**
     * Returns the Mermaid graph of the QpWorkflow FSM.
     * Embed in ``` ```mermaid ``` ``` blocks in any Mermaid-enabled viewer.
     */
    public function dumpGraph(): string
    {
        return $this->acceptor->dumpGraph();
    }


    // -------------------------------------------------------------------------
    // Transducer output — builds $listOfInformation from open/data/close events
    // -------------------------------------------------------------------------

    /**
     * Override: the Transducer base uses a node-name stack; qp_workflow drives
     * the stack via internal_open/close_node which it has inherited. The three
     * abstract methods below receive the resolved current-node context.
     */

    public function open_node(string $node, array $attribute): void
    {
        // Nothing to do on open — data arrives in c_data.
    }

    public function c_data(string $node, array $attribute, string $data): void
    {
        switch ($node) {
        case 'Identifire':
            $this->listOfInformation['Identifire'] = trim($data);
            break;

        case 'Command':
            if ($this->listOfInformation['Command']['Name'] === null) {
                $this->listOfInformation['Command']['Name'] = trim($data);
            } else {
                $value = trim($data);
                if ($this->listOfInformation['Command']['Name'] === '__redirect_node') {
                    $value = base64_decode(rawurldecode($value));
                }
                $this->listOfInformation['Command']['Value'] = $value;
            }
            break;

        case 'Parameter':
            $this->attributePieces[] = trim($data);
            break;

        case 'undefined':
            $this->listOfInformation['Command']['Value'] = trim($data);
            break;
        }
    }

    public function close_node(string $node, array $attribute): void
    {
        // When a Parameter node closes we have collected key + value.
        if (count($this->attributePieces) === 2) {
            $this->listOfInformation['Command']['Attribute'][$this->attributePieces[0]] = $this->attributePieces[1];
        }
        $this->attributePieces = [];
    }


    // -------------------------------------------------------------------------
    // Override internal_open_node to manage the node-name stack correctly.
    // (The base class Transducer already does this; override only for compat.)
    // -------------------------------------------------------------------------

    public function internal_open_node($parser, string $node_name, array $attribute): void
    {
        parent::internal_open_node($parser, $node_name, $attribute);
    }

    public function internal_c_data(string $node_name, string $data): void
    {
        parent::internal_c_data($node_name, $data);
    }

    public function internal_close_node($parser, string $node_name): void
    {
        parent::internal_close_node($parser, $node_name);
    }


    // -------------------------------------------------------------------------
    // Debug
    // -------------------------------------------------------------------------

    public function __debugInfo(): array
    {
        return $this->listOfInformation;
    }
}
