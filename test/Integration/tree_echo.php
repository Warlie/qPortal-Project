<?PHP
/**
*	__echo - Unterbaeume laden, nicht starten. Ueber das komplette qPortal.
*
*	Der Baum ist test/Integration/fixtures/echo_root.xml (ueber fixture_entry.php?doc=):
*
*	    echo_root: blatt    -> echo_leaf: tiefer -> echo_deep: kreis -> echo_leaf (Kreis)
*	               gesperrt -> echo_locked  (Sektor, den kein Pruefschluessel hat)
*	               fern     -> https://example.invalid  (Adresse)
*
*	depth zaehlt nur an einem src herunter, leer = 1, 0 = durchlaufen ohne zu laden.
*	Value laeuft genau einmal, auf dem Eingangsknoten. Ein schon geladenes Dokument
*	wird nicht neu geladen - der Kreis mit depth 5 laedt fuenfmal, aber nur zwei Baeume.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_echo.php
*/
$base = getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php';
$base .= '?doc=echo_root';

$token = '';
if (is_file(__DIR__ . '/../../mod_lib.php') && is_file(__DIR__ . '/../../config/config.ini'))
{
	require_once(__DIR__ . '/../../mod_lib.php');
	$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
	$liste = intern_key_list($cfg['intern']['key'] ?? []);
	usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
	if ($liste) $token = $liste[0]['token'];
}

function post($payload)
{
	global $base, $token;
	$ch = curl_init($base);
	curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER     => array_filter(['Content-Type: application/json',
		                                         $token !== '' ? 'Authorization: Bearer ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_TIMEOUT        => 30
	]);
	$body = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return [$code, (string) $body];
}

/* __echo auf der Wurzel, mit __look_around als Value, das Log als Antwort */
function echo_run($depth)
{
	$cmd = ['Name' => '__echo', 'Value' => ['Identifire' => '*', 'Command' => ['Name' => '__look_around']]];
	if (!is_null($depth)) $cmd['Attribute'] = ['depth' => $depth];
	[$code, $log] = post(['Identifire' => '*', 'Command' => ['Name' => '__give_log',
	                      'Value' => ['Identifire' => '*', 'Command' => $cmd]]]);
	if ($code !== 200 || strpos($log, 'log from') === false)
	{
		fwrite(STDERR, "Keine Logantwort (HTTP $code) - laeuft der Server, stimmt der Schluessel?\n");
		exit(2);
	}
	return $log;
}

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

$zeile   = fn($log, $tree, $rest) => (bool) preg_match('/__echo: tree "' . preg_quote($tree, '/') . '": ' . $rest . '/', $log);
$geladen = fn($log) => preg_match_all('/ geladen \(Baum (\d+), Tiefe/', $log, $m) ? $m[1] : [];
$sauber  = function($log, $wie) {
	result("$wie: nichts gestartet", strpos($log, 'start in ') === false, 'kein "start in" im Log');
	result("$wie: kein ERROR", !preg_match('/ERROR [\\\\\w]+: /', $log), 'keine geschluckte Ausnahme');
};

echo "qPortal __echo - Pruefstand\nZiel: $base\n" . str_repeat('-', 78) . "\n";

/* --- ohne Angabe: depth 1 --- */
$log = echo_run(null);
result('leer: blatt geladen',      $zeile($log, 'blatt', '.*echo_leaf\.xml geladen \(Baum \d+, Tiefe 1 -> 0\)'), 'erste src-Ebene');
result('leer: tiefer nicht',       $zeile($log, 'tiefer', 'Tiefe erschoepft'), 'dort steht es auf 0');
result('leer: gesperrt nicht',     $zeile($log, 'gesperrt', 'kein Zutritt'), 'mayEnter sagt nein');
result('leer: hinter der Sperre',  strpos($log, 'echo_locked.xml geladen') === false, 'echo_locked nie geladen');
result('leer: fern nicht',         $zeile($log, 'fern', 'Adresse, nicht geladen'), 'keine fremde Instanz');
result('leer: Value genau einmal', substr_count($log, 'look_around:act') === 1,
       substr_count($log, 'look_around:act') . 'x __look_around');
$sauber($log, 'leer');

/* --- depth 0: durchlaufen, nichts laden --- */
$log = echo_run(0);
result('0: nichts geladen',        count($geladen($log)) === 0, count($geladen($log)) . ' Ladevorgaenge');
result('0: blatt erschoepft',      $zeile($log, 'blatt', 'Tiefe erschoepft'), 'Lauf ja, Laden nein');
$sauber($log, '0');

/* --- depth 2: zwei Ebenen --- */
$log = echo_run(2);
result('2: blatt geladen',         $zeile($log, 'blatt', '.*echo_leaf\.xml geladen'), 'Ebene 1');
result('2: tiefer geladen',        $zeile($log, 'tiefer', '.*echo_deep\.xml geladen'), 'Ebene 2');
result('2: kreis nicht',           $zeile($log, 'kreis', 'Tiefe erschoepft'), 'Schluss nach zwei src');
$sauber($log, '2');

/* --- depth 5 ueber den Kreis: endet, laedt aber keinen Baum doppelt --- */
$log   = echo_run(5);
$baeume = $geladen($log);
result('5: endet',                 count($baeume) === 5, count($baeume) . ' Ladevorgaenge (5 erwartet)');
result('5: kein Baum doppelt',     count(array_unique($baeume)) === 2,
       count(array_unique($baeume)) . ' verschiedene Baeume (' . implode(',', array_unique($baeume)) . ')');
$sauber($log, '5');

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-28s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
