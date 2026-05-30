<?php

/**
 * @title: RstTurtle
 * @author: Stefan Wegerhoff
 * @description: Converts a recordset (rst) directly into the Turtle handle's
 *   intermediate array and injects it into the qPortal tree via
 *   Turtle_handle::parse_document_from_array() — no Turtle serialisation round-trip.
 *
 * Configuration methods (all callable directly from PEDL or via configuration()):
 *
 *   setPrefix($prefix, $namespace)
 *     Registers a namespace prefix.
 *     $namespace must include the trailing separator (e.g. 'http://example.org/vocab#').
 *
 *   setSubject($column_name, $type, $column_type)
 *     Defines the subject URI column and the rdf:type.
 *     $type (fixed string) and $column_type (rst column) are mutually exclusive — Exception if both set.
 *
 *   addPredicate($predicate, $column_predicate, $column_name, $datatype, $column_datatype)
 *     Adds a predicate mapping.
 *     $predicate / $column_predicate: fixed predicate URI/qname OR dynamic from column — mutually exclusive.
 *     $column_name: rst column holding the object literal value (required).
 *     $datatype / $column_datatype: fixed datatype URI/qname OR dynamic from column — mutually exclusive.
 *
 *   configuration($json)
 *     Convenience loader — calls the setters above from a JSON blob.
 *     JSON structure:
 *     {
 *       "prefixes":   { "ex": "http://example.org/vocab#" },
 *       "subject":    { "column": "_about", "type": "ex:Person" },
 *       "predicates": [
 *         { "predicate": "ex:name",  "column": "name" },
 *         { "predicate": "ex:age",   "column": "age",  "datatype": "xsd:integer" },
 *         { "column_predicate": "pred_col", "column": "val_col", "column_datatype": "dt_col" }
 *       ]
 *     }
 *
 *   execute()
 *     Iterates the rst, builds the intermediate array, and calls
 *     Turtle_handle::parse_document_from_array() on $this->back.
 */

require_once("plugin_interface.php");

class RstTurtle extends plugin
{
    private array   $prefixes             = [
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
    ];
    private ?string $subject_column       = null;
    private ?string $subject_prefix       = null;
    private ?string $subject_type         = null;
    private ?string $subject_type_column  = null;
    private array   $predicate_defs       = [];
    var $content = null;

    public function set_list(&$value)
    {
        $this->rst = &$value;
    }

    function __construct(/* System.Parser */ &$back, /* System.Content */ &$content)
    {
        $this->back    = &$back;
        $this->content = &$content;
    }

    public function setPrefix(string $prefix, string $namespace): void
    {
        $this->prefixes[$prefix] = $namespace;
    }

    public function setSubject(string $column_name, ?string $type = null, ?string $column_type = null, ?string $prefix = null): void
    {
        if ($type !== null && $column_type !== null)
            throw new \RuntimeException(
                "RstTurtle::setSubject — \$type and \$column_type are mutually exclusive: " .
                "got type=\"{$type}\" and column_type=\"{$column_type}\"."
            );
        $this->subject_column      = $column_name;
        $this->subject_prefix      = $prefix;
        $this->subject_type        = $type;
        $this->subject_type_column = $column_type;
    }

    public function addPredicate(
        ?string $predicate,
        ?string $column_predicate,
        string  $column_name,
        ?string $datatype        = null,
        ?string $column_datatype = null
    ): void {
        if ($predicate !== null && $column_predicate !== null)
            throw new \RuntimeException(
                "RstTurtle::addPredicate — \$predicate and \$column_predicate are mutually exclusive: " .
                "got predicate=\"{$predicate}\" and column_predicate=\"{$column_predicate}\"."
            );
        if ($predicate === null && $column_predicate === null)
            throw new \RuntimeException(
                "RstTurtle::addPredicate — either \$predicate or \$column_predicate must be set " .
                "(column_name=\"{$column_name}\")."
            );
        if ($datatype !== null && $column_datatype !== null)
            throw new \RuntimeException(
                "RstTurtle::addPredicate — \$datatype and \$column_datatype are mutually exclusive: " .
                "got datatype=\"{$datatype}\" and column_datatype=\"{$column_datatype}\"."
            );
        $this->predicate_defs[] = [
            'predicate'        => $predicate,
            'column_predicate' => $column_predicate,
            'column_name'      => $column_name,
            'datatype'         => $datatype,
            'column_datatype'  => $column_datatype,
        ];
    }

    public function configuration($json): void
    {
        $cfg = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        foreach ($cfg['prefixes'] ?? [] as $prefix => $ns)
            $this->setPrefix($prefix, $ns);

        if (isset($cfg['subject'])) {
            $s = $cfg['subject'];
            $this->setSubject(
                $s['column'],
                $s['type']        ?? null,
                $s['column_type'] ?? null,
                $s['prefix']      ?? null
            );
        }

        foreach ($cfg['predicates'] ?? [] as $p) {
            $this->addPredicate(
                $p['predicate']        ?? null,
                $p['column_predicate'] ?? null,
                $p['column'],
                $p['datatype']         ?? null,
                $p['column_datatype']  ?? null
            );
        }
    }

    public function execute(): void
    {
        if (!$this->rst)
            throw new \RuntimeException(
                "RstTurtle::execute — no recordset set. Call set_list() first."
            );
        if ($this->subject_column === null)
            throw new \RuntimeException(
                "RstTurtle::execute — subject not configured. Call setSubject() first."
            );
        if (empty($this->predicate_defs))
            throw new \RuntimeException(
                "RstTurtle::execute — no predicates configured. Call addPredicate() first."
            );

        require_once dirname(__DIR__) . '/classes/handles/class_index.php';

        $RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
        $subjects = [];

        $mf = $this->rst->moveFirst();
        //file_put_contents('/tmp/rst_turtle_debug.txt', "moveFirst=" . var_export($mf, true) . "\n", FILE_APPEND);
        if (!$mf) return;

        do {
            $raw = (string)$this->rst->col($this->subject_column);
            if ($raw === '') continue;
            
            $subject = $this->subject_prefix !== null
                ? $this->subject_prefix . preg_replace('/[^\w\-.~]/u', '_', $raw)
                : $raw;

            $triples = [];

            // rdf:type triple
            $raw_type = $this->subject_type_column !== null
                ? (string)$this->rst->col($this->subject_type_column)
                : $this->subject_type;
            if ($raw_type !== null && $raw_type !== '') {
                $triples[] = [
                    'predicate' => $RDF_TYPE,
                    'object'    => ['type' => 'uri', 'value' => $this->_resolve($raw_type), 'datatype' => null, 'lang' => null],
                ];
            }

            // predicate triples
            foreach ($this->predicate_defs as $def) {
                $pred_uri = $def['column_predicate'] !== null
                    ? $this->_resolve((string)$this->rst->col($def['column_predicate']))
                    : $this->_resolve($def['predicate']);

                $value = $this->rst->col($def['column_name']);
                if ($value === null || $value === false || (string)$value === '') continue;

                $dt_raw = $def['column_datatype'] !== null
                    ? (string)$this->rst->col($def['column_datatype'])
                    : $def['datatype'];
                $dt = ($dt_raw !== null && $dt_raw !== '') ? $this->_resolve($dt_raw) : null;

                $triples[] = [
                    'predicate' => $pred_uri,
                    'object'    => ['type' => 'literal', 'value' => (string)$value, 'datatype' => $dt, 'lang' => null],
                ];
            }

            if (!empty($triples))
                $subjects[$subject] = array_merge($subjects[$subject] ?? [], $triples);

        } while ($this->rst->next());

        if (empty($subjects)) return;

        $handle = My_Handle_factory::handle_factory('TURTLE');
        $handle->set_object($this->back);
        $handle->parse_document_from_array(['prefixes' => $this->prefixes, 'subjects' => $subjects], true);
    }

    public function getAdditiveSource() {}

    // Expands a qname (prefix:local) to a full URI using the configured prefixes.
    // Full URIs (containing '//') are returned unchanged.
    private function _resolve(string $value): string
    {
        if (!str_contains($value, '//') && str_contains($value, ':')) {
            [$prefix, $local] = explode(':', $value, 2);
            if (isset($this->prefixes[$prefix]))
                return $this->prefixes[$prefix] . $local;
        }
        return $value;
    }
}
?>
