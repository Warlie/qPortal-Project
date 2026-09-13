<?PHP
/**
*	__argument - der benannte Rahmen einer Befehlskette. STUB.
*
*	Das Ereignis hat genau EINEN mycontext, und jeder Schritt ueberschreibt den vorigen.
*	Damit laesst sich ein Datum weitergeben, aber keine Argumentliste sammeln. __argument
*	legt Werte unter NAMEN ab (myarguments am EventObject) und kann sie mit action=apply
*	in die Attribute des naechsten Befehls schreiben.
*
*	⚠ Was zurueckkommt, ist eine ABSCHRIFT zur Anschauung, keine Zusage - solange niemand
*	den Rahmen ausserhalb von apply liest, waere er von aussen sonst unsichtbar. Sie faellt
*	weg, sobald entschieden ist, wer ihn liest.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_argument.php
*/
$base = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=where_sign';

require_once(__DIR__ . '/../../mod_lib.php');
$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
$liste = intern_key_list($cfg['intern']['key'] ?? []);
usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
$token = $liste ? $liste[0]['token'] : '';

function post($payload)
{
	global $base, $token;
	$ch = curl_init($base);
	curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json', $token !== '' ? 'Authorization: Bearer ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30]);
	$b = (string) curl_exec($ch);
	curl_close($ch);
	return json_decode($b, true);
}
/* Ein Befehl, mit Value verkettet. */
function cmd($name, $attr = null, $value = null)
{
	$c = ['Name' => $name];
	if (!is_null($attr))  $c['Attribute'] = $attr;
	if (!is_null($value)) $c['Value']     = $value;
	return ['Identifire' => '*', 'Command' => $c];
}
function wert($kette) { $j = post($kette); return $j[0]['value'] ?? null; }

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

echo "qPortal __argument - Pruefstand\nZiel: $base\n" . str_repeat('-', 78) . "\n";

$owner = cmd('__to_owner');

/* --- sammeln ----------------------------------------------------------------------- */
$eins = wert(cmd('__argument', ['name' => 'x', 'value' => '5'], $owner));
result('Konstante binden',        ($eins['x'] ?? null) === '5', json_encode($eins));

$zwei = wert(cmd('__argument', ['name' => 'x', 'value' => '5'],
              cmd('__argument', ['name' => 'y', 'value' => 'sieben'], $owner)));
result('zwei Namen nebeneinander', ($zwei['x'] ?? null) === '5' && ($zwei['y'] ?? null) === 'sieben',
       json_encode($zwei));

/* --- fortschreiben: der Rahmen ist ein Arbeitsregister ------------------------------ */
$neu = wert(cmd('__argument', ['name' => 'x', 'value' => 'erster Stand'],
            cmd('__argument', ['name' => 'x', 'value' => 'zweiter Stand'], $owner)));
result('gleicher Name ueberschreibt', ($neu['x'] ?? null) === 'zweiter Stand', json_encode($neu));

/* Ohne value nimmt es, was der Schritt DAVOR erzeugt hat - so wandert ein Ergebnis
*  unter einen Namen (STW: __argument(a,wert) -> __call -> __argument(a)). */
$ausKette = wert(cmd('__argument', ['name' => 'x', 'value' => 'wert1'],
                 cmd('__where_am_i', ['scope' => 'local'],
                 cmd('__argument', ['name' => 'x'], $owner))));
result('ohne value: aus dem Kontext', is_array($ausKette['x'] ?? null) && isset($ausKette['x']['uri']),
       is_array($ausKette['x'] ?? null) ? 'x = ' . implode(',', array_keys($ausKette['x'])) : 'x = ' . json_encode($ausKette['x'] ?? null));

/* --- apply: der Rahmen geht in den naechsten Befehl --------------------------------- */
$satz = "PREFIX tree: <http://www.trscript.de/tree#>\n"
      . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n"
      . 'SELECT ?s WHERE { ?s rdf:type tree:tree }';

$appl = wert(cmd('__argument', ['name' => 'statement', 'value' => $satz, 'action' => 'apply'],
             cmd('__query', null, $owner)));
result('apply: __query bekommt statement', !empty($appl['rows']), count($appl['rows'] ?? []) . ' Zeilen');

/* ⚠ Der BEFEHL gewinnt: was dort steht, ist eine Sperre (STW). */
$sperre = wert(cmd('__argument', ['name' => 'model', 'value' => 'AUS_DEM_RAHMEN', 'action' => 'apply'],
               cmd('__query', ['model' => 'GESPERRT', 'statement' => 'x'], $owner)));
result('Attribut am Befehl ist Sperre', ($sperre['model'] ?? null) === 'GESPERRT',
       'model = ' . json_encode($sperre['model'] ?? null));

/* --- reset und flush ---------------------------------------------------------------- */
$res = wert(cmd('__argument', ['name' => 'a', 'value' => '1'],
            cmd('__argument', ['name' => 'b', 'value' => '2', 'action' => 'reset'], $owner)));
result('reset leert den Rahmen',  [] === $res, json_encode($res));

$fl = wert(cmd('__argument', ['name' => 'model', 'value' => 'gibtsnicht', 'action' => 'flush'],
           cmd('__query', ['statement' => 'x'], $owner)));
result('flush schreibt und leert', ($fl['model'] ?? null) === 'gibtsnicht',
       'model = ' . json_encode($fl['model'] ?? null) . ' (geschrieben), Rahmen danach leer');

/* --- was sich meldet statt zu werfen ------------------------------------------------ */
$ohne = wert(cmd('__argument', ['value' => '5'], $owner));
result('ohne name und action: nichts', [] === $ohne, json_encode($ohne));

$quatsch = wert(cmd('__argument', ['name' => 'a', 'value' => '1', 'action' => 'quatsch'], $owner));
result('unbekannte action: abgewiesen', ($quatsch['a'] ?? null) === '1', json_encode($quatsch));

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-32s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
