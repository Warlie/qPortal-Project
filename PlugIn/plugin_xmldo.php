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

private $table = [];

private $template_json;

	function __construct(/* System.Parser */ &$back, /* System.CurRef */ &$treepos, /* System.Content */ &$content)
	{
		$this->back= &$back;
		$this->treepos = &$value;
		$this->content = &$content;
		//echo $this->treepos->full_URI();
		//$this->id = $value; , &$id
	}
	
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}


	/**
	* XMLTEMPLATE
	*@function: HAS_TAG = returns a boolean value refering to the seeking tag, descripted by xpath 
	* TODO muss auf xpath erweitert werden
	*
	*/
	public function setXMLTemplate($new_template, $use_at_tag)
	{
		
		$this->template_json = "{\"new_template\" : \"$new_template\", \"use_at_tag\" : \"$use_at_tag\"}";
		$tmpstamp = $this->back->position_stamp();
		
		$this->template = $new_template;
		//echo $this->template . " " . $this->content->get_template($new_template) . " ";
		if(!$this->back->change_URI($this->content->get_template($new_template)))
		{
		///echo $new_template . ' isn\'t a available documentident (setXMLTemplate) ';
		$this->back->test_consistence();
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
	
	public function configuration($json)
	{
		$confi = json_decode($json, true, 512, JSON_THROW_ON_ERROR); // TODO Exception for NULL
		
		if(array_key_exists("serial",$confi))$this->processSerialConfiguration($confi["serial"]);
		

	}
	
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
		if(array_key_exists("template",$confi))$this->setXMLTemplate(...$confi["template"]);
		if(array_key_exists("structure",$confi))
		{//$this->setXMLTemplate(...$confi["template"]);
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
		{			//var_dump($confi["structure"]);
			foreach ($confi["definition"] as $command) {

					$this->define_tag(...$command);


			}
		}
		if(array_key_exists("process",$confi))  //define_tag
			switch ($confi["process"]) {
			case "Branch":
				$this->generateBranchTree();
				break;
			case "Tag":
				$this->generateTagTree();
				break;
			}

	}
	

	
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
	* SETTAG
	*
	*/
	public function define_tag($name, $xpath, $attrib_data = null, $pos = null, $prefix = null, $postfix = null, $value = null, $group = null, $branch = null)
	{	

		$cur_pos = count($this->verification);
		$this->verification[$cur_pos] = array();
		$this->verification[$cur_pos]['name'] = $name; /* name to call*/
		$this->verification[$cur_pos]['xpath'] = $xpath; /* pos in document */
		$this->verification[$cur_pos]['attrib_data'] = $attrib_data; /* data or attribute */
		$this->verification[$cur_pos]['pos'] = intval($pos);
		$this->verification[$cur_pos]['prefix'] = $prefix; /* prefix like dc(:autor) */
		$this->verification[$cur_pos]['postfix'] = $postfix; /* postfix like (dc:)autor */
		$this->verification[$cur_pos]['value'] = $value;  /* constant value */
		$this->verification[$cur_pos]['group'] = $group;  /* to concern several columns into groups */
		$this->verification[$cur_pos]['branch'] = intval($branch);  /* number of the child at the rootnode */
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
	* 
	* @param child_pos: 
	*/
	public function setCrotch( $child_pos, $xpath, $group)
	{

	  $this->level[] = array('type'=> 0, 'child_pos' => intval($child_pos),  'xpath' => $xpath, 'group' => $group);
	
	}
	
	public function setBranch($child_pos, $xpath, $group)
	{

	  $this->level[] = array('type'=> 1, 'child_pos' => intval($child_pos),  'xpath' => $xpath, 'group' => $group);
	}
	 
	public function setRelation($lvl, $group, $condition )
	{
	$this->relation[] = array('level' => intval($lvl), 'group' => $group, 'condition' => $condition ); 
	}

	private function deepRoot(&$finish, &$groups, &$node, $deep = 0)
	{
	$param_node = &$this->list[$this->level[$deep]['child_pos']]->cloning($node);

	}
	


	/**
	* creates an assoziatied array and set all entries to null
	*/
	
	private function create_tbl(&$tbl)
	{
			for($i = 0; count($this->verification) > $i; $i++ )
			{
				$tbl[$this->verification[$i]['name']] = null;
			}
	}
	
	/**
	* compares every entry in array to have altered
	* @return changed key
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

	private function &buildRow($tbl)
	{
		$res = array();
		  if(is_array($tbl))     
		  	  foreach($tbl as $key => $value)

		  	  	$res[$key] = $this->rst->col( $key);			  
		  return $res;
	}
	
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
  * @param $lvl : level in tree
  * @param $tbl : complete table
  * @return name of group
  * ---------------------------------------
  * dependencies
  * $this->relation
  *
  * returns name of the Group, depenending on level
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
    
    private function writeData($range, $lvl, &$tbl, &$groups)
    {
    
    		   			foreach ($groups as  $value)
    				 	 {
    				 	 	 if(DEBUG)echo str_repeat("   ", $lvl) . " [" . $value['name'] . "]={'value'=" .  $tbl[$range[0]][$value['name']] . ", 'xpath'=" .  $value['xpath'] . ", 'type'=" .  $value['attrib_data'] . "}\n";
    				 	 	 
    				 	 	 if(strcmp($value['attrib_data'], 'data') == 0)
    				 	 	 {

    				 	 	 	 $this->setData( $value['xpath'], $tbl[$range[0]][$value['name']]);
    				 	 	 }
    				 	 	 else
    				 	 	 {
    				 	 	 	 //var_dump($tbl[$range[0]][$value['name']]);
    				 	 	 	if(!is_null($tbl[$range[0]][$value['name']]) || $this->config['attribOnNull'] )
    				 	 	 	{
    				 	 	 		//echo str_pad($value['name'],40," ") . ":";
    				 	 	 	//var_dump($tbl[$range[0]][$value['name']]);	
    				 	 	 	$this->setAttrib( $value['xpath'], 
    				 	 	 		$value['prefix'] , 
    				 	 	 		$value['postfix'], $tbl[$range[0]][$value['name']]);
    				 	 	 	}
    				 	 	 		
    				 	 	}
    				 	 }
    				 	 
    
    }
    
    
    /** 
    *TODO Needs javadoc
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
  * @param $lvl : level in tree
  * @param $tbl : complete table
  * @return name of group
  * ---------------------------------------
  * dependencies
  * $this->relation
  */
    
    
      /**
  * @param $lvl : level in tree
  * @param $tbl : complete table
  * @return name of group
  * ---------------------------------------
  * dependencies
  * $this->relation
  */
    private function processingBox(&$tbl, &$groups, $box, $lvl)
    {
    	//var_dump($box);
    	// results current groupname
    	$subBranch = &$this->back->show_xmlelement();
    	if(is_null($lookfor = $this->gotoGroup($lvl, $tbl)))
    		{
    			if(DEBUG)echo "\n";
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
    	$cur_crotch = &$run_branch;
    	
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
    * buildCrotch
    * @param array(string) byRef groups 
    * @param string name
    * @return xml_element
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
    		//echo $this->back->get_URI() + '(' +  $this->back->position_stamp() . ") neues Baumelement \n";
    		$this->back->complete_list(false);
    		
    		$res = &$this->back->xpath($groups[$name]['crotch']['xpath']);
    		//echo $res->full_URI() + "\n";
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
    * buildBranch
    * @param array(string) byRef groups 
    * @param string name
    * @return xml_element
    *
    * c
    */
    
    private function &buildBranch(&$groups, $name)
    {
    	if($groups[$name]['branch'])
    	{
    		//echo "--------------------------------------startet in buildBranch-------------------------------------------\n";
    		//echo $this->back->position_stamp() . " Unterzweig fuer einen neuen Zweig \n";
    		//$this->back->show_xmlelement()->giveOutOverview();
    	
    		//Es wird ein neuer zweig ab der akutellen Position angehangen. Dieser ist der entsprechende
    		// Kindsknoten von der Hauptwurzel an
    		
    		$i = $groups[$name]['branch']['child_pos'];
    		$res = $this->back->append_xmlelement($this->list[$i]);
    		//echo $this->back->position_stamp() . " neuer Zweig \n";
    			
    		$this->back->complete_list(false);
    		//var_dump($groups[$name]['branch']);
     		$res = &$this->back->xpath($groups[$name]['branch']['xpath']);
     		/*
     		echo "---------------------------------------------------------------------------------\n";
     		if(is_object($res))
     		{
     		echo "looking for: " . $groups[$name]['branch']['xpath'] . "\n <br>";
     		//echo $res->giveOutOverview();
     		}
     		else
     		echo "vor Die Wand " + $groups[$name]['branch']['xpath'] +"\n";
     		echo "---------------------------------------------------------------------------------\n"; 
     		*/
    		//$this->back->set_xmlelement($res);
    		$this->back->complete_list(true);
    		//$this->back->overview_xpath_Result();
    		//$obj_ref_array = &$this->back->get_xpath_Result();
    		$this->back->free_xpath_Result();
    		
    		 // echo $this->back->position_stamp() . "  \n";
    		//$this->back->set_xmlelement($obj_ref_array[0]);
    		return $res;
    	//var_dump($groups[$name]['crotch']);
    	}

    }
    
    	private function setData( $xpath, $value)
	{
		
	//	echo $this->back->position_stamp() . " start Zweig fuer data \n";
    	
//		echo $value . " --------------------------------------------";
		$backjump = &$this->back->show_xmlelement();
		   	//	echo "->" . $backjump->name . "<-\n";
    			
    		$this->back->complete_list(false);
    		$res = &$this->back->xpath($xpath);
    		if(!$res)echo "alles klar, das kann gar nicht klappen, $xpath gibt es nicht";
     		//$this->back->set_xmlelement();
    		$this->back->set_xmlelement($res);
    		$this->back->complete_list(true);
    		$this->back->free_xpath_Result();

    		//$res =&$this->back->show_xmlelement()  ;
    		//echo $res->name . "-----------...---";
    		$this->back->set_node_cdata($value);
    		
    		$this->back->set_xmlelement($backjump);
    		
    		 // echo $this->back->position_stamp() . "  \n";
    		//$this->back->set_xmlelement($obj_ref_array[0]);

    		
	}
    
    	private function setAttrib( $xpath, $ns, $attrib, $value)
	{

//		echo $this->back->position_stamp() . " start Zweig fuer attib \n";
    	
//		echo " $ns:$attrib=$value  \n";
		$backjump = &$this->back->show_xmlelement();
		  // 		echo "->" . $backjump->name . "<-\n";
    			
    		$this->back->complete_list(false);
    		$res = &$this->back->xpath($xpath);
    		if(!$res)echo "alles klar, das kann gar nicht klappen, $xpath gibt es nicht";
     		//$this->back->set_xmlelement();
    		$this->back->set_xmlelement($res);
    		$this->back->complete_list(true);
    		$this->back->free_xpath_Result();
    		    		
    		//$res =&$this->back->show_xmlelement()  ;
    		//echo $res->name . "-----------...---";
    	//	echo 'Hier kommt jetzt das Attribut rein: ' . $this->back->position_stamp() . "  \n";
//TODO prefixe werden ignoriert
    		$this->back->set_node_attrib("$attrib", $value);

    		$this->back->set_xmlelement($backjump);
    		
   // 		 echo 'Und zurueck' . $this->back->position_stamp() . "  \n";
    		//$this->back->set_xmlelement($obj_ref_array[0]);

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
	*	requirements:
	*	this->rst : DB Datasource
	*	this->template: 
	*	$this->verification: contains description, how to use template and dbsource
	*	
	*	calls:
	*	this->deepCollect( bool, array[][], xmlElement)
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
				$tmp[] =$value['group'];
				$groups[$value['group']] = array('crotch'=>null, 'branch'=>null, 'data'=>array());
			}

		}
		if(!count($relation))		
			foreach ($tmp as $key => $value) $relation[++$iter] = array('level' => $iter, 'group' => $value, 'condition' => "" );
		unset($tmp);
				
		for($i = 0; count($verification) > $i; $i++ )$permutation[$verification[$i ]['group']][] = $verification[$i ] ;

		//var_dump($permutation);
		
		$names[$verification[0]['name']]  = $verification[0]['group']; 
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
								echo "wrong groupnames in level";
						
							if(is_null($groups[trim($value2)]['branch']))
							$groups[trim($value2)][$type[$value['type']]] = &$this->level[$key];
							else
							{
								//var_dump($groups[trim($value2)]);
							//echo "oh, doof";
							throw new Exception($value2); //$groups[trim($value2)]['branch']
							}
					}
					



		}
		
/* ----------------------------------------------------------------------------------------------------------------------------
* Bucketsort
*
* -----------------------------------------------------------------------------------------------------------------------------
*/
		
		                // appends branch and crotch to groups
                		
        for($i = 0; count($this->level) > $i; $i++ )
		{
			$arr = explode(',', $this->level[$i]["group"]);
			for($j = 0; count($arr) > $j; $j++ )
			{
				if($this->level[$i]["type"] == 0)
				{
					$groups[$arr[$j]]['crotch'] = &$this->level[$i];
				}
				else
				{
					$groups[$arr[$j]]['branch'] = &$this->level[$i];
				}
			}

		}	
		
		return [$groups, $permutation];
	}
	
	public function generateBranchTree()
	{

		/* check verification */
		if(count($this->verification) == 0)
		{
			echo "need Infomation about branches, please use define_tag";
			return;
		}
		
		if($this->config['actsAsCollector'])
		{
			$this->collectData();
			return;
		}
		
		$this->show_content();
		$this->config['setTreeType'] = "Branch";
		
		/*moveFirst*/
		if(!(is_null($this->rst) || $this->rst->moveFirst()))
		{
			//echo " no static data or recordset ";
			return '';
		}
		

		
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
		

		
		$this->rst->moveFirst();
		
		
		$res = "";
		$until = "";
		//var_dump($tbl);




			
		/* stamp for return */
		$tmpstamp = $this->back->position_stamp();



		/* chose page to collect templates */
		if(!$this->back->change_URI($this->content->get_template($this->template)))
		{
			echo $this->template . ' isn\'t a available documentident';
			return;
		}
		
		$this->list = array();

		$this->back->set_first_node();
		/* create list of nodes */
		for($i = 0; $this->back->index_child() > $i; $i++ )
		{
		  $this->back->child_node($i);
		  $this->list[$i] = $this->back->show_xmlelement();
		  $this->back->parent_node();
		}

		
		//TODO feld erstellen, das der Menge der Gruppen entspricht

		
		$this->back->freexpathresult();
		
		/* choose page for modification */
		if(!$this->back->change_URI($this->content->get_out_template()))
		echo $new_template . ' isn\'t a available documentident';
		
	

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
         
   /*             // appends branch and crotch to groups
                		
        for($i = 0; count($this->level) > $i; $i++ )
		{
			$arr = explode(',', $this->level[$i]["group"]);
			for($j = 0; count($arr) > $j; $j++ )
			{
				if($this->level[$i]["type"] == 0)
				{
					$groups[$arr[$j]]['crotch'] = &$this->level[$i];
				}
				else
				{
					$groups[$arr[$j]]['branch'] = &$this->level[$i];
				}
			}

		}	

		*/			
	$curHold = array();


	$xpath = "";
	$pos = 0;
	$prev_group = 1;
				
/* 				echo gettype()
				var_dump($value); */

		
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
			$res = $this->test_alter($tbl) ; //fills table with current values
			$until = $this->last_col($tbl); // finds first null element and returns its key

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
    		

    	


		

		$this->processingBox($fulltbl, $groups, array(0, count($fulltbl) - 1), 0);
		if(DEBUG)echo "}\n";


		
                /* check level */
		//TODO gruppen einzeln durchtesten und für mehrere Gruppen pro Tiefe auslegen

                
        /* return to former page */
        $this->back->go_to_stamp($tmpstamp);
                
                

	}
	
	
	private function collectData()
	{
		
		$tmpstamp = $this->back->position_stamp();
		
		/* chose page to collect templates */
		if(!$this->back->change_URI($this->content->get_template($this->template)))
		{
			echo $this->template . ' isn\'t a available documentident';
			return;
		}
		$this->back->set_first_node();

    		//var_dump($groups[$name]['branch']);

		
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
		
		$this->collectDataFromDocument($fulltbl, $tbl, $groups);
		
        $this->back->go_to_stamp($tmpstamp);

	}
	
	
	private function collectDataFromDocument(&$fulltbl, $tbl, $groups)
	{
		reset($groups);
		//var_dump($tbl, $groups);
		
		$fulltbl[] = $tbl;
		$this->internal_table_values = $this->iterateGroupsBranch($tbl, $fulltbl, $groups, $this->back->show_xmlelement());
		//var_dump($this->internal_table_values);
	}
	
	/**
	*	
	*
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
		
		if(count($result) == 0)$resultTbl[] = $tbl;

		foreach ($result as $value)
		{
		//var_dump($value->full_URI() . "-go");
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
	*	subfunc collects data out of every data section array.
	*	It starts at the specific buttom of every collection and fills out every 
	*	table in the table section
	*	@param tbl array[mixed] : result array with empty fields to fill 
	*	@param dataset array[mixed]] : array with description how to fill the result array
	*	@param root_object Interface_NS : basement node for statirng with
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

		//applies a xpath statement on the root node
    	if(!$res = &$this->back->xpath($data["xpath"]))
    		{
    			//echo $data["xpath"] . " in " . $root_object->full_URI() . " not found";
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
		
	public function generateTagTree( )
	{

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
		
		if(!is_null($this->rst))$datarec = $this->rst->col($this->verification[$i]['name']);
		$obj_ref[count($obj_ref) - 1]->setdata($datarec,$tmp);
		$obj_ref[count($obj_ref) - 1]->set_bolcdata($this->cdata);
		unset($tmp);
		unset($datarec);
		
		}elseif($this->verification[$i]['attrib_data'] == 'attrib')
		{
			
			$tmp = $this->rst->col($this->verification[$i]['name']);	
					  	
			if(is_null($tmp) && !$this->config['attribOnNull'] )continue;
    				 	 	 
			
		//TODO aenderung fuer bereits besehende Attribute
				if(!$this->verification[$i]['prefix'] )
				{
					

					$myattrib = &$this->back->get_Object_of_Namespace( $this->content->get_Main_NS() . '#' . 
					  $this->verification[$cur_pos]['postfix'] );
					  
					
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
}

?>
