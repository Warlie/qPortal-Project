<?php

namespace Finite\Elements;

/** 
* Acceptor for checking strings 
* 
* The acceptor uses the finite state machine to manage the states and uses regular expressions to hop from state to state.
* It supports clones but holds only references to the state machine.
* An exception will be thrown if the current state is not a stop state. 
* 
* if (Example_Class::example()) { 
* print "I am an example."; 
* } 
* 
* @package Example 
* @author Stefan Wegerhoff  
* @version $Revision: 0.1 $ 
* @access public 
* @internal "yohang/finite" is used
* @see http://www.example.com/pear 
*/
class Acceptor implements \Finite\StatefulInterface
{
    private $state;
    private string $prevState = '';
    private string $transition = '';
    private \Finite\StateMachine\StateMachine $stateMachine;
    private \Finite\Loader\ArrayLoader $loader;
    private string $command = "";
    private int $offset = 0;
    private string $result = '';
    private int $posInArray = 0;
    private Transducer $tranceducer;

    /**
    *	@param array = [] : array 			Specifired in yohang's library
    *	@param trans = null : Transducer	optional transducer 
    *	@see a static function 'createTransition' offers 
    *	@example array(
    *    'class'       => 'Acceptor',
    *    'states'      => array(
    *    	'Start'    => array(
    *            'type'       => Finite\State\StateInterface::TYPE_INITIAL,
    *            'properties' => [ Acceptor::createTransition('',TransitionType::PassByDefault, 'to_identifire', 0 , 
    *            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::NoResult, array('node_name' => 'Identifire')))]
    *        ),
    *        'Identifire'    => array(
    *            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
    *            'properties' => [ Acceptor::createTransition('/^(.*?)(?=\?)/',TransitionType::PassByHit, 'to_questionmark', 1 , 
    *            		Transducer::createTransduce( TransduceProjectionBehavior::NextNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Command', 'var_name' => 'valueX')))
    *            , Acceptor::createTransition('/^(.*?)(?=$)/',TransitionType::PassByHit, 'to_end' , 0 , 
    *            		Transducer::createTransduce( TransduceProjectionBehavior::EndsNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Command', 'var_name' => 'valueY')))]
    *        ),
    *        ...
    *        'EOF' => array(
    *            'type'       => Finite\State\StateInterface::TYPE_FINAL,
    *            'properties' => [],
    *        )
    *    ),
    *    'transitions' => array(
    *    	'to_identifire' => array('from' => array('Start'), 'to' => 'Identifire'),
    *        'to_questionmark' => array('from' => array('Identifire'), 'to' => 'Command'),
    *        'to_open_bracet'  => array('from' => array('Command'), 'to' => 'Bracet'),
    *        ...
    *        'to_end'  => array('from' => array('Command', 'Value', 'Identifire'), 'to' => 'EOF')
    *    )
    *)   
    */
    public function __construct( array $array = array(), ?Transducer $trans = null)
    {
    	
    	$this->stateMachine = new \Finite\StateMachine\StateMachine($this);
    	$this->loader       = new \Finite\Loader\ArrayLoader($array);
    	$this->loader->load($this->stateMachine);
    	

    	
    	$this->stateMachine->getDispatcher()->addListener(
    \Finite\Event\FiniteEvents::POST_TRANSITION,
    \Finite\Event\Callback\CallbackBuilder::create($this->stateMachine)
        ->setCallable(function () {

            $this->applyTransition($this->getStateMachine()->getCurrentState()->getProperties());

        })
        ->getCallback()
        );

    	if(!is_null($trans)){
    		$this->tranceducer = $trans;
        
    		$this->stateMachine->getDispatcher()->addListener(
    			\Finite\Event\FiniteEvents::PRE_TRANSITION,
    			\Finite\Event\Callback\CallbackBuilder::create($this->stateMachine)
    			->setCallable(function () {

    					$this->callTransducer(
    						((array_key_exists(
    							'transduce', 
    							$prop = $this->getStateMachine()->getCurrentState()->getProperties()[$this->posInArray]))? $prop['transduce'] : array() )
    							);
            	})
            	->getCallback()
            	);
        }	
    }
    
    /**
    * inherit getter method for current state
    *
    * @return state : string
    */
    public function getFiniteState()
    {
        return $this->state;
    }

    /**
    * inherit setter method for current state
    */
    public function setFiniteState($state)
    {
        $this->state = $state;
    }
    
    /**
    * Is setter for a string to accept. If there is a transducer, you will get a result beside the acceptance.
    *
    * @param command : string
    *
    * TODO var name 'command' could be missleading.   
    */
    public function setStringToCheck(string $command){ $this->command = $command;}
    
    /**
    * Starts the accepting process
    */
    public function initialize()
    {
    	$this->stateMachine->initialize();

    	$this->applyTransition($this->stateMachine->getCurrentState()->getProperties());
    }
    
    /**
    * @param args : array
    * @return void
    */
    function callTransducer($args) : void {
    	if(isset($this->tranceducer) && $this->tranceducer instanceof Transducer)
    		$this->tranceducer->callTransducer($this, $this->state, $args);
    }
    //moved to Transducer
    private function callTransducer2($args)
    { 
    	if(!isset($this->tranceducer))return;					

    	switch ($args['projection']) {
    	case TransduceProjectionBehavior::StartsNode:

    		$this->tranceducer->internal_open_node($this, $this->state, $args['properties']);
    		if($args['harvest'] != TransduceInformationHarvest::NoResult) $this->tranceducer->internal_c_data($this->state, $this->givesResult($args['harvest'] , $args['modify']));
    		
    		break;
    	case TransduceProjectionBehavior::EndsNode:
    		if($args['harvest'] != TransduceInformationHarvest::NoResult) $this->tranceducer->internal_c_data($this->state, $this->givesResult($args['harvest'] , $args['modify']));
    		$this->tranceducer->internal_close_node($this, $this->state);
    		
    		break;
    	case TransduceProjectionBehavior::ContinuesNode:
    		
    		if($args['harvest'] != TransduceInformationHarvest::NoResult) $this->tranceducer->internal_c_data($this->state, $this->givesResult($args['harvest'] , $args['modify']));
    		
    		break;
    	case TransduceProjectionBehavior::NextNode:
if($args['harvest'] != TransduceInformationHarvest::NoResult) $this->tranceducer->internal_c_data($this->state, $this->givesResult($args['harvest'] , $args['modify']));
    		$this->tranceducer->internal_close_node($this, $this->state);
    		$this->tranceducer->internal_open_node($this, $this->state, $args['properties']);
    		
    					
    		
    		break;
    	case TransduceProjectionBehavior::SingleNode:

    		$this->tranceducer->internal_open_node($this, $this->state, $args['properties']);
    		if($args['harvest'] != TransduceInformationHarvest::NoResult) $this->tranceducer->internal_c_data($this->state, $this->givesResult($args['harvest'] , $args['modify']));
    		$this->tranceducer->internal_close_node($this, $this->state);


    					
    		
    		break;
    	}

    	
    }
    /*
        		$result['reg'] = $reg;
    		$result['move'] = $move;
    */
    /**
    * 
    */
    function givesResult(TransduceInformationHarvest $type , array $modifier) : string
    {
    	switch ($type) {
    	case TransduceInformationHarvest::ReturnsResult: return $this->result;
    	case TransduceInformationHarvest::NoResult: return [];
    	case TransduceInformationHarvest::ProcessResult:
    		   	    $results;
    		   	    
    						
    	    preg_match($modifier['reg'], $this->result, $results, PREG_OFFSET_CAPTURE, $modifier['move']);
    		if(is_array($results) && (count($results)>0))
    		{
    			return $results[1][0];
    		}
    		throw new Exception("The reg doesn't apply on the result" );
    		
    		break;

    	}
    }
    
    private function applyTransition($constrains)
    {
		

    	usort($constrains, "Finite\\Elements\\cmp");
    	
    	for ($i = 0; $i < count($constrains); $i++) {


    	//var_dump($constrains[$i]);
  //  		var_dump($constrains[$i]['reg'], $this->command,  $this->offset, $constrains[$i]['mode']);

  			$this->transition = $constrains[$i]['transition'];
  			$this->posInArray = $i;
  			
    		switch ($constrains[$i]['mode']) {
    		case TransitionType::ApplyResult: if($this->doApplyResult($constrains[$i]))return; break; 
    		case TransitionType::PassByHit: if($this->doPassByHit($constrains[$i]))return; break;
    		case TransitionType::PassByDefault : if($this->doPassByDefault($constrains[$i]))return; break; }
    		};
    		//echo "here" . $this->state;
    	if(!$this->stateMachine->getCurrentState()->isFinal())
    		throw new \Finite\Exception\StateException('State '. $this->state . ' is not a final state!');

    	}


    	//var $result;
    	//preg_match('/\((.*?)\)/', $result[$i], $param);
    	//$this->command
    	//var_dump($constrains);

    
    private function doPassByHit($constrain){

    	    $results;
    	    preg_match($constrain['reg'], $this->command, $results, PREG_OFFSET_CAPTURE, $this->offset);
//var_dump($constrain['reg'], $this->command,  $this->offset, $constrain['mode']);
    		if(is_array($results) && (count($results)>0))
    		{

    			$this->offset += strlen($results[1][0]) + $constrain['move'];
    			//echo "Content(" . $this->offset . "):" .  substr($this->command, $this->offset) . "\n";
    			//echo "apply tranistion:" . $constrains[$i]['transition'] . "\n";
    			$this->result = $results[1][0];
    			
    			
    			$this->stateMachine->apply($constrain['transition']);
    			return true;
    		}
    		
    		return false;
    }
    
    private function doApplyResult($constrain){

    }
    private function doPassByDefault($constrain){
    			$this->stateMachine->apply($constrain['transition']);
    }
    
    public function getStateMachine(){return $this->stateMachine;}
    
    public static function createTransition(
    	string $reg, 					// regular expression
    	TransitionType $mode, 	// Element from enum TransitionType
    	string $transition, 			// name of the transition to choose when apply successful
    	int $move = 0,				// addition to the offset in string
    	$transduce = null) {  	// optional transducer parameter
    	if(!is_null($transduce))
    		return ['reg' => $reg, 'mode' => $mode, 'transition' => $transition, 'move'=> $move, 'transduce' => $transduce ]; 
    	else
    		return ['reg' => $reg, 'mode' => $mode, 'transition' => $transition, 'move'=> $move ]; }
    	
    public function __debugInfo() {
        return [];
    }
    
}

/*
$stateMachine->getDispatcher()->addListener(\Finite\Event\FiniteEvents::PRE_TRANSITION, function(\Finite\Event\TransitionEvent $e) {
    echo 'This is a pre transition', "\n";
});

$foobar = 42;
$stateMachine->getDispatcher()->addListener(
    \Finite\Event\FiniteEvents::POST_TRANSITION,
    \Finite\Event\Callback\CallbackBuilder::create($stateMachine)
        ->setCallable(function () use ($foobar) {
            echo "\$foobar is ${foobar} and this is a post transition\n";
        })
        ->getCallback()
);
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