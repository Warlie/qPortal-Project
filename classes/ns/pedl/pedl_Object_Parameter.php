<?php

/**  Aufstellung der functionen des XML Literal
* full_URI() : Gives out full Namespace with nodetype delimited with #
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
* getdata($pos=null) : 
* function set_bolcdata($bool) : en/disables cdata notation
* get_bolcdata() : show setting
* final_data() : internal value, shows that an node is complete
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
*/

class PEDL_Object_Parameter extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';


function &get_Instance()
{
return new PEDL_Object_Parameter();
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

	if($pedl_name = $this->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#refersTo'))
	{
		//echo $pedl_name . "\n";
		//echo  get_class($this->setPresetValues($pedl_name))  . "\n";
		$this->setdata($this->setPresetValues($pedl_name), 0);
	}
	
}

	public function setPresetValues($preSet)
	{
		$res = trim($preSet);
		$has_value = (strlen($res) > 0);

		//echo get_Class($refParser) . ': ';
		
		//echo $refParser->cur_idx();
		if($has_value && $this->get_parser())
		{
		$stamp = $this->get_parser()->position_stamp();
		$URI = '@registry_surface_system#' . $res;
			if($this->get_parser()->seek_node($URI,null,null))
			{
				//echo "gefunden";
				//$this->get_parser()->go_to_stamp($stamp);
				return  $this->get_parser()->show_xmlelement()->getdata(0);
				
			}
			else
			{
				//$this->get_parser()->go_to_stamp($stamp);
				echo "nicht gefunden: $URI";
				

			}
		
		//$refParser->flash_result();
		}
		
	}

}

?>
