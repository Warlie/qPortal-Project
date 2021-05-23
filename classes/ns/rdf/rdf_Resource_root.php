<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class RDF_Resource extends Interface_node
{
function &get_Instance()
{
return new RDF_Resource();
}



	function event_initiated()
	{
	//$this->to_listener();
	}	
	
	function event_message_in($type,&$obj)
	{
		
	}
	
	

/*
	
	//wird bei der initialisierung aufgerufen
	function start()
	{
		$this->to_listener();
	}
	//wird bei u.a. beim Ende des Parsens aufgerufen
	function event($event = '')
	{
		if('complete'==$event)
			{
				
						    if(!(false === ($tmp = strpos($this->value,'#'))))
							{
				
								$prefix = substr(strtolower($this->value),0,$tmp);
								$attribname = substr(strtolower($this->value),$tmp + 1);
								
								if('' <> trim($attribname))
								{
									$obj = &$this->back->parser->namespace_frameworks[$prefix]['node'][$attribname];
									//echo $this->back->name;
									
									$this->back->event_attribute($this->name,&$this,&$obj);
								}
							}
								
				//
				
				//
			}
	}
	*/
		
}

?>
