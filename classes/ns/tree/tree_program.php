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

class TREE_program extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
var $if_clause = array();
var $dowhile_clause = array();
var $loop_num = 1;
var $while_bool = false;

function __construct()
{

}

function &get_Instance()
{
return new TREE_program();
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
	$uri = $this->getRefprev()->full_URI();
	if( $uri == 'http://www.trscript.de/tree#program' || $uri == 'http://www.trscript.de/tree#content'
	)
	{
	
	$this->to_listener();
	
	}
	// || $uri == 'http://www.trscript.de/tree#tree' || $uri == 'http://www.trscript.de/tree#final'
}

function complete()
	{
		parent::complete();

	}

function event_message_in($type,&$obj)
	{
	
		if($obj instanceof EventObject )
		{

			 try{
			
			$res = $this->actualize_data($type,$obj);
			
			if($res)
			{

			//$this->loop_Operation($type,$obj);
			if($this->if_Operation($type,$obj))
			{
			$this->send_messages($type,$obj);
			}
			}
			else
			{
			$this->send_messages($type,$obj); 
			
			}
		
			 } catch (ProgramBlockException $e)
			 {
			 	 $this->get_parser()->get_ExceptionManager()->catchException($e);
			 }
			
		}//end of if obj == EventObject
	
	}
	
private function if_Operation($type,&$obj)
	{ 

	global $logger_class;
		
		$result = true;
	for($i=0;$i < count($this->if_clause);$i++ )
		{
		
		if($this->if_clause[$i] instanceof Interface_node)
		
		$many = $this->if_clause[$i]->data_many();
		$res = '';
		for($z = 0;$z < $many;$z++)
		{
		
			//$this->if_clause[$i]->event_message_in($type,$obj);
			$res .= $this->if_clause[$i]->getdata($z);
			if($z < ($many - 1))
			{
				$many_remote = $this->if_clause[$i]->getRefnext($z)->index_max();
						if(0 < $many_remote)
						{
						//echo $many_remote;
						$res .= $this->if_clause[$i]->getRefnext($z)->getRefnext($many_remote - 1)->getdata(0);
						}
			}
		}
		
		$logger_class->setAssert(' if Statement for eval "' . $res . '" ("' . $this->get_attribute('name') . '")(tree_program:if_Operation)' ,2);
		//echo $res;
		//return true;
			 try{

				$result = eval( $res );
		
			 } catch(ParseError $e){
			 
			 	 $this->get_parser()->get_ExceptionManager()->catchException($e);
			 }

		if(!$result)break;
		
		}
		if(count($this->if_clause) == 0)return true;

		return $result;
		
	}
	
private function dowhile_Operation()
	{
		$result = true;
		foreach ($this->dowhile_clause  as $value) 
		{
		$result = (eval($value) && $result);
		}
		return $result;
	}
	
private function loop_Operation($type,&$obj)
	{

		for($i = 0;($this->while_bool || ($i < $this->loop_num)) && $this->if_Operation();$i++)
			{

				$this->send_messages($type,$obj); 
				if(!$this->dowhile_Operation())break;
				$this->actualize_data($type,$obj);
			}
	}
	

	
private function actualize_data($type,&$obj)
	{
	unset($this->if_clause);
	$this->if_clause = array();
	$res = true;
		for($i = 0 ; $i < $this->index_max();$i++)
		{
			$tmp = $this->getRefnext($i,true);
			
			
			if($tmp->full_URI() == 'http://www.trscript.de/tree#param')
			{
				//requests param tag
				$tmp->send_messages($type,$obj);
				if(strtoupper($tmp->get_attribute('name')) == 'IF')
				{
				
				$this->if_clause[count($this->if_clause)] = &$tmp;

				}
				
				if(strtoupper($tmp->get_attribute('name')) == 'DOWHILE')
				$this->dowhile_clause[count($this->dowhile_clause)] = &$tmp;
			
				if(strtoupper($tmp->get_attribute('name')) == 'LOOP')
				$this->loop_num = $tmp->getdata();
			
				if(strtoupper($tmp->get_attribute('name')) == 'WHILE')
				$this->while_bool = $tmp->getdata();
				
				unset($tmp);
				
				$res = false;
			}


		}

	return !$res;
	}
	
}

?>
