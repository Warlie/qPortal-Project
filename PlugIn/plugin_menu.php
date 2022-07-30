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
private $goback  = false;
var $res = array();
private $logout_url = '';
private $logout = true;

	/**
	* @param $back get reference to container with all trees
	* @param $content get reference to the Content class, which handles control 
	*
	* jump_address shows a two dim array of its surroundings like
	* 
	array(4) {
  [0]=>
  array(2) {
    [0]=>
    string(14) "sub_name_1"
    [1]=>
    string(15) "sub_value_1"
  }
  [1]=>
  array(2) {
    [0]=>
    string(14) "sub_name_2"
    [1]=>
    string(7) "sub_value_2"
  }
  [2]=>
  string(3) "sur_name"
  [3]=>
  string(3) "sur_value"
}
	*/
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
	
	/* save a specific posstamp */
	$stamp_structur = $this->back->position_stamp();
	
	/* differences between the final and the tree tags */
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
		
		$locked = !$this->content->getAccess();
		
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
	


	//var_dump($this->jump_address);
	}
	
	/**
	* @param $columnname 
	* @see plugin
	*/
	public function col($columnname)
	{
	
	//echo "\nMenu.col[" . $this->pos . "](" . $columnname . ") -> '" . $this->res[$this->pos][$columnname] .  "' (" . count($this->res) . ")\n";
	
	return $this->res[$this->pos][$columnname];

	}
	
	
	function use_document($documentName)
	{
		$this->documentForInsert = $documentName;
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	* @see plugin
	*/
	public function &iter()
		{
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	
	function void_root(){$this->root  = false;}
	
	function show_root(){$this->root  = true;}
	
	function back_in_tree($name, $url)
	{

		if(!$name && !$url)
		{
			$this->goback = true;
		}
		else
		{
			$this->goback = array();
			if($name)$this->goback['Name'] = $name;
			if($url)$this->goback['URL'] = $url;

		}
	}
	
	function setLevelName( $name, $url) 
	{
	  $this->level[] = array('Name' => $name, 'URL' => $url);
	}
	//URL
	function addLogout($url)
	{
		if(isset($url)) $this->logout_url = $url;
		$this->logout = true;
	}
	
	function voidLogout($url)
	{
		$this->logout = false;
	}
	
	function add_logout_line()
	{
       $tmp = Array();
       
       	for($i = count($this->level) - 1; $i > 1; $i--)
       	{
	    $tmp[$this->level[$i]['URL']] = null;
	    $tmp[$this->level[$i]['Name']] = null;
	    }
       
	$tmp[$this->level[0]['URL']] = str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),STD_URL);
	$tmp[$this->level[0]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
       $tmp[$this->level[1]['Name']] = 'abmelden';
       $tmp[$this->level[1]['URL']] = str_replace('%s', '__system&modus=LOG_OUT& URL=' . $this->logout_url,STD_URL);;
       //& URL=
       	return $tmp; 
       
	}
	
	private function add_goback_line()
	{
		
       $tmp = Array();
       
       if(!$this->goback)return $tmp;
       
       	for($i = count($this->level) - 1; $i > 1; $i--)
	{
	    $tmp[$this->level[$i]['URL']] = null;
	    $tmp[$this->level[$i]['Name']] = null;
	}
       
	$tmp[$this->level[0]['URL']] = str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),STD_URL);
	$tmp[$this->level[0]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');

	$stamp = $this->back->position_stamp();
	$this->back->parent_node();
	
	//if($this->goback)
	//{

	$tmp[$this->level[1]['URL']] = ($this->goback['URL'] ? $this->goback['URL'] : str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),STD_URL)); //str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),STD_URL);
	$tmp[$this->level[1]['Name']] = ($this->goback['Name'] ? $this->goback['Name'] : 'zurück');
	
	//var_dump($tmp);
	//}
       //& URL=
       
	$this->back->go_to_stamp($stamp);
       
       	return $tmp; 
       
	}
	
	private function find_line_start(&$arr, $deep = 0)
	{
		$many = $this->back->index_child();
		//echo $this->back->show_ns_attrib('http://www.trscript.de/tree#name') . "\n";
	$iter = 0;
	$locked = false;
	for($i = 0; $i < $many; $i++)
	{
		
		$this->back->child_node($i);
		
		//echo $this->back->show_ns_attrib('http://www.trscript.de/tree#name') . "\n";
		if($this->back->cur_node() == 'tree')
		{
		/* Collect name and value of the tree-tags */
		
		/*
		if ($att_sector = $this->back->show_ns_attrib('http://www.trscript.de/tree#sector'))
		{
		if (false === strpos($_SESSION['http://www.auster-gmbh.de/surface#sector'],$att_sector )) //';' . $att_sector . ';' 
		{
			//echo $_SESSION['http://www.auster-gmbh.de/surface#sector'] . " ";
			//echo "kicked(sector)\n";
			$locked = true;
		}
		}
		*/
		//if ($att_sector = $this->back->show_ns_attrib('http://www.trscript.de/tree#sector'))
		//if (!$this->content->getAccess())$locked = true;
		$locked = !$this->content->getAccess();
		
		/*
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
*/		
		if(!$locked)
		{
		  $deep++;
		
		 //echo $this->back->show_ns_attrib('http://www.trscript.de/tree#name') . " is saved{\n";
		    
		    //var_dump($arr);
		    	//echo "}\n";
		  
		  if($this->back->index_child() == 0 || count($this->level) == ($deep + 1) )
		  	  $arr[] = array('stamp' => $this->back->position_stamp(), 'deep' => $deep);
		  	  else
		  	  $this->find_line_start($arr,$deep);

		   $deep--;
		}
		//else
		//{

		//echo $this->back->show_ns_attrib('http://www.trscript.de/tree#name') . " is locked{\n";
		    
		   // var_dump($arr);
		    //	echo "}\n";
		//}
		
				$locked = false;
		
		}
/*
		if(!$locked)
		{ 
		  $deep++;
		  if($this->back->index_child() == 0 || count($this->level) == ($deep + 1) )
		    $arr[] = array('stamp' => $this->back->position_stamp(), 'deep' => $deep);
		  else
		    $this->find_line_start($arr,$deep);
		  
		   $deep--;
		}
		
				$locked = false;
		
		}
*/
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
	//var_dump($res);
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
	if($this->root)
	{
	$this->back->set_first_node();
	$this->back->child_node(0);
	}
	
       $arr = Array();
       $this->res = Array();
       //
       $this->find_line_start($arr);
       
       //var_dump($arr);
       for( $i = 0; $i < count($arr); $i++)
       $this->res[] = &$this->collect_lines($arr[$i]['stamp'], $arr[$i]['deep']);
       //var_dump($this->res);

       	if(($this->back->get_NS_QName() != 'final') && ($this->goback) && false)
	{
		
			$this->back->parent_node(); 
			$tmp = Array();
			       	for($i = count($this->level) - 1; $i > 1; $i--)
			       	{
			       		$tmp[$this->level[$i]['URL']] = null;
			       		$tmp[$this->level[$i]['Name']] = null;
			       	}
       
			       	$tmp[$this->level[0]['URL']] = str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),STD_URL);
			       	$tmp[$this->level[0]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
	
			       	$tmp[$this->level[1]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
			       	$tmp[$this->level[1]['URL']] = str_replace('%s',  $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),STD_URL);
			

			$this->res[] = &$tmp;

	}

       //Todo hier sollte das Logout hingehören
       if( $this->logout && (intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass'])  > 0))$this->res[] = &$this->add_logout_line();
       if(!$this->root)$this->res[] = &$this->add_goback_line();

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
