<?PHP
/**
*	Der Kuehlschrank — das erste tree-Dokument mit eigenem Namensraum.
*
*	Zeigt drei Dinge nebeneinander:
*	  1. WELCHE Namen sich eingetragen haben und welche nicht,
*	  2. was der Knoten selbst darueber sagt (has_coined),
*	  3. was eine Abfrage von den Tuerschildern sieht.
*
*	⚠ Die Praegung laesst sich NICHT dadurch pruefen, dass man den Namen nachschlaegt:
*	get_Object_of_Namespace liefert fuer jeden Namen ein Objekt — fuer einen
*	ungepraegten einen generischen Interface_node aus alt_namespace_factory. Ein Test,
*	der nur auf is_object() sieht, sagt bei JEDEM Namen ja, auch bei einem, der nirgends
*	steht. Darum laeuft unten eine Gegenprobe mit, die NEIN sagen muss.
*
*	Aufruf:  php -d error_reporting=E_ERROR test/proben/probe_fridge.php
*/

require_once(__DIR__ . '/../bootstrap.php');

$datei = 'template/fridge/fridge.xml';
$pfad  = __DIR__ . '/../../' . $datei;
$NS    = 'https://qportal-project.org/fridge';

if(!is_file($pfad))
{
	echo "Dokument fehlt: $datei\n";
	exit(1);
}

$tree = new xml_semantic();
$tree->setNewTree($datei);
$tree->load_Stream(file_get_contents($pfad), 0, 'XML');

echo "Baum: $datei\n" . str_repeat('=', 78) . "\n";

/* ------------------------------------------------------------------ Praegung */

$erwartet_ja = array(
	'fridge'            => 'tree:name auf final',
	'contains_rst'      => 'tree:name auf tree',
	'power_consumption' => 'tree:name auf tree',
	'temperature_rst'   => 'tree:name auf tree',
	'put_in'            => 'tree:name auf tree',
	'take_out'          => 'tree:name auf tree');

/* Gegenprobe: steht in keinem Dokument. Sagt der Test hier ja, misst er nichts. */
$erwartet_nein = array('defrost', 'banane');

echo "\nPraegung — gepraegt heisst: der Prototyp erbt die Klasse seines Traegers\n";
echo str_repeat('-', 78) . "\n";
printf("  %-20s %-16s %-9s %s\n", 'Name', 'Prototyp', 'erwartet', 'woher');

$ok_n = $rot = 0;

foreach($erwartet_ja as $name => $woher)
{
	$k  = ($o = $tree->get_Object_of_Namespace($NS . '#' . $name)) ? get_class($o) : '-';
	$ok = ($k !== 'Interface_node' && $k !== '-');

	$ok ? $ok_n++ : $rot++;
	printf("  %-20s %-16s %-9s %s\n", $name, $k, $ok ? 'ja  ok' : 'ja  ROT', $woher);
}

foreach($erwartet_nein as $name)
{
	$k  = ($o = $tree->get_Object_of_Namespace($NS . '#' . $name)) ? get_class($o) : '-';
	$ok = ($k === 'Interface_node' || $k === '-');

	$ok ? $ok_n++ : $rot++;
	printf("  %-20s %-16s %-9s %s\n", $name, $k, $ok ? 'nein ok' : 'nein ROT',
	       $name === 'defrost' ? 'blanker Name — nur ein Flurstueck' : 'steht nirgends');
}

printf("\n  %d in Ordnung / %d rot\n", $ok_n, $rot);

/* --------------------------------------------------------- Der Namensraum */

/* Der Namensraum soll von einem owl:Ontology-Knoten erklaert werden, nicht
*  beilaeufig durch seine Verwendung in der Wurzel entstehen. Der Unterschied
*  steht im nativen Knoten: erklaert = die Vorlage aus der rdfs-Fabrik
*  (OWL_Ontology::event_initiated), beilaeufig = ein generischer Interface_node
*  aus alt_namespace_factory, und attrib bleibt dann NULL statt leerer Tabelle. */

echo "\nDer Namensraum — erklaert oder beilaeufig entstanden?\n";
echo str_repeat('-', 78) . "\n";

$fw = $tree->namespace_frameworks[$NS] ?? null;

if(!is_array($fw))
	echo "  kein Eintrag fuer $NS\n";
else
{
	$nativ = is_object($fw['nativ'] ?? null) ? get_class($fw['nativ']) : '-';

	printf("  nativ   %-16s %s\n", $nativ,
	       $nativ === 'Interface_node' || $nativ === '-'
	       ? 'ROT — beilaeufig entstanden, owl:Ontology fehlt'
	       : 'ok  — vom owl:Ontology-Knoten erklaert');

	printf("  node    %-16s %s\n",
	       is_array($fw['node']) ? count($fw['node']) . ' Eintraege' : '-',
	       is_array($fw['node']) ? implode(', ', array_keys($fw['node'])) : '');

	printf("  attrib  %-16s %s\n",
	       is_array($fw['attrib'] ?? null) ? count($fw['attrib']) . ' Eintraege' : 'NULL',
	       is_array($fw['attrib'] ?? null) ? '' : 'ROT — die Tabelle wurde nie angelegt');
}

/* ------------------------------------------------------- Der Schalter am Knoten */

echo "\nDer Schalter — has_coined() an jedem tree/final-Knoten\n";
echo str_repeat('-', 78) . "\n";
printf("  %-10s %-24s %s\n", 'gepraegt', 'tree:name', 'Sorte');

foreach(array('http://www.trscript.de/tree#final', 'http://www.trscript.de/tree#tree') as $sorte)
	foreach($tree->collect_nodes($sorte) as $n)
	{
		$name = $n->get_ns_attribute('http://www.trscript.de/tree#name');
		printf("  %-10s %-24s %s\n",
		       $n->has_coined() ? 'ja' : 'nein',
		       ($name === false ? '(keiner)' : $name),
		       substr($sorte, strrpos($sorte, '#') + 1));
	}

/* Gegenprobe an einem Dokument, das nur Flur ist: dort darf KEIN Knoten
*  gepraegt haben — kein Name im Bestand nennt einen Namensraum. */
$flur = new xml_semantic();
$flur->setNewTree('template/xml.xml');
$flur->load_Stream(file_get_contents(__DIR__ . '/../../template/xml.xml'), 0, 'XML');

$g = $u = 0;

foreach(array('http://www.trscript.de/tree#final', 'http://www.trscript.de/tree#tree') as $sorte)
	foreach($flur->collect_nodes($sorte) as $n)
		$n->has_coined() ? $g++ : $u++;

printf("\n  Gegenprobe template/xml.xml (reiner Flur): %d gepraegt / %d ungepraegt\n", $g, $u);

/* ---------------------------------------------------------------- Die Schilder */

ConnectionProfile::set_collection(array(
	'hier' => array('type' => 'qportal', 'address' => '', 'source' => $datei)));
SearchingModelObject::set_config(array('sparql' => array('use' => 'hier')));

function zeig($tree, $titel, $ausdruck)
{
	$m = $tree->seek_by_model('sparql');

	echo "\n" . $titel . "\n" . str_repeat('-', 78) . "\n";

	try { $m->query($ausdruck); }
	catch (Exception $e) { echo "  ! " . $e->getMessage() . "\n"; return; }

	$loesungen = $m->solutions();

	if(empty($loesungen)) { echo "  (leer)\n"; return; }

	foreach($loesungen as $z)
	{
		$zelle = array();
		foreach($z as $sp => $w)
			$zelle[] = $sp . '=' . (is_object($w) ? '<' . $w->position_stamp() . '>' : (string) $w);
		echo '  ' . implode('  ', $zelle) . "\n";
	}

	printf("\n  %d Loesungen\n", count($loesungen));
}

$P = "PREFIX tree: <http://www.trscript.de/tree#>\n"
   . "PREFIX desc: <http://www.trscript.de/2026/pedl-desc#>\n"
   . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n";

zeig($tree, '(1) Wo stehe ich, und was ist hier moeglich?',
     $P . "SELECT ?zimmer ?tut WHERE { ?p tree:name ?zimmer . ?p desc:function ?tut }");

zeig($tree, '(2) Was liefert einen Recordset — und in welcher Form?',
     $P . "SELECT ?zimmer ?spalten WHERE { ?p desc:delivers \"rst\" .
                                           ?p tree:name ?zimmer .
                                           ?p desc:columns ?spalten }");

zeig($tree, '(3) Was wirkt, statt zu liefern?',
     $P . "SELECT ?zimmer ?wirkung WHERE { ?p desc:delivers \"nichts\" .
                                           ?p tree:name ?zimmer .
                                           ?p desc:effect ?wirkung }");

zeig($tree, '(4) Was erwartet Angaben von mir?',
     $P . "SELECT ?zimmer ?erwartet WHERE { ?p desc:parameter ?erwartet .
                                            ?p tree:name ?zimmer }");

zeig($tree, '(5) Woran stoesst man sich?',
     $P . "SELECT ?zimmer ?warnung WHERE { ?p desc:tricky ?warnung . ?p tree:name ?zimmer }");

zeig($tree, '(6) Gegenprobe: ein Praedikat, das es nicht gibt, muss leer bleiben',
     $P . "SELECT ?p WHERE { ?p desc:gibtsnicht ?x }");

/* ⚠ Offen, und der Schalter macht es sichtbar: das Schild am UNGEPRAEGTEN Zimmer
*  (defrost) steht in Abfrage 1 und 3 mit drin. Es ist oertlich gemeint — der
*  Knoten weiss das (has_coined() = nein), die Abfrage fragt ihn noch nicht. */
?>
