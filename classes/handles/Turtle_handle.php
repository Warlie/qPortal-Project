<?php

class Turtle_handle extends Interface_handle
{
    var $base_object = null;
    private $prefixes = [];

    function set_object(&$obj)
    {
        $this->base_object = &$obj;
    }

    function check_format($example)
    {
        return false;
    }

    function parse_document($source)
    {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        $this->parse_document_from_array($this->_parse_to_array($source));
    }

    // Accepts the intermediate array produced by _parse_to_array (or built externally)
    // and feeds it into the qPortal tree without any Turtle serialisation round-trip.
    // This is the canonical entry point for plugins that already hold structured data.
    //
    // $direct = false (default): Pfad A creates a new tree slot via load_Stream (original
    //   behaviour — used when initialising from a Turtle file).
    // $direct = true: Pfad A inserts new subjects directly into the current slot at the
    //   rdf:RDF root level, keeping the same idx. Use this from plugins that run after the
    //   main template is already loaded, so that new triples end up in the rendered output.
    // $direct = false (default): Pfad A creates a new tree slot via load_Stream (original
    //   behaviour — used when initialising from a Turtle file).
    // $direct = true: Pfad A inserts new subjects into the TURTLE output slot via load_Stream
    //   with a position stamp — no new slot created, wrapper element skipped by omni_handle.
    function parse_document_from_array(array $data, bool $direct = false): void
    {
        // Split subjects: known URIs already in namespace_frameworks go to Pfad B,
        // new URIs go to Pfad A.
        $new      = [];
        $existing = [];
        foreach ($data['subjects'] as $uri => $triples) {
            if ($this->base_object->isURIused($uri))
                $existing[$uri] = $triples;
            else
                $new[$uri] = $triples;
        }

        if ($direct) {
            // Pfad A (stamp insert) — navigate to the target slot root and inject via load_Stream.
            // omni_handle skips the outer rdf:RDF wrapper; subjects land directly in the tree.
            // Works for TURTLE slots and XML slots (e.g. doctype_out="XML" with OWL skeleton).
            if (!empty($new)) {
                $target_idx = $this->_find_named_idx($data['target'] ?? null)
                    ?? $this->_find_turtle_idx() ?? $this->_find_main_doc_idx();
                if ($target_idx !== null) {
                    $saved_idx = $this->base_object->idx;
                    $this->base_object->change_idx($target_idx);
                    $this->base_object->set_first_node();
                    $stamp    = $this->base_object->position_stamp();
                    $new_data = ['prefixes' => $data['prefixes'], 'subjects' => $new, 'queryable' => ($data['queryable'] ?? false), 'target' => ($data['target'] ?? null)];
                    $rdf_xml  = $this->_to_rdf_xml($new_data);
                    $this->base_object->load_Stream($rdf_xml, 0, 'XML', '', $stamp);
                    $this->base_object->change_idx($saved_idx);
                }
            }
        } else {
            // Pfad A (load_Stream) — convert to RDF/XML and load into a new slot.
            // Always run even when $new is empty so mirror[$idx] gets initialised (rdf:RDF root).
            $new_data = ['prefixes' => $data['prefixes'], 'subjects' => $new, 'queryable' => ($data['queryable'] ?? false), 'target' => ($data['target'] ?? null)];
            $rdf_xml  = $this->_to_rdf_xml($new_data);
            $this->base_object->load_Stream($rdf_xml, 0, 'XML', $this->_ontology_uri($new_data));
        }

        // Pfad B — add new predicates directly to the already-registered canonical node.
        if (!empty($existing))
            $this->_extend_existing($existing, $data['prefixes'], $data['target'] ?? null);
    }

    // Returns the idx of the last TURTLE-typed slot with a valid mirror.
    // This is the slot that both stamp inserts and save_back should target.
    private function _find_turtle_idx(): ?int
    {
        $result = null;
        $max    = $this->base_object->max_idx ?? 0;
        for ($i = 0; $i <= $max; $i++) {
            if (($this->base_object->TYPE[$i] ?? '') === 'TURTLE' &&
                is_object($this->base_object->mirror[$i] ?? null)) {
                $result = $i;
            }
        }
        return $result;
    }

    // Returns the idx of the main RDF output document — first rdf:RDF-rooted slot whose
    // loaded_URI is a regular file path (not a @system-slot like @registry_surface_system).
    // Used as injection target when no TURTLE slot exists (XML/OWL output mode).
    // Loest einen BENANNTEN Zieldokument-Verweis auf. Der Name ist derselbe, den
    // <add id="..."> vergibt: ContentGenerator::set_template(id, uri). Von dort
    // geht es ueber uriToIndex() zurueck auf den Slot - genau der Weg, den
    // tree_main.php:124 fuer das Ausgabedokument schon geht (change_URI).
    //
    // "@me" ist der eigene Baum, wie in tree_content.php:123.
    //
    // ⚠ Ein unbekannter Name WIRFT. Er fiele sonst auf die Positionswahl zurueck und
    // schriebe still in ein fremdes Dokument - dieselbe Falle wie ein vergessenes
    // PREFIX in SPARQL, das bis 2026-09-14 null Zeilen statt eines Fehlers gab.
    private function _find_named_idx(?string $ziel): ?int
    {
        if ($ziel === null || '' === trim($ziel)) return null;
        $ziel = trim($ziel);

        if ('@me' === $ziel) return $this->base_object->cur_idx();

        $cg = method_exists($this->base_object, 'get_context_generator')
            ? $this->base_object->get_context_generator() : null;

        $uri = (is_object($cg) && method_exists($cg, 'get_template'))
            ? $cg->get_template($ziel) : null;

        if (!$uri)
            throw new \RuntimeException(
                'RstTurtle: target "' . $ziel . '" steht nicht im Template-Register. '
                . 'Der Name kommt von <add id="..."> oder <main>; "@me" ist der eigene Baum.');

        $idx = $this->base_object->uriToIndex($uri);

        if (false === $idx)
            throw new \RuntimeException(
                'RstTurtle: target "' . $ziel . '" zeigt auf "' . $uri . '", aber dieses '
                . 'Dokument ist nicht geladen.');

        return (int) $idx;
    }

    private function _find_main_doc_idx(): ?int
    {
        $RDF_ROOT = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#RDF';
        $max = $this->base_object->max_idx ?? 0;
        $result = null;
        for ($i = 0; $i <= $max; $i++) {
            $uri = $this->base_object->loaded_URI[$i] ?? '';
            $m   = $this->base_object->mirror[$i] ?? null;
            if (is_object($m)
                && $m->full_URI() === $RDF_ROOT
                && $uri !== ''
                && $uri[0] !== '@') {
                $result = $i;
            }
        }
        return $result;
    }

    // Appends new predicate nodes to already-registered subjects.
    // Sets cur_pointer to the canonical node, calls tag_open/cdata/tag_close,
    // then restores cur_pointer so the tree remains consistent.
    //
    // MUST run with the OWL document's idx (target_idx) so that:
    //   (a) tag_open creates nodes with the correct idx
    //   (b) the saved_cur for the restore is rdf:RDF root (idx=2), not an
    //       arbitrary node from the sub-script's slot
    //
    // ALIAS BUG: tag_close does  cur_pointer[$idx] = &var->prev_el, making
    // them reference aliases.  A plain  cur_pointer[$idx] = $saved_cur  would
    // then corrupt var->prev_el.  Break the alias with unset() first.
    /* ⚠ $ziel wird HEREINGEREICHT. Bis 2026-09-21 stand hier $data['target'] - eine
    *  Variable, die es in dieser Methode gar nicht gibt (sie ist Parameter von
    *  parse_document_from_array). Das ?? verschluckte den Fehler, das benannte Ziel
    *  wurde ignoriert, und die Anreicherung eines SCHON BEKANNTEN Knotens landete im
    *  positionell gewaehlten Slot - also womoeglich in einer Ontologie.
    *  Eingeschleppt beim Bau von "target" (aa97a53): die Ersetzung traf beide
    *  Aufrufstellen, geprueft wurde nur die eine. */
    private function _extend_existing(array $existing_subjects, array $prefixes, ?string $ziel = null): void
    {
        $RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

        $target_idx = $this->_find_named_idx($ziel)
                    ?? $this->_find_turtle_idx() ?? $this->_find_main_doc_idx();
        if ($target_idx === null) return;

        $saved_idx = $this->base_object->idx;
        $this->base_object->change_idx($target_idx);
        $idx = $target_idx;

        foreach ($existing_subjects as $subject_uri => $triples) {
            $tree_node = &$this->base_object->get_Tree_Node_of_Namespace($subject_uri);
            if (!is_object($tree_node)) continue;

            $saved_cur = $this->base_object->cur_pointer[$idx] ?? null;
            // Break any alias between cur_pointer[$idx] and mirror[$idx] before
            // assigning — a plain value-assign would otherwise corrupt mirror[$idx].
            unset($this->base_object->cur_pointer[$idx]);
            $this->base_object->cur_pointer[$idx] = $tree_node;

            $ersetzt = [];   // Praedikate, deren alte Werte in DIESEM Lauf schon weg sind

            foreach ($triples as $t) {
                if ($t['predicate'] === $RDF_TYPE) continue;

                $kind = $this->_assemble_predicate($t, $prefixes);
                if ($kind === null) continue;

                if (($t['mode'] ?? 'add') === 'replace' && !isset($ersetzt[$t['predicate']])) {
                    $this->_remove_predicate($tree_node, $t['predicate'], $t['object']);
                    $ersetzt[$t['predicate']] = true;
                }

                $this->base_object->tag_open($this, $kind['tag'], $kind['attribs']);
                if ($kind['text'] !== null)
                    $this->base_object->cdata($this, $kind['text']);
                $this->base_object->tag_close($this, $kind['tag']);
            }

            // tag_close left cur_pointer[$idx] aliased to the last predicate node's
            // prev_el.  A plain value-assign would corrupt that prev_el through the
            // shared PHP reference container.  Break the alias with unset() first so
            // the predicate node's prev_el (= the subject node) stays intact.
            unset($this->base_object->cur_pointer[$idx]);
            $this->base_object->cur_pointer[$idx] = $saved_cur;
        }

        $this->base_object->change_idx($saved_idx);
    }

    /* ERSETZEN (2026-10-04): die alten Werte EINES Praedikats an einem vorhandenen
    *  Subjekt entfernen, bevor die neuen angehaengt werden. Gerufen einmal je Subjekt,
    *  Praedikat und Lauf - weitere Werte desselben Laufs haengen danach wieder an, so
    *  wird eine Menge ersetzt.
    *
    *  Entfernt wird ueber removeNode(): haengt ab, rueckt die Textbehaelter nach
    *  (removeRefnext, seit 2026-10-03), loest die Zuhoererkanten und traegt den Knoten
    *  aus link_to_instance seiner Klasse aus.
    *
    *  ⚠ Das queryable-Attribut zieht mit. Es ist der Suchweg fuer SPARQL und stand bis
    *  hierher auf dem ERSTEN Wert, waehrend sich die Kindknoten haeuften (2026-09-21) -
    *  nach einem Ersetzen saehe SPARQL sonst den alten Wert. Steht keins, wird keins
    *  angelegt: ein Anhaengen tut das auch nicht. */
    private function _remove_predicate($subject_node, string $predicate, array $object): void
    {
        $alte = [];
        for ($i = 0, $n = $subject_node->index_max(); $i < $n; $i++) {
            $kind = $subject_node->getRefnext($i, true);
            if (is_object($kind) && $this->_same_uri($kind->full_URI(), $predicate))
                $alte[] = $kind;
        }

        foreach ($alte as $kind) $kind->removeNode();

        /* ⚠ NICHT set_ns_attribute: es sucht den Praefix in der Wurzel des Dokuments,
        *  und wo der dort nicht erklaert ist (RstTurtle schreibt die xmlns an sein
        *  eigenes rdf:RDF, das beim Einfuegen wegfaellt), legt es einen ZWEITEN
        *  Schluessel mit dem lokalen Namen an - gespeichert stand dann
        *  ex:label="alt" label="neu" (gemessen 2026-10-04). Attribute sind Knoten:
        *  den Wert des vorhandenen aendern, der Schluessel bleibt. */
        $attr = $subject_node->get_ns_attribute_obj($predicate);
        if (is_object($attr))
            $attr->setdata((string)$object['value'], 0);
    }

    // full_URI() haengt '#' auch an einen Namensraum, der schon auf '/' endet
    // (dcterms -> http://purl.org/dc/terms/#title). Beide Schreibweisen sind dasselbe.
    private function _same_uri(string $a, string $b): bool
    {
        return $a === $b || str_replace('/#', '/', $a) === str_replace('/#', '/', $b);
    }

    private function _ontology_uri(array $data): string
    {
        $rdf_type = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
        $owl_ont  = 'http://www.w3.org/2002/07/owl#Ontology';
        foreach ($data['subjects'] as $subject => $triples) {
            foreach ($triples as $t) {
                if ($t['predicate'] === $rdf_type && $t['object']['value'] === $owl_ont)
                    return $subject;
            }
        }
        return '';
    }

    // Converts the intermediate array to an RDF/XML string suitable for
    // load_Stream('XML'). Each subject becomes one top-level element:
    //   - first rdf:type → element tag name  (e.g. <owl:Class rdf:about="...">)
    //   - no rdf:type    → <rdf:Description rdf:about="...">
    //   - extra types    → <rdf:type rdf:resource="..."/> children
    // xmlns: declarations use the hardf prefix URIs with trailing # stripped,
    // matching qPortal's namespace_frameworks key convention.
    private function _to_rdf_xml(array $data): string
    {
        $prefixes = $data['prefixes'];

        $xmlns = '';
        foreach ($prefixes as $prefix => $uri) {
            $decl = rtrim($uri, '#/');
            $xmlns .= "\n    xmlns:{$prefix}=\"" . htmlspecialchars($decl, ENT_XML1 | ENT_QUOTES) . '"';
        }

        $body = '';
        foreach ($data['subjects'] as $subject => $triples)
            $body .= $this->_node_to_xml(
                $this->_assemble_subject($subject, $triples, $prefixes, !empty($data['queryable'])));

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rdf:RDF{$xmlns}>\n{$body}\n</rdf:RDF>";
    }

    /* ====================================================== Der Zusammenbau
    *
    *  Aus einem Subjekt und seinen Tripeln wird ein KNOTENBILD - Tag, Attribute, Kinder,
    *  Text, noch ohne Schreibweise:
    *
    *    ['tag' => 'storage:StockItem', 'attribs' => ['rdf:about' => '…', …],
    *     'children' => [ ['tag' => 'storage:amount', 'attribs' => [], 'text' => '2'], … ]]
    *
    *  Die Werte stehen ROH darin; maskiert wird erst beim Schreiben. Zwei Abnehmer:
    *  _node_to_xml macht Text daraus (neue Subjekte, load_Stream), _extend_existing
    *  Knoten (tag_open/cdata/tag_close an ein vorhandenes Subjekt). Bis 2026-10-04 bauten
    *  beide selbst - zweimal dieselbe Entscheidung, und sie liefen schon auseinander:
    *  ein leerer Knoten (bnode) als Objekt wurde dort uebersprungen und hier als leeres
    *  Element angelegt. Jetzt wird er in beiden Wegen uebersprungen.
    *
    *  Hier setzt eine zweite Schreibweise an (STW: PEDL - Struktur als Enthaltensein
    *  statt als Kante). Sie ist noch nicht entschieden: wo ein Ding zum zweiten Mal
    *  auftritt, muss es formal eine Referenz sein, und die Grundlage (Container gegen
    *  Property) ist in PEDL selbst noch nicht stimmig. */

    // Ein Subjekt: erster Typ -> Tag, ohne Typ rdf:Description, weitere Typen als
    // rdf:type-Kinder (⚠ Altlast, STW 09-15), danach die Praedikate in ihrer Reihenfolge.
    private function _assemble_subject(string $subject, array $triples, array $prefixes, bool $queryable): array
    {
        $rdf_type = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

        $types  = [];
        $others = [];
        foreach ($triples as $t) {
            if ($t['predicate'] === $rdf_type)
                $types[] = $t['object']['value'];
            else
                $others[] = $t;
        }

        $tag = !empty($types)
            ? $this->_uri_to_qname(array_shift($types), $prefixes)
            : 'rdf:Description';

        $attribs = ['rdf:about' => $subject];

        /* queryable: dieselben Praedikate ZUSAETZLICH als Attribute. SPARQL_Tree_Query
        *  liest ein Praedikat als ATTRIBUT (Element=Subjekt, Attribut=Praedikat,
        *  Attributwert=Objekt); die gestreifte Kindknotenform darunter sieht es nicht.
        *  Gemessen 2026-09-20: ohne dies fand "?s storage:designation ?o" 0 von 7.
        *
        *  ⚠ Ein Attributname kommt je Element nur EINMAL vor. Bei einem mehrfach
        *  belegten Praedikat traegt darum nur das erste - die Kindknoten darunter
        *  halten weiter alle. Das Attribut ist der Suchweg, nicht die Wahrheit.
        *
        *  ⚠ Der Datentyp geht im Attribut verloren (ein Attributwert ist eine
        *  Zeichenkette). Er steht unveraendert am Kindknoten. */
        if ($queryable)
            foreach ($others as $t) {
                $name = $this->_uri_to_qname($t['predicate'], $prefixes);
                if (!array_key_exists($name, $attribs))
                    $attribs[$name] = (string)$t['object']['value'];
            }

        $children = [];
        foreach ($types as $extra_type)
            $children[] = ['tag' => 'rdf:type', 'attribs' => ['rdf:resource' => $extra_type], 'text' => null];

        foreach ($others as $t) {
            $kind = $this->_assemble_predicate($t, $prefixes);
            if ($kind !== null) $children[] = $kind;
        }

        return ['tag' => $tag, 'attribs' => $attribs, 'children' => $children];
    }

    // Ein Praedikat: Verweis -> rdf:resource ohne Text, Literal -> Text mit Datentyp
    // und Sprache. Ein leerer Knoten (bnode) als Objekt -> null, also kein Kind.
    private function _assemble_predicate(array $t, array $prefixes): ?array
    {
        $obj = $t['object'];
        $tag = $this->_uri_to_qname($t['predicate'], $prefixes);

        if ($obj['type'] === 'uri')
            return ['tag' => $tag, 'attribs' => ['rdf:resource' => $obj['value']], 'text' => null];

        if ($obj['type'] === 'literal') {
            $attribs = [];
            if ($obj['datatype']) $attribs['rdf:datatype'] = $obj['datatype'];
            if ($obj['lang'])     $attribs['xml:lang']     = $obj['lang'];
            return ['tag' => $tag, 'attribs' => $attribs, 'text' => (string)$obj['value']];
        }

        return null;
    }

    // Die RDF/XML-Schreibweise eines Knotenbilds - Einrueckung wie bisher, Attribute
    // mit ENT_QUOTES, Text ohne.
    private function _node_to_xml(array $node): string
    {
        $attr = function(array $a): string {
            $out = '';
            foreach ($a as $k => $v)
                $out .= ' ' . $k . '="' . htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES) . '"';
            return $out;
        };

        $xml = "\n  <{$node['tag']}" . $attr($node['attribs']) . '>';

        foreach ($node['children'] as $c) {
            if ($c['text'] === null)
                $xml .= "\n    <{$c['tag']}" . $attr($c['attribs']) . '/>';
            else
                $xml .= "\n    <{$c['tag']}" . $attr($c['attribs']) . '>'
                      . htmlspecialchars($c['text'], ENT_XML1) . "</{$c['tag']}>";
        }

        return $xml . "\n  </{$node['tag']}>";
    }

    private function _uri_to_qname(string $uri, array $prefixes): string
    {
        foreach ($prefixes as $prefix => $ns_uri) {
            if (str_starts_with($uri, $ns_uri))
                return $prefix . ':' . substr($uri, strlen($ns_uri));
        }
        return $uri;
    }

    // Returns the intermediate array structure:
    //
    // [
    //   'prefixes' => [ 'rdf' => 'http://...', 'owl' => 'http://...', ... ],
    //   'subjects' => [
    //     'http://subject-uri' => [
    //       [
    //         'predicate' => 'http://predicate-uri',
    //         'object'    => [
    //           'type'     => 'uri' | 'literal' | 'bnode',
    //           'value'    => '...',
    //           'datatype' => 'http://...' | null,  // only for literals
    //           'lang'     => 'en' | null,           // only for lang-tagged literals
    //         ]
    //       ],
    //       ...
    //     ],
    //     ...
    //   ]
    // ]
    function _parse_to_array($source)
    {
        $this->prefixes = [];
        $triples = [];

        $parser = new \pietercolpaert\hardf\TriGParser(['format' => 'turtle']);
        $parser->parse(
            $source,
            function ($error, $triple) use (&$triples) {
                if ($error) throw new \RuntimeException('Turtle parse error: ' . $error);
                if ($triple !== null) $triples[] = $triple;
            },
            function ($prefix, $iri) {
                $this->prefixes[$prefix] = $iri;
            }
        );

        $subjects = [];
        foreach ($triples as $triple) {
            $subjects[$triple['subject']][] = [
                'predicate' => $triple['predicate'],
                'object'    => $this->_decode_object($triple['object']),
            ];
        }

        return ['prefixes' => $this->prefixes, 'subjects' => $subjects];
    }

    private function _decode_object($term)
    {
        if (\pietercolpaert\hardf\Util::isLiteral($term)) {
            return [
                'type'     => 'literal',
                'value'    => \pietercolpaert\hardf\Util::getLiteralValue($term),
                'datatype' => \pietercolpaert\hardf\Util::getLiteralType($term) ?: null,
                'lang'     => \pietercolpaert\hardf\Util::getLiteralLanguage($term) ?: null,
            ];
        }
        if (str_starts_with($term, '_:')) {
            return ['type' => 'bnode', 'value' => $term, 'datatype' => null, 'lang' => null];
        }
        return ['type' => 'uri', 'value' => $term, 'datatype' => null, 'lang' => null];
    }

    // Feeds the intermediate array into the qPortal SAX tree.
    // xmlns attributes are intentionally omitted from tag_open — they trigger
    // the native code path which requires a registered namespace handler.
    function _emit_array($data)
    {
        $this->base_object->tag_open($this, 'turtle', []);

        foreach ($data['subjects'] as $subject => $triples) {
            $this->base_object->tag_open($this, 'subject', ['uri' => $subject]);

            foreach ($triples as $triple) {
                $this->base_object->tag_open($this, 'predicate', ['uri' => $triple['predicate']]);
                $this->_emit_object($triple['object']);
                $this->base_object->tag_close($this, 'predicate');
            }

            $this->base_object->tag_close($this, 'subject');
        }

        $this->base_object->tag_close($this, 'turtle');
    }

    private function _emit_object($obj)
    {
        if ($obj['type'] === 'literal') {
            $attribs = [];
            if ($obj['datatype']) $attribs['datatype'] = $obj['datatype'];
            if ($obj['lang'])     $attribs['lang']     = $obj['lang'];
            $this->base_object->tag_open($this, 'literal', $attribs);
            $this->base_object->cdata($this, $obj['value']);
            $this->base_object->tag_close($this, 'literal');
        } else {
            $this->base_object->tag_open($this, 'uri', []);
            $this->base_object->cdata($this, $obj['value']);
            $this->base_object->tag_close($this, 'uri');
        }
    }

    /* ====================================================== Das Schreiben
    *
    *  WELCHER INHALT (STW 2026-10-04): instanzweit oder lokal - beides nuetzlich, das
    *  erste die Vorgabe.
    *
    *    doctype_out="TURTLE"                  alles, was die Instanz an Bedeutung kennt:
    *                                          jeder registrierte Name mit einem Knoten im
    *                                          Baum, ueber alle geladenen Dokumente,
    *                                          Vokabulare eingeschlossen (gefiltert wird
    *                                          danach)
    *    doctype_out="TURTLE;scope:document"   nur das Ausgabedokument, Element fuer
    *                                          Element (der Weg bis 2026-10-04)
    *
    *  Bis 2026-10-04 gab es nur den zweiten, und er las den Typ aus full_URI() des
    *  Knotens. Bei einer rdfs:Class-Instanz hat der Knoten seinen type mit der eigenen
    *  Identitaet ueberschrieben - heraus kam "fridge_door rdf:type fridge_door". Jetzt in
    *  beiden Wegen: rdf:type ist EIN Schritt link_to_class (STW 2026-09-15). */
    function save_back($format, $send_header = false)
    {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $writer = new \pietercolpaert\hardf\TriGWriter(['prefixes' => $this->_writer_prefixes()]);

        if ($this->_option('scope') === 'document')
            $this->_write_document($writer);
        else
            $this->_write_instance($writer);

        return $writer->end();
    }

    // Eine Option aus dem Beschreibungstext, wie CSV: "TURTLE;scope:document".
    private function _option(string $name): ?string
    {
        $type  = $this->base_object->TYPE[$this->base_object->idx] ?? '';
        $parts = explode(';', (string)$type);
        for ($i = 1; $i < count($parts); $i++) {
            $pair = explode(':', $parts[$i], 2);
            if (count($pair) == 2 && strtolower(trim($pair[0])) === strtolower($name))
                return strtolower(trim($pair[1]));
        }
        return null;
    }

    // Die Praefixe aus dem xmlns-Stapel. qPortal fuehrt URIs ohne abschliessendes '#',
    // hardf braucht '#' oder '/' am Ende.
    private function _writer_prefixes(): array
    {
        $prefixes = [];
        foreach ($this->base_object->prefixes as $key => $stack) {
            if (is_string($key) && $key !== '' && is_array($stack)) {
                $uri = end($stack);
                if ($uri) $prefixes[$key] = $uri . '#';
            }
        }

        // qPortal core hardcodes xmlns:xsd = rdfs namespace (historical bug in TreeEngine.php).
        // Override with correct standard URIs so Turtle output is valid.
        $prefixes['xsd']  = 'http://www.w3.org/2001/XMLSchema#';
        $prefixes['rdfs'] = 'http://www.w3.org/2000/01/rdf-schema#';

        return $prefixes;
    }

    // Der Typ eines Knotens: EIN Schritt link_to_class. rdf:Description ist kein Typ.
    //
    // ⚠ Ist der Schritt UNBENANNT, traegt der Tag die Aussage (STW 09-15: "der Tag IST
    // die Aussage"). So bei den Definitionen eines Vokabulars: <owl:Class rdf:about=…>
    // stammt vom Fabrik-Prototyp der Klasse OWL_Class, und der heisst "none#none" -
    // gemessen 2026-10-04 an 31 von 43 Subjekten. Ihr Tag (owl:Class) ist der Typ.
    private function _type_of($node): ?string
    {
        $klasse = is_object($node) ? $node->linkToClass() : null;
        if (!is_object($klasse)) return null;

        $typ = $klasse->full_URI();
        if (str_starts_with($typ, 'none#') || str_starts_with($typ, '#'))
            $typ = $node->full_URI();
        return ($typ === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Description') ? null : $typ;
    }

    // INSTANZWEIT: aus dem Register. Hinein kommt jeder Name mit einem Knoten im Baum -
    // der Repraesentant fuehrt ueber link_to_class dorthin (rdf:about, rdf:ID), eine
    // rdfs:Class steht selbst dort. Prototypen der Fabrik und leere Eintraege nicht.
    //
    // TOPOLOGISCH (STW): was ein Ding benutzt, steht vor ihm - sein Typ und die anderen
    // registrierten Dinge, auf die es per rdf:resource zeigt. Bei einem Kreis (Mieter ->
    // Konto -> Inhaber) entscheidet die Reihenfolge im Register.
    private function _write_instance($writer): void
    {
        $RDF_RESOURCE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#resource';

        $knoten = [];   // Name => Knoten im Baum, in Registerreihenfolge
        foreach ($this->base_object->namespace_frameworks as $ns => $fw) {
            foreach (($fw['node'] ?? []) as $name => $n) {
                if (!is_object($n)) continue;

                if (is_object($n->getRefprev()))
                    $k = $n;
                elseif (is_object($l = $n->linkToClass()) && is_object($l->getRefprev()))
                    $k = $l;
                else
                    continue;

                $knoten[$ns . '#' . $name] = $k;
            }
        }

        $folge  = [];
        $status = [];   // 1 = in Arbeit, 2 = fertig
        $besuche = function(string $uri) use (&$besuche, &$folge, &$status, $knoten, $RDF_RESOURCE) {
            if (isset($status[$uri])) return;
            $status[$uri] = 1;

            $k   = $knoten[$uri];
            $vor = [];
            if (($t = $this->_type_of($k)) !== null) $vor[] = $t;
            for ($j = 0, $m = $k->index_max(); $j < $m; $j++) {
                $p = $k->getRefnext($j);
                if (is_object($p) && false !== ($r = $p->get_ns_attribute($RDF_RESOURCE))) $vor[] = $r;
            }

            foreach ($vor as $v)
                if ($v !== $uri && isset($knoten[$v])) $besuche($v);

            $status[$uri] = 2;
            $folge[] = $uri;
        };

        foreach (array_keys($knoten) as $uri) $besuche($uri);

        foreach ($folge as $uri)
            $this->_write_node($writer, $uri, $knoten[$uri]);
    }

    // LOKAL: das Ausgabedokument, Element fuer Element - nur, was rdf:about traegt.
    private function _write_document($writer): void
    {
        $RDF_ABOUT = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#about';

        // Use the TURTLE output slot so that both the base ontology and plugin-inserted
        // subjects are visible, regardless of what idx is current at render time.
        $turtle_idx = $this->_find_turtle_idx();
        $idx  = $turtle_idx ?? $this->base_object->idx;
        $root = $this->base_object->mirror[$idx] ?? null;
        if (!is_object($root)) return;

        for ($i = 0, $n = $root->index_max(); $i < $n; $i++) {
            $s = $root->getRefnext($i);
            if (!is_object($s)) continue;

            $subject = $s->get_ns_attribute($RDF_ABOUT);
            if ($subject === false || $subject === '') continue;

            $this->_write_node($writer, $subject, $s);
        }
    }

    // Ein Subjekt: sein Typ, dann je Kind ein Tripel - Verweis per rdf:resource, sonst
    // der Text als Literal mit Sprache oder Datentyp. Leerer Text traegt nichts.
    private function _write_node($writer, string $subject, $s): void
    {
        $RDF_TYPE     = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
        $RDF_RESOURCE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#resource';
        $RDF_DATATYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#datatype';
        $XML_LANG     = 'http://www.w3.org/XML/1998/namespace#lang';

        if (($type = $this->_type_of($s)) !== null)
            $writer->addTriple($subject, $RDF_TYPE, $type);

        for ($j = 0, $m = $s->index_max(); $j < $m; $j++) {
            $p = $s->getRefnext($j);
            if (!is_object($p)) continue;

            $predicate = $p->full_URI();

            // URI object via rdf:resource attribute
            $resource = $p->get_ns_attribute($RDF_RESOURCE);
            if ($resource !== false) {
                $writer->addTriple($subject, $predicate, $resource);
                continue;
            }

            // Literal object — getdata() with no args concatenates all text segments.
            // ⚠ Ein Objekt im Datenteil (rdfs:range mit genau einem Repraesentanten,
            // xml_multitree_ns) ist kein Literal - uebersprungen.
            $value = $p->getdata();
            if (!is_scalar($value) || $value === '' || $value === false) continue;

            $datatype = $p->get_ns_attribute($RDF_DATATYPE);
            $lang     = $p->get_ns_attribute($XML_LANG);

            if ($lang !== false) {
                $object = \pietercolpaert\hardf\Util::createLiteral((string)$value, $lang);
            } elseif ($datatype !== false) {
                $object = \pietercolpaert\hardf\Util::createLiteral((string)$value, $datatype);
            } else {
                $object = \pietercolpaert\hardf\Util::createLiteral((string)$value);
            }

            $writer->addTriple($subject, $predicate, $object);
        }
    }

    function save_stream_back(&$stream, $format, $send_header = false)
    {
        return (false !== fwrite($stream, $this->save_back($format)));
    }

    function send_header()
    {
        header('Content-Type: text/turtle; charset=UTF-8');
    }
}

?>
