<?php

/**  Contains various namespaces with different behavior to interoperate together
*   PEDL (Programm Element Definition Lanuage)
*   	to peddle
*/




require_once('ate_isDisplayed2DImage.php');
require_once('ate_ATE.php');


class Anttree_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function __construct($ns)
	{

			$this->nativ = new ATE_ATE();

			$this->node['isDisplayed2DImage'] = new ATE_isDisplayed2DImage('isDisplayed2DImage', $ns);

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
