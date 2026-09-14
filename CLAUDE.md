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
# Das komplette qPortal mit eigenem INTERN-Baum (Server muss laufen)
php -d error_reporting=E_ERROR test/Integration/tree_passthrough.php   # 7/0
php -d error_reporting=E_ERROR test/Integration/tree_echo.php          # 21/0
php -d error_reporting=E_ERROR test/Integration/tree_where.php         # 31/0
php -d error_reporting=E_ERROR test/Integration/logger_listen.php      # 18/0 (Teil 1 ohne Server)
php -d error_reporting=E_ERROR test/Integration/tree_call.php          # 12/0
php -d error_reporting=E_ERROR test/Integration/schema_check.php       # 14/0 (Bestand: 512 gueltig / 60 nicht)
```

```bash
# Sonden — sie prüfen nicht, sie ZEIGEN. Ausgabe wird gelesen, kein grün/rot.
php -d error_reporting=E_ERROR test/proben/probe_registry_vocab.php   # ausser dieser: 32/0
```

`seek_scope.php` und `sparql_parse.php` müssen vollständig grün sein — jede rote Zeile
dort ist neu. `probe_registry_vocab.php` bewacht die Naht zwischen Vokabulardokument und
`build_up()` und steht bei **32 in Ordnung / 0 rot**.
`intern_walk.php` steht bei **35 gelaufen / 1 rot** — `__get_data` ist ein Bestandsdefekt
(über den Intern-Kanal ist der Aufrufer der ContentGenerator und damit kein Knoten; STW hat
entschieden, das nicht umzubauen). Diese Zahl ist die Vergleichsmarke: ändert sie sich, hat
die letzte Änderung etwas gebrochen.

⚠ Der Prüflauf **liest den Schlüssel aus `config/config.ini`** und schickt ihn als Bearer.
Ohne ihn käme er seit `[intern] anonymous = 0` gar nicht mehr herein, und er stünde auf
Stufe 0 — `__save_back` verlangt 10. Auf einer Installation ohne Schlüssel fällt er auf den
Sitzungsweg zurück.

`./server.sh` startet `symfony server:start` auf **https**; die Prüfstände sprechen dann
`https://localhost:8002`. Mit `php -S` geht nur http — dann `QPORTAL_URL` setzen.

## Baum ohne index.php hochfahren

`require_once(__DIR__ . '/../bootstrap.php');` — siehe `test/bootstrap.php`. Danach:

```php
$tree = new xml_semantic();     // die SPITZE der Schicht, nicht xml_ns
$tree->setNewTree($kennung);
$tree->load_Stream($xml, 0, "XML");
$treffer = $tree->collect_nodes('http://www.trscript.de/tree#param');
```

⚠ **Prüfstände bauen `xml_semantic`, nicht `xml_ns`** — dieselbe Spitze wie die
Produktion (`class_Contentgenerator.php:82`). Wer tiefer ansetzt, verliert Methoden,
die Knotenklassen aufrufen: ein Dokument mit `<owl:Ontology>` stirbt unter `xml_ns` an
`Call to undefined method xml_ns::currentOntology()` (`rdf_about.php:57`). Das sieht nach
einem kaputten Dokument aus und ist ein zu tief gewählter Prüfstandskopf.

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

## tree:name — Pfadsegment, und nur mit Namensraum ein Bezeichner

**Zum Navigieren ist der Name ein Pfadsegment.** `TREE_tree::event_message_in`
(`tree_tree.php:88`) vergleicht den rohen Attributwert gegen den **Kopf** der Namensliste
und macht dann `array_shift`. Aufgelöst wird also beim Abstieg unter **Geschwistern** —
dort und nur dort muss ein Name eindeutig sein. Die 520 × `home` sind deshalb keine
Kollision, sondern 520 Pfadanfänge (je ein `<final>` pro Dokument, Zeile 3).

**Zum Bezeichnen braucht er einen Namensraum.** Seit `591341a` ist `tree:name` eine
`subPropertyOf` von `rdf:ID` (so gesagt im Registrierungsbogen) und trägt in den
Namensraum ein — aber nur, wenn **beides** stimmt (`classes/ns/tree/tree_name.php`):

1. **Träger ist `tree` oder `final`.** Überall sonst tut der Knoten nichts.
2. **Der Wert nennt selbst einen Namensraum** — `praefix;Name` oder voller URI, dieselben
   Formen wie bei `rdf:ID` (`rdf_ID.php:57ff`), nur ohne dessen dritten Zweig. Ein blanker
   Name und die Form `#name` fallen auf den Default-Namensraum, und den teilen sich 571
   Dokumente; das ist keine Aussage. STW: *„Es bedeutet, dass jemand den Flur mit Zimmern
   möchte, aber diese nur zum Durchlaufen will."*

Gemessen: von 716 Namen auf `tree`/`final` trägt **heute keiner** einen Namensraum (676
blank, 40 mit führendem Punkt). Der Einbau ist damit byte-genau folgenlos — der Bezeichner
erscheint erst, wo jemand ihn hinschreibt. `first` ist bewusst draußen: es wird immer ohne
Namensliste gerufen (`behavior/tree.php:31`), sein Name würde nie verglichen.

**Der führende Punkt heißt „unsichtbar".** Vom Dateisystem entlehnt (STW): ein `tree:name`,
der mit `.` **beginnt**, erscheint nicht in der Navigation. Ausgewertet in
`class_Contentgenerator.php:263`, direkt neben `securitylevel` und `sector`:

```php
if ( !(false === ($hidden = strpos( show_ns_attrib('…tree#name'), '.' ) ) )
&& intval($hidden) == 0 )   $result = false;
```

Ein Punkt *innerhalb* des Namens ist harmlos — nur Position 0 zählt. Ebenfalls versteckend:
ein fehlendes `tree:value` (Zeile darüber). In `template/xml.xml` sind 16 von 56 Namen so
markiert (`.edit`, `.service_orga`, `.save_doc`, …) — Dienste und Unteraufrufe, keine
Menüpunkte. ⚠ **Die `NCName`-Frage ist damit weg:** ein blanker Name wird gar nicht erst
als Bezeichner gelesen, muss also kein `NCName` sein. Der Punkt behält „unsichtbar".

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

Auf `tree` und `final` ist der Name ein **Bezeichner**, die `#`-Form auf
`content`/`subtree`/`param` ein **Zugriff** (STWs Workaround, um Inhalte in Dokumenten
überhaupt modifizieren zu können), auf `wordfield` stehen **Beschriftungen** (`Bett (2)`,
`Mensch in einer Gemeinschaft`).

⚠ Zwei Vorbehalte zu dieser Tabelle: sie zählt auskommentiertes XML mit und lässt Träger
aus (`name` sitzt auch auf `object` 2743, `article` 303, `input`, `access`). Und sie zählt
**Vorkommen, nicht Identitäten** — die 716 auf `tree`/`final` sind **114 verschiedene**
Namen, davon `home` allein 520.

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

## Ein Namensraum wird erklärt, nicht benutzt

Ein `xmlns:x="…"` in der Wurzel **erklärt** keinen Namensraum, es bindet nur einen Präfix.
`add_new_namespace_from_attributes` (`xml_multitree_ns.php:574`) füllt
`namespace_frameworks[$ns]` nur, wenn `My_NameSpace_factory::namespace_factory()` für die
URI etwas kennt — für einen eigenen Namensraum also nie. Der entsteht dann **beiläufig**
beim Parsen über `alt_namespace_factory`, je benutztem Namen einer.

Der erklärte Weg ist ein **`owl:Ontology`-Knoten**. `OWL_Ontology::event_initiated`
(`classes/ns/owl/owl_Ontology.php`) liest `rdf:about`, nimmt die **rdfs-Fabrik** als
Vorlage und legt `nativ` plus leere `node`/`attrib`-Tabellen an. Gemessen am selben
Dokument:

| | ohne `owl:Ontology` | mit |
|---|---|---|
| `nativ` | `Interface_node` (generisch) | `RDF_RDF` (aus der rdfs-Fabrik) |
| `attrib` | `NULL` | leere Tabelle |
| `node` | die beim Parsen benutzten Namen | die geprägten Namen |

⚠ `set_Namespace()` (`:1751`) wird zusätzlich von `rdf:about` auf einem `owl:Ontology`
gerufen (`rdf_about.php:54`). Von seinen zwei Hälften wirkt heute nur eine: `cur_ns` wird
gesetzt und **nirgends gelesen**.

⚠ Ein fehlender nativer Knoten ist die Ursache von `native namespace is missing` — siehe
die Reihenfolge-Bedingung beim Registrierungsbogen. Ein eigener Namensraum gehört darum
mit `<owl:Ontology rdf:about="…">` erklärt, **bevor** Namen hineingeprägt werden.
Beispiel: `template/fridge/fridge.xml`.

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

⚠ **Beim Serialisieren wird der Schlüssel aus `attrib` zum Attributnamen.**
`all_attrib_axo()` (`xml_multitree.php:1476`) schreibt `$key . '="' . $value . '"'`, und
liest über `show_cur_attrib()` → `get_attribute()` **nur `attrib`**, nie `attrib_ns`. Dort
muss also ein gültiger XML-Name stehen — der Parse-Weg liefert ihn mit Präfix
(`create_node_to_attribute` → `identifier`).

⚠ **Ein zur Laufzeit gesetztes Attribut ging bis `2026-09-05` genau daran kaputt.**
`set_ns_attribute()` rief `attribute($uri, …)` mit der **vollen URI** als Namen und legte
den lokalen Namen in einer zweiten Zeile daneben. Ergebnis: zwei Schlüssel auf einen
Knoten, das Attribut stand **zweimal** im gespeicherten Dokument, eines davon mit einer URI
als Namen — `Failed to parse QName 'https:'`, das Dokument war nicht mehr wohlgeformt. Der
Fehler zeigte sich erst beim Speichern, nicht beim Lesen.

⚠ Der Präfix dafür kommt aus `showDocumentsNamespaces()`, und die liefert
**Präfix → Namensraum**. Die Gegenrichtung braucht `array_search($ns_uri, $ns, true)`, nicht
`isset($ns[$ns_uri])` — der alte Code schlug mit einer URI gegen eine präfixgeschlüsselte
Liste nach, traf nie, und setzte den Präfix ersatzweise auf die URI. Dieselbe Funktion ließ
außerdem `$newKey` zwischen den Runden stehen, wodurch jedes gewöhnliche Attribut einen
Präfix-Eintrag überschrieb.

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

**Geplant (STW, 2026-09-11): `__query` mit `statement` und `model` — eine Abfrage für alles.**
Später bekommen einzelne Modelle eigene Befehle, die `model` festlegen und eigene Attribute tragen.
Heute gibt es **keinen** Intern-Befehl für die Suchmodelle; `seek_by_model`/`query_by_model` stehen
fertig, haben aber keinen Aufrufer. Was beim Bau zu beachten ist:

- Modelle: `internal`, `sparql`; `xpath` ist ein Stub.
- `sparql.use` ist leer → `profile()` wirft; wie `__where_am_i` `use_source('qportal')` setzen oder
  die Gegenstelle als Attribut wählen lassen.
- Rückgabe: `query()` gibt nur die Knoten der ersten Spalte, `solutions()` die Zeilen. Nach außen die
  Zeilen, Knoten darin als `uri`/`name`/`stamp` (`answer_shape` serialisiert keinen Knoten) — über das
  Ereignis als Zwischenspeicher, dann `__to_owner`.
- `collect_nodes` ist baumlokal: über alles, was `__echo` geladen hat, mit derselben Baumschleife und
  demselben Zurückstellen des Parsers wie `__where_am_i scope=global`. Nur im selben Request.
- Stufe: `__query` sieht auch die Umsetzung der Prozesse → `addSecurity` auf eine höhere Stufe (der
  „andere Befehl“ für alles, was nicht `tree`/`final` ist).
- Für die **Türschilder** ist es schon da: `__where_am_i scope=global` nach `__echo` fragt jeden
  geladenen Baum per SPARQL ab und listet alle `tree`/`final` mit Schild, Baum und Stempel.

### Was der SPARQL-Parser als Begriff annimmt

Drei Formen, an jeder der drei Tripelstellen:

| | |
|---|---|
| `praefix:name` | braucht ein `PREFIX praefix: <…>` im selben Ausdruck |
| `<http://…#name>` | volle URI in spitzen Klammern — geht **nicht** noch einmal durch die Präfixtabelle, darum trägt auch `<mailto:…>` |
| `http://…#name` | blank hingeschrieben; erkannt am `://` vor dem Doppelpunkt |

Dazu `?var` und, als Objekt, `"ein Literal"`. Ein Name **ohne** Doppelpunkt hängt an der
`BASE` (mit `#`, wie `full_URI()` zusammensetzt).

⚠ **Ein unbekanntes Präfix wirft** (seit `2026-09-14`). Vorher blieb es unangetastet —
gedacht war, dass man im Ergebnis sieht, was fehlte, nur sieht man dort gar nichts: die
Abfrage lief gegen die Zeichenkette `tree:final`, die kein Knoten trägt, und gab still
**null Zeilen**. Ein vergessenes `PREFIX` war von einem leeren Ergebnis nicht zu
unterscheiden. `__where_am_i` merkte nie etwas davon, weil es seinen Präfixblock selbst
voranstellt; über `__query` schreibt den Ausdruck der Aufrufer.

⚠ Die spitzen Klammern trugen in `WHERE` bis dahin **nicht** — nur in `PREFIX`/`BASE`.
Der Automat starb an `Zustand "space_pre" hat keine Kante fuer "<"`. Das sah nach einem
Tippfehler aus und war eine Lücke in der Grammatik.

## start ist ein Muss

**Der Pfad für `start` steht im ÄUSSEREN `Attribute`** — neben `Command`, nicht darin — als
Liste von `tree`-Namen:

```json
{"Identifire":"http://www.trscript.de/tree#indextree","Command":{"Name":"start"},"Attribute":["fridge"]}
```

Leer laufen `first` und `final`. `?i=` in der URL wird am Intern-Rand verworfen
(`class_Contentgenerator.php`, Zweig `injectedLine`); über Intern wählt man den Weg im Befehl.
Der leere Befehl (`''`, `'*'`, `'*?start'`) wird zentral in `Command_Object` zu `start`.

**Nur `start` ist ein Lauf.** Einen anderen Befehl nimmt ein `tree` an, schreibt
`ist kein start` ins Log und lässt ihn stehen — kein Pfad verbraucht, kein `src` geladen,
**nichts weitergereicht**. Die tree-Aufrufe bleiben getrennt, eine Kaskade darf nicht losgehen.

⚠ **`tree`-Knoten hängen flach am `indextree`**, nicht am Eltern-`tree`
(`TREE_tree::event_initiated` → `to_listener('…#indextree')`). Innerhalb eines Dokuments
sind alle `tree` Geschwister; **ein Pfadsegment = eine Dokumentebene**, tiefer geht es nur über
`src`. In `way_out` eines `tree` stehen die übrigen Knoten — und die unterstellen alle einen
`start`. Gemessen: `?i=API&j=glossary` ist 404.

**Wer den Baum durchlaufen will, tut das als Befehl über die Struktur:** `getRefnext()` ohne
Index liefert die ganze `next_el`-Liste (nur Elemente, bei einem Blatt ggf. `null`). Ein
Baum hat keine Zykel — über `src` hinweg aber schon, dort braucht es eine Tiefe.

⚠ **Registry-Rückfall:** die Registry des Knotentyps (und darüber `event_message_in`) kommt nur
dran, wenn die **allgemeine den Befehl nicht kennt** — nicht mehr bei jedem `false`
(`Interface_ns::event_message_check`).

**`__echo` — Unterbäume laden, nicht starten** (`behavior/std.php`). Läuft über `getRefnext()`,
jeder Knoten bekommt den Befehl selbst; nach unten **ohne** `Value`, der Eingangsknoten feuert
`Value` am Ende **genau einmal** (sonst liefe es 0- oder n-mal). `depth` zählt **nur an einem
`src`** herunter: leer = 1, 0 = durchlaufen ohne zu laden — zugleich die Grenze gegen Kreise
zwischen Dokumenten. Nur `tree#src`, nur Dateien (Adresse → Logzeile), kein Laden hinter
`mayEnter` = nein. Doppelt geladen wird nicht (`xml::load()` gibt den vorhandenen Baum zurück;
gemessen: depth 5 über einen Kreis = 5 Ladevorgänge, 2 Bäume). Log Stufe 5 nur für
`src`-Ereignisse. ⚠ Danach liegen die Bäume im Parser — **SPARQL fragt aber nur den aktuellen
Baum** (`collect_nodes` ist baumlokal, kein `FROM`); das ist der nächste Schritt.

**Das Ereignis ist der Zwischenspeicher.** Ein Befehl legt ein Ergebnis per `set_context` ins
`EventObject`, der nächste liest es mit `get_context()` (so schon `__set_data`, und
`__get_attribute` mit `Value`). **`__to_owner`** gibt ein **gewöhnliches Datum** aus dem Kontext
nach außen (Zeichenkette, Zahl, Array → `{"value":…,"serialised":true}`), sonst wie bisher den
Knoten, auf dem es steht. Ein Objekt im Kontext zählt nicht — `tree_tree` legt dort im Baumlauf
seinen Knoten ab; oben im Intern-Aufruf ist der Kontext `null`.

**`__where_am_i`** — Türschilder als Array. **Ein Schild haben nur `tree` und `final`**: was sonst
im Baum steht, ist die Umsetzung des Prozesses und geht den Besucher nichts an (STW) — dafür kommt
ein eigener Befehl mit höherer Stufe. Was `mayEnter` verweigert, erscheint in keiner Liste.

| `scope` | liefert |
|---|---|
| `local` (Vorgabe) | das Schild **dieses** Knotens: `uri`, `name`, je Begriff ein Eintrag. Auf einem anderen Knoten nur `note` |
| `tree` | alle `tree`/`final` im Dokument des Knotens: `{scope, tree, hits:[…]}`, je Treffer mit `stamp` |
| `global` | dasselbe über **alle geladenen Bäume** (nach `__echo` mehr), je Treffer mit `tree` und `stamp` |

`show=function,delivers,…` begrenzt die Begriffe — Kurznamen (Schlüssel aus
`PHP_Ast_Scan::DESC_KEYS`, dazu `value`, `delivers`, `columns`, `effect`) oder Präfixform
(`desc:effect`); `uri`/`name` stehen immer drin, Unbekanntes landet in `note`. Weil der Name in die
Abfrage eingesetzt wird, gilt nur `präfix:name`.

Gefragt wird **per SPARQL** (`?s rdf:type tree:tree|tree:final`, dann je Begriff eine kleine
Abfrage — kein `OPTIONAL`, keine Prädikatvariable), im jeweiligen Baum (`change_idx` hin und
zurück). Begriffe ohne `show`: `tree:value`, die Zielwerte aus `DESC_KEYS` und
`desc:delivers`/`desc:columns`/`desc:effect` — die Ausgaben stehen **nicht** in `DESC_KEYS`.
`sparql.use` ist leer → `use_source('qportal')`. Nach außen:

```json
{"Identifire":"*","Command":{"Name":"__where_am_i","Value":{"Identifire":"*","Command":{"Name":"__to_owner"}}}}
{"Identifire":"*","Command":{"Name":"__where_am_i","Attribute":{"scope":"tree","show":"function,delivers"},"Value":{"Identifire":"*","Command":{"Name":"__to_owner"}}}}
```

⚠ **`dcterms` heißt als volle URI `http://purl.org/dc/terms/#title`** — `full_URI()` hängt `#` an
einen Namensraum, der schon auf `/` endet. Mit dem üblichen Präfix findet SPARQL nichts, nur mit
`<http://purl.org/dc/terms/#>`.

**Prüfstand mit eigenem Baum:** `test/Integration/fixture_entry.php` setzt `INTERN` per `define`
und bindet danach das unveränderte `index.php` ein — `createConfigFromINIFile` überspringt
definierte Konstanten. `?doc=<name>` wählt ein Dokument aus `test/Integration/fixtures/`
(nur Namen, keine Pfade). Nur über den Server (Befehl kommt aus `php://input`), nur von localhost.

## Unteraufrufe und Rückgabe

**Die Scope-Rückgabe:** `tree_sub` öffnet `createScope(<src>)`, startet `first`/`final` des
Unterdokuments und sammelt, was dessen `<result>` per `addResultToScope` ablegt. Ein `<result>`
hört nur unter `content`, `element` oder `program` und legt eine **frische Instanz** in den Scope.
Zurückgegeben wird nur, wenn das `<sub>` selbst unter `content` oder `element` steht — dann an
`$received_node`, den Knoten, der beim Eintritt im Ereignis stand (dort: der Ausgabeknoten).

- **Kinder** der Instanz (etwa ein Element) werden dorthin geklont.
- **Ein einfacher Wert** (`<result>2</result>`) kam bis 2026-09-11 nie an: die Instanz hatte ihn
  nicht (`getdata()` greift nicht auf `link_to_class` zurück). Jetzt gibt `TREE_result` ihn der
  Instanz mit, `tree_sub` hängt ihn an den empfangenden Knoten und merkt ihn sich in
  `$rueckgabe`.
- **`<sub>` in einem `<element>`** lief schon immer, aber das Element setzt danach seinen Text neu
  (`setdata($string, $point)` **ersetzt**) und tilgte die Rückgabe — `vor--nach`. Beide
  Zusammensetzungsschleifen (`process_exist_xhtml`, `process_new_xhtml`) haben jetzt einen Zweig für
  `tree#sub`, der dessen `$rueckgabe` an **seiner** Stelle einsetzt.

⚠ Fallen im Bestand, gemessen oder gelesen, nicht behoben:
- `leaveScope()` nimmt nur vom Stapel, löscht `$scopes[$name]` nicht — derselbe `src` zweimal in
  einem Request würde bei `createScope` „existiert bereits“ werfen (gelesen).
- `getParam()` hat keinen Aufrufer; die Parameter laufen real über `$param_arr` in `<variable>` und
  `<object variable=…>` des Unterdokuments.
- Eine **Element**-Rückgabe (`<result><element>…</element></result>`) über `<sub>` bleibt leer —
  **kein Mangel, sondern kein Einsatzzweck** (STW): `<element>` baut Bäume innerhalb von
  `<content>`; zum Klonen von Bäumen oder Inhalten gibt es `<subtree>`.
- Alle echten `<result>` im Bestand geben ein Objekt zurück und stehen unter `<program>`, wo der
  Rückgabezweig nicht greift.

**`__call`** — führt den Knoten, auf dem es steht, aus wie ein `<sub>`: `mayEnter`, ein **eigener**
Scope mit eindeutigem Namen (so geht derselbe `src` zweimal), mit `src` das Dokument (`first`,
`final`), sonst die eigenen Kinder außer `template`/`tree`. Ergebnis `{uri, name, results:[…]}`
ins Ereignis, dann `Value`; Parser und Scope-Stapel stehen danach wieder, wo sie waren. Objekte
erscheinen nur benannt. Argumente folgen.

```json
{"Identifire":"*","Command":{"Name":"__find_node","Attribute":{"json":"{\"name\":\"http://www.trscript.de/tree#tree\",\"attribute\":{\"http://www.trscript.de/tree#name\":\"fridge;power_consumption\"}}"},
 "Value":{"Identifire":"*","Command":{"Name":"__call","Value":{"Identifire":"*","Command":{"Name":"__to_owner"}}}}}
```

## Das tree-Schema

**`xml-schema/tree-schema.xsd`** ist das Verifikationsdokument für den Namensraum
`http://www.trscript.de/tree` — das, worauf 27 Dokumente per `xsi:schemaLocation` zeigen
(`https://service-wsf-gmbh.de/xml-schema/tree-schema.xsd`). Die Kopien unter
`template/validation/` und `template_/validation/` sind älter und ungetrackt. Gefasst 2026-09-11
nach dem, was im Bestand wirklich steht (vorher bestanden 103 von 573 Dokumenten), mit einer
Anmerkung je Typ: was der Knoten tut, wo er hört, wohin seine Rückgabe geht. **Zum Nachschlagen
dort zuerst.**

- Kinder in **beliebiger Reihenfolge**; Text nur, wo er Bedeutung hat (`element`, `param`, `remote`,
  `object`, `result`, `main`, `add`, `access`, `variable`).
- Attribute aus fremden Namensräumen überall (`desc:`, `dcterms:`, `xsi:`); unter `element/html`
  beliebiger Inhalt — dort steht HTML ohne eigenen Namensraum, also im tree-Namensraum.
- **Nur registrierte Namen** (`classes/ns/tree/class_index.php`).

Befunde im Bestand (`schema_check.php`, Teil 2 — nur ausgewertet, `template/` wird getrennt verwaltet):
`programm` (14 Dateien) und `add2` (13) sind **nicht registriert** — der Parser macht daraus
generische Knoten, die nichts tun; `element` außerhalb von `content` (16); Streutext direkt in
`final`/`template`/`content`/`program` (30); ein `select` unter `final`; ein `content` ohne `name`.
Registriert, aber im Bestand unbenutzt: `bag`, `description`, `document`, `rest`, `sparql`,
`statement`, `subject`, `workspace`, `xpath` — nicht im Schema.

## Zugang zum Intern-Endpunkt

```ini
[intern]
anonymous = 0                    ; 1 laesst den schluessellosen Weg offen
key.pfleger.token  = <hex>       ; bin2hex(random_bytes(32)), NUR in config.ini
key.pfleger.level  = 10
key.pfleger.sector = "alpha;beta" ; Semikolon wie in der Sitzung — MIT Anfuehrungszeichen
```

⚠ **Der Sektor MUSS in Anführungszeichen.** In einer ini beginnt `;` einen Kommentar —
`sector = alpha;beta` kommt als `alpha` an, und `beta` fehlt still. Gemessen.

**Die Regel:** ohne Schlüssel ist der Endpunkt offen und arbeitet auf **Stufe 0**. Sobald
**ein** Schlüssel steht, ist nichts mehr anonym — es sei denn, `anonymous = 1` lässt den
schlüssellosen Weg ausdrücklich offen, für Dienste und Sensoren, die abgefragt werden sollen,
ohne einen Schlüsselbund zu führen; sie bleiben dabei auf 0.

Ein Schlüssel **ohne `level` bekommt 0** — Rechte werden hingeschrieben, nicht stillschweigend
geerbt. Die alte flache Form `key[] = <hex>` trägt weiter und landet ebenfalls auf 0.
Der Name (`pfleger`) ist kein Geheimnis: er steht bei einer Abweisung im Log.

⚠ **Derselbe Token in beiden Schreibweisen: die benannte gilt.** `intern_key_list()` gibt die
benannten Einträge zuerst zurück, weil `index.php:439` den **ersten** Treffer nimmt (`break`).
Auf die Reihenfolge in der *Datei* ist kein Verlass: `parse_ini_file` sammelt `key[]` nach
`['key'][0]`, die Punktschlüssel expandiert `parse_ini_file_multi` erst **danach** — die flache
Zeile steht im Array also vorn, auch wenn sie in der Datei hinten steht. Bis `2026-09-14` gewann
darum die flache: der Schlüssel wurde angenommen, der Aufrufer stand aber auf **Stufe 0**, und
sichtbar war das nur als `ABGEWIESEN: … verlangt Stufe 6, der Aufrufer hat 0` im Log.

Ausgewertet in `index.php` (`intern_key_list()` in `mod_lib.php` bringt beide Schreibweisen
auf eine Form); ein Treffer setzt `ContentGenerator::setClearance()` und, wenn angegeben,
`setSectors()`. ⚠ Die Sektoren gehen **nicht** in `$_SESSION` — das bliebe über den Request
hinaus stehen; `mayEnter()` liest sie über `sectors()`, Schlüssel vor Sitzung.

⚠ Der Vergleich läuft über `hash_equals`, nicht `in_array`.

## Das Log

**Gelesen wird aus dem Speicher, nicht aus der Datei.** `__give_log` (LOG-Zweig in `getoutput()`)
gibt `Logger::giveLogText()` aus. `[log] active` schreibt nur noch zusätzlich die Datei unter
`path` — die Absturzkopie; fällt das ganze System, gibt es ohnehin nur eine 500.

**Geloggt wird nur, wenn es jemand will** (`Logger::$collect`). `index.php` schaut **vor**
`setstart` in `php://input`: ist der äußerste Befehl `__give_log`, wird gesammelt, Kopfzeile
eingeschlossen. Steht `__give_log` weiter innen, schaltet es das Sammeln bei seiner Ausführung
ein — dann fehlen die Zeilen davor. Ohne `active`, ohne `collect` und ohne Zuhörer wird **kein**
Eintrag gebaut (der Positionsaufruf `debug_backtrace()` kostet).

**Zuhörer — per Plugin-Aufruf, mit Name und Level** (`plugin[Logger]` in `[short]`):

```xml
<object id="lausch" name="Logger" src="PlugIn/plugin_log.php">
  <remote name="Logger.listen.name">mein_log</remote>
  <remote name="Logger.listen.level">6</remote>
  <remote name="Logger.listen" />
</object>
```

Jeder Zuhörer hat sein **eigenes Array** und sein **eigenes Level, unabhängig vom globalen** —
nach oben gekappt auf `[log] listen_max` (leer = wie `level`), weil die Zeilen Einblick in Pfade,
Daten und Abfragen geben. Ein schon angemeldeter Name fängt **leer** an: vermutlich läuft ein
Skript erneut, und den Neuen interessieren die Folgen seines Handelns. Gelesen wird wie eine
Ergebnismenge — `if(moveFirst()) do { col('msg'|'level'|'index') } while(next());` —, mit eigenem
Zeiger je Zuhörer. ⚠ `next()` rückt nur vor, wo ein Eintrag steht; so sieht ein Zuhörer auch, was
nach seinem letzten Lesen dazukam.

`__give_log` mit Attribut `level`: ein eigener Zuhörer für die Dauer seines `Value`, ausgegeben
wird dann nur, was dabei entsteht — ebenfalls gekappt auf `listen_max`.

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
