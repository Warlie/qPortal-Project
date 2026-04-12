<?PHP

/**
 * Demux
 *
 * Tokenises a structured AI response into named columns.
 *
 * The AI prompt defines the output order; setFields() mirrors that order
 * as column names. tokenize() splits each upstream row by delimiter and
 * maps the resulting tokens to those names.
 *
 * Example:
 *   Prompt:   "Schreibe Person, Tag, Uhrzeit durch Komma getrennt."
 *   Response: "Tabea, Dienstag, 16:00"
 *   setFields("person,day,time")
 *   col('person') → "Tabea"
 *   col('day')    → "Dienstag"
 *   col('time')   → "16:00"
 *
 * @title:       Demux
 * @author:      Stefan Wegerhoff
 * @description: Tokenises a structured AI response into named columns
 */

require_once("PlugIn/plugin_interface.php");

class Demux extends plugin
{
    private $fieldNames = [];   // ordered column names matching the prompt output
    private $delimiter  = ',';  // token separator
    private $column     = 'value'; // column to read from upstream

    function __construct() {}

    /**
     * Set which column from the upstream source holds the AI response text.
     * Defaults to 'value'.
     */
    public function column($name)
    {
        $this->column = $name;
    }

    /**
     * Set the token delimiter. Defaults to ','.
     */
    public function delimiter($char)
    {
        $this->delimiter = $char;
    }

    /**
     * Set the ordered field names matching the prompt output order.
     * Accepts a delimiter-separated string, e.g. "person,day,time".
     */
    public function setFields($fields)
    {
        $this->fieldNames = array_map('trim', explode($this->delimiter, $fields));
    }

    /**
     * Split all upstream rows by delimiter and map tokens to field names.
     * Populates internal_table_values — col/next/moveFirst work via base class.
     */
    public function tokenize()
    {
        $this->internal_table_values = [];
        if (!$this->rst || empty($this->fieldNames)) return;

        $this->rst->moveFirst();
        do {
            $tokens = array_map('trim', explode($this->delimiter, $this->rst->col($this->column)));
            $row = [];
            foreach ($this->fieldNames as $i => $name) {
                $row[$name] = $tokens[$i] ?? null;
            }
            $this->internal_table_values[] = $row;
        } while ($this->rst->next());

        reset($this->internal_table_values);
    }

    public function fields() { return $this->fieldNames; }
}
?>
