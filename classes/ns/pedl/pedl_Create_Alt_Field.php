<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class PEDL_Create_Alt_Field extends Interface_node
{

function &get_Instance()
{
return new PEDL_Create_Alt_Field();
}

function event_message_in($type,&$obj)
{
	//leitet den inhalt erst einmal an das andere Pedl-objekt weiter
	
	$prev = &$obj->getRefprev();
	$clone = &$obj->cloning($prev);
	if($this->attrib['rdf:about'])
	{
	$name =  $this->attrib['rdf:about']->out();
	
	if(!(false === ($tmp = strpos($name,'#'))))
	{
		
		$prefix = substr(strtolower($name),0,$tmp);
		$nodename = substr(strtolower($name),$tmp + 1);
		
		$clone->name = $nodename;
	}
	}
	//$clone->name;
	
}

}

?>
