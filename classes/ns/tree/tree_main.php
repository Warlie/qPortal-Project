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

class TREE_main extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new TREE_main();
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
	if($uri == 'http://www.trscript.de/tree#template' )
	{
	$this->to_listener();
	
	}
}

function event_message_in($type,&$obj)
	{
		
		//loads main template
		//$obj->get_requester()->template = $this->getdata();
		//$obj->get_requester()->maintemplate = $this->getdata();
		//$obj->get_requester()->out_template = $this->getdata();
		if(is_Null($this->get_attribute('doctype')))
		$doc_type = 'XML';
		else
		$doc_type = $this->get_attribute('doctype');
										
				//echo 'casefolding -' . $this->get_attribute('case_folding') . '-<br>';
										
		if($this->get_attribute('case_folding')=="0")
		{
		$preload = $this->get_attribute('doctype_out');
		
											
											//echo 'no-casefold';
											
		$this->get_parser()->load($this->getdata(),0,$doc_type);
											
											
											
			if($preload)
			{
													
				$this->get_parser()->TYPE[$this->get_parser()->idx] = $preload;
									
			}
											
		}
		else
		{
			$this->get_parser()->load($this->getdata(),1,$doc_type);
		}
		//$obj->get_requester()->set_template('@main',$this->getdata());
		
		$this->get_parser()->set_first_node();
		$obj->get_requester()->set_Main_NS($this->get_parser()->get_NS()); //saves URI of maintemplate 
		
										
			$uri = $this->getRefprev()->full_URI();
			if($uri == 'http://www.trscript.de/tree#template' )
			{
				$obj->get_requester()->set_template($this->getdata(),$this->getdata());
				
				if($output_doc = $this->get_attribute('output_doc'))
				{
					if(!(false === ($special = strpos($output_doc,';'))))
					{
						
						$pre = substr($output_doc,0,$special);
						$post = substr($output_doc,$special + 1);

						$obj->get_requester()->set_doc_out($pre);
						//SPECIAL
						$idx = $this->get_parser()->cur_idx();
						
						$this->get_parser()->change_URI($obj->get_requester()->doc_out_template);
						
						$array = explode(';',$post);
						for($i = 0;$i < count($array);$i++)
						{
						$pair = explode(':',$array[$i]);
						
						$this->get_parser()->set_special($pair[0], $pair[1]);
						
						}
						
						//***********************************
						$preload = $this->get_attribute('doctype_out');
			
											
						if($preload)
						{
													
							$this->get_parser()->TYPE[$this->get_parser()->idx] = $preload;
									
						}
						//***********************************
						
						$this->get_parser()->change_IDX($idx);
					}
					else
					$obj->get_requester()->set_doc_out($output_doc);
					
				}
				
					$obj->get_requester()->set_out_template($this->getdata());
	
			}
										//$this->TYPE[$this->idx]
										
		
		
		
	//echo $type . ' ' . get_class($obj->get_requester());
	}
}

?>
