<?PHP
/**
*	Haelt das Vokabulardokument und TreeEngine::build_up() aneinander.
*
*	Seit das Vokabular ein Dokument ist, sind die beiden Haelften getrennt - und
*	genau dort koennen sie auseinanderlaufen, ohne dass es jemand merkt:
*	use_ns_def_strict(true) WIRFT NICHT bei einem ungepraegten Tag, es faellt auf
*	einen generischen Interface_node zurueck. Die Seite rendert weiter, die
*	Bedeutung ist weg. Diese Sonde ist der Blick auf genau diese Naht.
*
*	Geprueft wird zweierlei:
*
*	  1. Jeder Name, den das Dokument mit rdf:ID praegt, ist danach im Namensraum
*	     des Bogens registriert UND traegt eine echte Klasse, keinen Interface_node.
*	  2. Jeder Tag, den der in PHP verbliebene Bindungsblock noch BENUTZT
*	     (System, System.Parser, ... Class_Collection, Class_Instance), wird vom
*	     Dokument auch gepraegt. Wer im Dokument einen Namen streicht, den PHP noch
*	     schreibt, faellt hier auf - nicht erst im Betrieb.
*
*	Aufruf:  php -d error_reporting=E_ERROR test/proben/probe_registry_vocab.php
*/

require_once(__DIR__ . '/../bootstrap.php');

$REG   = '@registry_surface_system';
$DOK   = __DIR__ . '/../../ontologies/registry_surface.owl';
$QUELL = __DIR__ . '/../../classes/TreeEngine.php';

/* --- 1. Was das Dokument zu praegen behauptet --- */
$dok = file_get_contents($DOK);
preg_match_all('/<([A-Za-z_][\w:.\-]*)\s+rdf:ID="([^"]+)"/', $dok, $m, PREG_SET_ORDER);
$gepraegt = array();
foreach($m as $t) $gepraegt[$t[2]] = $t[1];

/* --- 2. Welche Bogen-Tags der Bindungsblock noch benutzt --- */
$src  = file_get_contents($QUELL);
$body = substr($src, strpos($src, 'private function build_up()'));
preg_match_all('/tag_open\(\$this,\s*"([^"]+)"/', $body, $m2);
$benutzt = array();
foreach($m2[1] as $tag)
	if(false === strpos($tag, ':'))   // ohne Prefix = im Namensraum des Bogens
		$benutzt[$tag] = true;

/* --- 3. Bogen bauen wie build_up() und Dokument laden --- */
$ns = array(
	'xmlns'      => $REG,
	'xmlns:owl'  => 'http://www.w3.org/2002/07/owl',
	'xmlns:rdf'  => 'http://www.w3.org/1999/02/22-rdf-syntax-ns',
	'xmlns:rdfs' => 'http://www.w3.org/2000/01/rdf-schema',
	'xmlns:pedl' => 'http://www.w3.org/2006/05/pedl-lib',
	'xmlns:tree' => 'http://www.trscript.de/tree',
);
$tree = new xml_semantic();
$tree->setNewTree($REG);
$tree->createTree('http://qportal-project.org/regsys', 'rdf:RDF', $ns);
$tree->set_first_node();
$tree->use_ns_def_strict(true);
$tree->load_Stream($dok, 0, 'XML');

$reg = isset($tree->namespace_frameworks[$REG]['node'])
     ? $tree->namespace_frameworks[$REG]['node'] : array();

/* --- 4. Urteil --- */
$ok = 0; $rot = 0;
echo "\n1) Was das Dokument praegt, praegt es wirklich\n" . str_repeat('=', 74) . "\n";
foreach($gepraegt as $name => $tag)
{
	if(!isset($reg[$name]))          { printf("  [ROT ] %-18s aus %-24s NICHT registriert\n", $name, $tag); $rot++; }
	elseif('Interface_node' === get_class($reg[$name]))
	                                 { printf("  [ROT ] %-18s aus %-24s STUMPF (Interface_node)\n", $name, $tag); $rot++; }
	else { printf("  [ ok ] %-18s aus %-24s %s\n", $name, $tag, get_class($reg[$name])); $ok++; }
}

echo "\n2) Was der Bindungsblock benutzt, praegt das Dokument\n" . str_repeat('=', 74) . "\n";
foreach(array_keys($benutzt) as $tag)
{
	if(isset($gepraegt[$tag])) { printf("  [ ok ] %-18s vom Dokument gepraegt\n", $tag); $ok++; }
	else { printf("  [ROT ] %-18s PHP benutzt ihn, das Dokument praegt ihn NICHT\n", $tag); $rot++; }
}

printf("\n  %d in Ordnung, %d rot   (%d Praegungen im Dokument, %d Tags im Bindungsblock)\n",
	$ok, $rot, count($gepraegt), count($benutzt));
