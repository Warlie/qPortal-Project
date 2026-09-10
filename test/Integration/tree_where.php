<?PHP
/**
*	__where_am_i und __to_owner - das Ereignis als Zwischenspeicher.
*
*	__where_am_i legt das Tuerschild seines Knotens als Array ins Ereignis
*	(set_context); __to_owner gibt ein gewoehnliches Datum aus dem Ereignis nach
*	aussen, sonst wie bisher den Knoten. Der Baum ist fixtures/where_sign.xml, geladen
*	ueber fixture_entry.php?doc=where_sign.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_where.php
*/
$base = (getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php') . '?doc=where_sign';

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
	$body  = (string) curl_exec($ch);
	$ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);
	return [$ctype, $body, json_decode($body, true)];
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

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }

echo "qPortal __where_am_i / __to_owner - Pruefstand\nZiel: $base\n" . str_repeat('-', 78) . "\n";

/* 1. volles Schild, __to_owner als Value */
[$ct, $body, $j] = post(tree_named('messen', cmd('__where_am_i', null, cmd('__to_owner'))));
$v = $j[0]['value'] ?? null;
result('Schild: kommt als Datum',  is_array($v) && ($j[0]['serialised'] ?? null) === true, $ct);
result('Schild: Name und URI',     ($v['name'] ?? null) === 'messen' && ($v['uri'] ?? null) === T . 'tree', json_encode(['name' => $v['name'] ?? null]));
result('Schild: Beschriftung',     ($v['tree:value'] ?? null) === 'Messen', 'tree:value');
result('Schild: Titel',            ($v['dcterms:title'] ?? null) === 'Messung', 'dcterms:title (Praefix mit #)');
result('Schild: Eingaben',         ($v['desc:parameter'] ?? null) === 'wo', 'desc:parameter');
result('Schild: Ausgaben',         ($v['desc:delivers'] ?? null) === 'rst' && ($v['desc:columns'] ?? null) === 'wert, zeit', 'desc:delivers, desc:columns');
result('Schild: Funktion, Wirkung',($v['desc:function'] ?? null) === 'Misst etwas.' && ($v['desc:effect'] ?? null) === 'keiner, liest nur', 'desc:function, desc:effect');
result('Schild: kein Fehler',      !isset($v['error']) && !isset($v['note']), $v['error'] ?? $v['note'] ?? '-');

/* 2. Listenform: das Ereignis traegt den Wert zum naechsten Befehl */
[, , $j2] = post(tree_named('messen', [cmd('__where_am_i'), cmd('__to_owner')]));
result('Liste: gleiches Schild',   ($j2[0]['value'] ?? null) === $v, 'where_am_i, dann to_owner auf demselben Ereignis');

/* 3. zwei gleichnamige Knoten - jeder sein eigenes Schild */
[, , $j3] = post(tree_named('doppelt', cmd('__where_am_i', null, cmd('__to_owner'))));
$fn = array_map(fn($e) => $e['value']['desc:function'] ?? null, $j3 ?? []);
result('doppelt: zwei Antworten',  count($j3 ?? []) === 2, count($j3 ?? []) . ' Eintraege');
result('doppelt: nichts vermischt',$fn === ['Der erste gleichnamige.', 'Der zweite gleichnamige.'], json_encode($fn, JSON_UNESCAPED_UNICODE));

/* 4. stummer Knoten */
[, , $j4] = post(tree_named('stumm', cmd('__where_am_i', null, cmd('__to_owner'))));
result('stumm: nur uri und name',  array_keys($j4[0]['value'] ?? []) === ['uri', 'name'], json_encode(array_keys($j4[0]['value'] ?? [])));

/* 5. __to_owner ohne Datum im Ereignis: wie bisher der Knoten */
[, , $j5] = post(tree_named('messen', cmd('__to_owner')));
result('to_owner: sonst der Knoten', ($j5[0]['class'] ?? null) === 'TREE_tree' && ($j5[0]['serialised'] ?? null) === false, 'class TREE_tree, serialised false');

/* 6. __get_attribute legt hinein, __to_owner gibt hinaus */
[, , $j6] = post(tree_named('messen', cmd('__get_attribute', ['json' => json_encode(['name' => T . 'value'])], cmd('__to_owner'))));
result('get_attribute -> to_owner', ($j6[0]['value'] ?? null) === 'Messen', json_encode($j6[0] ?? null, JSON_UNESCAPED_UNICODE));

/* 7. ohne Value bleibt es im Ereignis, nach aussen geht nichts */
[, , $j7] = post(tree_named('messen', cmd('__where_am_i')));
result('ohne Value: nichts nach aussen', ($j7['answered'] ?? null) === false, 'answered:false');

/* 8. show: nur die genannten Begriffe, Kurzname und Praefixform */
[, , $j8] = post(tree_named('messen', cmd('__where_am_i', ['show' => 'function,delivers'], cmd('__to_owner'))));
result('show: Kurznamen',           array_keys($j8[0]['value'] ?? []) === ['uri', 'name', 'desc:function', 'desc:delivers'],
       json_encode(array_keys($j8[0]['value'] ?? [])));
[, , $j9] = post(tree_named('messen', cmd('__where_am_i', ['show' => 'desc:effect, value'], cmd('__to_owner'))));
result('show: Praefixform',         ($j9[0]['value']['desc:effect'] ?? null) === 'keiner, liest nur'
                                    && ($j9[0]['value']['tree:value'] ?? null) === 'Messen' && count($j9[0]['value'] ?? []) === 4,
       json_encode(array_keys($j9[0]['value'] ?? [])));
[, , $j10] = post(tree_named('messen', cmd('__where_am_i', ['show' => 'function,gibtsnicht'], cmd('__to_owner'))));
result('show: Unbekanntes in note', strpos($j10[0]['value']['note'] ?? '', 'gibtsnicht') !== false && isset($j10[0]['value']['desc:function']),
       $j10[0]['value']['note'] ?? '-');

/* 9. scope=local ausdruecklich = Vorgabe */
[, , $j11] = post(tree_named('messen', cmd('__where_am_i', ['scope' => 'local'], cmd('__to_owner'))));
result('scope local = Vorgabe',     ($j11[0]['value'] ?? null) === $v, 'dasselbe Schild');

/* 10. scope=tree: alle tree/final im Dokument des Knotens, nichts hinter dem Sektor */
$namen = fn($hits) => array_map(fn($h) => $h['name'] ?? null, $hits);
[, , $j12] = post(tree_named('messen', cmd('__where_am_i', ['scope' => 'tree'], cmd('__to_owner'))));
$h12 = $j12[0]['value']['hits'] ?? [];
result('tree: das ganze Dokument',   $namen($h12) === ['home', 'messen', 'doppelt', 'doppelt', 'stumm', 'anderswo'],
       json_encode($namen($h12)));
result('tree: Dokument genannt',     str_ends_with($j12[0]['value']['tree'] ?? '', 'where_sign.xml'), basename($j12[0]['value']['tree'] ?? '-'));
result('tree: Schild im Treffer',    ($h12[1]['desc:function'] ?? null) === 'Misst etwas.' && isset($h12[1]['stamp']), 'messen mit Funktion und Stempel');
result('tree: Sektor haelt',         !in_array('gesperrt', $namen($h12), true), 'gesperrt fehlt');

/* 11. global: alles, was geladen ist - ohne und nach __echo */
$aus = fn($hits, $doc) => array_values(array_filter($hits, fn($h) => str_ends_with($h['tree'] ?? '', $doc)));
[, , $j13] = post(tree_named('messen', cmd('__where_am_i', ['scope' => 'global'], cmd('__to_owner'))));
$h13 = $j13[0]['value']['hits'] ?? [];
result('global ohne echo',           count($aus($h13, 'where_sign.xml')) === 6 && count($aus($h13, 'where_other.xml')) === 0,
       count($h13) . ' Treffer, davon where_sign ' . count($aus($h13, 'where_sign.xml')));
$echo = cmd('__echo');
[, , $j14] = post([$echo, tree_named('messen', cmd('__where_am_i', ['scope' => 'global'], cmd('__to_owner')))]);
$h14 = $aus($j14[0]['value']['hits'] ?? [], 'where_other.xml');
result('global nach echo',           $namen($h14) === ['home', 'messen'] && ($h14[1]['desc:function'] ?? null) === 'Misst anderswo.',
       json_encode($namen($h14)) . ' aus where_other');
result('global: Baum und Stempel',   isset($h14[0]['tree'], $h14[0]['stamp']), 'je Treffer');
$uris = array_unique(array_map(fn($h) => $h['uri'] ?? '', $j14[0]['value']['hits'] ?? []));
result('nur tree und final',         array_diff($uris, [T . 'tree', T . 'final']) === [], json_encode(array_values($uris)));

/* 12. local nur auf tree/final, und nur mit Zutritt */
[, , $j15] = post(cmd('__find_node', ['json' => json_encode(['name' => T . 'indextree'])], cmd('__where_am_i', null, cmd('__to_owner'))));
result('local auf indextree',        strpos($j15[0]['value']['note'] ?? '', 'kein tree- oder final-Knoten') !== false
                                     && !isset($j15[0]['value']['name']) || ($j15[0]['value']['name'] ?? null) === null, $j15[0]['value']['note'] ?? '-');
[, , $j16] = post(tree_named('gesperrt', cmd('__where_am_i', null, cmd('__to_owner'))));
result('local hinter dem Sektor',    strpos($j16[0]['value']['note'] ?? '', 'kein Zutritt') !== false && !isset($j16[0]['value']['desc:function']),
       $j16[0]['value']['note'] ?? '-');

/* 13. unbekannter scope, und show zusammen mit tree */
[, , $j17] = post(tree_named('messen', cmd('__where_am_i', ['scope' => 'irgendwas'], cmd('__to_owner'))));
result('scope unbekannt',            strpos($j17[0]['value']['note'] ?? '', 'unbekannter scope') !== false, $j17[0]['value']['note'] ?? '-');
[, , $j18] = post(tree_named('messen', cmd('__where_am_i', ['scope' => 'tree', 'show' => 'function'], cmd('__to_owner'))));
$keys = array_unique(array_merge(...array_map('array_keys', $j18[0]['value']['hits'] ?? [[]])));
result('show wirkt auf die Liste',   array_diff($keys, ['uri', 'name', 'stamp', 'desc:function']) === [], json_encode(array_values($keys)));

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-30s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
