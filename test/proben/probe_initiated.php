<?PHP
/**  Sonde 2: feuert event_initiated() auf einem ATTRIBUTKNOTEN, und was steht dann bereit?
*    Bestand unveraendert - der Prototyp wird nur fuer diesen Lauf untergeschoben. */
require_once(__DIR__ . '/../bootstrap.php');

$GLOBALS['PROBE'] = array();

class Probe_attr extends Interface_node
{
	function &get_Instance(){ $o = new Probe_attr($this->type, $this->namespace); return $o; }

	function event_initiated()
	{
		$traeger = $this->getRefprev();
		$GLOBALS['PROBE'][] = array(
			'attribut'  => $this->full_URI(),
			'nodetype'  => $this->get_NodeType(),
			'wert'      => $this->getdata(),
			'traeger'   => is_object($traeger) ? $traeger->full_URI() : '(keiner)',
			'tr_attr'   => is_object($traeger) ? count($traeger->get_ns_attribute()) : -1,
			'parser'    => is_object($this->get_parser()) ? 'ja' : 'nein',
		);
	}
}

$xml = <<<XML
<?xml version='1.0' encoding="UTF-8"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema">
  <rdf:Property rdf:about="http://example.org/p#hatMiete">
     <rdfs:domain rdf:resource="http://example.org/p#Wohnung"/>
     <rdfs:range  rdf:resource="http://example.org/p#Betrag"/>
  </rdf:Property>
</rdf:RDF>
XML;

$RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';

$tree = new xml_ns();
// Durchlauf 1: baut den Namensraum-Rahmen regulaer auf
$tree->setNewTree('rahmen');
$tree->load_Stream($xml, 0, "XML");

$vorher = count($GLOBALS['PROBE']);
echo "Durchlauf 1 (Bestandsklassen): $vorher Meldungen\n\n";

// Prototyp fuer rdf:resource austauschen - namespace_frameworks ist global
$tree->namespace_frameworks[$RDF]['node']['resource'] = new Probe_attr('resource', $RDF);

// Durchlauf 2: derselbe Text, eigener Slot
$tree->setNewTree('sonde');
$tree->load_Stream($xml, 0, "XML");

if(!count($GLOBALS['PROBE']))
	echo "event_initiated() ist auf keinem Attributknoten gefeuert.\n";
else
	foreach($GLOBALS['PROBE'] as $n => $p)
		printf("[%d] %-46s = %-30s Traeger=%-46s NodeType=%s Attr.am.Traeger=%d Parser=%s\n",
		       $n, $p['attribut'], '"'.$p['wert'].'"', $p['traeger'], $p['nodetype'], $p['tr_attr'], $p['parser']);
