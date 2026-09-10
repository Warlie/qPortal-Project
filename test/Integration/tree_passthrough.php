<?PHP
/**
*	Kein start, keine Kaskade - ueber das komplette qPortal.
*
*	Nur start ist ein Lauf. Einen anderen Befehl nimmt ein tree-Knoten an, schreibt
*	eine Zeile ins Log und laesst ihn stehen (tree_tree.php): kein Pfad verbraucht,
*	kein src geladen, nichts gestartet, nichts an way_out weitergegeben. Die
*	tree-Aufrufe sind getrennt zu halten (STW). Wer den Baum durchlaufen will, tut
*	das als Befehl ueber die Struktur (getRefnext()).
*
*	⚠ In way_out eines tree stehen keine tree-Kinder - die haengen per
*	event_initiated flach am indextree. Das Durchreichen an way_out, das hier bis
*	2026-09-10 geprueft wurde, traf darum die uebrigen Knoten, und die unterstellen
*	alle einen start.
*
*	Der Baum ist test/Integration/fixtures/tree_nested.xml, geladen ueber
*	fixture_entry.php. Gefeuert wird ein Befehl, den keine Registry kennt, mit einem
*	Pfad, der zu keinem Namen passt, auf "aussen". Dass aussen antwortet, beweist
*	zugleich, dass das Pruefdokument geladen ist - aussen gibt es nur dort.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_passthrough.php
*/
$base = getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php';

/* Schluessel wie in intern_walk.php: aus der lokalen Config, der hoechste zuerst */
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

$results = [];
function result($name, $ok, $note)
{
	global $results;
	$results[] = [$name, $ok, $note];
}

/* Unbekannter Befehl mit unpassendem Pfad auf "aussen", das Log als Antwort */
$feuer = ['Identifire' => '*', 'Command' => ['Name' => 'qp_passthrough_probe'],
          'Attribute'  => ['passt_zu_keinem_namen']];

[$code, $log] = post(['Identifire' => '*', 'Command' => ['Name' => '__give_log', 'Value' =>
                     ['Identifire' => '*', 'Command' => ['Name' => '__find_node',
                      'Attribute' => ['json' => json_encode(['name' => 'http://www.trscript.de/tree#tree',
                                      'attribute' => ['http://www.trscript.de/tree#name' => 'aussen']])],
                      'Value' => $feuer]]]]);

echo "qPortal kein start, keine Kaskade - Pruefstand\nZiel: $base\n" . str_repeat('-', 78) . "\n";

if ($code !== 200 || strpos($log, 'log from') === false)
{
	fwrite(STDERR, "Keine Logantwort (HTTP $code) - laeuft der Server, stimmt der Schluessel?\n");
	exit(2);
}

$zeile = fn($name) => (bool) preg_match('/tree "' . preg_quote($name, '/') . '": .* ist kein start/', $log);

result('aussen nimmt an', $zeile('aussen'), 'ist kein start - Pruefdokument geladen, Pfad nicht verglichen');

foreach (['innen', 'ganz_innen', 'gesperrt', 'dahinter'] as $name)
	result("keine Kaskade: $name", !$zeile($name), 'nichts weitergereicht');
result('nichts gestartet',        strpos($log, 'start in ') === false, 'kein "start in" im Log');
result('kein ERROR',              !preg_match('/ERROR [\\\\\w]+: /', $log), 'keine geschluckte Ausnahme');

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-32s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
