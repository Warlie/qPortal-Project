<?PHP
/*@
   Folder legt Verzeichnisse an und sagt, ob es sie gibt.

   Absichtlich schlicht: keine Oberklasse, kein plugin_interface.php, keine
   Systemobjekte im Konstruktor.

title:: Folder
creator:: Stefan Wegerhoff, Claude

tricky::
   Gearbeitet wird nur unterhalb der Installation (ROOT_DIR). Ein Pfad, der
   hinausfuehrt - ueber '..' oder ueber einen Symlink -, wird abgewiesen und
   geloggt, nicht angelegt. Geprueft wird der tiefste VORHANDENE Vorfahr, weil
   es das Ziel meist noch nicht gibt.
@*/
class Folder
{
	/* Leer, aber noetig: der Lader baut fuer jede Plugin-Klasse einen Knoten
	*  Folder.__construct. Ohne Konstruktor scheitert das mit "Cannot instantiate
	*  constructor ... namespace node creation failed" (gemessen 2026-09-16). */
	function __construct()
	{
	}

	/*@
	function::
	   Legt das Verzeichnis an, fehlende Zwischenstufen eingeschlossen.

	function(lang=en)::
	   Creates the directory, including any missing intermediate levels.

	param:: path = das Verzeichnis; relativ ab ROOT_DIR, %ROOT_DIR% und
	   %PROGRAM_DIR% werden aufgeloest.

	delivers:: <http://www.w3.org/2001/XMLSchema#boolean>
	   true, wenn das Verzeichnis danach existiert - auch dann, wenn es schon
	   vorher da war. false, wenn der Pfad ausserhalb liegt, dort eine Datei
	   steht oder das Anlegen scheitert.
	@*/
	public function make($path)
	{
		if(false === ($ziel = $this->inside($path, 'make')))
			return false;

		if(is_dir($ziel))
			return true;

		if(file_exists($ziel))
		{
			$this->log('Folder.make: "' . $ziel . '" ist eine Datei, kein Verzeichnis', 0);
			return false;
		}

		$ok = @mkdir($ziel, 0775, true) && is_dir($ziel);

		$this->log('Folder.make: "' . $ziel . '" ' . ($ok ? 'angelegt' : 'NICHT angelegt'), $ok ? 5 : 0);

		return $ok;
	}

	/*@
	function:: Sagt, ob das Verzeichnis existiert.
	function(lang=en):: Tells whether the directory exists.

	param:: path = das Verzeichnis, wie bei make

	delivers:: <http://www.w3.org/2001/XMLSchema#boolean>
	   Ein Pfad ausserhalb der Installation existiert fuer dieses Plugin nicht.
	@*/
	public function exists($path)
	{
		if(false === ($ziel = $this->inside($path, 'exists')))
			return false;

		return is_dir($ziel);
	}

	/* Macht aus dem Pfad einen absoluten innerhalb von ROOT_DIR, oder false.
	*  Normalisiert '.' und '..' ohne das Dateisystem zu fragen - das Ziel gibt es ja
	*  meist noch nicht, realpath() wuerde scheitern. Gegen Symlinks wird danach der
	*  tiefste VORHANDENE Vorfahr mit realpath() geprueft: der muss innen liegen. */
	private function inside($path, $wer)
	{
		$path = trim((string) $path);

		if('' === $path)
		{
			$this->log('Folder.' . $wer . ': kein Pfad', 0);
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
			$this->log('Folder.' . $wer . ': "' . $path . '" liegt ausserhalb der Installation', 0);
			return false;
		}

		return $ziel;
	}

	private function log($text, $stufe)
	{
		global $logger_class;
		if(is_object($logger_class))
			$logger_class->setAssert($text . ' (PlugIn/folder/plugin_folder.php)', $stufe);
	}
}
?>
