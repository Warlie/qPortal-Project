<?PHP

/**
* SPARQL
* @-------------------------------------------
* @title:SPARQL
* @autor:Stefan Wegerhoff
* @description: Fragt ueber das Suchmodell sparql und gibt die Zeilen wie ein DBO heraus
*
*
* Ein Dokument holt Daten heute ueber ein Plugin - DBO fuer die Datenbank, XMLDO fuer
* Baeume. Fuer SPARQL gab es das nicht: der einzige Weg im Bestand ist der Handle
* (<add doctype="SPARQL">, siehe template/api/Fuseki/sparql_test.xml), und der liest eine
* fertige ANTWORT, er stellt keine Frage. Dieses Plugin stellt sie.
*
* == Warum es aussieht wie ein DBO ==
*
* Weil eine QUELLE in diesem System diese Form hat. Wer Zeilen herausgibt, bietet:
*
*     moveFirst()    auf die erste Zeile
*     col($name)     den Wert einer Spalte
*     next()         weiter, false am Ende
*
* Dazu iter(), das $this zurueckgibt, damit ein Dokument die Quelle weiterreichen kann:
*
*     <remote name="set_list.value" ><object id="spq" ><remote name="iter" /></object></remote>
*     <remote name="set_list" />
*
* ⚠ Das ist KEINE Einladung, bestehende Ketten umzubauen. STW (2026-09-13) zu
* RstTurtle: „Fable hat da ein sehr maechtiges Werkzeug gebaut. Es baut Baeume und das
* ist unfassbar maechtig. Die Emergenz ist wirklich wild. Deshalb lass das alles mal so."
* RstTurtle hat formal nicht einmal einen rst-EINGANG, es gibt nur aus; SQL ist
* konzeptionell eine Datenquelle, Turtle eine SENKE, und erst beide zusammen machen die
* Kette zu beidem - auch wenn formal kein Turtle hineingeht. Wer das auseinandernimmt,
* nimmt etwas auseinander, das er nicht gebaut hat. template/ontologies/real_estate_data.xml
* bleibt, wie es ist.
*
* == Die Spaltennamen ==
*
* ⚠ SPARQL nennt seine Spalten mit dem Fragezeichen: "?s", "?wert". col() nimmt beide
* Formen - "?s" und "s" -, weil ein Dokument, das von DBO kommt, das Fragezeichen nicht
* erwartet und eine Spalte sonst still leer bliebe.
*
* == Knoten und Zeichenketten ==
*
* ⚠ In den Zeilen stehen bei type=qportal KNOTEN, keine Zeichenketten - die Abfrage laeuft
* gegen den Baum. col() gibt darum die volle URI des Knotens heraus, denn ein Verbraucher
* erwartet einen Wert. Wer den Knoten selbst braucht, nimmt node().
*/

require_once("plugin_interface.php");

class SPARQL extends plugin
{
	/* Das Suchmodell. Es kommt vom Parser (seek_by_model), nicht aus einem eigenen new -
	*  so gilt die Konfiguration aus [search] und [connection] wie ueberall. */
	private $model = null;

	/* Die Zeilen der letzten Abfrage und der Zeiger darauf. */
	private array $zeilen  = [];
	private int   $pos     = 0;
	private bool  $gefragt = false;

	/**
	*	⚠ System.CurRef ist noetig, nicht Zierde: es gibt dem Plugin seinen KNOTEN, und
	*	daraus den Baum, in dem es steht. Ohne das fragt eine baumlokale Abfrage den
	*	falschen Baum - gemessen stand der Parser beim Ausfuehren eines <program> auf
	*	@registry_surface_system, weil der PEDL-Dispatch gerade dort durchgelaufen war.
	*	Weder das Dokument noch die Ausgabe.
	*/
	function __construct(/* System.Parser */ &$back, /* System.CurRef */ &$cur = null)
	{
		$this->back = &$back;

		if($cur !== null) $this->treepos = &$cur;
	}

	/**
	*@function: USE_SOURCE = waehlt die Gegenstelle ueber ihren Profilnamen aus [connection]
	*@parameter: name = Profilname, leer nimmt die Vorgabe aus sparql.use
	*/
	public function use_source($name = '')
	{
		$this->modell()->use_source((string) $name);

		return $this;
	}

	/**
	*@function: QUERY = stellt die Abfrage und haelt ihre Zeilen
	*@parameter: statement = der SPARQL-Ausdruck
	*/
	public function query($statement)
	{
		$m = $this->modell();

		/* Ohne gewaehlte Quelle die eigene Instanz - sparql.use ist im Bestand leer,
		*  und profile() wuerfe sonst. Dieselbe Vorgabe wie in __where_am_i. */
		if(method_exists($m, 'source') && $m->source() === '')
			$m->use_source('qportal');

		/* ⚠ In MEINEM Baum fragen, nicht in dem, auf den der Parser gerade zeigt.
		*  collect_nodes ist baumlokal, und beim Ausfuehren eines <program> steht der
		*  Parser woanders (gemessen: auf dem Registrierungsbogen). Dieselbe Klammer wie
		*  in __where_am_i: hin, fragen, zurueck - auch wenn es wirft. */
		$zurueck = $this->back->cur_idx();
		$ziel    = is_object($this->treepos) ? $this->treepos->get_idx() : $zurueck;

		try
		{
			$this->back->change_idx($ziel);

			$treffer = $m->query((string) $statement);
		}
		finally
		{
			$this->back->change_idx($zurueck);
		}

		/* Zeilen kann nur, wer solutions() hat - das Interface Searching_Model verlangt
		*  nur query(). Sonst ist die Trefferliste alles, was es gibt. */
		$roh = method_exists($m, 'solutions') ? $m->solutions() : $treffer;

		$this->zeilen  = is_array($roh) ? array_values($roh) : array();
		$this->pos     = 0;
		$this->gefragt = true;

		return $this;
	}

	/* ---------------------------------------------------------------- die Quelle ---- */

	/**
	*@function: ITER = gibt die Quelle selbst heraus, zum Weiterreichen per set_list
	*/
	public function &iter()
	{
		return $this;
	}

	/**
	*@function: MOVEFIRST = auf die erste Zeile
	*/
	public function moveFirst()
	{
		$this->pos = 0;

		return count($this->zeilen) > 0;
	}

	/**
	*@function: MOVELAST = auf die letzte Zeile
	*/
	public function moveLast()
	{
		$this->pos = max(0, count($this->zeilen) - 1);

		return count($this->zeilen) > 0;
	}

	/**
	*@function: NEXT = eine Zeile weiter, false am Ende
	*/
	public function next()
	{
		if($this->pos + 1 >= count($this->zeilen)) return false;

		$this->pos++;

		return true;
	}

	/**
	*@function: PREV = eine Zeile zurueck, false am Anfang
	*/
	public function prev()
	{
		if($this->pos <= 0) return false;

		$this->pos--;

		return true;
	}

	/**
	*@function: RESET = Zeiger auf den Anfang
	*/
	public function reset()
	{
		$this->pos = 0;
	}

	/**
	*@function: MANY = wie viele Zeilen, -1 wenn noch nicht gefragt wurde
	*/
	public function many()
	{
		return $this->gefragt ? count($this->zeilen) : -1;
	}

	/**
	*@function: FIELDS = die Spaltennamen der aktuellen Zeile, ohne Fragezeichen
	*/
	public function fields()
	{
		$res = array();

		foreach(array_keys($this->zeile()) as $k)
			$res[] = ltrim((string) $k, '?');

		return $res;
	}

	/**
	*@function: COL = der Wert einer Spalte in der aktuellen Zeile
	*@parameter: columnName = Spaltenname, mit oder ohne Fragezeichen
	*/
	public function col($columnName)
	{
		$wert = $this->wert_von($columnName);

		if($wert instanceof Interface_node)
			return (string) $wert->full_URI();

		return is_object($wert) ? (string) $wert : (string) $wert;
	}

	/**
	*@function: NODE = der Knoten einer Spalte, falls einer darin steht - sonst null
	*@parameter: columnName = Spaltenname, mit oder ohne Fragezeichen
	*/
	public function node($columnName)
	{
		$wert = $this->wert_von($columnName);

		return ($wert instanceof Interface_node) ? $wert : null;
	}

	/**
	*@function: STAMP = der Positionsstempel einer Spalte, falls ein Knoten darin steht
	*@parameter: columnName = Spaltenname, mit oder ohne Fragezeichen
	*/
	public function stamp($columnName)
	{
		$k = $this->node($columnName);

		return is_object($k) ? (string) $k->position_stamp() : '';
	}

	/* ---------------------------------------------------------------- innen ---------- */

	/** Das Modell, beim ersten Gebrauch geholt. */
	private function modell()
	{
		if(is_null($this->model))
		{
			if(!is_object($this->back))
				throw new RuntimeException('SPARQL: kein Parser am Plugin');

			$this->model = $this->back->seek_by_model('sparql');

			if(is_null($this->model))
				throw new RuntimeException('SPARQL: es gibt kein Suchmodell "sparql"');
		}

		return $this->model;
	}

	/** Die aktuelle Zeile als Array, oder eine leere. */
	private function zeile(): array
	{
		$z = $this->zeilen[$this->pos] ?? array();

		return is_array($z) ? $z : array('wert' => $z);
	}

	/** ⚠ Mit und ohne Fragezeichen - SPARQL schreibt "?s", ein Dokument erwartet "s". */
	private function wert_von($columnName)
	{
		$z    = $this->zeile();
		$name = (string) $columnName;

		if(array_key_exists($name, $z))        return $z[$name];
		if(array_key_exists('?' . $name, $z))  return $z['?' . $name];
		if(array_key_exists(ltrim($name, '?'), $z)) return $z[ltrim($name, '?')];

		return '';
	}
}

?>
