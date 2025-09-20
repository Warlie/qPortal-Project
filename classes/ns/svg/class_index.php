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
	
	function __construct($ns)
	{

			$this->nativ = new RDF_RDF('svg', $ns);
			$this->node['image'] = new SVG_image('image', $ns);
			$this->node['width'] = new SVG_width('width', $ns);
			$this->node['height'] = new SVG_height('height', $ns);

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
