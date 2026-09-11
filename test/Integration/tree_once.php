<?PHP
/**
*	once - ein Ausfuehrungsbereich, der im Leben der Installation hoechstens EINMAL laeuft.
*
*	Der Baum ist fixtures/once_run.xml: once legt "ONCE LIEF" in den Scope, first "FIRST LIEF",
*	der Knoten "2". once steht VOR first. Gemessen ueber __call, weil das die Ergebnisse des
*	Scopes nach aussen gibt.
*
*	Drei Zusagen:
*	    im selben Request   once und first laufen genau einmal, once zuerst
*	    im naechsten        first wieder (je Request), once NIE wieder (je Installation)
*	    im Register         eine Zeile mit Pfad, Name, done=1 und Zeitstempel
*
*	⚠ Dieser Prueflauf RAEUMT SEINE ZEILE VORHER WEG - sonst misst der zweite Lauf nichts
*	mehr. Getroffen wird nur der eine Schluessel dieses Pruefbaums, nie die Tabelle.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_once.php
*/
$fixture = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=once_run';

require_once(__DIR__ . '/../../mod_lib.php');
$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
$liste = intern_key_list($cfg['intern']['key'] ?? []);
usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
$token = $liste ? $liste[0]['token'] : '';

const T = 'http://www.trscript.de/tree#';

function post($payload)
{
	global $fixture, $token;
	$ch = curl_init($fixture);
	curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json', $token !== '' ? 'Authorization: Bearer ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 30]);
	$body = (string) curl_exec($ch);
	curl_close($ch);
	return json_decode($body, true);
}
function cmd($n, $a = null, $v = null)
{
	$c = ['Name' => $n];
	if (!is_null($a)) $c['Attribute'] = $a;
	if (!is_null($v)) $c['Value']     = $v;
	return ['Identifire' => '*', 'Command' => $c];
}
/* __call auf den Knoten "ohne_src" und die Rueckgabe nach aussen. */
$ruf = cmd('__find_node', ['json' => json_encode(['name' => T . 'tree', 'attribute' => [T . 'name' => 'ohne_src']])],
           cmd('__call', null, cmd('__to_owner')));
/* Die results-Listen aller Antworten einer Runde. */
$holen = function($folge) use ($ruf)
{
	$aus = [];
	foreach ((post($folge) ?? []) as $e)
		if (isset($e['value']['results'])) $aus[] = $e['value']['results'];
	return $aus;
};

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

echo "qPortal once - Pruefstand\nZiel: $fixture\n" . str_repeat('-', 78) . "\n";

/* Derselbe Schluessel, den TREE_once bildet: sha256(Dokument + '#' + tree:name). Das
*  Dokument ist der Pfad, unter dem der Parser es geladen hat - absolut. */
$dokument = realpath(__DIR__ . '/fixtures/once_run.xml');
$hash     = hash('sha256', $dokument . '#' . 'pruefstand;once_run_v1');

$dbc = parse_ini_file(__DIR__ . '/../../config/config.ini', true)['database'] ?? null;
$db  = $dbc ? @new mysqli($dbc['URL'], $dbc['User'], $dbc['PWST'], $dbc['db_name']) : null;

if (!is_object($db) || $db->connect_errno)
{
	echo "[UEBERSPRUNGEN] keine Datenbankverbindung - once braucht sein Register\n";
	exit(0);
}
/* Nur die eigene Zeile, und nur wenn die Tabelle schon existiert. */
@$db->query("DELETE FROM qp_once WHERE hash = '" . $db->real_escape_string($hash) . "'");

/* --- erster Request: zwei __call ------------------------------------------------ */
$r1 = $holen([$ruf, $ruf]);

result('once laeuft, und VOR first',  ($r1[0] ?? null) === ['ONCE LIEF', 'FIRST LIEF', '2'],
       json_encode($r1[0] ?? null));
result('beide nur einmal je Request', ($r1[1] ?? null) === ['2'],
       json_encode($r1[1] ?? null));

/* --- zweiter Request: frischer Prozess, frischer Baum ---------------------------- */
$r2 = $holen([$ruf]);

result('naechster Request: first ja, once nein', ($r2[0] ?? null) === ['FIRST LIEF', '2'],
       json_encode($r2[0] ?? null));

/* --- das Register ---------------------------------------------------------------- */
$row = null;
if ($erg = @$db->query("SELECT hash, path, name, done, stamp FROM qp_once WHERE hash = '"
                       . $db->real_escape_string($hash) . "'"))
	$row = $erg->fetch_assoc();

result('Register: eine Zeile',        is_array($row), is_array($row) ? 'steht' : 'keine Zeile');
result('Register: done = 1',          is_array($row) && intval($row['done']) === 1,
       is_array($row) ? 'done=' . $row['done'] : '-');
result('Register: Pfad und Name lesbar', is_array($row) && $row['path'] === substr($dokument, 0, 190)
       && $row['name'] === 'pruefstand;once_run_v1',
       is_array($row) ? basename($row['path']) . ' / ' . $row['name'] : '-');
result('Register: Zeitstempel gesetzt', is_array($row) && !is_null($row['stamp']) && '' !== trim((string) $row['stamp']),
       is_array($row) ? (string) $row['stamp'] : '-');

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-38s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
