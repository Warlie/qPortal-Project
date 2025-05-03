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


	
function event_message_in($type,&$obj)
	{

		global $_SESSION;
		$json = '{"name":"http://www.trscript.de/tree#final"}';

				$result = true;

		if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#sector') )		
			$result = in_array($tmp, explode(';', trim_with_null($_SESSION['http://www.auster-gmbh.de/surface#sector'], ';')));
		/*
		if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#method') )		
			$result = $_SERVER['REQUEST_METHOD'] === $tmp;
*/
				
		if($tmp =  intval($this->get_ns_attribute('http://www.trscript.de/tree#securitylevel')) )
		{
			
			if($_SESSION['http://www.auster-gmbh.de/surface#securityclass'])
				$sec = intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']);
			else
				$sec = -1;
				
			
			$result = $result  &&  ($tmp == -1) || ((($tmp != -1) &&  ($sec >= $tmp))) ; 
		}	
			

		//useless Exception
		if(!$result) throw new NoPermissionException('not Allowed');

		
		// consider the aspect for the next branch (tree)
		
		if($aspect = $this->get_ns_attribute('http://www.trscript.de/tree#consider_aspect') )
		{
			
				if(array_key_exists('Attribute',$swap = $type))
					if(array_key_exists($aspect,$type['Attribute']))
					{
						
						$json =  '{ "attribute":{"http://www.trscript.de/tree#name":"';
						$json .= $type['Attribute'][$aspect];
						$json .= '"}}';

					}
				

		}			
	
	$find = ["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>$json], "Value"=>$type ]] ;
		
	//var_dump($find);
	
//var_dump($type, $this);
	if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#src'))
	{
		
		//echo $tmp . "\n";
		$tmp = str_replace( '%ROOT_DIR%', ROOT_DIR, $tmp);
				 
		if(is_file($tmp))
		{
			$this->get_parser()->load($tmp,0);
			
		//$this->get_parser()->ALL_URI();
			
			//var_dump($this->get_parser()->show_xmlelement());
			$this->get_parser()->show_xmlelement()->event_message_check($find ,$obj);
			//$this->get_parser()->show_xmlelement()->event_message_in($find ,$obj);
			

		/*
			if()
			$this->get_parser()->show_xmlelement()->event_message_in( 
				["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>'{"name":"http://www.trscript.de/tree#final"}'], "Value"=> ["Identifire"=>"*", "Command"=> ["Name"=> "start" ], "Attribute"=>$this->param] ]] //["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>'{"name":"http://www.trscript.de/tree#final"}'], "Value"=> '*?start']]
				,$obj);
			else

			$this->get_parser()->show_xmlelement()->event_message_in(
				["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>'{ "attribute":{"http://www.trscript.de/tree#name":"' . $this->nodeName . '"}}'], "Value"=> ["Identifire"=>"*", "Command"=> ["Name"=> "start" ], "Attribute"=>$this->param]  ]]
				,$obj); //  "name":"http://www.trscript.de/tree#tree",

		*/
		
		
			//$this->get_parser()->seek_node('http://www.trscript.de/tree#final');
			//$this->get_parser()->show_xmlelement()->event_message_in($type,$obj);
			return true;
		}
		
		return false;
	}
	else
		if( ! count($this->way_out))
			throw new EmptyTreeException("Empty tree");

	
		$obj->set_context($this);
		$this->send_messages($type,$obj);

		/*
		//calls all childnodes, which are not template and tree
		for($i = 0 ; $i < $this->index_max();$i++)
			{
			$tmp = $this->getRefnext($i,true);
			
			if($tmp->full_URI() <> 'http://www.trscript.de/tree#template' 
			&&  $tmp->full_URI() <> 'http://www.trscript.de/tree#tree'
			)
			{
				//echo $tmp->full_URI() . "- \n";
				
				$tmp->event_message_in($type,$obj);
			}
			}
	//}
	*/
	}
}

?>
