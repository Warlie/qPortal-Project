<?php

namespace Finite\Elements;

enum TransitionType
{
    case PassByHit; //Use the mentioned transition
    case ApplyResult; //Use the result for transition
    case PassByDefault; //Use, if noting is left to try
}

enum TransduceProjectionBehavior
{
	case StartsNode; //Use the mentioned transition
    case EndsNode; //Use the mentioned transition
    case ContinuesNode;
    case NextNode;
    case SingleNode;
    

}

enum TransduceInformationHarvest
{
	case ReturnsResult; //Use the mentioned transition
    case ProcessResult; //Use the mentioned transition
    case NoResult;		//No data event will happen

    

}

function cmp($a, $b)
{
	
	$give_value = function($enum)
	{
		switch ($enum) {
		case TransitionType::ApplyResult: return 1;
		case TransitionType::PassByHit: return 2; 
		case TransitionType::PassByDefault :return 3; }

	};

	return $give_value($b['mode']) - $give_value($a['mode']);
}

?>