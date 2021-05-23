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

class TREE_object extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
var $obj_init = array();
	
function __construct()
{

}

function &get_Instance()
{
return new TREE_object();
}


function event_initiated()
{

	//echo $this->get_attribute('id') . "<br>\n";
	$uri = $this->getRefprev()->full_URI();
	if( $uri == 'http://www.trscript.de/tree#content' 
		|| $uri == 'http://www.trscript.de/tree#param' 
		|| $uri == 'http://www.trscript.de/tree#program'
		|| $uri == 'http://www.trscript.de/tree#element'
		|| $uri == 'http://www.trscript.de/tree#remote'
	)
	{
	
        //echo $this->getRefprev()->full_URI() . ' ' ;
	$this->to_listener();

	
	}

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
/**
*
*/
function event_message_in($type,&$obj)
	{
	
	$parser = &$this->get_parser();
		
	if($obj->get_node())
	{
		

		
		
		$this->get_parser()->go_to_stamp( '0000.' . $obj->get_node()->get_idx() . $obj->get_node()->position_stamp());
	}
	//echo $obj->get_node()->full_URI();
	//else
	//echo "\n kein Objekt im request \n";
		global $logger_class;
	/*-------------------------------------------------
	* standardattributes are classname, instanceid and src
	* classname : is name of class to call
	* instanceid : contains a name of a class instance
	* src : contains a path to a codefile
	* also it gets the registry-document to deal with.
	*--------------------------------------------------*/
	
		//gets all attributes
		$class_Name = $this->get_attribute('name');
		$instance_id = $this->get_attribute('id');
		
	//---------------------------------------------------------------------------------
	//--                       voids multiple entry instancing                       --
	//---------------------------------------------------------------------------------
	
		if($class_Name)
		 {	
		 
		  if(in_array($class_Name . ':' . $instance_id,$this->obj_init))return false;
		  $this->obj_init[count($this->obj_init)] = $class_Name . ':' . $instance_id;
		//echo ' - ' . $class_Name . ';' . $instance_id . "\n";
		 }
	//---------------------------------------------------------------------------------
	
	//---------------------------------------------------------------------------------
	//--                        gets PEDL Controlweb                                 --
	//---------------------------------------------------------------------------------

		$reg_name = $parser->getControlUnit( "surface_tree_engine")->getRegistrySpace();
	//echo ' -' . $reg_name . ' ' . get_Class($parser->getControlUnit( "surface_tree_engine")) . '- ';
	//---------------------------------------------------------------------------------
		
	/*-------------------------------------------------
	* on nonexisting classname shows an allready existing 
	* object to dealing with. 
	*--------------------------------------------------*/
		if(!$class_Name)
		{
	//------------------Allready existing name---------------------------
			//gives out first existing instance to id
			
			$object = $parser->getControlUnit( "surface_tree_engine")->getObjectByID($instance_id);
			
			//echo "\n<br>my logic is undeniable!<br>\n";

			for($j = 0;$j<count($object->way_out);$j++)
			{
			if($object->way_out[$j]->name <> 'remote')
			{
			$this->set_to_out($object->way_out[$j]);

			}
			}
			$obj_array = array();

				//walks trough all childnodes 
				for($i = 0;$this->index_max() > $i;$i++)
					{
						
						//goes to the remote tags
						if($this->getRefnext($i)->is_Node('http://www.trscript.de/tree#remote'))
						{
							//add the current remote-object
							$obj_array[$i] = &$this->getRefnext($i);
							
							
							//creates correct name of the function
							$node_func = $reg_name . '#' . 
							$this->getRefnext($i)->get_ns_attribute('http://www.trscript.de/tree#name');
							
							//
							$this->getRefnext($i)->connect_uri($node_func);
							$send = '*?__redirect_node='
							. rawurlencode( base64_encode( $node_func . '?__add_in_object' )) ; //http://www.trscript.de/tree#name
							$booh = null;
							$Event = new EventObject('',$this,$booh);
							$Event->set_node($this->getRefnext($i));
//echo 'boohme' ."<b/>\n";
							//echo "und eierabend :)";
							$this->send_messages($send,$Event);
							//echo "ende";
							//for($j = 0;$j<count($this->getRefnext($i)->way_out);$j++)
							//{
							//echo '-' . $this->getRefnext($i)->way_out[$j]->name . ':' .  $this->getRefnext($i)->way_out[$j]->get_attribute('name') . "<br>\n";			
							//}
							
							

							//for($l = 0;count($this->way_out) > $l;$l++)
							//{
							//echo $this->way_out[$l]->full_URI() . ' ';
							//}
							//$this->getRefnext($i)->event_alterdata(true);
							

		
							
						}
					}
			
			for($j = 0;$j<count($obj_array);$j++)
			{
			$this->set_to_out($obj_array[$j]);
			
			}
			
			//for($j = 0;$j<count($this->way_out);$j++)
			//{
			//echo '-' . $this->way_out[$j]->name . ':' .  $this->way_out[$j]->get_attribute('name') . " my logic...<br>\n";
			//}
			$booh = null;
			unset($Event);
			$Event = new EventObject('',$this,$booh);
			$send = 'http://www.trscript.de/tree#remote';
			//!!!!!überprüfen

			$Event->set_node($obj->get_node());
			$this->send_messages($send,$Event);
			
			//test

			
			if($this->index_max() > 0)
			if($tmp = &$this->getRefnext($this->index_max() - 1)->getdata(0))
			{
				
				if(is_Object($tmp))
				{
					
					$this->setdata($tmp,0);
				}
				elseif(strlen($tmp) > 0)
				{
					$booh = $tmp;
					$this->setdata($booh,0);
				}

			}
			
				
			//echo 'raus';
			return false;
		}
		else
		{
		//------------------Non existing name---------------------------
			/*-------------------------------------------------
			* set object-node by id to workspace-control
			* 
			*--------------------------------------------------*/
			//adds object to engine depending to name 
			$parser->getControlUnit( "surface_tree_engine")->setObjectByID($this,$instance_id);
		}
		
		
		//get current workspace
		$reg_name = $parser->getControlUnit( "surface_tree_engine")->getRegistrySpace();
		//saves old position
		$stamp = $parser->position_stamp();
		//change position to workspace
		$parser->change_URI($reg_name);
		//saves current index
		$idx_num = $parser->cur_idx();
	
		//finds systemnodes in workspace and saves curref
		$parser->flash_result();
		$temp_stamp = $parser->position_stamp();
		
		if($parser->seek_node('@registry_surface_system#System.CurRef',null,null))
			{
				//echo "gefunden";
				$parser->show_xmlelement()->setdata($this,0);
			}
			else
			{
				echo "nicht gefunden";

			}
			
			if($parser->seek_node('@registry_surface_system#System.EffBranch',null,null))
			{
				//echo "gefunden"; 
				//echo get_class($obj->get_requester());
				$parser->show_xmlelement()->setdata($obj->get_node(),0);

				
			}
			else
			{
				echo "so ein Mist";

			}
			
		//change to controldoc
		$parser->go_to_stamp($stamp);
		
		$parser->flash_result();
		//saves attributes (could be useless)
		$class_Name = $this->get_attribute('name');
		$instance_id = $this->get_attribute('id');
		
//echo $idx_num . ' --';
		
		//echo $class_Name . '0000.' . $this->get_idx() . $this->position_stamp();
		//
		//$parser->test_consistence();
		//$parser->ALL_URI();
//echo '"' .  $parser->cur_idx() . '"';	

		/*
		* looks in tree for allready existing classdescriptions and adds it if not exist
		* @see_also PHPhandle  
		* TODO geeignet f�r exception
		*/
		//
		if($parser->seek_node('@registry_surface_system#PhpClass',array('@registry_surface_system#ID' => $class_Name),null))
			{
				echo "gefunden";
			}
			else
			{
			
				//echo "nicht gefunden";
				$load_url = $this->get_attribute('src');
				if(!is_null($load_url))$parser->load($load_url,0,'PHP');
				
				//echo $idx_num . ' --';
			}
		
			//change to workspace 
			$parser->change_idx($idx_num);
			//prepair to find Class_Instance
			$parser->flash_result();
			
			//$parser->test_consistence();
		//echo 'booh';
		
			if($parser->seek_node('@registry_surface_system#Class_Instance',null,null,0))
			{
		
				if($parser->index_child() < 1) throw new ErrorException('Invalide workbench ', 0,100,"tree_object.php",236);
				$parser->child_node(0);
				//finds bag in node, ascertains its posstamp and creates a new instance
				$node_obj = &$parser->show_xmlelement();

				
				for($i = 0;$node_obj->index_max() > $i;$i++)
					{
						if($node_obj->getRefnext($i)->full_URI() == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Bag')
						{	//ascertains posstamp 
							$new_stamp =  '0000.' . $node_obj->get_idx() . $node_obj->position_stamp();
							break;
						}
					}
					//create instance and throws exception in case of not existing class
					$attrib = array('rdf:ID' => $instance_id);
					//echo "<info style=\"color:blue;font-size:200%\" >";
					if(!$parser->create_Ns_Node($class_Name,$new_stamp,$attrib))
					{
						
					$parser->test_consistence();
						throw new ErrorException('Cannot instancing following class:' . $class_Name . ' ', 0,75,$parser->getControlUnit( "surface_tree_engine")->getSystemSpace(),1);
					}
					
					
					if('PEDL_Object_Class' == get_class($parser->show_xmlelement()))
					$parser->show_xmlelement()->set_executive();
					//$parser->show_xmlelement()->get_classes();
				$parser->executed();
				//saves new created instance in $node_obj and unbound it before
				unset($node_obj);
				$node_obj = &$parser->show_xmlelement();
				//lists its position
				$instance_stamp = $parser->position_stamp();
					
					//create a constructor
					$attrib = array();
					if(!$parser->create_Ns_Node($class_Name . '.__construct',null,$attrib))
					{
					!$parser->test_consistence();
						throw new ErrorException('Cannot instancing following constructor:' . $class_Name . '.__construct ', 0,75,$parser->getControlUnit( "surface_tree_engine")->getSystemSpace(),1);
					}

					//$parser->show_xmlelement()->get_classes();
				$parser->executed();
				//echo "</info><br>\n";
			}
			
			//echo $parser->cur_node();
			//$parser->test_consistence();
		
			//$parser->go_to_stamp($stamp);
			//echo $node_obj->full_URI() . ' ';
			/*
			*set Instance as a direct listener of this objectnode
			*/
			$this->set_to_out($node_obj);
			//echo $node_obj->full_URI();

				//walk through all remotenodes
				for($i = 0;$this->index_max() > $i;$i++)
					{
					/*
					*
					* @tricky : Beware of confusing!
					* pedl_class_node is a listener to objectnode,
					* objectnode will send a redirect command to its listener, together with a reference of 
					* current remotenode. Class_node will send it to its listeners. These are all there childs,
					* but only the node, which matches the URI, will add itself to current remotenode
					*/
						
						if($this->getRefnext($i)->is_Node('http://www.trscript.de/tree#remote'))
						{
							//ascertains full URI of the specific function- or parameternode to find it 
							$node_func = $node_obj->get_NS() . '#' . $this->getRefnext($i)->get_ns_attribute('http://www.trscript.de/tree#name');				
							
							
							$this->getRefnext($i)->connect_uri($node_func);
							$send = $node_obj->full_URI() . '?__redirect_node='
							. rawurlencode( base64_encode( $node_func . '?__add_in_object=0' )) ; //http://www.trscript.de/tree#name
							$logger_class->setAssert('               redirect to "' . $node_obj->full_URI() . '" to sent a direct connection betweens "' . $node_func . '?__add_in_object=0" and ' . $this->getRefnext($i)->full_URI() . ' ("' . $this->get_attribute('name') . '")(tree_object:event_message_in)' ,5);
							
							$booh = null;
							$Event = new EventObject('',$this,$booh);
							$Event->set_node($this->getRefnext($i));
							
							$this->send_messages($send,$Event);
						
							/*
							* call of alterdata in 
							*
							*/
							$this->getRefnext($i)->to_listener();
							//for($i = 0;count($this->way_out) > $i;$i++)
							//{
							//echo $this->way_out[$i]->full_URI() . ' ';
							//}
						}
					}

			$booh = null;
			unset($Event);
			$Event = new EventObject('',$this,$booh);
			$send = 'http://www.trscript.de/tree#remote';
			$this->send_messages($send,$Event);
		
			//$parser->go_to_stamp($instance_stamp);
			if($this->index_max() > 0)
			if($tmp = &$this->getRefnext($this->index_max() - 1)->getdata(0))
			{
				
				if(is_Object($tmp))
				{
					$this->setdata($tmp,0);
				}
				elseif(strlen($tmp) > 0)
				{ 
					$booh = $tmp;
					$this->setdata($booh,0);
				}

			}
			
			
		//echo $this->get_attribute('name');
		//$parser->index_consistence();
		
	//echo $type . ' ' . get_Class($obj);
	}
	
	public function __toString()
     {
         return 'Tree_Object:' . $this->namespace . '#' . $this->name;
     }
	
}



?>
