<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class OWL_imports extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new OWL_imports();
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

}

?>
