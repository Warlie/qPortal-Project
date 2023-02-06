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
private $rst;
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
private $config= array('attribOnNull' => true);
private $testmode = false;
private $cdata;

	function __construct(/* System.Parser */ &$back, /* System.CurRef */ &$treepos, /* System.Content */ &$content)
	{
		$this->back= &$back;
		$this->treepos = &$value;
		$this->content = &$content;
		//echo $this->treepos->full_URI();
		//$this->id = $value; , &$id
	}
	
			
	/**
	*@function: MOVEFIRST = goes to first record
	*/
		
	public function moveFirst()
	{$this->pos = 0;}
	
	/**
	*@function: MOVELAST = goes to last record
	*/
	public function moveLast()
	{$this->pos = count($this->table) - 1;}
	

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
	


	/**
	* COLLECTION
	*
	*/
	public function setCollection()
	{
		$this->collection = $value;
	}
	
	/**
	* COLLECTION
	*
	*/
	public function setEmptyCaseText($text)
	{
		$this->collectionemptyText = $text;
	}
	
	/**
	* CDATA
	*
	*/
	public function setCDATAmode()
	{
			
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
	public function define_tag($name, $xpath, $attrib_data, $pos, $prefix, $postfix, $value, $group, $branch)
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
	
	
		public function col($columnName)
		{
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
	public function setCrotch( $child_pos, $xpath, $group, $level)
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
	
/*
	private function setData( &$node, $verificationNum, $pos)
	{

		if($this->back->freexpath($this->verification[$verificationNum]['xpath'], $node) == 0)
                {
	           		echo "bad xpath:" . $this->verification[$verificationNum]['xpath'] . " \n";
                		return ;
                }
              
                $obj_ref_array = &$this->back->get_xpath_Result();
                $this->back->free_xpath_Result();
                $note_use =  &$obj_ref_array[0];
                unset($obj_ref_array);

	
	  $tmp = $note_use->index_max();
		
	  if(!is_null($this->rst))$datarec = $this->rst->col($this->verification[$verificationNum]['name']);
		    
		   //echo "schreibe $datarec !\n";
	   
          $note_use->setdata($datarec,$tmp);
          $note_use->set_bolcdata($this->cdata);

          unset($tmp);
	  unset($datarec);
	}	
	*/
/*	
	private function setAttrib(  &$node, $verificationNum)
	{
		
	
		if($this->back->freexpath($this->verification[$verificationNum]['xpath'], $node) == 0)
                {
	           		echo "bad xpath:" . $this->verification[$verificationNum]['xpath'] . " \n";
                		return ;
                }
              
                $obj_ref_array = &$this->back->get_xpath_Result();
                $this->back->free_xpath_Result();
                $note_use =  &$obj_ref_array[0];
                unset($obj_ref_array);

	$postfix = $this->verification[$verificationNum]['postfix'];
	
	if(!$this->verification[$verificationNum]['prefix'] )
	$full_uri = ($prefix = $this->content->get_Main_NS()) . '#' . $postfix;
	else
	$full_uri = ($prefix = $this->verification[$verificationNum]['prefix']) . '#' . $postfix ;
	
	$prefix = $this->back->get_Prefix($prefix,$note_use->get_idx());

	if( strlen( $prefix ) == 0 )
	  $prefix .= $prefix;
	else
	  $prefix .= $prefix . ':';
	
	
	
	
	$myattrib = &$this->back->get_Object_of_Namespace( $full_uri );
	
	$myattrib2 = &$this->back->get_Object_of_Namespace( $full_uri );
	
	//If($myattrib === $myattrib2) echo "sind gleich \n";
	
	//echo " prepost " . $prefix . ":" . $postfix . "--";
	
	if($prefix . ":" . $postfix == ":src")
	{ //./images/02.png
			If($myattrib === $myattrib2) echo "sind gleich \n";
		if($this->machwasmit++ % 2 == 1 )
		$datarec = "./images/03.png";	
		else
		$datarec = "./images/02.png";
		
		//echo $datarec . "\n";
	$myattrib->setdata($datarec,0);
	
	
	$note_use->attribute($prefix . $postfix, $myattrib);

	  unset($myattrib);
	  unset($note_use);
		
		return;
	}
	
	$datarec = "";
	
	if(!is_null($this->rst))$datarec = $this->rst->col($this->verification[$verificationNum]['name']);

	//echo $datarec . " booho \n";
	
	$myattrib->setdata($datarec,0);
	
	unset($datarec);
	$note_use->attribute($prefix . $postfix, $myattrib);
	
	  unset($myattrib);
	  unset($note_use);
	

	}	
	
	*/

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
                		/*
                		echo "(";
                		for($i = 0; $i < count($obj_ref_array);$i++)
                			echo $obj_ref_array[$i]->position_stamp() . "-";
                		
                		echo ")";
                		*/
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
    	/*
    	echo "start box [" . $range[0] . "-" . $range[1] . "] with group " . $groups[0]['group'] . " with :\n";
    	foreach ($groups as  $value)
    		echo $value['name'] . "\n";
    	echo "\n"; */
    	//echo "  for tbl and groups \n";
    	$has_entries = false;
    	$res = $range;
    	$res[1] = $res[0];
    	
   		  foreach ($groups as  $value)
   			{
    			$has_entries = !is_null($tbl[$range[0]][$value['name']]);
    			if($has_entries)break;
    			/*
    			echo  "tbl[" . $range[0] . "][" . $value['name'] . "]=" . $tbl[$range[0]][$value['name']] . "(" . gettype($tbl[$range[0]][$value['name']]) . ")";
    			if($has_entries)
    					echo "gotcha\n";
    				else
    					echo "\n";*/
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
    			//echo $value['name'] . " in " . $i . ": ";
    			$has_entries = !$has_entries || !is_null($tbl[$i - 1][$value['name']]);
    			//var_dump($tbl);
    			//echo $tbl[$i - 1][$value['name']] . "=" . $tbl[$i][$value['name']] . "\n";
    			//if(strcmp($tbl[$i - 1][$value['name']], $tbl[$i][$value['name']]) <> 0)echo "----------------leave on " . $value['name'] . " with box [" . $res[0] . ", " . $res[1] . "]---------------\n";
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
    
	/**
	*	requirements:
	*	this->rst : DB Datasource
	*	this->template: 
	*	$this->verification: contains description, how to use template and dbsource
	*	
	*	calls:
	*	this->deepCollect( bool, array[][], xmlElement)
	*/
	
	public function generateBranchTree()
	{

		$this->show_content();
		
		/*moveFirst*/
		if(!(is_null($this->rst) || $this->rst->moveFirst()))
		{
			//echo " no static data or recordset ";
			return '';
		}
		
		/* check verification */
		if(count($this->verification) == 0)
		{
			echo "need Infomation about branches, please use define_tag";
			return;
		}
		
		/* create an index of all grouppositions */
		// TODO check whole consistence
		
		//$hold = $this->verification[0]['group'];
		/* ------------------------------------------ groups ------------------------------------------------------*/
		$groups = array();
		/* -----------------------------------------------------------------------------------------------------------*/
		//$groups[$hold] = array(0);
		$permutation= array();

		
		// if there is no relation entry, one new will generated, based on order of Verification

		
		$tmp = array();
		$iter = -1;
		foreach ($this->verification as $key => $value) 
		{		
			
			if(!in_array($value['group'], $tmp)) 
			{
				$tmp[] =$value['group'];
				$groups[$value['group']] = array('crotch'=>null, 'branch'=>null, 'data'=>array());
			}

		}
		if(!count($this->relation))		
			foreach ($tmp as $key => $value) $this->relation[++$iter] = array('level' => $iter, 'group' => $value, 'condition' => "" );
		unset($tmp);
		
		//var_dump($groups);
		//Fehlannahme, die gruppen koennen nicht so einfach gebaut werden, weil "Verification" nicht zwingend sortiert ist
		//var_dump($this->verification);
		//$this->verification = array_sort($this->verification, 'group', SORT_ASC);
		
		for($i = 0; count($this->verification) > $i; $i++ )$permutation[$this->verification[$i ]['group']][] = $this->verification[$i ] ;

		//var_dump($permutation);
		
		$names[$this->verification[0]['name']]  = $this->verification[0]['group']; 
		for($i = 1; count($this->verification) > $i; $i++ )
		{
			$names[$this->verification[$i]['name']] = $this->verification[$i]['group'];  
			
			if($hold != $this->verification[$i]['group'])
			{
			  $hold = $this->verification[$i]['group'];
			  //$groups[$hold] = array($i);
			}
			//else
			 //$groups[$hold][] = $i;
		}	
		//ToDo for only on element in group


		$empty = $this->verification[count($this->verification) - 1 ]['group'];
		if(is_numeric($empty))
		$names[''] = $empty + 1;  
		else
		$names[''] = 1;

		asort($names);
		/*
		foreach ($names as $k => $v)
		{
			$names[$this->verification[$i]['name']] = $this->verification[$i]['group'];  
			if($hold != $this->verification[$i]['group'])
			{
			  $hold = $this->verification[$i]['group'];
			  $groups[$hold] = array($i);
			}
			else
			 $groups[$hold][] = $i;
		}	
		*/
		
/* ----------------------------------------------------------------------------------------------------------------------------
* Bucketsort
*
* -----------------------------------------------------------------------------------------------------------------------------
*/
		// Verification
		foreach ($this->verification as $key => $value) 
		{		
		
			$groups[$value['group']]['data'][] = &$this->verification[$key];

		}
		
		//level
		foreach ($this->level as $key => $value) 
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
								var_dump($groups[trim($value2)]);
							//echo "oh, doof";
							throw new Exception($value2); //$groups[trim($value2)]['branch']
							}
					}
					



		}
		
		
//var_dump($groups);


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
		//
		//TODO 
		
		$this->back->freexpathresult();
		
		/* choose page for modification */
		if(!$this->back->change_URI($this->content->get_out_template()))
		echo $new_template . ' isn\'t a available documentident';
		
	//echo $this->back->position_stamp() . "\n";		

		//$tmpName = $this->back->show_xmlelement();
	//echo $tmpName->position_stamp() . ' ' . $tmpName->name . "\n";		

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
/*
	$bucketsort = array();
	
	$lvl_description = array();
	
                foreach ($this->relation as $key => $value) 
		{	
			if(in_array($value))
			if(!is_array($lvl_description[$value['level']])) $lvl_description[$value['level']] = array();
				//$lvl_description[$value['level']][$value[]] = 
				
		echo " $key = {";
			foreach ($value as $key1 => $value1)
			echo " $key1 : $value1;";
		echo "}\n";
		*/
	//	}
//var_dump($lvl_description);


                //var_dump($this->level);
  // */             
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
			$res = $this->test_alter($tbl) ;
			$until = $this->last_col($tbl);
			
			$fulltbl[$pos++] = $this->buildRow($tbl);
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
		
		
		//var_dump($fulltbl);
		
		   	//var_dump($box);
    	// results current groupname
    		//$subBranch = &$this->back->show_xmlelement();

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
		/*
		for($i = 0; $i <  $pos;i++)
		{
				foreach($tbl as $key => $value){		
					
				//$value ($key)
			  }	
		}
		*/
                
		//$curHold[1]->giveOutOverview();
		
                /* check level */
		//TODO gruppen einzeln durchtesten und für mehrere Gruppen pro Tiefe auslegen
		/*
		if(count($groups) !== count($this->level))
		{
			echo "level und gruppenanzahl passen nicht zusammen";
			return;
		}
*/
                
                /* return to former page */
                $this->back->go_to_stamp($tmpstamp);
                
                

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
