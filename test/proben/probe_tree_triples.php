<?PHP
/**
*	Was steht in einem geladenen Dokument als Tripel drin?
*
*	Laedt ein qPortal-Dokument eigenstaendig (ohne index.php, ohne Server) und gibt
*	die tree-Knoten als Aussagen aus: Subjekt, rdf:type, und jedes Attribut als
*	Praedikat/Objekt. Kein Umwandeln, keine zweite Ablage — das ist, was das Parsen
*	ohnehin hinterlaesst.
*
*	Aufruf:  php -d error_reporting=E_ERROR test/proben/probe_tree_triples.php [datei]
*/

require_once(__DIR__ . '/../bootstrap.php');

$datei = $argv[1] ?? 'template/xml.xml';
$pfad  = __DIR__ . '/../../' . $datei;

if(!is_file($pfad)) { fwrite(STDERR, "nicht gefunden: $pfad\n"); exit(1); }

$T = 'http://www.trscript.de/tree#';

$tree = new xml_ns();
$tree->setNewTree($datei);
$tree->load_Stream(file_get_contents($pfad), 0, 'XML');

echo "Dokument: $datei\n";
echo str_repeat('=', 78) . "\n";

$gesamt = 0;

foreach(array('first', 'tree', 'final') as $typ)
{
	$knoten = $tree->collect_nodes($T . $typ);

	printf("\n%s%s  —  %d Knoten\n", $T, $typ, count($knoten));
	echo str_repeat('-', 78) . "\n";

	foreach($knoten as $k)
	{
		$subjekt = $k->position_stamp();
		$gesamt++;

		printf("  <%s>\n", $subjekt);
		printf("      %-46s %s\n", 'rdf:type', $k->full_URI());

		foreach($k->get_ns_attribute() as $uri => $wert)
			printf("      %-46s \"%s\"\n", $uri, $wert);
	}
}

echo "\n" . str_repeat('=', 78) . "\n";
printf("%d Subjekte. Jedes traegt seinen Typ als URI und seine Attribute als Praedikate.\n", $gesamt);

/* Und die Gegenprobe: die Namen sind der Unterschied zwischen den Exemplaren,
*  der Typ ist der Unterschied zwischen den Sorten. */
$namen = array();
foreach($tree->collect_nodes($T . 'tree') as $k)
	$namen[] = $k->get_ns_attribute($T . 'name');

echo "\ntree:name der tree-Knoten (die Exemplare unterscheiden sich):\n  "
   . implode(', ', array_filter($namen)) . "\n";
?>
