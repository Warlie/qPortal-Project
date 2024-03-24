<?php

/**  Contains various namespaces with different behavior to interoperate together
*   PEDL (Programm Element Definition Lanuage)
*   	to peddle
* TODO hier geht es weiter
*/



require_once('xs_schema.php');
require_once('xs_String.php');


class XS_factory 
{

	var $nativ;
	var $node = array();
	var $attrib;
	
	function __construct($ns)
	{

			$this->nativ = new XS_schema('schema','http://www.w3.org/2001/XMLSchema');
			
			$this->node['string'] = new XS_String('string','http://www.w3.org/2001/XMLSchema');
			/*
			$this->node['boolean'] = new Interface_node();
			$this->node['height'] = new Interface_node();
			$this->node['boolean'] = new Interface_node();
			$this->node['decimal'] = new Interface_node();
			$this->node['integer'] = new Interface_node();
			$this->node['double'] = new Interface_node();
			$this->node['float'] = new Interface_node();
			$this->node['date'] = new Interface_node();
			$this->node['time'] = new Interface_node();
			$this->node['dateTime'] = new Interface_node();
			$this->node['dateTimeStamp'] = new Interface_node();
			$this->node['gYear'] = new Interface_node();
			$this->node['gMonth'] = new Interface_node();
			$this->node['gDay'] = new Interface_node();
			$this->node['gYearMonth'] = new Interface_node();
			$this->node['gMonthDay'] = new Interface_node();
			$this->node['duration'] = new Interface_node();
			$this->node['yearMonthDuration'] = new Interface_node();
			$this->node['dayTimeDuration'] = new Interface_node();
			$this->node['byte'] = new Interface_node();
			$this->node['short'] = new Interface_node();
			$this->node['int'] = new Interface_node();
			$this->node['long'] = new Interface_node();
			$this->node['unsignedByte'] = new Interface_node();
			$this->node['unsignedShort'] = new Interface_node();
			$this->node['unsignedInt'] = new Interface_node();
			$this->node['unsignedLong'] = new Interface_node();
			$this->node['positiveInteger'] = new Interface_node();
			$this->node['nonNegativeInteger'] = new Interface_node();
			$this->node['negativeInteger'] = new Interface_node();
			$this->node['nonPositiveInteger'] = new Interface_node();
			$this->node['hexBinary'] = new Interface_node();
			$this->node['base64Binary'] = new Interface_node();
			$this->node['anyURI'] = new Interface_node();
			$this->node['language'] = new Interface_node();
			$this->node['normalizedString'] = new Interface_node();
			$this->node['token'] = new Interface_node();
			$this->node['NMTOKEN'] = new Interface_node();
			$this->node['Name'] = new Interface_node();
			$this->node['NCName'] = new Interface_node();
*/
			$this->nativ->set_is_Class();
			foreach ($this->node as   $value)
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