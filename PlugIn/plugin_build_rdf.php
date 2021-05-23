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

class Build_RDF extends plugin 
{
private $test = 0;
private $rst;
private $pos = 0;
private $template;
private $content;
private $documentForInsert;
private $field;
private $ref_Name;
private $sparql;

	function Build_RDF(/* System.Parser */ &$back, /* System.CurRef */ &$treepos, /* System.Content */ &$content)
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
		
		if(!$this->back->change_URI($this->content->get_template($new_template)))
		echo $new_template . 'isn\'t a available documentident';
		
		
		
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
	public function data_field($name)
	{	
		$this->field = $name;
	}
	
	public function data_ref_name($name)
	{	
		$this->ref_Name = $name;
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
		
	public function generateRDFDokument()
	{
	$tmp = "";
		if(is_null($this->rst))return false;
		
		
		if( $this->rst->moveFirst())
		{
		
		

		//$this->back->use_ns_def_strict(true);
		do {
		//echo $this->rst->col($this->field);
		
		$this->back->load_Stream($this->rst->col($this->field),0,'',$this->rst->col($this->ref_Name));
		
		} while ( $this->rst->next() );
		
		//$this->back->use_ns_def_strict(false);
		
		
		
		}

	}
	
	public function mergeRDFDokument($uri,$pre,$post)
	{
	$res = "";
	$tmp;
		
		if(is_null($this->rst))return false;
		
		
		if( $this->rst->moveFirst())
		{
		
		

		//$this->back->use_ns_def_strict(true);
		do {
		//echo $this->rst->col($this->field);
		if($tmp = $this->rst->col($this->field))
		$res .= "\n" . $tmp;
		
		
		} while ( $this->rst->next() );
		
		$this->back->setNewTree($uri);
		
		$this->back->load_Stream($pre . $res . $post,0,'XML',$uri);
		
		
		$this->content->set_template($uri,$uri);
		
		
		//$this->back->ALL_URI();
		//$this->back->use_ns_def_strict(false);
		
		
		
		}

	}
	
	
	
	public function sparql($statement,$name_to_idx)
	{
		$cur_idx = $this->back->cur_idx();
		$this->back->change_URI($this->content->get_template($name_to_idx));
		$this->back->sparql_command($statement);
		$this->sparql = &$this->back->sparql_result();
		var_dump($this->sparql->db_field_list());
		$this->back->change_idx($cur_idx);
		
		echo $this->sparql->getCSV();
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
