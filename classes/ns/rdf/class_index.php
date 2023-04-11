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
		
			$this->nativ = new RDF_RDF('rdf','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['RDF'] = new RDF_RDF('RDF','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['Alt'] = new RDF_Alt('Alt','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['first'] = new RDF_first('first','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['object'] = new RDF_object('object','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['Property'] = new RDF_Property('Property','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['Statement'] = new RDF_Statement('Statement','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['type'] = new RDF_type('type','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['Bag'] = new RDF_Bag('Bag','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['rest'] = new RDF_rest('rest','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['subject'] = new RDF_subject('subject','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['value'] = new RDF_value('value','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			//$this->node['domain'] = new RDF_value();
			$this->node['Description'] = new RDF_description('Description','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['about'] = new RDF_about('about','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['ID'] = new RDF_ID('id','http://www.w3.org/1999/02/22-rdf-syntax-ns');
			$this->node['resource'] = new RDF_resource('resource','http://www.w3.org/1999/02/22-rdf-syntax-ns');
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
