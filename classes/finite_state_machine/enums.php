<?php

namespace Finite\Elements;

/**
 * Controls which matching strategy a transition uses when the Acceptor
 * tries to advance through the input string.
 *
 *  ApplyResult   — fire if the previous regex result matches a sub-pattern
 *  PassByHit     — fire if the transition's regex matches at the current offset
 *  PassByDefault — fire unconditionally (ε-transition / default fallthrough)
 *
 * Acceptor sorts transitions by priority: ApplyResult(1) > PassByHit(2) > PassByDefault(3).
 */
enum TransitionType
{
    case PassByHit;
    case ApplyResult;
    case PassByDefault;
}

/**
 * Tells the Transducer what tree-building action to perform when a
 * transition fires.
 *
 *  StartsNode   — open a new node (tag_open equivalent)
 *  EndsNode     — close the current node (tag_close equivalent)
 *  ContinuesNode— stay in the current node, just emit data
 *  NextNode     — close the current node, then open a new sibling
 *  SingleNode   — open, emit, and immediately close a self-contained node
 */
enum TransduceProjectionBehavior
{
    case StartsNode;
    case EndsNode;
    case ContinuesNode;
    case NextNode;
    case SingleNode;
}

/**
 * Controls how the Transducer harvests data from the regex match.
 *
 *  ReturnsResult — use the full match result as node data
 *  ProcessResult — apply an additional regex to extract a sub-match
 *  NoResult      — no data is emitted
 */
enum TransduceInformationHarvest
{
    case ReturnsResult;
    case ProcessResult;
    case NoResult;
}
