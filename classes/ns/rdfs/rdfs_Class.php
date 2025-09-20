<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class RDFS_Class extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	


function &get_Instance()
{
return new RDFS_Class($this->type,$this->namespace);
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

function event_initiated()
{
	$new_namespace = $this->get_ns_attribute("http://www.w3.org/1999/02/22-rdf-syntax-ns#about");

	//$obj = My_NameSpace_factory::namespace_factory('http://www.w3.org/2000/01/rdf-schema');
				
	if($new_namespace)
	{

		$uri = explode("#", $new_namespace);
		$this->namespace = $uri[0];
		$this->name = $uri[1];
		$this->type = $uri[1];
		
		$this->get_parser()->namespace_frameworks[$this->namespace]['node'][$this->type] = $this;

	}

}
	
}

?>
