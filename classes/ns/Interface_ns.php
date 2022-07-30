<?php

/**  Interface
*
* full_URI() : Gives out full Namespace with nodetype delimited with #
* get_NS() : Gives out nodetype
* get_QName() : Gives out Namespace
* position_stamp() : standard position stamp based on the index of spezific tree
* exhaustion() : (needs to be updated) removes all branches for this object
* &getRefnext($index,$bool_set=false) :get child nodes
* &getRefprev() : gets parent node
* index_max() : get many of child nodes
* setRefnext(&$ref) :add new child nodes 
* setRefprev(&$ref) :add parent nodes
* set_parser(&$obj) :add parserobject
* get_parser() :get parser
* get_idx()
* attribute($name,(<String>||<Interface_node>)&$value) :sets attributes 
* get_attribute($name = '') : gets an attibutes value
* get_ns_attribute($URI) : get an attribute depending of qname with namespace
* setdata($data,$pos = null) : sets a String, object or number to data
* &getdata($pos = null) : gets data
* data_many() : many of data
* function set_bolcdata($bool) : en/disables cdata notation
* get_bolcdata() : show setting
* final_data() : internal value, shows that an node is complete
* &linkToClass() : ref to classobject
* listOfListeners_stamp()
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
* addToTicketEvent($eventOnFullNS , Interface_node &$listener)
* TicketEvent(&$ticketObject)
* linkToInstance($pos) :get link to Instance- object
* linkToClass() : get link to class-objekt
* ManyInstance(): show many Instance-objects
* is_Class(): selects between classes and instances
* is_Node($nodename): checks for its own an its parentnodenames
* is_Command($nodename,$funcname)
* parseCommand($command);
*/

/*
* TODO
* clean up and set more comments
*/

//kind of node
define('NODE',0);
define('ATTRIBUTE',1); //doppel
define('DATA',2);

//modus
define('FUNCTION','');
define('SHOW','show');
define('OK',true);


class Interface_node extends XMLelement_objex
{

public $next_el;
var $prev_el;
var $name = '';
var $attrib;
protected $attrib_ns = array();
var $data;
var $cdata = false;

var $index = 0;
private $is_Class = false;


//for Namespace
var $type = 'rootnode';
var $namespace = 'default';
var $parser = null;
private $kind_of_node = NODE;

//becomes written by its attributes
var $start_list = array();
var $check_list = array();
var $link_to_class = null;
protected $link_to_instance = array();
var $way_out = array();
var $way_in = array();
var $test = 'testme';
private $idx = 0;
private $alter_sensity = false;
private $read_sensity = true;

var $position=-1;
var $mark = false;


public function listOfListeners_stamp($add = '0000.')
{
$res = array();

	for($i = 0;$i < count($this->way_out);$i++)
	{
	$res[$i] = $add .  $this->way_out[$i]->idx . $this->way_out[$i]->position_stamp();
	}
	return $res;
}

public function Class_stamp($add)
{

	if($this->link_to_class)
		if($this->link_to_class->link_to_class)
			return $add . $this->link_to_class->link_to_class->idx . $this->link_to_class->link_to_class->position_stamp();
	return false;
}

public function set_NodeType($type)
{

	$this->kind_of_node = $type;
}

public function get_NodeType()
{
	return $this->kind_of_node;
}

function get_classes()
{
echo $this->full_URI() . "<br>\n";
if($this->link_to_class)$this->link_to_class->get_classes();
}

function set_idx($idx)
{
	$this->idx = $idx;
}

function get_idx()
{
	return $this->idx;
}

protected function set_is_Class()
{ $this->is_Class = true ;}

public function is_Class()
{return $this->is_Class;}

function full_URI()
{
	return $this->namespace . '#' . $this->type;
}

function get_NS()
{
	
return $this->namespace ;
}

function get_QName()
{
return $this->type;
}

public function &linkToClass()
{
return $this->link_to_class;
}

public function &linkToInstance($pos)
{
if(count($this->link_to_instance) <= $pos)return false;
return $this->link_to_instance[$pos];
}

public function ManyInstance()
{
return count($this->link_to_instance);
}

public function is_Node($name)
{
	if(!(false === ($tmp = strpos($name,'?'))))
	{
		
		$name = substr($name,0,$tmp);
	}
	
	if(!(false === ($tmp = strpos($name,'*'))))
	{
		
		if( substr($name,0,$tmp - 1) == $this->get_NS())return true;
	}
	
	if($name == '')return true;
	if($name == '*')return true;
	if($name == $this->full_URI())return true;
	
	if(is_object($this->link_to_class))
	{
		
		return $this->link_to_class->is_Node($name);
	}
	else
	{
		return false;
	}

}

public function is_Command($name,$funcName)
{
	//echo '(' . $name . ')<br>';
if($this->is_Node($name))
	{
		//echo $name . ' !<br>';
			if(!(false === ($tmp = strpos($name,'?'))))
			{
		
				if(false === ($tmp2 = strpos($name,'=')))
					$tmp2 = strpos($name,'&');
					
				if(!(false === $tmp2))
				{
					//echo '1 ' . $funcName . ' == ' .  substr($name,$tmp + 1 ,$tmp2 - ($tmp + 1)) . ' ';
					return ($funcName == substr($name,$tmp + 1 ,$tmp2 - ($tmp + 1)));
				}
				else
				{
					//echo '2 ' . $funcName . ' == ' . substr($name,$tmp + 1)  . ' ';
					return ($funcName == substr($name,$tmp + 1));
				}
			}
	}
}

public function parseCommand($command)
{
return new Command_Object($command);
}

/* returns last stamp element */
public function position_last_stamp()
{
	

	if($this->get_NodeType() == 0)
	{

	$elem = &$this->prev_el;
	
	if(is_Null($elem))return '';
	
	for($i = 0; $elem->index_max() > $i;$i++)
	{
		if($elem->getRefnext($i,true)===$this)
		{
			
			return  $i;
		}
	}
	return 'X';

	}

	if($this->get_NodeType() == 1)return '@' . $this->full_URI();
	
	if($this->get_NodeType() == 2)return '#' . $this->get_QName();
	
}

function position_stamp()
{
	

	if($this->get_NodeType() == 0)
	{

	$elem = &$this->prev_el;
	
	if(is_Null($elem))return '';
	
	for($i = 0; $elem->index_max() > $i;$i++)
	{
		if($elem->getRefnext($i,true)===$this)
		{
			
			return $elem->position_stamp() . '.' . $i;
		}
	}
	return $elem->position_stamp() . '.X';

	}

	if($this->get_NodeType() == 1)
	{
	$elem = &$this->prev_el;
	
	if(is_Null($elem))return '';
	

			
	return $elem->position_stamp() . '@' . $this->full_URI();


	}
	
	if($this->get_NodeType() == 2)
	{
	$elem = &$this->prev_el;
	
	if(is_Null($elem))return '';
	

			
	return $elem->position_stamp() . '#' . $this->get_QName();


	}
	

	
}
/**
* creates a hashnumber, depending on the path, back to the root.
* @param stamp : (callbyRef) String to create a std. stamp without an idx  
*/
function position_hash_map(&$stamp)
{
 	$mult = $this->get_NodeType() + 1;
 
	$elem = &$this->prev_el;
	
	if(is_Null($elem))return 0;
	
	for($i = 0; $elem->index_max() > $i;$i++)
	{
		if($elem->getRefnext($i,true)===$this)
		{
			
			$stamp =   '.' . $i . $stamp;
			return $i * $mult * $elem->index_max() + 1 + $elem->position_hash_map($stamp);
		}
	}
	
	if($mult == 2)$stamp . '@' . $this->full_URI();
	if($mult == 3)$stamp . '#' . $this->get_QName();
	
	
	return $mult * 7 + $elem->position_hash_map($stamp);
	
}


function posInPrev()
{
	

	if($this->get_NodeType() == 0)
	{

	$elem = &$this->prev_el;
	
	if(is_Null($elem))return '';
	
	for($i = 0; $elem->index_max() > $i;$i++)
	{
		if($elem->getRefnext($i,true)===$this)
		{
			
			return $i;
		}
	}
	return false;

	}

	return false;
}


//removes all branches
function exhaustion()
{
	unset($this->next_el);
	$this->next_el = array();
	$this->position = 0;
}

//orginal
function &getRefnext($index,$bool_set=false)
{if(!$bool_set)$this->position = $index;return $this->next_el[$index];}
//orginal
function &getRefprev(){return $this->prev_el;}
//orginal
function index_max()
        {
        if(is_array($this->next_el))
	{
	//for($i = 0;count($this->next_el);$i++)
	if($this->next_el[0] instanceof Interface_node)
        return count($this->next_el);
        else
        return 0;
	}
        return 0;
        }
//orginal
function setRefnext(&$ref,$pos = -1){
		if(!($ref instanceof Interface_node))throw new ErrorException('insert not valid childelement');
                
                if($pos == -1)
                {
                if(is_array($this->next_el))

                        $index = count($this->next_el);

                else

                        $index = 0;

                $this->next_el[$index] = &$ref;
		}
		else
		{
		
			if(count($this->next_el) == 0)
			{
				$this->next_el[0] = &$ref;
				return true;
			}
			
			$tmp = array();
			for($i = 0;count($this->next_el) > $i;$i++ )
			{
				$tmp[$i] = &$this->next_el[$i];
				
			}
			
			unset($this->next_el);
			$this->next_el = array();
			$iter = 0;
			for($i = 0;count($tmp) >= $i;$i++ )
			{
				if($i <> $pos)
				{
					$this->next_el[$i] = &$tmp[$iter++];
				}
				else
				{
					$this->next_el[$i] = &$ref;
				}
			}
			
		}

                }
//orginal
function setRefprev(&$ref){$this->prev_el = &$ref;}

function set_parser(&$obj)
{
	$this->parser = $obj;
}

function &get_parser()
{
	return $this->parser;
}





function attribute($name,&$value){
	
	if(is_object($value))
	{
		
		if(is_Object($this->attrib[$name]))
		{
				if($value instanceof Interface_node)
				{
				//$this->attrib[$name]->freedata();
				
				$this->attrib[$name]->setdata($value);
				
				
				}
		}
		else
		{
			
		if($value instanceof Interface_node)
		{
			$value->set_NodeType(1);
			$value->setrefprev($this);
			//echo "\n<br> key ::= " . $value->full_URI() . '=>' . $value->getdata() . ";<p>\n";
			if($this->attrib_ns[$value->full_URI()]) unset($this->attrib_ns[$value->full_URI()]);
			$this->attrib_ns[$value->full_URI()] = &$value;
			$this->attrib[$name] = &$value;
			$value->event_initiated();
			
		}
		
		}
		
	}
	else
	{
		
		$this->attrib[$name] = $value;
		
        }
}
	
	
	public function &get_ns_attribute($uri  = '')
	{
		
							//foreach($this->attrib_ns as $att_key => $att_value )
							//{
							//	echo "\n<br> key ::= " . $att_key . '=>' . $att_value->getdata() . ";<p>\n";
							//}
		
		if($uri<>'')
		{ 
		if(is_object($this->attrib_ns[$uri]))
			
						if($this->attrib_ns[$uri] instanceof Interface_node)
						{
							/*
							foreach($this->attrib_ns as $att_key => $att_value )
							{
								echo $att_key . '=>' . $att_value->getdata() . ";<br>\n";
							}
							echo 'created in 223 ns with ' . $name . '=' . $this->attrib[$name]->getdata() . ';<br>';
							*/
							
						return $this->attrib_ns[$uri]->getdata();
						}
						
							
					
						return false;
		}
		else
		{
			
			
			
			if(!is_null($this->attrib_ns))
			if(is_array($this->attrib_ns))
			{
			//echo $this->name . " " . $this->attrib;
				foreach($this->attrib_ns as $key => $value)
				{
				
					if(is_object($this->attrib_ns[$key]))
					{
						if($this->attrib_ns[$key] instanceof Interface_node)
						{
						$res[ $key ] = $this->attrib_ns[$key]->getdata();
						}
						else
						{
						$res[ $key ] = $this->attrib_ns[$key]->out();
						}
					}
					else
					{
						
						$res[ $key ] = $value;
					}
					
				}
			
				return $res;
			}
			return null;
			
		}
		
		
						
	}
	
	
	public function &get_ns_attribute_obj($uri)
	{
							//foreach($this->attrib_ns as $att_key => $att_value )
							//{
							//	echo "\n<br> key ::= " . $att_key . '=>' . $att_value->getdata() . ";<p>\n";
							//}
		if(is_object($this->attrib_ns[$uri]))
			
						if($this->attrib_ns[$uri] instanceof Interface_node)
						{
							/*
							foreach($this->attrib_ns as $att_key => $att_value )
							{
								echo $att_key . '=>' . $att_value->getdata() . ";<br>\n";
							}
							echo 'created in 223 ns with ' . $name . '=' . $this->attrib[$name]->getdata() . ';<br>';
							*/
							
						return $this->attrib_ns[$uri];
						}
					
						return false;
	}
	
	
function get_attribute($name = '')
	{
		if($name<>'')
		{
			if(is_object($this->attrib[$name]))
			
						if($this->attrib[$name] instanceof Interface_node)
						{
							
							//echo 'created in 219 with ' . $name . '=' . $this->attrib[$name]->getdata() . ';<br>';
							
						return $this->attrib[$name]->getdata();
						}
						else
						{
							
						return $this->attrib[$name]->out();
						}
			
			else
			{
				//echo $this->attrib[$name] . " " .  'booh' . "\n";
			return $this->attrib[$name];
			}
		}
		else
		{
			
			
			
			if(!is_null($this->attrib))
			if(is_array($this->attrib))
			{
			//echo $this->name . " " . $this->attrib;
				foreach($this->attrib as $key => $value)
				{
				
					if(is_object($this->attrib[$key]))
					{
						if($this->attrib[$key] instanceof Interface_node)
						{
						$res[ $key ] = $this->attrib[$key]->getdata();
						}
						else
						{
						$res[ $key ] = $this->attrib[$key]->out();
						}
					}
					else
					{
						
						$res[ $key ] = $value;
					}
					
				}
			
				return $res;
			}
			return null;
			
		}
	}
protected function set_alter_event($bool)
{
	$this->alter_sensity = $bool;
}

protected function set_read_event($bool)
{
	$this->read_sensity = $bool;
}

public function freedata(){return true;}
//orginal
function setdata(&$data,$pos = null){


                if(is_null($data))
		{
			unset($data);
			$data = "";
		}
                if(is_null($pos))

                        $index = $this->index;
                        
                else
                        $index = $pos;

                //$this->data[$index] = (
                //                        (substr($data,strlen($data)-1)<>' ') &&
                //                        (substr($data,strlen($data)-2)==' ')
                //                        )? $this->data[$index] .= $data : $this->data[$index] .= $data;
                if(is_Object($data))
		{
			unset($this->data[$index]);
			$this->data[$index] = &$data;
		}
		else
		{
			//if(is_Object($this->data[$index]))echo get_class($this->data[$index]) . " is still on this position\n";
			//echo $data . ": $index  <br> in primitiv data area \n";
			$tmp = $data;
			//unset($this->data[$index]);
			$this->data[$index] = $tmp;
		}
			
		//causes alterdataevent
		if($this->alter_sensity)$this->event_alterdata(true);
//echo $index . ":" . $data . "\n";
        }
	
function &getdata($pos = null)
{
   	if($this->read_sensity)$this->event_readdata(true); 
	
              if(is_array($this->data))

                if(is_null($pos))
                {
                         for($i = 0;$i <= $this->index_max();$i++)
                        {
				if(is_Object($this->data[$i]))
				{
				//echo $this->type . '=' . $this->data  . '::' . $pos . ";is_object@ \n";
					return $this->data[$i]->getdata();
				}
				else
				{	//echo $this->type . '=' . $this->data  . '::' . $pos . ";is_Text@ \n";	
					$res1 .= $this->data[$i];
				}
                        }
                        
                        return $res1;
                }
                else
			if(is_Object($this->data[$pos]))
				{
					//echo $this->type . '=' . $this->data[$pos]  . '::' . $pos . ";is_object \n";
					return $this->data[$pos];//->getdata($pos);
				}
				else
				{		
					//echo $this->type . '=' . $this->data[$pos]  . '::' . $pos . ";is_Text \n";
					$res1 = $this->data[$pos];
					return $res1;
				}
                       
                
              else
			if(is_Object($this->data[$pos]))
				{
				//echo $this->type . '=' . $this->data  . '::' . $pos . ";is_object! \n";
					return $this->data;
				}
				else
				{			
				//echo $this->type . '=' . $this->data  . '::' . $pos . ";is_Text! \n";	
					$res1 = $this->data;
					return $res1;
				}
}

public function data_many()
{
return count($this->data);
}

public function giveOutOverview()
{
	echo "Overview of Node <b> $this->name </b><br>
	
	Childelements :<br>
	<ul>";
	if( count($this->next_el) == 0 ) echo '<li>empty</li>';
	for($i = 0;count($this->next_el) > $i;$i++)
	{
		if($this->next_el[$i] instanceof Interface_node)
		{
		echo '<li>' . $this->next_el[$i]->full_URI() . '</li>';
		}
		elseif($this->next_el[$i] instanceof object)
		{
		echo '<li>' . get_Class($this->next_el[$i]) . '</li>';
		}
		else
		{
		echo '<li>' . $this->next_el[$i] . '</li>';
		}
		
	}
	
echo '</ul> Parentelement : ';
if(is_object($this->prev_el)) echo $this->prev_el->full_URI();
else echo 'no parent available';
echo '<br>';

echo ' Attributes : (' . count($this->attrib) . ')<br><ul>';
if(count($this->attrib) > 0)	 
	foreach($this->attrib as $key => $value)
	{
	echo '<li><i>' . $key  . '</i><b></b></li>';
	}
	else
	{
	echo '<li><i>empty</i><b></b></li>';
	}
echo '</ul>';

echo ' Attributes (ns) : (' . count($this->attrib_ns) . ')<br><ul>';
if(count($this->attrib_ns) > 0)	 	 
	foreach($this->attrib_ns as $key => $value)
	{
	echo '<li><i>' . $key  . '</i>-<b>' . $value->full_URI() . '</b></li>';
	}
	else
	{
	echo '<li><i>empty</i><b></b></li>';
	}
echo '</ul>';
	 
echo ' Data : (' . count($this->data) . ')<br><ul>';	 
if(count($this->data) > 0)	
	foreach($this->data as $key => $value)
	{
	echo '<li>(<i>' . $key  . '</i>) <b>' . $value . '</b></li>';
	}
	else
	{
	echo '<li><i>empty</i><b></b></li>';
	}
echo '</ul>';
if($this->cdata)echo 'CDATA aktive!<br>';

echo 'index is ' . $this->index . '<br>';
echo "Type : $this->type <br>";
echo "Namespace : $this->namespace <br>";
echo "Parser : " . get_Class($this->parser). "<br>";
echo "Kind of node : $this->kind_of_node <br>";
echo "Input/Output Ref. (" . count($this->way_in) . ")/(" . count($this->way_out) . ")<br> \n";
if(  $this->is_Class() )
{ echo "<b> Is a class and owns "; 
	if($this->ManyInstance() == 1)
	{echo "1 Instance</b><br>\n"; 
	
	echo "<font color=\"#0000FF\">->\n";
	$tmp = $this->link_to_instance[0];
	$tmp->giveOutOverview();
	echo "</font>\n";
	
	}
	else
	{
	echo $this->ManyInstance() . " Instances</b><br>\n";
	echo "<font color=\"#0000FF\">->\n";
	for($i = 0;$this->ManyInstance() > $i; $i++)
		echo $this->linkToInstance($i)->full_URI() . "<br>\n"; //
	echo "</font>\n";
	}
}	
else echo "<i> Is not a class</i><br>\n";





}

//orginal
public function set_bolcdata($bool){$this->cdata = $bool;}
//orginal
public function get_bolcdata(){return $this->cdata;}
//orginal     
function final_data(){$this->index++;}

//Starts behavior of tagelement, for NS
function complete()
{
	//calls only attributes
	for($i = 0 ; count($this->start_list) > $i;$i++)
	{
		$this->start_list[$i]->event('*?start');
		
	}
	$this->alter_sensity = true;
	$this->event_initiated();
}

//distributor to inherit, fires most of all events in class
function event($type,&$obj)
{
		global $logger_class;
	$logger_class->setAssert('  Message ("' . $type . '") was send to "' . $this->full_URI() . '  ' . '"(Interface_node:event_message_in)' ,5);

	//löst in allen attributen event aus
	for($i = 0 ; count($this->start_list) > $i;$i++)
	{
		$this->start_list[$i]->event($type,$obj);
		
	}
	
	if($type == '*?parse_complete')
	{
	//löst event aus

	
		if($this->link_to_class && !$this->is_Class)
			{
				
				$obj->set_node($this);
				$this->link_to_class->event('*?parse_complete_classes',$obj);
			
			}
	//l�st bei den geklonten objekten ein event aus, wenn das parsen beendet ist
	

	}

	if($this->link_to_class && $type == '*?parse_complete_classes')
		{
			//
			$this->event_Instance($obj->get_node(),$type,$obj);
			$this->link_to_class->event('*?parse_complete_classes',$obj);
			
			//echo $this->full_URI() . "<br>\n";
		}
		
	//if($this->link_to_class)$this->link_to_class->event_Instance($this,$type,$obj);

		$this->event_parseComplete();

}

//way to send messages
function send_messages($type,&$obj)
{	//echo ' - ' . count($this->way_out);
	//echo 'booooooooooooooooooooooooooooooh' . count($this->way_out);
	//�bermittelt den event an alle Knoten, die an diesem Knoten h�ngen
	//send message to all nodes, which has been sign in over set_to_out
	//echo $type . ' ' . get_Class($this) . ' bin drin ' . count($this->way_out) . '<br>';
	for($i = 0;count($this->way_out) > $i;$i++)
	{
		
		//echo get_class($this->way_out[$i]) . ' ist das aktuelle objekt <br>';
		//echo ' send_messages(';
		$this->way_out[$i]->event_message_check($type,$obj);
		
		///echo $this->way_out[$i]->getRefprev()->full_URI() . ' ' ;
		//echo $this->way_out[$i]->get_attribute('value') . "<br>\n";
		//echo ') ';
	}
}

//way to send messages
function send_rev_messages($type,&$obj)
{	//echo ' - ' . count($this->way_out);
	//echo 'booooooooooooooooooooooooooooooh' . count($this->way_out);
	//�bermittelt den event an alle Knoten, die an diesem Knoten h�ngen
	//send message to all nodes, which has been sign in over set_to_out
	
	for($i = 0;count($this->way_in) > $i;$i++)
	{
		
		//echo get_class($this->way_in[$i]);
		$this->way_in[$i]->event_message_check($type,$obj);
		///echo $this->way_out[$i]->getRefprev()->full_URI() . ' ' ;
		//echo $this->way_out[$i]->get_attribute('value') . "<br>\n";
	}
}

//primar call after finishing object, ther wont be an existing childnode
function event_initiated()
{
	
}

//primar call on finshing intigity of current tree
function event_parseComplete()
{
}

function event_Instance(&$instance,$type,&$obj)
{
	//echo count($this->way_out) . ' object_name:' . $this->name . ' event:' . get_Class($obj) . '<br>';
}

function event_attribute($name,&$message)
{
	
}

/**
* @function event_message_check
* @param $type : commandline
* @param $obj : context of Eventobject
*/

protected function event_message_check($type,&$obj)
{
global $logger_class;

	$bool=true;
	for($i = 0;count($this->check_list) > $i;$i++)
	{
		
		//echo get_class($this->way_out[$i]);
		$this->check_list[$i]->event_attribute($type,$obj);
		
	}
	if(!true){
	echo "check of " . $this->full_URI() .  " ( " .  get_Class($this) .  ") gets: type:\"" . $type . '"  Event: Request' . $obj->get_request() ;

	if($obj->get_requester())
		if(is_string($obj->get_requester()))
		echo " Requester:"  . $obj->get_requester() . "(String) ";
		else
		echo " Requester:"  . get_Class($obj->get_requester()) . " ";
	else
		echo " keine Requester ";
	
	if($obj->get_context())
		if(is_string($obj->get_context()))
		echo " Context:"  . $obj->get_context() . "(String) ";
		else
		if($obj instanceof Interface_node)
		echo " Context:"  . $obj->get_context()->full_URI() . " ";
		else
		echo " Context:"  . get_Class($obj->get_context()) . " ";
	else
		echo " keine Context ";
	
	if($obj->get_node())
		echo "Daten:" . $obj->get_node()->full_URI() . "\n";
	else
		echo " keine Daten \n";
	}
	if($obj instanceof EventObject && !$obj->get_locked())
	{
		
		if($this->is_Command($type,'__redirect_node'))
		{
		$logger_class->setAssert('  Redirect was send to "' . $this->full_URI() . '"(Interface_node:event_message_check)' ,5);
			$com_elemnet = $this->parseCommand($type);
			//echo $com_elemnet->get_Command(0,1) . ' ' . get_Class($obj->get_Node()) . ' ' . get_Class($this) . ' <br>';
			$this->send_messages($com_elemnet->get_Command(0,1),$obj) ;
			return true;
			
		}
		
		if($this->is_Command($type,'__add_in_object'))
		{
			if(is_Object($myNode = &$obj->get_node()))
			{
			$logger_class->setAssert('  add_in_object of requester "' . $myNode->full_URI() . '" was send to "' . $this->full_URI() . '  ' . '"(Interface_node:event_message_check)' ,5);
			
				//echo get_Class($myNode);
				$myNode->set_to_out($this);
			}
			
			
			return true;
			
		}
		
		if($this->is_Command($type,'__set_data'))
		{
			
			$com_elemnet = $this->parseCommand($type);
			$this->set_alter_event(false);
			//echo $type . ' (' . $obj->get_context() .  ')  <b>cur.element</b> ' . $this->full_URI() . ' <b>requ.element</b> ' . $obj->get_requester()->full_URI() . '<br>';
//echo $obj->get_context() . ' ' . $obj->get_requester()->full_URI() . ' <br>';
			
			$logger_class->setAssert('  set_data of content "' . $obj->get_context() . '" was send to "' . $this->full_URI() . '  ' . '"(Interface_node:event_message_check)' ,5);
			
			$this->setdata($obj->get_context(),$com_elemnet->get_Command(0,1));
			
			$this->set_alter_event(true);
			
			//$this->event_alterdata(false);
			return true;
			
		}
		
		if($this->is_Command($type,'__get_data'))
		{
			//echo ' in __get_data(';
			

			
			$com_elemnet = $this->parseCommand($type);
			$obj->get_requester()->set_alter_event(false);
						
			

			if(!is_null($tmp = &$this->getdata($com_elemnet->get_Command(0,1))))
			{
				
				$logger_class->setAssert($obj->get_requester()->full_URI() . " gets $tmp to its datapart " ,5);
				if(is_Object($tmp))
				{
					$obj->get_requester()->setdata($tmp,0);
				}
				elseif(strlen($tmp) > 0)
				{
					$booh = $tmp;
					$obj->get_requester()->setdata($booh,0);
				}

			}
		
$logger_class->setAssert('__get_data of requester "' . $obj->get_requester()->full_URI() . '" was send to "' . $this->full_URI() . '" context is  "' . $tmp . '"(Interface_node:event_message_check)' ,5);
			$obj->get_requester()->set_alter_event(true);
				//echo ') ';
			//$this->event_alterdata(false);
			return true;
			
		}
		
		
		//echo 'booh-------------' . get_Class($this) . ' ' . $type . "<br>\n";
		//echo $type;
		if($this->is_Node($type))
		{
		$logger_class->setAssert('  Command was send to "' . $this->full_URI() . '  ' . '"(Interface_node:event_message_check)' ,5);
		
		
		$this->event_message_in($type,$obj);
		}
	}
}

protected function event_message_in($type,&$obj)
{
	
}
//sign in to be a listener
protected function set_to_out(&$obj)
{
	
	$this->way_out[count($this->way_out)] = &$obj;
	$obj->set_to_in($this);

	
		
}

public function &get_out_ref()
{
	return $this->way_out;
}

public function &get_in_ref()
{
	return $this->way_in;
}

//sign in to be a listener
function set_to_in(&$obj)
{
	
	$this->way_in[count($this->way_in)] = &$obj;

		
}


function set_to_check(&$obj)
{
	$this->check_list[count($this->check_list)] = &$obj;
}

//automatic sign in func
function to_listener()
{
	
	if(is_object($this->prev_el))
	{
	$this->prev_el->set_to_out($this);
	
	//echo "habe Knoten " . $this->full_URI() . " " . $this->index_max() . " <br>" ;
	
	}
	else
	{
		echo "bin in Knoten " . $this->full_URI() . " " . $this->index_max() . " <br>" ;
	}
	//$this->back->start_list[count($this->back->start_list)] = &$this;
}


function event_check($type,$bool,&$obj)
{
	
}

protected function event_alterdata($own)
	{
	}

protected function event_readdata($own)
	{
	}

function to_check_list()
{
	$this->prev_el->set_to_out($this);
}

protected function addToTicketEvent($eventOnFullNS , Interface_node &$listener)
{
	$this->get_parser()->ticketEvent($eventOnFullNS , $listener);
}

public function TicketEvent(&$ticketObject)
{
	
}

function &get_Instance()
{
return new Interface_node();
}

function &new_Instance()
{
	//is also class, when used
	

                	//if(!$this->get_parser())echo "kein Parser";
                	//else
                	//	echo "!Parser!";

	
                                $obj = &$this->get_Instance();
				$obj->link_to_class = &$this;
				$this->link_to_instance[] = &$obj; //count($this->link_to_instance)
				$this->is_Class = true;
				//echo "\n <br/>---" . $this->position_stamp() . "---<br/> \n";
				
				//$obj->set_parser($this->get_parser());
				return $obj;
}

function &cloning(&$prev_obj)
                {
                                $obj = $this->get_Instance();

                                if($prev_obj) $obj->set_idx($prev_obj->get_idx());
                                $obj->name =  $this->name;
                                $obj->attrib = $this->attrib;
                                $obj->data =  $this->data;
                                $obj->namespace =  $this->namespace;
                                $obj->type =  $this->type;

                                // if(!$this->get_parser())throw new ErrorException( $this->full_URI() . " needs a backref to its parser", 1332, 75);
                                
                                //$this->giveOutOverview();
                                //if($this->get_parser())$this->giveOutOverview();
                                
                                //$obj->idx = $this->idx;
                                //$obj->res_idx =  $this->res_idx;
                                //if(!is_null($this->ref))$obj->ref =  &$this->ref;

                                //echo '<span style="color:blue;" >' . $obj->get_type() . ' - ' . $obj->get_name() . ' mit dem index ' . $obj->res_idx . '</span><br>';
                                                           //instances_down[$z]

                                //$obj->instances_down = &$this->instances_down;
                                
                                /*
                                if(is_object($this->one_to_many))
                                $obj->one_to_many = &$this->one_to_many;
                                else
                                $obj->one_to_many = $this->one_to_many;
                                */
                                if(!is_null($this->next_el))
                                	for($i = 0;$i < count($this->next_el);$i++)
                                	{
					
                                		 if(!$this->next_el[$i]->get_parser())
                                		 {
                                		 	$this->next_el[$i]->set_parser($this->get_parser());
                                		 	
                                		 	 trigger_error($this->next_el[$i]->full_URI() . " in interface_ns needs a reference to its parser. please check the specific code", E_USER_WARNING);
                                		 }
                                		 
                                		$my_temp  =  &$this->next_el[$i]->cloning($obj);
                                		
                                //$obj->next_el[$i]
                                //echo 'in clone ' .  $my_temp->get_type();
                                	$obj->next_el[$i] = &$my_temp;
                                //echo 'in clone ' .  $obj->next_el[$i]->get_type();
                                	}

                                if(is_object($prev_obj))
                                {
                                //echo $prev_obj->name . '--';

                                $obj->prev_el = &$prev_obj;

                                // if(!is_null($prev_obj->next_el))
                                //var_dump(count($prev_obj->next_el)); //count($prev_obj->next_el)
                                if(!is_array($prev_obj->next_el))$prev_obj->next_el = array();
                                $prev_obj->next_el[] = &$obj;



                                //echo $obj->prev_el->name;
                                }

                                if($this->get_parser())
                                {$this->get_parser()->set_new_index($obj);
                                //$this->giveOutOverview();
                                }
                                else
                                {
                                	
                                	if($prev_obj &&  $prev_obj->get_parser())
                                		$prev_obj->get_parser()->set_new_index($obj);
                                	else
                                	//throw new ErrorException( $this->full_URI() . " needs a backref to its parser", 1332, 75);
                                	;
                                	//echo "danger, no index";
                                //	echo "not in ------------------------------------------------------------------------------------\n";
                               
                               
                                //echo "-------------------------------------------------------------------------------------------\n";
                                }
                                return $obj;
                }
		

//public function __toString(){return 'interface_node';}
}


class Interface_attrib
{
var $value;
var $name;
var $back;

	//referenz zum knoten
	function object_back($obj){$this->back = $obj;}
	//wertausgabe
	function out(){return $this->value;}
	//eingang
	function in($in){$this->value = $in; }
	//liste , die von dem Knoten abgefragt wird
	function to_listener()
	{
		$this->back->start_list[count($this->back->start_list)] = &$this;
	}
	
	//loest bei einem Event eine Aktion aus
	function to_check_list()
	{
		$this->back->check_list[count($this->back->check_list)] = &$this;
	}
	
	//wird bei der initialisierung aufgerufen
	function start()
	{
		
	}
	
	function check($type,$bool,&$obj)
	{
		return $bool;
	}
	
	//wird bei u.a. beim Ende des Parsens aufgerufen
	function event($event = '')
	{
		
	}
	
	function &get_Instance()
	{
		return new Interface_attrib();
	}
}

class Command_Object
{
	private $node_URI;
	private $commands = array();

	function __construct($command) 
	{

			if(!(false === ($tmp = strpos($command,'?'))))
			{
				$this->node_URI = substr($command,0,$tmp);
				$commandstr = substr($command,$tmp + 1);
				$this->commands = explode('&',$commandstr);
				
				for($i = 0;$i<count($this->commands);$i++)
				{
					$this->commands[$i] = explode('=',$this->commands[$i]);
					if($this->commands[$i][0] == '__redirect_node')
					{
						$this->commands[$i][1] = base64_decode(rawurldecode($this->commands[$i][1]));
					}
				}

			}

	}
	
	public function get_URI(){return $this->node_URI;}
	public function get_Command($num,$index){return $this->commands[$num][$index];}

}
?>
