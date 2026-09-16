<?PHP
/*
* manifest.php - der Dokumentscanner (STW 2026-09-16).
*
*   __manifest  scannt ganze Verzeichnisse und legt ein MANIFEST ab: je Dokument eine
*               Zeile mit Hash und dem, was es tut, je (Dokument, Plugin) eine Zeile.
*
* Zwei getrennte Aufrufe (STW): dieser hier bereitet vor, gelesen wird das Manifest in
* einem ZWEITEN Aufruf (__load + __query). Die gescannten Dateien liegen danach in diesem
* Parser, und beim Parsen praegt jedes Dokument Namen in die globale Bedeutung (rdf:ID,
* owl:Ontology, tree:name) - der Lese-Aufruf soll nur das Manifest sehen.
*
* Alles auf dem System gilt als vertrauenswuerdig (STW). Die Frage nach fremden Dokumenten
* gehoert zur Sammlung, nicht hierher. Gemessen 2026-09-16 trotzdem: Laden fuehrt NICHTS aus -
* object, program und access melden sich beim Parsen nur als Zuhoerer an, ein IF laeuft erst
* auf eine Nachricht (Gegenprobe: laden 0, ausfuehren 1 "if Statement for eval"). Der Scanner
* benutzt darum den qPortal-Parser und sieht die Dokumente so, wie das System sie sieht.
*
* Das Manifest ist eine MESSUNG, kein Urteil: es zaehlt und setzt Merkmale (ja/nein). Die
* Bewertung daraus ist eine eigene Entscheidung.
*
* ⚠ Alle Aussagen stehen als ATTRIBUT - Kindknoten sind fuer SPARQL keine Tripel (Befund 2).
*/

if (!defined('QP_MANIFEST_NS'))
{
	define('QP_MANIFEST_NS', 'http://www.trscript.de/2026/manifest');

	/* Schreibende Plugin-Aufrufe, v1 von Hand gefuehrt - bis Plugins ihre Wirkung selbst
	*  erklaeren (effect::). */
	define('QP_MANIFEST_WRITES', ['DBO.freeSQL', 'DBO.execute_no_result', 'Folder.make']);

	/* HTML steht in tree-Dokumenten oft OHNE eigenen Namensraum und landet so im tree-
	*  Namensraum. Das ist Inhalt, kein Tippfehler - gezaehlt wird es getrennt (man:html).
	*  Gemessen: constructpage_all.xml trug 113 "nicht registrierte" Tags, alle HTML. */
	define('QP_MANIFEST_HTML', ['a','abbr','address','area','article','aside','audio','b','base',
		'blockquote','body','br','button','canvas','caption','cite','code','col','colgroup','dd',
		'del','details','dfn','dialog','div','dl','dt','em','embed','fieldset','figcaption','figure',
		'footer','form','h1','h2','h3','h4','h5','h6','head','header','hr','html','i','iframe','img',
		'input','ins','kbd','label','legend','li','link','main','map','mark','meta','nav','noscript',
		'ol','optgroup','option','output','p','picture','pre','progress','q','s','samp','script',
		'section','select','small','source','span','strong','style','sub','summary','sup','svg',
		'table','tbody','td','template','textarea','tfoot','th','thead','time','title','tr','u','ul',
		'var','video','center','font','big','tt','frame','frameset','noframes','applet','marquee']);

	/* Befehle, die im Text eines <access> (einer Befehlskette) schreiben. */
	define('QP_MANIFEST_WRITE_CMDS', ['__save_back', '__set_cmd', '__remove_cmd', '__add_node',
		'__set_attribute', '__remove_attribute', '__remove_node', '__set_data', '__insert_data']);
}

if (!function_exists('qp_manifest_inside'))
{
	/* Absoluter Pfad innerhalb von ROOT_DIR oder false - wie Folder.inside: '.' und '..'
	*  ohne Dateisystem normalisieren, dann den tiefsten vorhandenen Vorfahren mit realpath. */
	function qp_manifest_inside(string $pfad)
	{
		$wurzel = realpath(ROOT_DIR);
		$pfad   = trim(resolve_path($pfad));
		if('' === $pfad || false === $wurzel) return false;

		$roh   = ('/' === $pfad[0]) ? $pfad : $wurzel . '/' . $pfad;
		$teile = [];
		foreach(explode('/', $roh) as $t)
		{
			if('' === $t || '.' === $t) continue;
			if('..' === $t) { array_pop($teile); continue; }
			$teile[] = $t;
		}
		$ziel = '/' . implode('/', $teile);

		$innen   = fn($p) => $p === $wurzel || 0 === strpos($p, $wurzel . '/');
		$vorfahr = $ziel;
		while(!file_exists($vorfahr) && '/' !== $vorfahr) $vorfahr = dirname($vorfahr);
		$echt = realpath($vorfahr);

		return ($innen($ziel) && false !== $echt && $innen($echt)) ? $ziel : false;
	}

	/* Pfad relativ zu ROOT_DIR - so steht er im Manifest, unabhaengig von der Maschine. */
	function qp_manifest_rel(string $abs) : string
	{
		$wurzel = realpath(ROOT_DIR) . '/';
		return (0 === strpos($abs, $wurzel)) ? substr($abs, strlen($wurzel)) : $abs;
	}

	function qp_manifest_attr(string $v) : string
	{
		return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
	}

	/* Ein Durchlauf ueber die Elemente eines geladenen Baums. Liest Attribute; Text nur bei
	*  tree:access (dort ist er eine Befehlskette) - getdata() auf beliebigen Knoten koennte
	*  bei PEDL-Funktionsknoten etwas ausloesen (Phantom set_list, 2026-06-19). */
	function qp_manifest_walk($knoten, array &$z, int $tiefe = 0, array $objekt = ['id' => '', 'name' => ''])
	{
		if(!is_object($knoten) || $tiefe > 200) return;

		$T   = 'http://www.trscript.de/tree#';
		$uri = $knoten->full_URI();
		$a   = (array) $knoten->get_ns_attribute();

		switch($uri)
		{
			case $T . 'tree':
			case $T . 'final':
				$z['signs']++;
				$src = (string) ($a[$T . 'src'] ?? '');
				if(preg_match('#^(https?://|[a-z][a-z0-9_]+:[^/])#i', $src)) $z['remote']++;
				break;

			case $T . 'header':
				$z['remote']++;
				break;

			case $T . 'program':
				$z['programs']++;
				break;

			case $T . 'param':
				$vorher = $knoten->getRefprev();
				if('IF' === ($a[$T . 'name'] ?? null)
					&& is_object($vorher) && $vorher->full_URI() === $T . 'program')
					$z['eval']++;
				break;

			case $T . 'access':
				$z['access']++;
				$text = (string) $knoten->getdata();
				foreach(QP_MANIFEST_WRITE_CMDS as $cmd)
					if(false !== strpos($text, '"' . $cmd . '"')) { $z['writes']++; break; }
				break;

			case $T . 'object':
				$z['objects']++;
				$id   = (string) ($a[$T . 'id']   ?? '');
				$name = (string) ($a[$T . 'name'] ?? '');
				if('' !== $name)
				{
					$z['plugins'][$name]['declared'] = 1;
					if('' !== $id) $z['ids'][$id] = $name;
				}
				/* Die Kinder gehoeren zu DIESEM Objekt - auch ein Verweis ohne name. */
				$objekt = ['id' => $id, 'name' => $name];
				break;

			case $T . 'remote':
				/* Erst nach dem Durchlauf zuordnen: ein Verweis kann vor seiner
				*  Deklaration stehen. */
				$z['remotes'][] = [$objekt['id'], $objekt['name'], (string) ($a[$T . 'name'] ?? '')];
				break;
		}

		/* Ein Tag im tree-Namensraum, den die Fabrik nicht kennt, wird ein generischer
		*  Knoten, der nichts tut. HTML ohne eigenen Namensraum ist Inhalt; alles andere ist
		*  ein fremder Name (programm, add2) und wird mit Namen gefuehrt. */
		if(0 === strpos($uri, $T) && 'Interface_node' === get_class($knoten))
		{
			$lokal = strtolower(substr($uri, strlen($T)));
			if(in_array($lokal, QP_MANIFEST_HTML, true)) $z['html']++;
			else { $z['foreign']++; $z['foreign_names'][$lokal] = true; }
		}

		foreach(($knoten->getRefnext() ?? []) as $kind)
			qp_manifest_walk($kind, $z, $tiefe + 1, $objekt);
	}

	/* Ordnet die gesammelten remotes ihren Plugins zu.
	*
	*  Das Plugin ist das UMSCHLIESSENDE Objekt, bei einem Verweis (<object id="ses">) ueber
	*  die Deklaration mit derselben id. Der remote-Name kommt in zwei Formen vor:
	*    Langform  Session.sessionIn / Session.sessionIn.value   (34.985 im Bestand)
	*    Kurzform  startTimer / startTimer.label                  (528) - Methode des Objekts
	*  Ein Name ohne weiteren Punkt FEUERT (calls), mit Punkt setzt er einen Parameterplatz
	*  (slots). Die Gegenrechnung am Kuehlschrank hatte "4 Aufrufe" gemeldet, wo 2 standen;
	*  "startTimer" stand als eigenes Plugin in der Liste. */
	function qp_manifest_resolve(array &$z)
	{
		foreach(($z['remotes'] ?? []) as [$id, $name, $remote])
		{
			$plugin = ('' !== $name) ? $name : ($z['ids'][$id] ?? '');
			$rest   = $remote;

			if('' !== $plugin && 0 === strpos($remote, $plugin . '.'))
				$rest = substr($remote, strlen($plugin) + 1);
			elseif('' === $plugin && false !== ($p = strpos($remote, '.')) && ctype_upper($remote[0]))
			{
				$plugin = substr($remote, 0, $p);      // remote ohne Objekt, Langform
				$rest   = substr($remote, $p + 1);
			}

			if('' === $plugin) $plugin = '?';          // nicht zuzuordnen - ehrlich zaehlen

			$art = (false === strpos($rest, '.')) ? 'calls' : 'slots';
			$z['plugins'][$plugin][$art] = ($z['plugins'][$plugin][$art] ?? 0) + 1;

			if('calls' === $art && in_array($plugin . '.' . $rest, QP_MANIFEST_WRITES, true))
				$z['writes']++;
		}
		unset($z['remotes'], $z['ids']);
	}
}

try {
	$reg = $content->getRegObj();
	$reg->_useGeneral();

	$reg->__manifest = function($node, $obj, $event)
		{
			global $logger_class;

			$s     = $event->get_Result_Array();
			$a     = $s['Command']['Attribute'] ?? [];
			$value = $s['Command']['Value'] ?? null;
			$datei = trim((string) ($a['file'] ?? ''));
			$tiefe = (isset($a['depth']) && '' !== trim((string) $a['depth'])) ? intval($a['depth']) : -1;

			if('' === $datei)                   throw new Exception('__manifest: file fehlt');
			if('' === trim((string) ($a['path'] ?? ''))) throw new Exception('__manifest: path fehlt');

			if(false === ($ziel = qp_manifest_inside($datei)))
				throw new Exception('__manifest: "' . $datei . '" liegt ausserhalb der Installation');

			/* Was das bisherige Manifest unter file gescannt hatte. Daran unterscheidet sich
			*  "verschwunden" von "vertippt": nur ein Pfad, den das alte Manifest schon nannte,
			*  darf fehlen - er wird dann vermerkt statt gescannt. Ein unbekannter Pfad bricht
			*  weiter ab, sonst schriebe path="template/relams" still ein leeres Manifest. */
			$bisher = [];
			if(is_file($ziel) && preg_match('#<man:Scan man:path="([^"]*)"#', (string) file_get_contents($ziel), $alt))
				foreach(explode(';', html_entity_decode($alt[1], ENT_XML1 | ENT_QUOTES, 'UTF-8')) as $b)
					if('' !== trim($b)) $bisher[] = trim($b);

			/* --- Verzeichnisse -> Dateien, nur ganze Verzeichnisse (STW) --- */
			$dateien = [];
			$wurzeln = [];
			$weg     = [];
			foreach(explode(';', (string) $a['path']) as $p)
			{
				if('' === trim($p)) continue;
				$abs = qp_manifest_inside($p);
				if(false === $abs)
					throw new Exception('__manifest: "' . trim($p) . '" liegt ausserhalb der Installation');

				if(!is_dir($abs))
				{
					$rel = qp_manifest_rel($abs);
					if(!in_array($rel, $bisher, true))
						throw new Exception('__manifest: "' . trim($p) . '" ist kein Verzeichnis innerhalb der Installation');

					/* Verschwunden: die Geisterzeilen fallen weg, die Tatsache bleibt stehen. */
					$wurzeln[] = $rel;
					$weg[]     = $rel;
					$logger_class->setAssert('__manifest: "' . $rel . '" ist verschwunden - seine Dokumente'
						. ' fallen aus dem Manifest, man:missing vermerkt es (behavior/manifest.php)', 5);
					continue;
				}

				$wurzeln[] = qp_manifest_rel($abs);
				$it = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS));
				$it->setMaxDepth($tiefe);
				foreach($it as $f)
					if($f->isFile() && '.xml' === strtolower(substr($f->getFilename(), -4)))
						$dateien[$f->getPathname()] = true;
			}
			$dateien = array_keys($dateien);
			sort($dateien);

			$parser  = $node->get_parser();
			$zurueck = $parser->cur_idx();
			$start   = microtime(true);
			$zeilen  = [];
			$fehler  = 0;

			try
			{
				foreach($dateien as $abs)
				{
					$rel = qp_manifest_rel($abs);
					$z = ['signs' => 0, 'programs' => 0, 'eval' => 0, 'access' => 0, 'objects' => 0,
					      'remote' => 0, 'writes' => 0, 'html' => 0, 'foreign' => 0,
					      'foreign_names' => [], 'plugins' => [], 'remotes' => [], 'ids' => []];
					$root = '';
					$err  = '';

					try
					{
						$idx = $parser->load($abs, 0);
						$parser->change_idx($idx);
						$parser->set_first_node();
						$wurzel = $parser->show_xmlelement();
						if(is_object($wurzel))
						{
							$root = $wurzel->full_URI();
							qp_manifest_walk($wurzel, $z);
							qp_manifest_resolve($z);
						}
						else
							$err = 'kein Wurzelknoten';
					}
					catch(\Throwable $t)
					{
						$err = get_class($t) . ': ' . $t->getMessage();
					}
					finally
					{
						$parser->change_idx($zurueck);
					}

					if('' !== $err) $fehler++;
					$zeilen[] = ['path' => $rel, 'hash' => hash_file('sha256', $abs),
					             'bytes' => filesize($abs), 'root' => $root, 'err' => $err, 'z' => $z];
				}
			}
			finally
			{
				$parser->change_idx($zurueck);
			}

			/* --- Manifest schreiben: flach, alles Attribut --- */
			$jn  = fn(int $n) => $n > 0 ? 'ja' : 'nein';
			$jetzt = date('c');
			$x   = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
			     . "<rdf:RDF xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns\"\n"
			     . "         xmlns:owl=\"http://www.w3.org/2002/07/owl\"\n"
			     . "         xmlns:dcterms=\"http://purl.org/dc/terms/\"\n"
			     . "         xmlns:man=\"" . QP_MANIFEST_NS . "\">\n"
			     . "<owl:Ontology rdf:about=\"" . QP_MANIFEST_NS . "\" />\n"
			     . '<man:Scan man:path="' . qp_manifest_attr(implode(';', $wurzeln)) . '" man:depth="' . $tiefe
			     . '" man:documents="' . count($zeilen) . '" man:errors="' . $fehler
			     . '" man:missing="' . ($weg ? 'ja' : 'nein') . '"'
			     . ($weg ? ' man:missing_paths="' . qp_manifest_attr(implode(';', $weg)) . '"' : '')
			     . ' dcterms:created="' . $jetzt . "\" />\n";

			foreach($zeilen as $r)
			{
				$z = $r['z'];
				$x .= '<man:Document man:path="' . qp_manifest_attr($r['path']) . '"'
				    . ' man:hash="' . $r['hash'] . '" man:bytes="' . $r['bytes'] . '"'
				    . ' man:root="' . qp_manifest_attr($r['root']) . '"'
				    . ' man:signs="' . $z['signs'] . '" man:programs="' . $z['programs'] . '"'
				    . ' man:objects="' . $z['objects'] . '" man:plugins="' . count($z['plugins']) . '"'
				    . ' man:eval="' . $z['eval'] . '" man:access="' . $z['access'] . '"'
				    . ' man:writes="' . $z['writes'] . '" man:remote="' . $z['remote'] . '"'
				    . ' man:html="' . $z['html'] . '" man:foreign="' . $z['foreign'] . '"'
				    . ('' !== ($fn = implode(' ', array_keys($z['foreign_names'])))
				        ? ' man:foreign_names="' . qp_manifest_attr($fn) . '"' : '')
				    . ' man:has_eval="' . $jn($z['eval']) . '" man:has_access="' . $jn($z['access']) . '"'
				    . ' man:has_write="' . $jn($z['writes']) . '" man:has_remote="' . $jn($z['remote']) . '"'
				    . ('' !== $r['err'] ? ' man:error="' . qp_manifest_attr($r['err']) . '"' : '')
				    . " />\n";

				ksort($z['plugins']);
				foreach($z['plugins'] as $plugin => $p)
					$x .= '<man:Uses man:path="' . qp_manifest_attr($r['path']) . '"'
					    . ' man:plugin="' . qp_manifest_attr($plugin) . '"'
					    . ' man:declared="' . (($p['declared'] ?? 0) ? 'ja' : 'nein') . '"'
					    . ' man:calls="' . ($p['calls'] ?? 0) . '"'
					    . ' man:slots="' . ($p['slots'] ?? 0) . "\" />\n";
			}
			$x .= "</rdf:RDF>\n";

			if(!is_dir(dirname($ziel)) && !@mkdir(dirname($ziel), 0775, true))
				throw new Exception('__manifest: Verzeichnis fuer "' . $datei . '" nicht anlegbar');

			$tmp = $ziel . '.tmp';
			if(false === file_put_contents($tmp, $x) || !rename($tmp, $ziel))
				throw new Exception('__manifest: "' . $datei . '" nicht schreibbar');

			$bericht = ['file' => qp_manifest_rel($ziel), 'path' => implode(';', $wurzeln),
			            'documents' => count($zeilen), 'errors' => $fehler, 'missing' => $weg,
			            'ms' => (int) round((microtime(true) - $start) * 1000),
			            'peak_mb' => round(memory_get_peak_usage(true) / 1048576, 1)];

			$logger_class->setAssert('__manifest: ' . json_encode($bericht, JSON_UNESCAPED_SLASHES)
				. ' (behavior/manifest.php)', 5);

			$obj->set_context($bericht);
			if(!empty($value)) $node->hold_messages($value, $obj);

			return true;
		};

	$reg->addLog(fn($node, $obj, $event) => '__manifest: '
		. ($event->get_Result_Array()['Command']['Attribute']['path'] ?? '?'), 6);
	/* Liest beliebige Dokumente unterhalb der Installation UND schreibt eine Datei. */
	$reg->addSecurity(10);

	$reg->addDescription(
		'Scannt ganze Verzeichnisse und legt ein Manifest ab: je Dokument Hash, Groesse und was'
		. ' es tut (Tuerschilder, program, eval ueber IF, access, schreibende Aufrufe, entfernte'
		. ' Quellen, nicht registrierte Tags), je Dokument und Plugin eine Zeile. Laden fuehrt'
		. ' nichts aus. Gelesen wird das Manifest in einem ZWEITEN Aufruf (__load, __query).'
		. ' Legt den Bericht ins Ereignis; mit Value (etwa __to_owner) geht er nach aussen.',
		[
			'path'  => ['description' => 'Ein oder mehrere Verzeichnisse, mit ; getrennt. Nur ganze'
			                           . ' Verzeichnisse, unterhalb der Installation.',
			            'required'    => true],
			'file'  => ['description' => 'Wohin das Manifest geschrieben wird.',
			            'required'    => true],
			'depth' => ['description' => 'Wie tief in Unterverzeichnisse; leer = unbegrenzt, 0 = nur'
			                           . ' das Verzeichnis selbst.',
			            'required'    => false]
		]);

} catch (Exception $e) {
	global $logger_class;
	if(is_object($logger_class))
		$logger_class->setAssert('manifest.php: ' . $e->getMessage(), 0);
}
?>
