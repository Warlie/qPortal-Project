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

class RDF_resource extends Interface_node
{
function &get_Instance()
{
return new RDF_resource();
}



	function event_initiated()
	{	
		global $logger_class;
		//echo ' rdf_resource ' . $this->full_URI() . ' ' . $namespace . ' to ' . $this->getdata() . " \n" ;
		//echo $this->getdata() . ;
		
		if(false === ($posinstr =  strpos(($data = $this->getdata()),'#')))
			{

				if(false === ($posinstr = strpos(($data),';')))
				{ //
				if($this->get_parser()->get_Prefix($data,$this->get_idx()))
					{
						$namespace = $data; //here
						$qname = "";
					}
					else
					{
						//echo $data;
						$namespace = $this->get_parser()->get_NS("",$this->get_idx());
						$qname = $data;
					}

				}
				else 
				{
				$namespace = substr($data,1,$posinstr);
				$qname = substr($data,$posinstr + 1);
				$namespace = $this->get_parser()->get_NS($namespace,$this->get_idx());
				}
				$namespace = $namespace . '#' . $qname;
			}
			else
			{
				$namespace = substr($data,0,$posinstr);
				$qname = substr($data,$posinstr + 1);
				
				if(strlen(trim($namespace)) > 0)
				{
					$namespace = $data;
				}
				else
				{
					$namespace = $this->get_parser()->get_NS('',$this->get_idx());
					$namespace = $namespace . '#' . $qname;
				}
			}
			
			
			//echo "\n" . $namespace . "\n";
			try {
				 $this->obj = $this->get_parser()->get_Class_of_Namespace($namespace);
				 if(is_object($this->obj->linkToClass()))
				 {
				 $logger_class->setAssert('          0000.' . $this->obj->linkToClass()->get_idx() . 
				 $this->obj->linkToClass()->position_stamp() . '  ' . 
				 $this->obj->linkToClass()->type . ' '
				 . $this->obj->linkToClass()->index_max() .  '(RDF_resource:event_initiated)' ,10);
				 $this->set_to_out($this->obj->linkToClass());
				}
				else
				{
				//$logger_class->setAssert('       ' Classobj '    .  
				// $this->obj->type . ' '
				// . $this->obj->index_max() .  '(RDF_resource:event_initiated)' ,10);
				
				$this->set_to_out($this->obj);
				}
				//echo ' connect to ' . $this->obj->linkToClass()->full_URI() . ' with Namespace ' . $namespace . "\n" ;
				//echo "<font color=\"#00FF1AA\">\n";
				//$this->obj->linkToClass()->giveOutOverview();
				//echo "</font>\n";
								 //$this->set_to_out($this->obj->linkToClass());
				 //echo 'booh' . $this->full_URI() . ' ' . $namespace . "\n";
			} catch (ErrorException $e) {
				//echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				$this->addToTicketEvent($namespace ,$this);
			}

		
	//$this->to_listener();
	}	
	
	function event_message_in($type,&$obj)
	{
		
	}
	
	public function TicketEvent(&$ticketObject)
	{
		global $logger_class;
		
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
				$namespace = $namespace . '#' . $qname;
			}
			else
			{
				$namespace = substr($data,0,$posinstr);
				$qname = substr($data,$posinstr + 1);
				
				if(trim($namespace) > 0)
				{
					$namespace = $data;
				}
				else
				{
					$namespace = $this->get_parser()->get_NS('',$this->get_idx());
					$namespace = $namespace . '#' . $qname;
				}
			}
			
			
			
			try {
				 $this->obj = $this->get_parser()->get_Class_of_Namespace($namespace);
				 $logger_class->setAssert('          0000.' . $this->obj->idx . $this->obj->position_stamp() . '  ' . 
				 $this->obj->type . '(RDF_resource:TicketEvent)' ,10);
				 
				 $this->set_to_out($this->obj->linkToClass());
				 //echo 'booh' . $this->full_URI() . ' ' . $namespace . ' ' . get_Class($this->obj) . ' ' . count($this->way_out[$i]) .  "\n";
			} catch (ErrorException $e) {
				//echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				
			}
	}

/*
	
	//wird bei der initialisierung aufgerufen
	function start()
	{
		$this->to_listener();
	}
	//wird bei u.a. beim Ende des Parsens aufgerufen
	function event($event = '')
	{
		if('complete'==$event)
			{
				
						    if(!(false === ($tmp = strpos($this->value,'#'))))
							{
				
								$prefix = substr(strtolower($this->value),0,$tmp);
								$attribname = substr(strtolower($this->value),$tmp + 1);
								
								if('' <> trim($attribname))
								{
									$obj = &$this->back->parser->namespace_frameworks[$prefix]['node'][$attribname];
									//echo $this->back->name;
									
									$this->back->event_attribute($this->name,&$this,&$obj);
								}
							}
								
				//
				
				//
			}
	}
	*/
		
}

?>
