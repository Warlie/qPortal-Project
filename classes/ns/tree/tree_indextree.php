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


function &get_Instance()
{
return new TREE_indextree();
}


	
	function event_message_check($type,&$obj){parent::event_message_check($type,$obj);}
	

	
}

?>
