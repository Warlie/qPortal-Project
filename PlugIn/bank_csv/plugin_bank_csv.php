<?PHP
/**
* BankCsv - bereitet einen hochgeladenen Kontoauszug (CSV) fuer den Import vor.
*
* @-------------------------------------------
* @title:BankCsv
* @autor:Stefan Wegerhoff, Claude
* @description: prueft die Kopfzeile gegen die erlaubten Formate, nimmt das BOM weg, wandelt
*               UTF-8 nach Latin-1 und gleicht Kontonummern an; legt das Ergebnis dort ab,
*               wo der Import (.bank_import) liest
*
* == Wofuer (STW, 2026-10-13) ==
*
*   "Wir machen weiter mit einem Upload der CSVs ueber eine Webseite. Orientiere Dich hier gern
*    an der lokalen sh-Datei." - die Schritte 1 und 1b aus ~/Nextcloud2/Bank-Auszuege/
*    upload_csv.sh, jetzt auf dem Server.
*
* == Ablauf ==
*
*   pattern(name, header)       ein erlaubtes Format: Name und Kopfzeile (Spalten mit ';').
*                               Derselbe Text wie im CSV_Pattern_Check des Imports. Mehrfach.
*   prepare(source, target)     liest source (die hochgeladene Datei), schreibt target
*                               (program/edit/.bank_inbox.csv). Gibt "true"/"false" (JSON)
*                               zurueck - fuer <param name="IF">.
*   accepted()                  dasselbe noch einmal, ohne neu zu rechnen
*
*   iter / moveFirst / col      EINE Zeile: status (angenommen|abgewiesen), format, kodierung,
*                               zeilen, nullen, meldung
*
* == Die Pruefungen ==
*
* 1. KOPFZEILE (vor allem anderen). Sie muss alle Spalten eines erlaubten Formats enthalten -
*    dieselbe Regel wie CSV_Pattern_Check (Teilmenge: array_diff). Passt keine, wird NICHTS
*    geschrieben, das Ziel bleibt unberuehrt.
*    ⚠ DATEV-Exporte haben KEINE Kopfzeile, die erste Zeile ist schon eine Buchung. Sie kuerzen
*      die Texte - der Verwendungszweck geht in die ID ein, das ergaebe doppelte Buchungen
*      (STW). Sie werden mit eigener Meldung abgewiesen.
* 2. BOM weg (Volksbank). Mit BOM hiesse die erste Spalte nicht "Bezeichnung Auftragskonto".
* 3. KODIERUNG am Inhalt: ist die Datei gueltiges UTF-8, wird sie nach Latin-1 gewandelt
*    (<add> mit Dateipfad liest eine CSV als ISO-8859-1 - eine UTF-8-Datei wuerde zweimal
*    gewandelt: "Ã¼"). Sonst bleibt sie. Eine reine ASCII-Datei ist in beiden gleich. Gemessen
*    an 26 Auszuegen: dieselbe Einteilung wie die Kontoliste des Skripts.
* 4. KONTONUMMERN (nur wo es die Spalte "Kontonummer/IBAN" gibt, Sparkasse): eine reine Nummer
*    ohne fuehrende Nullen, "0000000000" wird "0" - die Schreibweise des Bestands (sonst neue
*    ID, doppelte Buchung). Nur, wenn jede Zeile bytegleich durch Zerlegen und Zusammensetzen
*    geht (keine Anfuehrungszeichen) - sonst Abbruch, wie im Skript.
*
* ⚠ Nur Pflichtargumente: ein Remote-Parameter bleibt bis zum naechsten Setzen stehen.
* ⚠ target muss innerhalb der Installation liegen (dieselbe Regel wie File/Folder).
*/
require_once(__DIR__ . '/../plugin_interface.php');

class BankCsv extends plugin
{
	private $patterns = [];      // name => [spalten]
	private $result   = null;    // die eine Ergebniszeile
	private $pos      = 0;

	public function __construct() {}

	public function pattern($name, $header)
	{
		$spalten = array_map('trim', explode(';', (string)$header));
		$this->patterns[trim((string)$name)] = $spalten;
	}

	public function prepare($source, $target)
	{
		$this->result = $this->rechnen((string)$source, (string)$target);
		$this->melde($this->result['status'] . ': ' . $this->result['meldung'], 5);
		return json_encode($this->result['status'] === 'angenommen');
	}

	public function accepted()
	{
		return json_encode(is_array($this->result) && $this->result['status'] === 'angenommen');
	}

	public function &iter() { return $this; }
	public function set_list(&$value) {}
	public function moveFirst() { $this->pos = 0; return is_array($this->result); }
	public function next() { return false; }
	public function many() { return is_array($this->result) ? 1 : 0; }

	public function col($columnname)
	{
		if(!is_array($this->result)) return false;
		if(!array_key_exists($columnname, $this->result))
			throw new NotAFieldnameException('BankCsv: unknown column ' . $columnname);
		return $this->result[$columnname];
	}

	public function fields() { return ['status', 'format', 'kodierung', 'zeilen', 'nullen', 'meldung']; }

	public function datatype($columnname)
	{
		if($columnname === 'zeilen' || $columnname === 'nullen') return 'int(11)';
		return in_array($columnname, $this->fields(), true) ? 'varchar(255)' : false;
	}

	public function decription() { return "prueft und bereitet einen Kontoauszug (CSV) fuer den Import vor"; }

	/* ----------------------------------------------------------------- intern */

	private function melde(string $text, int $stufe): void
	{
		global $logger_class;
		if(is_object($logger_class)) $logger_class->setAssert('BankCsv: ' . $text, $stufe);
	}

	private static function ab(string $meldung, string $kodierung = '', int $zeilen = 0): array
	{
		return ['status' => 'abgewiesen', 'format' => '', 'kodierung' => $kodierung, 'zeilen' => $zeilen, 'nullen' => 0, 'meldung' => $meldung];
	}

	/** das Format, dessen Spalten alle in der Kopfzeile stehen; null = keins */
	public function format_of(array $kopf)
	{
		$kopf = array_map('trim', $kopf);
		foreach($this->patterns as $name => $spalten)
			if(count($spalten) > 0 && 0 === count(array_diff($spalten, $kopf))) return $name;
		return null;
	}

	/** sieht die erste Zeile nach einer Buchung aus (DATEV: BIC/IBAN in Anfuehrungszeichen, Datum, Betrag)? */
	public static function looks_like_datev(string $zeile): bool
	{
		return (bool)preg_match('/^"[A-Z0-9]{8,11}";"[A-Z]{2}\d{2}[A-Z0-9]+";/', $zeile)
		    || (bool)preg_match('/;\d{2}\.\d{2}\.\d{4};\d{2}\.\d{2}\.\d{4};-?\d+,\d{2};/', $zeile);
	}

	private function rechnen(string $source, string $target): array
	{
		if(count($this->patterns) === 0) return self::ab('Kein erlaubtes Format angegeben (pattern).');
		if($source === '' || !is_file($source) || !is_readable($source)) return self::ab('Die hochgeladene Datei ist nicht lesbar.');
		if(false === ($ziel = $this->inside($target))) return self::ab('Das Ziel liegt ausserhalb der Installation.');

		$roh = file_get_contents($source);
		if($roh === false || $roh === '') return self::ab('Die Datei ist leer.');

		// 2. BOM weg - vor der Kopfpruefung, sonst passt die erste Spalte nie
		if(substr($roh, 0, 3) === "\xEF\xBB\xBF") $roh = substr($roh, 3);

		// 3. Kodierung am Inhalt
		$utf8 = mb_check_encoding($roh, 'UTF-8');
		$kodierung = $utf8 ? 'UTF-8' : 'ISO-8859-1';
		if($utf8 && preg_match('/[\x80-\xFF]/', $roh))
		{
			$latin = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $roh);
			if($latin === false) return self::ab('Die Datei laesst sich nicht nach Latin-1 wandeln.', $kodierung);
			$roh = $latin;
		}

		$nl = (strpos($roh, "\r\n") !== false) ? "\r\n" : "\n";
		$zeilen = explode($nl, $roh);
		$ende = '';
		if(end($zeilen) === '') { array_pop($zeilen); $ende = $nl; }
		$anzahl = max(0, count($zeilen) - 1);

		// 1. Kopfzeile
		$kopf = str_getcsv($zeilen[0] ?? '', ';', '"', '');
		$format = $this->format_of($kopf);
		if($format === null)
		{
			if(self::looks_like_datev($zeilen[0] ?? ''))
				return self::ab('Keine Kopfzeile - vermutlich ein DATEV-Export. DATEV kuerzt die Texte, das ergaebe doppelte Buchungen. Bitte den CSV-Export der Bank nehmen.', $kodierung, $anzahl);
			return self::ab('Unbekanntes Format: die Kopfzeile passt zu keinem erlaubten Typ.', $kodierung, $anzahl);
		}

		// 4. Kontonummern ohne fuehrende Nullen (nur mit der Spalte Kontonummer/IBAN)
		$nullen = 0;
		$i = array_search('Kontonummer/IBAN', array_map('trim', $kopf), true);
		if($i !== false)
		{
			foreach($zeilen as $n => $zeile)
			{
				$felder = str_getcsv($zeile, ';', '"', '');
				if(implode(';', $felder) !== $zeile)
					return self::ab('Abbruch: Zeile ' . ($n + 1) . ' geht nicht bytegleich durch Zerlegen und Zusammensetzen - nichts angeglichen, nichts geschrieben.', $kodierung, $anzahl);
				if($n === 0) continue;
				if(isset($felder[$i]) && ctype_digit($felder[$i]) && $felder[$i][0] === '0' && $felder[$i] !== '0')
				{
					$felder[$i] = ltrim($felder[$i], '0');
					if($felder[$i] === '') $felder[$i] = '0';
					$zeilen[$n] = implode(';', $felder);
					$nullen++;
				}
			}
		}

		$neu = implode($nl, $zeilen) . $ende;
		if(!is_dir(dirname($ziel))) return self::ab('Das Zielverzeichnis fehlt.', $kodierung, $anzahl);
		if(false === file_put_contents($ziel, $neu)) return self::ab('Das Ziel ist nicht beschreibbar.', $kodierung, $anzahl);

		return ['status' => 'angenommen', 'format' => $format, 'kodierung' => $kodierung, 'zeilen' => $anzahl, 'nullen' => $nullen,
		        'meldung' => 'Format ' . $format . ', ' . $anzahl . ' Buchungen, ' . $kodierung . ($nullen ? ', ' . $nullen . ' Kontonummern angeglichen' : '') . '.'];
	}

	/* Wortgleich zu File::inside (ohne Meldungen): absolut innerhalb von ROOT_DIR, oder false. */
	private function inside($path)
	{
		$path = trim((string) $path);
		if('' === $path) return false;
		if(function_exists('resolve_path')) $path = resolve_path($path);

		$wurzel = realpath(defined('ROOT_DIR') ? ROOT_DIR : getcwd());
		if(false === $wurzel) return false;

		$roh   = ('/' === $path[0]) ? $path : $wurzel . '/' . $path;
		$teile = [];
		foreach(explode('/', $roh) as $teil)
		{
			if('' === $teil || '.' === $teil) continue;
			if('..' === $teil) { array_pop($teile); continue; }
			$teile[] = $teil;
		}
		$ziel = '/' . implode('/', $teile);
		$innen = fn($p) => $p === $wurzel || 0 === strpos($p, $wurzel . '/');

		$vorfahr = $ziel;
		while(!file_exists($vorfahr) && '/' !== $vorfahr) $vorfahr = dirname($vorfahr);
		$echt = realpath($vorfahr);

		if(!$innen($ziel) || false === $echt || !$innen($echt)) return false;
		return $ziel;
	}
}
?>
