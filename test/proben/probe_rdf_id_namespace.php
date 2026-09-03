<?PHP
/**
*	Zeigt, gegen WELCHEN Namensraum ein rdf:ID aufloest — und woher der kommt.
*
*	Anlass war die Frage: "Wenn ich xmlns:owl in den root packe und owl:Ontology
*	verwende — wie sage ich, welcher Namensraum bei rdf:ID gilt?"
*
*	Die Antwort steht nicht in einer Deklaration, sondern im WERT selbst.
*	RDF_ID::event_initiated (rdf_ID.php:57) kennt drei Zweige:
*
*	    "System"                     -> Default-Namensraum des Baums, get_NS('', idx)
*	    "voller#Name"                -> der URI vor dem # wird verbatim uebernommen
*	    "praefix;Name"               -> ueber den Praefix, get_NS_of_Tree(praefix, idx)
*
*	Das Traegerelement spielt in keinem der drei Zweige eine Rolle.
*
*	Teil 1  laedt ein Dokument mit allen Formen und zeigt, was registriert wurde.
*	Teil 1b nimmt die Praefixform einzeln vor.
*	Teil 2  laedt ZWEI Dokumente, die denselben Praefix verschieden binden, und
*	        stellt get_NS() und get_NS_of_Tree() nebeneinander.
*
*	Aufruf:  php -d error_reporting=E_ERROR test/proben/probe_rdf_id_namespace.php
*/

require_once(__DIR__ . '/../bootstrap.php');

const TREE_NS  = 'http://www.trscript.de/tree';
const FRIDGE   = 'https://qportal-project.org/fridge';
const COOKBOOK = 'https://qportal-project.org/cookbook';

function ueberschrift($t)
{
	echo "\n" . $t . "\n" . str_repeat('=', 78) . "\n";
}

/* ------------------------------------------------------------------ Teil 1 */

ueberschrift('TEIL 1 — vier Schreibweisen von rdf:ID in EINEM Dokument');

$doc = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"
     . '<indextree xmlns="' . TREE_NS . '"'
     . ' xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns"'
     . ' xmlns:owl="http://www.w3.org/2002/07/owl"'
     . ' xmlns:fridge="' . FRIDGE . '" >' . "\n"
     . '  <tree rdf:ID="blank" value="ohne alles" />' . "\n"
     . '  <tree rdf:ID="#mit_raute" value="fuehrende Raute" />' . "\n"
     . '  <tree rdf:ID="' . FRIDGE . '#contains_rst" value="voller URI" />' . "\n"
     . '  <tree rdf:ID="fridge;strom" value="ueber den Praefix" />' . "\n"
     . '  <owl:Ontology rdf:ID="das_dokument" />' . "\n"
     . '</indextree>' . "\n";

echo "\nDas Dokument deklariert:\n";
echo "    xmlns        = " . TREE_NS . "     (Default, regiert <tree>, <indextree>)\n";
echo "    xmlns:owl    = http://www.w3.org/2002/07/owl\n";
echo "    xmlns:fridge = " . FRIDGE . "\n";

echo "\nDarin stehen:\n\n";
foreach(array(
	array('<tree>',         'blank',                  'ohne alles'),
	array('<tree>',         '#mit_raute',             'fuehrende Raute'),
	array('<tree>',         FRIDGE . '#contains_rst', 'voller URI'),
	array('<tree>',         'fridge;strom',           'ueber den Praefix'),
	array('<owl:Ontology>', 'das_dokument',           'anderes Traegerelement'),
) as $z)
	printf("    %-16s rdf:ID=\"%s\"%s %s\n",
		$z[0], $z[1], str_repeat(' ', max(1, 48 - strlen($z[1]))), $z[2]);

$t1 = new xml_semantic();
$t1->setNewTree('probe_formen');
$t1->load_Stream($doc, 0, 'XML');

echo "\nWas danach im Namensraum-Register steht (namespace_frameworks):\n\n";
printf("    %-48s %s\n", 'Namensraum', 'darin registriert');
echo '    ' . str_repeat('-', 74) . "\n";

$gesucht  = array('blank','mit_raute','contains_rst','strom','das_dokument');
$gefunden = array();
foreach($t1->namespace_frameworks as $ns => $eintrag)
{
	if(!isset($eintrag['node']) || !is_array($eintrag['node'])) continue;
	$namen = array_values(array_intersect(array_keys($eintrag['node']), $gesucht));
	if(!count($namen)) continue;
	$gefunden = array_merge($gefunden, $namen);
	printf("    %-48s %s\n", ($ns === '' ? '(leer)' : $ns), implode(', ', $namen));
}
foreach(array_diff($gesucht, $gefunden) as $fehlt)
	printf("    %-48s %s\n", '-- nirgends --', $fehlt);

echo "\n    Merke: das Traegerelement aendert nichts. das_dokument steht im\n";
echo "    tree-Namensraum, obwohl es auf <owl:Ontology> sitzt.\n";

/* ----------------------------------------------------------------- Teil 1b */

ueberschrift('TEIL 1b — die Praefixform "praefix;Name" einzeln');

echo "\n    <tree rdf:ID=\"fridge;strom\" />   heisst: nimm den Praefix fridge\n\n";

$doc_b = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"
       . '<indextree xmlns="' . TREE_NS . '"'
       . ' xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns"'
       . ' xmlns:fridge="' . FRIDGE . '" >' . "\n"
       . '  <tree rdf:ID="fridge;strom" value="ueber den Praefix" />' . "\n"
       . '</indextree>' . "\n";

$t1b = new xml_semantic();
$t1b->setNewTree('probe_praefix');
try
{
	$t1b->load_Stream($doc_b, 0, 'XML');
	$wo = '-- nirgends --';
	foreach($t1b->namespace_frameworks as $ns => $eintrag)
		if(isset($eintrag['node']['strom'])) $wo = ($ns === '' ? '(leer)' : $ns) . '#strom';
	printf("    registriert als:  %s\n", $wo);
}
catch(Throwable $e)
{
	printf("    %s: %s\n", get_class($e), $e->getMessage());
	printf("    %s:%d\n", str_replace(realpath(ROOT_DIR) . '/', '', $e->getFile()), $e->getLine());
}

/* ------------------------------------------------------------------ Teil 2 */

ueberschrift('TEIL 2 — derselbe Praefix, zwei Dokumente');

$a = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"
   . '<indextree xmlns="' . TREE_NS . '" xmlns:res="' . FRIDGE . '" >' . "\n"
   . '  <tree value="Kuehlschrank" />' . "\n"
   . '</indextree>' . "\n";

$b = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"
   . '<indextree xmlns="' . TREE_NS . '" xmlns:res="' . COOKBOOK . '" >' . "\n"
   . '  <tree value="Kochbuch" />' . "\n"
   . '</indextree>' . "\n";

$t2 = new xml_semantic();
$t2->setNewTree('dokument_A');
$t2->load_Stream($a, 0, 'XML');
$idxA = $t2->cur_idx();

$t2->setNewTree('dokument_B');
$t2->load_Stream($b, 0, 'XML');
$idxB = $t2->cur_idx();

echo "\n    Dokument A (idx $idxA):  xmlns:res = " . FRIDGE . "\n";
echo "    Dokument B (idx $idxB):  xmlns:res = " . COOKBOOK . "\n\n";

printf("    %-24s %-38s %s\n", 'Baum', "get_NS('res', idx)", "get_NS_of_Tree('res', idx)");
echo '    ' . str_repeat('-', 74) . "\n";
foreach(array($idxA => 'A  (Kuehlschrank)', $idxB => 'B  (Kochbuch)') as $idx => $name)
	printf("    %-24s %-38s %s\n", $name,
		var_export($t2->get_NS('res', $idx), true),
		var_export($t2->get_NS_of_Tree('res', $idx), true));

echo "\n    Der Default ist in beiden Spalten richtig — er war nie das Problem:\n";
foreach(array($idxA => 'A', $idxB => 'B') as $idx => $name)
	printf("        get_NS('', %d)  [Dok %s]   %s\n", $idx, $name, var_export($t2->get_NS('', $idx), true));

/* ------------------------------------------------------------------ Befund */

ueberschrift('WAS DIE SONDE ZEIGT');

echo <<<TEXT

    1. Der Namensraum eines rdf:ID kommt aus dem WERT, nicht aus einer
       Deklaration und nicht aus dem Traegerelement. Es gibt kein xml:base.

    2. Die Form "voller#Name" trug schon vor der Reparatur. Ein Dokument kann
       sich global benennen, ohne dass eine Zeile Code geaendert wird — es
       muss den vollen URI nur an jeden Knoten schreiben.

    3. Die Abkuerzung dafuer ist der ";"-Zweig. Er stand seit jeher da und war
       nie gelaufen: substr($data,1,$posinstr) schnitt den Praefix falsch
       heraus, und in rdf_ID.php rief er get_id() — eine Methode, die es nicht
       gibt. Ein Dokument mit "praefix;Name" brach den Parse ab.

    4. Ein blosses Geraderuecken haette nicht gereicht. get_NS() schluesselt
       den Default nach Baumindex (richtig), den Praefix aber nach Namen ohne
       Baum und nimmt den zuletzt registrierten — Dokument A bekaeme den
       Praefix von Dokument B. Teil 2 zeigt beide Spalten nebeneinander.
       get_NS() bleibt unveraendert; get_NS_of_Tree() liest prefixes_inv, das
       die Zuordnung Baum -> Praefix ohnehin schon fuehrt.


TEXT;
