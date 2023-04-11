<?php

/**  Contains various namespaces with different behavior to interoperate together
*   
*/




//require_once('rdfs_Resource.php');
require_once('owl_Ontology.php');
require_once('owl_Class.php');   
require_once('owl_imports.php');
require_once('owl_DatatypeProperty.php');
//require_once('rdfs_Datatype.php');    
//require_once('rdfs_Container.php');
//require_once('rdfs_ContainerMembershipProperty.php');
//require_once('rdfs_subClassOf.php');
//require_once('rdfs_subPropertyOf.php');
//require_once('rdfs_domain.php');
//require_once('rdfs_range.php');
//require_once('rdfs_label.php');
//require_once('rdfs_comment.php');
//require_once('rdfs_member.php');
//require_once('rdfs_seeAlso.php');
//require_once('rdfs_isDefinedBy.php');

class OWL_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function __construct()
	{

			$this->nativ = new OWL_Ontology();
			$this->node['Ontology'] = new OWL_Ontology();
			$this->node['Class'] = new OWL_Class();
			$this->node['imports'] = new OWL_imports();
			$this->node['DatatypeProperty'] = new OWL_DatatypeProperty();
			//$this->node['literal'] = new RDFS_Literal();
			//$this->node['class'] = new RDFS_Class();
			//$this->node['datatype'] = new RDFS_Datatype();
			//$this->node['container'] = new RDFS_Container();
			//$this->node['caontainermembershipproperty'] = new RDFS_ContainerMembershipProperty();
			//$this->node['subclassof'] = new RDFS_subClassOf();
			//$this->node['subpropertyof'] = new RDFS_subPropertyOf();
			//$this->node['domain'] = new RDFS_domain();
			//$this->node['range'] = new RDFS_range();
			//$this->node['label'] = new RDFS_label();
			//$this->node['comment'] = new RDFS_comment();
			//$this->node['member'] = new RDFS_member();
			//$this->node['seealso'] = new RDFS_seeAlso();
			//$this->node['isdefinedby'] = new RDFS_isDefinedBy();

			
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
			$this->attrib = null;
			
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
