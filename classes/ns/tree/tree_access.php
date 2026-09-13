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
		|| $uri == 'http://www.trscript.de/tree#once'
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
*	⚠ Die Stufe DARF hier heben (Punkt 2 darunter) — ein Zugang ist eine Erklaerung,
*	kein Zweig. Ein <tree securitylevel> klammert weiter nach unten.
*
*	⚠ Das Verlassen steht in finally: eine Ausnahme aus der Kette darf den Stapel
*	nicht stehen lassen, sonst gilt die fremde Stufe weiter.
*
*	ENTSCHIEDEN UND GEBAUT (STW, 2026-09-13). Der Block darunter hielt die offenen Fragen
*	fest; sie sind beantwortet:
*
*	1. DIE WOERTER stehen: securitylevel und sector, dieselben wie an den Knoten, dazu
*	   hinzu="ja" fuer die Richtung des Sektors. Ein Wort je Begriff.
*	2. DIE STUFE DARF HEBEN. STW: „Sie darf heben und muss sogar. Sonst sind wir schnell
*	   fertig." Das ist setuid; pushClearance nimmt dafuer ein zweites Argument, der
*	   Vorgabefall (klammern nach unten) bleibt fuer TREE_tree unveraendert.
*	3. DER SEKTOR HAT JETZT EINE KLAMMER (pushSectors/popSectors) — mit der umgekehrten
*	   Richtung: einengen darf jeder, hinzunehmen nur ab Stufe 10.
*	4. BELEGT: test/Integration/tree_access.php, 8/0. Gemessen wird ueber die Abweisung
*	   von __save_back, die die geltende Stufe nennt — so laesst sich die Klammer von
*	   innen ablesen, ohne dass je etwas geschrieben wird.
*
*	OFFEN GEBLIEBEN ist nur der Punkt, der den lebenden Waechter betrifft:
*	⚠ mayEnter() liest clearance() NICHT, sondern $_SESSION[…#securityclass] direkt. Der
*	Stapel regelt also Befehle, nicht den Zutritt zu Knoten. Ein hebendes <access> wirkt
*	damit auf Befehle, nicht auf die Navigation. Das haengt mit der alten Frage
*	„Grundlinie anonym = 0 statt -1" zusammen und ist STW einzeln vorzulegen.
*
*	--- der urspruengliche Block, zur Herkunft ---
*
*	⚠⚠ MARKIERT, ZUSAMMEN ANZUSEHEN (STW, 2026-09-11) — hier ist noch nichts entschieden.
*
*	Der Zugang ist kaum in Verwendung (im ganzen Bestand steht KEIN einziges
*	<param name="security">), spielt aber fuer mehrere Vorhaben eine tragende Rolle.
*	STW: „So kann eine KI innerhalb des Baums aktiv werden, vorgespeicherte Aktionen
*	aktiviert oder bridges fuer Sektoren gebaut werden."
*
*	ENTSCHIEDEN ist nur die Form der Klammer. STW: „Durch die Linearitaet von PHP kann
*	Clearance in access starten und enden." Genau so steht es jetzt: pushClearance vor
*	der Kette, popClearance im finally — dieselbe Klammer wie in TREE_tree.
*	(Bis 2026-09-11 fehlte das Schieben; der finally-Zweig nahm eine Ebene vom Stapel,
*	die niemand daraufgelegt hatte, naemlich die des umgebenden <tree>. Ein <access>
*	mit security haette damit nicht gesenkt, sondern die Klammer des Elternzweiges
*	aufgerissen.)
*
*	OFFEN — nicht im Vorbeigehen festlegen:
*
*	1. DIE WOERTER. „security" fuer die Stufe ist gesetzt, nicht gewaehlt — es stand hier,
*	   als der Wachter gebaut wurde. Fuer den Sektor gibt es noch gar keines. Beide
*	   Namen bestimmen wir zusammen; sie sind danach Bestandteil des Dokumentformats
*	   und nicht mehr billig zu aendern.
*
*	2. DER SEKTOR HAT KEINE KLAMMER. Fuer die Stufe gibt es clearance_base plus
*	   clearance_stack; fuer den Sektor gibt es nur sector_override — EINEN Wert, den
*	   setSectors() setzt und sectors() liest, ohne Stapel. Eine „Bruecke fuer Sektoren"
*	   braucht dieselbe Linearitaet wie die Stufe, also erst ein pushSectors/popSectors.
*	   ⚠ Und die Richtung ist zu klaeren: bei der Stufe klammert min() auf das, was
*	   gerade gilt (nur senken). Eine Bruecke will das Gegenteil — einen Sektor
*	   HINZUnehmen. Das ist keine Kopie der Stufenlogik, sondern eine eigene Regel.
*
*	3. WER LIEST DAS ÜBERHAUPT? clearance() hat heute genau EINEN Leser:
*	   Interface_ns.php:1506, das Stufentor der Registry-Befehle (addSecurity).
*	   ⚠ mayEnter() gehoert NICHT dazu — es vergleicht gegen
*	   $_SESSION[…#securityclass]. Der Stapel regelt also Befehle, nicht den Zutritt
*	   zu Knoten. Ob das so bleiben soll, ist Teil derselben Frage.
*
*	4. UNBELEGT. Der Fix ist nicht gegengetestet — es gibt nichts im Bestand, was
*	   hier durchlaeuft, also kann auch nichts rot werden. Ein Pruefstand kommt,
*	   wenn die Woerter stehen.
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
		$stufe = $this->param_named('securitylevel');

		/* ⚠ Dieselbe Bedingung wie in TREE_tree: "keine Angabe" heisst KEINE AUSSAGE,
		*  nicht "Stufe 0" — sonst zoege die Klammer in pushClearance einen Zehner beim
		*  Betreten eines unmarkierten Zugangs auf 0 herunter, und er duerfte dahinter
		*  weniger als davor. -1 ist ebenfalls draussen: die Marke "nur fuer
		*  Nichtangemeldete" ist keine Faehigkeitsstufe. */
		$eigen = is_object($cg)
		      && !is_null($stufe)
		      && '' !== $stufe
		      && intval($stufe) >= 0;

		/* ⚠ Das Schieben fehlte: der finally-Zweig nahm eine Ebene vom Stapel, die
		*  nie jemand daraufgelegt hatte — also die des umgebenden <tree>. Ein <access>
		*  mit security haette damit nicht gesenkt, sondern die Klammer des Elternzweiges
		*  aufgerissen. */
		/* SETZEN, nicht klammern: ein <access> darf die Stufe auch HEBEN (zweites
		*  Argument). Ein <tree securitylevel> klammert weiter nach unten - ein Zweig ist
		*  Navigation, ein Zugang ist eine Erklaerung. */
		if($eigen) $cg->pushClearance(intval($stufe), true);

		/* Der Sektor, die zweite Klammer. Erst NACH der Stufe, damit ein <access>, das
		*  sich auf 10 hebt, danach auch hinzunehmen darf - dieselbe Reihenfolge wie bei
		*  setuid: erst werden, dann tun.
		*
		*  hinzu="ja" nimmt den Sektor auf (ab Stufe 10), sonst wird geschnitten. Das
		*  Wort steht am <param>, weil tree:param kein eigenes Verhalten hat und der
		*  Verbraucher es liest - wie securitylevel eine Zeile darueber. */
		$sektor = $this->param_named('sector');
		$eigener_sektor = is_object($cg) && !is_null($sektor) && '' !== $sektor;

		if($eigener_sektor)
			$cg->pushSectors($sektor, 'ja' === strtolower((string) $this->param_named('hinzu')));

		/* Die dritte Klammer: WER DIE ANTWORT BEKOMMT.
		*
		*  STW (2026-09-14): "Bei access bin ich mir sicher, dass der owner wechselt, da
		*  access ueber ihn den Rueckgabewert bekommt."
		*
		*  Damit hat ein Zugang nicht nur Bedingungen, sondern auch ein Ergebnis: ein
		*  __to_owner in seiner Kette schreibt in DIESEN Knoten (Knotenzweig von
		*  __to_owner: $eigner->setdata(...)), statt nach aussen zu gehen.
		*
		*  ⚠ Erster Gebrauch von set_owner ueberhaupt - der Slot existierte, __to_owner
		*  las ihn, aber gesetzt hat ihn nie jemand (die einzige Zuweisung stand
		*  auskommentiert in class_Contentgenerator.php:798). Es kann daran also nichts
		*  kaputtgehen; es faengt hier an.
		*
		*  ⚠ Und zurueckgegeben wie die anderen zwei: nach dem Zugang gilt wieder der
		*  alte Eigner, sonst schriebe eine spaetere Kette weiter in diesen Knoten. */
		$eigner_vorher = ($obj instanceof EventObject) ? $obj->get_owner() : null;
		$eigener_eigner = ($obj instanceof EventObject);

		if($eigener_eigner)
		{
			$ich = $this;
			$obj->set_owner($ich);
		}

		try
		{
			$this->hold_messages($show,$obj);
		}
		finally
		{
			if($eigener_eigner) $obj->set_owner($eigner_vorher);
			if($eigener_sektor) $cg->popSectors();
			if($eigen)          $cg->popClearance();
		}
	}



}


?>
