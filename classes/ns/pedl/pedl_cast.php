<?php

/**  Aufstellung der functionen des XML Literal
*  	$value;
* 	$name;
*	$back;

	function object_back($obj)
	function out()
	function in($in)
	function to_listener()
	function start()
	function event($event = '')
	function &get_Instance()
*/

class PEDL_cast extends Interface_attrib
{
function &get_Instance()
{
return new PEDL_cast();
}
	function start()
	{
	$this->to_check_list();
	}	
	
	function check($type,$bool,&$obj)
	{
		$arg = strtolower(trim(implode($obj->data,'')));
		
		switch (strtolower($this->value))
		{
			case 'boolean':
				
				
				
				if(strtolower($arg) == 'ja' || strtolower($arg) == 'true' || strtolower($arg) == '1')
				{
					
				$obj->data = array();
				$obj->data[0] = 1;
				return true;
				}
				
				if(strtolower($arg) == 'nein' || strtolower($arg) == 'false' || strtolower($arg) == '0')
				{
					
				$obj->data = array();
				$obj->data[0] = 0;
				return true;
				}
				
				//if(is_bool($arg))return true;
				
				//$obj->data[0] = false;
				return true;
			case 'string':
				if(is_String($arg))return true;
				
				
		}

		return true;
	
	}
}

?>
