<?PHP
/**
*	<access> - die Klammer um Stufe und Sektor.
*
*	Der Baum ist fixtures/access_bracket.xml. Gemessen wird ueber die ABWEISUNG von
*	__save_back: der Befehl traegt als einziger addSecurity(10) (behavior/std.php:422),
*	und die Abweisung schreibt "der Aufrufer hat <Stufe>" ins Log
*	(Interface_ns.php:1522). Damit laesst sich die Stufe INNERHALB der Klammer ablesen,
*	ohne dass je etwas geschrieben wird - die Abweisung ist das Messinstrument.
*
*	Zwei Zusagen:
*	    senken   <access securitylevel=0>        -> "der Aufrufer hat 0"
*	    heben    darin <access securitylevel=5>  -> "der Aufrufer hat 5"
*
*	Die 5 ist der Beleg: die innere Klammer hat die Stufe GEHOBEN, von 0 auf 5. Mit dem
*	alten min() waere sie bei 0 geblieben.
*
*	Dazu die dritte Klammer, der EIGNER. Ein <access> nimmt fuer die Dauer seiner Kette
*	den Owner auf sich; ein __to_owner darin schreibt in den Zugang statt nach aussen.
*	Gemessen an beiden Seiten: die Logzeile nennt den Zugang als Empfaenger, und die
*	Antwort nach aussen bleibt leer. Der Gegenfall ist dieselbe Kette ohne Zugang -
*	sie geht hinaus.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_access.php
*/
$fixture = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=access_bracket';

require_once(__DIR__ . '/../../mod_lib.php');
$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
$liste = intern_key_list($cfg['intern']['key'] ?? []);
usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
$token = $liste ? $liste[0]['token'] : '';

const T = 'http://www.trscript.de/tree#';

/* Ein start auf den benannten tree, das Protokoll darum herum. */
function lauf($name)
{
	global $fixture, $token;

	$start = ['Identifire' => T . 'indextree', 'Command' => ['Name' => 'start'],
	          'Attribute'  => ('' === $name) ? [] : [$name]];

	$ch = curl_init($fixture);
	curl_setopt_array($ch, [CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => json_encode(['Identifire' => '*',
			'Command' => ['Name' => '__give_log', 'Value' => $start]], JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json',
			$token !== '' ? 'Authorization: Bearer ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30]);
	$log = (string) curl_exec($ch);
	curl_close($ch);
	return $log;
}

/* Welche Stufe nennt die Abweisung? null = gar nicht abgewiesen. */
function gemeldete_stufe($log)
{
	return preg_match('/ABGEWIESEN: __save_back verlangt Stufe 10, der Aufrufer hat (-?\d+)/', $log, $m)
	       ? intval($m[1]) : null;
}

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

echo "qPortal <access> - Pruefstand\nZiel: $fixture\n" . str_repeat('-', 78) . "\n";

$senken = lauf('senken');
$heben  = lauf('heben');

result('senken: abgewiesen',        gemeldete_stufe($senken) !== null,
       'ABGEWIESEN-Zeile ' . (gemeldete_stufe($senken) !== null ? 'da' : 'FEHLT'));
result('senken: Aufrufer hat 0',    gemeldete_stufe($senken) === 0,
       'gemeldet: ' . var_export(gemeldete_stufe($senken), true));

result('heben: abgewiesen',         gemeldete_stufe($heben) !== null,
       'ABGEWIESEN-Zeile ' . (gemeldete_stufe($heben) !== null ? 'da' : 'FEHLT'));
result('heben: Aufrufer hat 5',     gemeldete_stufe($heben) === 5,
       'gemeldet: ' . var_export(gemeldete_stufe($heben), true) . ' (mit min() waere es 0)');

/* Der Sektor, und seine umgekehrte Richtung: einengen darf jeder, hinzunehmen nur ab
*  Stufe 10. Beobachtet an der Zeile, die das Verweigern nennt. */
$darf       = lauf('sektor_darf');
$darf_nicht = lauf('sektor_darf_nicht');

result('Sektor hinzu mit Stufe 10',  false === strpos($darf, 'Sektor NICHT hinzugenommen'),
       'keine Verweigerung');
result('Sektor hinzu ohne Stufe',    false !== strpos($darf_nicht, 'Sektor NICHT hinzugenommen'),
       preg_match('/Sektor NICHT hinzugenommen[^\n]*/', $darf_nicht, $m)
         ? trim(substr($m[0], 0, 74)) : 'keine Zeile');

/* Das Heben sagt es auch selbst - laut, auf Stufe 0. */
result('heben wird geloggt',        strpos($heben, 'Stufe GEHOBEN von 0 auf 5') !== false,
       preg_match('/Stufe GEHOBEN[^\n]*/', $heben, $m) ? trim($m[0]) : 'keine Zeile');

/* Und nichts davon steht in der Antwort - innen alles, aussen nichts. */
$ch = curl_init($fixture);
curl_setopt_array($ch, [CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => json_encode(['Identifire' => T . 'indextree',
		'Command' => ['Name' => 'start'], 'Attribute' => ['heben']]),
	CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json',
		$token !== '' ? 'Authorization: Bearer ' . $token : null]),
	CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30]);
$seite = (string) curl_exec($ch);
curl_close($ch);

result('nichts davon in der Antwort', false === stripos($seite, 'ABGEWIESEN')
       && false === stripos($seite, 'GEHOBEN'), strlen($seite) . ' bytes, ohne Stufen');

/* --- die dritte Klammer: der EIGNER ------------------------------------------------
*  STW (2026-09-14): "Bei access bin ich mir sicher, dass der owner wechselt, da access
*  ueber ihn den Rueckgabewert bekommt."
*
*  ⚠ Die Kette ist zweistufig (__position_stamp -> __to_owner), und das ist der Punkt:
*  __position_stamp baut fuer sein Value ein NEUES EventObject. Dessen Konstruktor setzt
*  den Owner auf den Requester - ueber den Intern-Weg also auf den ContentGenerator.
*  Genau dort fiel der Eigner heraus, den <access> gesetzt hatte; der Wert ging nach
*  aussen statt in den Knoten. EventObject::next_in_chain traegt ihn jetzt weiter. */
$eigner = lauf('eigner');

result('Empfaenger ist der Zugang',
       false !== strpos($eigner, '__to_owner: Wert an "' . T . 'access"'),
       preg_match('/__to_owner: Wert an "[^"]*"/', $eigner, $m) ? $m[0] : 'keine Zeile');

/* Und die Gegenprobe nach aussen: ohne Zugang kommt derselbe Wert heraus, mit Zugang
*  nicht. Sonst koennte "leer" auch heissen, dass die Kette gar nicht lief. */
function roh($payload)
{
	global $fixture, $token;
	$ch = curl_init($fixture);
	curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json',
			$token !== '' ? 'Authorization: Bearer ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30]);
	$b = (string) curl_exec($ch);
	curl_close($ch);
	return json_decode($b, true);
}

$kette  = ['Identifire' => '*', 'Command' => ['Name' => '__position_stamp',
           'Value' => ['Identifire' => '*', 'Command' => ['Name' => '__to_owner']]]];
$ohne   = roh($kette);
$mit    = roh(['Identifire' => T . 'indextree', 'Command' => ['Name' => 'start'],
               'Attribute' => ['eigner']]);

result('ohne Zugang geht der Wert hinaus', isset($ohne[0]['value']),
       'value = ' . json_encode($ohne[0]['value'] ?? null));
result('mit Zugang bleibt aussen nichts', !isset($mit[0]['value']) && false === ($mit['answered'] ?? true),
       'answered = ' . json_encode($mit['answered'] ?? null));

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-32s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
