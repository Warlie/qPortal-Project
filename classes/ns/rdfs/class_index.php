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
			$this->node['Resource'] = new RDFS_Resource('Resource','http://www.w3.org/2000/01/rdf-schema');
			$this->node['Literal'] = new RDFS_Literal('Literal','http://www.w3.org/2000/01/rdf-schema');
			$this->node['Class'] = new RDFS_Class('Class','http://www.w3.org/2000/01/rdf-schema');
			$this->node['Datatype'] = new RDFS_Datatype('ResourceDatatype','http://www.w3.org/2000/01/rdf-schema');
			$this->node['Container'] = new RDFS_Container('Container','http://www.w3.org/2000/01/rdf-schema');
			$this->node['ContainerMembershipProperty'] = new RDFS_ContainerMembershipProperty('ContainerMembershipProperty','http://www.w3.org/2000/01/rdf-schema');
			$this->node['subClassOf'] = new RDFS_subClassOf('subClassOf','http://www.w3.org/2000/01/rdf-schema');
			$this->node['subPropertyOf'] = new RDFS_subPropertyOf('subPropertyOf','http://www.w3.org/2000/01/rdf-schema');
			$this->node['domain'] = new RDFS_domain('domain','http://www.w3.org/2000/01/rdf-schema');
			$this->node['range'] = new RDFS_range('range','http://www.w3.org/2000/01/rdf-schema');
			$this->node['label'] = new RDFS_label('label','http://www.w3.org/2000/01/rdf-schema');
			$this->node['comment'] = new RDFS_comment('comment','http://www.w3.org/2000/01/rdf-schema');
			$this->node['member'] = new RDFS_member('member','http://www.w3.org/2000/01/rdf-schema');
			$this->node['seeAlso'] = new RDFS_seeAlso('seeAlso','http://www.w3.org/2000/01/rdf-schema');
			$this->node['isDefinedBy'] = new RDFS_isDefinedBy('isDefinedBy','http://www.w3.org/2000/01/rdf-schema');

			
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
