<?PHP

/**
*ContentGenerator
*
* Generates content by reading and writing trees
*
* @-------------------------------------------
* @title:XMLDO
* @autor:Stefan Wegerhoff
* @description: Treeobject, transforms trees to tables and back
* --------------------------------------------
* @function: MOVEFIRST = goes to first record
* @function: MOVELAST = goes to last record
*/

require_once("plugin_interface.php");
define("DEBUG",     false);

class XMLDO extends plugin 
{
private $machwasmit = 1;
private $test = 0;
protected $rst;
private $pos = 0;
private $template;
private $content;
private $treeObj;
private $documentForInsert;
private $verification = array();
private $level = array();
private $relation = array();
private $strucEl = array();
private $list = array();
private $emptyText = 'kein Datensatz';
private $config= array('attribOnNull' => true, 'actsAsCollector' => false);
private $testmode = false;
private $cdata;

private $cur_tree;
private $cur_id;

private $table = [];

private $template_json;

private $insertedLeafCount = 0;

	function __construct(/* System.Parser */ &$back, /* System.FuncTree */ &$tree,/* System.Content */ &$content)
	{ //throw new Exception('Division by zero.');
		$this->back= &$back;
		//$this->treepos = &$treepos;
		$this->content = &$content;
		$this->treeObj = $tree;
		//echo $eff->full_URI();
		//$this->id = $value; , &$id
		$this->testmode = DEBUG;
	}
	
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}


	/**
	* Loads a named template document and locates all nodes matching $use_at_tag.
	* Results land in $this->documentForInsert for later cloning. Restores parser
	* position afterwards.
	*
	* @param string $new_template  Template identifier (registered in content system)
	* @param string $use_at_tag    XPath / tag to locate clone roots within the template
	* @return string  "true" if xpath produced results, "false" if empty
	* @throws Exception  if template identifier is invalid
	*
	* @sideeffects $this->template_json, $this->template, $this->documentForInsert,
	*              $this->cur_tree, $this->cur_id
	* @called-by processSerialConfiguration()
	*/
	public function setXMLTemplate($new_template, $use_at_tag)
	{
		
		$this->template_json = "{\"new_template\" : \"$new_template\", \"use_at_tag\" : \"$use_at_tag\"}";

		// tesst ------------------------------------
		//var_dump($this->treeObj->get_EffBranch()->get_idx());
		//$this->back->change_idx($this->treeObj->get_EffBranch()->get_idx());
		$this->cur_tree = $this->treeObj->get_EffBranch();
		$this->cur_id = $this->cur_tree->get_idx();
		// --------------------------------------------
		
		$tmpstamp = $this->back->position_stamp();
		
		$this->template = $new_template;
		//echo $this->template . " " . $this->content->get_template($new_template) . " ";
		if(!$this->back->change_URI($this->content->get_template($new_template)))
		{
		///echo $new_template . ' isn\'t a available documentident (setXMLTemplate) ';
		throw new Exception("template " . $new_template . " isn't a valid identifer");
		}
		
		$this->back->set_first_node();
		
		$this->back->complete_list(true);
		$this->back->cloneResult(true);
		$this->back->xpath($use_at_tag);
		$this->back->cloneResult(false);
		$this->documentForInsert = $this->back->get_xpath_Result();
		
//echo " " . count($this->back->get_xpath_Result()) . " \n";
		/*
		$orginal = &$this->generator()->XMLlist->show_xmlelement();
		$clone = &$this->find($orginal,$has_tag);
		*/
		if(count($this->documentForInsert) == 0 )
		{
		$mytemp = "false";
		}
		else
		{
		$mytemp = "true";
		}	

		$this->back->go_to_stamp($tmpstamp);
		return $mytemp;
	}
	
	/**
	*@parameter: LIST = gets an object to receive data
	*/
	public function set_list(&$value)
	{
	
	if(is_object($value))
	{
		$this->rst = &$value;
	}
	else
	return 'no element received';
	
	//echo get_Class($this->rst) . 'xxxx';
	//return $this->back->get_context_generator()->get_template($new_template);
	//get_context_generator
	//	$this->template = $generator->heap['template'][$value];
	//	if(is_null($this->template))echo '<br><b>das Template ist nicht verf&uuml;gbar:' . $value . '</b><br>';
		
	}
	
	/**
	* Public entry point for JSON-based plugin configuration.
	* Decodes the JSON string and delegates to processSerialConfiguration().
	*
	* @param string $json  JSON with top-level key "serial"
	* @calls processSerialConfiguration()
	*/
	public function configuration($json)
	{
		// JsonException on malformed JSON is thrown by JSON_THROW_ON_ERROR
		$confi = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

		if(!array_key_exists("serial", $confi))
			throw new \RuntimeException("XMLDO configuration: JSON missing required top-level key 'serial'");

		$this->processSerialConfiguration($confi["serial"]);
		//$this->showConfiguration();
	}
	
	/**
	* Applies a "serial" config block in fixed order:
	*   1. "config"     → setEmptyCaseText(), setCDATAmode(), or $this->config[] directly
	*   2. "template"   → setXMLTemplate()
	*   3. "structure"  → setBranch() / setCrotch() per entry
	*   4. "definition" → define_tag() per entry
	*   5. "process"    → generateBranchTree() or generateTagTree()
	*
	* @param array $confi  Decoded "serial" section (from configuration())
	* @throws RuntimeException  if template xpath returns no results, or define_tag TypeError
	* @calls setEmptyCaseText(), setXMLTemplate(), setBranch(), setCrotch(),
	*        define_tag(), generateBranchTree(), generateTagTree()
	* @called-by configuration()
	*/
	private function processSerialConfiguration(array $confi)
	{
		if(array_key_exists("config",$confi))
			foreach ($confi["config"] as $function => $set)
			{
				//var_dump($set);
				
				switch ($function) {
		/*		case "attribOnNull":
					$this->config['attribOnNull'] = $set;
					break; */
				case "setCDATAmode":
					$this->cdata = true;
					break;
				case "setEmptyCaseText":
					$this->setEmptyCaseText($set);
					break;
				default:
					$this->config[$function] = $set;

				}
				
			}

		//if(array_key_exists("template",$confi))
		//if(array_key_exists("template",$confi))
		if(array_key_exists("template",$confi))
			$this->setXMLTemplate(...$confi["template"]);
		if(array_key_exists("structure",$confi))
		{
			foreach ($confi["structure"] as $command) {
				$type = $command["type"];
				unset($command["type"]);
				if($type == "Branch")
				
					$this->setBranch(...$command);
				
				else
					$this->setCrotch(...$command);
				}
			//var_dump($confi["structure"]);
		}
		
		if(array_key_exists("definition",$confi))  //define_tag
		{
			foreach ($confi["definition"] as $idx => $command) {
				try {
					$this->define_tag(...$command);
				} catch (\TypeError $e) {
					throw new \RuntimeException("XMLDO definition[$idx]: " . $e->getMessage(), 0, $e);
				}
			}
		}
		// "process" is optional — configuration can be applied in stages and the caller
		// may trigger generateBranchTree() / generateTagTree() separately afterwards.
		if(array_key_exists("process", $confi))
			switch ($confi["process"]) {
			case "Branch":
				$this->generateBranchTree();
				break;
			case "Tag":
				$this->generateTagTree();
				break;
			default:
				throw new \RuntimeException("XMLDO processSerialConfiguration: unknown process type '" . $confi["process"] . "' — expected 'Branch' or 'Tag'");
			}

	}
	

	/**
	* switch for collecting data out from a document
	*/
	public function actsAsCollector($bool)
	{
		$this->config['actsAsCollector'] = $bool;
	}
	
	/**
	* COLLECTION
	*
	*/
	public function setEmptyCaseText($text)
	{
		$this->config['setEmptyCaseText'] = $text;
		$this->collectionemptyText = $text;
	}
	
	/**
	* CDATA
	*
	*/
	public function setCDATAmode()
	{
			$this->config['setCDATAmode'] = true;
			$this->cdata = true;
			
			
	}
	
	public function ignoreNull($on)
	{
		
		//if($on == 'data')$this->config[''];
		if(strtolower($on) == 'attribute')
		{$this->config['attribOnNull'] = false;
		
		}
		
	}
	/**
	* Registers one column/field mapping in $this->verification[].
	* Each entry describes which DB column (name), where in the template (xpath),
	* whether to write as 'data' or 'attrib', an optional constant (value),
	* a group name, and a branch index.
	*
	* @param string      $name        DB column name (key into rst->col())
	* @param string      $xpath       XPath into the cloned template node ("." = self)
	* @param string|null $attrib_data 'data' | 'attrib'
	* @param int|null    $pos         (unused/legacy)
	* @param string|null $prefix      Namespace prefix for attribute
	* @param string|null $postfix     Attribute local name
	* @param mixed|null  $value       Constant override (skips DB column when set)
	* @param string|null $group       Group name (links to setBranch/setCrotch)
	* @param int|null    $branch      (unused/legacy branch index)
	* @param bool        $allowEmpty  If false, throws when xpath returns nothing (default: false)
	* @called-by processSerialConfiguration()
	*/
	public function define_tag($name, $xpath, $attrib_data = null, $pos = null, $prefix = null, $postfix = null, $value = null, $group = null, $branch = null, $allowEmpty = false)
	{	

		$cur_pos = count($this->verification);
		$this->verification[$cur_pos] = array();
		$this->verification[$cur_pos]['name'] = $name; /* name to call*/
		$this->verification[$cur_pos]['xpath'] = $xpath; /* pos in document */
		$this->verification[$cur_pos]['attrib_data'] = $attrib_data; /* data or attribute */
		$this->verification[$cur_pos]['pos'] = intval($pos);
		$this->verification[$cur_pos]['prefix'] = $prefix; /* prefix like dc(:autor) */
		$this->verification[$cur_pos]['postfix'] = $postfix; /* postfix like (dc:)autor */
		$this->verification[$cur_pos]['value'] = $value;       /* constant value */
		$this->verification[$cur_pos]['group'] = $group;       /* to concern several columns into groups */
		$this->verification[$cur_pos]['branch'] = intval($branch);  /* number of the child at the rootnode */
		$this->verification[$cur_pos]['allowEmpty'] = $allowEmpty;
	}
	
	

		/*
		if($type == "COL")
		{
			
			if(is_null($this->table))echo '<br><b>Keine Tabelle erstellt!</b><br>';
			$tmp=$this->table[$this->pos][$value];
			$this->param_out($tmp);
			//echo "<br><b>" . $value . '</b> ' . $tmp . ' <i>' . $this->pos . '</i>';
		}
		*/
		
		public function getMany()
		{
		if($type == "MANY"){$this->param_out(count( $this->table ));}
		}
		
		
		/*
		if($type == "ERR")
			{
				$tmp = $generator->XMLlist->error_num();
			$this->param_out($tmp );
			}
		
		if($type == "ERRDESC"){$this->param_out($generator->XMLlist->error_desc() );}
		*/

	public function setTestmode($bool){	$this->testmode = $bool;	}
	
	/**
	* Debug dump: prints verification columns and all rows from $this->rst.
	* Only active when $this->testmode is true (→ setTestmode()).
	*
	* @called-by generateBranchTree(), generateTagTree()
	*/
	private function show_content()
	{
		if(!$this->testmode)return;
		$length = 20;
		$tmp ="";
		//$length = max($length, str_len());
		
		foreach ($this->verification as $value)
			echo str_pad($value['name'], 30, " ", STR_PAD_RIGHT);
			//echo "this is ($key)" . $value['name'] . "="  .  $this->rst->col($value['name']) . " \n";
			echo "\n";
		
		if(!is_null($this->rst) && $this->rst->moveFirst())
		{
			
		do{
		//str_pad($str, 21, ":-)", STR_PAD_BOTH);
			foreach ($this->verification as $key => $value) 
				{
					$tmp = $this->rst->col($value['name']);
					
				echo str_pad("$tmp(" . gettype($tmp) . ")" , 30, " ", STR_PAD_RIGHT);

				}
				echo "\n";
			}while($this->rst->next());
		}
	}
		
		
	/**
	* Registers a "fork" level in $this->level[] (type=0).
	* A Crotch navigates INTO an existing node (doesn't clone a new branch).
	*
	* @param int    $child_pos   Index into $this->list[] (template child nodes)
	* @param string $xpath       XPath to the target node within the crotch element
	* @param string $group       Group name(s), comma-separated; links to define_tag() entries
	* @param bool   $allowEmpty  If true, an empty xpath result is silently accepted (default: true)
	* @called-by processSerialConfiguration()
	* @see setBranch() for type=1 (clones a new node instead)
	*/
	public function setCrotch( $child_pos, $xpath, $group, $allowEmpty = true)
	{

	  $this->level[] = array('type'=> 0, 'child_pos' => intval($child_pos),  'xpath' => $xpath, 'group' => $group, 'allowEmpty' => $allowEmpty);

	}
	
	/**
	* Registers a "branch" level in $this->level[] (type=1).
	* A Branch clones a template child node and appends it to the document.
	*
	* @param int    $child_pos   Index into $this->list[] (template child nodes)
	* @param string $xpath       XPath to the target node inside the cloned element
	* @param string $group       Group name(s), comma-separated; links to define_tag() entries
	* @param bool   $allowEmpty  If true, an empty xpath result is silently accepted (default: true)
	* @called-by processSerialConfiguration()
	* @see setCrotch() for type=0 (navigates into existing node instead)
	*/
	public function setBranch($child_pos, $xpath, $group, $allowEmpty = true)
	{

	  $this->level[] = array('type'=> 1, 'child_pos' => intval($child_pos),  'xpath' => $xpath, 'group' => $group, 'allowEmpty' => $allowEmpty);
	}
	 
	public function setRelation($lvl, $group, $condition )
	{
	$this->relation[] = array('level' => intval($lvl), 'group' => $group, 'condition' => $condition ); 
	}


// TODO Macht das was? Es sieht so unfertig aus
	private function deepRoot(&$finish, &$groups, &$node, $deep = 0)
	{
	$param_node = &$this->list[$this->level[$deep]['child_pos']]->cloning($node);

	}
	


	/**
	* Initializes $tbl as an associative array: one null entry per verification
	* column, keyed by the column's 'name' field.
	*
	* @param array $tbl  Output array (by ref)
	* @called-by generateBranchTree(), collectData()
	*/
	private function create_tbl(&$tbl)
	{
			for($i = 0; count($this->verification) > $i; $i++ )
			{
				$tbl[$this->verification[$i]['name']] = null;
			}
	}
	
	/**
	* Reads the current DB row from $this->rst into $tbl (updates all entries).
	* Returns the name of the last column that changed.
	*
	* @param array $tbl  Column map to update (by ref)
	* @return string     Key of the last changed column
	* @requires $this->rst  DB datasource (set by set_list())
	* @called-by generateBranchTree()
	*/
	private function test_alter(&$tbl)
	{
		$tmp = 0;
		$res = '';
		$bool = true;
		  if(is_array($tbl))     
		  	  foreach($tbl as $key => $value){
		  	  	  if($res == '')$res = $key;
		  	  	$tmp = $this->rst->col( $key);
				if($bool )$res = $key;
				
				if(!is_null($tmp) && !is_null($value))
				$bool = $bool && $bool = (!is_null($tmp) || !is_null($value)) && (strcmp($tmp, $value) == 0);
				else
					$bool = false;
				
				
				$tbl[$key] = $tmp;
		  	  	  
			  }	
		  return $res;
	}
/*
	private function &buildCorrelation($setDef, $setGroups, $setlevel)
	{
	$res
	
	}
*/	

	private function permutate(&$array, $permut )
	{
	}
	
	/**
	* Returns a shallow copy of $tbl (snapshot of the current row).
	*
	* @param array $tbl  Source row
	* @return array      Copied row
	* @called-by generateBranchTree()
	*/
	private function &buildRow($tbl)
	{
		$res = array();
		  if(is_array($tbl))     
		  	  foreach($tbl as $key => $value)
		  	  	$res[$key] = $value;
		  	  	//$res[$key] = $this->rst->col( $key);			  
		  return $res;
	}
	
	/**
	* Returns the key of the first null entry in $tbl, or "" if all are set.
	* Used to determine how far into the current row data has been filled.
	*
	* @param array $tbl  Column map
	* @return string     Key of first null entry, or ""
	* @called-by generateBranchTree()
	*/
	private function last_col(&$tbl)
	{

		$tmp = '';
		  if(is_array($tbl))     
		  	  foreach($tbl as $key => $value){

				if($value == null)return $key;
				//$tmp = $key;
		  	  	  
			  }	
		  return "";
	}
	
	/**
	* Clones $Root via $Node and navigates via $xpath to the result.
	* Legacy / appears unused — no callers found in this file.
	*/
	private function &build_root($xpath, &$Node,  &$Root )
	{
			if($this->back->freexpath($xpath, $Node->cloning($Root)) != 0)
                	{
	
                		$obj_ref_array = &$this->back->get_xpath_Result();
                		
                		$this->back->free_xpath_Result();
                		$res =  &$obj_ref_array[0];
                		unset($obj_ref_array);
                		return $res;
                	}
                	else
                	{
                		$fail = "fail";
                		$Root->setdata($fail,0);
                		echo "bad xpath: '$xpath' (build root)\n";
                		return $Root;
                	}
    }
    
    /**
	* Runs $xpath on $Root and clones the deepest-level result into $Node.
	* If $last=false, takes first result instead of deepest.
	* Legacy / appears unused — no callers found in this file.
	*/
    private function &build_element($xpath, &$Node,  &$Root, $last = true)
	{
		//$in = "in";
		//$Root->setdata($in,0);
		
			if($this->back->freexpath($xpath, $Root) != 0)
                	{

                		$obj_ref_array = &$this->back->get_xpath_Result();

                		$this->back->free_xpath_Result();
                		$pos = 0;
                		$cur_deep = substr_count($obj_ref_array[0]->position_stamp(),'.');
                		for($i = 0; $i < count($obj_ref_array); $i++)
                			{
                				//echo $obj_ref_array[$i]->position_stamp() . " " . substr_count($obj_ref_array[$i]->position_stamp(),'.') . "\n";
                				
                				if(substr_count($obj_ref_array[$i]->position_stamp(),'.') == $cur_deep)$pos = $i;
                			}
                		//echo "$pos\n\n";
                		//$out = "out";
                		//$obj_ref_array[$pos]->setdata($out,0);
                		if($last)
                			$res =  $Node->cloning($obj_ref_array[$pos]);
                		else
                			$res =  $Node->cloning($obj_ref_array[0]);
                		unset($obj_ref_array);
                		return $res;
                	}
                	else
                	{
                		$fail = "fail";
                		$Root->setdata($fail,0);
                		echo "bad xpath: '$xpath' (" . $Root->position_stamp() . ")\n";
                		return $Root;
                	}
    }
    
  /**
  * Returns the group name for a given tree level, looked up from $this->relation.
  *
  * @param int   $lvl  Tree level
  * @param array $tbl  (unused — kept for API consistency with older callers)
  * @return string|null  Group name, or null if no relation entry for this level
  * @requires $this->relation  (populated by setRelation() or auto-filled in createGroupArray())
  * @called-by processingBox(), generateBranchTree(), hasNextlvl()
  */
    private function gotoGroup($lvl, &$tbl)
    {
    	$res = null;
    	         foreach ($this->relation as $key => $value) 
		{		
			if($value['level'] ==$lvl)$res = $value['group']; 

		}
		
    	return $res;
    }
    
    /**
	* Computes the remainder of $box1 after removing $without from one end.
	* Returns null if there is no remainder or the ranges don't overlap.
	*
	* @param array $box1     [start, end] outer range
	* @param array $without  [start, end] inner range to subtract
	* @return array|null  Remainder range, or null
	* @called-by processingBox()
	*/
    private function boxWithout($box1, $without)
    {
    	$res = null; 
    	if($box1[0] > $without[0] || $box1[1] < $without[1]) return null;
    	if($box1[0] == $without[0] && $box1[1] > $without[1])
    		$res = array($without[1] + 1, $box1[1]);    	
    	if($box1[0] < $without[0] && $box1[1] == $without[1])
    		$res = array($box1[1], $without[0] - 1, );    	

    	return $res; 
    	
    	
    }

    /**
	* Narrows $range to a contiguous block where all $groups values stay identical
	* to the first row. Returns null if the first row has no data for this group.
	*
	* @param array $range   [start, end] candidate row range into $tbl
	* @param array $tbl     Full result table (fulltbl from generateBranchTree)
	* @param array $groups  Data definitions for the group to check (groups[name]['data'])
	* @return array|null    [start, end] of the matching block, or null
	* @called-by processingBox(), hasNextlvl()
	*/
    private function defineBlock( $range, &$tbl, &$groups)
    {

    	//echo "  for tbl and groups \n";
    	$has_entries = false;
    	$res = $range;
    	$res[1] = $res[0];
    	
   		  foreach ($groups as  $value)
   			{
    			$has_entries = !is_null($tbl[$range[0]][$value['name']]);
    			if($has_entries)break;

    	  	}
    		if(!$has_entries)
    		{
    			//echo "raus";
    			return null;
    		}
    	
    	
        for($i = $range[0] + 1 ; $i <= $range[1]; $i++)
    	{
    		$has_entries = false;
    		  foreach ($groups as  $value)
    		{
    			$has_entries = !$has_entries || !is_null($tbl[$i - 1][$value['name']]);
    			if(!is_null($tbl[$i - 1][$value['name']]) && !is_null($tbl[$i][$value['name']]))
    				if(strcmp($tbl[$i - 1][$value['name']], $tbl[$i][$value['name']]) <> 0)return $res;
    		}
    		
    		if(!$has_entries)return null;
    		
    		$res[1] = $i;

    	}
    	
    	return $res;
    }
    
    /**
	* Writes one row of data into the current branch node for all definitions in $groups.
	* Uses $value['value'] constant when set; otherwise reads from $tbl[$range[0]].
	* Dispatches to setData() for 'data' type, setAttrib() for 'attrib' type.
	*
	* @param array $range   [start, end] — only $range[0] is read
	* @param int   $lvl     Current level (debug indent only)
	* @param array $tbl     Full result table
	* @param array $groups  Current group's data definitions (groups[name]['data'])
	* @calls setData(), setAttrib()
	* @called-by processingBox()
	*/
    private function writeData($range, $lvl, &$tbl, &$groups)
    {
    
    		   			foreach ($groups as  $value)
    				 	 {
    				 	 	 if(DEBUG)echo str_repeat("   ", $lvl) . " [" . $value['name'] . "]={'value'=" .  $tbl[$range[0]][$value['name']] . ", 'xpath'=" .  $value['xpath'] . ", 'type'=" .  $value['attrib_data'] . "}\n";
    				 	 	 
    				 	 	 $data_val = !is_null($value['value']) ? $value['value'] : $tbl[$range[0]][$value['name']];
    				 	 	 if(strcmp($value['attrib_data'], 'data') == 0)
    				 	 	 {

    				 	 	 	 $this->setData( $value['xpath'], $data_val);
    				 	 	 }
    				 	 	 else
    				 	 	 {
    				 	 	 	 //var_dump($tbl[$range[0]][$value['name']]);
    				 	 	 	if(!is_null($data_val) || $this->config['attribOnNull'] )
    				 	 	 	{
    				 	 	 		//echo str_pad($value['name'],40," ") . ":";
    				 	 	 	//var_dump($tbl[$range[0]][$value['name']]); showDocumentsNamespaces()
    				 	 	 	$this->setAttrib( $value['xpath'],
    				 	 	 		$value['prefix'] ,
    				 	 	 		$value['postfix'], $data_val);
    				 	 	 	}

    				 	 	}
    				 	 }
    				 	 
    
    }
    
    
    /**
	* Checks whether the next sub-level (lvl+1) has any data rows in the current $box.
	*
	* @param array $tbl     Full result table
	* @param array $groups  Group index (from createGroupArray)
	* @param array $box     [start, end] current row range
	* @param int   $lvl     Current level (checks lvl+1)
	* @return bool
	* @calls gotoGroup(), defineBlock()
	* @called-by processingBox(), generateBranchTree()
	*/
    private function hasNextlvl($tbl, &$groups, $box, $lvl)
    {
    	 $lookfor = $this->gotoGroup($lvl + 1, $tbl);

    	//set new box
    	if(is_null($lookfor))return false;
    	//var_dump($groups[$lookfor]['data']);
    	    	 //echo " $lookfor ----------\n";
    	$newbox = $this->defineBlock( $box,  $tbl , $groups[$lookfor]['data']);
    	
    	return  !is_null($newbox);
    }
    
    /**
	* Recursive box processor — the core of the write pipeline.
	* For the row range $box, finds the matching group, clones the branch into the
	* document, writes column data, recurses into sub-levels, then processes the
	* remainder of the box at the same level.
	*
	* Flow per call:
	*   gotoGroup()      — find group name for this level
	*   defineBlock()    — narrow box to contiguous block for this group
	*   boxWithout()     — compute remainder box (rows after the block)
	*   buildBranch()    — clone branch template node into document
	*   writeData()      — write DB values into the cloned node
	*   buildCrotch()    — navigate to the sub-crotch
	*   processingBox()  [recursive, lvl+1]  — process children
	*   processingBox()  [recursive, same lvl] — process remainder
	*
	* @param array $tbl    Full result table (rows × columns)
	* @param array $groups Group index (from createGroupArray)
	* @param array $box    [start, end] row range in $tbl
	* @param int   $lvl    Current recursion depth / tree level
	* @calls gotoGroup(), defineBlock(), boxWithout(), hasNextlvl(),
	*        buildBranch(), writeData(), buildCrotch(), processingBox()
	* @called-by generateBranchTree(), processingBox()
	*/
    private function processingBox(&$tbl, &$groups, $box, $lvl)
    {
    	//var_dump($box);
    	// results current groupname
    	$subBranch = &$this->back->show_xmlelement();
    	if(is_null($lookfor = $this->gotoGroup($lvl, $tbl)))
    		{
    			if(DEBUG)
    			{
    				echo "processingBox: gotoGroup(lvl=$lvl) returned null — no relation entry for this level.\n";
    				echo "  available groups: " . implode(', ', array_keys($groups)) . "\n";
    				echo "  relation table: ";
    				foreach($this->relation as $r) echo "[lvl=" . $r['level'] . " → " . $r['group'] . "] ";
    				echo "\n";
    			}
    			return;
    		}
    	//set new box
    	$newbox = $this->defineBlock( $box,  $tbl , $groups[$lookfor]['data']);
    	$nextbox = $this->boxWithout( $box, $newbox);
    	$hasSubLvl = $this->hasNextlvl($tbl, $groups, $box, $lvl);
    	//var_dump($newbox);
    	//var_dump($nextbox);

    	
    	
    	if(!is_null($newbox))
    	{
    	if(DEBUG)echo str_repeat("   ", $lvl) . "Box[" . $newbox[0] . ", " . $newbox[1] . "]{\n";
    	

    	
    	//if(!$cur_root)echo "=====================================Fehler, die Astgabel der Gruppe $lookfor im level $lvl exitistiert nicht===========================================\n";
   
    	unset($run_branch);
    	
    	//$this->back->show_xmlelement()->giveOutOverview();
    	$run_branch = &$this->buildBranch($groups, $lookfor);
    	//$this->back->show_xmlelement()->giveOutOverview();
    	
    	
    	$this->writeData( $box, $lvl, $tbl , $groups[$lookfor]['data']);
    	
    	$this->back->set_xmlelement($run_branch);
    	
    	if($hasSubLvl)
    	$cur_crotch = &$this->buildCrotch($groups, $this->gotoGroup($lvl + 1, $tbl));
    	else
    	{
    		$this->insertedLeafCount++;  // leaf branch: one entry written per block
    		$cur_crotch = &$run_branch;
    	}
    	
    	$this->back->set_xmlelement($cur_crotch);
    	$this->processingBox($tbl, $groups, $newbox, $lvl + 1);
    	$this->back->set_xmlelement($subBranch);
    	
    	if(DEBUG)echo str_repeat("   ", $lvl) . "}\n";
    	
    	
    	
    	
    	if(!is_null($nextbox))
    		$this->processingBox($tbl, $groups, $nextbox, $lvl);
    	}
    	
    	//$this->back->set_xmlelement($subBranch);
    	
    }
    
    /**
	* Navigates to the crotch (fork) node for the given group.
	* If the group has a crotch configured: appends that template child node
	* ($this->list[$i]) and navigates via its xpath.
	* If no crotch configured: returns the current element unchanged (write directly).
	*
	* @param array  $groups  Group index (from createGroupArray)
	* @param string $name    Group name whose 'crotch' entry to use
	* @return xml_element    Reference to the crotch node (or current element)
	* @called-by processingBox(), generateBranchTree()
	*/
    private function &buildCrotch(&$groups, $name)
    {
    	
    	if(!is_null($name) && $groups[$name]['crotch'])
    	{
    		if(DEBUG)echo "// build fork for group " . $name  . "\n";
    		//echo $this->back->get_URI() + '(' +  $this->back->position_stamp() . ") startposition \n";
    	//$this->back->show_xmlelement()->giveOutOverview();
    		$i = $groups[$name]['crotch']['child_pos'];
    		$this->back->append_xmlelement($this->list[$i]);
    		//$this->cur_tree = $this->list[$i]->cloning($this->cur_tree);
    		//echo $this->back->get_URI() + '(' +  $this->back->position_stamp() . ") neues Baumelement \n";
    		$this->back->complete_list(false);
    		
    		$res = &$this->back->xpath($groups[$name]['crotch']['xpath']);
    		if(!$res)
    			throw new \RuntimeException("XMLDO: buildCrotch — xpath '" . $groups[$name]['crotch']['xpath'] . "' not found in group '$name' (position: " . $this->back->position_stamp() . ")");
    		$this->back->set_xmlelement($res);
    			$this->back->complete_list(true);
    		//$this->back->overview_xpath_Result();
    		//$obj_ref_array = &$this->back->get_xpath_Result();
    		$this->back->free_xpath_Result();
    		
//    		  echo $this->back->position_stamp() . " arbeitszweig  \n";
    		//$this->back->set_xmlelement($obj_ref_array[0]);
    		
    	//var_dump($groups[$name]['crotch']);
    	return $res ;
    	}
   		if(DEBUG)echo "// create direct in branch for group " . $name  . "\n";
    	
    	return $this->back->show_xmlelement();
    }
	
    /**
	* Appends the branch template node ($this->list[$i]) to the document and
	* navigates via xpath to the target element inside it.
	* Unlike buildCrotch(), this always clones a new node — one branch per DB row group.
	*
	* @param array  $groups  Group index (from createGroupArray)
	* @param string $name    Group name whose 'branch' entry to use
	* @return xml_element    Reference to the target element inside the new branch
	* @throws RuntimeException  if group missing, branch not configured, or xpath returns nothing
	* @called-by processingBox()
	*/
    private function &buildBranch(&$groups, $name)
    {
    	if(!isset($groups[$name]))
    		throw new \RuntimeException("XMLDO: buildBranch — group '$name' does not exist (available: " . implode(', ', array_keys($groups)) . ")");
    	if(!$groups[$name]['branch'])
    		throw new \RuntimeException("XMLDO: buildBranch — group '$name' has no branch configuration");

    	$i = $groups[$name]['branch']['child_pos'];
    	$res = $this->back->append_xmlelement($this->list[$i]);

    	$this->back->complete_list(false);
    	$res = &$this->back->xpath($groups[$name]['branch']['xpath']);
    	$this->back->complete_list(true);
    	$this->back->free_xpath_Result();

    	if(!$res)
    		throw new \RuntimeException("XMLDO: buildBranch — xpath '" . $groups[$name]['branch']['xpath'] . "' not found in group '$name' (position: " . $this->back->position_stamp() . ")");

    	return $res;
    }
    
    	/**
	* Navigates to $xpath from the current position and writes $value as CDATA.
	* Saves and restores the current element; throws on xpath miss.
	* Note: "." as xpath = self-reference (the current node itself).
	*
	* @param string $xpath  XPath from current position ("." = self)
	* @param mixed  $value  Data value to write
	* @throws RuntimeException  if xpath not found
	* @called-by writeData(), generateTagTree()
	*/
    	private function setData( $xpath, $value)
	{
		$backjump = &$this->back->show_xmlelement();

    		$this->back->complete_list(false);
    		$res = &$this->back->xpath($xpath);
    		$this->back->complete_list(true);
    		$this->back->free_xpath_Result();

    		if(!$res)
    		{
    			$this->back->set_xmlelement($backjump);
    			throw new \RuntimeException("XMLDO: setData — xpath '$xpath' not found at " . $this->back->position_stamp());
    		}

    		$this->back->set_xmlelement($res);
    		$this->back->set_node_cdata($value);
    		$this->back->set_xmlelement($backjump);
	}
    
    	/**
	* Navigates to $xpath from the current position and sets an attribute.
	* Saves and restores the current element; throws on xpath miss.
	* "." as xpath = self-reference.
	* When $prefix is null, sets a bare (unnamespaced) attribute.
	* When $prefix is a short namespace prefix (e.g. "rdf"), resolves it via the
	* document's xmlns declarations, creates a typed attribute object from the
	* namespace framework, and registers it with the correct prefix:local name
	* using get_Prefix() on the parser index — same pattern as generateTagTree().
	*
	* @param string $xpath   XPath from current position ("." = self)
	* @param string|null $prefix  Short namespace prefix (e.g. "rdf"), or null for bare attribute
	* @param string $attrib  Attribute local name
	* @param mixed  $value   Attribute value
	* @throws RuntimeException  if xpath not found or prefix unknown
	* @called-by writeData()
	*/
    	private function setAttrib( $xpath, $prefix, $attrib, $value)
	{
		$backjump = &$this->back->show_xmlelement();

    		$this->back->complete_list(false);
    		$res = &$this->back->xpath($xpath);
    		$this->back->complete_list(true);
    		$this->back->free_xpath_Result();

    		if(!$res)
    		{
    			$this->back->set_xmlelement($backjump);
    			throw new \RuntimeException("XMLDO: setAttrib — xpath '$xpath' not found at " . $this->back->position_stamp() . " (attrib: $prefix:$attrib)");
    		}

    		$this->back->set_xmlelement($res);

    		if(is_null($prefix))
    		{
    			$this->back->set_node_attrib($attrib, $value);
    		}
    		else
    		{
    			$nsMap = $res->showDocumentsNamespaces();
    			if(!isset($nsMap[$prefix]))
    				throw new \RuntimeException("XMLDO: setAttrib — unknown prefix '$prefix' (attrib: $prefix:$attrib)");

    			$fullURI = $nsMap[$prefix];
    			$attrib_obj = &$this->back->get_Object_of_Namespace($fullURI . '#' . $attrib);
    			$attrib_obj->setdata($value, 0);

    			$shortPrefix = $this->back->get_Prefix($fullURI, $res->get_idx());
    			$attrib_obj->name = (strlen((string)$shortPrefix) > 0)
    				? $shortPrefix . ':' . $attrib
    				: $attrib;

    			$res->attribute($attrib_obj->name, $attrib_obj);
    		}

    		$this->back->set_xmlelement($backjump);
	}
    
	public function showConfiguration()
	{
		$structure = [];
		$definition = [];
		$config = [];
		$process = $this->config['setTreeType'];


		foreach ($this->config as $key => $value) 
		{		
			if(is_bool($value))
			$config[] = "\"$key\" : " . ($value? "true": "false");
			elseif(is_int($value))
			$config[] = "\"$key\" : $value";
			else
			$config[] = "\"$key\" : \"$value\"";



		}
		
		foreach ($this->verification as $asso){
		$one_line = [];		
		foreach ($asso as $key => $value) 
		{		
			if(!is_null($value))
			if(is_numeric($value))
			$one_line[] = "\"$key\" : $value";
			else
			$one_line[] = "\"$key\" : \"$value\"";


		}
			$definition[] = "{" . implode(", ", $one_line) . " }";
		}
		//$structure[] = "{ " . implode(",\n", $one_line) . " }";

		
		foreach ($this->level as $asso){
		$one_line = [];		
		foreach ($asso as $key => $value) 
		{		
			
			if($key == 'type')
			$one_line[] = "\"$key\" : " . ($value == 0? "\"Crutch\"": "\"Branch\"");
			elseif(is_int($value))
			$one_line[] = "\"$key\" : $value";
			else
			$one_line[] = "\"$key\" : \"$value\"";


		}
			$structure[] = "{" . implode(", ", $one_line) . " }";
		}		
		
		//$this->level[] = array('type'=> 1, 'child_pos' => intval($child_pos),  'xpath' => $xpath, 'group' => $group);
		
	$res = '{"serial" :{
	"config":{
	' . implode(",\n	", $config) . '
	},
	"template":' . $this->template_json . ",\n";
	
	if($process == "Branch")
	$res .= '	"structure":[
	' . implode(",\n	", $structure) . '
	],'. "\n";
	
	$res .= '	"definition":[
	' . implode(",\n	", $definition) . '
	],
	"process":"' . $process . '"
	}}';

	
	echo $res;
	
	}
	
	/**
	* Builds the $groups index from $verification, $relation, and $level arrays.
	*
	* Each $groups entry: ['crotch' => level-entry|null, 'branch' => level-entry|null, 'data' => [...definitions]]
	* - Reads group names from $verification entries (define_tag's 'group' field)
	* - If $relation is empty, auto-generates one level-entry per group in order
	* - Distributes $verification entries into groups['data'] (bucket sort)
	* - Assigns $level entries (from setBranch/setCrotch) to groups['crotch'/'branch']
	*
	* @param array $verification  Column definitions (from define_tag())
	* @param array $relation      Level→group mapping (from setRelation(); auto-filled if empty)
	* @param array $level         Branch/Crotch entries (from setBranch()/setCrotch())
	* @return array  [$groups, $permutation]
	* @throws RuntimeException  if a structure entry references an unknown group, or a branch is duplicated
	* @called-by generateBranchTree(), collectData()
	*/
	private function createGroupArray(&$verification, &$relation, &$level)
	{
		/* ------------------------------------------ groups ------------------------------------------------------*/
		$groups = array();
		/* -----------------------------------------------------------------------------------------------------------*/
		//$groups[$hold] = array(0);
		$permutation= array();

		
		// if there is no relation entry, one new will generated, based on order of Verification

		
		$tmp = array();
		$iter = -1;
		
		
		foreach ($verification as $key => $value) 
		{		
			
			if(!in_array($value['group'], $tmp)) 
			{
				$tmp[] =$value['group']; // uses verifications to create a list tmp of all group names
				$groups[$value['group']] = array('crotch'=>null, 'branch'=>null, 'data'=>array()); // creates an empty list for every group
			}

		}
		// in case $relation is empty, It will be filled with a level, a group name and an empty condition
		if(!count($relation))		
			foreach ($tmp as $key => $value) $relation[++$iter] = array('level' => $iter, 'group' => $value, 'condition' => "" );
		unset($tmp);
				
		// creates a list of permutations as a list of group names in relation to its verification entry
		for($i = 0; count($verification) > $i; $i++ )$permutation[$verification[$i ]['group']][] = $verification[$i ] ;

		//var_dump($permutation);
		
		$names[$verification[0]['name']]  = $verification[0]['group'];
		$hold = $verification[0]['group'];
		for($i = 1; count($verification) > $i; $i++ )
		{
			$names[$verification[$i]['name']] = $verification[$i]['group'];

			if($hold != $verification[$i]['group'])
			{
			  $hold = $verification[$i]['group'];

			}

		}	
		//ToDo for only on element in group


		$empty = $verification[count($verification) - 1 ]['group'];
		if(is_numeric($empty))
		$names[''] = $empty + 1;  
		else
		$names[''] = 1;

		asort($names);

		
/* ----------------------------------------------------------------------------------------------------------------------------
* Bucketsort
*
* -----------------------------------------------------------------------------------------------------------------------------
*/
		// Verification
		foreach ($verification as $key => $value) 
		{		
		
			$groups[$value['group']]['data'][] = &$verification[$key];

		}
		
		//level
		foreach ($level as $key => $value) 
		{		

			$tmp = explode(',', $value['group']);
			$type = array('crotch', 'branch');
			
				foreach ($tmp as $value2)
					{

							if(is_null($groups[trim($value2)]))
								throw new \RuntimeException("XMLDO: structure references unknown group '" . trim($value2) . "' (available: " . implode(', ', array_keys($groups)) . ")");

							if(is_null($groups[trim($value2)]['branch']))
							$groups[trim($value2)][$type[$value['type']]] = &$this->level[$key];
							else
							{
								throw new \RuntimeException("XMLDO: group '" . trim($value2) . "' already has a branch assigned — duplicate structure entry");
							}
					}
					



		}
		
		
		return [$groups, $permutation];
	}
	
	/**
	* Main write pipeline — reads rows from $this->rst and writes them into the
	* XML template document, respecting the group/branch/crotch hierarchy.
	*
	* Flow:
	*   createGroupArray()         — build group/branch/crotch index from verification+level+relation
	*   create_tbl()               — init empty row template
	*   [loop over rst rows via next()]:
	*     test_alter()             — fill tbl from current DB row
	*     last_col()               — find first null column (boundary detection)
	*     buildRow()               — snapshot row into fulltbl
	*   buildCrotch()              — navigate to top-level crotch node in document
	*   processingBox()            — recursive: clone branches, write data, recurse
	*
	* If $this->config['actsAsCollector'] is true → delegates to collectData() instead.
	*
	* @throws RuntimeException  if no definition entries, or template not found
	* @requires $this->rst          DB datasource (set by set_list()); null = static data
	* @requires $this->template     Template name (set by setXMLTemplate())
	* @requires $this->verification Column definitions (set by define_tag())
	* @requires $this->level        Branch/Crotch structure (set by setBranch()/setCrotch())
	* @calls createGroupArray(), create_tbl(), show_content(), test_alter(), last_col(),
	*        buildRow(), hasNextlvl(), buildCrotch(), gotoGroup(), processingBox(),
	*        collectData() [if actsAsCollector]
	* @called-by processSerialConfiguration()
	*/
	public function generateBranchTree()
	{

		/* check verification */
		if(count($this->verification) == 0)
			throw new \RuntimeException("XMLDO: no definition entries — use define_tag() or add a 'definition' section to the configuration");
		
		// case of XMLDO as a collector
		if($this->config['actsAsCollector'])
		{
			$this->collectData();
			return;
		}
		
		// is for debug
		$this->show_content();
		$this->config['setTreeType'] = "Branch";
		
		/*moveFirst*/
		if(!is_null($this->rst) && !$this->rst->moveFirst())
			throw new \RuntimeException("XMLDO generateBranchTree: rst->moveFirst() returned false — datasource contains 0 records (check DBO query and connection)");
		

		/* --------------------------------- check id ---------------------------- */
		
		/* ----------------------------------------------------------------------- */
		
		
		/* create an index of all grouppositions */
		// TODO check whole consistence
		
		//$hold = $this->verification[0]['group'];
		/* ------------------------------------------ groups ------------------------------------------------------*/
		$groups = array();
		$permutation= array();
		/* -----------------------------------------------------------------------------------------------------------*/


$tmp = $this->createGroupArray($this->verification, $this->relation, $this->level);
$groups = $tmp[0];
$permutation = $tmp[1];

//var_dump($names);
		//TODO bugfix Wenn sich der zweite Eintrag einer gruppe geaendert hat, stimmt die gruppe nicht
		$tbl = array();
		$fulltbl = array();

		$this->create_tbl($tbl);
		

		// is it important?
		//$this->rst->moveFirst();
		
		
		$res = "";
		$until = "";
		//var_dump($tbl);




			
		/* stamp for return */
		$tmpstamp = $this->back->position_stamp();



		/* chose page to collect templates */
		if(!$this->back->change_URI($this->content->get_template($this->template)))
			throw new \RuntimeException("XMLDO: template '$this->template' not found — check the 'template' section in the configuration");
		
		$this->list = array();

		$this->back->set_first_node();
		/* create list of nodes */
		for($i = 0; $this->back->index_child() > $i; $i++ )
		{
		  $this->back->child_node($i);
		  $this->list[$i] = $this->back->show_xmlelement();
		  $this->back->parent_node();
		}

		if(count($this->list) == 0)
			throw new \RuntimeException("XMLDO: template '$this->template' has no child nodes — setBranch/setCrotch reference child indices that don't exist");

		
		//TODO feld erstellen, das der Menge der Gruppen entspricht

		
		$this->back->freexpathresult();
		
		/* choose page for modification */
		//echo $this->content->get_out_template() . " --- " . . "--- \n";
		$this->back->change_idx($this->treeObj->get_EffBranch()->get_idx());

		//if(!$this->back->change_URI($this->content->get_out_template()))
		//echo $new_template . ' isn\'t a available documentident';
		
	

//*			

if(DEBUG)
{

		$nameToGroup = array();
		echo "\n Verifikation \n";
		foreach ($this->verification as $key => $value) 
		{		
			$nameToGroup[$value['name']] = $value['group'];
		echo " $key = {";
			foreach ($value as $key1 => $value1)
			echo " $key1 : $value1;";

		echo "}\n";
		}

                //var_dump($this->verification);
                echo "\n groups \n";
                foreach ($groups as $key => $value) 
		{		
		echo " $key = { crotch:" . !is_null($value['crotch']) . ", branch:" . !is_null($value['branch']) . " Many of datasets: " . count($value['data']) . " }\n";

		}
                //var_dump($groups);
                echo "\n level \n";
                foreach ($this->level as $key => $value) 
		{		
		echo " $key = {";
			foreach ($value as $key1 => $value1)
			echo " $key1 : $value1;";
		echo "}\n";
		}
		
		echo "\n relation \n";
                foreach ($this->relation as $key => $value) 
		{		
		echo " $key = {";
			foreach ($value as $key1 => $value1)
			echo " $key1 : $value1;";
		echo "}\n";
		}
		 
		
}

/* ----------------------------------------------------------------------------------------------------------------------------
* Bucketsort
*
* -----------------------------------------------------------------------------------------------------------------------------
*/
         
			
	$curHold = array();


	$xpath = "";
	$pos = 0;
	$prev_group = 1;
				

		
		if(DEBUG)
		{
		$len = 5;
		foreach($tbl as $key => $value)$len = max($len, strlen($key));

		foreach($tbl as $key => $value)
		{
				
				echo str_pad($key, $len, ' ', STR_PAD_BOTH) . "|";
		}
		
		echo str_pad("from", $len, ' ', STR_PAD_BOTH) . "|" . str_pad("until", $len, ' ', STR_PAD_BOTH) .  "\n";
		
		foreach($tbl as $key => $value)
		{
				//var_dump($nameToGroup);
				echo str_pad($nameToGroup[$key], $len, ' ', STR_PAD_BOTH) . "|";
		}
		
		echo str_pad(" ", $len, ' ', STR_PAD_BOTH) . "|" . str_pad(" ", $len, ' ', STR_PAD_BOTH) .  "\n";
		
		}
		
		if(DEBUG)
		{
		
		//for($i = 1; $i < count($groups) + 1;$i++)
		//	echo "c$i|";
			
				//if(DEBUG)echo "eg|lw|Kommentar\n";
				if(DEBUG)
									//foreach($tbl as $key => $value)
									for($i=0;$i<count($tbl) + 2;$i++)
										echo str_pad("+", $len + 1, '-', STR_PAD_LEFT);
					if(DEBUG)				echo "\n";
				
		}
		do{
			// ------ update table ------
			// TODO It seems like a copy function. Just check this
			$res = $this->test_alter($tbl) ; //fills table with current values

			$until = $this->last_col($tbl); // finds first null element and returns its key
			//var_dump($tbl, $res, $until);

			$fulltbl[$pos++] = $this->buildRow($tbl); //builds up the full table


			// -------------------------------
			
		if(DEBUG)
		{

			$lock = (strcmp('', $res) == 0);
			
			foreach($tbl as $key => $value){		
				$lock = (strcmp($key, $res) == 0) || $lock;
				$lock = !(strcmp($key, $until) == 0) && $lock;
				if($lock)
				echo str_pad("--X--", $len, ' ', STR_PAD_BOTH) . "|";
				else
				echo str_pad("0", $len, ' ', STR_PAD_BOTH) . "|";
				

				
				//$value ($key)
			  }	
			  
			  echo str_pad($res, $len, ' ', STR_PAD_BOTH) . "|";
			  echo str_pad($until, $len, ' ', STR_PAD_BOTH) . "|";
			  
}
			


		



			
			

		


	

		if(DEBUG)echo "\n";

		}
		while($this->rst->next());
		
    	//set new box
    	//var_dump($groups);
    		$hasSubLvl = $this->hasNextlvl($fulltbl, $groups, array(0, count($fulltbl) - 1), 0);
//if($hasSubLvl)echo "boom";

		if(DEBUG)echo "MainBox{\n";    	
    	
    		//if($hasSubLvl)
    		//der gibt doch knoten zuruck, springt der da ueberhaupt hin?
    	$this->buildCrotch($groups, $this->gotoGroup(0, $fulltbl));
    		

    	


		

			$this->insertedLeafCount = 0;
		$this->processingBox($fulltbl, $groups, array(0, count($fulltbl) - 1), 0);
		if(DEBUG)echo "}\n";

		/* record count sanity check:
		 * $pos = rows read from DB (fulltbl entries)
		 * insertedLeafCount = leaf branches written to document
		 * In a flat single-group config these must be equal.
		 * In multi-level configs the leaf count reflects unique bottom-level groups —
		 * divergence from $pos indicates rows were silently skipped or merged unexpectedly. */
		if(!is_null($this->rst) && $this->insertedLeafCount !== $pos)
			throw new \RuntimeException(
				"XMLDO: record count mismatch — " . $pos . " rows from datasource, " .
				$this->insertedLeafCount . " leaf entries written to document"
			);

                /* check level */
		//TODO gruppen einzeln durchtesten und für mehrere Gruppen pro Tiefe auslegen

        /* return to former page */
        $this->back->go_to_stamp($tmpstamp);

	}
	
	/**
	* Collector path: reads data FROM the XML document into $this->internal_table_values.
	* Called by generateBranchTree() when $this->config['actsAsCollector'] is true.
	*
	* Flow:
	*   createGroupArray()         — build group index
	*   create_tbl()               — init empty row template
	*   collectDataFromDocument()  — traverse document nodes, fill rows
	*
	* @throws Exception  if template not found
	* @calls createGroupArray(), create_tbl(), collectDataFromDocument()
	* @called-by generateBranchTree()
	*/
	private function collectData()
	{
		
		// -------------- prepare the workplace ---------------------
		$tmpstamp = $this->back->position_stamp();
		
		
		/* chose page to collect templates */
		if(!$this->back->change_URI($this->content->get_template($this->template)))
		{
			//$this->content->show_templates();
			throw new Exception("template " . $this->template . " isn't a valid identifer");
			echo $this->template . ' isn\'t a available documentident';
			return;
		}
		
		
		$this->back->set_first_node();

    	// ------------------------------------------------------------

		//return "";
		/* ------------------------------------------ groups ------------------------------------------------------*/
		$groups = array();
		$permutation= array();
		/* -----------------------------------------------------------------------------------------------------------*/


		$tmp = $this->createGroupArray($this->verification, $this->relation, $this->level);
//var_dump($this->verification, $this->relation, $this->level, $tmp);
$groups = $tmp[0];
$permutation = $tmp[1];


//var_dump($names);
		//TODO bugfix Wenn sich der zweite Eintrag einer gruppe geaendert hat, stimmt die gruppe nicht
		$tbl = array();
		$fulltbl = array();

		$this->create_tbl($tbl);

		$this->collectDataFromDocument($fulltbl, $tbl, $groups);
		
        $this->back->go_to_stamp($tmpstamp);

	}
	
	/**
	* Top-level entry for document traversal in collector mode.
	* Writes results into $this->internal_table_values via iterateGroupsBranch().
	*
	* @param array $fulltbl  Accumulator for collected rows (by ref)
	* @param array $tbl      Empty row template (from create_tbl)
	* @param array $groups   Group index (from createGroupArray)
	* @calls iterateGroupsBranch()
	* @called-by collectData()
	*/
	private function collectDataFromDocument(&$fulltbl, $tbl, $groups)
	{
		reset($groups);
		//var_dump($tbl, $groups);
		
		$fulltbl[] = $tbl;
		$this->internal_table_values = $this->iterateGroupsBranch($tbl, $fulltbl, $groups, $this->back->show_xmlelement());
		reset($this->internal_table_values);
		//var_dump($this->internal_table_values);
		//var_dump("blub" . $fulltbl);
	}
	public function blub(){return "blub";}
	/**
	* Recursive document traversal for the collect path (mirror of processingBox).
	* For each node found via the current group's branch xpath:
	*   - recurses into the next group (next($groups)) → iterateGroupsBranch()
	*   - then collects field values from that node → iterateGroupsData()
	* Uses current() / next() to advance the $groups cursor.
	*
	* @param array       $tbl          Row template (empty or partially filled)
	* @param array       $fulltbl      Accumulated result rows (by ref)
	* @param array       $groups       Group index cursor (by ref — advances with next())
	* @param xml_element $root_object  Document node to start traversal from
	* @return array  All collected rows for this branch level
	* @calls iterateGroupsBranch() [recursive], iterateGroupsData()
	* @called-by collectDataFromDocument(), iterateGroupsBranch()
	*/
	private function iterateGroupsBranch($tbl,  &$fulltbl,  &$groups, $root_object)
	{

		//preparation
		$this->back->set_xmlelement($root_object);	
		$branch = &current($groups)['branch'];
		$data = &current($groups)['data'];
		$resultTbl = [];
		$newTbl = $tbl;
		//echo "-----------" . $branch['xpath'] . "------------------\n";		
		//var_dump($branch, $data);

		//goto branch
    	$this->back->complete_list(true);
    	$this->back->set_xmlelement($this->back->xpath($branch['xpath']));

		$result = &$this->back->get_xpath_Result();

		if(count($result) == 0)
		{
			if(!$branch['allowEmpty'])
				throw new \RuntimeException("XMLDO: iterateGroupsBranch — xpath '" . $branch['xpath'] . "' returned no results (set allowEmpty:true in setBranch/setCrotch to suppress)");
			$resultTbl[] = $tbl;
		}

		foreach ($result as $value)
		{
		//var_dump($value->full_URI() . " ");
		$this->back->complete_list(false);		
		$newTbl = [];
		
		if(next($groups))
		{
			//echo "next groups\n";
			$newTbl = $this->iterateGroupsBranch($tbl, $fulltbl, $groups, $value);
			//var_dump($newTbl);
			//echo "bekomme " . count($newTbl) . " eine dings\n";
			$this->iterateGroupsData($newTbl, $data, $value);
		}
		else
		{
			//echo "erzeuge eine dings\n";
			$newTbl[] = $tbl;
			$this->iterateGroupsData($newTbl, $data, $value);
		}

		$resultTbl = array_merge($resultTbl, $newTbl);


		reset($data);
		
			
		}
		//echo "ausgabe\n";
		//var_dump($resultTbl);
		return $resultTbl;
	}
	
	/**
	* Fills each row in $tbl with values read from $root_object in the document.
	* For 'data' type: reads node CDATA via getdata().
	* For 'attrib' type: reads attribute via get_ns_attribute().
	* If $value['value'] is set (constant override), skips xpath entirely.
	* Recurses with next($dataset) to process all definition entries for this group.
	*
	* @param array       $tbl          Rows to fill (by ref) — each row is key→value map
	* @param array       $dataset      Column definitions cursor (by ref — advances with next())
	* @param xml_element $root_object  Document node to read data from
	* @called-by iterateGroupsBranch()
	*/
	private function iterateGroupsData(&$tbl, &$dataset, $root_object)
	{

		//sets root node
		$this->back->set_xmlelement($root_object);
		// gives current data section part to the data variable
		$data = current($dataset);
		//echo "GroupsData:" . $data["xpath"] . " in " . $root_object->full_URI() .   "\n";
		//var_dump($tbl);
		foreach ($tbl as &$value)
		{
		if(!is_null($data['value'])){$value[$data['name']] = $data['value'];continue;}

		//applies a xpath statement on the root node
    	if(!$res = &$this->back->xpath($data["xpath"]))
    		{
    			if(!$data['allowEmpty'])
    				throw new \RuntimeException("XMLDO: iterateGroupsData — xpath '" . $data['xpath'] . "' returned no result for field '" . $data['name'] . "' (set allowEmpty:true in define_tag to suppress)");
    			continue;
    		}
//echo "--" . $data["xpath"] . ':' . $data['attrib_data'] . "--\n";
		if($data['attrib_data']== 'data')
    		{
//echo "!!!";
    			//collects an data entry
    			$value[$data['name']] = $res->getdata();

    		}
    		else
    		{
    			//echo "--" . $data["xpath"] . ':' . $data['attrib_data'] . "->" . $data['prefix'] . '#' . $data['postfix'] . "--\n";
    			//echo "???";
    			//collects an attribute entry
    			if(($value[$data['name']] = $res->get_ns_attribute($data['prefix'] . '#' . $data['postfix'])) === false)
    			$value[$data['name']] = null;
    				 	 	 		
    		}
//var_dump($value);    		
		}

		//applies same func rekursivly with next dataset section
		if(next($dataset))$this->iterateGroupsData($tbl, $dataset, $root_object);

	}
	
	public function generateEmptyTree($many)
	{
	        if(count($this->documentForInsert) > 0)
		{
		
	        $tmpstamp = $this->back->position_stamp();
		
		if(!$this->back->change_URI($this->content->get_out_template()))
		echo $new_template . ' isn\'t a available documentident (generateEmptyTree)';
		
		$tmpName = $this->back->show_xmlelement();
		$this->documentForInsert[0]->set_parser($this->back);
		for($i = 0;$i < $many; $i++)
			$this->documentForInsert[0]->cloning($this->back->show_xmlelement());
		}

	  $this->back->go_to_stamp($tmpstamp);
	
	}
		
	/**
	* Alternative write pipeline — flat mode, no grouping or recursion.
	* Clones $this->documentForInsert[0] once per DB row and writes column values
	* directly into each clone via xpath (data as CDATA, attribs inline).
	* Simpler than generateBranchTree() but does not support tree hierarchy.
	*
	* @requires $this->documentForInsert  Clone source (set by setXMLTemplate())
	* @requires $this->verification       Column definitions (set by define_tag())
	* @requires $this->rst                DB datasource (null = run once for static data)
	* @calls show_content()
	* @called-by processSerialConfiguration()
	*/
	public function generateTagTree( )
	{

		if(count($this->documentForInsert) == 0)
			throw new \RuntimeException("XMLDO generateTagTree: documentForInsert is empty — check 'use_at_tag' in template config (setXMLTemplate found no matching element)");

		$this->show_content();

				$this->config['setTreeType'] = "Tag";
		
		if(is_null($this->rst) || $this->rst->moveFirst())
		{
		
		
		$tmpstamp = $this->back->position_stamp();
		//echo $tmpstamp . " ";
		//echo "booja " . $this->content->get_current_template();
		if(!$this->back->change_URI($this->content->get_current_template()))
		  echo $new_template . 'isn\'t a available documentident (generateTagTree) ';

		$tmpName = $this->back->show_xmlelement();
		
		if(count($this->documentForInsert) > 0)
		{
			$this->documentForInsert[0]->set_parser($this->back);
		do {
		$obj = $this->documentForInsert[0]->cloning($this->back->show_xmlelement());
		
		/*
		* Creates new entries
		*
		*/ 
		$list = array();
		$obj_ref;
		for($i = 0;$i<count($this->verification);$i++)
		{
		
		$this->back->freexpath($this->verification[$i]['xpath'], $obj);
		$obj_ref = $this->back->get_xpath_Result();
		
		if($this->verification[$i]['attrib_data'] == 'data')
		{
		$tmp = $obj_ref[count($obj_ref) - 1]->index_max();
		
		if(!is_null($this->verification[$i]['value']))$datarec = $this->verification[$i]['value'];
		elseif(!is_null($this->rst))$datarec = $this->rst->col($this->verification[$i]['name']);
		$obj_ref[count($obj_ref) - 1]->setdata($datarec,$tmp);
		$obj_ref[count($obj_ref) - 1]->set_bolcdata($this->cdata);
		unset($tmp);
		unset($datarec);
		
		}elseif($this->verification[$i]['attrib_data'] == 'attrib')
		{
			
			if(!is_null($this->verification[$i]['value']))$tmp = $this->verification[$i]['value'];
			else $tmp = $this->rst->col($this->verification[$i]['name']);
					  	
			if(is_null($tmp) && !$this->config['attribOnNull'] )continue;
    				 	 	 
			
		//TODO aenderung fuer bereits besehende Attribute
				if(!$this->verification[$i]['prefix'] )
				{
					

					$myattrib = &$this->back->get_Object_of_Namespace( $this->content->get_Main_NS() . '#' .
					  $this->verification[$i]['postfix'] );
					  
					
					$myattrib->setdata($tmp,0);
					unset($tmp);
					$obj_ref[count($obj_ref) - 1]->attribute($this->verification[$i]['postfix'], $myattrib);
							 
					unset($myattrib);
					
					
				}
				else
				{
				

					$attrib = &$this->back->get_Object_of_Namespace(  
					  $this->verification[$i]['prefix'] . '#' . $this->verification[$i]['postfix'] );
						
					
					
					$attrib->setdata($tmp,0);
					
					//$attrib->setdata($tmp,0);
					
					
				        
				//var_dump($this->verification[$i]['prefix'],$obj_ref[count($obj_ref) - 1]->get_idx());
											
					$prefix = $this->back->get_Prefix($this->verification[$i]['prefix'],$obj_ref[count($obj_ref) - 1]->get_idx());
					$postfix = $this->verification[$i]['postfix'];
		
							if(strlen($prefix) > 0)
							{
								$attrib->name = $prefix . ':' . $postfix;
								$obj_ref[count($obj_ref) - 1]->attribute( $prefix . ':' . $postfix, $attrib);
								
							}
							else
							{
							
								$attrib->name = $postfix;
								$obj_ref[count($obj_ref) - 1]->attribute( $postfix, $attrib);
							}
							
					unset($attrib);
					unset($tmp);
						

				
				}
		
		//  $tmp = $obj_ref[count($obj_ref) - 1]->index_max();
		
		//if(!is_null($this->rst))$attribrec = $this->rst->col($this->verification[$i]['name']);
		//echo $attribrec . " \n";
		//$obj_ref[count($obj_ref) - 1]->setdata($datarec,$tmp);
		unset($datarec);
		
		
		}
		}

		} while (!is_null($this->rst) && $this->rst->next());
		
		$this->back->go_to_stamp($tmpstamp);
		return '';
		}
		}
		return $this->collectionemptyText;
	}
	//public function moveFirst(){parent::moveFirst();}
	//public function moveLast(){parent::moveFirst();}
    	//is never used
    	//abstract public function getAdditiveSource();


	public function prev(){return parent::prev();}
	public function next(){return parent::next();}
	
	public function reset()
	{
	
	$this->test = 0;
	unset($this->rst);
	$this->rst = null;
	$this->pos = 0;
	$this->template = '';

	$this->documentForInsert = '';
	$this->verification = array();
	}
		
	public function getAdditiveSource(){}
	public function __toString(){return 'xmldo';}
	
	public function fields()
	{
		var_dump($this->internal_table_values);
		return array_keys(current($this->internal_table_values));
	}
}

?>
