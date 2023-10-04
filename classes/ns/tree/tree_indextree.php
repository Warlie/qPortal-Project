<?php

/**  Aufstellung der functionen des XML Literal
*  	$value;
* 	$name;
*	$back;

	function object_back($obj)
	function out()
	function in($in)
	function to_listener()
	function start()
	function event($event = '')
	function &get_Instance()
*/

class TREE_indextree extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new TREE_indextree();
}


function &new_Instance()
{
                                
				$obj = $this->get_Instance();
				
				$obj->link_to_class = &$this;
				return $obj;
}

function complete()
	{
		parent::complete();

	}

	function event_message_check($type,&$obj){parent::event_message_check($type,$obj);}
	
	function event_message_in($type,&$obj)
	{

		//$this->event_message_check($type,$obj);
		//return parent::event_message_in($type,$obj);
	
	}
	
}

?>
