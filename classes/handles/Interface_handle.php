<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class Interface_handle 
{
	var $attribute_values = array();
	var $base_object = null;

	
	function check_format($example)
	{
		return false;
	}
	
	/**
	/*
	/* XML_OPTION_CASE_FOLDING : {0,1}
	*/
	function set_attribute($key,$value)
	{
		$this->attribute_values[$key] = $value;

	}
	
	function &get_attribute()
	{
		return $this->attribute_values;
	}
	
	
	function set_object(&$obj)
	{
		$this->base_object = &$obj;
	}
	
	function parse_document(&$source)
	{
	
	}
	
	function save_back($format,$send_header = false)
	{
		return '';
	}
	
	function save_stream_back(&$stream, $format,$send_header = false)
	{
		return '';
	}
	
function send_header()
{

					header("Content-type: text/html");

}
		
}

?>
