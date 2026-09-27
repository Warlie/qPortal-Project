<?PHP
/**
*	mode=load - ein tree, den der uebergeordnete Baum MITLAEDT, ueber das komplette qPortal.
*
*	Ein <tree> mit <param name="mode">load</param> laedt sein src NICHT selbst: es legt die
*	Adresse in die Schlange des ContentGenerators (queue_document), und abgearbeitet wird sie
*	in generate() zwischen load_structur und dem start - dann steht der Parser still.
*	STW (2026-09-27): "Das holt die Komplexitaet aus dem Baum und setzt sie in ContentGenerator."
*
*	⚠ Der Zeitpunkt ist der ganze Grund: event_initiated() feuert im tag_close, also MITTEN im
*	Parsen des umgebenden Dokuments. Ein load() von dort aus setzte einen neuen Baumindex,
*	waehrend der aeussere Parser noch in seinem Dokument steht.
*
*	Geprueft wird an drei Dokumenten (fixtures/load_a|b|c.xml):
*
*	    load_a  das Hauptdokument, laedt load_b (load) und load_d (embedded) mit - und
*	            load_c hinter sector="niemand"
*	    load_b  laedt load_a ZURUECK: der Kreis
*	    load_c  darf nie geladen werden
*
*	Die vier Aussagen:
*	  1. load_b ist geladen          (mitladen traegt)
*	  2. load_c ist NICHT geladen    (hinter mayEnter-Nein wird nicht geladen, wie __echo)
*	  3. der Kreis endet             (kein zweites Parsen: load() gibt den vorhandenen
*	                                  Baum zurueck, also feuert kein zweites event_initiated)
*	  4. genau DREI Baeume           (⚠ und nicht drei: bis realpath() in drain_documents
*	                                  stand, kam dieselbe Datei einmal relativ und einmal
*	                                  absolut herein - loaded_URI vergleicht Zeichenketten,
*	                                  und der Kreis legte einen Doppelgaenger an)
*
*	Gelesen wird ueber __where_am_i scope=global: es fragt jeden GELADENEN Baum und nennt je
*	Treffer seinen Baum - damit ist "welche Dokumente liegen im Parser" von aussen messbar,
*	ohne ins Log zu sehen.
*
*	Aufruf (Server muss laufen, ./server.sh):
*	    php -d error_reporting=E_ERROR test/Integration/tree_load.php
*/
$base = getenv('QPORTAL_FIXTURE_URL') ?: 'https://localhost:8002/test/Integration/fixture_entry.php';

/* Schluessel wie in tree_passthrough.php: aus der lokalen Config, der hoechste zuerst */
$token = '';
if (is_file(__DIR__ . '/../../mod_lib.php') && is_file(__DIR__ . '/../../config/config.ini'))
{
	require_once(__DIR__ . '/../../mod_lib.php');
	$cfg   = parse_ini_file_multi(__DIR__ . '/../../config/config.ini', true);
	$liste = intern_key_list($cfg['intern']['key'] ?? []);
	usort($liste, fn($a, $b) => $b['level'] <=> $a['level']);
	if ($liste) $token = $liste[0]['token'];
}

/* ⚠ Der Schluessel geht auf ZWEI Wegen hinaus. Authorization ist die uebliche Tuer, aber
*  manche Server reichen sie nicht an PHP weiter (gemessen 2026-09-26 bei IONOS: weder
*  SetEnvIf noch die Umschreibvariante noch CGIPassAuth wirkten). X-QPortal-Token geht
*  diesen Weg nicht; index.php liest sie, wenn Authorization nichts ergab. */
function post($payload, $doc)
{
	global $base, $token;

	$ch = curl_init($base . '?doc=' . urlencode($doc));
	curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
		CURLOPT_HTTPHEADER     => array_filter(['Content-Type: application/json',
		                                        'Accept: application/json',
		                                        $token !== '' ? 'Authorization: Bearer ' . $token : null,
		                                        $token !== '' ? 'X-QPortal-Token: ' . $token : null]),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_TIMEOUT        => 30
	]);
	$body = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$mime = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);

	return [$code, (string) $body, $mime];
}

$results = [];
function result($name, $ok, $note)
{
	global $results;
	$results[] = [$name, $ok, $note];
}

/* Die Tuerschilder aller geladenen Baeume - nach aussen ueber __to_owner */
[$code, $body, $mime] = post(['Identifire' => '*',
                              'Command' => ['Name' => '__where_am_i',
                                            'Attribute' => ['scope' => 'global'],
                                            'Value' => ['Identifire' => '*',
                                                        'Command' => ['Name' => '__to_owner']]]],
                             'load_a');

echo "qPortal mode=load - Pruefstand\nZiel: $base?doc=load_a\n" . str_repeat('-', 78) . "\n";

/* ⚠ Ein 200 allein beweist nichts: kommt text/html zurueck, hat qPortal den Befehl nicht
*  ausgefuehrt, sondern ein Dokument gerendert - meist ein fehlender Schluessel. */
if ($code !== 200 || false !== stripos($mime, 'text/html'))
{
	fwrite(STDERR, "Keine JSON-Antwort (HTTP $code, $mime) - laeuft der Server, stimmt der Schluessel?\n");
	exit(2);
}

$antwort = json_decode($body, true);

if (!is_array($antwort))
{
	fwrite(STDERR, "Antwort ist kein JSON:\n" . substr($body, 0, 400) . "\n");
	exit(2);
}

/* Je Treffer nennt __where_am_i seinen Baum. Daraus die Menge der geladenen Dokumente. */
$baeume = [];
foreach ($antwort as $eintrag)
	foreach ($eintrag['value']['hits'] ?? [] as $treffer)
		if (isset($treffer['tree'])) $baeume[basename((string) $treffer['tree'])] = true;

$namen = [];
foreach ($antwort as $eintrag)
	foreach ($eintrag['value']['hits'] ?? [] as $treffer)
		if (isset($treffer['name'])) $namen[(string) $treffer['name']] = true;

result('load_a ist da',        isset($baeume['load_a.xml']), 'das Hauptdokument, ueber ?doc=load_a');
result('load_b mitgeladen',    isset($baeume['load_b.xml']), 'mode=load hat die Adresse in die Schlange gelegt');
result('load_c NICHT geladen', !isset($baeume['load_c.xml']), 'sector="niemand" - hinter mayEnter-Nein wird nicht geladen');
result('load_d eingebettet',   isset($baeume['load_d.xml']),
       'mode=embedded laedt auch - attached UND load in einem Wort');
result('genau drei Baeume',    count($baeume) === 3,
       'a, b (load) und d (embedded) - kein Doppelgaenger, realpath() bringt relativ und absolut auf eine Adresse (' . count($baeume) . ')');
result('der Kreis endet',      isset($namen['zurueck']),
       'load_b zeigt auf load_a zurueck und ist trotzdem fertig geworden');

$ok = 0; $fail = 0;
foreach ($results as [$name, $good, $note])
{
	printf("[%s] %-26s %s\n", $good ? '  ok  ' : 'FEHLER', $name, $note);
	$good ? $ok++ : $fail++;
}
echo str_repeat('-', 78) . "\n";
echo 'Baeume im Parser: ' . implode(', ', array_keys($baeume)) . "\n";
echo "$ok gelaufen, $fail fehlgeschlagen\n";
exit($fail > 0 ? 1 : 0);
