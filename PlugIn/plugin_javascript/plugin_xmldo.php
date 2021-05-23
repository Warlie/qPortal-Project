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



class XMLDO extends plugin 
{
private $test = 0;
private $rst;
private $template;

	function XMLDO(/* System.Parser */ &$back, /* System.CurRef */ &$treepos)
	{
		
		$this->back= &$back;
		$this->treepos = &$value;
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
	*@function: HAS_TAG = returns a boolean value refering to the seeking tag, descripted by xpath 
	* TODO muss auf xpath erweitert werden
	*/
	public function has_Tag()
	{
	
	if(is_null($this->template))
	{
		return 'false';
	}
	
			
	$tmpstamp = $this->generator()->XMLlist->position_stamp();
				
	$this->generator()->XMLlist->change_URI($this->template);
	$this->generator()->XMLlist->set_first_node();
					//$generator->XMLlist->cur_idx(). "id \n";
					
					//$generator->XMLlist->seek_node($this->tag[$this->order[$i]]['xpath']);
	$orginal = &$this->generator()->XMLlist->show_xmlelement();
	$clone = &$this->find($orginal,$value);
	if(is_null($clone->name) )
	{
		$mytemp = "false";
	}
	else
	{
		$mytemp = "true";
	}	
		
	$generator->XMLlist->go_to_stamp($tmpstamp);
	return $mytemp;
	}
	
	/**
	*@parameter: LIST = gets an object to receive data
	*/
	public function set_list(&$value)
	{
			//echo 'booh';
	if(is_object($value))
	{
		$this->rst = &$value;
	}
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
	*
	*/
	public function setXMLTemplate($new_template)
	{
	//echo get_Class($this->back) . 'xxxx';
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
	public function setTag($name, $xpath, $attrib, $data, $content, $value)
	{	
		if($type == "TAG_IN")
		{
			
			$this->cur = $value;
			$this->order[count($this->order)] = $value;
			
		}
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
		
	public function __toString(){return 'xmldo';}	
}

?>
