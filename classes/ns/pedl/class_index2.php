<?php

/**  Contains various namespaces with different behavior to interoperate together
*   PEDL (Programm Element Definition Lanuage)
*   	to peddle
*/




require_once('pedl_Create_Alt_Field.php');
require_once('pedl_Convert.php');
require_once('pedl_Script.php');
require_once('pedl_name.php');
require_once('pedl_cast.php');
require_once('pedl_Object_Class.php');

class PEDL_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function PEDL_factory()
	{
		
			$this->nativ = new RDF_RDF();
			$this->node['create_alt_field'] = new PEDL_Create_Alt_Field();
			$this->node['convert'] = new PEDL_Convert();
			$this->node['script'] = new PEDL_Script();
			


			
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
			$this->attrib['name'] = new PEDL_Name();
			$this->attrib['cast'] = new PEDL_cast();
			
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
