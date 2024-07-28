<?php

/**  Aufstellung der functionen des XML Literal
*   
*/

class TREE_variable extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function event_initiated()
{


	$this->to_listener();
	$this->set_to_out($this->getRefprev());

}

function &get_Instance()
{
return new TREE_variable();
}


function &new_Instance()
{
                                
				$obj = $this->get_Instance();
				
				$obj->link_to_class = &$this;
				
				return $obj;
}

function complete()
	{
		parent::complete();

	}

function event_message_in($type,&$obj)
	{

		//echo "booho";
			$send = ["Identifire"=>"*", "Command"=> ["Name"=> '__insert_data', "Attribute"=>[], "Value"=> $this->posInPrev() + 1 ]];

			$booh = $this->getdata(0);
			
			$Event = new EventObject('',$this,$booh);

			
			$this->send_messages($send,$Event); 
		
	//echo $type . ' ' . get_Class($obj);
	}
}

?>
