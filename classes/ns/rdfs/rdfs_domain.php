<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class RDFS_domain extends Interface_node
{

	
	
function &get_Instance()
{
return new RDFS_domain();
}
	// alter parameter, original was event_attribute($name,&$attributeref,&$message)
	function event_attribute($name,&$message)
	{
		if('rdf:resource' == $name)
		{
			if($obj = &$this->getRefprev())
			{
				//echo '<hr>rdfs:domain<hr>';
				$obj->set_to_in($message);
				$message->set_to_out($obj);
				
				
				//echo $message->name . ' ' . get_class($message) . '-----------------<br>';
			}
			
			//set_to_out(&$obj)
		
		}
	}


}

?>
