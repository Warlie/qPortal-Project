<?php
namespace Finite\Elements;
/** 
* Example_Class is a sample class for demonstrating PHPDoc 
* 
* Example_Class is a class that has no real actual code, but merely * exists to help provide people with an understanding as to how the 
* various PHPDoc tags are used. * * Example usage: 
* if (Example_Class::example()) { 
* print "I am an example."; 
* } 
* 
* @package Example 
* @author David Sklar 
* @author Adam Trachtenberg 
* @version $Revision: 1.3 $ 
* @access public 
* @see http://www.example.com/pear 
*/

abstract class Transducer
{
	protected array $node_structure = [];
	 /** 
	 * static function for creating an normed array of arguments 
	 *
	 * @param TransduceProjectionBehavior behavior 	: gives advice to call the output functions
	 * @param TransduceInformationHarvest harvest 	: modifies the way collected data will be preprocessed
	 * @param array properties 						: List of properties as an argument in open_node function
	 * @param string reg (optional)					: regular expression for extracting substring
	 * @param int move (optional) 					: offset for regular expression
	 * @return array 								: array of arguments for the transducing part 
	 * @access public
	 */
	public static function createTransduce(
		TransduceProjectionBehavior $behaviour, // 
		TransduceInformationHarvest $harvest,
		array $properties = array(),
    	string $reg = '', 
    	int $move = 0) : array {
    
    	$result = array('projection' => $behaviour, 'harvest' => $harvest, 'properties' => $properties, 'modify' => array());

    	switch ($harvest) {

    	case TransduceInformationHarvest::ProcessResult:
    		return array_merge_recursive($result, array('modify' => array('reg' => $reg, 'move' => $move)));
    		break;

    	}
    	
    	return $result;
    	}
  
    /**
    *	@param acceptor	
    */
    function callTransducer( Acceptor $acceptor, string $state, array $args)
    { 
					

    	switch ($args['projection']) {
    	case TransduceProjectionBehavior::StartsNode:
    		
    		
    			
    			$this->internal_open_node($this, $acceptor->getFiniteState(), $args['properties']);
    			if($args['harvest'] != TransduceInformationHarvest::NoResult){ 
    				$this->internal_c_data($acceptor->getFiniteState(), 
    				$acceptor->givesResult($args['harvest'] , $args['modify']));
    		echo "$state in c_data";}
    		
    		break;
    	case TransduceProjectionBehavior::EndsNode:
    		//var_dump($acceptor->givesResult($args['harvest'] , $args['modify']));
    		if($args['harvest'] != TransduceInformationHarvest::NoResult) 
    			$this->internal_c_data(
    				$acceptor->getFiniteState(), 
    				$acceptor->givesResult($args['harvest'] , $args['modify']));
    		$this->internal_close_node($this, $acceptor->getFiniteState());
    		
    		break;
    	case TransduceProjectionBehavior::ContinuesNode:
    		
    		if($args['harvest'] != TransduceInformationHarvest::NoResult) 
    			$this->internal_c_data(
    				$acceptor->getFiniteState(), 
    				$acceptor->givesResult($args['harvest'] , $args['modify']));
    		
    		break;
    	case TransduceProjectionBehavior::NextNode:
if($args['harvest'] != TransduceInformationHarvest::NoResult){ $this->internal_c_data(
	$acceptor->getFiniteState(), 
$acceptor->givesResult($args['harvest'] , $args['modify']));
}
    		$this->internal_close_node($this, $acceptor->getFiniteState());
    		$this->internal_open_node($this, $acceptor->getFiniteState(), $args['properties']);
    		
    					
    		
    		break;
    	case TransduceProjectionBehavior::SingleNode:

    		$this->internal_open_node($this, $acceptor->getFiniteState(), $args['properties']);
    		if($args['harvest'] != TransduceInformationHarvest::NoResult) $this->internal_c_data($acceptor->getFiniteState(), $acceptor->givesResult($args['harvest'] , $args['modify']));
    		$this->internal_close_node($this, $acceptor->getFiniteState());


    					
    		
    		break;
    	}

    	
    }
    	
    	
    protected function createNodeRequirements($node_name, $property_array ){
    	// create or alter array of nodes with its necessary attributes
    	if(array_key_exists($node_name, $this->node_structure))
    	{
    		$this->node_structure[$node_name] = array_merge($this->node_structure , $property_array);
    	}
    	else
    	{
    		$this->node_structure[$node_name] = $property_array;
    	}
    }
    
    protected function addNodeContent(){}
    
	function internal_open_node($parser, $node_name, $attribute){$this->open_node($parser, $node_name, $attribute);}
	function internal_c_data($node_name,$data){$this->c_data($node_name,$data);}
	function internal_close_node($parser, $node_name){$this->close_node($parser, $node_name);}
    	
    /** 
	* static function for creating an normed array of arguments 
	*
	* @param TransduceProjectionBehavior behavior 	: gives advice to call the output functions
	* @param TransduceInformationHarvest harvest 	: modifies the way collected data will be preprocessed
	* @param array properties 						: List of properties as an argument in open_node function
	* @param string reg (optional)					: regular expression for extracting substring
	* @param int move (optional) 					: offset for regular expression
	* @return array 								: array of arguments for the transducing part 
	* @access public
	*/
	/*
	abstract protected function open_node($parser, $node_name, $attribute);
	abstract protected function c_data($node_name,$data);
	abstract protected function close_node($parser, $node_name);
	*/
}




?>