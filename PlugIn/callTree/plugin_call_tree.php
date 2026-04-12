<?PHP

/**
 * CallTree
 *
 * Routes execution to a named <tree> node driven by an incoming value —
 * typically the response from an AI classification step.
 *
 * Workflow:
 *   1. Receive the AI-selected tree name via set_list() then select(),
 *      or set it directly via setTarget().
 *   2. Call dispatch() to find and trigger the matching <tree> node.
 *
 * @title:       CallTree
 * @author:      Stefan Wegerhoff
 * @description: AI-driven tree dispatcher; selects and calls a <tree> node by name
 */

require_once("PlugIn/plugin_interface.php");

class CallTree extends plugin
{
    var $back    = null;   // System.Parser
    var $content = null;   // System.Content

    private $selected = null;    // AI-selected tree name
    private $column   = 'value'; // column to read from upstream rst
    private $listCommands = null; // list of valid names to call
    private $documentID = "@me";

    function __construct(/* System.Parser */ $back, /* System.FuncTree */ $tree,/* System.Content */ $content)
    {
        $this->back    = $back;
        $this->content = $content;
    }

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
     * Set which column from the upstream source holds the tree name.
     * Defaults to 'value'.
     */
    public function column($name)
    {
        $this->column = $name;
    }

    /**
     * Read and store the tree name from the upstream source.
     * Returns the selected value.
     */
    public function select()
    {
        if ($this->rst) {
            $this->selected = trim($this->rst->col($this->column));
        }
        return $this->selected;
    }

    /**
    * expect an ID refering to the document, it is destined to call
    *
    */
    public function callInDocument($id)
    {
        $this->documentID = $id;
    }
    
    /**
    * recieve a list of names, avalable for calling
    *
    */
    public function option_list($list, $name)
    {
    	// Todo needs a proper Exception in /classes/exceptions/ for $list != Interface_node ||  rst

    	$this->listCommands = [];
    	$list->moveFirst();
    	do {
    		$this->listCommands[] = trim($list->col($name));
    	} while ($list->next());

    }
    
    /**
     * Directly set the target tree name.
     */
    public function setTarget($name)
    {
        $this->listCommands[] = trim($name);
    }

    /**
     * Find the <tree> node whose name attribute matches the selected value
     * and trigger its dispatch (which loads and executes its src file).
     * Returns true on success, false if no matching node is found.
     */
    public function dispatch()
    {
    	
    	
    	
        //if (!$this->selected) return false;
        if (!$this->back)     return false;

        // ------------------------------------------
        // ------------------------------------------
        
        
        $listOfTagNames = [];
        if(!$this->back->change_URI($this->content->get_template($this->documentID))) // it needs a document for 
        	return false; // needs exception
        $parser = $this->back; 
        $parser->flash_result();
        $parser->seek_node('http://www.trscript.de/tree#tree');
        $trees = $parser->get_result();
        $parser->flash_result();

        foreach ($trees as $node) {
            $nodeName = $node->get_ns_attribute('http://www.trscript.de/tree#name');
            $listOfTagNames[$nodeName] = $node; 

        }

        $dispatched = false;
        $this->rst->moveFirst();
        do {
            	$res = $this->rst->col($this->column);
                $this->callElement($listOfTagNames[$res]);
                    $dispatched = true;
                
            
        } while ($this->rst->next());

        return $dispatched;
    }
    
    
    private function callElement($node)
    {
    	        $booh  = null;
                $Event = new EventObject('', $this, $booh);
                $type  = ["Identifire" => "*", "Command" => ["Name" => "start"]];
                $node->event_message_in($type, $Event);
    }
    

    /**
     * Expose the selected value, or delegate to upstream source.
     * Recognised column names for the local value: 'selected', 'value', 'name'.
     */
    public function col($columnname)
    {
        if ($this->rst)
            return $this->rst->col($columnname);
        else
        	return null;
    }

    public function &iter()             { return $this; }
    public function next()              { if ($this->rst) return $this->rst->next(); return false; }
    public function moveFirst()         { if ($this->rst) return $this->rst->moveFirst(); return false; }
    public function moveLast()          { if ($this->rst) return $this->rst->moveLast(); return false; }
    public function fields()            { if ($this->rst) return $this->rst->fields(); return []; }
    public function getAdditiveSource() { ; }
}
?>
