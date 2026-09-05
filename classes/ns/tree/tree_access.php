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

class TREE_access extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';

var $succesful = true;

function &get_Instance()
{
return new TREE_access();
}


//primar call after finishing object, ther wont be an existing childnode
function event_initiated()
{
	$uri = $this->getRefprev()->full_URI();
	if($uri == 'http://www.trscript.de/tree#tree' 
		|| $uri == 'http://www.trscript.de/tree#final'
		|| $uri == 'http://www.trscript.de/tree#first'
		|| $uri == 'http://www.trscript.de/tree#program')
	{
		$this->to_listener();

	}
}

/**
*	Der Zugang: was hier steht, wird als Befehlskette gefeuert.
*
*	Hier wohnt die Sicherheitsstufe des Aufrufs. Ein Nutzer ruft einen Agenten, der
*	einen Agenten ruft — jede Ebene betritt den Stapel des ContentGenerators und
*	verlaesst ihn danach wieder:
*
*	    <access>
*	        <param name="security" >4</param>
*	        … Befehlskette …
*	    </access>
*
*	Das Recht steht IM Zugang, nicht an ihm: ein <param> ist Inhalt des Dokuments
*	und liegt auf derselben Ebene wie das, was es erlaubt. Die uebrigen Slots des
*	Zugangs sind ebenso benannt (commandline, request).
*
*	⚠ Die Stufe kann nur SENKEN, nie heben — pushClearance klammert auf das, was
*	gerade gilt. Ein Dokument, das sich selbst hochstufen koennte, haette die ganze
*	Einstufung ausgehebelt. Gehoben wird nur beim Eintritt, aus dem API-Schluessel.
*
*	⚠ Das Verlassen steht in finally: eine Ausnahme aus der Kette darf den Stapel
*	nicht stehen lassen, sonst gilt die fremde Stufe weiter.
*/
	/**
	*	Der Wert eines benannten <param>-Kindes, oder null.
	*
	*	Nur DIREKTE Kinder, und nur tree:param — damit ein "security", das tiefer in
	*	der Befehlskette steht, nichts umstellt.
	*/
	private function param_named($name)
	{
		for($i = 0; $i < $this->index_max(); $i++)
		{
			$kind = $this->getRefnext($i);

			if(!is_object($kind) || $kind->full_URI() != 'http://www.trscript.de/tree#param')
				continue;

			if($kind->get_ns_attribute('http://www.trscript.de/tree#name') === $name)
				return trim((string) $kind->getdata());
		}

		return null;
	}

function event_message_in($type,&$obj)
	{
		$show = $this->getdata();

		$cg    = $this->get_parser()->get_context_generator();
		$stufe = $this->param_named('security');
		$eigen = is_object($cg) && !is_null($stufe);

		try
		{
			$this->hold_messages($show,$obj);
		}
		finally
		{
			if($eigen) $cg->popClearance();
		}
	}



}


?>
