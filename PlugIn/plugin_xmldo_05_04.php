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

class XMLDO extends plugin 
{
private $test = 0;
private $rst;
private $pos = 0;
private $template;
private $content;
private $documentForInsert;
private $verification = array();
private $level = array();
private $strucEl = array();
private $list = array();
private $emptyText = 'kein Datensatz';

	function __construct(/* System.Parser */ &$back, /* System.CurRef */ &$treepos, /* System.Content */ &$content)
	{
		$this->back= &$back;
		$this->treepos = &$value;
		$this->content = &$content;
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
		if(!$this->back->change_URI($this->content->get_template($new_template)))
		{
		echo $new_template . ' isn\'t a available documentident (setXMLTemplate) ';
		$this->back->test_consistence();
		}
		
		$this->back->set_first_node();
		
		$this->back->complete_list(true);
		$this->back->cloneResult(true);
		$this->back->xpath($use_at_tag);
		$this->back->cloneResult(false);
		$this->documentForInsert = &$this->back->get_xpath_Result();

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
		$this->verification[$cur_pos]['group'] = intval($group);  /* to concern several columns into groups */
		$this->verification[$cur_pos]['branch'] = intval($branch);  /* number of the child at the rootnode */
	}
	
	
		/*
		if($type == "TAG_OUT")
		{
			
			$this->cur = '';
			
			
		}
		if($type == "XPATH")
		{
			
			$this->tag[$this->cur]['xpath']=$value;
			
			
		}
		if($type == "ATTRIB")
		{
			
			$this->tag[$this->cur]['pos']='ATTRIB';
			$this->tag[$this->cur]['name']=$value;
			
		}
		if($type == "DATA")
		{
			//echo 'boooh' . $this->cur;
			$this->tag[$this->cur]['pos']='DATA';
			
			
		}
		if($type == "CONTENT")
		{
			
			$this->tag[$this->cur]['content']=$value;
			
			
		}
		if($type == "VALUE")
		{
			//$this->tag[$this->cur]['pos']='VALUE';
			$this->tag[$this->cur]['value']=$value;
			
			
		}

*/
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

	public function setCrotch( $child_pos, $xpath, $group)
	{
	  $this->level[] = array('type'=> 0, 'child_pos' => intval($child_pos),  'xpath' => $xpath, 'group' => $group);
	
	}
	
	public function setBranch($deep, $child_pos, $xpath, $group)
	{
	  $this->level[] = array('type'=> 1, 'child_pos' => intval($child_pos),  'xpath' => $xpath, 'group' => $group);
	}
	
	
	/**
	* deepCollect
	* @param finish [bool] (byRef) : terminates processing immediately
	* @param groups [array[int][int]] (byRef) : elementposition in template
	* @param node [ns_interface] (byRef) : parentelement to append new nodes
	* @param deep [int] optional(0) : shows the level of rekursion
	* 
	* 
	*/ 


	private function deepRoot(&$finish, &$groups, &$node, $deep = 0)
	{

	$param_node = &$this->list[$this->level[$deep]['child_pos']]->cloning($node);

	}
	
	//unbenutzt
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
		    
		   // echo "schreibe $datarec !\n";
	   
          $note_use->setdata($datarec,$tmp);
          $note_use->set_bolcdata($this->cdata);

          unset($tmp);
	  unset($datarec);
	}	
	
		//unbenutzt
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
	
	echo " prepost " . $prefix . ":" . $postfix . "--";
	
	if(!is_null($this->rst))$datarec = $this->rst->col($this->verification[$verificationNum]['name']);

	$myattrib->setdata($datarec,0);
	
	unset($datarec);
	$note_use->attribute($prefix . $postfix, $myattrib);
	
	  unset($myattrib);
	  
	

	}	
	
	//unbenutzt
	private function setData2( &$groups, &$node, $deep, $pos)
	{
	  $tmp = $node[count($node) - 1]->index_max();
		
	  if(!is_null($this->rst))$datarec = $this->rst->col($this->verification[$groups[$deep][$pos]]['name']);
		    
		   // echo "schreibe $datarec !\n";
          $node[count($node) - 1]->setdata($datarec,$tmp);
          $node[count($node) - 1]->set_bolcdata($this->cdata);
	  unset($tmp);
	  unset($datarec);
	}	
	//unbenutzt
	private function setAttrib2( &$groups, &$node, $deep, $pos)
	{
	
	
/*
				if(!$this->verification[$i]['prefix'] )
				{
					
					
					$myattrib = &$this->back->get_Object_of_Namespace( $this->content->get_Main_NS() . '#' . 
					  $this->verification[$cur_pos]['postfix'] );
					  
					$tmp = $this->rst->col($this->verification[$i]['name']);				
					$myattrib->setdata($tmp,0);
					unset($tmp);
					$obj_ref[count($obj_ref) - 1]->attribute($this->content->get_Main_NS() . 
					'#' . $this->verification[$i]['postfix'], $myattrib);
					unset($myattrib);
					
					
				}
				else
				{
				

					$attrib = &$this->back->get_Object_of_Namespace(  
					  $this->verification[$i]['prefix'] . '#' . $this->verification[$i]['postfix'] );
						
					$tmp = $this->rst->col($this->verification[$i]['name']);
					
					$attrib->setdata($tmp,0);
					
					//$attrib->setdata($tmp,0);
					
					
				        
				
											
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
	
*/

	$postfix = $this->verification[$groups[$deep][$pos]]['postfix'];
	
	if(!$this->verification[$groups[$deep][$pos]]['prefix'] )
	$full_uri = ($prefix = $this->content->get_Main_NS()) . '#' . $postfix;
	else
	$full_uri = ($prefix = $this->verification[$i]['prefix']) . '#' . $postfix ;
	
	$prefix = $this->back->get_Prefix($prefix,$node[count($node) - 1]->get_idx());

	if( strlen( $prefix ) == 0 )
	  $prefix .= $prefix;
	else
	  $prefix .= $prefix . ':';
	
	
	
	
	$myattrib = &$this->back->get_Object_of_Namespace( $full_uri );
	
	//echo " prepost " . $prefix . ":" . $postfix . "--";
	
	if(!is_null($this->rst))$datarec = $this->rst->col($this->verification[$groups[$deep][$pos]]['name']);

	$myattrib->setdata($datarec,0);
	
	unset($datarec);
	$node[count($node) - 1]->attribute($prefix . $postfix, $myattrib);
	
	  unset($myattrib);
	  
	
/*				if(!$this->verification[$groups[$deep][$pos]]['prefix'] )
				{
					
					
					$myattrib = &$this->back->get_Object_of_Namespace( $this->content->get_Main_NS() . '#' . 
					  $this->verification[$groups[$deep][$pos]]['postfix'] );
					  
					$tmp = $this->rst->col($this->verification[$i]['name']);				
					$myattrib->setdata($tmp,0);
					unset($tmp);
					$obj_ref[count($obj_ref) - 1]->attribute($this->content->get_Main_NS() . 
					'#' . $this->verification[$i]['postfix'], $myattrib);
					unset($myattrib);
					
					
				}
				else
				{
				

					$attrib = &$this->back->get_Object_of_Namespace(  
					  $this->verification[$i]['prefix'] . '#' . $this->verification[$i]['postfix'] );
						
					$tmp = $this->rst->col($this->verification[$i]['name']);
					
					$attrib->setdata($tmp,0);
					
					//$attrib->setdata($tmp,0);
					
					
				        
				
											
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
		

		unset($datarec);
*/
	}	
	
	
	/**
	* deepCollect
	* @param finish [bool] (byRef) : terminates processing immediately
	* @param groups [array[int][int]] (byRef) : elementposition in template
	* @param node [ns_interface] (byRef) : parentelement to append new nodes
	* @param deep [int] optional(0) : shows the level of rekursion
	* 
	* 
	*/ 


	private function deepCollect(&$finish, &$groups, &$node, $deep = 0)
	{
	
	 $sec = 0;
	 $hold = array();
	 $branch = 0;
	 $compare = true;
	 $new_node = null;
	 $param_node = null;
	 $obj_ref = null; //for value
	 $cur_group = $this->level[$deep]['group'];

	 /* ----------------checks the level to be valid------------------- */ 
	 
	/* collects label and href in hold */
	 for($i = 0;$i < count($groups[$deep]); $i++)
	 	 $hold[$i] =  $this->rst->col( $this->verification[$groups[$deep][$i]]['name']);
	 	 
	/* choose branch in template for this level */
	$branch = $this->verification[$groups[$deep][0]]['branch'];
	
	$this->back->freexpathresult();
	
	$param_node = &$node;  

	//echo " deep $deep, branch $branch, groups: ";
	
	//var_dump($groups);
	//echo "\n verification \n";
	//var_dump($this->verification);
	
	// Moeglicher Fehler, hier wird nicht ul, sondern li angefügt
	$new_node = &$this->list[$branch]->cloning($param_node);

	//echo "\n element zum arbeiten \n"; 
	//$new_node->giveOutOverview();

	/* new_node contains a xmlelement as described in groups */
	for($i = 0;$i < count($groups[$deep]); $i++)
	{
	        
	        if($this->back->freexpath($this->verification[$groups[$deep][$i]]['xpath'], $new_node) == 0)
	        {
	        	$obj_ref2 = array();
	        	$obj_ref2[] = &$new_node;
	        }
	        else
	        {
	        	$obj_ref2 = &$this->back->get_xpath_Result();
	        	$this->back->free_xpath_Result();
	        } 
	          
		
		
               
                
		  
		if($this->verification[ $groups[$deep][$i] ]['attrib_data'] == 'data')
		{

		  	  $this->setData2( $groups, $obj_ref2, $deep,$i);
		
		}
		elseif($this->verification[$groups[$deep][$i] ]['attrib_data'] == 'attrib')
		{

			   $this->setAttrib2( $groups, $obj_ref2, $deep, $i);
		}
		unset($obj_ref2);
	}
		

	  

	/* if current deep is the deepest or next level contains no entries, next recordset will be selected */
	if((deep + 1) == count($groups)  // $deep + 1
	|| $this->rst->col($this->verification[$groups[$deep + 1][0]]['name']) == null )
	{
		$finish = !$this->rst->next();
		return;
	}
	
	$this->back->freexpathresult();
	
	/*  */
	
	//echo "Watch level (deep is $deep)  \n";
	//var_dump($this->level);
	
	if($this->back->freexpath($this->level[$deep]['xpath'], $new_node) == 0)
	{
		$obj_ref2 = array();
		$obj_ref2[] = &$new_node;
		
		//$new_node->giveOutOverview();
		$exc_str = " " . $this->level[$deep]['xpath'] . " was not found in deep $deep ";
		throw new Exception($exc_str);

	}
	else
	{
		$obj_ref2 = &$this->back->get_xpath_Result();
		$this->back->free_xpath_Result();
	} 
	          

	$next_root_node = &$obj_ref2[0];

	unset($obj_ref2);

	$jump = "\n";
	$next_root_node->setdata($jump,0);
	unset($jump);
		//echo "<b>next level </b><br>\n";
	    while( !$finish )
	    {
	    
		      $this->deepCollect($finish, $groups, 
		      $next_root_node /*$new_node */, 
		      $deep + 1);
	    
	    
	      //count($groups[$deep])
	 	for($i = 0;$i < 1; $i++)
	 	{
	 	 
//	 	 echo "<b>out </b>" .  $hold[$i] . " != " .  $this->rst->col( $this->verification[$groups[$deep][$i]]['name']) . "<br>\n";
	 	
	   		if($hold[$i] !=  $this->rst->col( $this->verification[$groups[$deep][$i]]['name']))
	   		{
	   	        unset($new_node);
	    	        unset($next_root_node);
			return;
	    		}
	    	}

	    }
	    	unset($new_node);
	    	unset($next_root_node);
//		echo "<b>out </b><br>\n";
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

		  	  	$tmp = $this->rst->col( $key);
		  	  	
				if($bool && $bool = (strcmp($tmp, $value) == 0))$res = $key;
				
				$tbl[$key] = $tmp;
		  	  	  
			  }	
		  return $res;
	}

	private function last_col(&$tbl)
	{

		$tmp = '';
		  if(is_array($tbl))     
		  	  foreach($tbl as $key => $value){


		  	  	
				if($value == null)return $key;
				$tmp = $key;
		  	  	  
			  }	
		  return $tmp;
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
                		echo "bad xpath: $xpath \n";
                		return $Root;
                	}
    }
    
       	private function &build_element($xpath, &$Node,  &$Root )
	{
		
		
			if($this->back->freexpath($xpath, $Root) != 0)
                	{

                		$obj_ref_array = &$this->back->get_xpath_Result();
                		
                		$this->back->free_xpath_Result();
                		$res =  $Node->cloning($obj_ref_array[0]);
                		unset($obj_ref_array);
                		return $res;
                	}
                	else
                	{
                		echo "bad xpath: $xpath \n";
                		return $Root;
                	}
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

		
		
		/*moveFirst*/
		if(!(is_null($this->rst) || $this->rst->moveFirst()))
		{
			echo " no static data or recordset ";
			return;
		}
		
		/* check verification */
		if(count($this->verification) == 0)
		{
			echo "need Infomation about branches, please use define_tag";
			return;
		}
		
		/* create an index of all grouppositions */
		// TODO check whole consistence
		
		$hold = $this->verification[0]['group'];
		$groups = array();
		$groups[$hold] = array(0);
		$names[$this->verification[0]['name']]  = $this->verification[0]['group']; 
		
		for($i = 1; count($this->verification) > $i; $i++ )
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
		

		//TODO bugfix Wenn sich der zweite Eintrag einer gruppe geaendert hat, stimmt die gruppe nicht
		$tbl = array();

		$this->create_tbl($tbl);
		
		$this->rst->moveFirst();
$res = "";
$until = "";
		var_dump($tbl);




			
		/* stamp for return */
		$tmpstamp = $this->back->position_stamp();

		/* choose page for modification */
		if(!$this->back->change_URI($this->content->get_out_template()))
		echo $new_template . ' isn\'t a available documentident';
		
		$tmpName = $this->back->show_xmlelement();

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
		//----------------------------------------------------------------------
		/* create list of nodes */
		//for($i = 0; count($this->level) > $i; $i++ )
		//{
		//  $this->back->child_node($this->level[$i]['child_pos']);
		//  $this->level[$i]['element']= $this->back->show_xmlelement();
		 // $this->back->parent_node();
		//}		
		//----------------------------------------------------------------------
		
		//TODO feld erstellen, das der Menge der Gruppen entspricht
		//
		//TODO 
		
		$this->back->freexpathresult();

			
                var_dump($this->verification);
                echo "\n groups \n";
                var_dump($groups);
                echo "\n level \n";
                var_dump($this->level);
                
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
					var_dump($groups);
					
	$curHold = array();
	//$curHold[0] = &
	/*
	                	if($this->back->freexpath($this->level[0]['xpath'], $this->list[$this->level[0]['child_pos']]->cloning($tmpName)) != 0)
                	{
	
                		$obj_ref_array = &$this->back->get_xpath_Result();
                		
                		$this->back->free_xpath_Result();
                		$root =  $obj_ref_array[0];
                		unset($obj_ref_array);
                	}
                	else
                	{
                		echo "bad xpath" . $this->level[0]['xpath'] . " ";
                		return;
                	}
*/	
	$xpath = "";
	$pos = 0;
	$prev_group = 1;
				
	
		echo "Uebernehme Gabel \n";
		echo " (1) Richte neuen Knoten ein (pos:" .  $groups[1]['crotch']["child_pos"] . "/xpath:" . $groups[1]['crotch']["xpath"] . ")\n";
		$curHold[1] = &$this->build_root($groups[1]['crotch']["xpath"], 
			$this->list[$groups[1]['crotch']["child_pos"]] ,  
			$tmpName );
		
		do{
			
			$res = $this->test_alter($tbl) ;
			$until = $this->last_col($tbl);
			
			
			foreach($tbl as $key => $value){
				if(strcmp($key, $res) == 0)
				echo " -$value- ($key); ";
				else
				echo " $value ($key); ";		  	  	  
			  }	
			
		echo "\n" ;
		echo " $res " . $names[$res] . " $until " . $names[$until] . "\n";
		
		
		$prev_group = $names[$res] + 1;

		for($k = $names[$res] + 1 ; $names[$until] - 1 >= $k; $k++ )
		{

		if($prev_group < $k)
		{
			echo "Erstelle neuen crotch \n";
			unset($curHold[$k]);
			
			$curHold[$k] = &$this->build_element(
				$groups[$prev_group]['branch']["xpath"], 
				$this->list[$groups[$k]['crotch']["child_pos"]], 
				$curHold[$prev_group]); 
			echo " ($k) Verbindung an (pos:" .  $groups[$prev_group]['branch']["child_pos"] . "/xpath:" . $groups[$prev_group]['branch']["xpath"] . ")\n";
			
			/*
			$curHold[1] = &$this->build_element($groups[1]['crotch']["xpath"], 
			$this->list[$groups[1]['crotch']["child_pos"]] ,  
			$tmpName );
			*/
		}	
							
		//echo " ($k) Richte neuen Knoten ein (pos:" .  $groups[$k]['branch']["child_pos"] . "/xpath:" . $groups[$k]['branch']["xpath"] . ")\n";
		
		$cur_branch = &$this->build_element(
				$groups[$k]['crotch']["xpath"], 
				$this->list[$groups[$k]['branch']["child_pos"]], 
				$curHold[$k]);
		
		
			for($l = $groups[$k][0]; $groups[$k][1] >= $l; $l++ )
			{
				

				//echo  " Name:" . $this->verification[$l]['name'] . " xpath:" .  $this->verification[$l]['xpath'] . "\n";

				//echo " $l of group $k to add \n";
				
		if($this->verification[$l]['attrib_data'] == 'data')
		{
			echo "insert Data\n";
			$this->setData( $cur_branch, $l , 0);
			/*
		$tmp = $obj_ref[count($obj_ref) - 1]->index_max();
		
		if(!is_null($this->rst))$datarec = $this->rst->col($this->verification[$i]['name']);
		$obj_ref[count($obj_ref) - 1]->setdata($datarec,$tmp);
		$obj_ref[count($obj_ref) - 1]->set_bolcdata($this->cdata);
		unset($tmp);
		unset($datarec);
		*/
		}elseif($this->verification[$l]['attrib_data'] == 'attrib')
		{
			$this->setAttrib( $cur_branch, $l );

				}
				
			}
			
		}
		
//		if(($res = $this->test_alter($tbl)) != '')
//			echo $res . " " . $names[$res] . "\n";
		


		}
		while($this->rst->next());
                
                /* check level */
		//TODO gruppen einzeln durchtesten und für mehrere Gruppen pro Tiefe auslegen
		if(count($groups) !== count($this->level))
		{
			echo "level und gruppenanzahl passen nicht zusammen";
			return;
		}

                return;
		
                if($this->level[0]['child_pos'] !== -1)
                {
                	if($this->back->freexpath($this->level[0]['xpath'], $this->list[$this->level[0]['child_pos']]->cloning($tmpName)) != 0)
                	{
	
                		$obj_ref_array = &$this->back->get_xpath_Result();
                		
                		$this->back->free_xpath_Result();
                		$root =  $obj_ref_array[0];
                		unset($obj_ref_array);
                	}
                	else
                	{
                		echo "bad xpath" . $this->level[0]['xpath'] . " ";
                		return;
                	}
                	
                }
                else
                	$root = &$tmpName;

                	
		/* rekursive insert for all level */
                $bool = false;
                
                //var_dump($this->verification);
                /* builds the tree */
                //while(!$bool)
                //	$this->deepCollect($bool, $groups, $root);
                
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
		
		for($i = 0;$i < $many; $i++)
			$this->documentForInsert[0]->cloning($this->back->show_xmlelement());
		}

	  $this->back->go_to_stamp($tmpstamp);
	
	}
		
	public function generateTagTree()
	{
		if(is_null($this->rst) || $this->rst->moveFirst())
		{
		
		
		$tmpstamp = $this->back->position_stamp();
		
		if(!$this->back->change_URI($this->content->get_out_template()))
		echo $new_template . 'isn\'t a available documentident (generateTagTree) ';
		
		$tmpName = $this->back->show_xmlelement();
		
		if(count($this->documentForInsert) > 0)
		{
	
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
		$obj_ref = &$this->back->get_xpath_Result();
		
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
		//TODO aenderung fuer bereits besehende Attribute
				if(!$this->verification[$i]['prefix'] )
				{
					
					
					$myattrib = &$this->back->get_Object_of_Namespace( $this->content->get_Main_NS() . '#' . 
					  $this->verification[$cur_pos]['postfix'] );
					  
					$tmp = $this->rst->col($this->verification[$i]['name']);				
					$myattrib->setdata($tmp,0);
					unset($tmp);
					$obj_ref[count($obj_ref) - 1]->attribute($this->content->get_Main_NS() . 
					'#' . $this->verification[$i]['postfix'], $myattrib);
					unset($myattrib);
					
					
				}
				else
				{
				

					$attrib = &$this->back->get_Object_of_Namespace(  
					  $this->verification[$i]['prefix'] . '#' . $this->verification[$i]['postfix'] );
						
					$tmp = $this->rst->col($this->verification[$i]['name']);
					
					$attrib->setdata($tmp,0);
					
					//$attrib->setdata($tmp,0);
					
					
				        
				
											
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
