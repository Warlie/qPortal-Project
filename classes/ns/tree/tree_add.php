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

class TREE_add extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
private $caseFolding = XML_CASE_FOLDING_DEFAULT;
	

function &get_Instance()
{
return new TREE_add();
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
	if($uri == 'http://www.trscript.de/tree#template')
	{
	$this->to_listener();
	
	}
}

function event_message_in($type,&$obj)
	{

//$this->giveOutOverview();
		// doctype contains the supported document type
		if(is_Null($this->get_attribute('doctype')))
		$doc_type = 'XML';
		else
		$doc_type = $this->get_attribute('doctype');
										

										
		//it is the conventional case-folding
		if($this->get_attribute('case_folding')) 
			$this->caseFolding = intval($this->get_attribute('case_folding'));

		//var_dump($this->getdata());
		/*
		* if there is no URL, it will act as an incoming document
		*/
				if(is_null($this->getdata())){
					
					if($method = $this->get_attribute('method') && $id = $this->get_attribute('id') )
					{
						//var_dump($_PUT);



						$output = file_get_contents('php://input');
						
//var_dump($doc_type,$id);
						$this->get_parser()->setNewTree($id);
						//var_dump($output, $doc_type);
						$this->get_parser()->load_Stream($output,$this->caseFolding,$doc_type,$id);

						$obj->get_requester()->set_template($this->get_attribute('id'),$this->get_attribute('id'));
						return;

					}else

					return ;
				}
				else
				{

				$com_parameter = [];
				$com_parameter["Method"] = REST_Connection::GET;
				
					if(($method = $this->get_attribute('method')) && $id = $this->get_attribute('id') )
					{

						$com_parameter["Method"] = $method;

						$head = $this->findListByName('http://www.trscript.de/tree#header', $this);
						
						if(count($head) > 0)
						{
							$com_parameter ["RequestHeaders"] = [];
							$tmp_header_parameter =$this->findListByName("http://www.trscript.de/tree#param", $head[0]);
							
							for($i = 0; $i  < count($tmp_header_parameter); $i++)
								$com_parameter ["RequestHeaders"][] = $tmp_header_parameter[$i]->get_attribute('name') . ": " .  
									trim($tmp_header_parameter[$i]->getdata());


						}	
						// $template = $obj->get_requester()->get_template($other_template)
						// 	$this->get_parser()->change_URI($template);
						//save_stream($format = ''
						// ->save_Stream($out,$set_header)
						//$this->get_attribute('id')
						if($body = $this->get_attribute('body'))
						{

							$cur_idx = $this->get_parser()->cur_idx();
							$this->get_parser()->change_URI(
								$obj->get_requester()->get_template($body)
								);
							$com_parameter ["RequestBody" ] = $this->get_parser()->save_Stream();
							$com_parameter ["Parameters"] = [];
							$this->get_parser()->change_idx($cur_idx);
							
						}
							
						$tmp_parameter =$this->findListByName("http://www.trscript.de/tree#param", $this);
						
						if(count($tmp_parameter) > 0)
						{
							$com_parameter ["Parameters"] = [];
							
							for($i = 0; $i  < count($tmp_parameter); $i++)
								$com_parameter ["Parameters"][$tmp_parameter[$i]->get_attribute('name')] = 
									trim($tmp_parameter[$i]->getdata());


						}	
	//print($content->getoutput(SEND_HEADER,'ISO 8859-1'));
						//$com_parameter["RequestBody"]
						
					}
		

		//calls specific parser based on doc-type									
		$this->get_parser()->load(trim($this->getdata()),$this->caseFolding,$doc_type, $com_parameter );
		
		//TODO expands parser with Exceptions
		if($this->get_parser()->error_num() <> 0)
			throw new ErrorException($this->get_parser()->error_num() . ':' . $this->get_parser()->error_desc());									
										
			// saves output format					
			if($preload = $this->get_attribute('doctype_out'))							
				$this->get_parser()->TYPE[$this->get_parser()->idx] = $preload;

											

		$this->get_parser()->set_first_node();
		
		
										
			$uri = $this->getRefprev()->full_URI();
			if($uri == 'http://www.trscript.de/tree#template' )
			{
				//var_dump(get_class($obj->get_requester()),$this->get_attribute('id'), trim($this->getdata() ));
				$obj->get_requester()->set_template($this->get_attribute('id'),trim($this->getdata()));
				
				
	
			}

}
		
		
	}
	
	private function findListByName($name, $node)
	{
		$res = [];
							
		for($i = 0; $i  < $node->index_max(); $i++)
			if($node->getRefnext($i)->full_URI() == $name)
				{
					$res[] = $node->getRefnext($i);
				}
	
		return $res; 
	}					
	
}

?>
