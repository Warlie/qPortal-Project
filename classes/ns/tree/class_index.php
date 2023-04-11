<?php

/**  Contains various namespaces with different behavior to interoperate together
*   
*/


require_once('tree_indextree.php');
require_once('tree_tree.php');
require_once('tree_content.php');
require_once('tree_program.php');
require_once('tree_template.php');
require_once('tree_main.php');
require_once('tree_add.php');
require_once('tree_element.php');
require_once('tree_object.php');
require_once('tree_param.php');
require_once('tree_remote.php');
require_once('tree_workspace.php');
require_once('tree_SPARQL.php');
require_once('tree_xpath.php');
require_once('tree_document.php');
require_once('tree_header.php');
/*
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
require_once('rdf_description.php');
require_once('rdf_about.php');
require_once('rdf_resource.php');
*/
class TREE_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function __construct()
	{
		
			$this->nativ = new TREE_INDEXTREE();
			$this->node['indextree'] = new TREE_INDEXTREE();
			$this->node['final'] = new TREE_tree();
			$this->node['tree'] = new TREE_tree();
			$this->node['template'] = new TREE_template();
			$this->node['main'] = new TREE_main();
			$this->node['add'] = new TREE_add();
			$this->node['program'] = new TREE_program();
			$this->node['content'] = new TREE_content();
			$this->node['element'] = new TREE_element();
			$this->node['object'] = new TREE_object();
			$this->node['param'] = new TREE_param();
			$this->node['remote'] = new TREE_remote();
			$this->node['workspace'] = new TREE_workspace();
			$this->node['sparql'] = new TREE_SPARQL();
			$this->node['xpath'] = new TREE_xpath();
			$this->node['document'] = new TREE_document('document','http://www.trscript.de/tree');
			$this->node['header'] = new TREE_header('header','http://www.trscript.de/tree');
			
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
