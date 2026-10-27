<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class Interface_handle 
{
	var $attribute_values = array();
	var $base_object = null;
	protected $schema = false;

	
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
	
	function set_Schema($path)
	{
		$this->schema = $path;
	}
	
	function parse_document($source)
	{

	}

	/* Wohin der Handle schreibt, gefragt von xml::load() VOR dem Laden (3.2.2):
	*  'tree' (eigener Baum), 'registry' (Register der surface_tree_engine) oder null -
	*  keine Meinung, dann gilt die Pfadregel des Bestands (.php ins Register).
	*  $args sind die Argumente am doctype (My_Handle_factory::special_split). */
	function destination(array $args)
	{
		return null;
	}

	/* Legt den eigenen Baum an, bevor geladen wird. false = nichts getan, dann reserviert
	*  load() den Platz wie bisher mit setNewTree und der erste Tag wird die Wurzel. */
	function prepare(&$parser, $ident, array $args)
	{
		return false;
	}

	function parse_document_from_array(array $data): void
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
