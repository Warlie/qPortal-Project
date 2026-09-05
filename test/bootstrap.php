<?PHP
/**
*	Baumschicht ohne index.php hochfahren — fuer Pruefstaende und kurze Versuche auf der
*	Kommandozeile.
*
*	index.php macht auf dem Weg zum Baum noch Session, Datenbank, ContentGenerator und
*	Plugins. Nichts davon braucht, wer nur einen Baum laden und darin suchen will. Hier
*	steht genau das Minimum, in der Reihenfolge, in der es geladen werden muss:
*	die Konstanten, die die Knotenklassen beim Erzeugen lesen, dann der Automat (die
*	ns-Schicht braucht qp_workflow), dann die Baumschicht.
*
*	Aufruf:
*
*	    require_once(__DIR__ . '/../bootstrap.php');
*
*	    $tree = new xml_semantic();
*	    $tree->setNewTree($kennung);
*	    $tree->load_Stream($xml, 0, "XML");
*	    $treffer = $tree->collect_nodes('http://www.trscript.de/tree#param');
*
*	⚠ Die SPITZE der Baumschicht nehmen, nicht xml_ns. Die Produktion baut sie auch
*	(class_Contentgenerator.php:82). Wer tiefer ansetzt, verliert Methoden, die
*	Knotenklassen aufrufen — ein Dokument mit <owl:Ontology> stirbt unter xml_ns an
*	"Call to undefined method xml_ns::currentOntology()" (rdf_about.php:57), weil
*	currentOntology erst auf xml_semantic existiert. Der Fehler sieht nach einem
*	kaputten Dokument aus und ist ein zu tief gewaehlter Pruefstandskopf.
*
*	Warnungen: die Baumschicht schreibt beim Laden reichlich Notices und Warnings
*	(Bestand). Wer sie nicht sehen will, ruft mit -d error_reporting=E_ERROR auf.
*	Sie zu unterdruecken waere falsch — sie sind das Korrektheitssignal.
*/

chdir(__DIR__ . '/..');

/* Konstanten, die Knotenklassen und Handles beim Erzeugen lesen. Fehlt eine, bricht der
*  Aufbau mitten im Parsen mit "Undefined constant" ab. */
if(!defined('REPORT'))                   define('REPORT', 0);
if(!defined('MEMORY_USAGE'))             define('MEMORY_USAGE', false);
if(!defined('TRACE'))                    define('TRACE', false);
if(!defined('INSTALL'))                  define('INSTALL', false);
if(!defined('XML_SCHEMA_DEFAULT'))       define('XML_SCHEMA_DEFAULT', '');
if(!defined('XML_CASE_FOLDING_DEFAULT')) define('XML_CASE_FOLDING_DEFAULT', '0');
if(!defined('LANGUAGE_INPUT_DEFAULT'))   define('LANGUAGE_INPUT_DEFAULT', 'XML');
if(!defined('LANGUAGE_OUTPUT_DEFAULT'))  define('LANGUAGE_OUTPUT_DEFAULT', 'XML');
if(!defined('ROOT_DIR'))                 define('ROOT_DIR', __DIR__ . '/..');
if(!defined('CUR_PATH'))                 define('CUR_PATH', '');
if(!defined('STD_URL'))                  define('STD_URL', 'index.php?i=%s');
if(!defined('PLUG_IN_FOLDER'))           define('PLUG_IN_FOLDER', 'PlugIn/');
if(!defined('START_PAGE'))               define('START_PAGE', '');
if(!defined('FRONTEND_INDEX'))           define('FRONTEND_INDEX', '');
if(!defined('EDIT_INDEX'))               define('EDIT_INDEX', '');

require_once(__DIR__ . '/../vendor/autoload.php');

/* Der Logger ist global und wird von den Knotenklassen ueber "global $logger_class"
*  gegriffen. Ohne ihn faellt jede setAssert-Zeile auf die Nase. */
require_once(__DIR__ . '/../PlugIn/plugin_log.php');

if(!isset($GLOBALS['logger_class']))
{
	$GLOBALS['logger_class'] = new Logger();
	Logger::$active = false;
}

/* Der Automat kommt vor der ns-Schicht: Interface_ns.php erzeugt beim Laden ein
*  qp_workflow, und dessen Zustandsklassen liegen im Finite\-Namensraum (Composer). */
require_once(__DIR__ . '/../classes/finite_state_machine/enums.php');
require_once(__DIR__ . '/../classes/finite_state_machine/class_Transducer.php');
require_once(__DIR__ . '/../classes/finite_state_machine/class_Acceptor.php');
require_once(__DIR__ . '/../classes/finite_state_machine/class_Mealy.php');
require_once(__DIR__ . '/../classes/fs_parser/qp_workflow.php');

/* Die Baumschicht. Ueber xml_ns gabelt sie sich, sie endet nicht:
*
*    xml -> xml_objex -> xml_omni -> xml_ns -+-> xml_gen              (von niemandem geladen)
*                                            +-> xml_sparqle
*                                            +-> xml_xPath_sParqle -> xml_semantic
*
*  Die Produktion baut die SPITZE: class_Contentgenerator.php:82 macht ein
*  xml_semantic, PHP_handle ein xml_xPath_sParqle. Wer hier nur bis xml_ns laedt,
*  misst eine kuerzere Kette als die, die spaeter laeuft - und faellt genau dort um,
*  wo die oberen Schichten gebraucht werden: <owl:Ontology rdf:about="..."> ruft ueber
*  rdf_about.php:57 currentOntology(), und das steht erst in xml_semantic.
*
*  Darum wie in der Produktion die Spitze anfordern; die Kette laedt sich selbst nach
*  (xml_multitree_semantic.php:44 -> xPath -> SPARQL -> ns -> omni -> objex -> xml). */
require_once(__DIR__ . '/../classes/ns/Interface_ns.php');
require_once(__DIR__ . '/../classes/xml_multitree.php');
require_once(__DIR__ . '/../classes/xml_multitree_objex.php');
require_once(__DIR__ . '/../classes/xml_multitree_omni_handle.php');
require_once(__DIR__ . '/../classes/xml_multitree_ns.php');
require_once(__DIR__ . '/../classes/xml_multitree_semantic.php');

/* Suchschicht und was sie braucht. */
require_once(__DIR__ . '/../classes/class_REST.php');
require_once(__DIR__ . '/../classes/connection_profile.php');
require_once(__DIR__ . '/../classes/search_model/index_model.php');

?>
