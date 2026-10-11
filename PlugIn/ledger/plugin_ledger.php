<?PHP
/**
* Ledger - legt Eintraege (Zahlungen) neben ein Raster (Soll-Zeilen).
*
* @-------------------------------------------
* @title:Ledger
* @autor:Stefan Wegerhoff, Claude
* @description: ordnet jeden Eintrag einer Zweitquelle der ersten passenden Zeile eines Rasters
*               zu (sameAs, between) und liefert Rasterzeilen und Eintraege in einer Liste,
*               mit getrennten Spalten - etwa Soll und Haben
*
* == Wofuer (STW, 2026-10-11) ==
*
*   "Eine Zahlung sollte zwischen zwei Daten passieren. Sie steht in einer weiteren Spalte,
*    die Haben heissen soll. [...] Soll und Haben sollen zwei Spalten sein."
*   "pro Monat alles zusammen rechnen" - das tut danach Aggregation (Gruppe = Monat).
*
* Keine Tilgung einzelner Monate: die Mieter zahlen zusammen, rechnen Zahlungen zusammen.
* Ledger ordnet nur zeitlich ein; was ein Monat schuldet und was in ihm einging, summiert
* Aggregation.
*
* == Ablauf ==
*
*   set_list(rst)                    das Raster (z.B. Interval.iter mit setExpand)
*   set_entries(rst)                 die Eintraege (z.B. DBO.iter auf die Zahlungen)
*   sameAs(frame_column, entry_column)
*                                    der Eintrag passt nur, wenn beide Werte gleich sind; steht
*                                    im Raster eine Liste mit Komma (wie Aggregation Texte
*                                    verbindet), genuegt einer der Werte. Mehrfach = alle.
*   between(from, until, entry_column)
*                                    der Eintrag passt nur, wenn from <= Wert < until
*   frame(name, column)              Ausgabespalte nur der Rasterzeilen (Soll); Eintraege ''
*   entry(name, column)              Ausgabespalte nur der Eintraege (Haben); Rasterzeilen ''
*   inherit(name, column)            Ausgabespalte aus dem Raster, auch fuer jeden Eintrag -
*                                    aus SEINER Rasterzeile (Monat, Vertrag)
*
* ⚠ Jede Methode hat nur Pflichtargumente: ein Remote-Parameter bleibt bis zum naechsten
*   Setzen stehen. Ein optionales Argument, im zweiten Aufruf ausgelassen, kaeme mit dem Wert
*   des ersten an (gemessen: Haben = Zahlung + Soll, solange es column(name, frame, entry) gab).
*
*   iter / moveFirst / next / col    jede Rasterzeile in ihrer Reihenfolge, direkt dahinter
*                                    ihre Eintraege in deren Reihenfolge. Spalte 'ledger-row'
*                                    sagt 'frame' oder 'entry'.
*
* == Vergleich: lexikalisch, auf die groebere Genauigkeit ==
*
* Verglichen wird als Text (strcmp). Ist ein Wert der Anfang des anderen, gelten beide als
* gleich: '2026-02-01' (DBO, date) ist dann gleich '2026-02-01 00:00:00' (Interval). Ohne die
* Regel waere der Monatserste kleiner als der Monatsbeginn und fiele aus seinem Monat.
* ⚠ Gilt fuer Werte gleicher Schreibweise - Datum ISO, Zahlen gleich lang.
*
* == Eindeutig ==
*
* Ein Eintrag gehoert genau EINER Rasterzeile, der ersten passenden. Im Monat stehen mehrere
* Soll-Zeilen (Laden, Wohnung) mit demselben Zeitraum - sonst zaehlte die Zahlung doppelt.
* Ein Eintrag ohne passende Zeile faellt weg; ihre Anzahl steht in einer Logzeile (Stufe 5)
* und in unassigned().
*
* ⚠ Gerechnet wird beim ERSTEN LESEN (moveFirst), beide Quellen werden dabei ganz gelesen.
*/
require_once(__DIR__ . '/../plugin_interface.php');

class Ledger extends plugin
{
	private $frame   = null;      // rst des Rasters
	private $entries = null;      // rst der Eintraege
	private $same    = [];        // [frame_column, entry_column]
	private $range   = [];        // [from, until, entry_column]
	private $spalten = [];        // name => ['frame' => spalte|'', 'entry' => spalte|'', 'inherit' => bool]

	private $rows    = null;      // null = noch nicht gerechnet
	private $pos     = 0;
	private $lost    = 0;

	public function __construct() {}

	public function set_list(&$value)
	{
		if(!is_object($value)) return 'no element received';
		$this->frame = &$value;
		$this->rows = null;
	}

	public function set_entries(&$value)
	{
		if(!is_object($value)) return 'no element received';
		$this->entries = &$value;
		$this->rows = null;
	}

	public function sameAs($frame_column, $entry_column)
	{
		$this->same[] = [trim((string)$frame_column), trim((string)$entry_column)];
		$this->rows = null;
	}

	public function between($from, $until, $entry_column)
	{
		$this->range[] = [trim((string)$from), trim((string)$until), trim((string)$entry_column)];
		$this->rows = null;
	}

	public function frame($name, $column)
	{
		$this->spalten[trim((string)$name)] = ['frame' => trim((string)$column), 'entry' => '', 'inherit' => false];
		$this->rows = null;
	}

	public function entry($name, $column)
	{
		$this->spalten[trim((string)$name)] = ['frame' => '', 'entry' => trim((string)$column), 'inherit' => false];
		$this->rows = null;
	}

	public function inherit($name, $column)
	{
		$this->spalten[trim((string)$name)] = ['frame' => trim((string)$column), 'entry' => '', 'inherit' => true];
		$this->rows = null;
	}

	public function &iter() { return $this; }

	public function moveFirst()
	{
		$this->rechnen();
		$this->pos = 0;
		return count($this->rows) > 0;
	}

	public function next()
	{
		$this->rechnen();
		if($this->pos + 1 >= count($this->rows)) { $this->pos = count($this->rows); return false; }
		$this->pos++;
		return true;
	}

	public function many() { $this->rechnen(); return count($this->rows); }

	public function unassigned() { $this->rechnen(); return $this->lost; }

	public function col($columnname)
	{
		$this->rechnen();
		if(!isset($this->rows[$this->pos])) return false;
		if(!array_key_exists($columnname, $this->rows[$this->pos]))
			throw new NotAFieldnameException('Ledger: unknown column ' . $columnname);
		return $this->rows[$this->pos][$columnname];
	}

	public function fields() { return array_merge(array_keys($this->spalten), ['ledger-row']); }

	/** der Typ der Quelle - Raster zuerst, sonst die Eintraege; Aggregation summiert danach */
	public function datatype($columnname)
	{
		if($columnname === 'ledger-row') return 'varchar(5)';
		if(!isset($this->spalten[$columnname])) return false;
		$def = $this->spalten[$columnname];
		foreach([[$this->frame, $def['frame']], [$this->entries, $def['entry']]] as $q)
			if($q[1] !== '' && is_object($q[0]) && method_exists($q[0], 'datatype') && ($type = $q[0]->datatype($q[1])))
				return $type;
		return 'varchar(255)';
	}

	public function decription() { return "legt Eintraege (Haben) neben die passende Zeile eines Rasters (Soll)"; }

	/* ----------------------------------------------------------------- intern */

	/** lexikalisch, auf die groebere Genauigkeit: ist einer der Anfang des anderen, sind sie gleich */
	public static function compare($a, $b)
	{
		$a = (string)$a; $b = (string)$b;
		$n = min(strlen($a), strlen($b));
		return strcmp(substr($a, 0, $n), substr($b, 0, $n)) <=> 0;
	}

	private function melde(string $text, int $stufe): void
	{
		global $logger_class;
		if(is_object($logger_class)) $logger_class->setAssert('Ledger: ' . $text, $stufe);
	}

	private static function lesen($rst, array $cols): array
	{
		$res = [];
		if(!is_object($rst) || !$rst->moveFirst()) return $res;
		do {
			$row = [];
			foreach($cols as $c) $row[$c] = $rst->col($c);
			$res[] = $row;
		} while($rst->next());
		return $res;
	}

	private function passt(array $f, array $e): bool
	{
		foreach($this->same as [$fc, $ec])
		{
			$hit = false;
			foreach(explode(',', (string)$f[$fc]) as $v)
				if(trim($v) === trim((string)$e[$ec])) { $hit = true; break; }
			if(!$hit) return false;
		}
		foreach($this->range as [$from, $until, $ec])
		{
			if($f[$from] !== '' && $f[$from] !== null && self::compare($e[$ec], $f[$from]) < 0) return false;
			if($f[$until] !== '' && $f[$until] !== null && self::compare($e[$ec], $f[$until]) >= 0) return false;
		}
		return true;
	}

	private function rechnen(): void
	{
		if($this->rows !== null) return;
		$this->rows = [];
		$this->lost = 0;

		$fcols = []; $ecols = [];
		foreach($this->spalten as $def) { if($def['frame'] !== '') $fcols[] = $def['frame']; if($def['entry'] !== '') $ecols[] = $def['entry']; }
		foreach($this->same as [$fc, $ec]) { $fcols[] = $fc; $ecols[] = $ec; }
		foreach($this->range as [$from, $until, $ec]) { $fcols[] = $from; $fcols[] = $until; $ecols[] = $ec; }

		$frames  = self::lesen($this->frame,   array_values(array_unique($fcols)));
		$entries = self::lesen($this->entries, array_values(array_unique($ecols)));

		// je Rasterzeile ihre Eintraege - der erste Treffer gewinnt
		$je = array_fill(0, count($frames), []);
		foreach($entries as $e)
		{
			$ziel = null;
			foreach($frames as $i => $f) if($this->passt($f, $e)) { $ziel = $i; break; }
			if($ziel === null)
			{
				$this->lost++;
				continue;
			}
			$je[$ziel][] = $e;
		}

		// eine Zeile, nicht eine je Eintrag: die Zweitquelle ist oft breiter als das Raster
		if($this->lost > 0) $this->melde($this->lost . ' von ' . count($entries) . ' Eintraegen ohne Rasterzeile', 5);

		foreach($frames as $i => $f)
		{
			$row = ['ledger-row' => 'frame'];
			foreach($this->spalten as $name => $def) $row[$name] = $def['frame'] !== '' ? $f[$def['frame']] : '';
			$this->rows[] = $row;

			foreach($je[$i] as $e)
			{
				$row = ['ledger-row' => 'entry'];
				foreach($this->spalten as $name => $def)
					$row[$name] = $def['inherit'] ? $f[$def['frame']] : ($def['entry'] !== '' ? $e[$def['entry']] : '');
				$this->rows[] = $row;
			}
		}
	}
}
?>
