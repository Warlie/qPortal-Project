<?PHP
/**
*	Das komplette qPortal, nur mit einem anderen INTERN-Baum.
*
*	createConfigFromINIFile (mod_lib.php) ueberspringt jede Konstante, die schon
*	definiert ist. Hier wird INTERN vorab auf ein Pruefdokument gesetzt und danach das
*	unveraenderte index.php eingebunden - Schluessel, Registry, Ausgabe laufen wie in
*	der Produktion. Ueber die Kommandozeile geht das nicht: index.php liest den Befehl
*	aus php://input, und das ist dort leer. Darum ueber den laufenden Server.
*
*	Nur von dieser Maschine aus: ein zweiter Eingang mit eigenem Baum gehoert nicht
*	ins Netz.
*/
if(!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true))
{
	http_response_code(403);
	exit;
}

/* ?doc=<name> waehlt ein Dokument aus fixtures/, sonst tree_nested. Nur Namen, keine
*  Pfade - sonst waere das ein Eingang fuer jede Datei auf der Platte. */
$name = (string) ($_GET['doc'] ?? 'tree_nested');
$doc  = __DIR__ . '/fixtures/' . $name . '.xml';

if(!preg_match('/^[a-z0-9_]+$/', $name) || !is_file($doc))
{
	http_response_code(404);
	exit;
}

define('INTERN', $doc);

chdir(__DIR__ . '/../..');
require __DIR__ . '/../../index.php';
