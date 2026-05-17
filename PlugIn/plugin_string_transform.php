<?php

/**
 * @title:StringTransform
 * @author:Stefan Wegerhoff
 * @description: String transformation filter — sits in-line between a recordset and
 *               a consumer. Adds virtual columns derived from upstream columns.
 *
 * Methods (called via <remote name="StringTransform.method">...</remote>):
 *
 *   to_uri($json)
 *     JSON: {"target":"<col>","source":"<col>","prefix":"<uri-prefix>"}
 *     Generates a URI-safe slug from the source column and prepends the prefix.
 *     Replaces German umlauts, removes/encodes unsafe characters.
 *
 *   replace($json)
 *     JSON: {"target":"<col>","source":"<col>","map":{"ä":"ae", ...}}
 *     Replaces characters/strings in the source column using the given map.
 *     Uses PHP strtr() semantics (longest match first).
 */

require_once("plugin_interface.php");

class StringTransform extends plugin
{
    private $rules = [];
    var $rst = null;
    var $content = null;

    private static $DEFAULT_UMLAUT_MAP = [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
        'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
        'ß' => 'ss',
        'é' => 'e',  'è' => 'e',  'ê' => 'e',
        'á' => 'a',  'à' => 'a',  'â' => 'a',
        'ó' => 'o',  'ò' => 'o',  'ô' => 'o',
        'ú' => 'u',  'ù' => 'u',  'û' => 'u',
        'ñ' => 'n',  'ç' => 'c',
        ' ' => '_',  "\t" => '_',
        '&' => 'und', '@' => 'at', '+' => 'plus',
    ];

    function __construct(/* System.Parser */ &$back, /* System.Content */ &$content)
    {
        $this->back    = &$back;
        $this->content = &$content;
    }

    /**
     * Registers a URI-slug rule.
     * JSON: {"target":"<col>","source":"<col>","prefix":"<uri-prefix>"}
     */
    public function to_uri($json)
    { 
        $cfg = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->rules[$cfg['target']] = [
            'mode'   => 'to_uri',
            'source' => $cfg['source'],
            'prefix' => $cfg['prefix'] ?? '',
        ];
    }

    /**
     * Registers a character-replacement rule.
     * JSON: {"target":"<col>","source":"<col>","map":{"from":"to",...}}
     */
    public function replace($json)
    {
        $cfg = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->rules[$cfg['target']] = [
            'mode'   => 'replace',
            'source' => $cfg['source'],
            'map'    => $cfg['map'] ?? [],
        ];
    }

    public function col($columnname)
    { echo "booho";
        if (!$this->rst) return null;

        if (isset($this->rules[$columnname])) {
            $rule = $this->rules[$columnname];
            $val  = $this->rst->col($rule['source']);

            if ($rule['mode'] === 'to_uri') {
                $slug = strtr((string)$val, self::$DEFAULT_UMLAUT_MAP);
                $slug = preg_replace('/[^\w\-.~]/u', '', $slug);
                return $rule['prefix'] . $slug;
            }

            if ($rule['mode'] === 'replace') {
                return strtr((string)$val, $rule['map']);
            }
        }

        return $this->rst->col($columnname);
    }

    public function &iter()           { return $this; }
    public function moveFirst()       { return $this->rst ? $this->rst->moveFirst() : false; }
    public function moveLast()        { return $this->rst ? $this->rst->moveLast()  : false; }
    public function next()            { return $this->rst ? $this->rst->next()      : false; }
    public function fields()          { return $this->rst ? $this->rst->fields()    : []; }
    public function getAdditiveSource() {}
}
?>
