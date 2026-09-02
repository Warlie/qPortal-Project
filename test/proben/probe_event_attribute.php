<?PHP
/**  Sonde: feuert event_attribute()?  Nichts am Bestand geaendert. */
require_once(__DIR__ . '/../bootstrap.php');

$xml = <<<XML
<?xml version='1.0' encoding="UTF-8"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema"
         xmlns="http://www.trscript.de/tree">
  <rdf:Property rdf:about="http://example.org/p#hatMiete">
     <rdfs:domain rdf:resource="http://example.org/p#Wohnung"/>
     <rdfs:range  rdf:resource="http://example.org/p#Betrag"/>
  </rdf:Property>
</rdf:RDF>
XML;

$tree = new xml_ns();
$tree->setNewTree('probe');
$tree->load_Stream($xml, 0, "XML");

$RDFS = 'http://www.w3.org/2000/01/rdf-schema#';

$gesamt = 0; $mit_liste = 0;
foreach(array($RDFS.'domain', $RDFS.'range', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property') as $uri)
{
	$treffer = $tree->collect_nodes($uri, null, null, null, -1, -1);
	foreach($treffer as $n)
	{
		$gesamt++;
		$n_liste = count($n->check_list);
		$n_out   = count($n->get_out_ref());
		if($n_liste > 0) $mit_liste++;
		printf("%-52s check_list=%d  way_out=%d\n", $n->full_URI(), $n_liste, $n_out);
	}
}
printf("\nKnoten geprueft: %d   davon mit nichtleerer check_list: %d\n", $gesamt, $mit_liste);
