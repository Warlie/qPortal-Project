<?php

/**  Contains various namespaces with different behavior to interoperate together
*   PEDL (Programm Element Definition Lanuage)
*   	to peddle
*/




require_once('pedl_Create_Alt_Field.php');
require_once('pedl_Convert.php');
require_once('pedl_Script.php');
require_once('pedl_name.php');
require_once('pedl_cast.php');
require_once('pedl_Object_Class.php');
require_once('pedl_hasFunctions.php');
require_once('pedl_Functions.php');
require_once('pedl_hasParameter.php');
require_once('pedl_ParameterCollection.php');
require_once('pedl_Object_Funktion.php');
require_once('pedl_Object_Constructor.php');
require_once('pedl_Object_Parameter.php');
require_once('pedl_name.php');
require_once('pedl_refersTo.php');
require_once('pedl_hasCodeResource.php');
require_once('pedl_src.php');

class PEDL_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function __construct($ns)
	{

			$this->nativ = new RDF_RDF('rdfs', $ns);
			$this->node['create_alt_field'] = new PEDL_Create_Alt_Field('create_alt_field', $ns);
			$this->node['convert'] = new PEDL_Convert('convert', $ns);
			$this->node['script'] = new PEDL_Script('script', $ns);

			$this->node['Object_Class'] = new PEDL_Object_Class('Object_Class', $ns);
			$this->node['hasFunktions'] = new PEDL_hasFunctions('hasFunktions', $ns);
			$this->node['Funktions'] = new PEDL_Functions('Funktions', $ns);
			$this->node['hasParameter'] = new PEDL_hasFunctions('hasParameter', $ns);
			$this->node['ParameterCollection'] = new PEDL_Functions('ParameterCollection', $ns);
			$this->node['Object_Funktion'] = new PEDL_Object_Funktion('Object_Funktion', $ns);
			$this->node['Object_Constructor'] = new PEDL_Object_Constructor('Object_Constructor', $ns);
			$this->node['Object_Parameter'] = new PEDL_Object_Parameter('Object_Parameter', $ns);
			$this->node['hasCodeResource'] = new PEDL_hasCodeResource('hasCodeResource', $ns);
			$this->node['src'] = new PEDL_Object_Parameter('src', $ns);
			$this->node['name'] = new PEDL_name('name', $ns);
			$this->node['refersTo'] = new PEDL_refersTo('refersTo', $ns);
			
			$this->nativ->set_is_Class();
			foreach ($this->node as $value)
				$value->set_is_Class();
			
			/*
			$this->node['template'] = new TREE_template();
			$this->node['main'] = new TREE_main();
			$this->node['statement'] = new TREE_Statement();
			$this->node['type'] = new RDF_type();
			$this->node['bag'] = new RDF_Bag();
			$this->node['rest'] = new RDF_rest();
			$this->node['subject'] = new RDF_subject();
			$this->node['value'] = new RDF_value();
			$this->node['description'] = new RDF_description();
			$this->attrib['about'] = new RDF_about();
			$this->attrib['resource'] = new RDF_resource();
			*/
			/*
				if($carry)
				   if (is_array($carry)) 
				   {
					   foreach ($carry as $k => $v) 
					   {
		    
		    
						   echo $k . " " . get_class($v) . " <br>\n";
						   if(is_array($v))
						   	foreach ($v as $l => $w) 
							{
		    
		    
								echo $l . " " . get_class($w) . " <br>\n";
						  
						   	}
							reset($v);
                    
					   
						   	
                    
					   }
				   }
			reset($carry);
			*/

			
	}
	
	function &get_nodes()
	{
		
		return $this->node;
	}
	
	function &get_nativ()
	{
		
		return $this->nativ;
	}
	
	function &get_attrib()
	{
		return $this->attrib;
	}


	
	
}

?>
