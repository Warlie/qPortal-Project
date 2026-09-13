<?PHP
/**
*	__query - eine Abfrage fuer alle Suchmodelle.
*
*	Der Baum ist fixtures/where_sign.xml (derselbe wie fuer __where_am_i, er hat
*	tree-Knoten mit Schildern). Gemessen wird ueber __to_owner.
*
*	Zusagen:
*	    sparql laeuft, und Knoten kommen als {uri, name, stamp} zurueck
*	    ohne statement, unbekanntes Modell und xpath melden sich als note statt zu werfen
*	    die Stufe steht auf 6
*
*	⚠ KEIN scope, anders als bei __where_am_i: OWL ist in dieser Instanz instanzweit,
*	es gibt nichts zu begrenzen (STW).
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_query.php
*/
$base = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=where_sign';

require_once(__DIR__ . '/../../mod_lib.php');
$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
$liste = intern_key_list($cfg['intern']['key'] ?? []);
usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
$token = $liste ? $liste[0]['token'] : '';

const PRAEFIX = "PREFIX tree: <http://www.trscript.de/tree#>\n"
              . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n";

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
/* __query mit diesen Attributen, Ergebnis nach aussen. */
function frage(array $attr)
{
	[, $j] = post(['Identifire' => '*', 'Command' => ['Name' => '__query', 'Attribute' => $attr,
		'Value' => ['Identifire' => '*', 'Command' => ['Name' => '__to_owner']]]]);
	return $j[0]['value'] ?? null;
}

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

echo "qPortal __query - Pruefstand\nZiel: $base\n" . str_repeat('-', 78) . "\n";

/* --- sparql, der einzige gebaute Weg ----------------------------------------------- */
$a = frage(['statement' => PRAEFIX . 'SELECT ?s WHERE { ?s rdf:type tree:tree }']);

result('sparql: Modell genannt',   ($a['model'] ?? null) === 'sparql', json_encode($a['model'] ?? null));
result('sparql: Zeilen da',        !empty($a['rows']), count($a['rows'] ?? []) . ' Zeilen');
result('sparql: keine note',       !isset($a['note']), $a['note'] ?? 'keine');

$erste = $a['rows'][0]['?s'] ?? null;
result('Knoten als uri/name/stamp',
       is_array($erste) && isset($erste['uri'], $erste['name'], $erste['stamp'])
       && $erste['uri'] === 'http://www.trscript.de/tree#tree',
       is_array($erste) ? $erste['name'] . ' @ ' . $erste['stamp'] : json_encode($erste));

/* Ein Ausdruck, der nichts findet, ist kein Fehler. */
$leer = frage(['statement' => PRAEFIX . 'SELECT ?s WHERE { ?s rdf:type tree:gibtsnicht }']);
result('leere Treffermenge ist ok', is_array($leer) && [] === ($leer['rows'] ?? null) && !isset($leer['note']),
       json_encode($leer['rows'] ?? null));

/* --- die Wege, die sich melden statt zu werfen -------------------------------------- */
$ohne = frage([]);
result('ohne statement: note',     false !== strpos($ohne['note'] ?? '', 'kein statement'), $ohne['note'] ?? 'keine');

$fremd = frage(['model' => 'gibtsnicht', 'statement' => 'x']);
result('unbekanntes Modell: note', false !== strpos($fremd['note'] ?? '', 'kein Suchmodell'), $fremd['note'] ?? 'keine');

$xp = frage(['model' => 'xpath', 'statement' => '/a/b']);
result('xpath: note statt Wurf',   false !== strpos($xp['note'] ?? '', 'noch nicht gebaut'),
       substr($xp['note'] ?? 'keine', 0, 58));

/* Ein kaputter Ausdruck ebenso - der Parser meldet sich, der Aufruf stirbt nicht. */
$kaputt = frage(['statement' => 'SELECT ?s WHERE {{{']);
result('kaputter Ausdruck: note',  isset($kaputt['note']), substr($kaputt['note'] ?? 'keine', 0, 58));

/* --- die Einstufung ---------------------------------------------------------------- */
[, $info] = post(['Identifire' => '*', 'Command' => ['Name' => '__info']]);
$stufe = null;
array_walk_recursive($info, function($v, $k) use (&$stufe, $info) {});
$suchen = function($o) use (&$suchen, &$stufe) {
	if (is_array($o)) {
		if (($o['name'] ?? null) === '__query') $stufe = $o['security'] ?? null;
		foreach ($o as $v) $suchen($v);
	}
};
$suchen($info);
result('Stufe 6 in der Selbstauskunft', $stufe === 6, 'security=' . var_export($stufe, true));

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-34s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
