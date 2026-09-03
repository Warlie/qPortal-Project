<?PHP
/**
*	tree:name — der Name eines Astes, und unter einer Bedingung auch ein Bezeichner.
*
*	tree:name ist eine subPropertyOf von rdf:ID (so gesagt im Vokabular des
*	Registrierungsbogens). Es tut damit dasselbe wie rdf:ID — einen Knoten unter
*	einem Namen im Namensraum eintragen —, aber nur da, wo das eine Aussage ist.
*	Zwei Bedingungen, beide notwendig:
*
*	  1. DER TRAEGER. Ein Name heisst je Traegerelement etwas anderes. Auf tree und
*	     final ist er ein Bezeichner; auf content, subtree und param ist er eine
*	     ADRESSE (die #-Form, ein Workaround, um Inhalte in Dokumenten ueberhaupt
*	     modifizieren zu koennen), auf wordfield eine BESCHRIFTUNG, auf remote der
*	     Name der Gegenstelle. Hier wird darum nur gelesen, was auf tree oder final
*	     sitzt. Ueberall sonst tut dieser Knoten nichts — tree:tree macht weiter
*	     seine Sachen, dieser hier macht seine.
*	     first ist bewusst NICHT dabei: es wird immer und ohne Namensliste gerufen
*	     (behavior/tree.php:31), sein Name wuerde nie verglichen.
*
*	  2. DER WERT MUSS EINEN NAMENSRAUM NENNEN. Ein blanker Name ("login") loest
*	     gegen den Default-Namensraum des Baums auf — und den teilen sich 571
*	     Dokumente. Ein Bezeichner darin waere wertlos: 520-mal "home" ist keine
*	     Identitaet, sondern 520 Flureingaenge. STW dazu: ein blanker Name heisst
*	     "ich will einen Flur mit Zimmern, aber nur zum Durchlaufen".
*	     Es zaehlen deshalb nur die beiden Formen, die selbst sagen, wohin sie
*	     gehoeren:
*
*	         name="fridge;strom"                        ueber den Praefix
*	         name="https://.../fridge#strom"            voller URI
*
*	     Gemessen am Bestand: von 716 Namen auf tree/final traegt heute KEIN
*	     einziger einen Namensraum (676 blank, 40 mit fuehrendem Punkt). Der
*	     Bezeichner erscheint also erst dort, wo jemand ihn hinschreibt.
*
*	⚠ Der fuehrende Punkt ("unsichtbar", class_Contentgenerator.php:263) bleibt
*	unberuehrt. Ein blanker Name wird gar nicht erst als Bezeichner gelesen, also
*	muss er auch kein gueltiger NCName sein.
*
*	Die Navigation aendert sich nicht: TREE_tree::event_message_in vergleicht
*	weiter den rohen Attributwert gegen den Kopf der Namensliste. Der Bezeichner
*	kommt daneben, nicht an seine Stelle.
*/

class TREE_name extends Interface_node
{

function &get_Instance()
{
	return new TREE_name();
}

	function event_initiated()
	{
		if(!($carrier = $this->getRefprev()))return;

		$on = $carrier->full_URI();

		if($on != 'http://www.trscript.de/tree#tree'
		&& $on != 'http://www.trscript.de/tree#final')return;

		$data = $this->getdata();

		/* Der Wert muss selbst sagen, in welchen Namensraum er gehoert. Die beiden
		*  Formen sind dieselben wie bei rdf:ID (rdf_ID.php:57ff), nur ohne dessen
		*  dritten Zweig: der faellt dort auf den Default-Namensraum zurueck, und
		*  genau der ist hier die Form, die nichts aussagt. */
		if(false === ($posinstr = strpos($data,'#')))
		{
			if(false === ($posinstr = strpos($data,';')))return;   // blank — nur ein Flur

			$namespace = $this->get_parser()->get_NS_of_Tree(substr($data,0,$posinstr),$this->get_idx());
			$qname     = substr($data,$posinstr + 1);
		}
		else
		{
			$namespace = substr($data,0,$posinstr);
			$qname     = substr($data,$posinstr + 1);

			if(!strlen(trim($namespace)))return;   // "#name" — zeigt auf den Default, sagt nichts
		}

		if(!strlen(trim($namespace)) || !strlen($qname))return;

		$new_obj = &$carrier->new_Instance();
		$new_obj->name = $data;
		$new_obj->type = $qname;
		$new_obj->set_idx($this->get_idx());
		$new_obj->namespace = $namespace;
		$new_obj->set_parser($this);

		$this->get_parser()->set_Object_to_Namespace($namespace . '#' . $qname, $new_obj);
		$new_obj->set_is_Class();
	}

}

?>
