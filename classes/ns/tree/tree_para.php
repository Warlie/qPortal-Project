<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class TREE_param extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new TREE_param();
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

function event_message_in($type,&$obj)
	{
	//echo $type . ' ' . get_Class($obj);
	}
}

?>
