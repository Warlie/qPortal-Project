<?PHP
/**
*	<subtree> in einem <element> - was er einhaengt, steht an SEINER Stelle im Text.
*
*	Derselbe Standard wie fuer <sub> (tree_call.php) und ein inneres <element>: ein Knoten
*	haelt Text in Behaeltern data[i] VOR Kind i (Interface_ns), das Element setzt seinen Text
*	nach dem Lauf der Kinder neu zusammen. Bis 3.2.10 kannte diese Schleife den <subtree>
*	nicht: sein Text lief in einen Behaelter weiter, und das Eingehaengte stand dahinter -
*	"vor--nach<b>X</b>".
*
*	Zwei Wege, beide geprueft: <element value="h1"> legt einen NEUEN Knoten an
*	(process_new_xhtml, fixtures/subtree_in_element.xml), <element> ohne value aendert den
*	vorhandenen (process_exist_xhtml, fixtures/subtree_exist.xml).
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_subtree.php
*	Ohne Schluessel in config/config.ini geht es ueber die Sitzung (GET ?i=__intern, dann
*	der Befehl mit demselben Cookie) - das setzt [intern] anonymous = 1 oder keinen
*	Schluessel voraus.
*/
$base = getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php';

$token = '';
if (is_file(__DIR__ . '/../../mod_lib.php') && is_file(__DIR__ . '/../../config/config.ini'))
{
	require_once(__DIR__ . '/../../mod_lib.php');
	$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
	$liste = array_filter(intern_key_list($cfg['intern']['key'] ?? []), fn($k) => ($k['token'] ?? '') !== '');
	usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
	if ($liste) $token = $liste[0]['token'];
}

/* Die Seite eines Fixture-Dokuments, mit start ohne Pfad. */
function seite(string $doc) : string
{
	global $base, $token;
	$url = $base . '?doc=' . $doc;
	$jar = tempnam(sys_get_temp_dir(), 'qp');
	$opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
	        CURLOPT_TIMEOUT => 30, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar];

	if ($token === '')
	{
		$ch = curl_init($url . '&i=__intern');
		curl_setopt_array($ch, $opt);
		curl_exec($ch);
		curl_close($ch);
	}

	$ch = curl_init($url);
	curl_setopt_array($ch, $opt + [CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => json_encode(['Identifire' => 'http://www.trscript.de/tree#indextree',
		                                   'Command' => ['Name' => 'start'], 'Attribute' => []]),
		CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json',
		                                    $token !== '' ? 'Authorization: Bearer ' . $token : null])]);
	$body = (string) curl_exec($ch);
	curl_close($ch);
	@unlink($jar);
	return $body;
}

/* Inhalt des ersten <$tag> - ohne Attribute des Tags, Namensraumangaben im Inhalt entfernt,
*  damit der Vergleich nur die Reihenfolge von Text und Knoten sieht. */
function inhalt(string $seite, string $tag) : ?string
{
	if (!preg_match('#<' . $tag . '(?:\s[^>]*)?>(.*?)</' . $tag . '>#s', $seite, $m)) return null;
	//der Serialisierer schreibt "<b >" - Schreibweise, nicht Reihenfolge
	return preg_replace(['#\s+xmlns(:\w+)?="[^"]*"#', '#<(\w+)\s+>#'], ['', '<$1>'], $m[1]);
}

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

echo "qPortal <subtree> in <element> - Pruefstand\nZiel: $base\n" . str_repeat('-', 78) . "\n";

$neu = seite('subtree_in_element');
foreach ([
	'h1' => ['Text davor und danach',          'vor-<b>X</b>-nach'],
	'h2' => ['zwei subtree',                    'a<b>X</b>b<b>X</b>c'],
	'h3' => ['element, dann subtree',           'a<i>E</i>b<b>X</b>c'],
	'h4' => ['subtree allein (Bestand)',        '<b>X</b>'],
	'h5' => ['subtree, dann element',           'a<b>X</b>b<i>E</i>c'],
] as $tag => [$name, $soll])
{
	$ist = inhalt($neu, $tag);
	result('neu <' . $tag . '>: ' . $name, $ist === $soll, 'ist: ' . var_export($ist, true) . '  soll: ' . $soll);
}

$alt = seite('subtree_exist');
$ist = inhalt($alt, 'html');
result('vorhanden <html>: Text davor und danach', !is_null($ist) && trim($ist) === 'vor-<b>X</b>-nach',
       'ist: ' . var_export($ist, true));

$rot = 0;
foreach ($results as [$name, $ok, $note])
{
	printf("[%s] %-42s %s\n", $ok ? '  ok  ' : 'FEHLER', $name, $note);
	if (!$ok) $rot++;
}
echo str_repeat('-', 78) . "\n" . (count($results) - $rot) . " gelaufen, $rot fehlgeschlagen\n";
exit($rot ? 1 : 0);
