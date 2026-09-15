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
private $contentGenerator = null;

function event_initiated()
{
	$this->contentGenerator = $this->get_parser()->get_context_generator();
	$this->to_listener('http://www.trscript.de/tree#indextree');
}

function &get_Instance()
{
return new TREE_tree();
}


function &new_Instance()
{
                                
				$obj = $this->get_Instance();

				$obj->link_to_class = &$this;

				/* Der Prototyp kennt seine Instanzen - wie in Interface_node::new_Instance().
				*  Die Prototypen sind global (namespace_frameworks), also ist das die Antwort
				*  auf "welche Knoten sind tree:tree?" ueber ALLE geladenen Baeume, die
				*  anonymen eingeschlossen. Ausgetragen wird in removeNode().
				*  ⚠ is_Class wird hier bewusst NICHT gesetzt: ein tree, der per rdf:about
				*  oder tree:name praegt, ist dabei selbst Vorlage - mit is_Class reichte er
				*  *?parse_complete_classes nicht mehr weiter (Interface_ns::event, !is_Class). */
				$this->link_to_instance[] = &$obj;

				return $obj;
}


	
/**
*	Der Lauf in diesen Zweig — mit der Stufe, die der Knoten verlangt.
*
*	Der Waechter hat oben schon geprueft, ob der Aufrufer hier hinein darf. Traegt der
*	Knoten eine securitylevel, ist das zugleich die Stufe, auf der ALLES arbeitet, was
*	hinter ihm laeuft (STW): "das war ohnehin erlaubt, nur das Veraendern war verboten,
*	wenn das Level nicht stimmt".
*
*	⚠ Nur schieben, wenn der Knoten wirklich eine Stufe traegt. "Keine Angabe" heisst
*	KEINE AUSSAGE, nicht "Stufe 0" — sonst zieht die Klammer in pushClearance einen
*	Zehner beim Betreten eines unmarkierten Zweiges auf 0 herunter, und er duerfte
*	dahinter weniger als davor. -1 ist ebenfalls draussen: das ist die Marke "nur fuer
*	Nichtangemeldete", keine Faehigkeitsstufe.
*
*	⚠ Das Herausnehmen steht in finally — der Zweig hat mehrere Ausgaenge, und eine
*	Ausnahme darf die fremde Stufe nicht stehen lassen. Dass ein einzelner Stapel
*	genuegt, haengt daran, dass sich kein zweiter Prozess dazwischenschiebt (STW).
*/
function event_message_in($type,&$obj)
	{
		/* Nur start ist ein Lauf. Alles andere ist irgendetwas anderes - und bleibt
		*  hier stehen: kein Pfad verbraucht, kein Name verglichen, kein src geladen,
		*  nichts gestartet, und NICHT an way_out weitergegeben. Die tree-Aufrufe sind
		*  getrennt zu halten, es darf keine Kaskade losgehen (STW). Gemessen: in
		*  way_out eines tree stehen gar keine tree-Kinder (die haengen per
		*  event_initiated am indextree), sondern die uebrigen Knoten - und die
		*  unterstellen alle einen start. Wer den Baum durchlaufen will, tut das als
		*  Befehl ueber die Struktur (getRefnext()), nicht ueber die Zuhoerer.
		*  Der leere Befehl ist bereits in Command_Object zu start geworden.
		*
		*  ⚠ Die Zeile im Log gibt es nur, wo der Aufrufer hinein darf. */
		$com = ($type instanceof Command_Object) ? $type : $this->parseCommand($type);

		if(!$com->matchesCommand('start'))
		{
			global $logger_class;

			/* get_contentGen, nicht get_parser()->get_context_generator(): ein Knoten,
			*  der zur Laufzeit per __add_node entsteht, hat keinen Parser, wohl aber
			*  den ContentGenerator (gemessen). */
			$cg = $this->get_contentGen();

			if(is_object($cg) && !$cg->mayEnter($this))
				return false;

			$logger_class->setAssert('tree "' . $this->get_ns_attribute('http://www.trscript.de/tree#name')
				. '": ' . var_export($com->get_Command(), true) . ' ist kein start - nicht gestartet, nicht weitergereicht', 5);

			return true;
		}

		$type = $com;

//echo "Das Ding hier " . " [" . $this->full_URI() . "]" . spl_object_id($this) . " wurde aufgerufen von \n"; // . spl_object_id($obj->get_node()) .  "\n";
//	echo spl_object_id($this) . "--->\n";

//if(spl_object_id($obj->get_node())==354 && spl_object_id($this) == 354)throw new ErrorException("jap");

	    	 $structur = $type->get_Result_Array();
    		 $listTreeNames = $structur["Attribute"];
$showName = "";

    		 if(is_null($listTreeNames))$listTreeNames = []; //debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    		 if(is_string($listTreeNames)) // WTF
    		 {	//debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    		 	 $listTreeNames = []; 
    		 }

    		 if(!empty($listTreeNames) &&
    		 	 $listTreeNames[0] != $showName = $this->get_ns_attribute('http://www.trscript.de/tree#name')
    		 )return false;

    		 
//echo "Das Ding hier " . " [" . $this->full_URI() . "]" . spl_object_id($this) . " hat den Namen $showName " . count($listTreeNames) . " \n"; // . spl_object_id($obj->get_node()) .  "\n";
array_shift($listTreeNames);
/*
if($this->full_URI() == "http://www.trscript.de/tree#final")
	debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
*/
$obj->set_node($this);
		global $_SESSION;
		$json = '{"name":"http://www.trscript.de/tree#final"}';

				/* Zutritt: EINE Pruefung fuer Menue und Waechter — ContentGenerator::mayEnter.
		*  Vorher stand die Logik hier als Kopie und war in der Klammerung kaputt
		*  (eine hohe Stufe hob den Sektor aus). Ohne ContentGenerator — etwa in
		*  einem Pruefstand, der nur parst — bleibt es wie bisher offen. */
		$cg = $this->get_parser()->get_context_generator();

		if(is_object($cg) && !$cg->mayEnter($this))
			throw new NoPermissionException('not Allowed');

		/* Ab hier laeuft der Zweig — und zwar auf der Stufe, die DIESER Knoten
		*  verlangt (STW): "das war ohnehin erlaubt, nur das Veraendern war verboten,
		*  wenn das Level nicht stimmt". Der Waechter eine Zeile darueber hat gerade
		*  geprueft, dass der Aufrufer sie mitbringt.
		*
		*  ⚠ Nur schieben, wenn der Knoten wirklich eine Stufe traegt. "Keine Angabe"
		*  heisst KEINE AUSSAGE, nicht "Stufe 0" — sonst zieht die Klammer in
		*  pushClearance einen Zehner beim Betreten eines unmarkierten Zweiges auf 0
		*  herunter, und er duerfte dahinter weniger als davor. -1 ist ebenfalls
		*  draussen: die Marke "nur fuer Nichtangemeldete" ist keine Faehigkeitsstufe.
		*
		*  ⚠ Und erst HIER, nicht vor dem Namensvergleich: sonst schoebe jeder
		*  Geschwisterknoten, den die Nachricht nur streift, seine Stufe auf den
		*  Stapel. Dass ein einzelner Stapel genuegt, haengt daran, dass sich kein
		*  zweiter Prozess dazwischenschiebt (STW). */
		$stufe = $this->get_ns_attribute('http://www.trscript.de/tree#securitylevel');

		$eigene_stufe = is_object($cg)
		             && false !== $stufe
		             && '' !== trim((string) $stufe)
		             && intval($stufe) >= 0;

		if(!$eigene_stufe)
			return $this->run_branch($type, $obj, $listTreeNames);

		$cg->pushClearance(intval($stufe));

		try
		{
			return $this->run_branch($type, $obj, $listTreeNames);
		}
		finally
		{
			$cg->popClearance();
		}
	}

	/** Der eigentliche Lauf, aufgeteilt nur, damit die Stufe eine Klammer bekommt. */
	private function run_branch($type,&$obj,$listTreeNames)
	{

		
		// consider the aspect for the next branch (tree)
		/*
		if($aspect = $this->get_ns_attribute('http://www.trscript.de/tree#consider_aspect') )
		{
			if(!empty($listTreeNames))
			{
				$json =  '{ "attribute":{"http://www.trscript.de/tree#name":"';
				$json .= $listTreeNames[0];
				$json .= '"}}';
			}
			$this->get_parser()->get_context_generator()->setLexicalOrderParam($aspect);
		}
	
		
	$find = ["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>$json], "Value"=>$type ]] ;
		*/
	//var_dump($find);
	
//var_dump($type, $this);
	if($tmp = $this->get_ns_attribute('http://www.trscript.de/tree#src'))
	{
		
		$tmp = resolve_path($tmp);

		/* Ein src ist entweder eine Datei dieser Installation oder die Adresse einer
		*  ENTFERNTEN qPortal-Instanz. resolve_path laesst eine URL unangetastet, es
		*  ersetzt nur %ROOT_DIR% und Geschwister — der Schalter ist darum das Schema.
		*  Ohne diesen Zweig fiel eine Adresse still durch das is_file() und der Knoten
		*  gab false zurueck, ohne dass irgendwo etwas stand. */
		$remote = !is_file($tmp) && preg_match('#^https?://#i', $tmp);

		if(is_file($tmp) || $remote)
		{
			if($remote)
			{
				$this->get_parser()->load($tmp, 0, $this->remote_doctype(), $this->request_parameter());

				/* ⚠ Der URL-Zweig von xml::load() kehrt direkt nach load_Stream zurueck
				*  (xml_multitree.php:867) und laesst den Zeiger stehen, wo er war — der
				*  Datei-Zweig laeuft weiter und setzt ihn. Ohne das hier ist
				*  show_xmlelement() null und die naechste Zeile stirbt an
				*  "hold_messages() on null". TREE_add ruft an derselben Stelle
				*  dasselbe (tree_add.php, nach dem load). */
				$this->get_parser()->set_first_node();
			}
			else
				$this->get_parser()->load($tmp,0);
			
		//$this->get_parser()->ALL_URI();

			//var_dump($this->get_parser()->show_xmlelement());
			//echo  "deeper " . spl_object_id($this) . " insert " . spl_object_id($this->get_parser()->show_xmlelement()) . " [" . $this->get_parser()->show_xmlelement()->full_URI() . "]--------\n";
			//var_dump($find);
			$this->get_parser()->show_xmlelement()->hold_messages(
				["Identifire"=>"http://www.trscript.de/tree#indextree", "Command"=> ["Name"=> "start" ], "Attribute"=>$listTreeNames] 
				,$obj); //event_message_check($find ,$obj);
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
		$this->send_messages(
			["Identifire"=>"*", "Command"=> ["Name"=> "start" ], "Attribute"=>""]
			,$obj);
		
		
		$obj->myrequester->found_relevant_page(); // found relevant page
		
		return true;
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
	//echo spl_object_id($this) . "<---\n";
	}

	/**
	*	Der Kopf einer Anfrage an eine entfernte Instanz.
	*
	*	Gebaut wie in TREE_add::event_message_in — dieselben Schluessel, weil dahinter
	*	dieselbe Tuer liegt: xml::load($ref,$case,$typ,$com_parameter).
	*
	*	⚠ <header> traegt keine eigene Bedeutung, es MARKIERT nur, was darin steht (STW).
	*	Das ist noetig, weil <param> an vielen Stellen etwas anderes heisst — als Angabe
	*	an einen Unteraufruf, als Spaltenname, als Feld einer Anfrage. Erst die Huelle
	*	sagt, dass diese Paare Kopfzeilen sind. Deshalb ist TREE_header eine leere Klasse
	*	und die Auswertung steht hier.
	*
	*	<param> DIREKT am tree-Knoten sind dagegen Anfrageparameter, nicht Kopfzeilen —
	*	genau die Trennung, die <add> auch macht.
	*
	*	Die Rechte kommen spaeter und haengen am API-Schluessel; er ist eine Kopfzeile
	*	wie jede andere und braucht hier nichts Eigenes.
	*/
	private function request_parameter()
	{
		$com_parameter = ["Method" => REST_Connection::GET];

		if($method = $this->get_ns_attribute('http://www.trscript.de/tree#method'))
			$com_parameter["Method"] = $method;

		$head = $this->findListByName('http://www.trscript.de/tree#header', $this);

		if(count($head) > 0)
		{
			$com_parameter["RequestHeaders"] = [];
			$kopfzeilen = $this->findListByName('http://www.trscript.de/tree#param', $head[0]);

			for($i = 0; $i < count($kopfzeilen); $i++)
				$com_parameter["RequestHeaders"][] = $kopfzeilen[$i]->get_attribute('name') . ": "
				                                   . trim($kopfzeilen[$i]->getdata());
		}

		$felder = $this->findListByName('http://www.trscript.de/tree#param', $this);

		if(count($felder) > 0)
		{
			$com_parameter["Parameters"] = [];

			for($i = 0; $i < count($felder); $i++)
				$com_parameter["Parameters"][$felder[$i]->get_attribute('name')] = trim($felder[$i]->getdata());
		}

		return $com_parameter;
	}

	/** Was die Gegenstelle liefert. Eine qPortal-Instanz antwortet XML; das bleibt die Vorgabe. */
	private function remote_doctype()
	{
		if($typ = $this->get_ns_attribute('http://www.trscript.de/tree#doctype'))
			return $typ;

		return 'XML';
	}

	/* Kinder eines Knotens nach ihrer vollen URI. Gleichlautend in TREE_add — dort
	*  privat und in Gebrauch; nicht zusammengelegt, weil das den <add>-Weg anfassen
	*  wuerde, den niemand angefragt hat. */
	private function findListByName($name, $node)
	{
		$res = [];

		for($i = 0; $i < $node->index_max(); $i++)
			if($node->getRefnext($i)->full_URI() == $name)
				$res[] = $node->getRefnext($i);

		return $res;
	}

}

?>
