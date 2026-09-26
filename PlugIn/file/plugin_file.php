<?PHP
/*@
   File schreibt uebergebene Inhalte in Dateien und sagt, was dort liegt.

   Das Gegenstueck zu Folder, und wie Folder absichtlich schlicht: keine
   Oberklasse, kein plugin_interface.php, keine Systemobjekte im Konstruktor.
   Dieser Plugin-Typ GIBT EINEN WERT ZURUECK - Wahrheitswert, Hash, JSON-Text -
   und ist keine Ergebnismenge. Wer moveFirst()/col()/next() braucht, erbt von
   plugin; hier waere das eine Luege.

title:: File
creator:: Stefan Wegerhoff, Claude

tricky::
   Gearbeitet wird nur unterhalb der Installation (ROOT_DIR), geprueft mit
   derselben Regel wie in Folder: '..' und Symlinks nach draussen werden
   abgewiesen und geloggt, nicht geschrieben.

   ⚠ Es gibt BEWUSST keinen Lesegriff, der einen Dateiinhalt zurueckgibt.
   Unterhalb von ROOT_DIR liegt auch config/config.ini mit Passwort und
   Intern-Token; ein solcher Griff waere der bequemste Weg, sie herauszutragen.
   hash() und dir() reichen zum Abgleichen, ohne Inhalt preiszugeben.

   ⚠ put() schreibt erst in eine Nachbardatei und benennt sie dann um. Ein
   abgebrochener Schreibvorgang laesst damit die alte Datei stehen, statt eine
   halbe zu hinterlassen - wichtig, weil hier Dokumente ankommen, die das
   System im selben Augenblick laden koennte.

   ⚠ Der Inhalt kommt base64-kodiert, weil er durch JSON reist. Dekodiert wird
   streng (base64_decode mit $strict): was keine gueltige Kodierung ist, wird
   abgewiesen, nicht halb geschrieben. Nur mit encoding=text nimmt es den Text,
   wie er kommt - fuer Handarbeit im Dokument, nicht fuer die Fernwartung.
@*/
class File
{
	/* Leer, aber noetig: der Lader baut fuer jede Plugin-Klasse einen Knoten
	*  File.__construct. Ohne Konstruktor scheitert das mit "Cannot instantiate
	*  constructor ... namespace node creation failed" (siehe Folder). */
	function __construct()
	{
	}

	/*@
	function::
	   Schreibt den Inhalt in die Datei und ersetzt, was dort stand. Fehlende
	   Verzeichnisse werden angelegt.

	function(lang=en)::
	   Writes the content to the file, replacing what was there. Missing
	   directories are created.

	param:: path = die Datei; relativ ab ROOT_DIR, %ROOT_DIR% und
	   %PROGRAM_DIR% werden aufgeloest.
	param:: content = der Inhalt, base64-kodiert.
	param:: encoding = "base64" (Vorgabe) oder "text" fuer unkodierten Inhalt.

	delivers:: <http://www.w3.org/2001/XMLSchema#string>
	   Der sha256 des Geschriebenen - damit der Aufrufer vergleichen kann, ohne
	   noch einmal zu fragen. false, wenn der Pfad ausserhalb liegt, die
	   Kodierung nicht stimmt oder das Schreiben scheitert.
	@*/
	public function put($path, $content, $encoding)
	{
		if(false === ($ziel = $this->inside($path, 'put')))
			return false;

		if(false === ($roh = $this->dekodiert($content, $encoding, 'put')))
			return false;

		if(!$this->verzeichnis_da($ziel, 'put'))
			return false;

		/* Erst daneben, dann umbenennen - eine halbe Datei waere schlimmer als
		*  eine alte. rename() ist auf demselben Dateisystem unteilbar. */
		$neben = $ziel . '.teil-' . bin2hex(random_bytes(4));

		if(false === @file_put_contents($neben, $roh) || !@rename($neben, $ziel))
		{
			@unlink($neben);
			$this->log('File.put: "' . $ziel . '" NICHT geschrieben', 0);
			return false;
		}

		$hash = hash('sha256', $roh);
		$this->log('File.put: "' . $ziel . '" ' . strlen($roh) . ' Bytes, sha256 ' . $hash, 5);

		return $hash;
	}

	/*@
	function::
	   Haengt den Inhalt an die Datei an. Der Weg fuer grosse Dateien: das erste
	   Stueck mit put, jedes weitere mit append.

	function(lang=en)::
	   Appends the content to the file. The way for large files: the first chunk
	   with put, every further one with append.

	param:: path = die Datei, wie bei put
	param:: content = das Stueck, base64-kodiert
	param:: encoding = "base64" (Vorgabe) oder "text"

	delivers:: <http://www.w3.org/2001/XMLSchema#integer>
	   Die Groesse der Datei NACH dem Anhaengen - daran sieht der Aufrufer, wo
	   er steht, wenn eine Uebertragung abbricht. false bei einem Fehler.
	@*/
	public function append($path, $content, $encoding)
	{
		if(false === ($ziel = $this->inside($path, 'append')))
			return false;

		if(false === ($roh = $this->dekodiert($content, $encoding, 'append')))
			return false;

		if(!is_file($ziel))
		{
			$this->log('File.append: "' . $ziel . '" gibt es nicht - das erste Stueck gehoert in put', 0);
			return false;
		}

		if(false === @file_put_contents($ziel, $roh, FILE_APPEND))
		{
			$this->log('File.append: "' . $ziel . '" NICHT angehaengt', 0);
			return false;
		}

		clearstatcache(true, $ziel);
		$gross = filesize($ziel);

		$this->log('File.append: "' . $ziel . '" + ' . strlen($roh) . ' Bytes, jetzt ' . $gross, 5);

		return $gross;
	}

	/*@
	function:: Der sha256 der Datei.
	function(lang=en):: The sha256 of the file.

	param:: path = die Datei, wie bei put

	delivers:: <http://www.w3.org/2001/XMLSchema#string>
	   false, wenn es die Datei nicht gibt oder sie ausserhalb liegt. Damit ist
	   "fehlt" von "ist anders" unterscheidbar.
	@*/
	public function hash($path)
	{
		if(false === ($ziel = $this->inside($path, 'hash')))
			return false;

		if(!is_file($ziel))
			return false;

		return hash_file('sha256', $ziel);
	}

	/*@
	function:: Sagt, ob die Datei existiert.
	function(lang=en):: Tells whether the file exists.

	param:: path = die Datei, wie bei put

	delivers:: <http://www.w3.org/2001/XMLSchema#boolean>
	   Ein Pfad ausserhalb der Installation existiert fuer dieses Plugin nicht.
	   Ein Verzeichnis ist keine Datei - dafuer gibt es Folder.exists.
	@*/
	public function exists($path)
	{
		if(false === ($ziel = $this->inside($path, 'exists')))
			return false;

		return is_file($ziel);
	}

	/*@
	function::
	   Listet ein Verzeichnis: je Eintrag Name, Groesse und sha256. Damit kann
	   ein Aufrufer vergleichen, was oben liegt, und nur das Abweichende
	   schicken.

	function(lang=en)::
	   Lists a directory: name, size and sha256 per entry.

	param:: path = das Verzeichnis; relativ ab ROOT_DIR
	param:: depth = 1 (Vorgabe) nur dieses Verzeichnis, -1 auch alle darunter

	delivers:: <http://www.w3.org/2001/XMLSchema#string>
	   JSON-Text, eine Liste von {"name","size","hash"}, nach Namen sortiert;
	   ein Verzeichnis erscheint als {"name","dir":true}. ⚠ Kein rst: der
	   Aufrufer bekommt EINEN Wert, den er weitergeben oder auslesen kann.
	   false, wenn der Pfad ausserhalb liegt oder kein Verzeichnis ist.
	@*/
	public function dir($path, $depth)
	{
		if(false === ($wurzel = $this->inside($path, 'dir')))
			return false;

		if(!is_dir($wurzel))
		{
			$this->log('File.dir: "' . $wurzel . '" ist kein Verzeichnis', 0);
			return false;
		}

		$tief  = (intval($depth) === -1);
		$liste = $this->sammeln($wurzel, $wurzel, $tief);

		usort($liste, fn($a, $b) => strcmp($a['name'], $b['name']));

		$this->log('File.dir: "' . $wurzel . '" ' . count($liste) . ' Eintraege'
			. ($tief ? ' (mit allen Ebenen)' : ''), 5);

		return json_encode($liste, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	/* --------------------------------------------------------------- innendrin */

	/* Namen relativ zur angefragten Wurzel, damit die Liste dasselbe sagt wie der
	*  Aufrufer gefragt hat - nicht den absoluten Pfad des Servers. */
	private function sammeln($wurzel, $hier, $tief)
	{
		$raus = array();

		if(false === ($eintraege = @scandir($hier)))
			return $raus;

		foreach($eintraege as $name)
		{
			if('.' === $name || '..' === $name) continue;

			$voll = $hier . '/' . $name;
			$kurz = ltrim(substr($voll, strlen($wurzel)), '/');

			if(is_link($voll))
				continue;                      /* wie Folder: nichts hinter einem Symlink */

			if(is_dir($voll))
			{
				$raus[] = array('name' => $kurz, 'dir' => true);

				if($tief)
					$raus = array_merge($raus, $this->sammeln($wurzel, $voll, true));

				continue;
			}

			$raus[] = array('name' => $kurz,
			                'size' => filesize($voll),
			                'hash' => hash_file('sha256', $voll));
		}

		return $raus;
	}

	/* base64 ist die Vorgabe, weil der Inhalt durch JSON reist. Streng dekodiert:
	*  was keine gueltige Kodierung ist, wird abgewiesen - halb geschriebene
	*  Dokumente sind schlimmer als ein Fehler. */
	private function dekodiert($content, $encoding, $wer)
	{
		$encoding = strtolower(trim((string) $encoding));
		if('' === $encoding) $encoding = 'base64';

		if('text' === $encoding)
			return (string) $content;

		if('base64' !== $encoding)
		{
			$this->log('File.' . $wer . ': unbekanntes encoding "' . $encoding . '"', 0);
			return false;
		}

		$roh = base64_decode((string) $content, true);

		if(false === $roh)
		{
			$this->log('File.' . $wer . ': der Inhalt ist kein gueltiges base64', 0);
			return false;
		}

		return $roh;
	}

	/* Das Verzeichnis der Zieldatei anlegen, wenn es fehlt. Dieselbe Regel wie
	*  Folder.make - der Pfad wurde von inside() schon als innen liegend erkannt. */
	private function verzeichnis_da($ziel, $wer)
	{
		$ordner = dirname($ziel);

		if(is_dir($ordner))
			return true;

		if(file_exists($ordner))
		{
			$this->log('File.' . $wer . ': "' . $ordner . '" ist eine Datei, kein Verzeichnis', 0);
			return false;
		}

		$ok = @mkdir($ordner, 0775, true) && is_dir($ordner);

		$this->log('File.' . $wer . ': Verzeichnis "' . $ordner . '" '
			. ($ok ? 'angelegt' : 'NICHT angelegt'), $ok ? 5 : 0);

		return $ok;
	}

	/* Macht aus dem Pfad einen absoluten innerhalb von ROOT_DIR, oder false.
	*  Wortgleich zu Folder::inside - dieselbe Regel, damit beide Plugins nicht
	*  auseinanderlaufen: '.' und '..' ohne das Dateisystem aufloesen (das Ziel
	*  gibt es meist noch nicht), danach den tiefsten VORHANDENEN Vorfahr mit
	*  realpath() gegen Symlinks nach draussen pruefen. */
	private function inside($path, $wer)
	{
		$path = trim((string) $path);

		if('' === $path)
		{
			$this->log('File.' . $wer . ': kein Pfad', 0);
			return false;
		}

		if(function_exists('resolve_path'))
			$path = resolve_path($path);

		$wurzel = realpath(defined('ROOT_DIR') ? ROOT_DIR : getcwd());
		if(false === $wurzel)
			return false;

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
		while(!file_exists($vorfahr) && '/' !== $vorfahr)
			$vorfahr = dirname($vorfahr);

		$echt = realpath($vorfahr);

		if(!$innen($ziel) || false === $echt || !$innen($echt))
		{
			$this->log('File.' . $wer . ': "' . $path . '" liegt ausserhalb der Installation', 0);
			return false;
		}

		return $ziel;
	}

	private function log($text, $stufe)
	{
		global $logger_class;
		if(is_object($logger_class))
			$logger_class->setAssert($text . ' (PlugIn/file/plugin_file.php)', $stufe);
	}
}
?>
