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
*	    $tree = new xml_ns();
*	    $tree->load_Stream($xml, 0, "XML");
*	    $treffer = $tree->collect_nodes('http://www.trscript.de/tree#param');
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

/* Die Baumschicht, von unten nach oben:
*  xml (xml_multitree) -> xml_objex -> xml_omni -> xml_ns -> xml_gen */
require_once(__DIR__ . '/../classes/ns/Interface_ns.php');
require_once(__DIR__ . '/../classes/xml_multitree.php');
require_once(__DIR__ . '/../classes/xml_multitree_objex.php');
require_once(__DIR__ . '/../classes/xml_multitree_omni_handle.php');
require_once(__DIR__ . '/../classes/xml_multitree_ns.php');

/* Suchschicht und was sie braucht. */
require_once(__DIR__ . '/../classes/class_REST.php');
require_once(__DIR__ . '/../classes/connection_profile.php');
require_once(__DIR__ . '/../classes/search_model/index_model.php');

?>
