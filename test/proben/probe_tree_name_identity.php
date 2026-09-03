<?PHP
/**
*	Zeigt, wann tree:name ein Bezeichner ist und wann nur ein Flurname.
*
*	tree:name ist eine subPropertyOf von rdf:ID (gesagt im Registrierungsbogen,
*	getan in classes/ns/tree/tree_name.php). Es traegt einen Knoten unter seinem
*	Namen in den Namensraum ein — aber nur unter zwei Bedingungen zugleich:
*
*	    1. der TRAEGER ist tree oder final (nicht content, param, remote, ...)
*	    2. der WERT nennt selbst einen Namensraum ("praefix;Name" oder voller URI)
*
*	Die zweite Bedingung ist die, die alles Bestehende in Ruhe laesst: von 716
*	Namen auf tree/final traegt heute keiner einen Namensraum.
*
*	Diese Sonde prueft nicht, sie ZEIGT. Gelesen wird die Ausgabe.
*
*	Aufruf:  php -d error_reporting=E_ERROR test/proben/probe_tree_name_identity.php
*/

require_once(__DIR__ . '/../bootstrap.php');

const TREE_NS = 'http://www.trscript.de/tree';
const FRIDGE  = 'https://qportal-project.org/fridge';

function ueberschrift($t)
{
	echo "\n" . $t . "\n" . str_repeat('=', 78) . "\n";
}

/* Ein Dokument, das jede Kombination einmal enthaelt. */
$doc = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"
     . '<indextree xmlns="' . TREE_NS . '" xmlns:fridge="' . FRIDGE . '" >' . "\n"
     . '  <final name="home" value="blanker Name auf final" >' . "\n"
     . '    <tree name="login"                value="blanker Name auf tree" />' . "\n"
     . '    <tree name=".edit"                value="fuehrender Punkt" />' . "\n"
     . '    <tree name="#anhang"              value="Raute ohne Namensraum davor" />' . "\n"
     . '    <tree name="fridge;strom"         value="Praefixform auf tree" />' . "\n"
     . '    <tree name="' . FRIDGE . '#contains_rst" value="voller URI auf tree" />' . "\n"
     . '    <content name="fridge;inhalt"     value="Praefixform auf content" />' . "\n"
     . '    <param   name="fridge;param"      value="Praefixform auf param" />' . "\n"
     . '    <remote  name="fridge;fern"       value="Praefixform auf remote" />' . "\n"
     . '  </final>' . "\n"
     . '  <final name="fridge;geraet" value="Praefixform auf final" />' . "\n"
     . '</indextree>' . "\n";

$faelle = array(
	array('final',   'home',                     'blank'),
	array('tree',    'login',                    'blank'),
	array('tree',    '.edit',                    'blank, fuehrender Punkt'),
	array('tree',    '#anhang',                  'Raute, nichts davor'),
	array('tree',    'fridge;strom',             'Praefix'),
	array('tree',    FRIDGE . '#contains_rst',   'voller URI'),
	array('content', 'fridge;inhalt',            'Praefix'),
	array('param',   'fridge;param',             'Praefix'),
	array('remote',  'fridge;fern',              'Praefix'),
	array('final',   'fridge;geraet',            'Praefix'),
);

$tree = new xml_semantic();

/* Erst ein Dokument OHNE Namen laden, das beide Namensraeume deklariert. Damit
*  stehen alle Element-Prototypen im Register (tree:param, tree:tree, ...), und
*  der Schnappschuss danach ist die Grundlinie. Sonst zaehlt man Prototypen als
*  Eintraege - namespace_frameworks ist global ueber alle Baeume. */
$leer = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"
     . '<indextree xmlns="' . TREE_NS . '" xmlns:fridge="' . FRIDGE . '" >' . "\n"
     . '  <tree value="ohne Namen" /><content value="ohne Namen" />' . "\n"
     . '  <param value="ohne Namen" /><remote value="ohne Namen" />' . "\n"
     . '  <final value="ohne Namen" />' . "\n"
     . '</indextree>' . "\n";

$tree->setNewTree('grundlinie');
$tree->load_Stream($leer, 0, 'XML');

function schnappschuss($tree)
{
	$out = array();
	foreach($tree->namespace_frameworks as $ns => $eintrag)
	{
		if(!isset($eintrag['node']) || !is_array($eintrag['node'])) continue;
		foreach(array_keys($eintrag['node']) as $qname) $out[$ns . '#' . $qname] = true;
	}
	return $out;
}

$vorher = schnappschuss($tree);

$tree->setNewTree('probe_tree_name');
$tree->load_Stream($doc, 0, 'XML');

$neu = array_diff_key(schnappschuss($tree), $vorher);

ueberschrift('tree:name — was sich eintraegt und was nicht');

echo "\n    Das Dokument deklariert  xmlns:fridge = " . FRIDGE . "\n\n";
printf("    %-9s %-34s %-24s %s\n", 'Traeger', 'name=', 'Form', 'eingetragen als');
echo '    ' . str_repeat('-', 100) . "\n";

foreach($faelle as $f)
{
	list($traeger, $wert, $form) = $f;

	/* der lokale Name, unter dem es stehen wuerde */
	$q = $wert;
	if(false !== ($p = strpos($q,';'))) $q = substr($q,$p + 1);
	if(false !== ($p = strrpos($q,'#'))) $q = substr($q,$p + 1);

	$wo = '—';
	foreach(array_keys($neu) as $uri)
		if(substr($uri, -(strlen($q) + 1)) === '#' . $q) $wo = $uri;

	printf("    %-9s %-34s %-24s %s\n", $traeger, $wert, $form, $wo);
}

echo "\n    Eingetragen wird nur, wo BEIDES stimmt: Traeger tree/final UND ein\n";
echo "    Wert, der selbst einen Namensraum nennt.\n";

/* --------------------------------------------------------- Navigation bleibt */

ueberschrift('Die Navigation ist unberuehrt');

echo "\n    TREE_tree::event_message_in:88 vergleicht den ROHEN Attributwert gegen\n";
echo "    den Kopf der Namensliste. Was dort steht, hat sich nicht geaendert:\n\n";

foreach($tree->collect_nodes(TREE_NS . '#tree') as $knoten)
	printf("        %-34s %s\n",
		var_export($knoten->get_ns_attribute(TREE_NS . '#name'), true),
		var_export($knoten->get_ns_attribute(TREE_NS . '#value'), true));

/* ------------------------------------------------------------ Der Bestand */

ueberschrift('Und was das fuer den Bestand heisst');

echo <<<TEXT

    Gemessen ueber template/**/*.xml, Kommentare entfernt:

        716 Namen auf tree/final/first
        676 blank  ·  40 mit fuehrendem Punkt  ·  0 Raute  ·  0 Praefix  ·  0 URI

    Kein einziger davon nennt einen Namensraum. Nach dem Einbau traegt sich also
    nichts ein, kein Register waechst, keine Duplikat-Warnung faellt an. Der
    Bezeichner erscheint erst dort, wo jemand ihn hinschreibt.


TEXT;
