<?PHP
/**
*	Navigation - das Menue aus dem Baum, ueber das komplette qPortal.
*
*	Geprueft wird an fixtures/navi_probe.xml: fuenf tree-Knoten, die fuenf verschiedene
*	Antworten verlangen, und ein Muster (setLine).
*
*	    eins        gewoehnlicher Weg   -> erscheint
*	    zwei        mode=embedded       -> erscheint als SPRUNGMARKE /#zwei, nicht als Weg:
*	                                       ein eingebetteter Abschnitt laeuft mit der Seite,
*	                                       auf der er steht (tree_tree.php, seit 2026-09-27)
*	    .versteckt  fuehrender Punkt    -> erscheint NICHT
*	    gesperrt    sector="niemand"    -> erscheint NICHT
*	    ohne_wert   kein tree:value     -> erscheint NICHT
*
*	Die drei letzten beantwortet EIN Test: ContentGenerator::getAccess(). In der alten
*	Klasse Menue stand er zweimal - und im dritten Weg (build_menu) gar nicht.
*
*	⚠ Und die Zeile, die den Anlass gab: Menue.page("$this") ruft indexToUri() am
*	ContentGenerator, wo es die Methode nicht gibt (sie sitzt am Parser). Die Ausnahme wird
*	geschluckt, das Menue nimmt still den Vorgabebaum - gemessen 2026-09-30. Hier wird
*	darum ausdruecklich geprueft, dass im Log KEIN "Call to undefined method" steht.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/menu_navigation.php
*/
$base = getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php';

/* Schluessel wie in den uebrigen Pruefstaenden: aus der lokalen Config, der hoechste zuerst */
$token = '';
if (is_file(__DIR__ . '/../../mod_lib.php') && is_file(__DIR__ . '/../../config/config.ini'))
{
	require_once(__DIR__ . '/../../mod_lib.php');
	$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
	$liste = intern_key_list($cfg['intern']['key'] ?? []);
	usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
	if ($liste) $token = $liste[0]['token'];
}

function post($payload, $doc = 'navi_probe')
{
	global $base, $token;

	$ch = curl_init($base . '?doc=' . urlencode($doc));
	curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER     => array_filter(['Content-Type: application/json',
		                                        $token !== '' ? 'Authorization: Bearer ' . $token : null,
		                                        $token !== '' ? 'X-QPortal-Token: ' . $token : null]),
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

/* Der Lauf: start auf der Wurzel des Pruefdokuments. Das Menue entsteht dabei in der
*  Ausgabevorlage (build_menu), die Musterzeile im zweiten div. */
$start = ['Identifire' => 'http://www.trscript.de/tree#indextree',
          'Command'    => ['Name' => 'start'],
          'Attribute'  => []];

[$code, $seite] = post($start);

echo "qPortal Navigation - Pruefstand\nZiel: $base?doc=navi_probe\n" . str_repeat('-', 78) . "\n";

if ($code !== 200 || false === strpos($seite, 'id="bars"'))
{
	fwrite(STDERR, "Keine gerenderte Seite (HTTP $code) - laeuft der Server, stimmt der Schluessel?\n"
	             . substr($seite, 0, 300) . "\n");
	exit(2);
}

preg_match_all('#<a href="([^"]*)"[^>]*>([^<]*)#', $seite, $treffer, PREG_SET_ORDER);

$links = [];
foreach ($treffer as $t) $links[trim($t[2])] = $t[1];

result('eins erscheint',        isset($links['Eins']), 'ein gewoehnlicher tree-Knoten wird zur Zeile');
result('eins traegt eine Adresse', isset($links['Eins']) && false !== strpos($links['Eins'], 'eins'),
       'aus tree:name gebaut: ' . ($links['Eins'] ?? '-'));

result('zwei ist Sprungmarke',  ($links['Zwei'] ?? '') === '/#zwei',
       'mode=embedded laeuft mit der Seite, ist also kein Weg (' . ($links['Zwei'] ?? '-') . ')');

result('.versteckt fehlt',      !isset($links['Versteckt']), 'fuehrender Punkt - getAccess sagt nein');
result('gesperrt fehlt',        !isset($links['Gesperrt']),  'sector="niemand"');
result('ohne_wert fehlt',       false === strpos($seite, 'ohne_wert'), 'kein tree:value - kein Schild, keine Zeile');
result('genau zwei Zeilen',     count($links) === 2, 'aus fuenf Knoten werden zwei (' . count($links) . ')');

/* ⚠ use_element: die Zeilen gehoeren IN das genannte Element. Ohne die Angabe landen
*  sie an der Dokumentwurzel - gemessen: hinter </body>, als Geschwister von <body>.
*  Dort nuetzt eine Navigationsleiste niemandem. */
result('Zeilen stehen im Element',
       (bool) preg_match('#<div id="bars"[^>]*>\s*<a href#', $seite),
       'use_element(bars) - nicht an der Wurzel hinter </body>');

/* setLine: die erste Zeile im Muster, im zweiten div */
preg_match('#id="muster"[^>]*>([^<]*)#', $seite, $m);
$musterzeile = trim($m[1] ?? '');

result('Muster gefuellt', false !== strpos($musterzeile, 'eins = Eins'),
       'Platzhalter ersetzt: "' . $musterzeile . '"');
result('Muster kennt die Tiefe', 0 === strpos($musterzeile, '[0]'),
       '%DEEP% der ersten Ebene ist 0');

/* ⚠ Der Anlass: page("$this") darf nicht still scheitern. */
[$code2, $log] = post(['Identifire' => '*', 'Command' => ['Name' => '__give_log', 'Value' => $start]]);

result('page($this) laeuft',    false !== strpos($log, 'Navigation.page($this): Baum'),
       'die Zeile steht im Log - der Baum wurde wirklich gewechselt');
result('keine geschluckte Ausnahme', false === strpos($log, 'Call to undefined method'),
       'die alte Klasse ruft indexToUri am ContentGenerator, wo es sie nicht gibt');

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-26s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
