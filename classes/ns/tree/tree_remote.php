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
//var_dump($type);
//	echo $this->full_URI()  . " called by " . (($obj->get_requester() instanceof Interface_node)?$obj->get_requester()->full_URI(): get_class($obj->get_requester())) . " \n" ;
	
	//activates a child tree:Object object to receive its data later
	$message = ["Identifire"=>"http://www.trscript.de/tree#object", "Command"=> ["Name"=> null, "Attribute"=>[], "Value"=> null]]; // 'http://www.trscript.de/tree#object'

	$booh = null;
	$Event = new EventObject('',$this,$booh);
	$Event->set_node($obj->get_node());


	//starts a linked node, if it is an "http://www.trscript.de/tree#object"
	$this->send_messages($message,$Event);
	
	//gets its value from a linked node of type "http://www.trscript.de/tree#object" //
	//$send = ["Identifire"=>"http://www.trscript.de/tree#object", "Command"=> ["Name"=> "__get_data", "Attribute"=>[], "Value"=> "0"]]; // 'http://www.trscript.de/tree#object?__get_data=0' 
	$this->send_messages( ["Identifire"=>"http://www.trscript.de/tree#object", "Command"=> ["Name"=> "__get_data", "Attribute"=>[], "Value"=> "0"]],$Event);
	$this->send_messages( ["Identifire"=>"http://www.trscript.de/tree#variable", "Command"=> ["Name"=> "", "Attribute"=>[], "Value"=> "0"]],$Event);
	
	
	//--------------------------------------
	
	//for($i = 0 ; $i < $this->index_max();$i++)
	//	echo get_class($this->getRefnext($i));//$this->getRefnext($i)->send_messages($type,$obj);

	
	if($tmp = &$this->getdata())
	$booh = $tmp;
	else
	$booh = null;
	
	
	//var_dump((gettype($booh)=='object'?get_class($booh): $booh ));
	//writes data to a linked Parameterobject
	$send = ["Identifire"=>"http://www.w3.org/2006/05/pedl-lib#Object_Parameter", "Command"=> ["Name"=> "__set_data", "Attribute"=>[], "Value"=> "0"]] ;  // 'http://www.w3.org/2006/05/pedl-lib#Object_Parameter?__set_data=0'
	
	
	$Event = new EventObject('',$this,$booh);
	$Event->set_node($obj->get_node());			
	$this->send_messages($send,$Event);
	
	//receive date from a funtion object, if there is a function-object, it causes a alterdataevent
	$send = ["Identifire"=>"http://www.w3.org/2006/05/pedl-lib#Object_Funktion", "Command"=> ["Name"=> "__get_data", "Attribute"=>[], "Value"=> "0"]]; //  'http://www.w3.org/2006/05/pedl-lib#Object_Funktion?__get_data=0' 			
	$this->send_messages($send,$Event);

	
	}

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

			}
			
			}//End of activ dataaltering
		else
		{
				$logger_class->setAssert('               not own(tree_remote:event_alterdata)' ,15);

		}
//echo "exit<br>\n";
	}

}

?>