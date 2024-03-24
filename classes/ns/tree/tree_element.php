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

class TREE_element extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
private $cdata_switch = false;
	
function __construct()
{

}

function &get_Instance()
{
return new TREE_element();
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
	$uri = $this->getRefprev()->full_URI();
	if( $uri == 'http://www.trscript.de/tree#content' 
	|| 
	$uri == 'http://www.trscript.de/tree#element'
	|| 
	$uri == 'http://www.trscript.de/tree#programm')
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
		
		//echo $this->get_attribute('value') . ' ' . $type . ' ' . $obj->get_node()->name . "<br>\n";
	
	//checks for type 
	
	$tag_array = array();

			// looks for a type-attibute
			if ($att_type = $this->get_attribute('type'))
			{
						//collects all param-tags
						for($i = 0 ; $i < $this->index_max();$i++)
						{
							$tmp = &$this->getRefnext($i,true);
							//activates all param tags
							if($tmp->full_URI() == 'http://www.trscript.de/tree#param')
							{$res =  $tmp->getdata();
								$tmp->send_messages($type,$obj);
								if($tmp->getRefnext(0) instanceof Interface_node)
								{
								$many_remote = $tmp->getRefnext(0)->index_max();
								
								if(0 < $many_remote)
								{
							
								$res .= $tmp->getRefnext(0)->getRefnext($many_remote - 1)->getdata(0); //
								//$tag_array[$tmp->get_attribute('name')] = $tmp->getRefnext($many_remote - 1)->getdata(0);
								}
								}
								$tag_array[$tmp->get_attribute('name')] = $res;
								
							}
							unset($tmp);
							unset($res);
						}
				
				if ($att_mode = $this->get_attribute('mode'))
				{
					
					$this->cdata_switch = ($att_mode == 'CDATA');
					
				}
				
			
				if(strtolower($att_type) == 'xhtml')
					if($value = $this->get_attribute('value'))
					{
						
						//echo "new";
						//creates a new xml-element
						$this->process_new_xhtml($obj,$value,$tag_array);
					}
					else
					{
						//echo "exists";
						//alters a existing xml-element
						$this->process_exist_xhtml($obj,$tag_array);
					}
				
			}
			
	}

// creates new tag in specific tree
function process_new_xhtml(&$obj,$name,$attrib)
	{
		//$get_fu
		//echo $name . ' <br>';
		//tests for namespace with qname or just qname
		//creates an object depending on Namespace 
		if(false === ($posinStr = strpos($name,'#')))
		{
			
		$new_node = $this->get_parser()->get_Object_of_Namespace( $obj->get_requester()->get_Main_NS() . '#' . $name );
		$new_node->name = $name;
		
		$prefix = $obj->get_requester()->get_Main_NS();
		$postfix = $name;
		 
		}
		else
		{
			
			//set_idx
			$prefix_full = substr($name,0,$posinStr);
			$postfix_full = substr($name,$posinStr + 1);
			
		$new_node = $this->get_parser()->get_Object_of_Namespace( $name );
		if(!$obj) throw new ErrorException('no tree for ' . $name, 169,71);
		//echo $obj->get_node()->get_id();
		$prefix = $this->get_parser()->get_Prefix(substr($name,0,$posinStr),$obj->get_node()->get_idx());
		$postfix = substr($name,$posinStr + 1);
		
		
		
		if(strlen($prefix) > 0)
			$new_node->name = $prefix . ':' . $postfix;
		else
			$new_node->name = $postfix;
		
		
		}
		

		$new_node->type = $postfix_full;
		$new_node->namespace = $prefix_full ;
		$new_node->set_idx($obj->get_node()->get_idx());

		//$new_node->name = $name;
		//collects all param-tags
		//adds all attributes from array
		if(is_array($attrib))
			foreach ($attrib as $key => $value)
			{
				$ns_qname = strpos($key,'#');
				
				if(false === $ns_qname )
				{
				//for nonexisting namespace	
	
					$attrib_obj = $this->get_parser()->get_Object_of_Namespace( $obj->get_requester()->get_Main_NS() . '#' . $key );
				
					$attrib_obj->setdata($value,0);
					
					$new_node->attribute($key, $attrib_obj);
					unset($value);
					unset($attrib_obj);
				}
				else
				{
				//for existing namespacese 

					$attrib_obj = $this->get_parser()->get_Object_of_Namespace(  $key );
					$attrib_obj->namespace = $obj->get_node()->get_idx();
											
					$prefix = $this->get_parser()->get_Prefix(substr($key,0,$ns_qname),$obj->get_node()->get_idx());
					$postfix = substr($key,$ns_qname + 1);
					
					//not nice, set namespace and type
					$attrib_obj->namespace = substr($key,0,$ns_qname);
					$attrib_obj->type = $postfix; 

					
					
					$attrib_obj->setdata($value,0);
					unset($value);
		
							if(strlen($prefix) > 0)
							{
								$attrib_obj->name = $prefix . ':' . $postfix;
								$new_node->attribute( $prefix . ':' . $postfix, $attrib_obj);
							}
							else
							{
								$attrib_obj->name = $postfix;
								$new_node->attribute( $postfix, $attrib_obj);
							}
					unset($attrib_obj);
					

					
				}
				

			}
			
		$this->get_parser()->set_new_index($new_node);	

		//catches current node from eventobject and add the new node
		$cur_element = &$obj->get_node();
		$cur_element->setRefnext( $new_node );
		$new_node->setRefprev($cur_element);
			//echo $name . ' = ' . $this->get_data_many() . ' <br>';

		//prepare eventobject for all listet nodes in this node
		$new_event = new EventObject($obj->get_request(),$obj->get_requester(),$obj->get_context());
		if($this->cdata_switch)$new_node->set_bolcdata(!$new_node->get_bolcdata());
		$new_event->set_node($new_node);
		 //new node to next messaging
		$this->send_messages('',$new_event); 

		//adds textnodes if exist		
		if((0 < $this->get_data_many()) || (0 < $this->index_max()))
		{
		
		
			
			$string = '';
			$point = 0;
			
			//loop for all textnodes
			for($i = 0; $i <= $this->index_max() ;$i++)
			{
			
				
				if($i == $this->index_max())
				{
				//on last textnode
					//echo $i . ' ' . $new_node->getdata($point);
						$string .= $this->getdata($i);
						
						
						$new_node->setdata($string,$point);
				}
				else
				{
					//echo 'booh';
					//echo get_Class($this->getRefnext($i,false));
					
					if($this->getRefnext($i,false)->full_URI() == 'http://www.trscript.de/tree#element')
					{
						$string .= $this->getdata($i);
						
						$new_node->setdata($string,$point++);
						
						$string = '';
					}
					elseif($this->getRefnext($i,false)->full_URI() == 'http://www.trscript.de/tree#object')
					{ //echo 'booh';
						$string .= $this->getdata($i);
						
						$many_remote = $this->getRefnext($i)->index_max();
						if(0 < $many_remote)
						{
						$string .= $this->getRefnext($i)->getRefnext($many_remote - 1)->getdata(0);
						
						}
						
						//$string .= $this->getRefnext($i)->getdata(0);

						
						//$new_node->setdata($string,$point++);
						
						//$string = '';
					}
					else
					{
						$string .= $this->getdata($i);
						
					}
					//echo " \n" . $i . ' ' . $string . " \n";
					
				}
			}
			
			
		}



//$this->get_parser()->show_index();
		

	}
	
// edit tag in specific tree
function process_exist_xhtml(&$obj,$attrib)
	{		
//var_dump($attrib);
		$cur_element = &$obj->get_node();
		//$new_node->name = $name;
		//collects all param-tags
		if(is_array($attrib))
			foreach ($attrib as $key => $value)
			{
				$ns_qname = strpos($key,'#');
				
				if(false === $ns_qname )
				{
					
					
					$myattrib = $this->get_parser()->get_Object_of_Namespace( $obj->get_requester()->get_Main_NS() . '#' . $key );
					$tmp = $value;
					unset($value);
					
					$myattrib->setdata($tmp,0);
					unset($tmp);
					$cur_element->attribute($key, $myattrib);
					unset($myattrib);
					
				}
				else
				{
					//echo $key . " \n";
					$attrib = $this->get_parser()->get_Object_of_Namespace(  $key );
											
					$prefix = $this->get_parser()->get_Prefix(substr($key,0,$ns_qname),$obj->get_node()->get_idx());
					$postfix = substr($key,$ns_qname + 1);
					
					$attrib->setdata($value,0);
		
							if(is_string($prefix) && strlen($prefix) > 0)
							{
								$attrib->name = $prefix . ':' . $postfix;
								$cur_element->attribute( $prefix . ':' . $postfix, $attrib);
								
							}
							else
							{
							
								$attrib->name = $postfix;
								$cur_element->attribute( $postfix, $attrib);
							}
						

					
				}
				

			}
		
		$new_event = new EventObject($obj->get_request(),$obj->get_requester(),$obj->get_context());
		$new_event->set_node($cur_element);
		//echo $obj->get_request();
		//echo $cur_element->full_URI();
		if($this->cdata_switch)$cur_element->set_bolcdata(!$cur_element->get_bolcdata());
		 //new node to next messaging
		$this->send_messages('',$new_event);
			
		if((0 < $this->get_data_many()) || (0 < $this->index_max()) )
		{
			$string = '';
			$point = 0;
			
			for($i = 0; $i < $this->index_max() + 1;$i++)
			{
			
				if($i == $this->index_max())
				{
					
						$string .= $this->getdata($i);
						
						$cur_element->setdata($string,$point);
				}
				else
				{
					//echo 'booh';
					//echo get_Class($this->getRefnext($i,false));
					
					if($this->getRefnext($i,false)->full_URI() == 'http://www.trscript.de/tree#element')
					{
						$string .= $this->getdata($i);
						
						if($this->getRefnext($i)->is_Node('http://www.trscript.de/tree#element'))
						{
							$string .= $this->getRefnext($i)->getdata(0);
						}
						
						$cur_element->setdata($string,$point++);
						
						$string = '';
					}
					elseif($this->getRefnext($i,false)->full_URI() == 'http://www.trscript.de/tree#object')
					{ 
						$string .= $this->getdata($i);
						
						$many_remote = $this->getRefnext($i)->index_max();

						//echo $this->getRefnext($i)->get_data_many();
						if($many_remote <> 0)$string .= $this->getRefnext($i)->getRefnext($many_remote - 1)->getdata(0);
						
						//echo $string;
						
						//$cur_element->setdata($string,$point++);
						//$string .= $this->getRefnext($i)->getdata(0);

						
						//$new_node->setdata($string,$point++);
						
						//$string = '';
					}
					else
					{
						$string .= $this->getdata($i);
					}
					
				}
			}
		}




		

	}
}

?>
