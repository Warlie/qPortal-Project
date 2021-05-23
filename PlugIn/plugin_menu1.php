<?PHP

/**
*
*
* creates a menu
* @-------------------------------------------
* @title:DBO
* @autor:Stefan Wegerhoff
* @description: Databaseobject, needs only a columndefinition to receive data from other object
*
*/
require_once("plugin_interface.php");

class Menue extends plugin 
{

var $rst = null;
var $obj = null;
var $back =  null;
var $content = null;
var $dbencode = "ISO-8859-1";
var $myDocumentName = '';
var $node = "";
var $param = array();
var $images = array();
var $tag;
var $jump_address = array();
var $is_final = false;
var $pos = 0;
var $root = true;
var $level = array();
var $res = array();

	function __construct(/* System.Parser */ &$back, /* System.Content */ &$content)
	{
		global $_SESSION;
		
		$this->back= &$back;
		$this->content = &$content;
		
			
		

	
	/* array for name and value for next menupoints */
	
	$this->jump_address[0] = array();
	$this->jump_address[1] = array();
	
	/* generall backjump stamp */
	$stamp = $this->back->position_stamp();
	
	/* URIs for spezific trees */
	$structur = $this->content->getXMLStructur();
	$output = $this->content->get_out_template();

	/* change to control tree */
	$this->back->change_URI($structur);
	
	$stamp_structur = $this->back->position_stamp();
	
	
	$this->is_final = ($this->back->get_NS_QName() == 'final');
	if(!$this->is_final)
	{
	$this->back->parent_node();
	$this->jump_address[2] = $this->back->show_ns_attrib('http://www.trscript.de/tree#name');
	$this->jump_address[3] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
	$this->back->go_to_stamp($stamp_structur);
	}
	
	
	$many = $this->back->index_child();
	$iter = 0;
	$locked = false;
	for($i = 0; $i < $many; $i++)
	{
		
		$this->back->child_node($i);
		
		
		if($this->back->cur_node() == 'tree')
		{
		/* Collect name and value of the tree-tags */
		
		
		if ($att_sector = $this->back->show_ns_attrib('http://www.trscript.de/tree#sector'))
		{
			if (false == strpos($_SESSION['http://www.auster-gmbh.de/surface#sector'],$att_sector))$locked = true;
		}
		
		
		if ($att_security = $this->back->show_ns_attrib('http://www.trscript.de/tree#securitylevel'))
		{

		if ((intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']) < intval($att_security)) 
		&& 
		(intval($att_security) <> -1)  )
		{
		$locked = true;
		}
		
		if (($_SESSION['http://www.auster-gmbh.de/surface#securityclass']) 
		&& 
		(intval($att_security) == -1)  )
		{
		$locked = true;
		}
		
		

		}
		
		  if ( !(false === ($hidden = strpos( $this->back->show_ns_attrib('http://www.trscript.de/tree#name'), '.' ) ) )
		   && $hidden < 2)$locked = true;
		
		
			if(!$locked)
			{
			$this->jump_address[0][$iter] = $this->back->show_ns_attrib('http://www.trscript.de/tree#name');
			$this->jump_address[1][$iter++] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
			}
		}
		
		$locked = false;
		$this->back->parent_node();
		
	}

	$this->back->go_to_stamp($stamp_structur);
	$this->back->go_to_stamp($stamp);
	


		
	}
	
	public function col($columnname)
	{
	
	//echo "\nMenu.col[" . $this->pos . "](" . $columnname . ") -> '" . $this->res[$this->pos][$columnname] .  "' (" . count($this->res) . ")\n";
	
	return $this->res[$this->pos][$columnname];
	/*
		$type = 0;
		
		
		if($columnname == 'value')$type = 1;	
	
		if($columnname == 'URI')
		{
		$add_SID = '';
		if(false)$add_SI = 'PHPSESSID=' . htmlspecialchars(session_id()) . '&';
		
		if(count($this->jump_address[$type]) > $this->pos)
		return '?' . $add_SI . 'i=' . $this->jump_address[0][$this->pos];
		elseif(count($this->jump_address[$type]) == $this->pos)
		return '?' . $add_SI . 'i=' . $this->jump_address[$type + 2];
		}
		

		
		if(count($this->jump_address[$type]) > $this->pos)
		  return $this->jump_address[$type][$this->pos];
		elseif(count($this->jump_address[$type]) == $this->pos)
		  return $this->jump_address[$type + 2];
	
	  return null; */
	}
	
	
	function use_document($documentName)
	{
		$this->documentForInsert = $documentName;
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	
	function void_root(){$this->root  = false;}
	
	function show_root(){$this->root  = true;}
	
	function setLevelName( $name, $url ) 
	{
	  $this->level[] = array('Name' => $name, 'URL' => $url);
	}
	
	private function find_line_start(&$arr, $deep = 0)
	{
		$many = $this->back->index_child();
	$iter = 0;
	$locked = false;
	for($i = 0; $i < $many; $i++)
	{
		
		$this->back->child_node($i);
		
		//echo $this->back->show_ns_attrib('http://www.trscript.de/tree#name') . "\n";
		if($this->back->cur_node() == 'tree')
		{
		/* Collect name and value of the tree-tags */
		
		
		if ($att_sector = $this->back->show_ns_attrib('http://www.trscript.de/tree#sector'))
		{
		if (false === strpos($_SESSION['http://www.auster-gmbh.de/surface#sector'],';' . $att_sector . ';' ))
		{
			//echo "kicked(sector)\n";
			$locked = true;
		}
		}
		
		
		if ($att_security = $this->back->show_ns_attrib('http://www.trscript.de/tree#securitylevel'))
		{

		if ((intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']) < intval($att_security)) 
		&& 
		(intval($att_security) <> -1)  )
		{
			//echo "kicked(<)\n";
		$locked = true;
		}
		
		if (($_SESSION['http://www.auster-gmbh.de/surface#securityclass']) 
		&& 
		(intval($att_security) == -1)  )
		{
			//echo "kicked(sec -1)\n";
		$locked = true;
		}
		}

		

		
		if ( !(false === ($hidden = strpos( $this->back->show_ns_attrib('http://www.trscript.de/tree#name'), '.' ) ) )
		&& intval($hidden) == 0 )
		{

//echo "kicked (.)\n";
		$locked = true;
		
		
		}
		
		if(!$locked)
		{ 
		  $deep++;
		
		 //echo $this->back->show_ns_attrib('http://www.trscript.de/tree#name') . " is saved{\n";
		    $arr[] = array('stamp' => $this->back->position_stamp(), 'deep' => $deep);
		    //var_dump($arr);
		    	//echo "}\n";
		  
		  if(!($this->back->index_child() == 0 || count($this->level) == ($deep + 1) ))$this->find_line_start($arr,$deep);

		   $deep--;
		}
		else
		{

		//echo $this->back->show_ns_attrib('http://www.trscript.de/tree#name') . " is locked{\n";
		    
		   // var_dump($arr);
		    //	echo "}\n";
		}
		
				$locked = false;
		
		}
		
		$this->back->parent_node();
	}

	}
	
	private function &collect_lines($stamp, $deep)
	{
	
	$res = array();
	
	for($i = count($this->level) - 1; $i >= 0; $i--)
	{
    
         
	  if($i > $deep)
	  {
	    $res[$this->level[$i]['URL']] = null;
	    $res[$this->level[$i]['Name']] = null;
	  }
	  if($i == $deep)
	  {
	   $this->back->go_to_stamp($stamp);
	    $res[$this->level[$i]['URL']] = str_replace('%s', 
	    $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),STD_URL);
	    $res[$this->level[$i]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
	
	  }
	  if($i < $deep)
	  {
	    $this->back->parent_node();
	    $res[$this->level[$i]['URL']] = str_replace('%s', 
	    $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),STD_URL);
	    // $this->back->show_ns_attrib('http://www.trscript.de/tree#name');
	    $res[$this->level[$i]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
	  }
	
	}
	
	 return $res;
	}
	
	public function collect_Content() 
	{
	
	global $_SESSION;
		
	/* generall backjump stamp */
	$stamp = $this->back->position_stamp();
	
	/* URIs for spezific trees */
	$structur = $this->content->getXMLStructur();
	$output = $this->content->get_out_template();

	/* change to control tree */
	$this->back->change_URI($structur);
	
	$stamp_structur = $this->back->position_stamp();
	
	$this->is_final = ($this->back->get_NS_QName() == 'final');
	if(!$this->is_final)
	{
	$this->back->parent_node();
	$this->jump_address[2] = $this->back->show_ns_attrib('http://www.trscript.de/tree#name');
	$this->jump_address[3] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
	$this->back->go_to_stamp($stamp_structur);
	}
	/* TODO optional selection for menue-start */
	$this->back->set_first_node();
	$this->back->child_node(0);
	
       $arr = Array();
       $this->res = Array();
       $this->find_line_start($arr);
      // var_dump($arr);
       for( $i = 0; $i < count($arr); $i++)
       $this->res[] = &$this->collect_lines($arr[$i]['stamp'], $arr[$i]['deep']);
       //var_dump($this->res);


	$this->back->go_to_stamp($stamp_structur);
	$this->back->go_to_stamp($stamp);
	

	}
	
	function build_menu()
	{
	
	global $_SESSION;
		
	/* generall backjump stamp */
	$stamp = $this->back->position_stamp();
	
	/* URIs for spezific trees */
	$structur = $this->content->getXMLStructur();
	$output = $this->content->get_out_template();

	/* switches to output tree */
	$this->back->change_URI($output);
	
	$stamp_template = $this->back->position_stamp();
	
	$add_SID = '';
	if(true)$add_SI = 'PHPSESSID=' . htmlspecialchars(session_id()) . '&';
	
	for($i = 0; $i < count($this->jump_address[1]); $i++)	
	{
		//if(substr(c[$i],0,1) <> '.')
		//{
		$this->back->create_Ns_Node('a', $stamp_template, array('href' => '?' . $add_SI . 'i=' . $this->jump_address[0][$i]) );
		$this->back->set_node_cdata($this->jump_address[1][$i],0);
		$this->back->parent_node();
		$this->back->create_Ns_Node('span', $stamp_template, array() );
		$this->back->set_node_cdata(' ',0);
		$this->back->parent_node();
		//}
	}
	
	if(!$this->is_final)
	{
		
		
		$this->back->create_Ns_Node('a', $stamp_template, array('href' => '?i=' . $this->jump_address[2]) );
		$this->back->set_node_cdata($this->jump_address[3],0);
	}
	
	
	//$res = $this->back->cur_node();
	
	$this->back->go_to_stamp($stamp_template);
	$this->back->go_to_stamp($stamp);
	
	return '';
	
	}
	
	
	function getAdditiveSource(){;}
	protected function moveFirst(){$this->pos = 0;return true;}
    	protected function moveLast(){$this->pos = count($this->res)-1 ;return true;}
    	
    	public function many(){return count($this->res);}
	//public function next(){return (($this->pos < count($this->res)-1) && !$this->pos++);}
    	public function next()
    	{
    	if($this->pos < count($this->res)-1)
    	{
    	//echo "ich mache weiter";
    	$this->pos++;
    	return true;
    	
    	} 
    	else
    	{
    	//echo "bin raus";
    	return false;
    	}
    	
    	}
    	
    	public function set_list(&$value){;}
	

}
?>
