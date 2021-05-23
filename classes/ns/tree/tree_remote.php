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
* is_Node($nodename): checks for its own an its parentnodenames
*/

class TREE_remote extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
private $connect;
	
function connect_uri($uri)
{
$this->connect = $uri;
}

function __construct()
{

}

function &get_Instance()
{
return new TREE_remote();
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
	global $logger_class;
	//echo $type . '-' . $obj->get_request() . '-' . $obj->get_context() . "<br>\n";
	/*
	$datacol = array();
	$disconnect = '';
	$has_obj_child = false;
	*/
	/*
	
	Na super, ich vermute, dass ich wieder probleme bekomme, sobald ich mehrere object Tags habe.
	
	*/
	/*
	for($i = 0;$this->index_max() > $i;$i++)
	{
	  $has_obj_child = ($this->getRefnext[$i]->full_URI() == 'http://www.trscript.de/tree#object');
	}
	
	 
	//saves all data
	for($i = 0;$this->data_many() > $i;$i++)
	{
	$disconnect = $this->getdata($i);
	$datacol[$i] = $disconnect;
	unset($disconnect);
	
	}
	*/

	//for($i = 0;count($this->way_out) > $i;$i++)
	//{
		
	//	echo $this->way_out[$i]->full_URI() . ' ist das aktuelle objekt <br>';
	//	}

	//if($this->get_attribute('name') == 'FileService.add_fix')echo 'checkpoint (' . $this->get_attribute('name') . ")<br>\n";
	//activates a child tree:Object object to receive its data later
	$message = 'http://www.trscript.de/tree#object';
	
	$booh = null;
	$Event = new EventObject('',$this,$booh);
	$Event->set_node($obj->get_node());

	$logger_class->setAssert('               remote starts transaction "' . $this->get_attribute('name') . '" (tree_remote:event_message_in)' ,15);
	//starts a linked node, if it is an "http://www.trscript.de/tree#object"
	$this->send_messages($message,$Event);
	
	//gets its value from a linked node of type "http://www.trscript.de/tree#object"
	$send = 'http://www.trscript.de/tree#object?__get_data=0'; 
	$this->send_messages($send,$Event);
	
	
	//--------------------------------------
	
	if($tmp = &$this->getdata(0))
	$booh = $tmp;
	else
	$booh = null;
	
	/*
	if($has_obj_child)
	{
	$res = '';
	for($i = 0;count($datacol) > $i;$i++)
	{
	

	
	$res .= $datacol[$i] . $booh;
	
	}
	$booh = $res;
	}
	
	echo $booh . "\n";
	*/
	//writes data to a linked Parameterobject
	$send = 'http://www.w3.org/2006/05/pedl-lib#Object_Parameter?__set_data=0'; 
	
	
	$Event = new EventObject('',$this,$booh);
	$Event->set_node($obj->get_node());
	//		echo '{';				
	$this->send_messages($send,$Event);
	
	//receive date from a funtion object, if there is a function-object, it causes a alterdataevent
	$send = 'http://www.w3.org/2006/05/pedl-lib#Object_Funktion?__get_data=0'; 
	//echo '}[';
			
	$this->send_messages($send,$Event);
	//echo ']---' . count($this->way_out) . '--';
	
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
protected function event_alterdata($own)
	{
	
	global $logger_class;
	$logger_class->setAssert('               calls a functioncall over remote "' . $this->get_attribute('name') . '"(tree_remote:event_alterdata)' ,15);
		
	
		/*echo "\n<br>" . 'alter :' . $this->get_attribute('name') . "<br>"; */
		/*
		* activation param from tree_object is true 
		*/
		if($own)
		{
		$logger_class->setAssert('               own(tree_remote:event_alterdata)' ,15);
		$booh = null;
		//;

			if(is_Object($object = &$this->getRefnext(0)))
			{
			//if there ist a Tree:Object
			//echo 'in ancillery remote node' . "<br>\n{<br>\n";
				if($object instanceof TREE_object)
				{
					//an unspecified message will send to an child tree:object, if exist 
					$Event = new EventObject('',$this,$booh);
					$Event->set_node($this);
					$object->event_message_check('*',$Event);
			
					if(is_Object($object2 = &$object->getRefnext($object->index_max() - 1)))
					{
						if($object2 instanceof TREE_remote)
						{
						
							$this->set_alter_event(false);
							$text = 'booh';
							//$object2->setdata($text,0);
							
							//takes current value of ancillery remote-node
							$this->setdata($object2->getdata(0),0);
							$this->set_alter_event(false);
							$send = $this->connect . '?__set_data=0'; //http://www.trscript.de/tree#name
							//get content
							
							//echo '+ ' . $send . ' ' . $object2->getdata(0) . '<br>';
							
							$Event2 = new EventObject('',$this,$object2->getdata(0));
							$this->send_messages($send,$Event2);
							$this->set_alter_event(true);
							
							//for($i = 0;count($this->way_out) > $i;$i++)
							//{
							//echo $this->way_out[$i]->full_URI() . ' ';
							//}
						
						}
					}
				}
				//echo "<br>\n}<br>\n"; 
			}elseif($this->data_many() > 0) //!is_null($text = &$this->getdata(0))
			{// if there is a textnode
			//echo 'direct command on textnode:' . "<br>\n{<br>\n";
			
			$send = $this->connect . '?__set_data=0'; //http://www.trscript.de/tree#name
			//get content
			$Event = new EventObject('',$this,$this->getdata(0));
	
				//echo "rabusch123 ";			
			$this->send_messages($send,$Event);
			
			//echo "<br>\n}<br>\n";
			} 
			else
			{//if($this->get_attribute('name') == 'XMLDO.iter')return false;
			//
			//echo ' in stange area ' . "<br>\n{<br>\n";
			$send = $this->connect . '?__set_data=0';
			$booh = '';
			
			$Event = new EventObject('',$this,$booh);

			
			$this->send_messages($send,$Event);
			
			//call for value
			//$send = $this->connect . '?__get_data=0';
			//$send = 'http://www.w3.org/2006/05/pedl-lib#Object_Funktion?__get_data=0'; //http://www.trscript.de/tree#name
			//get content
			//$Event = new EventObject('',$this,$booh);
	
				//echo "rabusch123 ";			
			//$this->send_messages($send,$Event);
			//echo "<br>\n}<br>\n"; 
			}
			
			}//End of activ dataaltering
		else
		{
				$logger_class->setAssert('               not own(tree_remote:event_alterdata)' ,15);
		/*
				if(!is_null($text = &$this->getdata(0)))
				{
					if($this->getRefprev()->is_Node('http://www.trscript.de/tree#object'))
					{
					$this->getRefprev()->setdata($text,0);
					}
				} */
		}
//echo "exit<br>\n";
	}

}

?>
