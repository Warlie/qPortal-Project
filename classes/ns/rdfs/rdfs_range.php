<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class RDFS_range extends Interface_node
{

function &get_Instance()
{
return new RDFS_range();
}
	// alter parameter, original was event_attribute($name,&$attributeref,&$message)
	function event_attribute($name,&$message)
	{
		if('rdf:resource' == $name)
		{
			if($obj = &$this->getRefprev())
			{
				$obj->set_to_out($message);
				
				//echo $name . get_class($message);
			}
			
			//set_to_out(&$obj)
		
		}
	}
}

?>
