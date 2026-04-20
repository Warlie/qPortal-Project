<?php

namespace Finite\Elements;

/**
 * Abstract Transducer — maps Acceptor parser events to open/data/close node calls.
 *
 * Subclasses implement open_node(), c_data(), close_node() to build whatever
 * output structure they need (e.g. qp_workflow fills $listOfInformation).
 *
 * The internal_* methods manage a node-name stack so that close_node() always
 * receives the name of the node that was opened, regardless of how many levels
 * deep the parser currently is.
 *
 * @author Stefan Wegerhoff
 */
abstract class Transducer
{
    /** Tracks the currently open node chain. */
    protected array $node_structure = [];

    private array $node_stack  = [['node' => 'undefined', 'attribute' => []]];
    private array $current_node = ['node' => 'undefined', 'attribute' => []];


    // -------------------------------------------------------------------------
    // Static factory
    // -------------------------------------------------------------------------

    /**
     * Builds the transduce-config array that goes into Acceptor::props().
     *
     * @param TransduceProjectionBehavior $behaviour  What tree action to perform.
     * @param TransduceInformationHarvest $harvest    How to extract data from the match.
     * @param array                       $properties Node properties passed to open_node().
     * @param string                      $reg        Sub-regex for ProcessResult harvest.
     * @param int                         $move       Offset for the sub-regex.
     */
    public static function createTransduce(
        TransduceProjectionBehavior $behaviour,
        TransduceInformationHarvest $harvest,
        array $properties = [],
        string $reg = '',
        int $move = 0
    ): array {
        $result = [
            'projection' => $behaviour,
            'harvest'    => $harvest,
            'properties' => $properties,
            'modify'     => [],
        ];

        if ($harvest === TransduceInformationHarvest::ProcessResult) {
            $result['modify'] = ['reg' => $reg, 'move' => $move];
        }

        return $result;
    }


    // -------------------------------------------------------------------------
    // Main dispatch — called by Acceptor::fireTransducer()
    // -------------------------------------------------------------------------

    /**
     * Dispatches the transduce config to the right open/data/close sequence.
     *
     * @param Acceptor $acceptor  The live acceptor (for givesResult()).
     * @param string   $state     Current state name (before the transition fires).
     * @param array    $args      Output of createTransduce().
     */
    public function callTransducer(Acceptor $acceptor, string $state, array $args): void
    {
        $data = fn() => $acceptor->givesResult($args['harvest'], $args['modify']);

        switch ($args['projection']) {
        case TransduceProjectionBehavior::StartsNode:
            $this->internal_open_node($this, $state, $args['properties']);
            if ($args['harvest'] !== TransduceInformationHarvest::NoResult) {
                $this->internal_c_data($state, $data());
            }
            break;

        case TransduceProjectionBehavior::EndsNode:
            if ($args['harvest'] !== TransduceInformationHarvest::NoResult) {
                $this->internal_c_data($state, $data());
            }
            $this->internal_close_node($this, $state);
            break;

        case TransduceProjectionBehavior::ContinuesNode:
            if ($args['harvest'] !== TransduceInformationHarvest::NoResult) {
                $this->internal_c_data($state, $data());
            }
            break;

        case TransduceProjectionBehavior::NextNode:
            if ($args['harvest'] !== TransduceInformationHarvest::NoResult) {
                $this->internal_c_data($state, $data());
            }
            $this->internal_close_node($this, $state);
            $this->internal_open_node($this, $state, $args['properties']);
            break;

        case TransduceProjectionBehavior::SingleNode:
            $this->internal_open_node($this, $state, $args['properties']);
            if ($args['harvest'] !== TransduceInformationHarvest::NoResult) {
                $this->internal_c_data($state, $data());
            }
            $this->internal_close_node($this, $state);
            break;
        }
    }


    // -------------------------------------------------------------------------
    // Node-stack management (internal_ methods)
    // Subclasses may override these for custom stack behaviour.
    // -------------------------------------------------------------------------

    public function internal_open_node($parser, string $node_name, array $attribute): void
    {
        if (array_key_exists('node_name', $attribute)) {
            $this->current_node = ['node' => $attribute['node_name'], 'attribute' => $attribute];
            array_push($this->node_stack, $this->current_node);
            unset($attribute['node_name']);
        } else {
            echo "MISSING NODENAME! (state: $node_name)";
        }
        $this->open_node($this->current_node['node'], $this->current_node['attribute']);
    }

    public function internal_c_data(string $node_name, string $data): void
    {
        $this->c_data($this->current_node['node'], $this->current_node['attribute'], $data);
    }

    public function internal_close_node($parser, string $node_name): void
    {
        $this->close_node($this->current_node['node'], $this->current_node['attribute']);
        array_pop($this->node_stack);
        $this->current_node = end($this->node_stack) ?: ['node' => 'undefined', 'attribute' => []];
    }


    // -------------------------------------------------------------------------
    // Abstract output interface — implement in subclasses
    // -------------------------------------------------------------------------

    abstract public function open_node(string $node, array $attribute): void;
    abstract public function c_data(string $node, array $attribute, string $data): void;
    abstract public function close_node(string $node, array $attribute): void;
}
