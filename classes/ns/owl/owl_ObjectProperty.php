<?php

/**  Aufstellung der functionen des OWL ObjectProperty
*
*/

class OWL_ObjectProperty extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';

function __construct()
{

}

function &get_Instance()
{
return new OWL_ObjectProperty();
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

function event_initiated()
{
	$new_namespace = $this->get_ns_attribute("http://www.w3.org/1999/02/22-rdf-syntax-ns#about");

}

}

?>
