<?PHP
/**
*	Zeigt, welche Knoten ein tree-Prototyp als seine Instanzen kennt.
*
*	rdf:type ist eine Kante. In qPortal ist sie der Tag: jeder Knoten zeigt ueber
*	link_to_class auf seinen Prototyp, und die Prototypen stehen GLOBAL in
*	namespace_frameworks. Die Gegenrichtung - "welche Knoten sind tree:tree?" -
*	braucht link_to_instance am Prototyp. Interface_ns::new_Instance() fuehrt die
*	Liste, TREE_tree::new_Instance() hat sie bisher ausgelassen.
*
*	Gebraucht wird die Liste ueber ALLE geladenen Baeume und fuer ALLE Knoten -
*	auch die anonymen: ein <final> ohne Namen kann Beschreibungen tragen (STW).
*
*	Diese Sonde prueft nicht, sie ZEIGT. Gelesen wird die Ausgabe.
*
*	Aufruf:  php -d error_reporting=E_ERROR test/proben/probe_tree_instances.php
*/

require_once(__DIR__ . '/../bootstrap.php');

const TREE_NS = 'http://www.trscript.de/tree';
const FRIDGE  = 'https://qportal-project.org/fridge';
const DCT     = 'http://purl.org/dc/terms/';

function ueberschrift($t)
{
	echo "\n" . $t . "\n" . str_repeat('=', 78) . "\n";
}

/* Zwei Dokumente. Im Baum stehen: benannt, blank, fuehrender Punkt, anonym mit
*  Beschreibung, gepraegt (Praefixform) - und ein first. */
$doc_a = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"
       . '<indextree xmlns="' . TREE_NS . '" xmlns:fridge="' . FRIDGE . '" xmlns:dcterms="' . DCT . '" >' . "\n"
       . '  <first />' . "\n"
       . '  <final name="home" value="blank" >' . "\n"
       . '    <tree name="login" value="blank" />' . "\n"
       . '    <tree name=".edit" value="fuehrender Punkt" />' . "\n"
       . '    <tree value="anonym" />' . "\n"
       . '    <tree name="fridge;strom" value="gepraegt" />' . "\n"
       . '  </final>' . "\n"
       . '  <final dcterms:description="anonym mit Beschreibung" />' . "\n"
       . '</indextree>' . "\n";

$doc_b = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"
       . '<indextree xmlns="' . TREE_NS . '" >' . "\n"
       . '  <final name="home" value="zweiter Baum" >' . "\n"
       . '    <tree value="anonym im zweiten Baum" />' . "\n"
       . '  </final>' . "\n"
       . '</indextree>' . "\n";

$tree = new xml_semantic();

$tree->setNewTree('probe_instances_a');
$tree->load_Stream($doc_a, 0, 'XML');

$tree->setNewTree('probe_instances_b');
$tree->load_Stream($doc_b, 0, 'XML');

/* Was im Dokument steht - gezaehlt ueber die baumlokale Suche, je Baum. Das ist
*  die Vergleichszahl: so viele Knoten muss die globale Liste mindestens kennen. */
$im_baum = array();
$zurueck = $tree->cur_idx();

for($i = 0; $i <= $tree->max_idx(); $i++)
{
	$tree->change_idx($i);
	foreach(array('tree', 'final', 'first') as $ln)
		$im_baum[$ln] = ($im_baum[$ln] ?? 0) + count($tree->collect_nodes(TREE_NS . '#' . $ln));
}

$tree->change_idx($zurueck);

ueberschrift('Was die Prototypen als Instanzen kennen');

foreach(array('tree', 'final', 'first') as $ln)
{
	$proto = $tree->namespace_frameworks[TREE_NS]['node'][$ln] ?? null;

	echo "\n    " . TREE_NS . '#' . $ln . '   (' . (is_object($proto) ? get_class($proto) : 'kein Prototyp') . ")\n";

	if(!is_object($proto))
		continue;

	$n = $proto->ManyInstance();
	echo '    im Baum gefunden (collect_nodes je Baum): ' . $im_baum[$ln]
	   . '   ·   Instanzliste des Prototyps: ' . $n . "\n";

	for($k = 0; $k < $n; $k++)
	{
		$inst = $proto->linkToInstance($k);

		if(!is_object($inst))
		{
			echo "      [$k] kein Objekt\n";
			continue;
		}

		$name = $inst->get_ns_attribute(TREE_NS . '#name');
		$desc = $inst->get_ns_attribute(DCT . '#description');

		/* Ein Knoten im Baum hat einen Elternteil. Eine Praegung (rdf:about,
		*  tree:name) haengt nicht im Baum - sie ist das Vorlage-Objekt, das unter dem
		*  Namen eingetragen wird. Das ist das Prototyping-Rauschen. */
		$im_baum_haengend = is_object($inst->getRefprev());

		printf("      [%d] %-10s %-18s %-14s %s\n",
		       $k,
		       $im_baum_haengend ? 'Baum ' . $inst->get_idx() : 'Vorlage',
		       $im_baum_haengend ? $inst->position_stamp() : '(' . $inst->name . ')',
		       $name === false ? 'ohne name' : 'name=' . $name,
		       $desc === false ? '' : 'dcterms:description');
	}
}

ueberschrift('Gelesen wird');

echo "\n    Vor der Aenderung an TREE_tree::new_Instance() steht in jeder Liste 0.\n"
   . "    Danach: jeder tree/final/first aus BEIDEN Baeumen, die anonymen eingeschlossen,\n"
   . "    dazu fuer jede Praegung ein Vorlage-Objekt in der Liste ihres Traegers\n"
   . "    (nicht des Prototyps - der Traeger ist dann selbst Vorlage).\n";
