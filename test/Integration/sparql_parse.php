<?PHP
/**
*	Prueft den Mealy-Automaten und den SPARQL-Parser — die Portierung aus
*	anttree/funct_parser_lib.js.
*
*	Beides braucht weder Baum noch Verbindung: der Automat liest Zeichen, der Parser
*	liest die Sprache. Darum steht hier kein Server und keine Gegenstelle.
*
*	Aufruf:  php test/Integration/sparql_parse.php
*/

require_once(__DIR__ . '/../bootstrap.php');

use Finite\Elements\Mealy_Automat;

$pass = 0; $fail = 0;

function check($name, $got, $want)
{
	global $pass, $fail;

	if($got === $want){ $pass++; printf("[  ok  ] %-46s %s\n", $name, $got); }
	else             { $fail++; printf("[FEHLER] %-46s erwartet %s, bekam %s\n", $name, var_export($want,true), var_export($got,true)); }
}

/** Faengt einen Wurf ein und gibt zurueck, ob er kam. */
function wirft(callable $f): string
{
	try     { $f(); return 'nein'; }
	catch (Exception $e) { return 'ja'; }
}

/* ============================================================ Der Automat allein */

/* Eine winzige Sprache: "a=1,b=2" — zwei Felder, Komma trennt die Zeilen. */
$m = new Mealy_Automat();
$m->setNodes('start', 'key', 'value');
$m->setStringNode('start', 'name');
$m->setEdge('start', 'value', '=', '');
$m->setStringNode('value', 'wert');
$m->setEdge('value', 'start', ',', 'next()');

$m->checkString('a=1,b=2,');
$rows = $m->getResult();

check('Automat: zwei Zeilen gelesen', count($rows), 2);
check('Automat: Feld vor dem Gleich', $rows[0]['name'], 'a');
check('Automat: Feld dahinter', $rows[0]['wert'], '1');
check('Automat: zweite Zeile', $rows[1]['name'] . '=' . $rows[1]['wert'], 'b=2');

/* Zutat gegenueber der Vorlage: der Lauf setzt zurueck, das Objekt darf stehenbleiben */
$m->checkString('x=9,');
check('Automat: zweiter Lauf sammelt nicht dazu', count($m->getResult()), 1);
check('Automat: und liefert das Neue', $m->getResult()[0]['name'], 'x');

/* Zutat: eine angefangene Zeile geht am Ende nicht verloren */
$m->checkString('z=7');
check('Automat: Zeile ohne Abschluss bleibt', count($m->getResult()), 1);
check('Automat: mit ihrem Wert', $m->getResult()[0]['wert'], '7');

/* Ein Zeichen, fuer das der Zustand keine Kante hat, faellt auf */
$eng = new Mealy_Automat();
$eng->setNodes('start', 'ziel');
$eng->setEdge('start', 'ziel', 'A', '');
check('Automat: unangenommener Text wirft', wirft(fn() => $eng->checkString('B')), 'ja');

/* Ein Zustand, den niemand angemeldet hat, faellt beim Bauen auf statt beim Lesen */
$roh = new Mealy_Automat();
$roh->setNode('start');
check('Automat: unbekannter Zustand wirft', wirft(fn() => $roh->setEdge('start', 'gibtesnicht', 'x', '')), 'ja');

/* Laengste Uebereinstimmung: ein Schluesselwort wird als eines gelesen */
$lang = new Mealy_Automat();
$lang->setNodes('start', 'drin');
$lang->setEdge('start', 'drin', 'SELECT', 'section(gefunden)');
$lang->setStringNode('drin', 'rest');
$lang->checkString('SELECTxy');
check('Automat: Schluesselwort als ein Schritt', $lang->getResult()[0]['rest'], 'xy');
check('Automat: section landet in der Zeile', $lang->getResult()[0]['#section'], 'gefunden');

/* ================================================================== Der Parser */

$p = new SPARQL_Parser();

$q = "BASE <http://www.trscript.de/tree>\n"
   . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n"
   . "PREFIX ex: <http://example.org/dinge#>\n"
   . "SELECT ?s ?wert\n"
   . "WHERE {\n"
   . "  ?s rdf:type ex:Wohnung .\n"
   . "  ?s ex:name \"zwei\" .\n"
   . "  ?s ex:wert ?wert\n"
   . "}";

$r = $p->parse($q);

check('Parser: BASE gelesen', $r['base'], 'http://www.trscript.de/tree');
check('Parser: beide Praefixe', implode(',', array_keys($r['prefixes'])), 'rdf,ex');
check('Parser: Praefix zeigt auf die URI', $r['prefixes']['ex'], 'http://example.org/dinge#');
check('Parser: zwei Spalten', implode(' ', $r['select']), '?s ?wert');
check('Parser: drei Tripel', count($r['where']), 3);

check('Parser: Praedikat aufgeloest', $r['where'][0]['p'],
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type');
check('Parser: Objekt aufgeloest', $r['where'][0]['o'], 'http://example.org/dinge#Wohnung');
check('Parser: Variable bleibt Variable', $r['where'][0]['s'], '?s');
check('Parser: Literal behaelt seine Zeichen', $r['where'][1]['o'], '"zwei"');
check('Parser: Variable als Objekt', $r['where'][2]['o'], '?wert');
check('Parser: Klammertiefe steht mit', $r['where'][0]['deep'], 1);

/* Mehrzeilig ist die uebliche Form — die Abweichung von der Vorlage */
check('Parser: Umbruch vor der Klammer',
      count($p->parse("SELECT ?a\nWHERE\n{\n\t?s ?p ?o\n}")['where']), 1);

/* Die rohe Tabelle zeigt, was gelesen wurde, bevor etwas aufgeloest ist */
$roh_rows = $p->rows('PREFIX ex: <http://example.org/#> SELECT ?a WHERE { ?a ex:p ?b }');
check('Parser: rohe Tabelle unaufgeloest', $roh_rows[2]['predicate'], 'ex:p');
check('Parser: rohe Zeile nennt ihren Abschnitt', $roh_rows[2]['#section'], 'where');

/* Ohne BASE und ohne bekanntes Praefix bleibt der Name stehen — man sieht, was fehlte */
$offen = $p->parse('SELECT ?s WHERE { ?s foo:bar ?o }');
check('Parser: unbekanntes Praefix bleibt sichtbar', $offen['where'][0]['p'], 'foo:bar');

/* Ein Name ohne Doppelpunkt haengt an der BASE, wie full_URI() es zusammensetzt */
$mit_base = $p->parse('BASE <http://example.org/x> SELECT ?s WHERE { ?s hatWert ?o }');
check('Parser: Name ohne Praefix an der BASE', $mit_base['where'][0]['p'],
      'http://example.org/x#hatWert');

/* Mehrere Spalten, mit Komma wie mit Leerraum */
check('Parser: Komma trennt Spalten',
      implode(' ', $p->parse('SELECT ?a, ?b WHERE { ?s ?p ?o }')['select']), '?a ?b');
check('Parser: der Stern ist eine Spalte',
      implode(' ', $p->parse('SELECT * WHERE { ?s ?p ?o }')['select']), '*');

/* Mehrere Tripel, mit und ohne abschliessenden Punkt */
check('Parser: zwei Tripel mit Punkt',
      count($p->parse('SELECT ?s WHERE { ?s ?p ?o . ?o ?q ?r }')['where']), 2);
check('Parser: letztes Tripel ohne schliessende Klammer',
      count($p->parse('SELECT ?s WHERE { ?s ?p ?o')['where']), 1);

/* Derselbe Parser, zweimal gefragt — er haelt keinen Rest vom letzten Mal */
check('Parser: zweiter Lauf traegt nichts mit',
      count($p->parse('SELECT ?s WHERE { ?s ?p ?o }')['where']), 1);

/* Was die Vorlage nicht traegt, faellt auf statt still durchzugehen */
check('Parser: verschachtelte Klammer wirft',
      wirft(fn() => $p->parse('SELECT ?s WHERE { ?a ?b ?c . { ?d ?e ?f } }')), 'ja');
check('Parser: volle URI in WHERE wirft',
      wirft(fn() => $p->parse('SELECT ?s WHERE { ?s <http://x/y> ?o }')), 'ja');
check('Parser: kleingeschriebenes select wirft',
      wirft(fn() => $p->parse('select ?s where { ?s ?p ?o }')), 'ja');

/* Die Meldung nennt Zustand, Zeichen und Stelle — sonst sucht man im Dunkeln */
$meldung = '';
try { $p->parse('SELECT ?s WHERE { ?s <http://x/y> ?o }'); }
catch (Exception $e) { $meldung = $e->getMessage(); }
check('Parser: Meldung nennt den Zustand', strpos($meldung, 'space_pre') !== false, true);
check('Parser: Meldung nennt die Stelle', strpos($meldung, 'Stelle') !== false, true);

/* ============================================================= Parser am Modell */

SearchingModelObject::set_config(array());
ConnectionProfile::set_collection(array(
	'hier' => array('type' => 'qportal', 'address' => '', 'source' => 'haupt')));

$leer = null;
$sp = SearchingModelObject::model_factory('sparql', $leer);
check('Modell: parse braucht keine Gegenstelle', count($sp->parse($q)['where']), 3);

$sp->use_source('hier');

/* qportal wertet gegen den eigenen Baum aus. Ein kleiner Baum dafuer, damit der
*  Prueflauf ohne Dokument auskommt. */
$mini = <<<XML
<?xml version='1.0' encoding="UTF-8"?>
<indextree xmlns="http://www.trscript.de/tree">
	<final name="home" src="a.xml">
		<tree name="login" src="b.xml" securitylevel="-1" />
		<tree name="offen" src="c.xml" />
		<tree name="ohne_quelle" securitylevel="3" />
	</final>
</indextree>
XML;

$baum = new xml_ns();
$baum->setNewTree('mini');
$baum->load_Stream($mini, 0, 'XML');

$sp2 = $baum->seek_by_model('sparql');
$sp2->use_source('hier');

$PX = "PREFIX tree: <http://www.trscript.de/tree#>\n"
    . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n";

$sp2->query($PX . 'SELECT ?t ?name WHERE { ?t rdf:type tree:tree . ?t tree:name ?name }');
$l = $sp2->solutions();
check('Auswertung: drei tree-Knoten', count($l), 3);
check('Auswertung: Namen gebunden',
      implode(',', array_column($l, '?name')), 'login,offen,ohne_quelle');
check('Auswertung: Subjekt bleibt ein Knoten', is_object($l[0]['?t']), true);

/* Die UND-Verknuepfung muss wegwerfen, wer das Praedikat nicht hat. ⚠ get_ns_attribute
*  liefert dafuer FALSE, nicht '' — ein leerer Wert waere eine Aussage, ein fehlender
*  ist keine. */
$sp2->query($PX . 'SELECT ?name WHERE { ?t rdf:type tree:tree . ?t tree:name ?name . ?t tree:src ?src }');
check('Auswertung: ohne src faellt heraus',
      implode(',', array_column($sp2->solutions(), '?name')), 'login,offen');

$sp2->query($PX . 'SELECT ?name WHERE { ?t rdf:type tree:tree . ?t tree:securitylevel ?s . ?t tree:name ?name }');
check('Auswertung: zwei Attribute UND-verknuepft',
      implode(',', array_column($sp2->solutions(), '?name')), 'login,ohne_quelle');

/* Fester Wert im Objekt, und die andere Sorte */
$sp2->query($PX . 'SELECT ?t ?src WHERE { ?t tree:name "login" . ?t tree:src ?src }');
check('Auswertung: fester Wert trifft einen',
      count($sp2->solutions()) . '/' . $sp2->solutions()[0]['?src'], '1/b.xml');

$sp2->query($PX . 'SELECT ?name WHERE { ?t rdf:type tree:final . ?t tree:name ?name }');
check('Auswertung: final ist eine andere Sorte',
      implode(',', array_column($sp2->solutions(), '?name')), 'home');

$sp2->query($PX . 'SELECT ?name WHERE { ?t rdf:type tree:tree . ?t tree:name "gibtesnicht" . ?t tree:name ?name }');
check('Auswertung: kein Treffer ist leer', count($sp2->solutions()), 0);

/* query() haelt die Zusage der Schnittstelle: Knoten zurueck */
$knoten = $sp2->query($PX . 'SELECT ?t WHERE { ?t rdf:type tree:tree . ?t tree:src ?src }');
check('Auswertung: query gibt Knoten', count($knoten) . '/' . (is_object($knoten[0]) ? 'obj' : '-'), '2/obj');

$pv = '-';
try { $sp2->query($PX . 'SELECT * WHERE { ?s ?p ?o }'); }
catch (Exception $e) { $pv = 'Praedikatvariable abgewiesen'; }
check('Auswertung: Praedikatvariable wird abgewiesen', $pv, 'Praedikatvariable abgewiesen');

$kaputt = '';
try { $sp->query('SELECT ?s WHERE { ?s <http://x/y> ?o }'); }
catch (Exception $e) { $kaputt = $e->getMessage(); }
check('Modell: kaputter Ausdruck meldet den Parser',
      strpos($kaputt, 'Mealy_Automat') !== false, true);

ConnectionProfile::set_collection(array());

/* ------------------------------------------------------------------- Ergebnis */

echo str_repeat('-', 78) . "\n";
printf("%d gelaufen, %d fehlgeschlagen\n", $pass + $fail, $fail);
exit($fail > 0 ? 1 : 0);

?>
