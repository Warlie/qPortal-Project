<?PHP
/**
*	Sprachfassungen: von mehreren Geschwistern mit xml:lang laeuft beim start nur die beste.
*
*	Reihenfolge des Vorzugs: lang= am Befehl, Accept-Language, [language] prefer; passt
*	nichts, die Fassung ohne Sprache, dann die erste. Ein Knoten ohne xml:lang laeuft immer.
*	Die Faelle OHNE Sprachangabe haengen an der Konfiguration dieser Installation - ihre
*	Erwartung wird aus prefer gerechnet, nicht angenommen.
*
*	Fixtures: lang_variants.xml (__call, <result>), lang_output.xml (start mit Ausgabe),
*	desc_sign.xml (__where_am_i).
*
*	Aufruf:  php -d error_reporting=E_ERROR test/Integration/lang_choice.php
*	         (Server muss laufen; QPORTAL_FIXTURE_URL wie bei den anderen Pruefstaenden)
*/
$base = getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php';

$token = '';
$prefer = 'en';
if (is_file(__DIR__ . '/../../mod_lib.php'))
{
	require_once(__DIR__ . '/../../mod_lib.php');
	$ini = is_file(__DIR__ . '/../../config/default.ini') ? parse_ini_file_multi(__DIR__ . '/../../config/default.ini', true) : [];
	if (is_file(__DIR__ . '/../../config/config.ini'))
	{
		$cfg = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
		$ini = array_replace_recursive($ini, $cfg);
		$liste = intern_key_list($cfg['intern']['key'] ?? []);
		usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
		if ($liste) $token = $liste[0]['token'];
	}
	$prefer = (string) ($ini['language']['prefer'] ?? 'en');
}
$vorzug = array_values(array_filter(array_map(fn($s) => strtolower(trim($s)), explode(';', $prefer))));

/* Was die Instanz ohne Sprachangabe waehlen muss: die erste aus prefer, die es gibt. */
function erwartet(array $fassungen): string
{
	global $vorzug;
	foreach ($vorzug as $l) if (in_array($l, $fassungen, true)) return $l;
	return $fassungen[0];
}

function post($doc, $payload, $kopf = [])
{
	global $base, $token;
	$ch = curl_init($base . '?doc=' . $doc);
	curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER     => array_values(array_filter(array_merge(['Content-Type: application/json',
		                          $token !== '' ? 'Authorization: Bearer ' . $token : null], $kopf))),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_TIMEOUT        => 30
	]);
	$body = (string) curl_exec($ch);
	curl_close($ch);
	return $body;
}

const T = 'http://www.trscript.de/tree#';

function rufe($ast, $kopf = [])
{
	$j = json_decode(post('lang_variants', ['Identifire' => '*', 'Command' => ['Name' => '__find_node',
		'Attribute' => ['json' => json_encode(['name' => T . 'tree', 'attribute' => [T . 'name' => $ast]])],
		'Value' => ['Identifire' => '*', 'Command' => ['Name' => '__call',
		'Value' => ['Identifire' => '*', 'Command' => ['Name' => '__to_owner']]]]]], $kopf), true);
	return $j[0]['value']['results'] ?? null;
}

function seite($pfad, $kopf = [])
{
	$roh = post('lang_output', ['Identifire' => T . 'indextree', 'Command' => ['Name' => 'start'],
	                            'Attribute' => $pfad], $kopf);
	if (!preg_match('~<html([^>]*)>(.*?)</html>~s', $roh, $m)) return '(keine Seite)';
	return trim($m[2]) . (preg_match('~\slang="en"~', $m[1]) ? ' [en-Huelle]' : '');
}

function schild($attr, $kopf = [])
{
	$j = json_decode(post('desc_sign', ['Identifire' => '*', 'Command' => ['Name' => '__where_am_i',
		'Attribute' => $attr, 'Value' => ['Identifire' => '*', 'Command' => ['Name' => '__to_owner']]]], $kopf), true);
	return $j[0]['value']['hits'] ?? [];
}

$results = [];
function result($name, $ok, $note) { global $results; $results[] = [$name, (bool) $ok, $note]; }
$en = ['Accept-Language: en'];

echo "qPortal Sprachfassungen - Pruefstand\nZiel: $base\nprefer: $prefer\n" . str_repeat('-', 78) . "\n";

/* --- __call: welche <program>-Fassung liefert ------------------------------------ */
$d = erwartet(['de', 'en', 'fr']);
$r = rufe('drei');                                      result("drei, ohne Angabe: $d + neutral", $r === [$d, 'neutral'], json_encode($r));
if (null === $r) { fwrite(STDERR, "Keine Antwort - laeuft der Server, stimmt der Schluessel?\n"); exit(2); }
$r = rufe('drei', $en);                                 result('drei, en: en + neutral',          $r === ['en', 'neutral'], json_encode($r));
$r = rufe('drei', ['Accept-Language: fr-FR,fr;q=0.9']); result('drei, fr-FR: fr + neutral',       $r === ['fr', 'neutral'], json_encode($r));
$r = rufe('drei', ['Accept-Language: it']);             result("drei, it (unbekannt): $d",        $r === [$d, 'neutral'], json_encode($r));
$r = rufe('nur_fr');                                    result('nur_fr: das Naechstbeste',        $r === ['fr'], json_encode($r));
$e = erwartet(['fr', 'en']);
$r = rufe('en_fr');                                     result("en_fr, ohne Angabe: $e",          $r === [$e], json_encode($r));

/* --- start: content, tree (Pfadsegment), main ------------------------------------ */
$h = 'HOME-' . strtoupper(erwartet(['de', 'en']));
$s = seite([]);                result("content, ohne Angabe: $h",  $s === $h, $s);
$s = seite([], $en);           result('content, en: HOME-EN',      $s === 'HOME-EN', $s);
$a = 'A-' . strtoupper(erwartet(['de', 'en']));
$s = seite(['a']);             result("tree a, ohne Angabe: $a",   $s === $a, $s);
$s = seite(['a'], $en);        result('tree a, en: A-EN',          $s === 'A-EN', $s);
$s = seite(['m'], $en);        result('main, en: englische Huelle', $s === 'M [en-Huelle]', $s);
$m = erwartet(['de', 'en']) === 'en' ? 'M [en-Huelle]' : 'M';
$s = seite(['m']);             result("main, ohne Angabe: $m",     $s === $m, $s);

/* --- __where_am_i: ein Satz je Begriff -------------------------------------------- */
$satz = ['de' => 'Der Flur des Pruefstands fuer Schilder.', 'en' => 'The hallway of the sign test bench.'];
$t = schild(['scope' => 'tree'])[0]['desc:text'] ?? null;
$w = $satz[erwartet(['de', 'en'])];
result('Schild, ohne Angabe',            $t === $w, (string) $t);
$t = schild(['scope' => 'tree', 'lang' => 'en'])[0]['desc:text'] ?? null;
result('Schild, lang=en am Befehl',      $t === $satz['en'], (string) $t);
$t = schild(['scope' => 'tree'], $en)[0]['desc:text'] ?? null;
result('Schild, Accept-Language: en',    $t === $satz['en'], (string) $t);
$t = schild(['scope' => 'tree', 'lang' => 'de'], $en)[0]['desc:text'] ?? null;
result('lang= schlaegt den Browser',     $t === $satz['de'], (string) $t);
$v = null;
foreach (schild(['scope' => 'tree']) as $x) if (($x['name'] ?? '') === 'verweis') $v = $x['desc:delivers'] ?? null;
result('delivers als Verweis',           $v === ['resource' => '#karten'], json_encode($v));
$k = schild(['scope' => 'tree', 'show' => 'text']);
result('show=text grenzt ein',           isset($k[1]['desc:text']) && !isset($k[1]['desc:delivers']), json_encode($k[1] ?? null));

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-38s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $good ? '' : $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail ? 1 : 0);
