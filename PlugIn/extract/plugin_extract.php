<?PHP

/**
 * Extract
 *
 * Lazy filtering iterator that scans an upstream data source for occurrences
 * of known candidate names and exposes matching rows transparently.
 *
 * Sits as a pipeline stage between an AI response and CallTree:
 *   AI response → Extract → CallTree
 *
 * No rows are copied — the upstream cursor is advanced directly.
 * col() intercepts only the extraction column (returning the matched candidate);
 * all other columns are delegated to the upstream source unchanged.
 *
 * @title:       Extract
 * @author:      Stefan Wegerhoff
 * @description: Lazy filtering iterator; extracts candidate names from free-text upstream source
 */

require_once("PlugIn/plugin_interface.php");

class Extract extends plugin
{
    private $candidates   = [];      // valid names to search for
    private $column       = 'value'; // column to scan and intercept
    private $default = false;		// default for missmatch

    function __construct() {}

    /**
     * Receive an upstream data source (typically the AI response plugin).
     */
    public function set_list(&$value)
    {
        if (is_object($value)) {
            $this->rst = &$value;
        }
    }

    /**
     * Set which column from the upstream source holds the text to scan.
     * Defaults to 'value'.
     */
    public function column($name)
    {
        $this->column = $name;
    }

    /**
     * Load candidate names from an iterable source.
     */
    public function candidates($list, $name)
    {
        $this->candidates = [];
        $list->moveFirst();
        do {
            $this->candidates[] = trim($list->col($name));
        } while ($list->next());
    }

    /**
     * Add a single candidate name.
     */
    public function addCandidate($name)
    {
        $this->candidates[] = trim($name);
    }

    /**
     * Initialise — positions on the first matching row.
     * Call this before iterating.
     */
    public function extract()
    {
        $this->moveFirst();
    }
    
    /**
    * Default for a complete missmatch
    * if unset, it will throw an exception
    */
    public function setDefault($default)
    {
    	$this->default = $default;
    }
    
    /**
     * For the extraction column: return the matched candidate name.
     * For all other columns: delegate to upstream unchanged.
     */
    public function col($columnname)
    {
        if ($columnname === $this->column) return  $this->scanToMatch($this->rst->col($this->column));
        return $this->rst ? $this->rst->col($columnname) : null;
    }

    /**
     * Reset to the first matching row.
     */
    public function moveFirst()
    {
        if (!$this->rst) return false;
        return $this->rst->moveFirst();
    }

    public function next()
    {
        if (!$this->rst) return false;
        return $this->rst->next();
    }

    /**
     * Check $haystack for the first matching candidate.
     * Returns the candidate name, the default value, or throws on complete mismatch.
     */
    private function scanToMatch($haystack)
    {
        foreach ($this->candidates as $candidate) {
            if (stripos($haystack, $candidate) !== false)
                return $candidate;
        }
        return ($this->default ? $this->default : throw new Exception("needs a proper exception"));
    }

    public function &iter()             { return $this; }
    public function fields()            { return $this->rst ? $this->rst->fields() : []; }
    public function getAdditiveSource() { ; }
}
?>
