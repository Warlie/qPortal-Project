<?PHP
/**
*	SPARQL gegen einen geladenen qPortal-Baum — der ganze Weg an einem Stueck.
*
*	Ausdruck -> Mealy-Automat -> Struktur -> Auswertung ueber die Indexschicht.
*	Ohne Server, ohne Gegenstelle, ohne Fuseki.
*
*	Aufruf:  php -d error_reporting=E_ERROR test/proben/probe_sparql_tree.php [datei]
*/

require_once(__DIR__ . '/../bootstrap.php');

$datei = $argv[1] ?? 'template/xml.xml';
$pfad  = __DIR__ . '/../../' . $datei;

$tree = new xml_ns();
$tree->setNewTree($datei);
$tree->load_Stream(file_get_contents($pfad), 0, 'XML');

ConnectionProfile::set_collection(array(
	'hier' => array('type' => 'qportal', 'address' => '', 'source' => $datei)));
SearchingModelObject::set_config(array('sparql' => array('use' => 'hier')));

function zeig($tree, $titel, $ausdruck)
{
	$m = $tree->seek_by_model('sparql');

	echo "\n" . $titel . "\n" . str_repeat('-', 78) . "\n";
	echo trim(preg_replace('/\s+/', ' ', $ausdruck)) . "\n\n";

	try { $m->query($ausdruck); }
	catch (Exception $e) { echo "  ! " . $e->getMessage() . "\n"; return; }

	$loesungen = $m->solutions();

	if(empty($loesungen)) { echo "  (leer)\n"; return; }

	$spalten = array_keys($loesungen[0]);
	printf("  %s\n", implode('   ', array_map(fn($s) => str_pad($s, 22), $spalten)));

	foreach($loesungen as $z)
	{
		$zelle = array();
		foreach($z as $w)
			$zelle[] = str_pad(is_object($w) ? '<' . $w->position_stamp() . '>' : (string) $w, 22);
		echo '  ' . implode('   ', $zelle) . "\n";
	}

	printf("\n  %d Loesungen\n", count($loesungen));
}

$P = "PREFIX tree: <http://www.trscript.de/tree#>\n"
   . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n";

echo "Baum: $datei\n" . str_repeat('=', 78) . "\n";

zeig($tree, '(1) Alle tree-Knoten mit ihrem Namen',
     $P . "SELECT ?t ?name WHERE { ?t rdf:type tree:tree . ?t tree:name ?name }");

zeig($tree, '(2) Nur die mit einer Sicherheitsstufe — zwei Attribute, UND-verknuepft',
     $P . "SELECT ?name ?stufe WHERE { ?t rdf:type tree:tree . ?t tree:name ?name . ?t tree:securitylevel ?stufe }");

zeig($tree, '(3) Fester Wert im Objekt: welcher Knoten heisst "login"?',
     $P . "SELECT ?t ?src WHERE { ?t tree:name \"login\" . ?t tree:src ?src }");

zeig($tree, '(3b) "realms" hat kein src — die UND-Verknuepfung muss leer liefern',
     $P . "SELECT ?t ?src WHERE { ?t tree:name \"realms\" . ?t tree:src ?src }");

zeig($tree, '(4) Ueber die Sorten hinweg: was traegt einen sector?',
     $P . "SELECT ?name ?sector WHERE { ?t tree:sector ?sector . ?t tree:name ?name }");

zeig($tree, '(5) Der final-Knoten — eine andere Sorte, dieselbe Frage',
     $P . "SELECT ?name ?src WHERE { ?t rdf:type tree:final . ?t tree:name ?name . ?t tree:src ?src }");
?>
