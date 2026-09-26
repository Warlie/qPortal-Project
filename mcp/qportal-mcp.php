#!/usr/bin/env php
<?PHP
/*
* qPortal MCP — stdio-Adapter
* ===========================
* Spricht MCP (JSON-RPC ueber Zeilen auf stdin/stdout) und reicht nach innen an den
* Intern-Kanal von qPortal weiter (POST JSON auf ?i=__intern).
*
* Bewusst duenn: der Adapter kennt DREI Werkzeuge, davon zwei die Selbstauskunft
* (__info_ns, __info) und eines den durchgereichten Befehl. Was qPortal kann, sagt
* qPortal selbst — hier steht kein zweites Vokabular, das auseinanderdriften koennte.
*
* Alles darueber hinaus kommt als PLUGIN aus mcp/plugins/*.php. Ein Plugin gibt
* Werkzeugbeschreibungen und einen Aufrufer zurueck und bekommt qp_command() als
* Griff - es sieht den Token NIE, der bleibt in qp_curl(). Liegt kein Plugin da,
* gibt es das Werkzeug nicht: was nie Standard sein soll, muss weglassbar sein.
* Der Kern liefert also die Faehigkeit mit, nicht die Oeffnung.
*
* Konfiguration ueber Umgebungsvariablen:
*   QPORTAL_URL       Vorgabe https://localhost:8002/index.php
*   QPORTAL_TOKEN     Bearer-Token aus [intern] key[] der config.ini (optional)
*   QPORTAL_INSECURE  1 = Zertifikat nicht pruefen (Vorgabe bei localhost, symfony
*                     nutzt ein selbstsigniertes)
*
* Ohne Token wird die Sitzung genommen: einmal GET ?i=__intern, danach traegt der
* Cookie den Modus. Der Cookie lebt im Speicher dieses Prozesses.
*
* (C) Stefan Wegerhoff
*/

const PROTOCOL_FALLBACK = '2024-11-05';

$QP_URL      = getenv('QPORTAL_URL')   ?: 'https://localhost:8002/index.php';
$QP_TOKEN    = getenv('QPORTAL_TOKEN') ?: '';
$QP_INSECURE = getenv('QPORTAL_INSECURE');
if ($QP_INSECURE === false || $QP_INSECURE === '')
	$QP_INSECURE = (false !== strpos($QP_URL, 'localhost') || false !== strpos($QP_URL, '127.0.0.1')) ? '1' : '0';

// Cookie-Ablage nur fuer die Laufzeit dieses Prozesses.
$COOKIE_JAR = tempnam(sys_get_temp_dir(), 'qportal_mcp_');
register_shutdown_function(function() use ($COOKIE_JAR) { @unlink($COOKIE_JAR); });

$SESSION_READY = false;


/* ------------------------------------------------------------------ qPortal */

function qp_curl(string $url, ?string $post = null): array
{
	global $QP_TOKEN, $QP_INSECURE, $COOKIE_JAR;

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR,  $COOKIE_JAR);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $COOKIE_JAR);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

	if ($QP_INSECURE === '1')
	{
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	}

	$header = ['Accept: application/json'];
	if ($QP_TOKEN !== '') $header[] = 'Authorization: Bearer ' . $QP_TOKEN;

	if (!is_null($post))
	{
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$header[] = 'Content-Type: application/json';
	}

	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	$body = curl_exec($ch);
	$err  = curl_error($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);

	if (false === $body)
		throw new RuntimeException('qPortal nicht erreichbar: ' . $err);

	return ['status' => $code, 'mime' => $mime, 'body' => $body];
}

/* Bringt die Sitzung in den Intern-Modus. Mit Token unnoetig, aber harmlos. */
function qp_open_session(): void
{
	global $QP_URL, $SESSION_READY, $QP_TOKEN;

	if ($SESSION_READY || $QP_TOKEN !== '') { $SESSION_READY = true; return; }

	$sep = (false === strpos($QP_URL, '?')) ? '?' : '&';
	qp_curl($QP_URL . $sep . 'i=__intern');
	$SESSION_READY = true;
}

/* Schickt ein Intern-Kommando und liefert den Antwortkoerper.
*  Ein 200 allein beweist nichts: kommt text/html zurueck, hat qPortal die Route
*  nicht erkannt und den Indexbaum gerendert. Das wird hier benannt, nicht geschluckt.
*/
function qp_command(array $command): array
{
	global $QP_URL;

	qp_open_session();
	$res = qp_curl($QP_URL, json_encode($command, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

	if (false !== stripos($res['mime'], 'text/html'))
		$res['warning'] = 'Antwort ist text/html, nicht application/json — qPortal hat den Befehl'
		                . ' vermutlich nicht ausgefuehrt, sondern ein Dokument gerendert.';

	return $res;
}


/* ------------------------------------------------------------------ Plugins */

/* Ein Plugin ist eine Datei in mcp/plugins/, die ein Array zurueckgibt:
*
*     return ['tools' => [ <MCP-Werkzeugbeschreibung>, ... ],
*             'call'  => function(string $name, array $args, callable $qp): array];
*
*  $qp ist qp_command(): "schick dieses Kommando und gib mir die Antwort". Damit
*  braucht ein Plugin den Token nicht zu kennen - und kann ihn auch nicht
*  weitergeben. Liegt kein Plugin da, gibt es das Werkzeug nicht.
*
*  ⚠ Ein Plugin laeuft im selben Prozess: was dort steht, darf dieser Rechner tun.
*  Die Dateien gehoeren der Installation, nicht dem Kern - darum sind sie nicht
*  mitgeliefert.
*/
function plugins(): array
{
	static $geladen = null;

	if (!is_null($geladen)) return $geladen;

	$geladen = [];

	foreach (glob(__DIR__ . '/plugins/*.php') ?: [] as $datei)
	{
		$p = require $datei;

		if (!is_array($p) || !isset($p['tools']) || !is_array($p['tools']) || !is_callable($p['call'] ?? null))
		{
			fwrite(STDERR, 'qportal-mcp: ' . basename($datei)
				. " gibt kein ['tools' => [...], 'call' => fn] zurueck - uebersprungen\n");
			continue;
		}

		$geladen[] = $p;
	}

	return $geladen;
}

/* ---------------------------------------------------------------- Werkzeuge */

function tool_list(): array
{
	$werkzeuge = [
		[
			'name'        => 'qportal_info_ns',
			'description' => 'Listet die Namensraeume der qPortal-Befehlsregistry mit ihren lokalen'
			               . ' Namen und der Anzahl der dort registrierten Befehle. Der Einstieg:'
			               . ' erst hier nachsehen, welche Namensraeume es gibt, dann qportal_info'
			               . ' je Namensraum, dann qportal_command zum Ausfuehren.',
			'inputSchema' => ['type' => 'object', 'properties' => new stdClass(), 'required' => []]
		],
		[
			'name'        => 'qportal_info',
			'description' => 'Liefert die Befehle eines Namensraums mit Beschreibung und Parametern.'
			               . ' Die Beschreibungen kommen aus der Registry selbst, stehen also neben'
			               . ' dem Code, der sie umsetzt.',
			'inputSchema' => [
				'type' => 'object',
				'properties' => [
					'ns' => ['type' => 'string',
					         'description' => 'Namensraum. Leerer String = die allgemeinen'
					                        . ' Primitives. Namen liefert qportal_info_ns.'],
					'ln' => ['type' => 'string',
					         'description' => 'Lokaler Name innerhalb des Namensraums.'
					                        . ' Weglassen = alle.']
				],
				'required' => []
			]
		],
		[
			'name'        => 'qportal_command',
			'description' => 'Schickt ein Intern-Kommando an qPortal und gibt die Antwort roh'
			               . ' zurueck. Form: {"Identifire":"*","Command":{"Name":"<befehl>",'
			               . '"Attribute":{...},"Value":{...}}}. Identifire waehlt den Knoten,'
			               . ' auf dem der Befehl ankommt ("*" = der Einstiegsknoten), Value ist'
			               . ' das verschachtelte Folgekommando. Welche Befehle es gibt und welche'
			               . ' Attribute sie nehmen, sagt qportal_info — nicht raten, nachsehen.'
			               . ' Achtung: ein Skript wird nur durch "start" aktiv; ohne start liegt'
			               . ' es still und ist editierbar.',
			'inputSchema' => [
				'type' => 'object',
				'properties' => [
					'command' => ['type' => 'object',
					              'description' => 'Das Kommando als JSON-Objekt.']
				],
				'required' => ['command']
			]
		]
	];

	/* Die Werkzeuge der Plugins dahinter. Ein Plugin, das einen vorhandenen Namen
	*  belegt, wird nicht eingemischt: die drei eigenen gehen vor, sonst koennte
	*  eine Datei im Verzeichnis den durchgereichten Befehl ersetzen. */
	$eigene = array_column($werkzeuge, 'name');

	foreach (plugins() as $p)
		foreach ($p['tools'] as $t)
		{
			if (!isset($t['name']) || in_array($t['name'], $eigene, true))
			{
				fwrite(STDERR, 'qportal-mcp: Werkzeug "' . ($t['name'] ?? '?')
					. "\" kollidiert oder hat keinen Namen - uebersprungen\n");
				continue;
			}

			$werkzeuge[] = $t;
			$eigene[]    = $t['name'];
		}

	return $werkzeuge;
}

function tool_call(string $name, array $args): array
{
	switch ($name)
	{
		case 'qportal_info_ns':
			$res = qp_command(['Identifire' => '*', 'Command' => ['Name' => '__info_ns']]);
			break;

		case 'qportal_info':
			$attrib = ['ns' => $args['ns'] ?? ''];
			if (isset($args['ln'])) $attrib['ln'] = $args['ln'];
			$res = qp_command(['Identifire' => '*',
			                   'Command' => ['Name' => '__info', 'Attribute' => $attrib]]);
			break;

		case 'qportal_command':
			if (!isset($args['command']) || !is_array($args['command']))
				return ['content' => [['type' => 'text', 'text' => 'command fehlt oder ist kein Objekt.']],
				        'isError' => true];
			$res = qp_command($args['command']);
			break;

		default:
			/* Nicht meins - vielleicht eines der Plugins. Es bekommt qp_command als
			*  Griff, nicht den Token. */
			foreach (plugins() as $p)
				foreach ($p['tools'] as $t)
					if (($t['name'] ?? null) === $name)
						return ($p['call'])($name, $args, 'qp_command');

			return ['content' => [['type' => 'text', 'text' => 'Unbekanntes Werkzeug: ' . $name]],
			        'isError' => true];
	}

	$text = $res['body'];
	if (isset($res['warning']))
		$text = '[' . $res['warning'] . "]\n\n" . $text;

	return [
		'content' => [['type' => 'text', 'text' => $text]],
		'isError' => $res['status'] >= 400
	];
}


/* -------------------------------------------------------------- MCP / stdio */

function respond($id, array $result): void
{
	echo json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result],
	                 JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
}

function respond_error($id, int $code, string $message): void
{
	echo json_encode(['jsonrpc' => '2.0', 'id' => $id,
	                  'error' => ['code' => $code, 'message' => $message]],
	                 JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
}

$in = fopen('php://stdin', 'r');
stream_set_blocking($in, true);

while (false !== ($line = fgets($in)))
{
	$line = trim($line);
	if ($line === '') continue;

	$msg = json_decode($line, true);
	if (!is_array($msg)) { fwrite(STDERR, "qportal-mcp: unlesbare Zeile\n"); continue; }

	$id     = $msg['id']     ?? null;
	$method = $msg['method'] ?? '';
	$params = $msg['params'] ?? [];

	// Benachrichtigungen haben keine id und bekommen keine Antwort.
	if (is_null($id) && $method !== '') continue;

	try {
		switch ($method)
		{
			case 'initialize':
				respond($id, [
					'protocolVersion' => $params['protocolVersion'] ?? PROTOCOL_FALLBACK,
					'capabilities'    => ['tools' => new stdClass()],
					'serverInfo'      => ['name' => 'qportal', 'version' => '0.1.0']
				]);
				break;

			case 'ping':
				respond($id, new stdClass());
				break;

			case 'tools/list':
				respond($id, ['tools' => tool_list()]);
				break;

			case 'tools/call':
				respond($id, tool_call($params['name'] ?? '', $params['arguments'] ?? []));
				break;

			default:
				respond_error($id, -32601, 'Unbekannte Methode: ' . $method);
		}
	}
	catch (Throwable $e) {
		respond_error($id, -32603, $e->getMessage());
	}
}
