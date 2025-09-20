<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class RDF_Property extends Interface_node
{
var $name = 'empty';
var $type = 'Property';
var $namespace = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';
	
function __construct()
{

}

function &get_Instance()
{
return new RDF_Property();
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
