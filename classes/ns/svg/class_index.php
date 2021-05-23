<?php

/**  Contains various namespaces with different behavior to interoperate together
*   PEDL (Programm Element Definition Lanuage)
*   	to peddle
*/




require_once('svg_image.php');
require_once('svg_width.php');
require_once('svg_height.php');


class SVG_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function __construct()
	{

			$this->nativ = new RDF_RDF();
			$this->node['image'] = new SVG_image();
			$this->node['width'] = new SVG_width();
			$this->node['height'] = new SVG_height();

			
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
