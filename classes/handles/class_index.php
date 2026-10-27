<?php

/**  Contains various handles to parse documents
*   
*/

require_once('Interface_handle.php');
require_once('XML_handle.php');
require_once('CSV_handle.php');
require_once('RAW_handle.php');
require_once('JSON_handle.php');
require_once('YAML_handle.php');
require_once('PHP_handle.php');
require_once('SVG_Overview_handle.php');
require_once('XML_handle_registry_output.php');
require_once('SPARQL_handle.php');
require_once('Turtle_handle.php');

class My_Handle_factory
{
	/* Die Argumente am doctype: "PHP;TARGET:TREE;MODUS:X". Links vom ersten ';' steht der
	*  Handle, danach Paare KEY:VALUE. Zerlegt wird NUR hier - handle_select setzt sie dem
	*  Handle, load() fragt den Handle damit, wohin er schreibt (3.2.1). Eine zweite Lesart
	*  liefe auseinander.
	*
	*  ⚠ Bestand, nicht geaendert: Schluessel UND Wert werden grossgeschrieben, und am ':'
	*  wird ohne Grenze geteilt - ein Wert mit Doppelpunkt (URI) kommt abgeschnitten an.
	*  CSV liest die Werte so aus PARAMETER. Ein leerer Typ ist XML, wie in handle_select.
	*  NAMESPACES(...) ist kein Paar und bleibt bei handle_select. */
	public static function special_split($special)
	{
		$teile = explode(';', (string) $special);
		$typ   = strtoupper(trim(array_shift($teile)));
		$args  = [];

		foreach($teile as $teil)
		{
			if(false !== strpos(strtoupper($teil), 'NAMESPACES')) continue;

			[$key, $value] = self::special_pair($teil);
			$args[$key] = $value;
		}

		return [('' === $typ) ? 'XML' : $typ, $args];
	}

	public static function special_pair($teil)
	{
		$pair = explode(':', $teil);
		return [strtoupper($pair[0]), strtoupper($pair[1] ?? '')];
	}


	public static function &handle_factory($type)
	{
		if(!(false === ($tmp = strpos($type , ';'))))
		{
			
			$type = substr($type,0,strpos($type , ';'));
			
		}
		

		switch(strtoupper($type))
		{
			case 'XML' :
			return new XML_handle();
			case 'CSV' :
			return new CSV_handle();
			case 'PHP' :
			return new PHP_handle();
			case 'JSON' :
			return new JSON_handle();
			case 'YAML' :
			return new YAML_handle();
			case 'RAW' :
			return new RAW_handle();
			case 'SVG_OVERVIEW' :
			return new SVG_Overview_handle();
			case 'REGISTRY' :
			return new XML_handle_registry();
			case 'SPARQL' :
			return new SPARQL_handle();
			case 'TURTLE' :
			return new Turtle_handle();
			default:
			throw new ErrorException('There is no "' .  strtoupper($type) . '";');
		}
	}
	
	
}

?>
