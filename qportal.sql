-- surface.sql — das Schema, das qPortal FUER SICH SELBST braucht.
--
-- Gelesen vom Install-Aspekt in index.php (Zweig '@_mod' == 'install', Zeile ~346):
--     $load = implode('', file('surface.sql'));  $content->injectSQL($load);
-- Erreichbar ueber ?i=__install, aber nur solange define('INSTALL', ...) auf true steht
-- (index.php:127). Der Aspekt setzt es nach getaner Arbeit selbst wieder auf false.
--
-- ⚠ Die Datei fehlte. Genau daran starb das Install: file() auf eine fehlende Datei gibt
-- false, und implode('', false) ist unter PHP 8 ein TypeError.
--
-- ⚠ FORMAT — injectSQL trennt auf genau ";\n", also Semikolon UND Zeilenumbruch:
--     * jede Anweisung endet mit ; am Zeilenende
--     * kein ; mitten in einer Zeile, kein ; am Dateiende ohne folgende Anweisung
--     * Kommentare stehen VOR ihrer Anweisung (sie reisen im selben Stueck mit).
--       Ein Kommentarblock NACH der letzten Anweisung waere ein eigenes Stueck und
--       ginge als Anweisung an die Datenbank.
--
-- ⚠ Kein ENGINE, kein CHARSET — wie qp_cmd_ensure_table in behavior/stored.php: die
-- Vorgabe der Datenbank soll gelten. Der Bestand hier ist historisch MyISAM/latin1;
-- eine frische Installation bekommt, was der Server heute vorgibt.
--
-- ⚠ IF NOT EXISTS ueberall: das Install muss ein zweites Mal laufen duerfen, ohne etwas
-- kaputtzumachen. Es legt NUR an. Eine bestehende Tabelle bleibt, wie sie ist — auch
-- wenn sie anders aussieht als hier (siehe die Anmerkung zu den Primaerschluesseln).
--
-- ⚠ PRIMAERSCHLUESSEL: im Bestand hat KEINE der sieben tbl_-Tabellen unten auch nur
-- einen Index (gemessen 2026-09-11). Das ist kein Schoenheitsfehler — ohne
-- Primaerschluessel liefert get_rst() einen READONLY-rst, und ein Schreibversuch
-- scheitert still im Log. Eine frische Installation bekommt sie deshalb richtig.
-- Fuer eine BESTEHENDE Installation aendert diese Datei nichts; dort gehoert je Tabelle
-- ein eigenes ALTER TABLE ... ADD PRIMARY KEY (ID), und das ist eine Entscheidung an
-- lebenden Daten, keine Nebenwirkung eines Install-Laufs.
--
-- Was hier NICHT drinsteht und warum:
--   * die vier *_collection (tag_collection, tag_content, attrib_collection,
--     connect_collection) — das Konzept "Dokument/Baum vollstaendig in der Datenbank".
--     STW 2026-09-11: wird voraussichtlich nicht mehr gebraucht, die Funktionalitaet ist
--     raus. Die Tabellen BLEIBEN in bestehenden Datenbanken stehen (ausdruecklich so
--     entschieden), eine frische Installation bekommt sie nicht mehr. Gefuellt waren sie
--     nie; der einzige Code dazu war classes/sql.sql, eine Leseabfrage ohne Aufrufer, die
--     ausserdem nicht mehr zur Tabelle passte (sie las tag_collection.content, die Spalte
--     heisst content_ref). Entfernt am 2026-09-11, zurueckzuholen mit
--     `git show HEAD:classes/sql.sql`. Die Spaltenbedeutungen sind ausserdem in
--     template/edit/surface_basicinfo_page_structur.xml als Hilfeseite beschrieben.
--   * precache — TYPO3-Erbe, qPortal rendert so nicht mehr (STW 2026-09-11). Altlast.
--   * tbl_Item, tbl_qportal_doc_overview, tbl_qportal_doc_ref aus qportal.sql (2008):
--     existieren in keiner laufenden Installation mehr.
--   * alle tbl_* der Instanz (real_estate) und die qry_*-VIEWs — das sind Geschaeftsdaten
--     bzw. Sicht darauf, nicht qPortal.

-- ==========================================================================
-- 1. qPortal-System
--
-- ⚠ Diese zwei legen sich auch SELBST an, beim ersten Gebrauch:
--     qp_stored_command  behavior/stored.php        qp_cmd_ensure_table()
--     qp_once            classes/ns/tree/tree_once.php  TREE_once::tabelle_sichern()
-- Sie stehen hier, damit die Datei zeigt, was qPortal besitzt. Beide Seiten benutzen
-- IF NOT EXISTS, wer zuerst laeuft gewinnt, die andere ist ein Leerlauf. ⚠ Damit gibt es
-- die Definition aber ZWEIMAL: wer eine Spalte aendert, muss beide Stellen anfassen.
-- ==========================================================================

-- Eigene, benannte Befehlsketten. Der Name (ns+ln+name) ist der Schluessel, nicht der
-- Inhalt; der Block liegt verbatim als Text und wird erst beim Aufruf gefeuert.
CREATE TABLE IF NOT EXISTS qp_stored_command (
  ns VARCHAR(190) NOT NULL DEFAULT '',
  ln VARCHAR(60) NOT NULL DEFAULT '',
  name VARCHAR(120) NOT NULL,
  description TEXT,
  param_json TEXT,
  body_json MEDIUMTEXT,
  PRIMARY KEY (ns, ln, name)
);

-- Das Register fuer <once>: was im Leben der Installation hoechstens einmal laufen soll.
-- hash = sha256(Dokument + '#' + tree:name). done=0 heisst ANGEFANGEN und nicht fertig,
-- nicht "erledigt" — die Zeile wird vor dem Lauf geschrieben und danach abgehakt.
CREATE TABLE IF NOT EXISTS qp_once (
  hash CHAR(64) NOT NULL,
  path VARCHAR(190) NOT NULL DEFAULT '',
  name VARCHAR(190) NOT NULL DEFAULT '',
  done TINYINT(1) NOT NULL DEFAULT 0,
  stamp DATETIME NULL,
  PRIMARY KEY (hash)
);

-- ==========================================================================
-- 2. Benutzer und Rechte
--
-- Gelesen von mod_lib.php. securityclass ist die Stufe des angemeldeten Nutzers, die
-- ContentGenerator::mayEnter gegen tree:securitylevel haelt; sector am
-- tbl_group_management ist die Existenzaussage, die keine Stufe ueberstimmt.
-- ==========================================================================

-- Ein Konto. Key ist das Passwort-Geheimnis; securityclass die Stufe (0 = keine
-- Systemabfragen, 6 = schreiben, 10 = "das System gehoert dir").
CREATE TABLE IF NOT EXISTS tbl_user_management (
  ID INT NOT NULL AUTO_INCREMENT,
  User CHAR(120) NOT NULL,
  `Key` CHAR(120) NOT NULL,
  forename CHAR(60) NOT NULL DEFAULT 'Mr.',
  surname CHAR(60) NOT NULL DEFAULT 'Anderson',
  securityclass INT NOT NULL DEFAULT 0,
  PRIMARY KEY (ID)
);

-- Eine Gruppe. sector ist semikolongetrennt, dieselbe Form wie [intern] key.*.sector.
CREATE TABLE IF NOT EXISTS tbl_group_management (
  ID INT NOT NULL AUTO_INCREMENT,
  groupname CHAR(60) NOT NULL,
  groupdescription CHAR(255) NOT NULL,
  sector CHAR(255) NOT NULL,
  PRIMARY KEY (ID)
);

-- Wer in welcher Gruppe ist.
CREATE TABLE IF NOT EXISTS tbl_user_to_group (
  ID INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  group_id INT NOT NULL,
  PRIMARY KEY (ID)
);

-- Rechte VORBEREITEN: ein Code traegt Gruppen und eine Stufe und wird spaeter von einer
-- Person eingeloest (mod_lib.php:399 legt an, :469 loest ein). Das Dokument dazu ist
-- template/xml.xml, Knoten givecode2 — und der steht auf securitylevel 10, der Stufe
-- "Zugang vergeben".
CREATE TABLE IF NOT EXISTS tbl_marked_for_group (
  ID INT NOT NULL AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL,
  `groups` VARCHAR(200) NOT NULL,
  seclevel INT NOT NULL DEFAULT 0,
  to_person INT DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY code (code)
);

-- ==========================================================================
-- 3. Ontologien
--
-- Ein Tripelspeicher: tbl_semantic_property ist antecessor —node— successor, also
-- Subjekt–Praedikat–Objekt, beide Enden als Verweis auf tbl_semantic_node, alles unter
-- einer ns_id. tbl_document_sem haengt ein Dokument an seine Aussagen.
-- Im Bestand alle drei leer — der Entwurf steht, gefuellt wurde er nie.
-- ==========================================================================

-- Ein benannter Knoten in einem Namensraum.
CREATE TABLE IF NOT EXISTS tbl_semantic_node (
  ID INT NOT NULL AUTO_INCREMENT,
  ns_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  PRIMARY KEY (ID),
  KEY ns_name (ns_id, name)
);

-- Eine Aussage. node ist das Praedikat, antecessor und successor zeigen auf
-- tbl_semantic_node.ID.
CREATE TABLE IF NOT EXISTS tbl_semantic_property (
  ID INT NOT NULL AUTO_INCREMENT,
  ns_id INT NOT NULL,
  node VARCHAR(150) NOT NULL,
  antecessor INT NOT NULL,
  successor INT NOT NULL,
  PRIMARY KEY (ID),
  KEY antecessor (antecessor),
  KEY successor (successor)
);

-- Welches Dokument traegt welche Aussage.
CREATE TABLE IF NOT EXISTS tbl_document_sem (
  ID INT NOT NULL AUTO_INCREMENT,
  source_id INT NOT NULL,
  sem_id INT NOT NULL,
  PRIMARY KEY (ID),
  KEY source_id (source_id)
);
