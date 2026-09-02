# real_estate — qPortal-Instanz

Produktive qPortal-Instanz. **Der Core liegt woanders**: `~/rauhnacht/qPortal-Project`
mit eigener `CLAUDE.md` (Intern-Protokoll, Modi, Dev-Server, Skill `/qportal`). Diese
Datei ist die Code-Karte für *diese* Arbeitskopie — die Dinge, die man beim Arbeiten
immer wieder sucht.

## Kurz laufen lassen

```bash
# Prüfstand ohne Server (lädt einen Baum selbst)
php -d error_reporting=E_ERROR test/Integration/seek_scope.php

# Automat und SPARQL-Parser (braucht weder Server noch Baum)
php -d error_reporting=E_ERROR test/Integration/sparql_parse.php

# Prüfstand über HTTP
php -S 127.0.0.1:8002 -t . &
QPORTAL_URL=http://127.0.0.1:8002/index.php php test/Integration/intern_walk.php
```

```bash
# Sonden — sie prüfen nicht, sie ZEIGEN. Ausgabe wird gelesen, kein grün/rot.
php -d error_reporting=E_ERROR test/proben/probe_registry_vocab.php   # ausser dieser: 32/0
```

`seek_scope.php` und `sparql_parse.php` müssen vollständig grün sein — jede rote Zeile
dort ist neu. `probe_registry_vocab.php` bewacht die Naht zwischen Vokabulardokument und
`build_up()` und steht bei **32 in Ordnung / 0 rot**.
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
| Baumschicht (unten → oben) | `xml` (`classes/xml_multitree.php`) → `xml_objex` → `xml_omni` (`_omni_handle.php`) → `xml_ns`; darüber **gabelt** sie sich: `xml_gen` (`_ns_gen.php`, ⚠ von niemandem geladen und instanziiert), `xml_sparqle` (`_SPARQL.php`), `xml_xPath_sParqle` (`_xPath.php`) → `xml_semantic` (`_semantic.php`). Die Produktion baut die **Spitze**: `class_Contentgenerator.php:82` macht ein `xml_semantic`, `PHP_handle` ein `xml_xPath_sParqle`. Die Kette lädt sich selbst nach — ein `require` auf `_semantic.php` genügt |
| Vokabular des Bogens | `ontologies/registry_surface.owl`, Pfad in `config/default.ini` `[runtime] REGISTRY_VOCABULARY` |
| Knoten | `classes/ns/Interface_ns.php` (`Interface_node`) |
| Namensräume | `classes/ns/<ns>/` je mit `class_index.php`, das die Knotentypen registriert — `tree`, `rdf`, `rdfs`, `owl`, `pedl`, `svg`, `xs`, `xlink`, `mathml`, `ate` |
| Handles (Ein-/Ausgabeformate) | `classes/handles/` — `XML`, `CSV`, `RAW`, `SPARQL`, `PHP`, dazu `class_index.php` |
| Suchmodelle | `classes/search_model/<name>/` — `internal`, `xpath` (Stub), `sparql` |
| Automat | `classes/finite_state_machine/` — `Acceptor`/`Transducer` (regex je Übergang, baut einen Knotenbaum; Beispiel `classes/fs_parser/qp_workflow.php`) und `Mealy_Automat` (`class_Mealy.php`: ein Zeichen je Schritt, sammelt in eine flache Tabelle) |
| SPARQL-Parser | `classes/search_model/sparql/sparql_parser.php` — Grammatik auf dem Mealy, portiert aus `anttree/funct_parser_lib.js` |
| Beschreibungsschicht | `dcterms:` (`http://purl.org/dc/terms/`, **nicht** `elements/1.1`) für Aussagen über ein *Dokument*, `desc:` für Aussagen über *Code*. Schlüsseltabelle: `PHP_Ast_Scan::DESC_KEYS`. ⚠ `SVG_Overview_handle` schreibt bewusst weiter `elements/1.1` — Inkscape/CC-Konvention |
| Registry-Befehle | `behavior/*.php` — per `glob` in `index.php:250` geladen |
| Plugins | `PlugIn/`, registriert in `config/default.ini` unter `[short]` |

## tree:name — der führende Punkt heißt „unsichtbar"

Vom Dateisystem entlehnt (STW): ein `tree:name`, der mit `.` **beginnt**, erscheint nicht in
der Navigation. Ausgewertet in `class_Contentgenerator.php:263`, direkt neben
`securitylevel` und `sector`:

```php
if ( !(false === ($hidden = strpos( show_ns_attrib('…tree#name'), '.' ) ) )
&& intval($hidden) == 0 )   $result = false;
```

Ein Punkt *innerhalb* des Namens ist harmlos — nur Position 0 zählt. Ebenfalls versteckend:
ein fehlendes `tree:value` (Zeile darüber). In `template/xml.xml` sind 16 von 56 Namen so
markiert (`.edit`, `.service_orga`, `.save_doc`, …) — Dienste und Unteraufrufe, keine
Menüpunkte.

⚠ **Folge für Vokabular-Pläne:** solche Namen sind **keine gültigen `rdf:ID`**. `NCName`
darf mit Buchstabe oder `_` beginnen, nicht mit `.` (der Punkt ist nur Folgezeichen). Wer
tree-Knoten benennbar machen will, nimmt darum ein URI-**Fragment** (`rdf:about="#.edit"`) —
Fragmente dürfen Punkte und Schrägstriche tragen (RFC 3986), und nur damit lässt sich auch
ein Pfad als Name schreiben.

⚠ **`name` heißt nicht überall dasselbe — es hängt am Trägerelement.** Gemessen über
`template/**/*.xml`:

| Träger | NCName | Punkt | `#`-Adresse | anderes |
|---|---|---|---|---|
| `remote` | 46224 | 0 | 0 | 0 |
| `param` | 2818 | 0 | **5329** | 9 |
| `content` | 0 | 0 | **2370** | 0 |
| `wordfield` | 1818 | 0 | 0 | **27** |
| `final` | **543** | 0 | 0 | 0 |
| `tree` | **174** | **43** | 0 | 0 |
| `subtree` | 0 | 0 | **117** | 0 |

Auf `tree` und `final` ist der Name ein **Bezeichner** und sauber — 717 NCName, 43 führende
Punkte, keine Adresse, keine Beschriftung (`first` kommt in den Dokumenten nicht vor). Die
`#`-Form auf `content`/`subtree`/`param` ist ein **Zugriff**, kein Name — STWs Workaround,
um Inhalte in Dokumenten überhaupt modifizieren zu können. Auf `wordfield` stehen
**Beschriftungen** (`Bett (2)`, `Mensch in einer Gemeinschaft`).

Wer über `name` etwas aussagen will, grenzt darum **nach Knotentyp** ab, nicht über alle
Werte. Sonst zählt man 1697 Namen und schließt das Falsche.

## Prägen im Registrierungsbogen

Das Vokabular steht seit `fb32f2e` in **`ontologies/registry_surface.owl`**, nicht mehr als
`tag_open()`-Folge in `TreeEngine::build_up()`. Ein Name wird dort aus einer pedl-Grundlage
**geprägt** und ist danach als Tag brauchbar — eine PHP-Klasse je Tag braucht es nicht
(`<pedl:Object_Class rdf:ID="PhpClass"/>`, danach `<PhpClass rdf:ID="System"/>`). So
entstehen `PhpInterface`, `PhpTrait`, `PhpProperty`, `Funktion`.

**Die Naht:** in PHP bleibt nur, was sich nicht aufschreiben lässt — der Bindungsblock
(`<System>` mit `pedl:ParameterCollection`, wo `cdata_ref()` echte Objekte einhängt und
`get_Element()` die Griffe für `get_CurRef`/`set_CurRef` abgreift) und die beiden
`rdf:Bag`-Behälter, die zur Laufzeit gefüllt werden. Ein Verweis auf ein laufendes Objekt
ist kein Text.

⚠ **Reihenfolge ist Bedingung:** das Dokument wird **nach** `createTree()` geladen (erst das
legt den Namensraum mit seinem nativen Knoten an — sonst `native namespace is missing`) und
**vor** dem Bindungsblock (der benutzt Tags, die das Dokument erst prägt).

⚠ **Fehlt die Datei, bricht der Aufbau ab** — mit Absicht, siehe die nächste Warnung. Der
Bogen benennt sich selbst über `dcterms:identifier` am `owl:Ontology`; das ist eine Aussage
über das Dokument, **keine** Parserregel (`rdf:ID` löst weiter gegen den Default-Namensraum
des Baums auf, `rdf_ID.php:57`).

⚠ **Eine vergessene Prägung schlägt still fehl.** `use_ns_def_strict(true)` **wirft nicht**
bei einem ungeprägten Tag — es fällt auf einen generischen `Interface_node` zurück. Der Tag
funktioniert, die Seite rendert, nur `is_Node('…#Object_Class')` sagt dann nein. Die
Prägung entscheidet nicht über die Annahme, sondern über den Sinn.

`rdf:ID` gegen `rdf:about`: beide lösen am `#` auf (`rdf_ID.php:57`, `rdf_about.php:83`).
Ohne `#` gilt der Namensraum des Bogens — ein **neuer** Name. Mit `#` der davor — also eine
**Aussage über einen vorhandenen** Knoten. `set_Object_to_Namespace`
(`xml_multitree_ns.php:1571`) gibt bei belegtem Namen den vorhandenen Eintrag zurück und
ignoriert den neuen; verdrängt wird also nichts. ⚠ Das gilt nur, solange der Namensraum
schon registriert ist — Namensräume kommen **lazy** beim Parsen eines `xmlns`
(`xml_multitree_ns.php:592`). Deshalb steht `xmlns:tree` im Bogen.

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

⚠ **`get_ns_attribute($uri)` gibt bei FEHLENDEM Attribut `false` zurück**
(`Interface_ns.php:737`) — nicht `null` und nicht `''`. Der Unterschied trägt: ein Attribut
mit **leerem** Wert ist eine Aussage, ein **fehlendes** ist keine. Wer nur auf `''` prüft,
lässt jeden Knoten durch, der das Attribut gar nicht hat — eine UND-Verknüpfung wird damit
still wirkungslos.

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

Neue ini-Schlüssel gehören in die Tabelle `$list_of_configuration_parameters`
(`index.php:26`) — `createConfigFromINIFile` (`mod_lib.php:131`) macht daraus die
Konstante und löst dabei `__ROOT_DIR`/`__PROGRAM_DIR` auf. Die Unterscheidung trägt:
`__PROGRAM_DIR` sind die Dokumente **dieser Installation**, `__ROOT_DIR` ist, was zum
**System** gehört (`PLUG_IN_FOLDER`, `REGISTRY_VOCABULARY`).

`parse_ini_file_multi` (`mod_lib.php:4`) macht aus Punkt-Schlüsseln verschachtelte Arrays:
`sparql.fuseki.endpoint = ""` wird zu `$ini_array['search']['sparql']['fuseki']['endpoint']`.

Das `sparql`-Modell fragt nicht „welche Quelle", sondern **„welche Gegenstelle"**: SPARQL
ist eine *Sprache*, keine Gegenstelle. Wer sie spricht, steht als **Verbindungsprofil** im
Abschnitt `[connection]` — vier Felder `type` / `address` / `source` / Zugang. `type` ist
die Plattform (`qportal`, `fuseki`, `mysql`), `address` leer heißt *diese Instanz*, und
`source` ist überall dieselbe Ebene, nur anders benannt: bei mysql die Datenbank, bei
fuseki der benannte Graph, bei qportal der Baum. `sparql.use` nennt das Vorgabeprofil,
`use_source()` überschreibt es je Aufruf.
Pfade dürfen `__ROOT_DIR`/`__PROGRAM_DIR` enthalten, Dokumente `%NAME%` (`resolve_path`).

## Arbeitsweise

- **Committen: nur modifizierte getrackte Dateien.** Nie `git add -A`. Neue Dateien nur,
  wenn STW sie ausdrücklich nennt. Untracked und bewusst draußen: `db130399.sql`
  (Produktivdump mit IBANs), `img/`, `images/`, `script/`, `.claude/`, `mcp/`, `overview/`,
  `server.sh` — und **`template/`**, das laut `.gitignore` „managed separately" ist. Darum
  liegt das Vokabular in `ontologies/` und nicht bei `template/ontologies/`.
  ⚠ Erzeugte Artefakte (`*.pedl`, `*_shortcut.php`) sind ebenfalls ignoriert: git holt sie
  nicht zurück, und eine gelöschte `.pedl` kommt erst beim nächsten **Gebrauch** ihres
  Plugins wieder, nicht beim nächsten Seitenaufruf.
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
