<?php

/**  Aufstellung der functionen des XML Literal
* full_URI() : Gives out full Namespace with nodetype delimited with #
* get_NS() : Gives out nodetype
* get_QName() : Gives out Namespace
* position_stamp() : standard position stamp based on the index of spezific tree
* exhaustion() : (needs to be updated) removes all branches for this object
* &getRefnext($index,$bool_set=false) :get child nodes
* &getRefprev() : gets parent node
* index_max() : get many of child nodes
* setRefnext(&$ref) :add new child nodes 
* setRefprev(&$ref) :add parent nodes
* set_parser(&$obj) :add parserobject
* get_parser() :get parser
* attribute($name,(<String>||<Interface_node>)&$value) :sets attributes 
* get_attribute($name = '') : gets an attibutes value
* setdata($data,$pos = null) : sets a String or number to data
* function set_bolcdata($bool) : en/disables cdata notation
* get_bolcdata() : show setting
* final_data() : internal value, shows that an node is complete
* &linkToClass() : ref to classobject
* -------------behavior----------------
* complete() : become called, when node has been complete, calls "event_initiated"
* event($type,&$obj) : works like a dispenser and distributes all incomming events to spezific eventfunctions 
* send_messages($type,&$obj) : standardcommandfunction for extended classes, sends messages as an event to "event_message_in" and "event_attribute"
* event_initiated() : is called by finishing node
* event_parseComplete() : is called by finishing tree
* event_Instance(&$instance,$type,&$obj) : (works actually only for next upperclass) request behavior of an upperclass for an instance, called for an event
* event_attribute($name,&$message) : attribute is called, when an Node has listed as an attribute, needs call "to_check_list" when it has to get an event
* event_message_check($type,&$obj) : is a function in a part of a chain of "send_messages", it asks all attribute nodes of an node
* event_message_in($type,&$obj) : standardevent, gets an Eventobject and a type of event, most ''
* set_to_out(&$obj) : parameter becomes listed to nodes gets an event by outgoing by "send_message"
* set_to_check(&$obj) : parameter becommes listed to nodes, see an event, before it has be seen by the node
* to_listener() : easy way to be listed to parentnode
* deprecated : event_check($type,$bool,&$obj) would be not nessesary because of event_attribute()
* to_check_list() : add to checklist node (for attributes)
* &get_Instance() // a simple row instance
* &new_Instance() //advanced instance with connection to classobject and could be a subtree
* &cloning(&$prev_obj) : add node with all branches to the prev node
* addToTicketEvent($eventOnFullNS , Interface_node &$listener)
* TicketEvent(&$ticketObject)
* linkToInstance($pos) :get link to Instance- object
* linkToClass() : get link to class-objekt
* ManyInstance(): show many Instance-objects
* is_Class(): selects between classes and instances
*/

require_once('factory_interface.php');

class PEDL_Object_Constructor extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new PEDL_Object_Constructor();
}


function &new_Instance()
{
                                
				$obj = $this->get_Instance();
				
				$obj->link_to_class = &$this;
				
				return $obj;
}

//primar call after finishing object, ther wont be an existing childnode
function event_initiated()
{

}

function event_Instance(&$instance,$type,&$obj)
{

	
	$parser = &$this->get_parser();

	//wrong request
	if($this->index_max() > 0)
	{
		
	$new_stamp =  '0000.' . $instance->get_idx() . $instance->position_stamp();
	$res = array();
	$go = false;
	$all_names = array();
	//echo $instance->full_URI() . "<br>\n";
	//goes to parametercontainer
	for($j = 0; $instance->index_max() > $j;$j++)
			if($instance->getRefnext($j)->is_Node('http://www.w3.org/2006/05/pedl-lib#hasParameter'))
			{
				$instance = $instance->getRefnext($j);
				for($k = 0; $instance->index_max() > $k;$k++)
				if($instance->getRefnext($k)->is_Node('http://www.w3.org/2006/05/pedl-lib#ParameterCollection'))
				{
				$instance = $instance->getRefnext($k);
				
				}
			}
	//collects all Parameternames
	for($j = 0; $instance->index_max() > $j;$j++)
	{
	
		if($instance->getRefnext($j)->is_Node('http://www.w3.org/2006/05/pedl-lib#Object_Parameter'))
			{
				$all_names[count($all_names)] = $instance->getRefnext($j)->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#name');
			}
	}		
	//	
	for($i = 0; $this->index_max() > $i;$i++)
		{
			
			if($this->getRefnext($i)->is_Node('http://www.w3.org/2006/05/pedl-lib#Object_Parameter'))
				{
					$go = true;
					$func = &$this->getRefnext($i);
					$function_name = $func->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#ID');
					$pedl_name = $func->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#name');
					if(!in_array($pedl_name,$all_names))
					{
					$attrib2 = array('pedl:name' => $pedl_name);
					if(!$parser->create_Ns_Node($function_name,$stamp,$attrib2))
					throw new ErrorException('Cannot instancing following class:' . $function_name . ' ', 0,75,"pedl_Object_Construktor",119); //$value->getParam_name()
					}
					if($func->data_many() > 0)$parser->show_xmlelement()->setdata($func->getdata(0),0);
					//echo $function_name . ' "' . $pedl_name . '" ' . $func->get_QName() . ' ' . $func->data_many() . ' <br>';
					$parser->parent_node();
					unset($func);
					unset($tmp);
				}
				
		
		}
		
		
		foreach($res as $key => $value)
			{
				
					$attrib = array('pedl:name' => $value->pedl_name());
					if(!$parser->create_Ns_Node($value->q_name(),$new_stamp,$attrib))
					{
					
					
						throw new ErrorException('Cannot instancing following class:' . $value->q_name() . ' ', 0,75,$parser->getControlUnit( "surface_tree_engine")->getSystemSpace(),1);
					}
					
					if($value->has_param())
					{
						
						$stamp = $parser->position_stamp();
						do
						{
							$attrib2 = array('pedl:name' => $value->getParam_pedl_name());
							
							if(!$parser->create_Ns_Node($value->getParam_name(),$stamp,$attrib2))
								throw new ErrorException('Cannot instancing following class:' . $value->getParam_name() . ' ', 0,75,"pedl_Object_Class",132);
					
							
						}
						while($value->pop());
						
						
					}
					
			}
	
	
	}
	if(!is_null($this->getRefprev()) &&(get_class($this->getRefprev()) == 'PEDL_Object_Class'))
	{
	//echo "pedl_Object_Constructor";
	//if(get_class($this->getRefprev()) == 'PEDL_Object_Class' && $this->getRefprev()->is_executive() && false)
	//{
	//echo get_class($this->getRefprev());
	//caution it is important to save own instance first.
	//otherweise PEDL-Class-Object is not able to connect instance of descripted class
	$instance->set_to_out($instance);
	
	//$this->set_to_out();
	$instance->send_messages('http://www.w3.org/2006/05/pedl-lib#Object_Constructor?construct',new EventObject('',$this,$booh));
	}
	
	
	//fires event
	
	//if(get_class($this->getRefprev()) == 'PEDL_Object_Class' )echo 'jo';
	
}	

function event_message_in($type,&$obj)
	{
		//echo $type . 'wohooo';
		if($this->is_Command($type,'construct'))$this->event_activate_Construktor($type,$obj);

	}

protected function event_activate_Construktor($type,&$obj)
{

		if('' == ($this->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#ID')))
		$this->create_factory_class();
}

	
private function create_factory_class()
{
	//echo $this->get_QName();
	$all_values = array();
	
	for($j = 0; $this->index_max() > $j;$j++)
	{
		//echo 'booh';
		if($this->getRefnext($j)->is_Node('http://www.w3.org/2006/05/pedl-lib#Object_Parameter'))
			{
				//echo '-' . gettype($this->getRefnext($j)->getdata(0)) . '-';
				if(is_Object($this->getRefnext($j)->getdata(0)))
				{
				//echo 'booh';
				$all_values[count($all_values)] = &$this->getRefnext($j)->getdata(0);
				//echo get_Class($this->getRefnext($j)->getdata(0));
				}
				//echo $this->getRefnext($j)->getdata(0) . ' istdrin';
			}
	}			
	
	$carrier = array();
	//echo $this->getRefprev()->get_QName() . " is a class!\n";
	
	//echo $this->get_parser()->getControlUnit( "surface_tree_engine")->getSystemSpace();
	$this->get_parser()->change_URI('@registry_surface_system');
	
	//echo $this->get_parser()->save_Stream('UTF-8',false);
	//print_r(get_declared_classes());
	//echo "-" . $this->getRefprev()->get_QName() . "-";
	//try{
	$carrier[0] = new ReflectionClass($this->getRefprev()->get_QName());
	//}
	//catch (Exception $e) {
    //echo 'Caught exception: ',  $e->getMessage(), "\n";
    //return;
    //}
	//echo $carrier[0]->getName() . " is a class!\n";
	//echo 'call_Instance';
	//echo count($all_values) . "-";
	
	//try {
	$carrier[1] = $carrier[0]->newInstanceArgs($all_values);
	//} catch (Exception $e) {
    	//echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
    	//}
	
    //var_dump($carrier);
    
	if($this->getRefprev()->is_Node('http://www.w3.org/2006/05/pedl-lib#Object_Class'))
	{
		$obj = &$this->getRefprev();
		$obj->setobj($carrier);
		
	}


}
}

?>
