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
private $currentTreeNode;
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
private $aspect = 'i';
private $addAspects = [];
private $page;

private $URLString = STD_URL;

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
	function __construct(/* System.Parser */ &$back, /* System.Content */ &$content, /* System.CurRef */ &$cur)
	{
		global $_SESSION;
		
                $this->currentTreeNode = $cur; // Good for this feature
		$this->back= &$back;
		$this->content = &$content;
		
		//var_dump($content->getHeap());
		
		$this->page = $this->content->getXMLStructur();
		

	
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
	
	
	public function configuration($json)
	{
		$confi = json_decode($json, true); // TODO Exception for NULL
		
		//if(array_key_exists("serial",$confi))$this->processSerialConfiguration($confi["serial"]);
		//var_dump($confi);
		if(array_key_exists("config",$confi))
			foreach ($confi["config"] as $function => $set)
			{
				//var_dump($set);
				
				switch ($function) {
				case "root":
					$this->root = $set;
					break;
				case "aspect":
					$this->aspect = $set;
					break;
				case "page":
					$this->page($set);
					break;
				case "back":
					if($set['active'])
					{	
						unset($set['active']);
						$this->back_in_tree(...$set);
					}
					else
					$this->goback = true;
					break;

				}
				
			}
		//if(array_key_exists("back_in_tree",$confi))$this->setXMLTemplate(...$confi["template"]);
		//back_in_tree($name, $url)
		if(array_key_exists("structure",$confi))
		{//$this->setXMLTemplate(...$confi["template"]); {"name" : "n1", "url" : "u1"},
			foreach ($confi["structure"] as $command) {
				$this->setLevelName(...$command);
		}
		

		$this->collect_Content();
}
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
	
	function page($page)
	{
		 
			if($page=='$default');
			elseif($page=="$this")
			{
				$this->page = $this->content->indexToUri(
					intval($this->currentTreeNode->get_idx())
					);
			}
			else
				$this->page = $page;	
//var_dump($page, $this->page);
	}
	
	function aspects($leadingAspect, $otherAspects = null)
	{
		$this->aspect = $leadingAspect;
		if($otherAspects)$this->addAspects = explode(',',$otherAspects);
		
		$res = [];
		foreach($this->addAspects as $aspect)
			array_push($res,  $aspect ."=" . $this->content->getHeap()['request'][$aspect]);
		//	$aspect = 
		
		array_push($res,  $leadingAspect ."=%s" );
		
		
		$this->URLString = 'index.php?' . implode('&',$res);
	}
	
	function back_in_tree($name = null, $url = null)
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
       
	$tmp[$this->level[0]['URL']] = str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),$this->URLString);
	$tmp[$this->level[0]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
       $tmp[$this->level[1]['Name']] = 'abmelden';
       $tmp[$this->level[1]['URL']] = str_replace('%s', '__system&modus=LOG_OUT&URL=' . $this->logout_url,$this->URLString);
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
       
	$tmp[$this->level[0]['URL']] = str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),$this->URLString);
	$tmp[$this->level[0]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');

	$stamp = $this->back->position_stamp();
	$this->back->parent_node();
	
	//if($this->goback)
	//{

	$tmp[$this->level[1]['URL']] = ($this->goback['URL'] ? $this->goback['URL'] : str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),$this->URLString)); //str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),$this->URLString);
	$tmp[$this->level[1]['Name']] = ($this->goback['Name'] ? $this->goback['Name'] : 'zurück');
	
	//var_dump($tmp);
	//}
       //& URL=
       
	$this->back->go_to_stamp($stamp);
       
       	return $tmp; 
       
	}
	
	/**
	*	runns trough all lines
	*/
	private function find_line_start(&$arr, $deep = 0)
	{
		$many = $this->back->index_child();
		//echo $this->back->show_ns_attrib('http://www.trscript.de/tree#name') . "\n";
	$iter = 0;
	$locked = false;
	for($i = 0; $i < $many; $i++)
	{
		
		$this->back->child_node($i);
		

		if($this->back->cur_node() == 'tree')
		{

		$locked = !$this->content->getAccess();
		
		
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

		$this->back->parent_node();
	}

	}
	
	
	/**
	*	@param $stamp : recipes a position stamp
	*	@param $deep : the max deep, it is allowed to collect data
	*	@return an array of
	*/
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
	    $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),$this->URLString);
	    $res[$this->level[$i]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
	
	  }
	  if($i < $deep)
	  {
	    $this->back->parent_node();
	    $res[$this->level[$i]['URL']] = str_replace('%s', 
	    $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),$this->URLString);
	    $res[$this->level[$i]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
	  }
	  
	}
	//var_dump($res);
	 return $res;
	}
	
	/**
	*	collects all specified lines and creates a recordset 
	*
	*/
	public function collect_Content() 
	{
	
	global $_SESSION;
		
	/* generall backjump stamp */
	$stamp = $this->back->position_stamp();
	
	/* URIs for spezific trees */
	$structur = $this->page;
	$output = $this->content->get_out_template();

	/* change to control tree */
	$this->back->change_URI($structur);
	//var_dump($this->back->cur_idx(), $structur, $this->page);
	//$this->back->ALL_URI();
	
	/* saves current position */
	$stamp_structur = $this->back->position_stamp();
	
	/* checks the current position to be the root */
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

       // Does not look reachable
       //TODO check this
       	if(($this->back->get_NS_QName() != 'final') && ($this->goback) && false)
	{
		echo "test me";
			$this->back->parent_node(); 
			$tmp = Array();
			       	for($i = count($this->level) - 1; $i > 1; $i--)
			       	{
			       		$tmp[$this->level[$i]['URL']] = null;
			       		$tmp[$this->level[$i]['Name']] = null;
			       	}
       
			       	$tmp[$this->level[0]['URL']] = str_replace('%s', $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),$this->URLString);
			       	$tmp[$this->level[0]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
	
			       	$tmp[$this->level[1]['Name']] = $this->back->show_ns_attrib('http://www.trscript.de/tree#value');
			       	$tmp[$this->level[1]['URL']] = str_replace('%s',  $this->back->show_ns_attrib('http://www.trscript.de/tree#name'),$this->URLString);
			

			$this->res[] = &$tmp;

	}

	//var_dump($this->res);
	
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
