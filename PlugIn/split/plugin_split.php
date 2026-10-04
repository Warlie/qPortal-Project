<?PHP
/**
* Split - ein Bestand wird durch Aenderungen veraendert, und was dabei geteilt wird,
* bleibt als Rest stehen.
*
* @-------------------------------------------
* @title:Split
* @autor:Stefan Wegerhoff, Claude
* @description: wendet eine Aenderungsliste (etwa ein Formular ueber Request) auf einen
*               Bestand an; liefert den bewegten Teil (iter) und den Rest (give_residue)
*
* == Wofuer (STW, 2026-10-05) ==
*
*   "Es ist ja nicht einfach Butter sondern 300g Butter. Der Rest bleibt im anderen
*    Kuehlschrank. Ich werde also zwei RSTs haben. Der Eine modifiziert den anderen."
*
* Ein Posten ist ein HAUFEN (storage.owl, "Ein StockItem ist ein Haufen, kein Ding").
* Wird ein Teil davon bewegt, teilt er sich: der bewegte Teil behaelt die Identitaet
* (den Primaerschluessel), der Rest am alten Ort ist NEU - sein Schluessel ist null,
* und wer ihn speichert, legt ihn an.
*
* == Ablauf ==
*
*   set_list(rst)                 der Bestand (z.B. DBO.iter); der Primaerschluessel
*                                 kommt aus der Tabelleninformation (DBO.prim_field)
*   set_changes(rst)              die Aenderungen (z.B. Request.iter) - SOFORT in ein
*                                 Array gelesen: beide Listen werden vielfach gefragt,
*                                 und eine Kette vor jeder Abfrage waere langsamer
*   change_on(spalte, quelle)     die Spalte bekommt den Wert aus der Aenderung
*   sum_on(spalte, quelle)        die Mengenspalte; leere Menge = alles
*   key_on(quelle)                welche Spalte der Aenderung den Schluessel traegt
*                                 (Vorgabe: der Name des Primaerschluessels ohne Tabelle)
*
*   iter / moveFirst / next / col der BEWEGTE Teil: Schluessel bleibt, change_on neu,
*                                 Menge = die bewegte
*   give_residue()                der REST am alten Ort, nur fuer teilweise Bewegtes:
*                                 Schluessel null, Menge = alt - bewegt
*
* Eine Aenderungszeile, die nichts aendert (gleicher Ort, keine Teilmenge), faellt weg.
* Eine Menge <= 0 oder keine Zahl faellt weg, mit Logzeile.
*
* ⚠ Gerechnet wird beim ERSTEN LESEN, nicht beim Ansagen - wie bei Navigation: der
*   Konstruktor laeuft, bevor das erste Remote ankommt.
* ⚠ Noch nicht: einen gleichen Haufen am Ziel zusammenlegen (sum_on wird dafuer der
*   Anker sein), Einheiten umrechnen (die Menge gilt in der Einheit des Haufens).
*/
require_once("PlugIn/plugin_interface.php");

class Split extends plugin
{
	private $bestand  = null;     // das rst des Bestands
	private $changes  = [];       // die Aenderungen als Array von Zeilen
	private $change   = [];       // spalte => quelle
	private $sum      = null;     // Mengenspalte
	private $sum_src  = null;     // ihre Quelle in den Aenderungen
	private $key_src  = null;     // Schluesselspalte in den Aenderungen
	private $key      = null;     // Primaerschluessel des Bestands
	private $felder   = [];       // Spalten des Bestands

	private $bewegt   = null;     // null = noch nicht gerechnet
	private $rest     = [];
	private $zeige_rest = false;  // give_residue liefert einen Klon mit true

	public function __construct()
	{
	}

	public function set_list(&$value)
	{
		if(!is_object($value))
			throw new \RuntimeException("Split.set_list: der Bestand ist kein Objekt");

		$this->bestand = &$value;
		$this->bewegt  = null;
	}

	public function set_changes(&$value)
	{
		if(!is_object($value))
			throw new \RuntimeException("Split.set_changes: die Aenderungen sind kein Objekt");

		$this->changes = [];
		$felder = $value->fields();

		if($value->moveFirst())
			do
			{
				$zeile = [];
				foreach($felder as $f)
					$zeile[$f] = $value->col($f);
				$this->changes[] = $zeile;
			}
			while($value->next() && count($this->changes) < 100000);

		$this->bewegt = null;
	}

	public function change_on($columnname, $source = null)
	{
		$this->change[(string)$columnname] = self::quelle($columnname, $source);
		$this->bewegt = null;
	}

	public function sum_on($columnname, $source = null)
	{
		$this->sum     = (string)$columnname;
		$this->sum_src = self::quelle($columnname, $source);
		$this->bewegt  = null;
	}

	public function key_on($source)
	{
		$this->key_src = (string)$source;
		$this->bewegt  = null;
	}

	/** Der Rest am alten Ort - ein eigenes rst, damit ein zweites DBO ihn anlegen kann. */
	public function &give_residue()
	{
		$this->rechnen();
		$klon = clone $this;
		$klon->zeige_rest = true;
		$klon->internal_table_values = $this->rest;
		return $klon;
	}

	/* --------------------------------------------------------- das rst nach aussen */

	public function &iter()      { $this->rechnen(); return $this; }
	public function moveFirst()  { $this->rechnen(); return count($this->internal_table_values) > 0 && false !== reset($this->internal_table_values); }
	public function many()       { $this->rechnen(); return count($this->internal_table_values); }
	public function fields()     { $this->rechnen(); return $this->felder; }

	public function col($columnName)
	{
		$zeile = current($this->internal_table_values);
		if(!is_array($zeile)) return null;
		return array_key_exists($columnName, $zeile) ? $zeile[$columnName] : null;
	}

	public function decription() { return "teilt einen Bestand nach einer Aenderungsliste in den bewegten Teil und den Rest"; }

	/* ----------------------------------------------------------------- intern */

	/** Die Quelle einer Spalte in den Aenderungen: angegeben, sonst der Name ohne Tabelle. */
	private static function quelle($columnname, $source)
	{
		if(!is_null($source) && '' !== trim((string)$source)) return trim((string)$source);
		$teile = explode('.', (string)$columnname);
		return end($teile);
	}

	private function melde(string $text, int $stufe): void
	{
		global $logger_class;
		if(is_object($logger_class)) $logger_class->setAssert('Split: ' . $text, $stufe);
	}

	private function rechnen(): void
	{
		if(!is_null($this->bewegt)) return;

		$this->bewegt = [];
		$this->rest   = [];

		if(!is_object($this->bestand))
			throw new \RuntimeException("Split: kein Bestand (set_list fehlt)");

		$this->felder = $this->bestand->fields();

		/* Der Primaerschluessel aus der Tabelleninformation des Bestands. */
		$this->key = method_exists($this->bestand, 'prim_field') ? $this->bestand->prim_field() : null;
		if(!$this->key)
			throw new \RuntimeException("Split: der Bestand nennt keinen Primaerschluessel (prim_field)");

		$key_src = $this->key_src ?? self::quelle($this->key, null);

		/* Die Aenderungen nach Schluessel - EINE Nachschlage je Bestandszeile. */
		$nach_schluessel = [];
		foreach($this->changes as $c)
		{
			$k = (string)($c[$key_src] ?? '');
			if('' !== $k) $nach_schluessel[$k] = $c;
		}

		if(!count($nach_schluessel) || !$this->bestand->moveFirst())
		{
			$this->internal_table_values = [];
			return;
		}

		do
		{
			$k = (string)$this->bestand->col($this->key);
			if(!isset($nach_schluessel[$k])) continue;

			$c = $nach_schluessel[$k];

			$zeile = [];
			foreach($this->felder as $f) $zeile[$f] = $this->bestand->col($f);

			/* Was die Aenderung setzt. */
			$geaendert = false;
			$neu = $zeile;
			foreach($this->change as $spalte => $quelle)
				if(array_key_exists($quelle, $c) && !is_null($c[$quelle]) && '' !== (string)$c[$quelle])
				{
					if((string)$c[$quelle] !== (string)$zeile[$spalte]) $geaendert = true;
					$neu[$spalte] = $c[$quelle];
				}

			/* Wieviel bewegt wird. */
			$teil = false;
			if(!is_null($this->sum))
			{
				$alt   = (float)$zeile[$this->sum];
				$menge = $c[$this->sum_src] ?? '';

				if(!is_null($menge) && '' !== trim((string)$menge))
				{
					$menge = str_replace(',', '.', trim((string)$menge));
					if(!is_numeric($menge) || (float)$menge <= 0)
					{
						$this->melde('Menge "' . $menge . '" fuer ' . $k . ' ist keine positive Zahl - uebergangen', 0);
						continue;
					}
					if((float)$menge < $alt)
					{
						$teil = true;
						$neu[$this->sum] = (string)(float)$menge;
					}
				}
			}

			if(!$geaendert && !$teil) continue;

			$this->bewegt[] = $neu;

			if($teil)
			{
				$rest = $zeile;
				$rest[$this->key]  = null;   // kein Rest im Sinne des Alten: der Rest ist NEU
				$rest[$this->sum]  = (string)($alt - (float)$neu[$this->sum]);
				$this->rest[] = $rest;
			}
		}
		while($this->bestand->next());

		$this->internal_table_values = $this->zeige_rest ? $this->rest : $this->bewegt;
		reset($this->internal_table_values);
	}
}
?>
