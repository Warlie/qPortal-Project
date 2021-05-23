<?php

/**  Contains various namespaces with different behavior to interoperate together
*   
*/




require_once('rdf_Alt.php');
require_once('rdf_first.php');
require_once('rdf_object.php');
require_once('rdf_Property.php');
require_once('rdf_Statement.php');
require_once('rdf_type.php');
require_once('rdf_Bag.php');
require_once('rdf_List.php');
require_once('rdf_rest.php');
require_once('rdf_subject.php');
require_once('rdf_value.php');
require_once('rdf_RDF.php');
require_once('rdf_Description.php');
require_once('rdf_about.php');
require_once('rdf_resource.php');
require_once('rdf_ID.php');
class RDF_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function __construct()
	{
		
			$this->nativ = new RDF_RDF();
			$this->node['RDF'] = new RDF_RDF();
			$this->node['Alt'] = new RDF_Alt();
			$this->node['first'] = new RDF_first();
			$this->node['object'] = new RDF_object();
			$this->node['Property'] = new RDF_Property();
			$this->node['Statement'] = new RDF_Statement();
			$this->node['type'] = new RDF_type();
			$this->node['Bag'] = new RDF_Bag();
			$this->node['rest'] = new RDF_rest();
			$this->node['subject'] = new RDF_subject();
			$this->node['value'] = new RDF_value();
			//$this->node['domain'] = new RDF_value();
			$this->node['Description'] = new RDF_description();
			$this->node['about'] = new RDF_about();
			$this->node['ID'] = new RDF_ID();
			$this->node['resource'] = new RDF_resource();
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
