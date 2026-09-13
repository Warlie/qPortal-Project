<?PHP
/**
*	Das SPARQL-Plugin als Quelle in einem Dokument.
*
*	Bisher holte ein Dokument Daten nur ueber DBO (Datenbank) oder den Handle
*	(<add doctype="SPARQL">, der eine fertige ANTWORT liest). Dieses Plugin stellt die
*	Frage und gibt die Zeilen heraus wie ein DBO - moveFirst/col/next, dazu iter().
*
*	Der Baum ist fixtures/sparql_plugin.xml: zwei <tree>-Knoten, danach gefragt.
*
*	⚠ Die eigentliche Zusage ist, dass es den EIGENEN Baum fragt. Beim Ausfuehren eines
*	<program> steht der Parser woanders (gemessen: auf @registry_surface_system), also
*	klammert das Plugin mit change_idx auf den Baum seines Knotens - dafuer braucht es
*	System.CurRef im Konstruktor.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_sparql_plugin.php
*/
$base = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=sparql_plugin';

require_once(__DIR__ . '/../../mod_lib.php');
$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
$liste = intern_key_list($cfg['intern']['key'] ?? []);
usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
$token = $liste ? $liste[0]['token'] : '';

function seite()
{
	global $base, $token;
	$ch = curl_init($base);
	curl_setopt_array($ch, [CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => json_encode(['Identifire' => 'http://www.trscript.de/tree#indextree',
			'Command' => ['Name' => 'start'], 'Attribute' => []]),
		CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json',
			$token !== '' ? 'Authorization: Bearer ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30]);
	$b = (string) curl_exec($ch);
	curl_close($ch);
	return $b;
}

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

echo "qPortal SPARQL-Plugin - Pruefstand\nZiel: $base\n" . str_repeat('-', 78) . "\n";

$s = seite();

result('Seite rendert',            strlen($s) > 80, strlen($s) . ' bytes');
result('many(): zwei Zeilen',      false !== strpos($s, '<p>2</p>'),
       preg_match('#<p>([^<]*)</p>#', $s, $m) ? 'many=' . $m[1] : 'kein <p>');
result('col(): die URI des Knotens', false !== strpos($s, '<b>http://www.trscript.de/tree#tree</b>'),
       preg_match('#<b>([^<]*)</b>#', $s, $m) ? $m[1] : 'kein <b>');
result('kein Fehler in der Antwort', false === stripos($s, 'exception') && false === stripos($s, 'fehler'),
       'sauber');

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-30s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
