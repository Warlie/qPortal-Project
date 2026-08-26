# real_estate — qPortal-Instanz

Produktive qPortal-Instanz. **Der Core liegt woanders**: `~/rauhnacht/qPortal-Project`
mit eigener `CLAUDE.md` (Intern-Protokoll, Modi, Dev-Server, Skill `/qportal`). Diese
Datei ist die Code-Karte für *diese* Arbeitskopie — die Dinge, die man beim Arbeiten
immer wieder sucht.

## Kurz laufen lassen

```bash
# Prüfstand ohne Server (lädt einen Baum selbst)
php -d error_reporting=E_ERROR test/Integration/seek_scope.php

# Prüfstand über HTTP
php -S 127.0.0.1:8002 -t . &
QPORTAL_URL=http://127.0.0.1:8002/index.php php test/Integration/intern_walk.php
```

`seek_scope.php` muss vollständig grün sein — jede rote Zeile dort ist neu.
`intern_walk.php` steht bei **32 gelaufen / 1 rot** — `__get_data` ist ein Bestandsdefekt
(über den Intern-Kanal ist der Aufrufer der ContentGenerator und damit kein Knoten). Diese
Zahl ist die Vergleichsmarke: ändert sie sich, hat die letzte Änderung etwas gebrochen.

`./server.sh` startet `symfony server:start` auf **https**; die Prüfstände sprechen dann
`https://localhost:8002`. Mit `php -S` geht nur http — dann `QPORTAL_URL` setzen.

## Baum ohne index.php hochfahren

`require_once(__DIR__ . '/../bootstrap.php');` — siehe `test/bootstrap.php`. Danach:

```php
$tree = new xml_ns();
$tree->load_Stream($xml, 0, "XML");
$treffer = $tree->collect_nodes('http://www.trscript.de/tree#param');
```

Nicht von Hand zusammensuchen: die Konstanten (`XML_CASE_FOLDING_DEFAULT`, `ROOT_DIR`, …)
werden beim *Parsen* gelesen, und `Interface_ns.php` braucht `qp_workflow` vorher geladen.
Fehlt eins, bricht es mitten im Aufbau ab.

⚠ Die Baumschicht schreibt beim Laden viele Notices/Warnings (Bestand). Für Testläufe
`-d error_reporting=E_ERROR` — **nicht** im Code unterdrücken, sie sind das Korrektheitssignal.

⚠ **Mehrere Bäume brauchen `setNewTree($kennung)` vor jedem `load_Stream`.** `setNewTree`
setzt `max_idx = count(loaded_URI)` und ist die einzige Stelle, die `loaded_URI` füllt —
ohne den Aufruf landet *jeder* Baum still in Slot 0 und überschreibt den vorigen. STWs
eigener TODO steht an `xml_multitree.php:860` („das gehört ins load_Stream"). Der
Datei-Weg ruft es bereits; `load_Stream` nicht.

## Wo was liegt

| | |
|---|---|
| Baumschicht (unten → oben) | `xml` (`classes/xml_multitree.php`) → `xml_objex` → `xml_omni` (`_omni_handle.php`) → `xml_ns` → `xml_gen` (`_ns_gen.php`) |
| Knoten | `classes/ns/Interface_ns.php` (`Interface_node`) |
| Namensräume | `classes/ns/<ns>/` je mit `class_index.php`, das die Knotentypen registriert — `tree`, `rdf`, `rdfs`, `owl`, `pedl`, `svg`, `xs`, `xlink`, `mathml`, `ate` |
| Handles (Ein-/Ausgabeformate) | `classes/handles/` — `XML`, `CSV`, `RAW`, `SPARQL`, `PHP`, dazu `class_index.php` |
| Suchmodelle | `classes/search_model/<name>/` — `internal`, `xpath` (Stub), `sparql` |
| Automat | `classes/finite_state_machine/` (Acceptor, Transducer), Beispiel `classes/fs_parser/qp_workflow.php` |
| Registry-Befehle | `behavior/*.php` — per `glob` in `index.php:250` geladen |
| Plugins | `PlugIn/`, registriert in `config/default.ini` unter `[short]` |

## Knoten und Attribute

**Attribute sind Knoten.** `Interface_ns.php:633` `attribute()` setzt `set_NodeType(ATTRIBUTE)`
und `setrefprev($this)` — der Träger ist der Vorgänger, der Weg zurück ist `getRefprev()`.
`NODE=0, ATTRIBUTE=1, DATA=2`.

⚠ **`attribute()` ist die einzige Stelle, an der `attrib_ns` beschrieben wird**, und daran
hängt die Erfassung für Lookup-Tabelle, Wertmenge und Identität. Wer `attrib_ns` direkt
beschreibt, macht die Wertmenge lückenhaft — dann verliert eine Suche still einen Treffer.

**Zugriff — die häufigste Falle:**

| | |
|---|---|
| `get_ns_attribute($uri)` | **volle URI** (`namespace#name`). Roher Name trifft nie, ohne Warnung |
| `get_attribute($name)` | roher Name, andere Tabelle (`attrib`) |
| `get_ns_attribute()` ohne Argument | alle Attribute als `URI => Wert` |
| `get_ns_attribute_obj($uri)` | der Attributknoten selbst |

`full_URI()` = `namespace . '#' . type`. `name` und `type` sind **nicht** dasselbe.

**Positionsstempel** (`Interface_ns.php:369`): Element `.i.j.k`, Attribut `…@<full_URI>`,
Daten `…#<QName>`. Der Baum-Stempel (`xml_multitree.php:345`) stellt `0000.<idx>` voran;
`go_to_stamp` versteht `me` und `prev` als idx.

## Suche

`seek_node()` ist die Hülle (hängt an `result_nodes`, setzt den Cursor),
**`collect_nodes()` ist die reine Fassung** (Menge rein, Menge raus, kein Zustand):

```php
collect_nodes($type, $attrib, $data, $scope, $depth, $kind)
```

`$scope` Knoten oder Menge · `$depth` −1 descendant-or-self, 1 child, 0 self ·
`$kind` Vorgabe `NODE`, sonst `ATTRIBUTE` oder −1 für beides.
`only_child_node(true)` setzt den Suchraum auf den aktuellen Knoten.

⚠ `seek_node()` gibt `count(result_nodes) > 0` zurück — **nicht**, ob *diese* Suche etwas
fand. Deshalb steht bei den Aufrufern `flash_result()` davor.

**Struktur ist baumlokal, Bedeutung ist global.** `looking_index`, `index_attrib_count`
und `index_value_set` sind alle nach `[$idx]` geschlüsselt — gesucht wird im aktuellen Baum.
`identity_index` dagegen ist **global über alle geladenen Bäume**, wie `namespace_frameworks`
(das auch kein `$idx` hat): Identität ist eine Aussage der Bedeutung, nicht des Dokuments.
`identity_of_idx` ist nur das Nebenregister zum Aufräumen; `delete_index()` räumt darüber
die Tabellen eines entladenen Baums (`drop_index_of()`).

**Regel für alle Indizes hier: eintragen ist Pflicht, austragen nicht, nachgeprüft wird
beim Lesen.** Falsch-positiv kostet einen Durchlauf, falsch-negativ verliert still einen
Treffer. `attribute_is_current()` / `identity_is_current()` sind diese Nachprüfung.

Über die Modelle: `$tree->seek_by_model('internal')` bzw. `query_by_model($m, $ausdruck)`.
Jede Suche hält ihr eigenes Modell — der Baum wird je Anfrage durchgereicht.

## Konfiguration

`config/default.ini` ist die committete Basis mit **jedem** Schlüssel, den das System
braucht; `config/config.ini` liegt darüber und ist **nicht im Repo** — dort stehen der echte
`db_name`, `stamp_key`, Intern-Tokens und Passwörter. Geheimnisse gehören nie in
`default.ini`.

`parse_ini_file_multi` (`mod_lib.php:4`) macht aus Punkt-Schlüsseln verschachtelte Arrays:
`sparql.fuseki.endpoint = ""` wird zu `$ini_array['search']['sparql']['fuseki']['endpoint']`.

Das `sparql`-Modell kennt mehrere Quellen **nebeneinander**, nicht eine aktive: jede hat
ihren Zweig `sparql.<quelle>.*`, und `source` darin ist der Geltungsbereich *innerhalb*
dieser Quelle (Fuseki: Datensatz/Graph; intern: Baum). `sparql.use` ist die Vorgabe,
`use_source()` überschreibt sie je Aufruf — gewechselt wird die Quelle, nicht die Frage.
Pfade dürfen `__ROOT_DIR`/`__PROGRAM_DIR` enthalten, Dokumente `%NAME%` (`resolve_path`).

## Arbeitsweise

- **Committen: nur modifizierte getrackte Dateien.** Nie `git add -A`. Neue Dateien nur,
  wenn STW sie ausdrücklich nennt. Untracked und bewusst draußen: `db130399.sql`
  (Produktivdump mit IBANs), `img/`, `script/`, `template_/`, `.claude/`,
  `behavior/stored.php`, `test/Integration/intern_walk.php`.
- **Kleinschrittig, additiv daneben.** Bestandsverhalten nicht ändern, wo es niemand
  verlangt hat; neue Parameter mit Vorgaben, die alte Aufrufe unberührt lassen.
- **Kein DOM.** SAX bleibt, flache Arrays statt Objektgraphen — wegen Portierbarkeit bis C
  für Mikrocontroller.
- **Befehle, nicht Tags.** Neues Verhalten kommt über die Registry (`behavior/`), nicht
  über neue Knotenklassen.
- Ein Skript wird nur durch `start` aktiv; `__info`/`__set_cmd` sind Aspekte daneben.
- **Keine `echo`/`var_dump` im Produktionspfad.** Sie landen mitten in der Antwort und
  zerstören `JSON_RESPONSE` und jede Serialisierung. Diagnosen gehören in
  `$logger_class->setAssert($text, $stufe)`. Log-Stufen: 0 = Minimum, 5 = normal, 6 = Debug.
