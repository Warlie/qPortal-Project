<?PHP
/**
* Navigation - das Menue aus dem Baum, EINMAL gesammelt.
*
* @-------------------------------------------
* @title:Navigation
* @autor:Stefan Wegerhoff, Claude
* @description: laeuft den Baum ab und macht aus jedem erlaubten tree-Knoten eine Zeile -
*               als Ergebnismenge fuer XMLDO oder als Knoten im Ausgabebaum
*
* ⚠ DIE ALTE KLASSE BLEIBT. PlugIn/plugin_menu.php (class Menue) wird von 338 Aufrufen in
*   real_estate und rauhnacht benutzt und bleibt unangetastet. Diese hier heisst darum
*   anders: zwei Klassen gleichen Namens sterben, sobald ein Request beide Dateien laedt
*   ("Cannot redeclare class").
*
* == Warum es sie gibt (STW, 2026-09-30) ==
*
* "Ich will nur verhindern, dass eine Implementierung zwei mal vorkommt."
*
* In Menue steht die Sammelschleife DREIMAL: im Konstruktor (fuer build_menu), in
* find_line_start (fuer collect_Content) und die Zeilenbauerei ein drittes Mal in
* collect_lines. Die drei driften: nur zwei fragen getAccess(), nur eine kennt die
* URL-Vorlage mit dem Pfad-Praefix, und jede fuellt eine andere Datenstruktur.
*
* Hier gibt es EINE Sammelstelle (zeilen()), EINEN Zutrittstest und EINE Stelle, an der
* eine Zeile entsteht. Beide Ausgaenge - Ergebnismenge und Baum - holen dieselbe Liste ab.
*
* == ⚠ Der Konstruktor sammelt NICHT ==
*
* Gemessen 2026-09-30 im Log: Menue.__construct laeuft, BEVOR das erste Remote ankommt
* (Zeile 52 gegen Zeile 80). Alles, was das Dokument sagt - page, void_root, setLevelName -,
* kommt also zu spaet fuer das, was der alte Konstruktor schon gesammelt hat. build_menu
* verbraucht genau diese zu frueh entstandene Liste und zeigt darum das Menue des falschen
* Baums, wenn ein Dokument Menue.page setzt.
*
* Hier haelt der Konstruktor nur die Griffe und baut die URL-Vorlage. Gesammelt wird beim
* ERSTEN LESEN, dann sind alle Ansagen da.
*
* == Das Muster ==
*
*     <remote name="Navigation.setLine.pattern"><a href="%URL%">%VALUE%</a></remote>
*
*   %NAME%   tree:name    das Pfadsegment
*   %VALUE%  tree:value   das Tuerschild
*   %URL%    die fertige Adresse, mit Pfad-Praefix - oder /#name bei einem Abschnitt,
*            der mit mode=attached/embedded am Elternknoten haengt: der ist kein Weg,
*            sondern eine Sprungmarke auf derselben Seite
*   %DEEP%   die Tiefe, 0 fuer die erste Ebene
*
* Damit braucht es setLevelName nicht mehr: die Ebenen-Spaltennamen gab es nur, damit
* XMLDO die Tiefe unterscheiden kann. Die Spalten heissen hier fuer alle Zeilen gleich
* (name, value, url, deep, line), und die Tiefe steht IN der Zeile.
*/
/* ⚠ Diese Schreibweise, nicht dirname(__DIR__): so machen es die uebrigen Plugins in
*  Unterordnern (callTree, concat, demux, extract). Mit dem absoluten Pfad meldete der
*  Lader "Missing RDF edge ref (dangling subClassOf): PhpClass (pedl:name=Navigation)"
*  - die Klasse stand dann zwar in PHP, aber ihre Oberklasse nicht im Registrierungsbogen
*  (gemessen 2026-09-30). */
require_once("PlugIn/plugin_interface.php");

class Navigation extends plugin
{
	/* --- Griffe, vom Lader gestellt ---
	*  ⚠ $back NICHT neu deklarieren: die Oberklasse plugin fuehrt es als var (public),
	*  und PHP bricht sonst mit "Access level to Navigation::$back must be public" ab
	*  (gemessen 2026-09-30). */
	private $content = null;      // der ContentGenerator
	private $currentTreeNode;     // der Knoten, auf dem das <object> steht

	/* --- Ansagen des Dokuments, alle VOR dem ersten Lesen --- */
	private $seite    = null;     // welcher Baum (page)
	private $wurzel   = true;     // die Wurzel mitnehmen?
	private $muster   = '%VALUE%';// setLine
	private $ziel     = null;     // use_document: wohin build_menu schreibt
	private $max_tief = -1;       // -1 = ohne Grenze

	/* --- Ergebnis, einmal gesammelt --- */
	private $zeilen = null;       // null = noch nicht gesammelt
	private $pos    = 0;

	private $urlMuster = '';      // index.php?i=impressum&j=%s
	private $urlBasis  = '';      // index.php?i=%s

	var $tag;

	function __construct(/* System.Parser */ &$back, /* System.Content */ &$content, /* System.CurRef */ &$cur)
	{
		$this->back            = &$back;
		$this->content         = &$content;
		$this->currentTreeNode = $cur;

		$this->seite = $this->content->getXMLStructur();

		$this->url_vorlage_bauen();
	}

	/* ------------------------------------------------------- Ansagen des Dokuments */

	/*@
	function:: Waehlt den Baum, aus dem das Menue entsteht.
	param:: page = "$default" (das Dokument, in dem das object steht), "$this" (der Baum
	   des aufrufenden Knotens) oder eine URI.
	delivers:: <http://www.w3.org/2001/XMLSchema#boolean>
	@*/
	public function page($page)
	{
		if('$default' === $page)      { /* bleibt */ }
		elseif('$this' === $page)
		{
			/* ⚠ indexToUri gibt es am PARSER, nicht am ContentGenerator
			*  (xml_multitree.php:1318). Die alte Klasse ruft sie am ContentGenerator
			*  (plugin_menu.php:258) - "Call to undefined method
			*  ContentGenerator::indexToUri()". Die Ausnahme wird geschluckt, und das
			*  Menue nimmt still den Vorgabebaum: Menue.page("$this") hat nie getragen
			*  (gemessen 2026-09-30). */
			$idx = is_object($this->currentTreeNode) ? $this->currentTreeNode->get_idx() : null;
			$this->seite = $this->back->indexToUri(intval($idx));

			$this->melde('Navigation.page($this): Baum ' . var_export($idx, true)
				. ' = ' . var_export($this->seite, true), 5);
		}
		else                          $this->seite = $page;

		return $this->neu_sammeln();
	}

	/*@
	function:: Die Wurzel NICHT mitnehmen - das Menue beginnt bei ihren Kindern.
	@*/
	public function void_root() { $this->wurzel = false; return $this->neu_sammeln(); }

	/*@
	function:: Die Wurzel wieder mitnehmen. Gegenstueck zu void_root.
	@*/
	public function show_root() { $this->wurzel = true;  return $this->neu_sammeln(); }

	/*@
	function::
	   Das Muster einer Zeile. %NAME%, %VALUE%, %URL% und %DEEP% werden ersetzt.

	param:: pattern = z.B. <a href="%URL%">%VALUE%</a>

	delivers:: <http://www.w3.org/2001/XMLSchema#boolean>
	@*/
	public function setLine($pattern)
	{
		$this->muster = (string) $pattern;
		return $this->neu_sammeln();
	}

	/*@
	function::
	   Wohin build_menu schreibt. Ohne Angabe ist es die Ausgabevorlage.

	param:: documentName = die id eines <add> oder eine URI - dieselbe Schreibweise wie
	   bei get_template().

	delivers:: <http://www.w3.org/2001/XMLSchema#boolean>
	   ⚠ In der alten Klasse setzte use_document nur eine Eigenschaft, die NIEMAND las
	   (plugin_menu.php:235, gemessen 2026-09-30). Hier hat sie ihre gedachte Bedeutung.
	@*/
	public function use_document($documentName)
	{
		$this->ziel = (string) $documentName;
		return true;
	}

	/*@
	function:: Wie tief das Menue reicht. -1 = ohne Grenze.
	param:: deep = Zahl
	@*/
	public function deep($deep)
	{
		$this->max_tief = intval($deep);
		return $this->neu_sammeln();
	}

	/* --------------------------------------------------------------- Die Ausgaenge */

	/*@
	function::
	   Sammelt, falls noch nicht geschehen. Der Name aus der alten Klasse, damit ein
	   Dokument seine Gewohnheit behaelt - noetig ist er nicht, jedes Lesen sammelt selbst.

	delivers:: <http://www.w3.org/2001/XMLSchema#integer> Zahl der Zeilen
	@*/
	public function collect_Content()
	{
		return count($this->zeilen());
	}

	/*@
	function::
	   Schreibt das Menue als Knoten in den Zielbaum: je Zeile ein <a href>, dahinter ein
	   Leerzeichen.

	delivers:: <http://www.w3.org/2001/XMLSchema#integer> Zahl der geschriebenen Zeilen

	tricky::
	   ⚠ Das MUSTER gilt hier nicht. Ein Baum will Knoten, keine Zeichenkette: ein
	   Muster mit Markup muesste geparst werden, und Text mit " & < > landet ueber
	   setdata() in CDATA - in text/html ein unechter Kommentar, der Text verschwindet
	   (gemessen 2026-09-26 an der Live-Seite, zehn Stellen). Wer eigenes Markup will,
	   nimmt die Ergebnismenge und setzt es im Dokument zusammen.

	   ⚠ Gemeinsam mit der Ergebnismenge ist die LISTE: dieselbe Sammlung, derselbe
	   Zutrittstest, dieselben Adressen. In der alten Klasse liefen hier zwei getrennte
	   Wege, und nur einer fragte getAccess().
	@*/
	public function build_menu()
	{
		$zeilen = $this->zeilen();

		$stamp_zurueck = $this->back->position_stamp();
		$ziel          = $this->ziel_uri();

		if('' === $ziel) return 0;

		$this->back->change_URI($ziel);
		$stamp_ziel = $this->back->position_stamp();

		foreach($zeilen as $zeile)
		{
			$this->back->create_Ns_Node('a', $stamp_ziel, array('href' => $zeile['url']));
			$this->back->set_node_cdata($zeile['value'], 0);
			$this->back->parent_node();

			$this->back->create_Ns_Node('span', $stamp_ziel, array());
			$this->back->set_node_cdata(' ', 0);
			$this->back->parent_node();
		}

		$this->back->go_to_stamp($stamp_ziel);
		$this->back->go_to_stamp($stamp_zurueck);

		return count($zeilen);
	}

	/* ------------------------------------------------- Ergebnismenge (fuer XMLDO) */

	public function &iter() { return $this; }

	public function moveFirst() { $this->zeilen(); $this->pos = 0; return count($this->zeilen) > 0; }
	public function moveLast()  { $this->zeilen(); $this->pos = max(0, count($this->zeilen) - 1); return count($this->zeilen) > 0; }

	public function next()
	{
		$this->zeilen();

		if($this->pos < count($this->zeilen) - 1) { $this->pos++; return true; }

		return false;
	}

	public function prev()
	{
		if($this->pos > 0) { $this->pos--; return true; }

		return false;
	}

	public function many()   { return count($this->zeilen()); }
	public function fields() { return array('name', 'value', 'url', 'deep', 'line'); }

	public function col($columnName)
	{
		$zeilen = $this->zeilen();

		if(!isset($zeilen[$this->pos])) return false;

		return $zeilen[$this->pos][$columnName] ?? false;
	}

	public function decription() { return "macht aus den tree-Knoten eines Dokuments Menuezeilen"; }

	/* -------------------------------------------------------------------- innendrin */

	/** Eine Zeile ins Log - hier gibt es kein echo, das landete mitten in der Antwort. */
	private function melde(string $text, int $stufe): void
	{
		global $logger_class;

		if(is_object($logger_class)) $logger_class->setAssert($text, $stufe);
	}

	/** Eine Ansage nach dem Sammeln macht die Sammlung ungueltig, nicht falsch. */
	private function neu_sammeln()
	{
		$this->zeilen = null;
		$this->pos    = 0;

		return true;
	}

	/**
	*	DIE EINE SAMMELSTELLE. Laeuft den Baum ab, fragt je Knoten den Waechter und macht
	*	aus jedem erlaubten tree-Knoten eine Zeile. Gesammelt wird beim ersten Lesen -
	*	dann sind die Ansagen des Dokuments vollstaendig.
	*/
	private function zeilen(): array
	{
		if(is_array($this->zeilen)) return $this->zeilen;

		$this->zeilen = array();

		if(!is_object($this->back)) return $this->zeilen;

		$stamp_zurueck = $this->back->position_stamp();

		/* ⚠ change_URI vergleicht gegen die GELADENEN URIs (xml_multitree.php:1272) und
		*  gibt bei einem Fehlschlag still false zurueck - der Parser bleibt dann stehen,
		*  wo er war, und das Menue entsteht aus dem falschen Dokument, ohne dass jemand
		*  etwas merkt. Darum die Zeile im Log. */
		if(!$this->back->change_URI($this->seite))
			$this->melde('Navigation: Baum "' . var_export($this->seite, true)
				. '" ist nicht geladen - gesammelt wird aus dem Dokument, in dem der Parser steht', 0);

		$stamp_seite = $this->back->position_stamp();

		/* ⚠ Die Bedeutung ist die der alten Klasse (plugin_menu.php:477): bei WURZEL
		*  steigt es zuerst auf den ersten Knoten und von dort in dessen erstes Kind -
		*  also von <indextree> nach <final>, wo die tree-Knoten haengen. Ohne diesen
		*  Abstieg stuende der Parser auf dem Dokumentknoten, dessen Kinder keine trees
		*  sind, und es kaeme NICHTS heraus (gemessen: build_menu gab 0 zurueck, weil ich
		*  die Bedingung zuerst verdreht hatte). */
		if($this->wurzel)
		{
			$this->back->set_first_node();
			$this->back->child_node(0);
		}

		$this->ebene_sammeln(0);

		$this->back->go_to_stamp($stamp_seite);
		$this->back->go_to_stamp($stamp_zurueck);

		return $this->zeilen;
	}

	/**
	*	Eine Ebene und alles darunter. Der Parser steht beim Eintritt auf dem Elternknoten
	*	und steht beim Verlassen wieder dort - das ist die Bedingung dafuer, dass sich die
	*	Rekursion nicht selbst verirrt.
	*/
	private function ebene_sammeln(int $tiefe): void
	{
		if($this->max_tief >= 0 && $tiefe > $this->max_tief) return;

		$viele = $this->back->index_child();

		for($i = 0; $i < $viele; $i++)
		{
			$this->back->child_node($i);

			if($this->back->cur_node() == 'tree')
			{
				/* ⚠ EIN Zutrittstest, und zwar der gemeinsame: getAccess() prueft Stufe,
				*  Sektor, den fuehrenden Punkt und ein fehlendes value. In der alten Klasse
				*  stand er zweimal - und in build_menu gar nicht. */
				if($this->content->getAccess())
				{
					$this->zeilen[] = $this->zeile_vom_knoten($tiefe);
					$this->ebene_sammeln($tiefe + 1);
				}
			}

			$this->back->parent_node();
		}
	}

	/** DIE EINE STELLE, an der eine Zeile entsteht. */
	private function zeile_vom_knoten(int $tiefe): array
	{
		$name  = (string) $this->back->show_ns_attrib('http://www.trscript.de/tree#name');
		$wert  = (string) $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
		$url   = $this->url_zum_knoten($name);

		$zeile = array('name' => $name, 'value' => $wert, 'url' => $url, 'deep' => $tiefe);

		$zeile['line'] = str_replace(array('%NAME%', '%VALUE%', '%URL%', '%DEEP%'),
		                             array($name, $wert, $url, (string) $tiefe),
		                             $this->muster);

		return $zeile;
	}

	/**
	*	Die Adresse eines Knotens.
	*
	*	⚠ Ein Abschnitt, der mit mode=attached oder embedded am Elternknoten haengt, ist
	*	KEIN Weg: er laeuft mit der Seite, auf der er steht. Sein Eintrag ist darum eine
	*	Sprungmarke (/#name), kein Pfadsegment - sonst zeigte das Menue auf eine Adresse,
	*	die es nicht gibt (tree_tree.php, mode seit 2026-09-27).
	*/
	private function url_zum_knoten(string $name): string
	{
		$mode = $this->mode_des_knotens();

		if('attached' === $mode || 'embedded' === $mode)
			return '/#' . $name;

		if(false !== strpos($this->urlMuster, '%s'))
			return str_replace('%s', $name, $this->urlMuster);

		return str_replace('%s', $name, $this->urlBasis);
	}

	/** Der mode aus einem direkten <param name="mode">-Kind, oder ''. */
	private function mode_des_knotens(): string
	{
		$viele = $this->back->index_child();

		for($i = 0; $i < $viele; $i++)
		{
			$this->back->child_node($i);

			$treffer = ($this->back->cur_node() == 'param')
			        && ('mode' === (string) $this->back->show_ns_attrib('http://www.trscript.de/tree#name'));

			$wert = $treffer ? trim((string) $this->back->show_cur_data(0)) : '';

			$this->back->parent_node();

			if($treffer) return $wert;
		}

		return '';
	}

	/** Wohin build_menu schreibt: use_document, sonst die Ausgabevorlage. */
	private function ziel_uri(): string
	{
		if(is_null($this->ziel)) return (string) $this->content->get_out_template();

		$aus_heap = $this->content->get_template($this->ziel);

		return (string) ($aus_heap ?: $this->ziel);
	}

	/**
	*	Die URL-Vorlage aus der Achsenfolge (seit 2026-07-28): alle belegten Achsen plus
	*	den ersten freien - der freie ist die Abzweigung. Aus i=impressum wird
	*	index.php?i=impressum&j=%s, ein Menuepunkt haengt also an der aktuellen Position.
	*
	*	⚠ Ist keine Achse belegt (Startseite), bleibt in der alten Klasse ein
	*	"index.php?" OHNE %s stehen - dann setzt str_replace nichts ein und ALLE
	*	Menuepunkte zeigen auf dieselbe Adresse. Hier faellt es darum auf urlBasis
	*	zurueck.
	*/
	private function url_vorlage_bauen(): void
	{
		$achsen = $this->content->getLexicalOrderParam();
		$heap   = $this->content->getHeap()['request'] ?? array();

		$teile = array();
		foreach($achsen as $achse)
			$teile[] = $achse . '=' . ($heap[$achse] ?? '');

		$this->urlMuster = 'index.php?' . implode('&', $teile);

		$pos = strrpos($this->urlMuster, '=');

		if(false !== $pos)
			$this->urlMuster = substr($this->urlMuster, 0, $pos + 1) . '%s';
		else
			$this->urlMuster = '';          /* keine Achse belegt - urlBasis traegt */

		$erste = count($achsen) ? $achsen[0] : 'i';

		$this->urlBasis = 'index.php?' . $erste . '=%s';
	}
}
?>
