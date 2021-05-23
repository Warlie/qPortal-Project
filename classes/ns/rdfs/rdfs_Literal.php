<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class RDFS_Literal extends Interface_node
{

function &get_Instance()
{
return new RDFS_Literal();
}
	
public function __toString()
	{
	return $this->getdata();
	}
	
}

?>
