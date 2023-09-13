<?php

/**  TREE_tree
*
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

class TREE_tree extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new TREE_tree();
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
	
		global $_SESSION;
		
				$result = true;

		if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#sector') )		
			$result = in_array($tmp, explode(';', trim_with_null($_SESSION['http://www.auster-gmbh.de/surface#sector'], ';')));

			//var_dump($tmp, $result);
			//$this->giveOutOverview();
				
		if($tmp =  intval($this->get_ns_attribute('http://www.trscript.de/tree#securitylevel')) )
		{
			
			if($_SESSION['http://www.auster-gmbh.de/surface#securityclass'])
				$sec = intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']);
			else
				$sec = -1;
				
			
			$result = $result  &&  ($tmp == -1) || ((($tmp != -1) &&  ($sec >= $tmp))) ; 
		}	
			

		
		if(!$result) throw new NoPermissionException('not Allowed');
		/*
		
		if ($att_sector = $this->get_attribute('sector'))
		{
		if (false === strpos($_SESSION['http://www.auster-gmbh.de/surface#sector'],';' . $att_sector . ';' )); //return false;
		}
		
		
		if ($att_security = $this->get_attribute('securitylevel'))
		{
		if ((intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']) < intval($att_security)) 
		&& 
		(intval($att_security) <> -1)  )
		{
		; //return false;
		}
		
		if (($_SESSION['http://www.auster-gmbh.de/surface#securityclass']) 
		&& 
		(intval($att_security) == -1)  )
		{
		; //return false;
		}
		}
		
		*/
	
		//echo $this->get_attribute('name') . ' ' . $type . "<br>\n";
	//echo $type . ' ' . $obj->get_request() . ' ' . $this->name .  '<br>';
	if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#src'))
	{
		if(is_file($tmp))
		{
			$this->get_parser()->load($tmp,0);
		//$this->get_parser()->ALL_URI();
			$this->get_parser()->seek_node('http://www.trscript.de/tree#final');
			$this->get_parser()->show_xmlelement()->event_message_in($type,$obj);
			return true;
		}
		
	}
	//echo count($this->way_out);
	//if($obj instanceof EventObject )
	//{
		
		$obj->set_context($this);
	
		$this->send_messages($type,$obj);
		
		//calls all childnodes, which are not template and tree
		for($i = 0 ; $i < $this->index_max();$i++)
			{
			$tmp = $this->getRefnext($i,true);
			
			if($tmp->full_URI() <> 'http://www.trscript.de/tree#template' 
			&& $tmp->full_URI() <> 'http://www.trscript.de/tree#tree'
			)
			{
				//echo $tmp->full_URI() . "- \n";
				
				$tmp->event_message_in('',$obj);
			}
			}
	//}
	
	}
}

?>
