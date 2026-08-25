<?PHP
/**
*	Prueft die Teilbaum-Suche in xml_ns::collect_nodes()/seek_node().
*
*	Laedt einen kleinen Baum eigenstaendig (wie mod_lib.php es tut) und vergleicht
*	die Treffermengen mit und ohne Suchraum.
*
*	Aufruf:  php test/Integration/seek_scope.php
*/

require_once(__DIR__ . '/../bootstrap.php');

$pass = 0; $fail = 0;

function check($name, $got, $want)
{
	global $pass, $fail;

	if($got === $want){ $pass++; printf("[  ok  ] %-42s %s\n", $name, $got); }
	else             { $fail++; printf("[FEHLER] %-42s erwartet %s, bekam %s\n", $name, var_export($want,true), var_export($got,true)); }
}

$xml = <<<XML
<?xml version='1.0' encoding="UTF-8"?>
<indextree xmlns="http://www.trscript.de/tree" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns">
	<first>
		<param name="a" id="x">1</param>
		<element>
			<param name="b" id="x" rdf:about="http://example.org/dinge#zwei">2</param>
			<param name="c" id="y" rdf:about="http://example.org/dinge#drei">3</param>
		</element>
	</first>
	<final>
		<param name="d" id="x">4</param>
	</final>
</indextree>
XML;

$T = 'http://www.trscript.de/tree#';

$tree = new xml_ns();
$tree->load_Stream($xml, 0, "XML");

/* ------------------------------------------------------------------ Bestand */

$all = $tree->collect_nodes($T . 'param');
check('ohne Suchraum: alle param', count($all), 4);

$named = $tree->collect_nodes($T . 'param', array($T . 'name' => 'c'));
check('Attributfilter einzeln', count($named), 1);

/* ------------------------------------- Attribute UND-verknuepft (Korrektur) */

// name=b UND id=x trifft genau einen; im Bestand entschied nur das letzte Attribut
$both = $tree->collect_nodes($T . 'param', array($T . 'name' => 'b', $T . 'id' => 'x'));
check('zwei Attribute, beide passen', count($both), 1);

// name=a passt, id=y nicht -> UND muss leer liefern (Bestand lieferte den Treffer von id=y)
$conflict = $tree->collect_nodes($T . 'param', array($T . 'name' => 'a', $T . 'id' => 'y'));
check('zwei Attribute, eines passt nicht', count($conflict), 0);

/* ------------------------------------------------------------- Suchraum neu */

$first = $tree->collect_nodes($T . 'first');
check('Ankerknoten first gefunden', count($first), 1);

$in_first = $tree->collect_nodes($T . 'param', null, null, $first[0]);
check('param unter first (descendant)', count($in_first), 3);

$element = $tree->collect_nodes($T . 'element');
$in_elem = $tree->collect_nodes($T . 'param', null, null, $element[0]);
check('param unter element', count($in_elem), 2);

/* --------------------------------------------------------------- Achsentiefe */

$direct = $tree->collect_nodes($T . 'param', null, null, $first[0], 1);
check('depth=1 nur direkte Kinder von first', count($direct), 1);

$self_only = $tree->collect_nodes($T . 'param', null, null, $first[0], 0);
check('depth=0 nur der Knoten selbst', count($self_only), 0);

/* ------------------------------------------------------- Suchraum als Menge */

$as_set = $tree->collect_nodes($T . 'param', null, null, array($element[0], $tree->collect_nodes($T . 'final')[0]));
check('Suchraum als Menge (element + final)', count($as_set), 3);

$empty_scope = $tree->collect_nodes($T . 'param', null, null, array());
check('leerer Suchraum laesst nichts durch', count($empty_scope), 0);

/* ------------------------------------------- Suchraum und Attribute zusammen */

$combo = $tree->collect_nodes($T . 'param', array($T . 'id' => 'x'), null, $first[0]);
check('Suchraum + Attribut', count($combo), 2);

/* --------------------------------------------- kein Wachstum bei Fehlschlag */

$before = count($tree->collect_nodes());
$tree->collect_nodes('http://www.trscript.de/tree#gibtesnicht');
$tree->collect_nodes('http://www.trscript.de/tree#gibtesnicht');
$after = count($tree->collect_nodes());
check('erfolglose Suche laesst den Index unveraendert', $after, $before);

/* --------------------------------------------- only_child_node ueber seek_node */

$tree->flash_result();
$tree->collect_nodes($T . 'first');            // Cursor steht danach nicht automatisch
$tree->flash_result();
$tree->seek_node($T . 'first');                // setzt den Cursor auf first
$tree->flash_result();
$tree->only_child_node(true);
$tree->seek_node($T . 'param');
$scoped = count($tree->get_result());
$tree->only_child_node(false);
check('only_child_node greift wieder', $scoped, 3);

/* ------------------------------------------------ Attribute in der Lookup-Tabelle */

// Vorgabe bleibt NODE: Bestandsaufrufe sehen keine Attributknoten
$as_node = $tree->collect_nodes($T . 'name');
check('Vorgabe NODE findet kein Attribut', count($as_node), 0);

$as_attr = $tree->collect_nodes($T . 'name', null, null, null, -1, ATTRIBUTE);
check('name als ATTRIBUTE indiziert', count($as_attr), 4);

$both_kinds = $tree->collect_nodes($T . 'name', null, null, null, -1, -1);
check('kind=-1 nimmt beide Sorten', count($both_kinds), 4);

$id_attr = $tree->collect_nodes($T . 'id', null, null, null, -1, ATTRIBUTE);
check('id als ATTRIBUTE indiziert', count($id_attr), 4);

// der Uebergang zurueck zum Traeger ist getRefprev()
$traeger = $as_attr[0]->getRefprev();
check('Uebergang Attribut -> Element', $traeger->full_URI(), $T . 'param');

// der Suchraum wirkt auch auf Attributknoten, ohne dass etwas ergaenzt werden musste
$attr_in_first = $tree->collect_nodes($T . 'name', null, null, $first[0], -1, ATTRIBUTE);
check('Attribute im Suchraum first', count($attr_in_first), 3);

// Attributwert steht am Knoten
$werte = array();
foreach($as_attr as $a) $werte[] = $a->getdata();
sort($werte);
check('Werte der name-Attribute', implode(',', $werte), 'a,b,c,d');

/* --------------------------------- nachtraeglich gesetztes Attribut wird nachgetragen */

$ziel = $tree->collect_nodes($T . 'element');
$ziel[0]->set_ns_attribute($T . 'id', 'wert1');

$frisch = $tree->collect_nodes($T . 'id', null, null, null, -1, ATTRIBUTE);
check('nachtraegliches Attribut ist indiziert', count($frisch), 5);
check('sein Wert steht am Knoten', $ziel[0]->get_ns_attribute($T . 'id'), 'wert1');

// zweimal setzen darf keinen Doppeleintrag geben
$ziel[0]->set_ns_attribute($T . 'id', 'wert2');
$frisch2 = $tree->collect_nodes($T . 'id', null, null, null, -1, ATTRIBUTE);
check('ueberschriebenes Attribut trifft nicht mehr', count($frisch2), 5);

/* -------------------------------- neuer Attributname ohne vorhandenen Prototyp */

$ziel[0]->set_ns_attribute($T . 'frisch', 'wert3');
$neu = $tree->collect_nodes($T . 'frisch', null, null, null, -1, ATTRIBUTE);
check('unbekannter Attributname legt Prototyp an', count($neu), 1);
check('sein Wert ist lesbar', $ziel[0]->get_ns_attribute($T . 'frisch'), 'wert3');
check('sein Typ ist gesetzt', count($neu) ? $neu[0]->full_URI() : '-', $T . 'frisch');

/* --------------------------------------------- Wertmenge als Tuerschild */

$werte_set = $tree->attribute_value_set($T . 'name');
$namen = array_keys($werte_set);
sort($namen);
check('Wertmenge zu name', implode(',', $namen), 'a,b,c,d');

check('may_have: vorhandener Wert',   $tree->may_have_attribute_value($T . 'name', 'b'), true);
check('may_have: fehlender Wert',     $tree->may_have_attribute_value($T . 'name', 'zzz'), false);
check('may_have: unbekannter Name',   $tree->may_have_attribute_value($T . 'gibtsnicht', 'x'), false);
check('may_have: nur Vorhandensein',  $tree->may_have_attribute_value($T . 'name', null), true);

// frueher Abbruch liefert dasselbe wie der Durchlauf
check('Suche auf fehlenden Wert',   count($tree->collect_nodes($T . 'param', array($T . 'name' => 'zzz'))), 0);
check('Suche auf vorhandenen Wert', count($tree->collect_nodes($T . 'param', array($T . 'name' => 'b'))), 1);

// geaenderter Wert wird nachgetragen
$ps = $tree->collect_nodes($T . 'param', array($T . 'name' => 'a'));
$attr_obj = $ps[0]->get_ns_attribute_obj($T . 'name');
$attr_obj->setdata('umbenannt', 0);
check('neuer Wert in der Menge',   $tree->may_have_attribute_value($T . 'name', 'umbenannt'), true);
check('neuer Wert ist findbar',    count($tree->collect_nodes($T . 'param', array($T . 'name' => 'umbenannt'))), 1);
check('alter Wert bleibt Schranke', $tree->may_have_attribute_value($T . 'name', 'a'), true);
check('alter Wert trifft nicht mehr', count($tree->collect_nodes($T . 'param', array($T . 'name' => 'a'))), 0);

/* ------------------------------------------------------ Identitaetstabelle */

$R = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

$ids = $tree->identity_set();
sort($ids);
check('Identitaeten erfasst', implode(',', $ids),
      'http://example.org/dinge#drei,http://example.org/dinge#zwei');

$byid = $tree->node_by_identity('http://example.org/dinge#zwei');
check('Knoten per Identitaet gefunden', is_object($byid) ? $byid->full_URI() : '-', $T . 'param');
check('es ist der richtige Knoten', is_object($byid) ? $byid->get_ns_attribute($T . 'name') : '-', 'b');

$fehlt = $tree->node_by_identity('http://example.org/dinge#gibtsnicht');
check('unbekannte Identitaet gibt null', is_null($fehlt), true);

// geaenderte Identitaet: der neue Schluessel greift, der alte nicht mehr
$byid->set_ns_attribute($R . 'about', 'http://example.org/dinge#neu');
check('neue Identitaet greift',
      is_object($n2 = $tree->node_by_identity('http://example.org/dinge#neu')), true);
check('alte Identitaet greift nicht mehr',
      is_null($tree->node_by_identity('http://example.org/dinge#zwei')), true);

$ids2 = $tree->identity_set();
sort($ids2);
check('Identitaetsmenge zieht nach', implode(',', $ids2),
      'http://example.org/dinge#drei,http://example.org/dinge#neu');

/* -------------------------------------------------- Suchmodell-Fassade */

$tree->flash_result();   // frueherer seek_node-Test hat die Ergebnisliste gefuellt

$modelle = SearchingModelObject::describe_models();
$namen = array_keys($modelle);
sort($namen);
check('Modelle melden sich selbst', implode(',', $namen), 'internal,sparql,xpath');

$m = $tree->seek_by_model('internal');
check('Fassade liefert ein Modell', is_object($m) ? get_class($m) : '-', 'Internal_Searching_Model');
check('es traegt die gemeinsame Flaeche',
      $m instanceof Searching_Model, true);

$leer = $tree->seek_by_model('gibtsnicht');
check('unbekanntes Modell gibt null', is_null($leer), true);

// dasselbe Ergebnis wie der direkte Weg
$ueber_modell = $m->query($T . 'param');
$direkt       = $tree->collect_nodes($T . 'param');
check('Modell und collect_nodes stimmen ueberein', count($ueber_modell), count($direkt));

check('Kurzform query_by_model', count($tree->query_by_model('internal', $T . 'param')), count($direkt));

// die strukturierte Form, in der der Automat einen Schritt weiterreicht
$mit_scope = $m->requestArray('http://www.trscript.de/tree', 'param', null, null, $first[0]);
check('requestArray mit Suchraum', count($mit_scope), 3);

$mit_attr = $m->requestArray('', $T . 'param', array($T . 'name' => 'c'), null);
check('requestArray mit Attribut', count($mit_attr), 1);

// zwei Modelle nebeneinander teilen keinen Zustand — Re-Entrancy
$m2 = $tree->seek_by_model('internal');
$a = $m->query($T . 'param');
$b = $m2->query($T . 'element');
check('zwei Modelle stoeren sich nicht', count($a) . '/' . count($b), count($direkt) . '/1');
check('result_nodes des Baums unberuehrt', count($tree->get_result()), 0);

// Grenzen werden benannt statt still zu scheitern
$fehler = '-';
try { $m->query($T . 'param[@name="a"]'); } catch (Exception $e) { $fehler = 'Praedikat abgewiesen'; }
check('zusammengesetzter Ausdruck', $fehler, 'Praedikat abgewiesen');

$fehler2 = '-';
try { $m->query('param'); } catch (Exception $e) { $fehler2 = 'roher Name abgewiesen'; }
check('Name ohne Namensraum', $fehler2, 'roher Name abgewiesen');

$fehler3 = '-';
try { $tree->query_by_model('xpath', $T . 'param'); } catch (Exception $e) { $fehler3 = 'xpath meldet sich'; }
check('xpath-Stub wirft statt null zu geben', $fehler3, 'xpath meldet sich');

/* ------------------------------------------- SPARQL-Modell: Quellen mit Geltungsbereich */

SearchingModelObject::set_config(array());
$sp = $tree->seek_by_model('sparql');
check('sparql-Modell da', is_object($sp) ? get_class($sp) : '-', 'SPARQL_Model');
check('ohne Config keine Quelle', $sp->source(), '');
check('ohne Config keine Quellenliste', count($sp->configured_sources()), 0);

$f = '-';
try { $sp->query('SELECT * WHERE { ?s ?p ?o }'); } catch (Exception $e) { $f = 'nennt die Config'; }
check('ohne Wahl sagt es, was fehlt', $f, 'nennt die Config');

/* Beide Quellen nebeneinander, jede mit eigenem Geltungsbereich */
SearchingModelObject::set_config(array('sparql' => array(
	'use'    => 'intern',
	'intern' => array('source' => 'haupt'),
	'fuseki' => array('source' => 'http://example.org/graph/eins',
	                  'endpoint' => '', 'user' => '', 'password' => ''))));

$sp2 = $tree->seek_by_model('sparql');
check('Vorgabequelle aus sparql.use', $sp2->source(), 'intern');
check('Geltungsbereich der Vorgabe', $sp2->scope(), 'haupt');
check('Geltungsbereich der anderen Quelle', $sp2->scope('fuseki'), 'http://example.org/graph/eins');

$liste = $sp2->configured_sources();
check('beide Quellen gemeldet', implode(',', array_keys($liste)), 'intern,fuseki');
check('mit ihren Bereichen', $liste['intern'] . '|' . $liste['fuseki'],
      'haupt|http://example.org/graph/eins');

/* Der Aufruf darf die Quelle wechseln, ohne den Ausdruck anzufassen */
$sp2->use_source('fuseki');
check('use_source wechselt', $sp2->source(), 'fuseki');
check('und der Bereich wandert mit', $sp2->scope(), 'http://example.org/graph/eins');
$sp2->use_source();
check('zurueck zur Vorgabe', $sp2->source(), 'intern');

$f2 = '-';
try { $sp2->use_source('quatsch'); } catch (Exception $e) { $f2 = 'unbekannte Quelle abgewiesen'; }
check('use_source prueft den Namen', $f2, 'unbekannte Quelle abgewiesen');

$f3 = '-';
try { $sp2->query('SELECT * WHERE { ?s ?p ?o }'); } catch (Exception $e) { $f3 = 'sagt: nicht gebaut'; }
check('intern meldet sich ehrlich', $f3, 'sagt: nicht gebaut');

$f4 = '-';
try { $sp2->use_source('fuseki')->query('SELECT * WHERE { ?s ?p ?o }'); }
catch (Exception $e) { $f4 = 'kein Endpunkt, kein Versand'; }
check('fuseki ohne Endpunkt schickt nichts', $f4, 'kein Endpunkt, kein Versand');

check('drei Modelle gemeldet', count(SearchingModelObject::describe_models()), 3);

// die Antwortverarbeitung laesst sich ohne Netz pruefen
$antwort = '{"head":{"vars":["s","p"]},"results":{"bindings":['
         . '{"s":{"type":"uri","value":"http://example.org/a"},"p":{"type":"literal","value":"eins"}},'
         . '{"s":{"type":"uri","value":"http://example.org/b"},"p":{"type":"literal","value":"zwei"}}]}}';
$knoten = $tree->seek_by_model('sparql')->answer_to_nodes($antwort);
check('Antwort wird zu Knoten', count($knoten) > 0, true);
check('leere Antwort gibt leer', count($tree->seek_by_model('sparql')->answer_to_nodes('')), 0);

echo str_repeat('-', 78) . "\n";
echo ($pass + $fail) . " gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
