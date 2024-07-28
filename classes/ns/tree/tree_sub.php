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

class TREE_sub extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new TREE_sub();
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
	//echo $this->getRefprev()->full_URI() . ' ' ;
	//echo $this->get_attribute('value') . "<br>\n";
/*
			if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#method') )		
				if($_SERVER['REQUEST_METHOD'] === strtoupper($tmp))
				{
					echo "add";
					$this->to_listener();
				}
	*/
	$this->to_listener();

}

function complete()
	{
		parent::complete();
		

	}


	
function event_message_in($type,&$obj)
	{

		global $_SESSION;

		
		$param_arr = [];
		
				$result = true;

		if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#sector') )		
			$result = in_array($tmp, explode(';', trim_with_null($_SESSION['http://www.auster-gmbh.de/surface#sector'], ';')));

			//var_dump($tmp, $result);
			//$this->giveOutOverview();

		if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#method') )		
			if($_SERVER['REQUEST_METHOD'] !== strtoupper($tmp))return;
			
		if($tmp =  intval($this->get_ns_attribute('http://www.trscript.de/tree#securitylevel')) )
		{
			
			if($_SESSION['http://www.auster-gmbh.de/surface#securityclass'])
				$sec = intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']);
			else
				$sec = -1;
				
			
			$result = $result  &&  ($tmp == -1) || ((($tmp != -1) &&  ($sec >= $tmp))) ; 
		}	
			

		
		if(!$result) throw new NoPermissionException('not Allowed');

	
		
		// collects all param tag info
		for($i = 0 ; $i < $this->index_max();$i++)
		{
			$tmp = &$this->getRefnext($i,true);
							//activates all param tags
							if($tmp->full_URI() == 'http://www.trscript.de/tree#param')
							{
								$tmp->send_messages($type,$obj);
								//echo "call";
								$res =  $tmp->getdata();
								
								if($tmp->getRefnext(0) instanceof Interface_node)
								{
								$many_remote = $tmp->getRefnext(0)->index_max();
								
								if(0 < $many_remote)
								{
							
								$res .= $tmp->getRefnext(0)->getRefnext($many_remote - 1)->getdata(0); //
								//$tag_array[$tmp->get_attribute('name')] = $tmp->getRefnext($many_remote - 1)->getdata(0);
								}
								}
								$param_arr[$tmp->get_attribute('name')] = $res;
								
							}
							unset($tmp);
							unset($res);
						}

		//echo $this->get_attribute('name') . ' ' . $type . "<br>\n";
	//echo $type . ' ' . $obj->get_request() . ' ' . $this->name .  '<br>';
	if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#src'))
	{

		 $tmp = str_replace( '%ROOT_DIR%', ROOT_DIR, $tmp);
		if(is_file($tmp))
		{

			$this->get_parser()->load($tmp,0);
		//$this->get_parser()->ALL_URI();
		
		$var_name;
		
		if(count($param_arr)){
		$this->get_parser()->flash_result();
		$this->get_parser()->seek_node('http://www.trscript.de/tree#variable');
		$res_tags = $this->get_parser()->get_result();
		
		foreach($res_tags as $value){If(array_key_exists(
			$var_name = $value->get_ns_attribute('http://www.trscript.de/tree#name'),
			$param_arr))
				$value->setdata( $param_arr[$var_name] ,0);

		}
				
		
		 $this->get_parser()->flash_result();
		}
		
		
		
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
