<?PHP

/**
*	once — ein Ausfuehrungsbereich, der im Leben der Installation HOECHSTENS EINMAL laeuft.
*
*	Geschwister von first und final, und er laeuft VOR first (STW). Die Reihenfolge ist
*	die Aussage: once ist die EINRICHTUNG, first sind die VORAUSSETZUNGEN jedes Laufs.
*	Was einmal eingerichtet wurde, steht, wenn first sich darauf stuetzt.
*
*	    <indextree>
*	        <once name="fridge;contains_v1"> … genau einmal … </once>
*	        <first> … vor jedem Lauf … </first>
*	        <final name="…"> … </final>
*	    </indextree>
*
*	Dieselbe Bauform wie TREE_first: ein tree, der sich nach getaner Arbeit von der Kante
*	am indextree ABMELDET. Nur haelt die Marke hier nicht bis zum Ende des Requests,
*	sondern in einer Tabelle.
*
*	== Wofuer, und wofuer NICHT ==
*
*	⚠ NICHT fuer CREATE TABLE. Gemessen auf MariaDB 12.3.3: CREATE TABLE IF NOT EXISTS
*	und ALTER TABLE ... ADD COLUMN IF NOT EXISTS sind beide idempotent (zweiter Lauf =
*	eine Warnung, kein Fehler). Die Datenbank beantwortet die Frage selbst, und zwar als
*	einzige Instanz, die es wirklich weiss. Ein Register daneben waere eine ZWEITE
*	WAHRHEIT: wer die Tabelle von Hand loescht, hat ein Register, das "erledigt" sagt,
*	und eine Tabelle, die nie wiederkommt.
*
*	Dieses hier ist fuer das, was die Datenbank NICHT idempotent machen kann:
*	Wirkungen ausserhalb von ihr (eine Datei anlegen, eine Gegenstelle rufen, eine Mail
*	schicken) und Datenumformungen, die kein zweites Mal vertragen.
*
*	== Der Schluessel ==
*
*	sha256(Dokument + '#' + tree:name) (STW). Das Dokument steckt mit drin, damit
*	derselbe Name in zwei Dokumenten zwei Eintraege sind; gehasht, weil ein Dokumentpfad
*	laenger werden kann, als ein Indexschluessel traegt. `path` und `name` stehen
*	zusaetzlich im Klartext daneben — ein Register, das man nicht lesen kann, ist beim
*	Suchen wertlos. (Das `name` daneben ist meine Zutat, nicht in STWs Aufzaehlung.)
*
*	⚠ Der Name ist PFLICHT. Ohne ihn gaebe es keinen stabilen Schluessel, und "einmal"
*	waere eine Aussage ueber nichts. Ein once ohne Namen laeuft NICHT und sagt es im Log.
*
*	== Warum ein bool NEBEN der Zeile ==
*
*	Die Zeile wird VOR dem Lauf geschrieben (done = 0) und danach auf 1 gesetzt. Erst
*	dadurch traegt das bool ueberhaupt etwas: waere "Zeile da" gleich "erledigt", koennte
*	man es weglassen. So unterscheidet das Register drei Zustaende:
*
*	    keine Zeile        noch nie gesehen
*	    Zeile, done = 0    ANGEFANGEN und nicht fertig geworden — abgestuerzt, oder von
*	                       Hand stillgelegt
*	    Zeile, done = 1    durch
*
*	⚠ OFFEN (STW): was bei done = 0 geschehen soll. Hier laeuft es ERNEUT und sagt es
*	laut im Log — "nie fertig geworden" liegt naeher an "nicht erledigt" als an
*	"erledigt". Wer eine Wirkung hat, die auch halb schon schadet, will das andersherum.
*	Eine Zeile, ein Vorzeichen.
*
*	== Wann geprueft wird ==
*
*	⚠ OFFEN (STW: "Ab wann man die Existenz ueberprueft, weiss ich noch nicht").
*	Hier: beim START, im event_message_in, unmittelbar bevor gelaufen wuerde — der
*	einzige Zeitpunkt, an dem feststeht, dass es ueberhaupt losginge. Die Alternative
*	waere das Parsen (event_initiated), das kostet aber einen Datenbankweg je once-Knoten
*	in JEDEM Dokument, das geladen wird, auch wenn niemand es startet.
*
*	Die TABELLE wird hoechstens einmal je Request angelegt (statische Marke unten), nicht
*	je Knoten.
*/

class TREE_once extends TREE_tree
{
	/** Einmal je Request: die Tabelle steht. Nicht je Knoten. */
	private static $tabelle_steht = false;

	/** Der Registername. Faellt der weg, faellt der Schluessel weg. */
	const TABELLE = 'qp_once';

function &get_Instance()
{
return new TREE_once();
}

	/**
	*	Legt das Register an, falls es fehlt — idempotent und still.
	*
	*	⚠ Kein ENGINE, kein CHARSET, wie qp_cmd_ensure_table in behavior/stored.php:
	*	die Vorgabe der Datenbank soll gelten. hash als CHAR(64) ist der sha256 in hex.
	*/
	private static function tabelle_sichern($db): void
	{
		if(self::$tabelle_steht) return;

		$db->SQL(
			'CREATE TABLE IF NOT EXISTS ' . self::TABELLE . ' ('
			. ' hash CHAR(64) NOT NULL,'
			. ' path VARCHAR(190) NOT NULL DEFAULT \'\','
			. ' name VARCHAR(190) NOT NULL DEFAULT \'\','
			. ' done TINYINT(1) NOT NULL DEFAULT 0,'
			. ' stamp DATETIME NULL,'
			. ' PRIMARY KEY (hash) )'
		);

		self::$tabelle_steht = true;
	}

	/** Das Dokument, in dem dieser Knoten steht — die eine Haelfte des Schluessels. */
	private function dokument(): string
	{
		$parser = $this->get_parser();

		if(!is_object($parser)) return '';

		return (string) $parser->indexToUri($this->get_idx());
	}

function event_message_in($type,&$obj)
	{
		global $logger_class;

		$com = ($type instanceof Command_Object) ? $type : $this->parseCommand($type);

		/* Nur ein start ist ein Lauf — alles andere geht weiter wie bei jedem tree. */
		if(!$com->matchesCommand('start'))
			return parent::event_message_in($type, $obj);

		$name = $this->get_ns_attribute('http://www.trscript.de/tree#name');
		$name = (false === $name) ? '' : trim((string) $name);

		if('' === $name)
		{
			if($logger_class) $logger_class->setAssert(
				'once ohne tree:name in ' . $this->dokument()
				. ' - ohne Namen gibt es keinen Schluessel, nicht gelaufen', 0);
			return true;
		}

		$cg = $this->get_contentGen();
		$db = is_object($cg) ? $cg->getSQLObj() : null;

		if(!is_object($db))
		{
			if($logger_class) $logger_class->setAssert(
				'once "' . $name . '": keine Datenbank am ContentGenerator, nicht gelaufen', 0);
			return true;
		}

		$pfad = $this->dokument();
		$hash = hash('sha256', $pfad . '#' . $name);

		self::tabelle_sichern($db);

		$stand = $this->stand_lesen($db, $hash);

		if(1 === $stand)
		{
			if($logger_class) $logger_class->setAssert(
				'once "' . $name . '": steht im Register als erledigt, nicht gelaufen', 5);

			/* Innerhalb dieses Requests braucht uns niemand mehr zu fragen. Das Register
			*  bleibt die Wahrheit; das hier spart nur den zweiten Datenbankweg.
			*  ⚠ Mit der URI — to_listener in TREE_tree::event_initiated traegt am indextree
			*  ein, nicht bei prev_el. */
			$this->remove_listener('http://www.trscript.de/tree#indextree');

			return true;
		}

		if(0 === $stand)
		{
			/* ⚠ Klammern sind hier Pflicht: ohne sie binde das else an das innere
			*  if($logger_class), und eine fehlende Zeile bekaeme nie ihren Eintrag. */
			if($logger_class) $logger_class->setAssert(
				'once "' . $name . '": Zeile steht auf done=0 - ein frueherer Lauf wurde nicht'
				. ' fertig. Laeuft ERNEUT (' . $pfad . ')', 0);
		}
		else
		{
			$this->eintragen($db, $hash, $pfad, $name);
		}

		$ergebnis = parent::event_message_in($type, $obj);

		/* Erst NACH dem Lauf. Wirft der Inhalt, bleibt done auf 0 stehen und die Zeile
		*  erzaehlt, dass hier etwas angefangen und nicht beendet wurde. */
		$this->abhaken($db, $hash);
		$this->remove_listener('http://www.trscript.de/tree#indextree');

		return $ergebnis;
	}

	/**
	*	1 = erledigt, 0 = angefangen, null = keine Zeile.
	*
	*	⚠ NICHT ueber get_rst(). Das ist der Apparat fuer eine Ergebnismenge, die ein
	*	Dokument weiterverarbeitet — es parst die Anweisung nach Tabellennamen, baut ein
	*	Feldverzeichnis und haengt an einer Tabelle ohne Primaerschluessel einen
	*	READONLY-rst dahinter. Fuer eine Zahl ist das der falsche Weg und er lieferte hier
	*	still null, obwohl die Zeile stand (gemessen). Gelesen wird wie in
	*	behavior/stored.php: SQL() und die rohe mysqli-Zeile daneben.
	*/
	private function stand_lesen($db, string $hash)
	{
		$db->SQL('SELECT done FROM ' . self::TABELLE
			. ' WHERE hash = \'' . $db->escape($hash) . '\'');

		$row = is_object($db->table_db) ? $db->table_db->fetch_assoc() : null;

		return is_null($row) ? null : intval($row['done']);
	}

	private function eintragen($db, string $hash, string $pfad, string $name): void
	{
		$db->SQL('INSERT INTO ' . self::TABELLE . ' (hash, path, name, done, stamp) VALUES ('
			. '\'' . $db->escape($hash) . '\','
			. '\'' . $db->escape(substr($pfad, 0, 190)) . '\','
			. '\'' . $db->escape(substr($name, 0, 190)) . '\','
			. '0, NOW() )');
	}

	private function abhaken($db, string $hash): void
	{
		$db->SQL('UPDATE ' . self::TABELLE . ' SET done = 1, stamp = NOW()'
			. ' WHERE hash = \'' . $db->escape($hash) . '\'');
	}
}

?>
