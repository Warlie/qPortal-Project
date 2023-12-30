<?php

/*
$namespaces=array();
foreach(get_declared_classes() as $name) {
	echo $name . "\n";
    if(preg_match_all("@[^\\\]+(?=\\\)@iU", $name, $matches)) {
        $matches = $matches[0];
        $parent =&$namespaces;
        while(count($matches)) {
            $match = array_shift($matches);
            if(!isset($parent[$match]) && count($matches))
                $parent[$match] = array();
            $parent =&$parent[$match];

        }
    }
}

print_r($namespaces);
*/


use Finite\Elements\{Acceptor,TransitionType , Transducer, TransduceProjectionBehavior, TransduceInformationHarvest};

$workflow = new qp_workflow("*?__find_node(model=xpath_model,namespace=boom,query=wubb)=wer");

/*
class ConfigClass extends Transducer
{
	public function open_node($parser, $node_name, $attribute){echo "\n-----------------------\n"; var_dump($parser, $node_name, $attribute);}
	public function c_data($data){var_dump($data);}
	public function close_node($parser, $node_name){echo "\n-----------------------\n";}
}



// Configure your graph
$Acceptor     = new Acceptor(array(
    'class'       => 'Acceptor',
    'states'      => array(
        'Identifire'    => array(
            'type'       => Finite\State\StateInterface::TYPE_INITIAL,
            'properties' => [ Acceptor::createTransition('/(.*?)(\?|$)/s',TransitionType::PassByHit, 'to_questionmark', 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::ReturnsResult, array('namespace' => 'xyz'))) ],
        ),
        'Command' => array(
            'type'       => Finite\State\StateInterface::TYPE_FINAL,
            'properties' => [ Acceptor::createTransition('/([A-Za-z0-9_]+)(?=\()/',TransitionType::PassByHit, 'to_open_bracet' , 1 )
            	, Acceptor::createTransition('/([A-Za-z0-9_\s]*)(?==)/',TransitionType::PassByHit, 'to_equal' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::ReturnsResult))],
        ),
        'Parameters' => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => [ Acceptor::createTransition('/([A-Za-z0-9_]+)(?==)/',TransitionType::PassByHit, 'to_param_equal' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::ReturnsResult))],
        ),
        'Parameter' => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => [Acceptor::createTransition('/([A-Za-z0-9_]+)(?=,)/',TransitionType::PassByHit, 'to_param_comma' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::ReturnsResult))
            	, Acceptor::createTransition('/([A-Za-z0-9_]+)(?=\))/',TransitionType::PassByHit, 'to_close_bracet' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::ReturnsResult))],
        ),
        'P-VAlue' => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => [Acceptor::createTransition('/(\s*)(?==)/',TransitionType::PassByHit, 'to_equal' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::ReturnsResult))],
        ),
        'Value' => array(
            'type'       => Finite\State\StateInterface::TYPE_FINAL,
            'properties' => [Acceptor::createTransition('/([A-Za-z0-9_]+)(?=,|$)/',TransitionType::PassByHit, 'to_comma' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::ReturnsResult))],
        )
    ),
    'transitions' => array(
        'to_questionmark' => array('from' => array('Identifire'), 'to' => 'Command'),
        'to_open_bracet'  => array('from' => array('Command'), 'to' => 'Parameters'),
        'to_close_bracet'  => array('from' => array('Parameter'), 'to' => 'Command'),
        'to_param_equal'  => array('from' => array('Parameters'), 'to' => 'Parameter'),
        'to_param_comma'  => array('from' => array('Parameter'), 'to' => 'Parameters'),
        'to_equal'  => array('from' => array('Command', 'Parameters'), 'to' => 'Value'),
        'to_comma'  => array('from' => array('Value'), 'to' => 'Command')
    )
), new ConfigClass());



$stateMachine = $Acceptor->getStateMachine();
    			$Acceptor->setStringToCheck("*?__find_node(model=xpath_model,namespace=boom,query=wubb)=wer");
    			$Acceptor->initialize();
*/
/*
try {
	
var_dump($stateMachine->getCurrentState()->getName(),  $stateMachine->getCurrentState()->getTransitions());
	
$stateMachine->apply('to_questionmark');

var_dump($stateMachine->getCurrentState()->getName(),  $stateMachine->getCurrentState()->getTransitions());

$stateMachine->apply('to_open_bracet');

var_dump($stateMachine->getCurrentState()->getName(),  $stateMachine->getCurrentState()->getTransitions());

$stateMachine->apply('to_param_equal');

var_dump($stateMachine->getCurrentState()->getName(),  $stateMachine->getCurrentState()->getTransitions());

$stateMachine->apply('to_param_comma');

var_dump($stateMachine->getCurrentState()->getName(),  $stateMachine->getCurrentState()->getTransitions());
$stateMachine->apply('to_equal');

    
} catch (\Finite\Exception\StateException $e) {
    echo $e->getMessage();
}
*/

// Display Script End time
//$time_end = microtime(true);

//dividing with 60 will give the execution time in minutes, otherwise seconds
//$execution_time = ($time_end - $time_start);

//execution time of the script
//echo '<b>Total Execution Time:</b> '.$execution_time.' Secs';
?>