<?PHP
/**
*	Der Logger: Speicher, Zuhoerer, Level - ohne Server, dann ueber das komplette qPortal.
*
*	Teil 1 (ohne Server): der Logger allein. Gesammelt wird nur, wenn es jemand will;
*	ein Zuhoerer hat sein eigenes Array und Level, gekappt auf listenMax; gelesen wird
*	klassisch mit moveFirst/col/next; eine zweite Anmeldung leert.
*	Teil 2 (Server): Anmeldung per Plugin-Aufruf in fixtures/logger_plugin.xml, und
*	__give_log mit level.
*
*	Aufruf:  php -d error_reporting=E_ERROR test/Integration/logger_listen.php
*/
require_once(__DIR__ . '/../bootstrap.php');

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

echo "qPortal Logger - Pruefstand\n" . str_repeat('-', 78) . "\n";

$log = new Logger();
$log->setImportance(5);
$GLOBALS['logger_class'] = $log;
Logger::$active  = false;
Logger::$collect = false;
Logger::$listenMax = 5;
Logger::openChannel('global');

/* 1. niemand will das Log: nichts entsteht */
$log->setstart('log from ...');
$log->setAssert('eins', 5);
result('aus: nichts gebaut',          Logger::getChannel('global') === [], count(Logger::getChannel('global')) . ' Eintraege');

/* 2. gesammelt: nach dem globalen Level */
Logger::$collect = true;
$log->setstart('log from ...');
$log->setAssert('fuenf', 5);
$log->setAssert('sechs', 6);
$msgs = array_column(Logger::getChannel('global'), 'msg');
result('collect: Kopf und Level',     $msgs === ['log from ...', 'fuenf'], json_encode($msgs));
result('giveLogText wie die Datei',   Logger::giveLogText() === "log from ...\nfuenf\n", json_encode(Logger::giveLogText()));

/* 3. ein Zuhoerer ist unabhaengig vom globalen Level - nach unten */
$tief = new Logger();
$tief->listen('tief', 1);
$log->setAssert('null', 0);
$log->setAssert('fuenf-b', 5);
result('Zuhoerer tiefer als global',  array_column(Logger::listened('tief'), 'msg') === ['null'], json_encode(array_column(Logger::listened('tief'), 'msg')));

/* 4. ... und nach oben, gekappt auf listenMax */
Logger::$listenMax = 6;
$hoch = new Logger();
$hoch->listen('hoch', 9);
Logger::$collect = false;
$stand = count(Logger::getChannel('global'));
$log->setAssert('sechs-b', 6);
$log->setAssert('sieben', 7);
result('Zuhoerer hoeher als global',  array_column(Logger::listened('hoch'), 'msg') === ['sechs-b'], 'sechs ja, sieben nein (Kappe 6)');
result('ohne collect: global steht', count(Logger::getChannel('global')) === $stand, 'globaler Puffer waechst nicht mehr, der Zuhoerer schon');

/* 5. klassisch lesen, mit eigenem Zeiger */
$gelesen = [];
if ($hoch->moveFirst()) do { $gelesen[] = $hoch->col('index') . ':' . $hoch->col('msg') . '@' . $hoch->col('level'); } while ($hoch->next());
result('moveFirst/col/next',          $gelesen === ['0:sechs-b@6'], json_encode($gelesen));
result('fields',                      $hoch->fields() === ['index', 'msg', 'level'], json_encode($hoch->fields()));
$log->setAssert('sechs-c', 6);
result('was spaeter kommt, sieht next', $hoch->next() && $hoch->col('msg') === 'sechs-c', 'live');
$fehler = '';
try { $hoch->col('gibtsnicht'); } catch (Exception $e) { $fehler = $e->getMessage(); }
result('unbekannte Spalte wirft',     $fehler !== '', $fehler);

/* 6. zwei Zuhoerer, zwei Zeiger */
$zwei = new Logger();
$zwei->listen('zwei', 6);
$log->setAssert('x', 5);
$zwei->moveFirst();
result('zwei Zeiger',                 $zwei->col('msg') === 'x' && $hoch->col('msg') === 'sechs-c', 'hoch steht noch, wo er war');

/* 7. derselbe Name noch einmal: leer */
$neu = new Logger();
$neu->listen('hoch', 6);
result('zweite Anmeldung leert',      Logger::listened('hoch') === [], count(Logger::listened('hoch')) . ' Eintraege');

/* 8. ohne Anmeldung liest ein Logger nichts */
$leer = new Logger();
result('ohne Anmeldung leer',         $leer->moveFirst() === false && $leer->col('msg') === false, 'moveFirst false');

/* --- Teil 2: ueber das komplette qPortal --------------------------------------------- */
$token = '';
if (is_file(__DIR__ . '/../../config/config.ini'))
{
	require_once(__DIR__ . '/../../mod_lib.php');
	$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
	$liste = intern_key_list($cfg['intern']['key'] ?? []);
	usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
	if ($liste) $token = $liste[0]['token'];
}
function post($url, $payload)
{
	global $token;
	$ch = curl_init($url);
	curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json', $token !== '' ? 'Authorization: Bearer ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_TIMEOUT => 60]);
	$body  = (string) curl_exec($ch);
	$ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);
	return [$ctype, $body];
}
$fix  = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=logger_plugin';
$main = getenv('QPORTAL_URL') ?: 'https://localhost:8002/index.php';

/* 9. Anmeldung per Plugin-Aufruf: start fuehrt das program aus */
[$ct, $body] = post($fix, ['Identifire' => '*', 'Command' => ['Name' => '__give_log', 'Value' =>
                     ['Identifire' => 'http://www.trscript.de/tree#indextree', 'Command' => ['Name' => 'start'], 'Attribute' => []]]]);
result('Plugin-Aufruf: angemeldet',   (bool) preg_match('/Logger: Zuhoerer "pruef" angemeldet, level \d+ \(gekappt von 9, listen_max\)/', $body),
       preg_match('/Logger: Zuhoerer[^\n]*/', $body, $m) ? $m[0] : 'keine Zeile');
result('Plugin-Aufruf: kein ERROR',   !preg_match('/ERROR [\\\\\w]+: /', $body), $ct);
result('Log aus dem Speicher',        strpos($body, 'log from ') !== false, '__give_log vorn: Kopfzeile da');

/* 10. __give_log mit level: nur was dabei entsteht */
$look = ['Identifire' => '*', 'Command' => ['Name' => '__find_node', 'Attribute' => ['json' => json_encode(['name' => 'http://www.trscript.de/tree#final'])],
         'Value' => ['Identifire' => '*', 'Command' => ['Name' => '__look_around']]]];
[, $voll]  = post($main, ['Identifire' => '*', 'Command' => ['Name' => '__give_log', 'Value' => $look]]);
[, $knapp] = post($main, ['Identifire' => '*', 'Command' => ['Name' => '__give_log', 'Attribute' => ['level' => 1], 'Value' => $look]]);
result('give_log ohne level: global', strpos($voll, 'look_around:act') !== false && strpos($voll, 'log from ') !== false, strlen($voll) . ' Bytes');
result('give_log level 1: knapp',     strpos($knapp, 'look_around:act') === false && strpos($knapp, 'log from ') === false,
       strlen($knapp) . ' Bytes');

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-32s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
