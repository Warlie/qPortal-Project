<?php

/**  Interface
*
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

class PEDL_Object_Class extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
private $id_of_object;
private $executive = false;
private $is_Class;

function set_executive(){$this->executive = true;}
function is_executive(){return $this->executive;}	
	
function __construct()
{

}

function &get_Instance()
{
return new PEDL_Object_Class();
}

function get_id_name()
{
	return $this->id_of_object;
}

function &new_Instance()
{
	//is also class, when used
	//is also class, when used
	$this->is_Class = true;
                                $obj = $this->get_Instance();
				$obj->link_to_class = &$this;
				$this->link_to_instance[count($this->link_to_instance)] = &$obj;
				//echo $this->ManyInstance(); 
				return $obj;
}

public function ManyInstance()
{
return count($this->link_to_instance);
}

//primar call after finishing object, ther wont be an existing childnode
function event_initiated()
{
	$this->id_of_object = $this->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#ID');
	//echo "new id $this->id_of_object " . $this->position_stamp() . "\n"; 	
		for($i = 0; $this->index_max() > $i;$i++)
		{	//echo "\n" . get_class($this->getRefnext($i)) . " " . $this->getRefnext($i)->position_stamp() . "\n";
			$url = null;
			if($this->getRefnext($i)->is_Node('http://www.w3.org/2006/05/pedl-lib#hasCodeResource'))
			{
				$url = $this->getRefnext($i)->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#src');
				
				//echo 'gefunden';
				break;
			}
		}
			
//echo "i am here $url \n";
			if(!is_null($url))
				{
				require_once($url);
				}
		
	
	//$this->ManyInstance() . 'booh';
//echo $this->full_URI() . ' ' . $this->ManyInstance() . "<br>\n";
//$this->name .= 'booh';
}

function event_parseComplete()
{
						/*	for($i = 0;count($this->way_out) > $i;$i++)
							{
							echo '<br>' . $this->way_out[$i]->full_URI() . "<br>\n";
							}*/
//$this->ManyInstance() . 'booh';
}

function event_Instance(&$instance,$type,&$obj)
{
	/*
	* find positionstamp of this node
	*/
	$parser = &$this->get_parser();
	$new_stamp =  '0000.' . $instance->get_idx() . $instance->position_stamp();

					
	//echo "pedl_Object_Class.php 144\n";
	
	//echo $this->full_URI(). " " . "\n";
	
	$res = array();
	$go = false;
	/*
	* starts, when there are childnodes
	* 
	*/
	if($this->index_max() > 0)
	{
		
			$parser->go_to_stamp($new_stamp);
			$basic_obj = &$parser->show_xmlelement();
			//echo $basic_obj->full_URI();
			
	
	for($i = 0; $this->index_max() > $i;$i++)
		{
			//collects all Object_functions and lists it up 
			if($this->getRefnext($i)->is_Node('http://www.w3.org/2006/05/pedl-lib#Object_Funktion'))
				{
					$go = true;
					$func = &$this->getRefnext($i);
					$function_name = $func->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#ID');
					$pedl_name = $func->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#name');
					
					$tmp = new Function_Object($pedl_name,$function_name,0);
					
					for($j = 0; $func->index_max() > $j;$j++)
						{
							$elem = &$func->getRefnext($j);
							$param_name = $elem->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#ID');
							$pedl_name2 = $elem->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#name');
							$tmp->pushParam($param_name,$pedl_name2);
						}
					
					//
					$res[$pedl_name] = &$tmp;
					unset($func);
					unset($tmp);
				}
				
			if($this->getRefnext($i)->is_Node('http://www.w3.org/2000/01/rdf-schema#subClassOf'))
				{
					$go = true;
				if($this->getRefnext($i)->get_ns_attribute_obj('http://www.w3.org/1999/02/22-rdf-syntax-ns#resource')->getobj())
				{
					$class = &$this->getRefnext($i)->get_ns_attribute_obj('http://www.w3.org/1999/02/22-rdf-syntax-ns#resource')->getobj()->linkToClass();
					
					$this->find_functions($class,$res);
				}
				else
				{
					/*TODO gescheite Fehlerbeschreibung */
				
				echo $this->name; 
				$name = $this->get_ns_attribute_obj("http://www.w3.org/2006/05/pedl-lib#name")->getdata();
				echo ' <b>' . $name .'</b><br>';
				echo "Missing RDF edge ref:";
				echo $this->full_URI() . '->' . $this->getRefnext($i)->full_URI();
				echo '(' . get_class($this->getRefnext($i)) . ')->';
				echo $this->getRefnext($i)->get_ns_attribute_obj('http://www.w3.org/1999/02/22-rdf-syntax-ns#resource')->full_URI();
				echo '(' . get_class($this->getRefnext($i)->get_ns_attribute_obj('http://www.w3.org/1999/02/22-rdf-syntax-ns#resource')) . ")<br>\n";
				$this->giveOutOverview();
				$this->get_parser()->test_consistence();
				die();
				
				}
				
				}
				
		}

		//after collection 
		foreach($res as $key => $value)
			{
					
					$attrib = array('pedl:name' => $value->pedl_name());
					
					if(!$pos = $parser->create_Ns_Node($value->q_name(),$new_stamp,$attrib))
						{
							throw new ErrorException('Cannot instancing following class:' . $value->q_name() . ' ', 0,75,$parser->getControlUnit( "surface_tree_engine")->getSystemSpace(),1);
						}
						else
						{
							//echo $instance->get_id_name();
							$parser->show_xmlelement()->set_id_name($instance->get_id_name());
							//$parser->go_to_stamp($pos);
							
							//$parser->goto
							$parser->set_Ns_to_Listener();
						}
					
					if($value->has_param())
					{
						
						$stamp = $parser->position_stamp();
						do
						{
							$attrib2 = array('pedl:name' => $value->getParam_pedl_name());
							
							if(!$parser->create_Ns_Node($value->getParam_name(),$stamp,$attrib2))
							{
							
								throw new ErrorException('Cannot instancing following class:' . $value->getParam_name() . ' ', 0,75,"pedl_Object_Class",132);
							}
							else
							{
								$param_obj = &$parser->show_xmlelement();
								//echo $param_obj->full_URI();
								$basic_obj->set_to_out($param_obj);
							}
							
						}
						while($value->pop());
						
						
					}
					
			}
	

	$parser->go_to_stamp($new_stamp);

	
	}
			
			
	//echo $this->index_max() . ' object_name:' . $this->full_URI() . ' event:' . $instance->full_URI() . ' ' . $type .  "<br>\n";
	
}

private function find_functions(&$class, array &$result,$priority = 1)
{
	$go = false;
	for($i = 0; $class->index_max() > $i;$i++)
		{
			//echo 'booh';
			if($class->getRefnext($i)->is_Node('http://www.w3.org/2006/05/pedl-lib#Object_Funktion'))
				{
					$go = true;
					
					$func = &$class->getRefnext($i);
					$function_name = $func->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#ID');
					$pedl_name = $func->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#name');
					
					//voids redundant functionnames
					$bool_ok = !is_object($result[$pedl_name]);
					if(!$bool_ok)
						{
							
							$bool_ok = ($result[$pedl_name]->has_priority() > $priority);
						}
					
					
					if($bool_ok)
					{
					$tmp = new Function_Object($pedl_name,$function_name,$priority);
					
					for($j = 0; $func->index_max() > $j;$j++)
						{
							$elem = &$func->getRefnext($j);
							$param_name = $elem->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#ID');
							$pedl_name2 = $elem->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#name');
							$tmp->pushParam($param_name,$pedl_name2);
						}
					
					//
					$result[$pedl_name] = &$tmp;
					unset($tmp);	
					}
	
				}
				
			if($class->getRefnext($i)->is_Node('http://www.w3.org/2000/01/rdf-schema#subClassOf'))
				{
					$go = true;
					$class2 = &$class->getRefnext($i)->get_ns_attribute_obj('http://www.w3.org/1999/02/22-rdf-syntax-ns#resource')->getobj()->linkToClass();
					$this->find_functions($class2,$result);
				}
		}

	}

	function event_message_in($type,&$obj)
	{
		
		if($this->is_Command($type,'seek'))
		{
			$com_elemnet = $this->parseCommand($type);
			//echo $com_elemnet->get_Command(0,1);
									//base64_decode __redirect_node
			//$send = '?seek=' . $this->getRefnext($i)->get_ns_attribute('http://www.trscript.de/tree#name');
			//$booh = null;
			//$this->send_messages($send,new EventObject('',$this,$booh));
							
		}

	}
/*
function send_messages($message,&$event)
{

	echo "**********send_message*****<br>\n";
	echo "Message:" .$message . "<br>\n";
	echo "Request  :" .$event->get_request() . "<br>\n";
	echo "Requester:" .get_Class($event->get_requester()) . "<br>\n";
	echo "Context  :" .get_Class($event->get_context()) . "<br>\n";
	echo '**************way out****************' . "<br>\n";
			for($i = 0;count($this->way_out) > $i;$i++)
			{
			echo $this->way_out[$i]->full_URI() . "<br>\n";
			}
	echo '*************************************' . "<br>\n";
	echo "****************************<br>\n";	
	
	parent::send_messages($message,$event);
}
*/
}

class Function_Object
{
	private $pedl_name;
	private $q_name;
	private $param_name = array();
	private $param_pedl = array();
	private $priority = 0;
	private $pos = 0;
	
	public function __construct($pedl_name,$q_name,$priority)
	{
		
		$this->q_name = $q_name;
		$this->pedl_name = $pedl_name;
		$this->priority = $priority;
	}
	
	public function pedl_name()
	{
		return $this->pedl_name;
	}
	
	public function q_name()
	{
		return $this->q_name;
	}
	
	public function pushParam($param,$pedl)
	{
		
		$this->param_name[count($this->param_name)] = $param;
		$this->param_pedl[count($this->param_pedl)] = $pedl;
	}
	
	public function getParam_name()
	{
		return $this->param_name[$this->pos];
	}
	
	public function getParam_pedl_name()
	{
		return $this->param_pedl[$this->pos];
	}
	
	public function pop()
	{
		
		return (++$this->pos < count($this->param_name));
	}
	
	public function has_param()
	{
		return (0 < count($this->param_name));
	}
	
	public function has_priority()
	{
		return $this->priority;
	}
}

?>
