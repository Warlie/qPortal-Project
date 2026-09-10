<?PHP
/**
*	Das tree-Schema (xml-schema/tree-schema.xsd) gegen die Dokumente.
*
*	Die Pruefdokumente unter test/Integration/fixtures muessen ALLE bestehen - sie sind das,
*	was dieser Stand zusagt. Der Bestand unter template/ wird nur ausgewertet: wie viele
*	bestehen, woran die uebrigen scheitern. Das ist ein Befund, keine Pruefung - template/
*	wird getrennt verwaltet.
*
*	Gelesen wird mit XMLReader::setSchema, also nacheinander wie SAX, ohne DOM.
*
*	Aufruf:  php -d error_reporting=E_ERROR test/Integration/schema_check.php
*/
chdir(__DIR__ . '/../..');
$schema = 'xml-schema/tree-schema.xsd';

libxml_use_internal_errors(true);

/* Ein Dokument pruefen: null = wohlgeformt und gueltig, sonst die Fehlermeldungen. */
function pruefe(string $datei, string $schema): ?array
{
	libxml_clear_errors();
	$r = new XMLReader();
	if (!@$r->open($datei, null, LIBXML_NONET))
		return ['nicht lesbar'];
	if (!@$r->setSchema($schema))
		return ['Schema nicht ladbar'];
	while (@$r->read()) {}
	$r->close();
	$fehler = array_map(fn($e) => trim($e->message), libxml_get_errors());
	libxml_clear_errors();
	return $fehler ? $fehler : null;
}

/* Nur Dokumente mit indextree als Wurzel - nicht die Ausgabevorlagen (out_blank.xml). */
function ist_tree(string $datei): bool
{
	$r = new XMLReader();
	if (!@$r->open($datei, null, LIBXML_NONET)) return false;
	while (@$r->read())
		if ($r->nodeType === XMLReader::ELEMENT)
		{
			$ja = $r->namespaceURI === 'http://www.trscript.de/tree' && $r->localName === 'indextree';
			$r->close();
			return $ja;
		}
	$r->close();
	return false;
}

function dateien(string $wurzel): array
{
	if (!is_dir($wurzel)) return [];
	$res = [];
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($wurzel, FilesystemIterator::SKIP_DOTS));
	foreach ($it as $f)
		if (preg_match('/\.xml$/', $f->getFilename()))
			$res[] = $f->getPathname();
	sort($res);
	return $res;
}

echo "qPortal tree-Schema - Pruefstand\nSchema: $schema\n" . str_repeat('-', 78) . "\n";

/* --- Teil 1: die Pruefdokumente, alle gueltig ------------------------------------ */
$ok = 0; $fail = 0;
foreach (dateien('test/Integration/fixtures') as $f)
{
	if (!ist_tree($f)) continue;
	$e = pruefe($f, $schema);
	printf("[%s] %-44s %s\n", $e ? 'FEHLER' : '  ok  ', basename($f), $e ? $e[0] : 'gueltig');
	$e ? $fail++ : $ok++;
}
echo str_repeat('-', 78) . "\n$ok gelaufen, $fail fehlgeschlagen\n";

/* --- Teil 2: der Bestand, nur ausgewertet ---------------------------------------- */
$gut = 0; $schlecht = 0; $arten = []; $unregistriert = [];
foreach (dateien('template') as $f)
{
	if (!ist_tree($f)) continue;
	$e = pruefe($f, $schema);
	if (!$e) { $gut++; continue; }
	$schlecht++;
	foreach ($e as $m)
	{
		$art = preg_replace(['/\{http:\/\/www\.trscript\.de\/tree\}/', '/Expected is .*$/'], '', $m);
		$arten[$art] = ($arten[$art] ?? 0) + 1;
		if (preg_match("/Element '(programm|add2)'/", $art, $x))
			$unregistriert[$x[1]][$f] = true;
	}
}
if ($gut + $schlecht > 0)
{
	echo "\nBestand template/: $gut gueltig, $schlecht ungueltig (Befund, keine Pruefung)\n";
	arsort($arten);
	foreach (array_slice($arten, 0, 12, true) as $art => $n)
		printf("  %5d  %s\n", $n, mb_strimwidth($art, 0, 110, '…'));
	foreach ($unregistriert as $name => $fs)
		printf("  nicht registriert: <%s> in %d Dateien (z.B. %s)\n", $name, count($fs), basename(array_key_first($fs)));
}

exit($fail > 0 ? 1 : 0);
