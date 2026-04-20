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
* getdata($pos = null)
* get_data_many()
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

class RDF_about extends Interface_node
{
function &get_Instance()
{
return new RDF_about();
}


	function event_initiated()
	{
	//$this->to_listener();
	// Case Ontology needs to be defined
	if($this->getRefprev()->full_URI() == 'http://www.w3.org/2002/07/owl#Ontology')
		{
		//echo $this->getdata() . "  \n" ;
		 	$this->get_parser()->currentOntology($this->getdata());
		//setOntologyRelationship
			$this->get_parser()->set_Namespace($this->getdata());
		}
		else
		{// public function setNodeRelationship($new, $basedOn)
			
			//"about" defines a new resource without fully qualified URI
			if(false === ($posinstr =  strpos(($data = $this->getdata()),'#')))
			{
				
				if(false === ($posinstr = strpos(($data),';')))
				{
				$namespace = $this->get_parser()->get_NS('',$this->get_idx());
				$qname = $data;
				}
				else 
				{
				$namespace = substr($data,1,$posinstr);
				$qname = substr($data,$posinstr + 1);
				$namespace = $this->get_parser()->get_NS($namespace,$this->get_idx());
				}
				$namespace2 = $namespace . '#' . $qname;
			}
			else
			{
				// "about" with fully qualified URI
				$namespace = substr($data,0,$posinstr);
				$qname = substr($data,$posinstr + 1);
				
				if(strlen(trim($namespace)) > 0)
				{
					$namespace2 = $data;
				}
				else
				{
					$namespace = $this->get_parser()->get_NS('',$this->get_idx());
					$namespace2 = $namespace . '#' . $qname;
				}
			}
			
				// create a "class" instance
				$new_obj = &$this->getRefprev()->new_Instance();
				$new_obj->name = $data;
				$new_obj->type = $qname;
				$new_obj->set_idx( $this->get_idx());
				$new_obj->namespace = $namespace;
				$new_obj->set_parser($this);
				
				$this->get_parser()->set_Object_to_Namespace($namespace2,$new_obj);
				$new_obj->set_is_Class();
				
		}
	}	
	
	function __toString()
	{
	return 'rdf:about';
	}

}
?>
