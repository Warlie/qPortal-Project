<?php

/**
*arbiter
*
* runs thought xmltree to interpret 
*
* (C) Stefan Wegerhoff
*/

class Arbiter{
	
	var $Contentgenerator = null;
	var $xml_element = null;
	
	function Arbiter( $ContentGenerator, $xml_element )
	{
		if(!($ContentGenerator instanceof Contentgenerator))
		{
			echo "error arbiter gets wrong object for contentgenerator";
		}
		else
		{
			if(!($xml_element instanceof XMLelement_objex))
			{
				echo "error arbiter gets wrong object for contentgenerator";
			}else
			{
				$this->Contentgenerator = $ContentGenerator;
				$this->xml_element = $xml_element;
				$this->start();
			}
		}
	}
	
	function start()
	{

	}
}

?>
