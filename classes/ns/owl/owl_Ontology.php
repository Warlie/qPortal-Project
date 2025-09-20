<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class OWL_Ontology extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new OWL_Ontology();
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

//primar call after finishing object, ther wont be an existing childnode
function event_initiated()
{
	$new_namespace = $this->get_ns_attribute("http://www.w3.org/1999/02/22-rdf-syntax-ns#about");
	$obj = My_NameSpace_factory::namespace_factory('http://www.w3.org/2000/01/rdf-schema');
				
	if(is_object($obj) )
	{

				/*
				echo $value . " " . get_Class($obj) . "\n";
				
				foreach($obj->get_nodes() as $key3 => $value3)
				{
				echo $key3 . ', ';
				}
				echo "\n";
				*/
				
				$this->get_parser()->namespace_frameworks[$new_namespace]['nativ'] = $obj->get_nativ();
				$this->get_parser()->namespace_frameworks[$new_namespace]['node'] = array();
				$this->get_parser()->namespace_frameworks[$new_namespace]['attrib'] = array();
	}

	/*
	$uri = $this->getRefprev()->full_URI();
	if( $uri == 'http://www.trscript.de/tree#program')
	{
	$this->to_listener();
	
	}
	*/
}
	
function event_message_in($type,&$obj)
	{
		echo "boooho----------------------------------------------------------";
/*
		
		// controls securitylevel
		if ($this->get_attribute('securitylevel'))
		{

		}
	*/	
	}
	
}

?>
