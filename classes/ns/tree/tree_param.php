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

protected function event_readdata($own)
{ 
	if (!is_array($this->data)) return;
	foreach ($this->data as &$value)
		if (is_string($value))
			 $value = preg_replace_callback('/%(\w+)/',
				fn($m) => CONSTANTS[$m[1]] ?? $m[0], $value);

}

}

?>
