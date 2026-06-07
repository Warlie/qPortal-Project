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
                $target_idx = $this->_find_turtle_idx() ?? $this->_find_main_doc_idx();
                if ($target_idx !== null) {
                    $saved_idx = $this->base_object->idx;
                    $this->base_object->change_idx($target_idx);
                    $this->base_object->set_first_node();
                    $stamp    = $this->base_object->position_stamp();
                    $new_data = ['prefixes' => $data['prefixes'], 'subjects' => $new];
                    $rdf_xml  = $this->_to_rdf_xml($new_data);
                    $this->base_object->load_Stream($rdf_xml, 0, 'XML', '', $stamp);
                    $this->base_object->change_idx($saved_idx);
                }
            }
        } else {
            // Pfad A (load_Stream) — convert to RDF/XML and load into a new slot.
            // Always run even when $new is empty so mirror[$idx] gets initialised (rdf:RDF root).
            $new_data = ['prefixes' => $data['prefixes'], 'subjects' => $new];
            $rdf_xml  = $this->_to_rdf_xml($new_data);
            $this->base_object->load_Stream($rdf_xml, 0, 'XML', $this->_ontology_uri($new_data));
        }

        // Pfad B — add new predicates directly to the already-registered canonical node.
        if (!empty($existing))
            $this->_extend_existing($existing, $data['prefixes']);
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
    private function _find_main_doc_idx(): ?int
    {
        $RDF_ROOT = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#RDF';
        $max = $this->base_object->max_idx ?? 0;
        for ($i = 0; $i <= $max; $i++) {
            $uri = $this->base_object->loaded_URI[$i] ?? '';
            $m   = $this->base_object->mirror[$i] ?? null;
            if (is_object($m)
                && $m->full_URI() === $RDF_ROOT
                && $uri !== ''
                && $uri[0] !== '@') {
                return $i;
            }
        }
        return null;
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
    private function _extend_existing(array $existing_subjects, array $prefixes): void
    {
        $RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

        $target_idx = $this->_find_turtle_idx() ?? $this->_find_main_doc_idx();
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

            foreach ($triples as $t) {
                if ($t['predicate'] === $RDF_TYPE) continue;

                $pred_qname = $this->_uri_to_qname($t['predicate'], $prefixes);
                $obj        = $t['object'];

                $attribs = [];
                if ($obj['type'] === 'uri') {
                    $attribs['rdf:resource'] = $obj['value'];
                } elseif ($obj['type'] === 'literal') {
                    if ($obj['datatype']) $attribs['rdf:datatype'] = $obj['datatype'];
                    if ($obj['lang'])     $attribs['xml:lang']     = $obj['lang'];
                }

                $this->base_object->tag_open($this, $pred_qname, $attribs);
                if ($obj['type'] === 'literal')
                    $this->base_object->cdata($this, $obj['value']);
                $this->base_object->tag_close($this, $pred_qname);
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
        $rdf_type = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

        $xmlns = '';
        foreach ($prefixes as $prefix => $uri) {
            $decl = rtrim($uri, '#/');
            $xmlns .= "\n    xmlns:{$prefix}=\"" . htmlspecialchars($decl, ENT_XML1 | ENT_QUOTES) . '"';
        }

        $body = '';
        foreach ($data['subjects'] as $subject => $triples) {
            $types  = [];
            $others = [];
            foreach ($triples as $t) {
                if ($t['predicate'] === $rdf_type)
                    $types[] = $t['object']['value'];
                else
                    $others[] = $t;
            }

            $element_tag = !empty($types)
                ? $this->_uri_to_qname(array_shift($types), $prefixes)
                : 'rdf:Description';

            $about = htmlspecialchars($subject, ENT_XML1 | ENT_QUOTES);
            $body .= "\n  <{$element_tag} rdf:about=\"{$about}\">";

            foreach ($types as $extra_type) {
                $res   = htmlspecialchars($extra_type, ENT_XML1 | ENT_QUOTES);
                $body .= "\n    <rdf:type rdf:resource=\"{$res}\"/>";
            }

            foreach ($others as $t) {
                $pred_tag = $this->_uri_to_qname($t['predicate'], $prefixes);
                $obj      = $t['object'];

                if ($obj['type'] === 'uri') {
                    $res   = htmlspecialchars($obj['value'], ENT_XML1 | ENT_QUOTES);
                    $body .= "\n    <{$pred_tag} rdf:resource=\"{$res}\"/>";
                } elseif ($obj['type'] === 'literal') {
                    $val    = htmlspecialchars($obj['value'], ENT_XML1);
                    $attrs  = '';
                    if ($obj['datatype']) $attrs .= ' rdf:datatype="' . htmlspecialchars($obj['datatype'], ENT_XML1 | ENT_QUOTES) . '"';
                    if ($obj['lang'])     $attrs .= ' xml:lang="'     . htmlspecialchars($obj['lang'],     ENT_XML1 | ENT_QUOTES) . '"';
                    $body .= "\n    <{$pred_tag}{$attrs}>{$val}</{$pred_tag}>";
                }
                // bnode objects: skipped
            }

            $body .= "\n  </{$element_tag}>";
        }

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rdf:RDF{$xmlns}>\n{$body}\n</rdf:RDF>";
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

    function save_back($format, $send_header = false)
    {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $RDF_TYPE     = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
        $RDF_ABOUT    = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#about';
        $RDF_RESOURCE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#resource';
        $RDF_DATATYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#datatype';
        $XML_LANG     = 'http://www.w3.org/XML/1998/namespace#lang';
        $RDF_DESC     = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Description';

        // Use the TURTLE output slot so that both the base ontology and plugin-inserted
        // subjects are visible, regardless of what idx is current at render time.
        $turtle_idx = $this->_find_turtle_idx();
        $idx  = $turtle_idx ?? $this->base_object->idx;
        $root = $this->base_object->mirror[$idx] ?? null;
        if (!is_object($root)) return '';

        // Rebuild prefix map from qPortal's xmlns stack.
        // qPortal stores URIs without trailing '#'; hardf requires '#' or '/' at the end.
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

        $writer = new \pietercolpaert\hardf\TriGWriter(['prefixes' => $prefixes]);

        for ($i = 0, $n = $root->index_max(); $i < $n; $i++) {
            $s = $root->getRefnext($i);
            if (!is_object($s)) continue;

            $subject = $s->get_ns_attribute($RDF_ABOUT);
            if ($subject === false || $subject === '') continue;

            // type triple from element name (rdf:Description = no-type fallback, skip)
            $type = $s->full_URI();
            if ($type !== $RDF_DESC) {
                $writer->addTriple($subject, $RDF_TYPE, $type);
            }

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

                // Literal object — getdata() with no args concatenates all text segments
                $value = $p->getdata();
                if ($value === null || $value === false || $value === '') continue;

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

        return $writer->end();
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
