<?PHP
/**
* Formula - rechnet je Zeile neue Spalten aus Formeln ueber die Spalten der Quelle.
*
* @-------------------------------------------
* @title:Formula
* @autor:Stefan Wegerhoff, Claude
* @description: Formel-Rechner nach Kahn (faehub/kahn.js + effect_parser.js): die Zeile der
*               Quelle ist die Wertliste, die Formeln werden nach ihren Abhaengigkeiten
*               geordnet und der Reihe nach gerechnet
*
* == Herkunft ==
*
* Uebertragen aus ~/rauhnacht/qPortal-Project/faehub (JavaScript, 2026-07): kahn.js sortiert
* die Formelliste topologisch (Kahn), effect_parser.js rechnet die Terme (parseTerms,
* evalTerms). Die Wertliste ist hier die aktuelle Zeile der Quelle, kein Charakter.
*
* == Ablauf ==
*
*   set_list(rst)               die Quelle (z.B. Aggregation.iter)
*   formula(name, expression)   eine neue Spalte; mehrfach. Eine Formel darf eine andere
*                               nennen - die Reihenfolge der Angabe spielt keine Rolle.
*
*   iter / moveFirst / next / col   die Zeilen der Quelle, dazu die Formelspalten
*
* == Grammatik (wie effect_parser.parseTerms) ==
*
*   ausdruck := Term { ("+"|"-") Term }          "haben - soll - nebenkosten", "1.19netto"
*   Term     := [Koeffizient] [Name]             Name = [A-Za-z_][A-Za-z0-9_.]* - der Punkt ist
*                                                hier erlaubt (qualifizierte Spalten: tbl.col)
*
* Kein Mal zwischen zwei Spalten, keine Klammern - wie im Vorbild. Ein Name mit '-' (start-dt)
* ist nicht ansprechbar: '-' ist das Minus.
*
* == Fehlende Werte ==
*
* Ein leerer oder nicht-numerischer Wert zaehlt 0 (Aggregation liefert '' fuer leere Texte).
* Eine Spalte, die die Quelle nicht hat, zaehlt ebenfalls 0 - wie im Vorbild ein Feature
* ("Formeln duerfen nennen, was es noch nicht gibt") -, wird aber EINMAL geloggt (Stufe 5).
* Ein Zyklus wird namentlich geloggt, seine Formeln liefern '' - nie still.
* Eine Formel, die sich nicht lesen laesst, wirft beim Ansagen (InvalidArgumentException).
*
* ⚠ Nur Pflichtargumente: ein Remote-Parameter bleibt bis zum naechsten Setzen stehen.
*/
require_once(__DIR__ . '/../plugin_interface.php');

class Formula extends plugin
{
	private $formeln  = [];      // name => ['raw' => text, 'terms' => [...], 'deps' => [namen]]
	private $order    = null;    // null = noch nicht sortiert
	private $cycle    = [];
	private $werte    = null;    // Formelwerte der aktuellen Zeile, null = noch nicht gerechnet
	private $gemeldet = [];

	public function __construct() {}

	public function set_list(&$value)
	{
		if(!is_object($value)) return 'no element received';
		$this->rst = &$value;
		$this->werte = null;
	}

	public function formula($name, $expression)
	{
		$name = trim((string)$name);
		$terms = self::parseTerms((string)$expression);
		if($name === '' || $terms === null)
			throw new InvalidArgumentException("Formula: '" . $name . ":" . $expression . "' ist keine Formel");
		$deps = [];
		foreach($terms as $t) if($t['sym'] !== null) $deps[] = $t['sym'];
		$this->formeln[$name] = ['raw' => (string)$expression, 'terms' => $terms, 'deps' => array_values(array_unique($deps))];
		$this->order = null;
		$this->werte = null;
	}

	public function &iter() { return $this; }

	public function moveFirst()
	{
		$this->werte = null;
		return $this->rst ? $this->rst->moveFirst() : false;
	}

	public function next()
	{
		$this->werte = null;
		return $this->rst ? $this->rst->next() : false;
	}

	public function col($columnname)
	{
		if(isset($this->formeln[$columnname]))
		{
			$this->rechnen();
			return $this->werte[$columnname];
		}
		return $this->rst ? $this->rst->col($columnname) : false;
	}

	public function fields()
	{
		$quelle = $this->rst ? $this->rst->fields() : [];
		return array_merge($quelle, array_values(array_diff(array_keys($this->formeln), $quelle)));
	}

	public function datatype($columnname)
	{
		if(isset($this->formeln[$columnname])) return 'decimal(20,6)';
		if($this->rst && method_exists($this->rst, 'datatype')) return $this->rst->datatype($columnname);
		return false;
	}

	/** die Formelliste in Rechenreihenfolge, Zyklen am Ende mit reihenfolge null - wie kahn.explain */
	public function explain()
	{
		$this->sortieren();
		$res = [];
		foreach($this->order as $i => $name) $res[] = ['reihenfolge' => $i + 1, 'name' => $name, 'formel' => $this->formeln[$name]['raw']];
		foreach($this->cycle as $name)       $res[] = ['reihenfolge' => null,   'name' => $name, 'formel' => $this->formeln[$name]['raw']];
		return $res;
	}

	public function decription() { return "rechnet je Zeile neue Spalten aus Formeln ueber die Spalten der Quelle"; }

	/* ----------------------------------------------------------------- intern */

	/** "-2w+o" -> [{sign:-1,coef:2,sym:"w"},{sign:1,coef:1,sym:"o"}]; null = kein Zahlausdruck */
	public static function parseTerms($src)
	{
		$raw = preg_replace('/\s+/', '', $src);
		if($raw === '') return null;
		if(!preg_match_all('/[+-]?[^+-]+/', $raw, $parts) || implode('', $parts[0]) !== $raw) return null;

		$terms = [];
		foreach($parts[0] as $part)
		{
			if(!preg_match('/^([+-]?)(\d*\.?\d*)([A-Za-z_][A-Za-z0-9_.]*)?$/', $part, $m)) return null;
			$coef = $m[2]; $sym = $m[3] ?? '';
			if(($coef === '' || $coef === '.') && $sym === '') return null;
			$terms[] = [
				'sign' => $m[1] === '-' ? -1 : 1,
				'coef' => ($coef === '' ? 1.0 : floatval($coef)),
				'sym'  => ($sym === '' ? null : $sym),
			];
		}
		return $terms;
	}

	private function melde(string $text, int $stufe): void
	{
		global $logger_class;
		if(is_object($logger_class)) $logger_class->setAssert('Formula: ' . $text, $stufe);
	}

	/** Kahn: eine Formel kommt dran, wenn alle Formeln, die sie nennt, gerechnet sind */
	private function sortieren(): void
	{
		if($this->order !== null) return;
		$indeg = []; $wartet = [];
		foreach($this->formeln as $name => $f)
		{
			$indeg[$name] = 0;
			foreach($f['deps'] as $d)
				if(isset($this->formeln[$d]))      // auch sich selbst: das ist ein Zyklus
				{
					$indeg[$name]++;
					$wartet[$d][] = $name;
				}
		}
		$queue = array_keys(array_filter($indeg, fn($n) => $n === 0));
		$this->order = [];
		while($queue)
		{
			$name = array_shift($queue);
			$this->order[] = $name;
			foreach($wartet[$name] ?? [] as $j)
				if(--$indeg[$j] === 0) $queue[] = $j;
		}
		$this->cycle = array_keys(array_filter($indeg, fn($n) => $n > 0));
		if($this->cycle)
			$this->melde('Formel-Zyklus, uebersprungen - ' . implode(' | ', array_map(fn($n) => $n . ' (' . $this->formeln[$n]['raw'] . ')', $this->cycle)), 5);
	}

	private function rechnen(): void
	{
		if($this->werte !== null) return;
		$this->sortieren();
		$this->werte = array_fill_keys($this->cycle, '');
		$felder = $this->rst ? array_flip($this->rst->fields()) : [];

		foreach($this->order as $name)
		{
			$sum = 0.0;
			foreach($this->formeln[$name]['terms'] as $t)
			{
				$faktor = 1.0;
				if($t['sym'] !== null)
				{
					if(array_key_exists($t['sym'], $this->werte))   $v = $this->werte[$t['sym']];
					elseif(isset($felder[$t['sym']]))               $v = $this->rst->col($t['sym']);
					else
					{
						if(!isset($this->gemeldet[$t['sym']]))
						{
							$this->gemeldet[$t['sym']] = true;
							$this->melde("unbekannte Spalte '" . $t['sym'] . "' - zaehlt 0 (einmalige Meldung)", 5);
						}
						$v = 0;
					}
					$faktor = is_numeric($v) ? floatval($v) : 0.0;
				}
				$sum += $t['sign'] * $t['coef'] * $faktor;
			}
			// gegen Gleitkommareste (0.1 + 0.2): auf 6 Stellen, wie der Typ decimal(20,6)
			$this->werte[$name] = round($sum, 6);
		}
	}
}
?>
