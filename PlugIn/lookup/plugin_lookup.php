<?PHP
/**
* Lookup - schlaegt je Zeile einer Liste Spalten in einer zweiten Liste nach.
*
* @-------------------------------------------
* @title:Lookup
* @autor:Stefan Wegerhoff, Claude
* @description: legt neben jede Zeile der Quelle Spalten aus der ersten passenden Zeile einer
*               Nachschlageliste (gleicher Schluessel); Zeilen und Reihenfolge der Quelle bleiben
*
* == Wofuer (STW, 2026-11-15) ==
*
*   "Bei Steller landet die ID. ... jedoch steht dieser hinterher auch in der Zeile, die nicht
*    editierbar ist. Da waere es nuetzlich, den echten Namen zu lesen."
*   Die Rechnungen liegen in vertraege, die Namen in real_estate - auf orga zwei Datenbanken mit
*   eigenen Benutzern, ein JOIN geht nicht. Lookup verbindet zwei Ergebnismengen.
*
* == Abgrenzung zu Ledger ==
*
* Ledger ordnet jeden EINTRAG genau EINER Rasterzeile zu (eine Zahlung zaehlt einmal). Hier ist
* es umgekehrt: viele Zeilen der Quelle duerfen dieselbe Zeile der Nachschlageliste treffen
* (zehn Rechnungen desselben Stellers), und keine Zeile der Quelle faellt weg.
*
* == Ablauf ==
*
*   set_list(rst)                   die Quelle (z.B. DBO.iter auf die Rechnungen)
*   set_lookup(rst)                 die Nachschlageliste (z.B. DBO.iter auf die Akteure)
*   key(list_column, lookup_column) Schluessel: Wert der Quelle == Wert der Liste (Text, exakt)
*   take(name, column)              neue Spalte name = column der passenden Zeile; mehrfach.
*                                   Ohne Treffer: '' (die Zeile bleibt).
*
*   iter / moveFirst / next / col   die Zeilen der Quelle, dazu die take-Spalten
*
* Die Nachschlageliste wird beim ersten Lesen einmal ganz gelesen; bei doppeltem Schluessel gilt
* die erste Zeile. Wie viele Zeilen der Quelle ohne Treffer blieben, sagt missing() und eine
* Logzeile (Stufe 5).
*
* ⚠ Nur Pflichtargumente: ein Remote-Parameter bleibt bis zum naechsten Setzen stehen.
*/
require_once(__DIR__ . '/../plugin_interface.php');

class Lookup extends plugin
{
	private $lookup     = null;
	private $list_col   = null;
	private $lookup_col = null;
	private $take       = [];      // name => Spalte der Nachschlageliste
	private $map        = null;    // Schluessel => [name => Wert]; null = noch nicht gelesen
	private $missing    = null;

	public function __construct() {}

	public function set_list(&$value)
	{
		if(!is_object($value)) return 'no element received';
		$this->rst = &$value;
		$this->missing = null;
	}

	public function set_lookup(&$value)
	{
		if(!is_object($value)) return 'no element received';
		$this->lookup = &$value;
		$this->map = null;
	}

	public function key($list_column, $lookup_column)
	{
		$this->list_col = (string)$list_column;
		$this->lookup_col = (string)$lookup_column;
		$this->map = null;
	}

	public function take($name, $column)
	{
		$name = trim((string)$name);
		if($name === '') throw new InvalidArgumentException("Lookup: take braucht einen Namen");
		$this->take[$name] = (string)$column;
		$this->map = null;
	}

	public function &iter() { return $this; }

	public function moveFirst()
	{
		$this->lesen();
		if(!$this->rst || !$this->rst->moveFirst()) return false;
		if($this->missing === null) $this->zaehlen();
		return true;
	}

	public function next() { return $this->rst ? $this->rst->next() : false; }

	public function col($columnname)
	{
		if(isset($this->take[$columnname]))
		{
			$this->lesen();
			$k = $this->rst ? (string)$this->rst->col($this->list_col) : '';
			return isset($this->map[$k]) ? $this->map[$k][$columnname] : '';
		}
		return $this->rst ? $this->rst->col($columnname) : false;
	}

	public function fields()
	{
		$quelle = $this->rst ? $this->rst->fields() : [];
		return array_merge($quelle, array_values(array_diff(array_keys($this->take), $quelle)));
	}

	public function datatype($columnname)
	{
		if(isset($this->take[$columnname]))
			return ($this->lookup && method_exists($this->lookup, 'datatype')) ? $this->lookup->datatype($this->take[$columnname]) : 'varchar(255)';
		if($this->rst && method_exists($this->rst, 'datatype')) return $this->rst->datatype($columnname);
		return false;
	}

	/** wie viele Zeilen der Quelle keinen Treffer hatten (nach dem ersten moveFirst) */
	public function missing() { return $this->missing === null ? 0 : $this->missing; }

	public function decription() { return "schlaegt je Zeile einer Liste Spalten in einer zweiten Liste nach"; }

	/* ----------------------------------------------------------------- intern */

	private function lesen()
	{
		if($this->map !== null) return;
		$this->map = [];
		if(!$this->lookup || $this->lookup_col === null || !$this->lookup->moveFirst()) return;
		do
		{
			$k = (string)$this->lookup->col($this->lookup_col);
			if(isset($this->map[$k])) continue;          // der erste gilt
			$zeile = [];
			foreach($this->take as $name => $spalte) $zeile[$name] = $this->lookup->col($spalte);
			$this->map[$k] = $zeile;
		}
		while($this->lookup->next());
	}

	/** einmal ueber die Quelle: Zeilen ohne Treffer zaehlen, danach wieder auf die erste */
	private function zaehlen()
	{
		$n = 0;
		do { if(!isset($this->map[(string)$this->rst->col($this->list_col)])) $n++; } while($this->rst->next());
		$this->missing = $n;
		$this->rst->moveFirst();
		if($n > 0)
		{
			global $logger_class;
			if(is_object($logger_class)) $logger_class->setAssert("Lookup: $n Zeile(n) ohne Treffer fuer '" . $this->list_col . "'", 5);
		}
	}
}
