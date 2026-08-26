#!/usr/bin/env php
<?PHP
/*
* intern_walk.php - laeuft jeden Intern-Befehl einmal an und berichtet, ob er lief.
*
* Aufruf (Server muss laufen, siehe server.sh):
*     php test/Integration/intern_walk.php
*     QPORTAL_URL=https://host/index.php php test/Integration/intern_walk.php
*
* Warum ueber __give_log und nicht ueber den Rueckgabewert:
* Interface_ns.php:1396 faengt jede Exception ab, schreibt sie als "ERROR ..." auf
* Logstufe 0 und liefert false. Der Request bleibt dabei HTTP 200. Ein Fehlschlag ist
* von aussen also nur im Log sichtbar - deshalb laeuft jeder Fall in einer
* __give_log-Klammer, und das Erfolgssignal ist: erwartete Logzeile da UND kein ERROR.
*
* Was dieser Test NICHT kann, und warum:
* Die schreibenden Befehle (__add_node, __set_attribute, __remove_attribute,
* __remove_node, __insert_data, __set_namespace) feuern kein Value und koennen ihre
* Wirkung darum nicht an ein pruefendes Kommando weiterreichen. Ein zweiter Request
* hilft nicht, weil jeder Request den Baum neu laedt - Aenderungen sind fluechtig, und
* der Array-Modus aus dem Glossar ("wrap in a JSON array") wird nicht ausgefuehrt.
* Fuer diese Befehle gilt deshalb nur das schwaechere "gelaufen, ohne ERROR".
* Echte Wirkung wird nur geprueft, wo sie den Befehl verlaesst: __position_stamp ->
* __go_to_stamp (Stempel als Kontext) und __save_back (Datei auf der Platte).
*/

const NODE   = 'http://www.trscript.de/tree#final';
const NS_ATT = 'http://www.trscript.de/tree#name';

$base   = getenv('QPORTAL_URL') ?: 'https://localhost:8002/index.php';
$jar    = tempnam(sys_get_temp_dir(), 'qp_walk_');
$outdir = sys_get_temp_dir() . '/qp_walk_' . getmypid();
@mkdir($outdir, 0700, true);

/* ------------------------------------------------------------------ Transport */

function http_post($payload)
{
	global $base, $jar;

	$ch = curl_init($base);
	curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER         => true,
		CURLOPT_COOKIEJAR      => $jar,
		CURLOPT_COOKIEFILE     => $jar,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_TIMEOUT        => 30
	]);

	$raw = curl_exec($ch);
	if ($raw === false)
	{
		$err = curl_error($ch);
		curl_close($ch);
		return ['body' => '', 'ctype' => '', 'error' => $err];
	}

	$split = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);

	return ['body' => substr($raw, $split), 'ctype' => (string)$ctype, 'error' => ''];
}

/* Sitzung oeffnen - der Intern-Modus haengt an der Session, nicht am Request */
function open_session()
{
	global $base, $jar;

	$ch = curl_init($base . '?i=__intern');
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_COOKIEJAR      => $jar,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_TIMEOUT        => 30
	]);
	$ok = curl_exec($ch) !== false;
	curl_close($ch);
	return $ok;
}

/* ------------------------------------------------------------------ Bausteine */

function cmd($name, $attr = null, $value = null)
{
	$c = ['Name' => $name];
	if (!is_null($attr))  $c['Attribute'] = $attr;
	if (!is_null($value)) $c['Value']     = $value;
	return ['Identifire' => '*', 'Command' => $c];
}

/* __find_node sucht in dem Baum, in dem es steht - hier also im Indexbaum */
function on_node($inner, $uri = NODE)
{
	return cmd('__find_node', ['json' => json_encode(['name' => $uri])], $inner);
}

function with_log($inner)
{
	return cmd('__give_log', null, $inner);
}

/* ------------------------------------------------------------------ Auswertung */

$results = [];

function check($command, $payload, $expect, $note = '')
{
	global $results;

	$res = http_post($payload);
	$log = $res['body'];

	if ($res['error'] !== '')
		return $results[] = ['cmd' => $command, 'state' => 'FEHLER',
		                     'note' => 'Transport: ' . $res['error']];

	/* Interface_ns.php:1396 - geschluckte Exception, nur hier sichtbar */
	if (preg_match('/ERROR [\\\\\w]+: (.+?) in /', $log, $m))
		return $results[] = ['cmd' => $command, 'state' => 'FEHLER',
		                     'note' => trim($m[1])];

	if (is_callable($expect))
	{
		[$ok, $detail] = $expect($log, $res);
		return $results[] = ['cmd' => $command, 'state' => $ok ? 'ok' : 'FEHLER',
		                     'note' => $detail];
	}

	$ok = $expect === '' ? true : (bool)preg_match($expect, $log);

	return $results[] = ['cmd' => $command, 'state' => $ok ? 'ok' : 'FEHLER',
	                     'note' => $ok ? $note : 'erwartete Logzeile fehlt: ' . $expect];
}

/* ------------------------------------------------------------------ Der Lauf */

echo "qPortal Intern-Befehle - Durchlauf\n";
echo "Ziel: $base\n";
echo str_repeat('-', 78) . "\n";

if (!open_session())
{
	fwrite(STDERR, "Sitzung liess sich nicht oeffnen - laeuft der Server?\n");
	exit(2);
}

/* --- Selbstauskunft: antwortet selbst als JSON, darf nicht in __give_log --- */

check('__info_ns', cmd('__info_ns'), function($body, $res) {
	if (strpos($res['ctype'], 'application/json') === false)
		return [false, 'Content-Type ist "' . $res['ctype'] . '" statt application/json'];
	$j = json_decode($body, true);
	return [isset($j['namespaces']), 'Namensraeume: ' . count($j['namespaces'] ?? [])];
});

check('__info', cmd('__info', ['ns' => '']), function($body, $res) {
	if (strpos($res['ctype'], 'application/json') === false)
		return [false, 'Content-Type ist "' . $res['ctype'] . '"'];
	$j = json_decode($body, true);
	$n = count($j['commands'] ?? []);
	$ohne = 0;
	foreach ($j['commands'] ?? [] as $c) if (is_null($c['description'])) $ohne++;
	return [$n > 0, "$n Befehle, davon $ohne ohne Beschreibung"];
});

/* --- Die Klammer selbst --- */

check('__give_log', with_log(on_node(cmd('__look_around'))),
      '/log from /', 'Log wird statt des Dokuments ausgeliefert');

/* --- Navigieren --- */

check('__find_node', with_log(on_node(cmd('__look_around'))),
      '/__find_node in /', 'Knoten gefunden');

check('__look_around', with_log(on_node(cmd('__look_around'))),
      '/look_around:act:/', 'Umgebung protokolliert');

/* --- Komposition --- */

check('__redirect_node', with_log(on_node(cmd('__redirect_node', null, cmd('__look_around')))),
      '/__redirect_node in /', 'Ereignis weitergereicht');

/* --- Stempel: der einzige echte Roundtrip --- */

$stamp = null;
check('__position_stamp', with_log(on_node(cmd('__position_stamp'))),
      function($log) use (&$stamp) {
	if (!preg_match('/__position_stamp: (\d{4}\.[\w\[\]\/+=.-]*)/', $log, $m))
		return [false, 'kein Stempel im Log'];
	$stamp = $m[1];
	return [true, 'Stempel ' . $stamp];
});

if (is_null($stamp))
	$results[] = ['cmd' => '__go_to_stamp', 'state' => 'uebersprungen',
	              'note' => 'kein Stempel aus __position_stamp'];
else
	check('__go_to_stamp',
	      with_log(cmd('__go_to_stamp', null, cmd('__look_around'))),
	      function($log) use ($stamp) {
		/* Der Stempel steht neben Command, nicht darin - siehe Beschreibung */
		return [strpos($log, '__go_to_stamp: ') !== false,
		        'aufgeloest und dort weitergefeuert'];
	});

/* Der eigentliche Beweis: Stempel nehmen, dorthin springen, dort erneut stempeln.
*  __position_stamp reicht den Stempel als Ereigniskontext weiter, __go_to_stamp
*  nimmt genau den, wenn kein Attribut gesetzt ist - die Kette schliesst sich also
*  ohne Zutun von aussen. Kommen zwei gleiche Stempel heraus, bezeichnet der Stempel
*  wirklich dieselbe Stelle im Baum und der Sprung trifft sie. */
check('Stempel-Rundlauf',
      with_log(on_node(cmd('__position_stamp', null,
                           cmd('__go_to_stamp', null,
                               cmd('__position_stamp'))))),
      function($log) {
	/* Hash und Position getrennt: die ersten vier Ziffern sind der Hash, die
	*  Adresse ist der Teil dahinter. Verglichen wird die Adresse - der Hash haengt
	*  am Zustand des Knotens und darf abweichen, ohne dass der Sprung falsch ist. */
	preg_match_all('/__position_stamp: (\d{4})\.([\w\[\]\/+=.-]*)/', $log, $m,
	               PREG_SET_ORDER);
	if (count($m) < 2)
		return [false, 'nur ' . count($m) . ' Stempel im Log, erwartet 2'];

	[$h1, $p1] = [$m[0][1], $m[0][2]];
	[$h2, $p2] = [$m[1][1], $m[1][2]];

	$hinweis = $h1 === $h2 ? '' : " (Hash $h1 -> $h2)";
	return [$p1 === $p2,
	        $p1 === $p2
	            ? "hin und zurueck auf Position $p1" . $hinweis
	            : "Sprung landet woanders: $p1 -> $p2" . $hinweis];
});

/* Stempel als Attribut - der Befehl liest ihn NEBEN Command, sein eigenes addLog
*  aber IN Command. Geprueft wird deshalb die Wirkung (der Sprung), nicht die
*  Logzeile: nach dem Sprung muss derselbe Stempel wieder herauskommen. */
if (!is_null($stamp))
	foreach (['voll' => $stamp, 'ohne Hash' => substr($stamp, 5)] as $form => $wert)
	{
		$go = cmd('__go_to_stamp', null, cmd('__position_stamp'));
		$go['Attribute'] = ['stamp' => $wert];

		check("__go_to_stamp (Attribut, $form)", with_log($go),
		      function($log) use ($stamp, $wert, $form) {
			if (!preg_match('/__position_stamp: \d{4}\.([\w\[\]\/+=.-]*)/', $log, $m))
				return [false, 'am Ziel kein Stempel - Sprung nicht angekommen'];

			$ziel   = substr($stamp, 5);
			$trifft = $m[1] === $ziel;

			/* Der Hash gehoert zur Eingabe: die Aufloesung schneidet die ersten
			*  Stellen als Hash ab. Ein Stempel ohne Hash verliert dadurch sein
			*  erstes Pfadglied und landet flacher - erwartet, kein Defekt. */
			if ($form === 'ohne Hash')
				return [!$trifft, $trifft
					? 'unerwartet: Stempel ohne Hash trifft doch'
					: "\"$wert\" landet auf {$m[1]} statt $ziel - Hash gehoert zur Eingabe"];

			return [$trifft, $trifft
				? "\"$wert\" fuehrt auf Position $ziel"
				: "\"$wert\" landet auf {$m[1]} statt $ziel"];
		});
	}

/* --- Lesen --- */

check('__get_attribute', with_log(on_node(cmd('__get_attribute'))),
      '/vorhanden=\{.*' . preg_quote(NS_ATT, '/') . '/',
      'alle Attribute des Knotens gelesen');

check('__get_attribute (benannt)',
      with_log(on_node(cmd('__get_attribute', ['json' => json_encode(['name' => NS_ATT])],
                           cmd('__look_around')))),
      '/name=' . preg_quote(NS_ATT, '/') . '/', 'benannter Zugriff');

check('__get_data', with_log(on_node(cmd('__get_data'))),
      '/__get_data of requester/', 'Datenteil an den Aufrufer kopiert');

/* --- Schreiben: fluechtig, Wirkung nicht nachlesbar (siehe Kopf) --- */

check('__set_data', with_log(on_node(cmd('__set_data'))),
      '/__set_data:/', 'gelaufen (Wirkung nicht nachlesbar)');

check('__insert_data', with_log(on_node(cmd('__insert_data'))),
      '', 'gelaufen, kein ERROR (Wirkung nicht nachlesbar)');

check('__set_namespace', with_log(on_node(cmd('__set_namespace'))),
      '', 'gelaufen, kein ERROR (Wirkung nicht nachlesbar)');

check('__add_node',
      with_log(on_node(cmd('__add_node',
                           ['json' => json_encode(['name' => 'qp_walk_probe',
                                                   'attribute' => ['probe' => 'ja'],
                                                   'text' => 'Testknoten'])]))),
      '/__add_node: qp_walk_probe/', 'Knoten angelegt');

/* Anlegen und im selben Schritt benutzen: Value feuert auf dem neuen Knoten.
*  Verglichen werden die Positionen von Eltern und Kind - das Kind muss unterhalb
*  des Elternknotens liegen, also dessen Position als Anfang tragen und ein Glied
*  laenger sein. Damit ist bewiesen, dass der Knoten wirklich dort haengt. */
check('__add_node (anlegen und stempeln)',
      with_log(on_node(cmd('__position_stamp', null,
                           cmd('__add_node',
                               ['json' => json_encode(['name' => 'qp_walk_probe',
                                                       'attribute' => ['probe' => 'ja'],
                                                       'text' => 'Testknoten'])],
                               cmd('__position_stamp'))))),
      function($log) {
	preg_match_all('/__position_stamp: (\d{4})\.([\w\[\]\/+=.-]*)/', $log, $m, PREG_SET_ORDER);
	if (count($m) < 2)
		return [false, 'nur ' . count($m) . ' Stempel - Value feuert nicht auf dem neuen Knoten'];

	$eltern = $m[0][2];
	$kind   = $m[1][2];

	if ($kind === $eltern)
		return [false, "Kind hat die Position des Elternknotens ($kind)"];

	return [str_starts_with($kind, $eltern . '.'),
	        str_starts_with($kind, $eltern . '.')
	            ? "Kind $kind haengt unter Eltern $eltern"
	            : "Kind $kind liegt nicht unter $eltern"];
});

check('__set_attribute',
      with_log(on_node(cmd('__set_attribute',
                           ['json' => json_encode(['name' => NS_ATT, 'value' => 'probe'])]))),
      '', 'gelaufen, kein ERROR (Wirkung nicht nachlesbar)');

check('__remove_attribute',
      with_log(on_node(cmd('__remove_attribute',
                           ['json' => json_encode(['name' => NS_ATT])]))),
      '', 'gelaufen, kein ERROR (Wirkung nicht nachlesbar)');

check('__remove_node', with_log(on_node(cmd('__remove_node'))),
      '', 'gelaufen, kein ERROR (fluechtig, Baum bleibt auf der Platte)');

/* --- Verdrahtung: braucht einen object-Kontext, den es hier nicht gibt --- */

check('__add_in_object', with_log(on_node(cmd('__add_in_object'))),
      '/__add_in_object:/',
      'gelaufen - sinnvoll nur im object-Element');

/* --- Sichern: die einzige Wirkung, die den Prozess verlaesst --- */

/* save_file (xml_multitree_omni_handle.php:250) schreibt nur in eine Datei, die es
*  schon gibt, sonst liefert es false - __save_back wertet das nicht aus und meldet
*  trotzdem Erfolg. Deshalb zwei Faelle: das Ziel vorbereitet und das Ziel neu. */
$target = $outdir . '/saveback.xml';
touch($target);

check('__save_back',
      with_log(on_node(cmd('__save_back', ['format' => '', 'file' => $target]))),
      function($log) use ($target) {
	clearstatcache();
	if (!is_file($target))
		return [false, 'keine Datei unter ' . $target];
	return [filesize($target) > 0,
	        filesize($target) > 0
	            ? 'geschrieben: ' . filesize($target) . ' Bytes'
	            : 'Datei blieb leer'];
});

$neu = $outdir . '/saveback_neu.xml';
check('__save_back (neuer Pfad)',
      with_log(on_node(cmd('__save_back', ['format' => '', 'file' => $neu]))),
      function($log) use ($neu) {
	clearstatcache();
	/* Erwartet wird der stille Fehlschlag - er ist dokumentiert, nicht behoben */
	return [!is_file($neu), is_file($neu)
		? 'unerwartet: neue Datei wurde doch angelegt'
		: 'legt keine neue Datei an und meldet trotzdem Erfolg (still)'];
});

/* --- Der einzige Befehl ausserhalb des Standards --- */

check('start (tree#indextree)',
      with_log(on_node(cmd('start', ['i' => '']), 'http://www.trscript.de/tree#indextree')),
      '/start in /', 'Startbefehl am Wurzelknoten');

/* --- Eigene Befehlsketten: anlegen, aufrufen, lesen, loeschen -----------------
*  Jeder check() ist ein eigener Request, und die Registry wird zu Beginn jedes
*  Requests aus der Tabelle aufgebaut. Deshalb ist die angelegte Kette ab dem
*  naechsten check() aufrufbar - genau die Reihenfolge, die hier durchlaufen wird. */

const PROBE = '__walk_probe_cmd';

/* Body: sucht den in %target% genannten Knoten und sieht sich dort um. Ueber
*  %target% wird sichtbar, ob die Einsetzung greift. */
$probe_body = on_node(cmd('__look_around'), '%target%');

check('__set_cmd', with_log(cmd('__set_cmd',
      ['name' => PROBE, 'description' => 'Sonde des Durchlaufs',
       'param' => ['target' => NODE]], $probe_body)),
      '/__set_cmd: /', 'Kette angelegt');

check('gespeicherte Kette in __info', cmd('__info', ['ns' => '']),
      function($body) {
	$j = json_decode($body, true);
	foreach ($j['commands'] ?? [] as $c)
		if ($c['name'] === PROBE)
			return [!is_null($c['description']), 'erscheint mit Beschreibung'];
	return [false, 'taucht nicht in __info auf'];
});

check('Kette laeuft (Default)', with_log(cmd(PROBE)),
      '/look_around:act:' . preg_quote(NODE, '/') . '/',
      'Default eingesetzt, Kette ausgefuehrt');

check('Kette laeuft (Override)',
      with_log(cmd(PROBE, ['target' => 'http://www.trscript.de/tree#content'])),
      '/look_around:act:http:\/\/www\.trscript\.de\/tree#content/',
      'Override statt Default eingesetzt');

check('__get_cmd', cmd('__get_cmd', ['name' => PROBE]),
      function($body) {
	$j = json_decode($body, true);
	if (!($j['found'] ?? false)) return [false, 'found=false'];
	/* Der Platzhalter muss roh erhalten sein, nicht schon eingesetzt */
	return [strpos(json_encode($j['body']), '%target%') !== false,
	        'rohe Definition mit %target% zurueck'];
});

check('__remove_cmd', with_log(cmd('__remove_cmd', ['name' => PROBE])),
      '/__remove_cmd: /', 'Loeschbefehl gelaufen');

check('Kette nach dem Loeschen weg', cmd('__get_cmd', ['name' => PROBE]),
      function($body) {
	$j = json_decode($body, true);
	return [($j['found'] ?? true) === false, 'found=false'];
});

/* ------------------------------------------------------------------ Bericht */

$ok = $fail = $skip = 0;
foreach ($results as $r)
{
	$mark = match($r['state']) { 'ok' => '  ok  ', 'FEHLER' => 'FEHLER', default => ' --   ' };
	printf("[%s] %-28s %s\n", $mark, $r['cmd'], $r['note']);
	if ($r['state'] === 'ok') $ok++; elseif ($r['state'] === 'FEHLER') $fail++; else $skip++;
}

echo str_repeat('-', 78) . "\n";
printf("%d gelaufen, %d fehlgeschlagen, %d uebersprungen\n", $ok, $fail, $skip);

if (is_dir($outdir)) { @array_map('unlink', glob($outdir . '/*')); @rmdir($outdir); }
@unlink($jar);

exit($fail > 0 ? 1 : 0);
