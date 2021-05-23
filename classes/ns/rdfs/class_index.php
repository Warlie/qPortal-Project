<?php

/**  Contains various namespaces with different behavior to interoperate together
*   
*/




require_once('rdfs_Resource.php');
require_once('rdfs_Literal.php');
require_once('rdfs_Class.php');   
require_once('rdfs_Datatype.php');    
require_once('rdfs_Container.php');
require_once('rdfs_ContainerMembershipProperty.php');
require_once('rdfs_subClassOf.php');
require_once('rdfs_subPropertyOf.php');
require_once('rdfs_domain.php');
require_once('rdfs_range.php');
require_once('rdfs_label.php');
require_once('rdfs_comment.php');
require_once('rdfs_member.php');
require_once('rdfs_seeAlso.php');
require_once('rdfs_isDefinedBy.php');

class RDFS_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function __construct()
	{
		
			$this->nativ = new RDF_RDF();
			$this->node['Resource'] = new RDFS_Resource();
			$this->node['Literal'] = new RDFS_Literal();
			$this->node['Class'] = new RDFS_Class();
			$this->node['Datatype'] = new RDFS_Datatype();
			$this->node['Container'] = new RDFS_Container();
			$this->node['ContainerMembershipProperty'] = new RDFS_ContainerMembershipProperty();
			$this->node['subClassOf'] = new RDFS_subClassOf();
			$this->node['subPropertyOf'] = new RDFS_subPropertyOf();
			$this->node['domain'] = new RDFS_domain();
			$this->node['range'] = new RDFS_range();
			$this->node['label'] = new RDFS_label();
			$this->node['comment'] = new RDFS_comment();
			$this->node['member'] = new RDFS_member();
			$this->node['seeAlso'] = new RDFS_seeAlso();
			$this->node['isDefinedBy'] = new RDFS_isDefinedBy();

			
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
