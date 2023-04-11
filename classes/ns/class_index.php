<?php

/**  Contains various namespaces with different behavior to interoperate together
*   
*/

require_once('Interface_ns.php');
require_once('rdf/class_index.php');
require_once('rdfs/class_index.php');
require_once('pedl/class_index.php');
require_once('tree/class_index.php');
require_once('owl/class_index.php');
require_once('ate/class_index.php');
require_once('svg/class_index.php');
require_once('xlink/class_index.php');
require_once('xs/class_index.php');

class My_NameSpace_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	public static function &namespace_factory($ns)
	{
		$carry = array();
		$ns = str_replace('#','',$ns);
		
		switch($ns)
		{
			
			case 'http://www.w3.org/1999/02/22-rdf-syntax-ns' :
			
			return new RDF_factory();
			
			case 'http://www.w3.org/2000/01/rdf-schema':
			return new RDFS_factory();
			
			case 'http://www.w3.org/2006/05/pedl-lib':
			return new PEDL_factory();
			
			case 'http://www.trscript.de/tree':
			return new TREE_factory();
			
			case 'http://www.w3.org/2002/07/owl':
			return new OWL_factory();
			
			case 'http://www.auster-gmbh.de/2010/08/anttree-lib':
			return new Anttree_factory();
			
			case 'http://www.w3.org/2000/svg' :
			return new SVG_factory();
			
			case 'http://www.w3.org/1998/Math/MathML' :
			return new SVG_factory();
			
			case 'http://www.w3.org/1999/xlink' :
			return new XLINK_factory ();
			
			case 'http://www.w3.org/2001/XMLSchema' :
			return new XS_factory ();
			
			default :
			

			
			
			return new My_NameSpace_factory();
		}
	}
	
	//wenn keine namespaces gefunden wurden
	public static function &alt_namespace_factory()
	{
		
			return new Interface_node();

		
	}
	
	public function __construct()
	{
		$this->nativ = new Interface_node();
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
