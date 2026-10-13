<?PHP
/**
* Slice - zerteilt Zeitraeume an den Grenzen eines Rasters (Jahr, Monat, Tag, Stunde, Minute).
*
* @-------------------------------------------
* @title:Slice
* @autor:Stefan Wegerhoff, Claude
* @description: jede Zeile der Quelle ist ein Zeitraum [von, bis); wo er eine Rastergrenze
*               ueberschreitet, wird er in Stuecke zerlegt - je Stueck eine Zeile mit seinem
*               Anteil an der ganzen Dauer (slice_share, double)
*
* == Wofuer (STW, 2026-10-13) ==
*
*   "Eine Stromlieferung geht meist ueber das Jahr hinaus. Da wir nur einzelne Jahre abrechnen
*    koennen, muessen wir sie zerschneiden." - "Hier wuerde ich die Tage nehmen. Start bis 31.12
*    und dann immer weiter, bis Ende passiert ist. Hierbei wuerde ich jeweils in double angeben,
*    wie viel Anteil in diesem Teil steckt."
*
* Slice TEILT: die Anteile eines Zeitraums ergeben zusammen 1. Anders Interval.setExpand, das
* WIEDERHOLT (jeder Monat traegt die volle Miete). Den Betrag rechnet danach Formula
* (kosten = gross * slice_share).
*
* == Ablauf ==
*
*   set_list(rst)                  die Quelle (z.B. DBO auf qry_cost_invoice)
*   span(from_column, until_column)
*                                  der Zeitraum, halboffen: until gehoert nicht dazu
*   grid(unit)                     year | month | day | hour | minute
*
*   iter / moveFirst / next / col  je Stueck eine Zeile: alle Spalten der Quelle, dazu
*                                  slice_start, slice_end ('Y-m-d H:i:s', Ende ausschliesslich)
*                                  und slice_share (Anteil an der ganzen Dauer)
*
* == Anteil ==
*
* Gemessen in Sekunden in UTC - ohne Zeitumstellung. Bei Datumswerten sind das genau die Tage
* (Gas 1.7.2023 - 1.7.2024: 184/366 und 182/366). Das letzte Stueck bekommt 1 - Summe der
* anderen, damit die Anteile auf 1 aufgehen.
*
* Ein Zeitraum, der sich nicht lesen laesst oder nicht vorwaerts laeuft (bis <= von), bleibt
* EINE Zeile mit leeren slice-Spalten; ihre Anzahl steht in einer Logzeile (Stufe 5).
*
* ⚠ Spaltennamen mit Unterstrich, nicht mit '-' (start-dt): Formula liest '-' als Minus.
* ⚠ Nur Pflichtargumente: ein Remote-Parameter bleibt bis zum naechsten Setzen stehen.
* ⚠ Gerechnet wird beim ERSTEN LESEN, die Quelle wird dabei ganz gelesen.
*/
require_once(__DIR__ . '/../plugin_interface.php');

class Slice extends plugin
{
	const UNITS = ['year', 'month', 'day', 'hour', 'minute'];

	private $from   = '';
	private $until  = '';
	private $unit   = 'year';

	private $rows   = null;      // null = noch nicht gerechnet
	private $felder = [];
	private $pos    = 0;

	public function __construct() {}

	public function set_list(&$value)
	{
		if(!is_object($value)) return 'no element received';
		$this->rst = &$value;
		$this->rows = null;
	}

	public function span($from_column, $until_column)
	{
		$this->from  = trim((string)$from_column);
		$this->until = trim((string)$until_column);
		$this->rows = null;
	}

	public function grid($unit)
	{
		$unit = strtolower(trim((string)$unit));
		if(!in_array($unit, self::UNITS, true))
			throw new InvalidArgumentException("Slice: unbekannte Einheit '" . $unit . "' - erlaubt: " . implode(', ', self::UNITS));
		$this->unit = $unit;
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

	public function col($columnname)
	{
		$this->rechnen();
		if(!isset($this->rows[$this->pos])) return false;
		if(!array_key_exists($columnname, $this->rows[$this->pos]))
			throw new NotAFieldnameException('Slice: unknown column ' . $columnname);
		return $this->rows[$this->pos][$columnname];
	}

	public function fields()
	{
		$quelle = ($this->rst && method_exists($this->rst, 'fields')) ? $this->rst->fields() : [];
		return array_merge(array_values(array_diff($quelle, ['slice_start', 'slice_end', 'slice_share'])), ['slice_start', 'slice_end', 'slice_share']);
	}

	public function datatype($columnname)
	{
		if($columnname === 'slice_share') return 'double';
		if($columnname === 'slice_start' || $columnname === 'slice_end') return 'datetime';
		if($this->rst && method_exists($this->rst, 'datatype')) return $this->rst->datatype($columnname);
		return false;
	}

	public function decription() { return "zerteilt Zeitraeume an Jahres-, Monats-, Tages-, Stunden- oder Minutengrenzen, mit Anteil je Stueck"; }

	/* ----------------------------------------------------------------- intern */

	private function melde(string $text, int $stufe): void
	{
		global $logger_class;
		if(is_object($logger_class)) $logger_class->setAssert('Slice: ' . $text, $stufe);
	}

	private static function datum($value)
	{
		$value = trim((string)$value);
		if($value === '') return null;
		try { return new DateTimeImmutable($value, new DateTimeZone('UTC')); }
		catch(Exception $e) { return null; }
	}

	/** die naechste Rastergrenze NACH $t */
	public static function nextBoundary(DateTimeImmutable $t, string $unit): DateTimeImmutable
	{
		switch($unit)
		{
			case 'year':   return $t->setDate((int)$t->format('Y') + 1, 1, 1)->setTime(0, 0, 0);
			case 'month':  return $t->setDate((int)$t->format('Y'), (int)$t->format('n'), 1)->setTime(0, 0, 0)->modify('+1 month');
			case 'day':    return $t->setTime(0, 0, 0)->modify('+1 day');
			case 'hour':   return $t->setTime((int)$t->format('G'), 0, 0)->modify('+1 hour');
			case 'minute': return $t->setTime((int)$t->format('G'), (int)$t->format('i'), 0)->modify('+1 minute');
		}
		throw new InvalidArgumentException('Slice: unbekannte Einheit ' . $unit);
	}

	/** die Stuecke eines Zeitraums: [[start, ende, anteil], …]; null = kein Zeitraum */
	public static function pieces($from, $until, string $unit)
	{
		$a = self::datum($from); $b = self::datum($until);
		if($a === null || $b === null || $b <= $a) return null;

		$ganz = $b->getTimestamp() - $a->getTimestamp();
		$res = []; $summe = 0.0; $cur = $a;
		while($cur < $b)
		{
			$grenze = self::nextBoundary($cur, $unit);
			$ende = $grenze < $b ? $grenze : $b;
			$anteil = ($ende->getTimestamp() - $cur->getTimestamp()) / $ganz;
			$res[] = [$cur, $ende, $anteil];
			$summe += $anteil;
			$cur = $ende;
		}
		// das letzte Stueck traegt den Rest, damit die Anteile auf 1 aufgehen
		$res[count($res) - 1][2] = 1.0 - ($summe - $res[count($res) - 1][2]);
		return $res;
	}

	private function rechnen(): void
	{
		if($this->rows !== null) return;
		$this->rows = [];
		if(!is_object($this->rst) || !$this->rst->moveFirst()) return;

		$quelle = array_values(array_diff($this->rst->fields(), ['slice_start', 'slice_end', 'slice_share']));
		$schlecht = 0;
		do {
			$row = [];
			foreach($quelle as $c) $row[$c] = $this->rst->col($c);
			$stuecke = self::pieces($this->rst->col($this->from), $this->rst->col($this->until), $this->unit);
			if($stuecke === null)
			{
				$schlecht++;
				$this->rows[] = $row + ['slice_start' => '', 'slice_end' => '', 'slice_share' => ''];
				continue;
			}
			foreach($stuecke as [$s, $e, $anteil])
				$this->rows[] = $row + ['slice_start' => $s->format('Y-m-d H:i:s'), 'slice_end' => $e->format('Y-m-d H:i:s'), 'slice_share' => $anteil];
		} while($this->rst->next());

		if($schlecht > 0) $this->melde($schlecht . ' Zeilen ohne lesbaren Zeitraum (' . $this->from . ', ' . $this->until . ') - ungeteilt durchgereicht', 5);
	}
}
?>
