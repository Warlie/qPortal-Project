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
* setobj(&$data) : 
* &getobj() : 
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

class PEDL_Object_Funktion extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
private $mycount = 0;
private $id_of_object;

function __construct()
{

}

function &get_Instance()
{
return new PEDL_Object_Funktion();
}

function set_id_name($id)
{
	$this->id_of_object = $id;
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

function event_message_in($type,&$obj)
	{
		

	}
protected function event_readdata($own)
	{
		//echo ' in event_readdata( ';

	global $logger_class;
	//echo '             data in "' . $this->full_URI() . '" will read and causes an readdataevent ' . '(PEDL_Object_Funktion:event_readdata)' . "\n";
	$logger_class->setAssert('             data in "' . $this->full_URI() . '" will read and causes an readdataevent ' . '(PEDL_Object_Funktion:event_readdata)' ,15);

		if($own)
		{

		//echo 'function-alter(' . $this->id_of_object . '):';
		$myarray = &$this->getRefprev()->getobj();
		$reflectionObject = $myarray[0];
		//echo get_Class($myarray[0]);
		
		$methodname = $this->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#name');

		if(strlen(trim($methodname)) == 0)return false;
		if($method = $myarray[0]->getMethod($methodname)) 
			{

				if ($method->isPublic() && !$method->isAbstract()) 
				{
					
						$all_values = array();
						for($j = 0; $this->index_max() > $j;$j++)
						{
							//echo 'booh';
							if($this->getRefnext($j)->is_Node('http://www.w3.org/2006/05/pedl-lib#Object_Parameter'))
							{

								$tmp = &$this->getRefnext($j)->getdata(0);
								if(is_object($tmp))
								{
									$all_values[] = &$tmp;
									//echo "got an object:" . get_class( $tmp ) . "\n";
								}
								else
								{
									$all_values[] = $tmp;
									//echo "got a value:$tmp\n";
								}

							}
						}

						$result;
						
					//echo $method->getName() . " \n";
					$this->set_alter_event(false);
					
					// contains a reflectionClass and a SQL String
					try{
					$refl = $method->invokeArgs($myarray[1], $all_values);
					}
					catch(ObjectBlockException $e)
					{
						//print_r('in '.$e->getFile().' at line '.$e->getLine());
						$this->parser->get_ExceptionManager()->catchException($e);
					}
					$this->setdata($refl,0);

					

					
					$this->set_alter_event(true);
				}
			}
			
		}
		//echo ') ';
	}
}

?>
