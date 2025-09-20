<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class PEDL_Convert extends Interface_node
{

var $data_to_check;

function &get_Instance()
{
return new PEDL_Convert();
}
	

function event_initiated()
{
}

function event_parseComplete()
{
	//echo $this->name;
}

function event_Instance(&$instance,$type,&$obj)
{
	
}

function event_message_in($type,&$obj)
{
	//leitet den inhalt erst einmal an das andere Pedl-objekt weiter
	$this->send_messages('transmit',$obj);
	
}
//noch im aufbau
function event_message_out(&$obj)
{

}

}
//set_to_out(&$obj)


?>
