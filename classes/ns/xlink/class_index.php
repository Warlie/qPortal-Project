<?php

/**  Contains various namespaces with different behavior to interoperate together
*   PEDL (Programm Element Definition Lanuage)
*   	to peddle
*/




require_once('xlink_href.php');

class XLINK_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function __construct($ns)
	{

			$this->nativ = new RDF_RDF();
			$this->node['href'] = new XLINK_href('href', $ns);
			
			$this->nativ->set_is_Class();
			foreach ($this->node as $value)
				$value->set_is_Class();

			
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
