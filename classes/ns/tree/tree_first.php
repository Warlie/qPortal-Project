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
/**
*	first — der Ausfuehrungsbereich der VORAUSSETZUNGEN, und er laeuft je Baum nur EINMAL.
*
*	Ein first haelt, was ein Dokument braucht, bevor irgendetwas darin arbeitet: angelegte
*	Tabellen, geladene Vorlagen, gesetzte Werte. behavior/tree.php schickt bei JEDEM start
*	zuerst an #first, und seit 2026-09-11 fuehrt __call es ebenfalls mit aus — ohne Bremse
*	liefe es also mehrfach je Request.
*
*	Die Bremse ist das ABMELDEN, nicht eine Sperre im Inneren (STW): „das kommt mir
*	sympathischer vor, als eine Methode zu sperren." In einem Nachrichtensystem ist das
*	Loesen der Kante die strukturelle Form von „ich bin fertig" — danach gibt es den
*	Zuhoerer nicht mehr, statt dass er dasteht und nichts tut.
*
*	⚠ Abgemeldet wird NUR nach einem echten start. TREE_tree::event_message_in hat drei
*	Ausgaenge, die den Zweig gar nicht laufen lassen — kein start (nur geloggt), mayEnter
*	verweigert, Name passt nicht. Wer bedingungslos abmeldet, traegt first wegen einer
*	Nachricht aus, die nur vorbeikam, und der spaetere echte start findet es nicht mehr.
*	Die Rueckgabe des Elternteils taugt dafuer nicht: true heisst dort BEIDES.
*
*	⚠ Mit der URI abmelden, nicht ohne. to_listener($uri) in TREE_tree::event_initiated
*	steigt die prev_el-Kette hoch bis zum indextree; das Gegenstueck muss dieselbe Stelle
*	treffen. Ohne Argument traefe es prev_el — hier zufaellig derselbe Knoten, an jedem
*	anderen Traeger der falsche, und zwar still.
*/
class TREE_first extends TREE_tree
{

function &get_Instance()
{
//return new TREE_first();
return new TREE_first();
}




function event_message_in($type,&$obj)
	{
		$com = ($type instanceof Command_Object) ? $type : $this->parseCommand($type);

		$ergebnis = parent::event_message_in($type, $obj);

		/* Erst jetzt — ein start, der an mayEnter scheitert, wirft in run_branch und
		*  kommt hier nie an; das ist richtig so, dann bleibt die Kante stehen. */
		if($com->matchesCommand('start'))
			$this->remove_listener('http://www.trscript.de/tree#indextree');

		return $ergebnis;
	}

	
}

?>
