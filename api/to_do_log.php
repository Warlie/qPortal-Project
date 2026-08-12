<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// ── DB config ─────────────────────────────────────────────────────────────────
// Zugangsdaten kommen aus config/config.ini (Fallback config/default.ini), wie
// in index.php. Welches Profil gilt, steht dort unter [todolog] profile:
// leer = der Abschnitt [database] selbst, sonst dessen ext.<profile>.*-Zeilen.
// Der config-Ordner wird von hier aus nach oben gesucht, damit diese Datei
// umziehen kann, ohne dass ein relativer Pfad nachgezogen werden muss.

const CONFIG_SEARCH_DEPTH = 5;

/**
 * Sucht config/config.ini (sonst config/default.ini) ab dem eigenen Verzeichnis
 * aufwaerts und liefert das geparste Ini-Array, oder null wenn nichts lesbar war.
 */
function todolog_read_config(): ?array
{
    $dir = __DIR__;

    for ($up = 0; $up < CONFIG_SEARCH_DEPTH; $up++) {
        foreach (['config/config.ini', 'config/default.ini'] as $rel) {
            $path = $dir . '/' . $rel;
            if (!is_readable($path)) continue;

            $ini = parse_ini_file($path, true);
            if (is_array($ini)) return $ini;

            error_log("to_do_log: {$path} ist nicht lesbar oder fehlerhaft — suche weiter");
        }

        $parent = dirname($dir);
        if ($parent === $dir) break;  // Wurzel erreicht
        $dir = $parent;
    }

    return null;
}

$todolog_ini = todolog_read_config();

if ($todolog_ini === null) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'config/config.ini nicht gefunden (ab ' . __DIR__ . ' aufwaerts gesucht)',
    ]);
    exit;
}

$todolog_db      = $todolog_ini['database'] ?? [];
$todolog_profile = trim((string)($todolog_ini['todolog']['profile'] ?? ''));

// Profil-Zeilen (ext.<profile>.URL usw.) ueber die Grundwerte legen. Die
// Punkt-Schluessel stehen so in der Ini; parse_ini_file laesst sie literal
// stehen, deshalb hier direkt adressiert statt ueber qPortals mod_lib.php —
// das haelt diese Datei frei von Abhaengigkeiten zum Elternprojekt.
if ($todolog_profile !== '') {
    $todolog_hit = false;

    foreach (['URL', 'User', 'PWST', 'db_name', 'codeset'] as $todolog_key) {
        $todolog_ext = 'ext.' . $todolog_profile . '.' . $todolog_key;
        if (array_key_exists($todolog_ext, $todolog_db)) {
            $todolog_db[$todolog_key] = $todolog_db[$todolog_ext];
            $todolog_hit = true;
        }
    }

    if (!$todolog_hit) {
        error_log("to_do_log: [todolog] profile=\"{$todolog_profile}\" hat keine "
                . "ext.{$todolog_profile}.*-Eintraege in [database] — nutze Grundprofil");
    }
}

define('DB_HOST', (string)($todolog_db['URL'] ?? 'localhost'));
define('DB_NAME', (string)($todolog_db['db_name'] ?? ''));
define('DB_USER', (string)($todolog_db['User'] ?? ''));
define('DB_PASS', (string)($todolog_db['PWST'] ?? ''));

if (DB_NAME === '') {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Kein db_name in [database]'
                   . ($todolog_profile !== '' ? " (Profil \"{$todolog_profile}\")" : ''),
    ]);
    exit;
}

const LOG_TABLE = 'to_do_log';
const ELEM_TABLE = 'odysseey_elements';

// ── Routing table ─────────────────────────────────────────────────────────────
// Maps command names to handler functions.
// Each handler receives (PDO $pdo, array $row) and must throw on failure.
// Handlers run atomically BEFORE the log entry is written.

const COMMAND_ROUTES = [
    'inventory' => 'route_inventory',
    'moveTo'    => 'route_moveTo',
    'claim'     => 'route_claim',
    'release'   => 'route_release',
    'spawn'     => 'route_spawn',
    'despawn'   => 'route_despawn',
];

/**
 * Removes a spawned family copy (admin panel). Safety net: only names with
 * a numeric family suffix (template_N) can be despawned — originals stay.
 * Idempotent: already gone is fine. Clients remove the victim from their
 * place lists when the log row arrives; fully gone after the next boot.
 */
function route_despawn(PDO $pdo, array $row): void
{
    $name = (string)$row['param'];
    if (!preg_match('/_\d+$/', $name)) {
        throw new \RuntimeException("Despawn nur fuer Familien-Kopien (name_N): {$name}");
    }
    $del = $pdo->prepare(
        "DELETE FROM `" . ELEM_TABLE . "` WHERE element_name = ? AND for_schema = 'Character'");
    $del->execute([$name]);
}

/**
 * Instantiates a copy of a template element (admin spawn card, "Herbeirufung").
 *
 * param JSON: { "template": "<element_name>", "position": "<positionInGame>" }
 * A plain-string param stays an announcement-only spawn (clients reload an
 * existing element) and passes through untouched.
 *
 * The copy gets the next free numeric suffix (template_1, template_2, ...,
 * atomic via row lock on the template), the sender's position, and a fresh
 * toDoCounter so clients never replay old history onto the newborn.
 * The handler rewrites the row IN PLACE — the log announces the COPY,
 * not the template, so every client hot-loads the right element.
 */
function route_spawn(PDO $pdo, array &$row): void
{
    $param = json_decode((string)$row['param'], true);
    if (!is_array($param) || !isset($param['template'])) {
        return; // reine Ankuendigung: bestehendes Element nachladen
    }
    $template = (string)$param['template'];
    $position = (string)($param['position'] ?? '');

    $pdo->beginTransaction();
    try {
        $sel = $pdo->prepare(
            "SELECT * FROM `" . ELEM_TABLE . "` WHERE element_name = ? FOR UPDATE");
        $sel->execute([$template]);
        $src = $sel->fetch();
        if (!$src) {
            throw new \RuntimeException("Spawn-Vorlage nicht gefunden: {$template}");
        }

        // naechste freie Nummer der Familie template_N
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $template) . '\\_%';
        $num = $pdo->prepare(
            "SELECT MAX(CAST(SUBSTRING(element_name, ?) AS UNSIGNED)) AS n
             FROM `" . ELEM_TABLE . "` WHERE element_name LIKE ?");
        $num->execute([strlen($template) + 2, $like]);
        $copyName = $template . '_' . ((int)($num->fetch()['n'] ?? 0) + 1);

        $model = json_decode((string)$src['element_model'], true, 512, JSON_THROW_ON_ERROR);
        $model['elementName'] = $copyName;
        // isActive:true = "dieser Spawner betreibt das Monster": sein Client
        // speichert dessen Effekte (Verteil-Regel: nur der Aktive speichert)
        $model['isActive']    = (bool)($param['isActive'] ?? false);
        $model['user-tag']    = (string)($param['userTag'] ?? '');
        if ($position !== '') {
            $model['positionInGame'] = $position;
        }
        $model['toDoCounter'] = (int)$pdo
            ->query("SELECT COALESCE(MAX(ID),0) FROM `" . LOG_TABLE . "`")->fetchColumn();

        $ins = $pdo->prepare(
            "INSERT INTO `" . ELEM_TABLE . "`
             (element_name, element_description, element_chunk, element_model,
              for_game_engine, for_schema, element_config)
             VALUES (?,?,?,?,?,?,?)");
        $ins->execute([
            $copyName,
            $src['element_description'],
            $src['element_chunk'],
            json_encode($model, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            $src['for_game_engine'],
            $src['for_schema'],
            $src['element_config'],
        ]);

        // Geburts-moveTo: die Gesichter-Leiste der Clients wird ueber das
        // Replay von moveTo-Zeilen befuellt (moveActorsPosition→addToPosition).
        // Ohne Historie steht der Neuling nirgends — diese Zeile ist seine
        // erste. Ihre ID liegt ueber dem toDoCounter der Kopie, der Min-Cursor
        // stellt sie zu, sobald der Client den Neuling konstruiert hat.
        $birthPos = $position !== '' ? $position : (string)($model['positionInGame'] ?? '');
        if ($birthPos !== '') {
            $log = $pdo->prepare(
                "INSERT INTO `" . LOG_TABLE . "` (`ID_Name`,`ID_From`,`command`,`param`)
                 VALUES (?,?,?,?)");
            $log->execute([$copyName, $row['ID_From'], 'moveTo', $birthPos]);
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $row['ID_Name'] = $copyName;
    $row['param']   = $copyName;
}

/**
 * Claims a character for a user (admin identity switch: "als Koeter spielen").
 *
 * param: user-tag string of the claiming user.
 * Atomic: succeeds only if the character is unclaimed (empty user-tag)
 * or already claimed by the same user.
 * Der user-tag IST die Besessenheits-Markierung ("possessed by <tag>").
 * isActive wird NICHT mehr angefasst: aktiv = der leitende Charakter
 * (genau einer, Milena), Puppen empfangen nur — sonst bleibt ein
 * Charakter fuer immer aktiv, wenn der Admin ohne release schliesst.
 */
function route_claim(PDO $pdo, array $row): void
{
    if (trim((string)$row['param']) === '') {
        throw new \RuntimeException("Claim ohne user-tag (param) ist nicht erlaubt");
    }

    $patch = json_encode(['user-tag' => $row['param']],
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    $upd = $pdo->prepare(
        "UPDATE `" . ELEM_TABLE . "`
         SET element_model = JSON_MERGE_PATCH(element_model, ?)
         WHERE element_name = ?
           AND for_schema = 'Character'
           AND (JSON_VALUE(element_model, '$.\"user-tag\"') IS NULL
                OR JSON_VALUE(element_model, '$.\"user-tag\"') = ''
                OR JSON_VALUE(element_model, '$.\"user-tag\"') = ?)"
    );
    $upd->execute([$patch, $row['ID_Name'], $row['param']]);
    if ($upd->rowCount() === 0 && !tag_matches($pdo, $row['ID_Name'], $row['param'])) {
        // rowCount zaehlt GEAENDERTE Zeilen — Re-Claim durch den Besitzer
        // aendert nichts, ist aber idempotent erlaubt (daher die Nachpruefung)
        throw new \RuntimeException(
            "Claim fehlgeschlagen: {$row['ID_Name']} ist vergeben oder kein Character");
    }
}

/** true, wenn der Character existiert und sein user-tag exakt $tag ist */
function tag_matches(PDO $pdo, string $name, string $tag): bool
{
    $sel = $pdo->prepare(
        "SELECT JSON_VALUE(element_model, '$.\"user-tag\"') AS tag
         FROM `" . ELEM_TABLE . "` WHERE element_name = ? AND for_schema = 'Character'"
    );
    $sel->execute([$name]);
    $cur = $sel->fetch();
    return $cur !== false && (string)$cur['tag'] === $tag;
}

/**
 * Releases a character (identity switch back: "wieder er selbst sein").
 *
 * param: user-tag of the releasing user — only the owner may release.
 * Loescht nur den user-tag; isActive bleibt unberuehrt (release konnte
 * sonst den leitenden Charakter degradieren, obwohl claim ihn nie befoerdert).
 */
function route_release(PDO $pdo, array $row): void
{
    $patch = json_encode(['user-tag' => ''],
        JSON_THROW_ON_ERROR);

    $upd = $pdo->prepare(
        "UPDATE `" . ELEM_TABLE . "`
         SET element_model = JSON_MERGE_PATCH(element_model, ?)
         WHERE element_name = ?
           AND for_schema = 'Character'
           AND JSON_VALUE(element_model, '$.\"user-tag\"') = ?"
    );
    $upd->execute([$patch, $row['ID_Name'], $row['param']]);
    if ($upd->rowCount() === 0 && !tag_matches($pdo, $row['ID_Name'], '')) {
        // Doppel-Release ist idempotent okay (Charakter schon frei)
        throw new \RuntimeException(
            "Release fehlgeschlagen: {$row['ID_Name']} gehoert nicht '{$row['param']}'");
    }
}

/**
 * Atomically applies a full inventory replacement + skrilla delta to one character.
 *
 * param JSON: { "bag": [...], "cashflow": <int> }
 *   bag      — complete new inventory array (replaces existing)
 *   cashflow — signed skrilla delta (positive = gain, negative = loss)
 */
/**
 * Overwrites positionInGame for one character.
 *
 * param: position string, e.g. "Buildings_places.NakamuraStreet#Nakamura.root.Place.nextPlaces.0.id"
 */
function route_moveTo(PDO $pdo, array $row): void
{
    $upd = $pdo->prepare(
        "UPDATE `" . ELEM_TABLE . "`
         SET element_model = JSON_SET(element_model, '$.positionInGame', ?)
         WHERE element_name = ?"
    );
    $upd->execute([$row['param'], $row['ID_Name']]);
    if ($upd->rowCount() === 0) {
        // rowCount zaehlt GEAENDERTE Zeilen (gleiche rowCount-Falle wie bei
        // claim) — ein moveTo auf die aktuelle Position ist idempotent okay
        $chk = $pdo->prepare(
            "SELECT 1 FROM `" . ELEM_TABLE . "` WHERE element_name = ?");
        $chk->execute([$row['ID_Name']]);
        if (!$chk->fetch()) {
            throw new \RuntimeException("Character not found: {$row['ID_Name']}");
        }
    }
}

function route_inventory(PDO $pdo, array $row): void
{
    $param    = json_decode($row['param'], true, 512, JSON_THROW_ON_ERROR);
    $bag      = json_encode($param['bag'] ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $cashflow = (int)($param['cashflow'] ?? 0);
    $name     = $row['ID_Name'];

    $pdo->beginTransaction();
    try {
        $sel = $pdo->prepare(
            "SELECT JSON_EXTRACT(element_model, '$.stats.skrilla') AS skrilla
             FROM `" . ELEM_TABLE . "` WHERE element_name = ? FOR UPDATE"
        );
        $sel->execute([$name]);
        $cur = $sel->fetch();
        if (!$cur) {
            throw new \RuntimeException("Character not found: {$name}");
        }

        $newSkrilla = (int)$cur['skrilla'] + $cashflow;

        // JSON_QUERY(?, '$') re-parses the bag string as a JSON document (MariaDB)
        $upd = $pdo->prepare(
            "UPDATE `" . ELEM_TABLE . "`
             SET element_model = JSON_SET(
                 JSON_SET(element_model, '$.stats.skrilla', ?),
                 '$.inventory', JSON_QUERY(?, '$')
             )
             WHERE element_name = ?"
        );
        $upd->execute([$newSkrilla, $bag, $name]);
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// ── Request handling ──────────────────────────────────────────────────────────

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $method = $_SERVER['REQUEST_METHOD'];

    // ── GET ───────────────────────────────────────────────────────────────────

    if ($method === 'GET') {
        $sinceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($sinceId > 0) {
            $stmt = $pdo->prepare(
                "SELECT `ID`, `ID_Name`, `ID_From`, `command`, `param`
                 FROM `" . LOG_TABLE . "`
                 WHERE `ID` > :id
                 ORDER BY `ID` ASC"
            );
            $stmt->execute([':id' => $sinceId]);
        } else {
            $stmt = $pdo->query(
                "SELECT `ID`, `ID_Name`, `ID_From`, `command`, `param`
                 FROM `" . LOG_TABLE . "`
                 ORDER BY `ID` ASC"
            );
        }

        $rows = $stmt->fetchAll();
        echo json_encode([
            'status' => 'ok',
            'count'  => count($rows),
            'data'   => $rows,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ── POST ──────────────────────────────────────────────────────────────────

    if ($method === 'POST') {
        $input       = file_get_contents('php://input');
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        $data = stripos($contentType, 'application/json') !== false
            ? json_decode($input, true, 512, JSON_THROW_ON_ERROR)
            : $_POST;

        foreach (['ID_Name', 'ID_From', 'command', 'param'] as $field) {
            if (!array_key_exists($field, $data)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Missing field: {$field}"],
                    JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Run server-side handler if one is registered for this command
        $cmd = $data['command'];
        if (array_key_exists($cmd, COMMAND_ROUTES)) {
            $handler = COMMAND_ROUTES[$cmd];
            $handler($pdo, $data);
        }

        // Log for client polling regardless
        $ins = $pdo->prepare(
            "INSERT INTO `" . LOG_TABLE . "` (`ID_Name`,`ID_From`,`command`,`param`)
             VALUES (:name, :from, :cmd, :param)"
        );
        $ins->execute([
            ':name'  => $data['ID_Name'],
            ':from'  => $data['ID_From'],
            ':cmd'   => $data['command'],
            ':param' => $data['param'],
        ]);

        echo json_encode([
            'status'      => 'ok',
            'inserted_id' => (int)$pdo->lastInsertId(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;

} catch (\RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()],
        JSON_UNESCAPED_UNICODE);
} catch (\JsonException $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON: ' . $e->getMessage()],
        JSON_UNESCAPED_UNICODE);
}
