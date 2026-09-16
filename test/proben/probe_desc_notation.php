<?PHP
/**
*	Sonde: die Kopfnotation in /*@ ... @* / (STW 2026-09-16).
*
*	Ein Block wird an Zeilen "name::" gegliedert. Text vor dem ersten Kopf bleibt desc:text,
*	"name::" allein nimmt die folgenden Zeilen, "name:: wert" ist eine einzeilige Aussage,
*	und ein Wert in Verweisform (#name, <uri>) wird rdf:resource statt Text.
*
*	Geprueft wird ueber PHP_Ast_Scan::scan, also die Ernte selbst - nicht ueber die
*	privaten Helfer. Zum Schluss stehen die echten Bloecke im Bestand, zum Lesen.
*
*	Aufruf (ohne Server):
*	    php -d error_reporting=E_ERROR test/proben/probe_desc_notation.php
*/
chdir(__DIR__ . '/../..');
require_once('vendor/autoload.php');
require_once('classes/handles/PHP_ast_scan.php');

/* Ein Logger, der mitschreibt - die Ernte meldet unbekannte Namen und leere Koepfe. */
class Mitschrift { public $zeilen = []; function setAssert($t, $s) { $this->zeilen[] = $t; } }
$logger_class = new Mitschrift();

$ok = 0; $rot = 0;
function pruefe($titel, $bedingung, $zeige = '')
{
	global $ok, $rot;
	$bedingung ? $ok++ : $rot++;
	printf("  [%s] %-58s %s\n", $bedingung ? ' ok ' : 'ROT ', $titel, $zeige);
}

/* Beschreibungen einer Klasse bzw. einer Methode aus einer kleinen Quelle. */
function ernte($kommentar, $methode = null)
{
	$quelle = "<?php\n";
	if(is_null($methode)) $quelle .= $kommentar . "\nclass Probe {}\n";
	else                  $quelle .= "class Probe {\n" . $kommentar . "\npublic function " . $methode . " {}\n}\n";

	foreach(PHP_Ast_Scan::scan($quelle, 'probe.php', false) as $e)
	{
		$kind = $e['meta']['kind'] ?? '';
		if(is_null($methode) && 'method' !== $kind && !empty($e['meta']['desc'])) return $e['meta'];
		if(!is_null($methode) && 'method' === $kind)                             return $e['meta'];
	}
	return ['desc' => []];
}
function tags($desc)  { return array_map(fn($d) => $d['tag'], $desc); }
function finde($desc, $tag) { foreach($desc as $d) if($d['tag'] === $tag) return $d; return null; }

echo "\nFormen\n";

$m = ernte("/*@\n   Ein Absatz.\n\n   Noch einer.\n@*/");
pruefe('Block ohne Kopf -> genau ein desc:text', tags($m['desc']) === ['desc:text']);
pruefe('... Text ohne Einrueckung, Absaetze bleiben', ($m['desc'][0]['text'] ?? '') === "Ein Absatz.\n\nNoch einer.");

$m = ernte("/*@\n   Vorspann.\n\nfunction::\n   Tut etwas.\n   Zweite Zeile.\n@*/");
pruefe('Vorspann + Kopf -> desc:text, desc:function', tags($m['desc']) === ['desc:text', 'desc:function'], implode(', ', tags($m['desc'])));
pruefe('... Koerper ausgerueckt', (finde($m['desc'], 'desc:function')['text'] ?? '') === "Tut etwas.\nZweite Zeile.");

$m = ernte("/*@\ntricky:: Achtung, Reihenfolge.\n@*/");
pruefe('Einzeiler Text', (finde($m['desc'], 'desc:tricky')['text'] ?? '') === 'Achtung, Reihenfolge.');

$m = ernte("/*@\ndelivers:: #plugin\n@*/");
$d = finde($m['desc'], 'desc:delivers');
pruefe('Verweis #name -> rdf:resource, kein Text', ($d['resource'] ?? null) === '#plugin' && !isset($d['text']), json_encode($d));

$m = ernte("/*@\ndelivers:: <http://www.w3.org/2001/XMLSchema#boolean>\n@*/");
pruefe('Verweis <uri> -> ohne Klammern', (finde($m['desc'], 'desc:delivers')['resource'] ?? null) === 'http://www.w3.org/2001/XMLSchema#boolean');

$m = ernte("/*@\ndelivers:: #plugin\n   Was danach kommt.\n@*/");
pruefe('Verweis + Text danach -> Verweis und eigenes desc:text', tags($m['desc']) === ['desc:delivers', 'desc:text']);

$m = ernte("/*@\ndcterms:title:: Probe\n@*/");
pruefe('Praefix dcterms: geht durch', (finde($m['desc'], 'dcterms:title')['text'] ?? '') === 'Probe');

$m = ernte("/*@\ncreator:: Jemand\n@*/");
pruefe('Dublin-Core-Name selbst (creator -> dcterms:creator)', tags($m['desc']) === ['dcterms:creator'], implode(', ', tags($m['desc'])));

$m = ernte("/*@\nfunc:: Kurzname\n@*/");
pruefe('Kurzname laeuft ueber DESC_KEYS (func -> desc:function)', tags($m['desc']) === ['desc:function']);

echo "\nKollisionen - muessen Fliesstext bleiben\n";

$m = ernte("/*@\n   Siehe plugin::col() und Interface_ns::attribute().\n@*/");
pruefe('"plugin::col()" mitten in der Zeile', tags($m['desc']) === ['desc:text']);

$m = ernte("/*@\n   Beispiel:\n   <access>\n   <final>\n@*/");
pruefe('"<access>" / "<final>" allein auf einer Zeile', tags($m['desc']) === ['desc:text']);

$m = ernte("/*@\nfoo:bar:: gibt es nicht\n@*/");
pruefe('unbekanntes Praefix foo: -> kein Kopf', tags($m['desc']) === ['desc:text']);

$m = ernte("/*@\n@see: anderswo\n@*/");
pruefe('"@see:" ist kein DESC_KEY -> Fliesstext', tags($m['desc']) === ['desc:text']);

echo "\nAlte Form und Ueberschneidung\n";

$m = ernte("/*@\n   Text.\n@function: tut etwas\n@*/");
pruefe('"@function:" im Block ist Kopf - und NICHT doppelt', tags($m['desc']) === ['desc:text', 'desc:function'], implode(', ', tags($m['desc'])));
pruefe('... der Fliesstext enthaelt die Zeile nicht', false === strpos(finde($m['desc'], 'desc:text')['text'] ?? '', '@function'));

$m = ernte("/**\n* @title: Alt\n* @function: wie immer\n*/");
pruefe('Form 2 ausserhalb der Bloecke unveraendert', tags($m['desc']) === ['dcterms:title', 'desc:function']);

echo "\nParameter\n";

$m = ernte("/*@\nfunction:: Legt an.\nparam:: path = das Verzeichnis\n@*/", 'make($path)');
$p = $m['params'][0]['desc'] ?? [];
pruefe('"param:: path = ..." landet am Parameter path', ($p[0]['text'] ?? '') === 'das Verzeichnis', json_encode($p, JSON_UNESCAPED_UNICODE));
pruefe('... und nicht mehr an der Methode', null === finde($m['desc'], 'desc:parameter'));

echo "\nMeldungen - erlaubt, aber nicht still\n";

$logger_class->zeilen = [];
$m = ernte("/*@\nfucntion:: vertippt\n@*/");
pruefe('unbekannter Name wird desc:<name>', tags($m['desc']) === ['desc:fucntion']);
pruefe('... und gemeldet', (bool) array_filter($logger_class->zeilen, fn($z) => false !== strpos($z, 'fucntion')));

$logger_class->zeilen = [];
$m = ernte("/*@\neffect::\n@*/");
pruefe('Kopf ohne Inhalt -> uebersprungen', [] === $m['desc']);
pruefe('... und gemeldet', (bool) array_filter($logger_class->zeilen, fn($z) => false !== strpos($z, 'ohne Inhalt')));

$logger_class->zeilen = [];
ernte("/*@\ndelivers:: #plugin\ncolumns:: file, number\n@*/");
pruefe('bekannte freie Namen (delivers, columns) -> keine Meldung', [] === $logger_class->zeilen, implode(' | ', $logger_class->zeilen));

echo "\nAngaben in der Klammer - xml:lang\n";

$m = ernte("/*@\nfunction(lang=de):: Legt an.\n@*/");
$d = finde($m['desc'], 'desc:function');
pruefe('Einzeiler mit lang -> attrib xml:lang', ($d['attrib']['xml:lang'] ?? null) === 'de' && ($d['text'] ?? '') === 'Legt an.', json_encode($d, JSON_UNESCAPED_UNICODE));

$m = ernte("/*@\nfunction(lang=de)::\n   Legt an.\nfunction(lang=en)::\n   Creates.\n@*/");
$sprachen = array_map(fn($d) => ($d['attrib']['xml:lang'] ?? '-') . ':' . $d['text'], $m['desc']);
pruefe('zwei Sprachen nebeneinander -> zwei Eintraege', $sprachen === ['de:Legt an.', 'en:Creates.'], implode(' | ', $sprachen));

$m = ernte("/*@\ntricky(lang=\"en\")::\n   careful\n@*/");
pruefe('Wert in Anfuehrungszeichen', (finde($m['desc'], 'desc:tricky')['attrib']['xml:lang'] ?? null) === 'en');

$m = ernte("/*@\nfunction(lang=de-DE):: Text\n@*/");
pruefe('Region de-DE ist eine Sprachkennung', (finde($m['desc'], 'desc:function')['attrib']['xml:lang'] ?? null) === 'de-DE');

$m = ernte("/*@\nfunction:: Ohne Klammer.\n@*/");
pruefe('ohne Klammer: kein attrib (Eintrag wie vorher)', !array_key_exists('attrib', finde($m['desc'], 'desc:function') ?? []));

$m = ernte("/*@\nparam(lang=en):: path = the directory\n@*/", 'make($path)');
$p = $m['params'][0]['desc'][0] ?? [];
pruefe('param(lang=en) -> Sprache reist mit zum Parameter', ($p['attrib']['xml:lang'] ?? null) === 'en' && ($p['text'] ?? '') === 'the directory', json_encode($p));

$logger_class->zeilen = [];
$m = ernte("/*@\ndelivers(lang=de):: #plugin\n@*/");
$d = finde($m['desc'], 'desc:delivers');
pruefe('lang an einem Verweis -> weggelassen', ($d['resource'] ?? null) === '#plugin' && !isset($d['attrib']), json_encode($d));
pruefe('... und gemeldet', (bool) array_filter($logger_class->zeilen, fn($z) => false !== strpos($z, 'keine Sprache')));

$m = ernte("/*@\ndelivers(lang=de):: #plugin\n   Ein Plugin.\n@*/");
pruefe('Verweis + Text: lang geht an den Text', (finde($m['desc'], 'desc:text')['attrib']['xml:lang'] ?? null) === 'de' && !isset(finde($m['desc'], 'desc:delivers')['attrib']));

$logger_class->zeilen = [];
$m = ernte("/*@\nfunction(farbe=rot, lang=de):: Text\n@*/");
$d = finde($m['desc'], 'desc:function');
pruefe('unbekannte Angabe weg, bekannte bleibt', ($d['attrib'] ?? null) === ['xml:lang' => 'de'], json_encode($d['attrib'] ?? null));
pruefe('... unbekannte gemeldet', (bool) array_filter($logger_class->zeilen, fn($z) => false !== strpos($z, 'farbe')));

$logger_class->zeilen = [];
$m = ernte("/*@\nfunction(lang=de_DE):: Text\n@*/");
pruefe('ungueltige Sprachkennung de_DE -> weggelassen', !isset(finde($m['desc'], 'desc:function')['attrib']));
pruefe('... und gemeldet', (bool) array_filter($logger_class->zeilen, fn($z) => false !== strpos($z, 'keine Sprachkennung')));

$m = ernte("/*@\n   Aufruf wie make(path):: nicht am Anfang.\n@*/");
pruefe('"make(path)::" mitten im Satz bleibt Fliesstext', tags($m['desc']) === ['desc:text']);

echo "\nBestand - zum Lesen\n";
foreach(['PlugIn/plugin_filter.php', 'PlugIn/folder/plugin_folder.php'] as $datei)
{
	if(!is_file($datei)) { echo "  ($datei fehlt)\n"; continue; }
	echo "  $datei\n";
	foreach(PHP_Ast_Scan::scan(file_get_contents($datei), $datei, false) as $e)
	{
		$wo = preg_replace('/\s+/', ' ', trim(strtok($e['tag'], '{')));
		foreach(($e['meta']['desc'] ?? []) as $d)
			printf("    %-38s %-16s %s\n", substr($wo, 0, 38), $d['tag'],
				isset($d['resource']) ? '-> ' . $d['resource'] : substr(strtok($d['text'], "\n"), 0, 48));
		foreach(($e['meta']['params'] ?? []) as $par)
			foreach(($par['desc'] ?? []) as $d)
				printf("    %-38s %-16s %s\n", substr('  $' . $par['name'], 0, 38), $d['tag'], substr(strtok($d['text'], "\n"), 0, 48));
	}
}

printf("\n  %d in Ordnung, %d rot\n", $ok, $rot);
