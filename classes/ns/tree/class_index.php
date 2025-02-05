<?php

/**  Contains various namespaces with different behavior to interoperate together
*   
*/


require_once('tree_indextree.php');
require_once('tree_tree.php');
require_once('tree_sub.php');
require_once('tree_addtree.php');
require_once('tree_content.php');
require_once('tree_program.php');
require_once('tree_first.php');
require_once('tree_variable.php');
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
	
	function __construct($ns)
	{
		
			$this->nativ = new TREE_INDEXTREE('indextree', $ns);
			$this->node['indextree'] = new TREE_INDEXTREE('indextree', $ns);
			$this->node['final'] = new TREE_tree('final', $ns);
			$this->node['tree'] = new TREE_tree('tree', $ns);
			$this->node['sub'] = new TREE_sub('sub', $ns);
			$this->node['subtree'] = new TREE_subtree('subtree', $ns);
			$this->node['template'] = new TREE_template('template', $ns);
			$this->node['main'] = new TREE_main('main', $ns);
			$this->node['add'] = new TREE_add('add', $ns);
			$this->node['program'] = new TREE_program('program', $ns);
			$this->node['first'] = new TREE_first('first', $ns);
			$this->node['variable'] = new TREE_variable('variable', $ns);
			$this->node['content'] = new TREE_content('content', $ns);
			$this->node['element'] = new TREE_element('element', $ns);
			$this->node['object'] = new TREE_object('object', $ns);
			$this->node['param'] = new TREE_param('param', $ns);
			$this->node['remote'] = new TREE_remote('remote', $ns);
			$this->node['workspace'] = new TREE_workspace('workspace', $ns);
			$this->node['sparql'] = new TREE_SPARQL('sparql', $ns);
			$this->node['xpath'] = new TREE_xpath('xpath', $ns);
			$this->node['document'] = new TREE_document('document', $ns);
			$this->node['header'] = new TREE_header('header', $ns);
			$this->node['id'] = new Interface_node('id', $ns);
			
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
