<?PHP
/*@
   DbCheck sagt, was in der Datenbank steht - und aendert nichts.

   Ein Wert-Plugin wie File: keine Oberklasse, kein plugin_interface.php. Jeder
   Griff GIBT EINEN WERT ZURUECK, einen JSON-Text, und ist keine Ergebnismenge.
   Anders als File braucht es ein Systemobjekt im Konstruktor, die Datenbank -
   denselben Griff, den DBO bekommt.

   Entstanden am 2026-10-06 nach dem Nachladen der Bankumsaetze auf orga: jede
   Pruefung dort (Zeilenzahl, Primaerschluessel, Zeitraeume je Konto, kaputte
   Umlaute, Doppelte) lief per ssh mit eingebettetem PHP, am Intern-Weg vorbei,
   und einmal kam still nichts zurueck, weil die Quotierung brach.

   Bewusst neben DBO und nicht darin: DBO ist eine Ergebnismenge und schreibt;
   DbCheck ist ein Wert und liest nur. Wer es ruft, kann damit nichts aendern.

title:: DbCheck
creator:: Stefan Wegerhoff, Claude

tricky::
   ⚠ NUR LESEN. Jede Anweisung baut das Plugin selbst, und es sind nur SELECT
   und SHOW. Es gibt keinen Griff, der SQL des Aufrufers ausfuehrt - ein solcher
   machte die Datenbank mit einem einzigen Schluessel ueber HTTP schreibbar.

   ⚠ Namen kommen nie roh in eine Anweisung. Eine Tabelle muss so heissen, wie
   die Datenbank sie selbst nennt (information_schema, genau, mit Gross- und
   Kleinschreibung), eine Spalte so, wie SHOW COLUMNS sie meldet. Erst dann wird
   sie in Backticks gesetzt. Was nicht passt, wird geloggt und mit false
   beantwortet - die Antwort verraet nicht, ob es die Tabelle gibt.

   ⚠ Kaputte Umlaute (doppelt kodiertes UTF-8, "Ã¼" statt "ü") werden BINAER
   gesucht. Ein Vergleich in einer _ai_ci-Sortierung findet "Ã" auch in jedem
   "a" - gemessen am 2026-10-05: 623 statt 71.
@*/
class DbCheck
{
	private $db;

	function __construct(/* System.Database */ &$db)
	{
		$this->db = &$db;
	}

	/*@
	function::
	   Alle Tabellen der Datenbank mit Zeilenzahl und Primaerschluessel.

	function(lang=en)::
	   All tables of the database with row count and primary key.

	delivers:: <http://www.w3.org/2001/XMLSchema#string>
	   JSON-Text, eine Liste von {"name","rows","primary":[...]} nach Namen
	   sortiert. Eine leere "primary"-Liste heisst: kein Primaerschluessel -
	   dann kann DBO.saves_dataset_back dort nicht schreiben. false bei einem
	   Fehler.
	@*/
	public function tables()
	{
		if(false === ($namen = $this->table_names()))
			return false;

		$liste = [];
		foreach($namen as $t)
		{
			if(false === ($zeilen = $this->count_rows($t)))
				return false;
			$liste[] = ['name' => $t, 'rows' => $zeilen, 'primary' => $this->primary($t)];
		}

		return json_encode($liste, JSON_UNESCAPED_UNICODE);
	}

	/*@
	function::
	   Eine Tabelle im Einzelnen: Zeilen, Spalten, Indizes, Primaerschluessel.

	function(lang=en)::
	   One table in detail: rows, columns, indexes, primary key.

	param:: name = der Tabellenname, genau wie in der Datenbank

	delivers:: <http://www.w3.org/2001/XMLSchema#string>
	   JSON-Text {"name","rows","primary":[...],"columns":[{"name","type",
	   "null","key"}],"indexes":[{"name","column","unique"}]}. false, wenn es
	   die Tabelle nicht gibt.
	@*/
	public function table($name)
	{
		if(false === ($t = $this->known_table($name, 'table')))
			return false;

		$spalten = $this->query('SHOW COLUMNS FROM ' . $this->q($t), ['Field', 'Type', 'Null', 'Key']);
		$indizes = $this->query('SHOW INDEX FROM ' . $this->q($t), ['Key_name', 'Column_name', 'Non_unique']);
		if(false === $spalten || false === $indizes || false === ($zeilen = $this->count_rows($t)))
			return false;

		return json_encode([
			'name'    => $t,
			'rows'    => $zeilen,
			'primary' => $this->primary($t),
			'columns' => array_map(fn($s) => ['name' => $s['Field'], 'type' => $s['Type'],
			                                  'null' => $s['Null'], 'key' => $s['Key']], $spalten),
			'indexes' => array_map(fn($i) => ['name' => $i['Key_name'], 'column' => $i['Column_name'],
			                                  'unique' => '0' === (string) $i['Non_unique']], $indizes),
		], JSON_UNESCAPED_UNICODE);
	}

	/*@
	function::
	   Zaehlt doppelt kodierte Umlaute in allen Textspalten einer Tabelle.

	function(lang=en)::
	   Counts double-encoded umlauts in every text column of a table.

	param:: name = der Tabellenname, genau wie in der Datenbank

	delivers:: <http://www.w3.org/2001/XMLSchema#string>
	   JSON-Text {"name","rows","columns":{"spalte":anzahl,...}} - "rows" sind
	   die Zeilen mit mindestens einem Treffer, "columns" nur Spalten mit
	   Treffern. Gesucht wird nach "Ã" und "Â" in den Bytes; beides steht in
	   deutschem Text praktisch nie, in doppelt kodiertem fast immer. false,
	   wenn es die Tabelle nicht gibt.
	@*/
	public function mojibake($name)
	{
		if(false === ($t = $this->known_table($name, 'mojibake')))
			return false;

		if(false === ($spalten = $this->query('SHOW COLUMNS FROM ' . $this->q($t), ['Field', 'Type'])))
			return false;

		$text = array_values(array_filter($spalten,
			fn($s) => preg_match('/char|text|enum|set/i', $s['Type'])));

		if(!$text)
			return json_encode(['name' => $t, 'rows' => 0, 'columns' => new stdClass()]);

		$bedingung = fn($s) => '(INSTR(BINARY ' . $this->q($s['Field']) . ", BINARY 'Ã') > 0"
		                     . ' OR INSTR(BINARY ' . $this->q($s['Field']) . ", BINARY 'Â') > 0)";

		$felder = [];
		foreach($text as $i => $s)
			$felder[] = 'SUM(' . $bedingung($s) . ') AS c' . $i;
		$felder[] = 'SUM(' . implode(' OR ', array_map($bedingung, $text)) . ') AS gesamt';

		$z = $this->query('SELECT ' . implode(', ', $felder) . ' FROM ' . $this->q($t),
			array_merge(array_map(fn($i) => 'c' . $i, array_keys($text)), ['gesamt']));
		if(false === $z)
			return false;

		$je = [];
		foreach($text as $i => $s)
			if((int) $z[0]['c' . $i] > 0)
				$je[$s['Field']] = (int) $z[0]['c' . $i];

		return json_encode(['name' => $t, 'rows' => (int) $z[0]['gesamt'],
		                    'columns' => $je ?: new stdClass()], JSON_UNESCAPED_UNICODE);
	}

	/*@
	function::
	   Sucht Zeilen, die in den genannten Spalten gleich sind.

	function(lang=en)::
	   Finds rows that are equal in the given columns.

	param:: name = der Tabellenname, genau wie in der Datenbank
	param:: columns = Spaltennamen, durch Komma getrennt

	delivers:: <http://www.w3.org/2001/XMLSchema#string>
	   JSON-Text {"name","columns":[...],"groups","rows"} - "groups" ist die
	   Zahl der Gruppen mit mehr als einer Zeile, "rows" die Zeilen darin.
	   ⚠ Gleich heisst nicht falsch: zwei gleiche Gebuehren am selben Tag sind
	   zwei Buchungen. Die Zahl sagt, wo man hinsehen muss. false, wenn es die
	   Tabelle oder eine Spalte nicht gibt.
	@*/
	public function duplicates($name, $columns)
	{
		if(false === ($t = $this->known_table($name, 'duplicates')))
			return false;

		if(false === ($sp = $this->known_columns($t, $columns, 'duplicates')))
			return false;

		$gruppe = implode(', ', array_map([$this, 'q'], $sp));
		$z = $this->query('SELECT COUNT(*) AS g, COALESCE(SUM(n), 0) AS r FROM (SELECT COUNT(*) AS n FROM '
			. $this->q($t) . ' GROUP BY ' . $gruppe . ' HAVING COUNT(*) > 1) AS x', ['g', 'r']);
		if(false === $z)
			return false;

		return json_encode(['name' => $t, 'columns' => $sp,
		                    'groups' => (int) $z[0]['g'], 'rows' => (int) $z[0]['r']], JSON_UNESCAPED_UNICODE);
	}

	/*@
	function::
	   Je Wert einer Spalte: Zeilenzahl und Zeitraum. Etwa je Konto, von wann bis
	   wann Buchungen da sind.

	function(lang=en)::
	   Per value of one column: row count and time span.

	param:: name = der Tabellenname, genau wie in der Datenbank
	param:: group = die Spalte, nach der gruppiert wird
	param:: date = die Spalte mit dem Datum

	delivers:: <http://www.w3.org/2001/XMLSchema#string>
	   JSON-Text, eine Liste von {"group","rows","from","to"}, nach "group"
	   sortiert. false, wenn es die Tabelle oder eine Spalte nicht gibt.
	@*/
	public function range($name, $group, $date)
	{
		if(false === ($t = $this->known_table($name, 'range')))
			return false;

		if(false === ($sp = $this->known_columns($t, $group . ',' . $date, 'range')) || 2 !== count($sp))
			return false;

		[$g, $d] = array_map([$this, 'q'], $sp);
		$z = $this->query('SELECT ' . $g . ' AS g, COUNT(*) AS n, MIN(' . $d . ') AS von, MAX(' . $d . ') AS bis FROM '
			. $this->q($t) . ' GROUP BY ' . $g . ' ORDER BY ' . $g, ['g', 'n', 'von', 'bis']);
		if(false === $z)
			return false;

		return json_encode(array_map(fn($r) => ['group' => $r['g'], 'rows' => (int) $r['n'],
		                                        'from' => $r['von'], 'to' => $r['bis']], $z), JSON_UNESCAPED_UNICODE);
	}

	/* ---------------------------------------------------------------- intern */

	/* Die Tabellen, wie die Datenbank sie nennt. */
	private function table_names()
	{
		$z = $this->query('SELECT TABLE_NAME AS t FROM information_schema.TABLES'
			. ' WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME', ['t']);
		return false === $z ? false : array_column($z, 't');
	}

	/* Der Name, wenn es die Tabelle gibt - genau verglichen. Sonst false. */
	private function known_table($name, $wer)
	{
		$name = trim((string) $name);
		if(false === ($namen = $this->table_names()))
			return false;

		if('' === $name || !in_array($name, $namen, true))
		{
			$this->log('DbCheck.' . $wer . ': keine Tabelle "' . $name . '"', 0);
			return false;
		}
		return $name;
	}

	/* Die genannten Spalten in ihrer Reihenfolge, wenn es alle gibt. Sonst false. */
	private function known_columns($t, $columns, $wer)
	{
		$gibt = $this->query('SHOW COLUMNS FROM ' . $this->q($t), ['Field']);
		if(false === $gibt)
			return false;
		$gibt = array_column($gibt, 'Field');

		$sp = array_values(array_filter(array_map('trim', explode(',', (string) $columns)), 'strlen'));
		foreach($sp as $s)
			if(!in_array($s, $gibt, true))
			{
				$this->log('DbCheck.' . $wer . ': keine Spalte "' . $s . '" in ' . $t, 0);
				return false;
			}

		if(!$sp)
		{
			$this->log('DbCheck.' . $wer . ': keine Spalten genannt', 0);
			return false;
		}
		return $sp;
	}

	private function primary($t)
	{
		$z = $this->query('SHOW INDEX FROM ' . $this->q($t), ['Key_name', 'Column_name']);
		if(false === $z)
			return [];
		return array_values(array_map(fn($i) => $i['Column_name'],
			array_filter($z, fn($i) => 'PRIMARY' === $i['Key_name'])));
	}

	private function count_rows($t)
	{
		$z = $this->query('SELECT COUNT(*) AS n FROM ' . $this->q($t), ['n']);
		return false === $z ? false : (int) $z[0]['n'];
	}

	/* Ein Name in Backticks. Nur fuer Namen, die vorher geprueft wurden. */
	private function q($ident)
	{
		return '`' . str_replace('`', '``', $ident) . '`';
	}

	/* Eine Anweisung, die Zeilen liefert, als Liste von Arrays mit den genannten
	*  Spalten. Ueber System.Database::SQL() - dieselbe Verbindung, dasselbe Log.
	*  ⚠ SQL() faengt einen Fehler selbst ab und laesst dann das VORIGE, schon
	*  freigegebene Ergebnis stehen; darum wird errno() gefragt, bevor gelesen wird. */
	private function query($sql, $spalten)
	{
		try
		{
			$this->db->SQL($sql);
			if(0 != $this->db->errno())
			{
				$this->log('DbCheck: Anweisung gescheitert: ' . $sql, 0);
				return false;
			}

			$zeilen = [];
			for($i = 0, $n = $this->db->sEffectNum(); $i < $n; $i++)
			{
				$z = [];
				foreach($spalten as $s)
					$z[$s] = $this->db->sResult($i, 'x.' . $s);
				$zeilen[] = $z;
			}
			return $zeilen;
		}
		catch(Throwable $e)
		{
			$this->log('DbCheck: ' . $e->getMessage() . ' bei ' . $sql, 0);
			return false;
		}
	}

	private function log($text, $stufe)
	{
		global $logger_class;
		if(is_object($logger_class))
			$logger_class->setAssert($text . ' (PlugIn/db_check/plugin_db_check.php)', $stufe);
	}
}
?>
