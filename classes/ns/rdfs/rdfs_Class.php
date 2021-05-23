<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class RDFS_Class extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new RDFS_Class();
}


function &new_Instance()
{
                                
                                
				$obj = $this->get_Instance();
				
				$obj->link_to_class = &$this;
				$this->link_to_instance[] = &$obj;
				$this->set_is_Class();
				return $obj;
}

function complete()
	{
		parent::complete();

	}

}

?>
