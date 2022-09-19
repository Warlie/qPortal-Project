<?php

/**  Contains various handles to parse documents
*   
*/

require_once('Interface_handle.php');
require_once('XML_handle.php');
require_once('CSV_handle.php');
require_once('JSON_handle.php');
require_once('PHP_handle.php');
require_once('SVG_Overview_handle.php');

class My_Handle_factory 
{

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
			case 'SVG_OVERVIEW' :
			return new SVG_Overview_handle();
			default:
			throw new ErrorException('There is no "' .  strtoupper($type) . '";');
		}
	}
	
	
}

?>
