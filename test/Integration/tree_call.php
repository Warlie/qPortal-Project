<?PHP
/**
*	__call - einen Knoten ausfuehren wie ein <sub> und seine Rueckgabe einsammeln.
*
*	Der Baum ist fixtures/call_sign.xml (ueber fixture_entry.php?doc=call_sign). __call
*	legt {uri, name, results:[...]} ins Ereignis; __to_owner gibt es hinaus.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_call.php
*/
$base = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=call_sign';

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
	curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json', $token !== '' ? 'Authorization: Bearer ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30]);
	$body = (string) curl_exec($ch);
	curl_close($ch);
	return [$body, json_decode($body, true)];
}

const T = 'http://www.trscript.de/tree#';
function cmd($name, $attr = null, $value = null)
{
	$c = ['Name' => $name];
	if (!is_null($attr))  $c['Attribute'] = $attr;
	if (!is_null($value)) $c['Value']     = $value;
	return ['Identifire' => '*', 'Command' => $c];
}
function tree_named($name, $inner)
{
	return cmd('__find_node', ['json' => json_encode(['name' => T . 'tree', 'attribute' => [T . 'name' => $name]])], $inner);
}
$call = fn() => cmd('__call', null, cmd('__to_owner'));

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

echo "qPortal __call - Pruefstand\nZiel: $base\n" . str_repeat('-', 78) . "\n";

[$b1, $j1] = post(tree_named('einfach', $call()));
$v1 = $j1[0]['value'] ?? null;
result('einfach: Rueckgabe 2',       ($v1['results'] ?? null) === ['2'], json_encode($v1['results'] ?? $b1));
result('einfach: Name und URI',      ($v1['name'] ?? null) === 'einfach' && ($v1['uri'] ?? null) === T . 'tree', 'am Ergebnis');

[, $j2] = post(tree_named('zwei', $call()));
result('zwei Ergebnisse',            ($j2[0]['value']['results'] ?? null) === ['a', 'b'], json_encode($j2[0]['value']['results'] ?? null));

[, $j3] = post(tree_named('leer', $call()));
result('leer: keine Rueckgabe',      ($j3[0]['value']['results'] ?? null) === [] && !isset($j3[0]['value']['note']), json_encode($j3[0]['value'] ?? null));

[, $j4] = post(tree_named('gesperrt', $call()));
result('gesperrt: kein Zutritt',     strpos($j4[0]['value']['note'] ?? '', 'kein Zutritt') !== false && ($j4[0]['value']['results'] ?? []) === [],
       $j4[0]['value']['note'] ?? '-');

[, $j5] = post(tree_named('unter', $call()));
result('src: Rueckgabe des Unterdokuments', ($j5[0]['value']['results'] ?? null) === ['aus dem Unterdokument'], json_encode($j5[0]['value']['results'] ?? null));

/* derselbe Aufruf zweimal in einem Request - tree_sub wuerde am Scope-Namen scheitern */
[, $j6] = post([tree_named('unter', $call()), tree_named('unter', $call())]);
result('zweimal derselbe src',       count($j6 ?? []) === 2 && ($j6[1]['value']['results'] ?? null) === ['aus dem Unterdokument'],
       count($j6 ?? []) . ' Antworten');

/* das Ereignis traegt es auch in der Listenform */
[, $j7] = post(tree_named('einfach', [cmd('__call'), cmd('__to_owner')]));
result('Liste: call, dann to_owner', ($j7[0]['value']['results'] ?? null) === ['2'], 'Ereignis als Zwischenspeicher');

/* __call startet nichts ausser dem gerufenen Knoten */
[$b8] = post(cmd('__give_log', null, tree_named('einfach', cmd('__call'))));
result('nichts sonst gestartet',     strpos($b8, 'start in ') === false && !preg_match('/ERROR [\\\\\w]+: /', $b8), 'kein "start in", kein ERROR');

/* Die einfache Rueckgabe ueber <sub>: vor 2026-09-11 kam sie nie an - die Instanz, die
*  TREE_result in den Scope legt, hatte ihren Wert nicht. Jetzt steht er beim Aufrufer. */
$render = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=sub_value_render';
$ch = curl_init($render);
curl_setopt_array($ch, [CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => json_encode(['Identifire' => T . 'indextree', 'Command' => ['Name' => 'start'], 'Attribute' => []]),
	CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json', $token !== '' ? 'Authorization: Bearer ' . $token : null]),
	CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30]);
$seite = (string) curl_exec($ch);
curl_close($ch);
result('<sub>: einfache Rueckgabe',   strpos($seite, 'SUB LIEFWERT') !== false,
       preg_match('/<html[^>]*>([^<]*)</', $seite, $m) ? 'Seite: ' . $m[1] : 'keine Seite');

/* <sub> IN einem <element>: es lief schon immer (die Spur im Log), aber das Element setzte
*  danach seinen Text neu und tilgte die Rueckgabe - "vor--nach". Jetzt steht sie an ihrer Stelle. */
$el = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=sub_in_element';
$hole = function($payload) use ($el, $token)
{
	$ch = curl_init($el);
	curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload),
		CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json', $token !== '' ? 'Authorization: Bearer ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30]);
	$b = (string) curl_exec($ch);
	curl_close($ch);
	return $b;
};
$start  = ['Identifire' => T . 'indextree', 'Command' => ['Name' => 'start'], 'Attribute' => []];
$seite2 = $hole($start);
$spur   = $hole(['Identifire' => '*', 'Command' => ['Name' => '__give_log', 'Value' => $start]]);
result('<sub> im <element>: lief',    strpos($spur, 'Zuhoerer "sub_lief" angemeldet') !== false, 'Spur im Log');
result('<sub> im <element>: an seiner Stelle', strpos($seite2, 'vor-SPUR-nach') !== false,
       preg_match('/<html[^>]*>([^<]*)</', $seite2, $m) ? 'Seite: ' . $m[1] : 'keine Seite');

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-34s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
