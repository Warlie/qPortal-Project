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

class TREE_content extends Interface_node
{


function &get_Instance()
{
return new TREE_content();
}



//primar call after finishing object, ther wont be an existing childnode
function event_initiated()
{
	$uri = $this->getRefprev()->full_URI();
	if( $uri == 'http://www.trscript.de/tree#program'
		||  $uri == 'http://www.trscript.de/tree#tree' ||  $uri == 'http://www.trscript.de/tree#final'
		|| $uri == 'http://www.trscript.de/tree#first')
	{
	$this->to_listener();
	
	}
}

function complete()
	{
		parent::complete();

	}

function event_message_in($type,&$obj)
	{
		//echo "ich mache hier schon Dinge\n";
		//throw new ErrorException($this->full_URI());
		global $_SESSION;
		
		// requests the permission to enter a special sector tag
		if ($att_sector = $this->get_attribute('sector'))
		{
		if (false === strpos($_SESSION['http://www.auster-gmbh.de/surface#sector'],';' . $att_sector . ';' ))return false;
		}
		
		// controls securitylevel
		if ($att_security = $this->get_attribute('securitylevel'))
		{
		if ((intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']) < intval($att_security)) 
		&& 
		(intval($att_security) <> -1)  )
		{
		return false;
		}
		
		// controls securityclass
		if (($_SESSION['http://www.auster-gmbh.de/surface#securityclass']) 
		&& 
		(intval($att_security) == -1)  )
		{
		return false;
		}
		}

		// ---------------------- Start with progress --------------------
		
		if($obj instanceof EventObject )
		{
		$template = $obj->get_requester()->get_out_template($other_template); //???

		$obj->get_requester()->set_current_template($obj->get_requester()->get_out_template()) ;
		//echo "Ich mache dinge\n";
		//throw new ErrorException("dinge");
			//changes maintemplate, to edit an non maintemplate
			if ($other_template = $this->get_attribute('id'))
			{
				
				//$this->get_idx()
				//echo "here it is" . get_class($obj->get_requester()) . " " . $obj->get_requester()->get_current_template() . " " . $this->get_parser()->cur_URI() . " " . $this->get_idx() .  " \n";
			
				if($other_template == "@me")
					{
						$template = $this->get_parser()->indexToUri($this->get_idx() );
					}
				else
				if($template = $obj->get_requester()->get_template($other_template))
				{
							$obj->get_requester()->set_current_template($other_template) ;
				}	
				else
				{
				echo "Der Name $other_template konnte nicht bei den Templates gefunden werden";
				$template = $obj->get_requester()->get_out_template($other_template);
				}
			}
		
		
		
			
		$tag_name = $this->get_attribute('name');
		$tag_array = array();
		$void = false;
		
		for($i = 0 ; $i < $this->index_max();$i++)
		{
			$tmp = $this->getRefnext($i,true);
			if($tmp->full_URI() == 'http://www.trscript.de/tree#param')
			{
				$tmp->event_message_in('',$obj);
				
				$tag_array[$tmp->get_attribute('name')] = $tmp->getdata();
			}
		}
	
	$this->get_parser()->change_URI($template);
	$this->get_parser()->flash_result();
	if($this->get_parser()->seek_node($tag_name,$tag_array) )
	{
		
	$obj->set_node($this->get_parser()->show_xmlelement());
	//echo $this->get_parser()->show_xmlelement()->name;
	//echo $obj->name;

	$this->send_messages('*',$obj); 
	}
	else
	{
		echo 'Tag "' . $tag_name . '" was not found in the ' . $template . " document<br>\n";
				$this->get_parser()->test_consistence();
		//var_dump($tag_array);
	}
	//echo $type . ' ' . get_Class($obj);
	}
	}//end of if obj == EventObject
}

?>
