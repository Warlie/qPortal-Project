<?PHP
/*
* stored.php - eigene, benannte Befehlsketten in einer Tabelle.
*
* Drei Primitives im allgemeinen Namensraum:
*   __set_cmd    legt eine Kette an  (Attribute: ns, ln, name, description, param;
*                Value = der zu speichernde Befehlsblock, verbatim abgelegt)
*   __get_cmd    liest die rohe Definition zurueck (mit %platzhaltern%)
*   __remove_cmd loescht eine Kette
*
* Danach der Ladeteil: beim Start liest dieses File die Tabelle und haengt jede Zeile
* als Befehl in die Registry - unter ihrem ns+ln+name, gleichberechtigt neben den
* Primitives. Dadurch erscheint eine eigene Kette in __info und wird wie jeder andere
* Befehl gefeuert. Beim Aufruf ueberschreiben die mitgeschickten Attribute die im
* param hinterlegten Vorgabewerte; %name% im Block wird durch den Wert ersetzt.
*
* Speicher statt Ausfuehren: der Value-Block wird beim Anlegen NICHT gefeuert, sondern
* als Text abgelegt. Ausgefuehrt wird er erst, wenn der benannte Befehl aufgerufen wird.
* Das ist dieselbe Trennung wie bei einem Dokument, das ohne start still liegt.
*/

const QP_CMD_TABLE = 'qp_stored_command';

/* Legt die Tabelle an, falls sie fehlt. Idempotent und still - kein ENGINE/CHARSET,
*  damit die DB-Vorgabe gilt und nichts kollidiert. Die Schluessellaengen bleiben klein
*  genug fuer einen dreispaltigen Primaerschluessel auch unter utf8mb4. */
function qp_cmd_ensure_table($db)
{
	$db->SQL(
		'CREATE TABLE IF NOT EXISTS ' . QP_CMD_TABLE . ' ('
		. ' ns VARCHAR(190) NOT NULL DEFAULT \'\','
		. ' ln VARCHAR(60) NOT NULL DEFAULT \'\','
		. ' name VARCHAR(120) NOT NULL,'
		. ' description TEXT,'
		. ' param_json TEXT,'
		. ' body_json MEDIUMTEXT,'
		. ' PRIMARY KEY (ns, ln, name) )'
	);
}

/* Setzt %name% in jedem String-Blatt der Struktur ein. Arbeitet auf einer Kopie (der
*  Parameter kommt per Wert herein), damit die im Speicher gehaltene Vorlage unberuehrt
*  bleibt. Rekursiv ueber flache Arrays - keine Objektgraphen, keine Textchirurgie am
*  JSON. */
if (!function_exists('qp_cmd_fill'))
{
	function qp_cmd_fill(array $block, array $params): array
	{
		array_walk_recursive($block, function(&$leaf) use ($params) {
			if (is_string($leaf))
				foreach ($params as $k => $v)
					$leaf = str_replace('%' . $k . '%', (string)$v, $leaf);
		});
		return $block;
	}
}

try {
	$reg = $content->getRegObj();
	$reg->_useGeneral();

	/* ---------------------------------------------------------------- __set_cmd */

	$reg->__set_cmd = function($node, $obj, $event)
		{
			$s    = $event->get_Result_Array();
			$a    = $s['Command']['Attribute'] ?? [];
			$ns   = $a['ns']   ?? '';
			$ln   = $a['ln']   ?? '';
			$name = $a['name'] ?? '';

			if ($name === '')
				throw new Exception('__set_cmd: name fehlt');

			$desc  = $a['description'] ?? '';
			$param = $a['param']       ?? [];
			$body  = $s['Command']['Value'] ?? null;

			if (empty($body))
				throw new Exception('__set_cmd: kein Value-Block zum Speichern');

			$db = $node->get_contentGen()->getSQLObj();
			qp_cmd_ensure_table($db);

			$e = fn($v) => $db->escape($v);
			$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

			/* Neu anlegen oder eine gleichnamige Kette ersetzen - der Name ist der
			*  Schluessel, nicht der Inhalt. */
			$db->SQL(
				'INSERT INTO ' . QP_CMD_TABLE
				. ' (ns, ln, name, description, param_json, body_json) VALUES ('
				. '\'' . $e($ns)   . '\','
				. '\'' . $e($ln)   . '\','
				. '\'' . $e($name) . '\','
				. '\'' . $e($desc) . '\','
				. '\'' . $e(json_encode($param, $flags)) . '\','
				. '\'' . $e(json_encode($body,  $flags)) . '\')'
				. ' ON DUPLICATE KEY UPDATE description=VALUES(description),'
				. ' param_json=VALUES(param_json), body_json=VALUES(body_json)'
			);
			return true;
		};

	$reg->addLog(function($node, $obj, $event) {
			$a = $event->get_Result_Array()['Command']['Attribute'] ?? [];
			return '__set_cmd: ' . ($a['ns'] ?? '') . '#' . ($a['name'] ?? '?')
				. ' gespeichert';
		}, 3);

	$reg->addDescription(
		'Legt eine benannte Befehlskette in der Tabelle ' . QP_CMD_TABLE . ' ab. Der'
		. ' Value-Block wird nicht gefeuert, sondern verbatim gespeichert - ausgefuehrt'
		. ' wird er erst, wenn die Kette unter ihrem Namen aufgerufen wird. Beim naechsten'
		. ' Start haengt sich die Kette selbst in die Registry und erscheint in __info.'
		. ' Schreibender Befehl.',
		[
			'name'  => ['description' => 'Name des Befehls, unter dem er aufrufbar wird.',
			            'required'    => true],
			'ns'    => ['description' => 'Namensraum; leer = die allgemeinen Primitives.',
			            'required'    => false],
			'ln'    => ['description' => 'Lokaler Name (Knotentyp); leer = die Auffangebene.',
			            'required'    => false],
			'description' => ['description' => 'Text fuer __info.', 'required' => false],
			'param' => ['description' => 'Objekt name->vorgabewert. Im Value-Block gilt'
			                          . ' %name% als Platzhalter; beim Aufruf ueberschreibt'
			                          . ' ein gleichnamiges Attribut den Vorgabewert.',
			            'required'    => false]
		]);

	/* ---------------------------------------------------------------- __get_cmd */

	$reg->__get_cmd = function($node, $obj, $event)
		{
			$s    = $event->get_Result_Array();
			$a    = $s['Command']['Attribute'] ?? [];
			$ns   = $a['ns']   ?? '';
			$ln   = $a['ln']   ?? '';
			$name = $a['name'] ?? '';

			$db = $node->get_contentGen()->getSQLObj();
			qp_cmd_ensure_table($db);
			$e = fn($v) => $db->escape($v);

			$db->SQL(
				'SELECT ns, ln, name, description, param_json, body_json FROM '
				. QP_CMD_TABLE
				. ' WHERE ns=\'' . $e($ns) . '\' AND ln=\'' . $e($ln)
				. '\' AND name=\'' . $e($name) . '\''
			);

			$row = is_object($db->table_db) ? $db->table_db->fetch_assoc() : null;

			$out = is_null($row) ? ['found' => false, 'name' => $name] : [
				'found'       => true,
				'ns'          => $row['ns'],
				'ln'          => $row['ln'],
				'name'        => $row['name'],
				'description' => $row['description'],
				'param'       => json_decode($row['param_json'] ?? '[]', true),
				'body'        => json_decode($row['body_json']  ?? 'null', true)
			];

			$node->get_contentGen()->setResponse(json_encode(
				$out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			return true;
		};

	$reg->addLog(fn($node, $obj, $event) =>
		'__get_cmd: ' . ($event->get_Result_Array()['Command']['Attribute']['name'] ?? '?'), 5);

	$reg->addDescription(
		'Liest die rohe Definition einer gespeicherten Kette zurueck - mit den %platzhaltern%,'
		. ' also so, wie sie abgelegt wurde. Antwortet selbst als JSON. Fehlt die Kette,'
		. ' kommt found=false. Lesender Befehl.',
		[
			'name' => ['description' => 'Name der Kette.',                      'required' => true],
			'ns'   => ['description' => 'Namensraum; leer = Primitives.',       'required' => false],
			'ln'   => ['description' => 'Lokaler Name; leer = Auffangebene.',   'required' => false]
		]);

	/* ------------------------------------------------------------- __remove_cmd */

	$reg->__remove_cmd = function($node, $obj, $event)
		{
			$s    = $event->get_Result_Array();
			$a    = $s['Command']['Attribute'] ?? [];
			$ns   = $a['ns']   ?? '';
			$ln   = $a['ln']   ?? '';
			$name = $a['name'] ?? '';

			if ($name === '')
				throw new Exception('__remove_cmd: name fehlt');

			$db = $node->get_contentGen()->getSQLObj();
			qp_cmd_ensure_table($db);
			$e = fn($v) => $db->escape($v);

			$db->SQL(
				'DELETE FROM ' . QP_CMD_TABLE
				. ' WHERE ns=\'' . $e($ns) . '\' AND ln=\'' . $e($ln)
				. '\' AND name=\'' . $e($name) . '\''
			);
			return true;
		};

	$reg->addLog(fn($node, $obj, $event) =>
		'__remove_cmd: ' . ($event->get_Result_Array()['Command']['Attribute']['name'] ?? '?'), 3);

	$reg->addDescription(
		'Loescht eine gespeicherte Befehlskette aus der Tabelle. Wirkt erst beim naechsten'
		. ' Start, weil die Registry pro Request aus der Tabelle aufgebaut wird. Schreibender'
		. ' Befehl.',
		[
			'name' => ['description' => 'Name der Kette.',                    'required' => true],
			'ns'   => ['description' => 'Namensraum; leer = Primitives.',     'required' => false],
			'ln'   => ['description' => 'Lokaler Name; leer = Auffangebene.', 'required' => false]
		]);

	/* --------------------------------------------------- Laden und Registrieren */

	$db = $content->getSQLObj();
	qp_cmd_ensure_table($db);
	$db->SQL('SELECT ns, ln, name, description, param_json, body_json FROM ' . QP_CMD_TABLE);

	$rows = [];
	if (is_object($db->table_db))
		while ($r = $db->table_db->fetch_assoc())
			$rows[] = $r;

	foreach ($rows as $row)
	{
		$ns   = $row['ns'];
		$ln   = $row['ln'];
		$name = $row['name'];

		$defaults = json_decode($row['param_json'] ?? '[]',   true) ?: [];
		$body     = json_decode($row['body_json']  ?? 'null', true);

		if (is_null($body))
			continue;   // kaputte Zeile - lieber ueberspringen als beim Feuern platzen

		/* Kontext waehlen: der leere Namensraum existiert schon, ein eigener wird bei
		*  Bedarf angelegt; der lokale Name wird in jedem Fall sichergestellt. */
		if ($ns === '') $reg->_useGeneral();
		else            $reg->_addNS($ns);
		$reg->_addLN($ln);

		/* Nicht ueber einen bestehenden Befehl schreiben - eine Kollision mit einem
		*  Primitive soll auffallen, nicht ihn verdecken. */
		if (isset($reg->$name))
		{
			global $logger_class;
			$logger_class->setAssert('stored.php: "' . $ns . '#' . $name
				. '" kollidiert mit einem vorhandenen Befehl - uebersprungen', 0);
			continue;
		}

		$reg->$name = function($node, $obj, $event) use ($defaults, $body)
			{
				$s    = $event->get_Result_Array();
				/* Aufrufwerte ueberschreiben Vorgaben (linke Seite gewinnt bei +) */
				$args = ($s['Command']['Attribute'] ?? []) + $defaults;

				$filled = qp_cmd_fill($body, $args);
				$node->hold_messages($filled, $obj);
				return true;
			};

		$attrDescr = [];
		foreach ($defaults as $k => $v)
			$attrDescr[$k] = ['description' => 'Vorgabe: ' . $v, 'required' => false];

		$reg->addDescription(
			($row['description'] !== '' && !is_null($row['description']))
				? $row['description']
				: 'Gespeicherte Befehlskette.',
			$attrDescr);

		$reg->addLog(function($node, $obj, $event) use ($name) {
				return $name . ' (gespeicherte Kette) auf ' . $node->full_URI();
			}, 5);
	}

} catch (Exception $e) {
	echo "Fehler: " . $e->getMessage();
}
?>
