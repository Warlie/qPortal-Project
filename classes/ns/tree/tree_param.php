<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class TREE_param extends Interface_node
{


function &get_Instance()
{
return new TREE_param();
}


function &new_Instance()
{
                                
				$obj = $this->get_Instance();
				
				$obj->link_to_class = &$this;
				
				return $obj;
}


}

?>
