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

class CSV_Pattern_Check 
{
	private $back;
	private $treepos;
	private $content;
	private $patterns = [];
	private $documents = [];
	private $checked = [];

	function __construct(/* System.Parser */ &$back, /* System.CurRef */ &$treepos, /* System.Content */ &$content)
	{
		$this->back= &$back;
		$this->treepos = &$value;
		$this->content = &$content;
		
	}

	public function set_Pattern($name, $pattern, $seperator = ',')
	{
		$this->patterns[$name] = explode($seperator, $pattern);
	}
	
	public function analyse_Pattern($id)
	{
		
		$tmpstamp = $this->back->position_stamp();
		//$this->back->test_consistence();
		//echo $id . "  " .$this->content->get_template($id) . " \n";
		
		if(!$this->back->change_URI($this->content->get_template($id)))
		{
		///echo $new_template . ' isn\'t a available documentident (setXMLTemplate) ';
		$this->back->test_consistence();
		}
		$this->back->index_child();
		if(0 <$this->back->index_child())
		{

			$this->back->child_node(0);

		}

		
		
		//$this->back->set_first_node();
/*
		$this->back->flash_result();
		echo $this->back->seek_node( "http://www.csv.de/csv#ROW"); //,,[ 'http://www.csv.de/csv#NUM'  => '1'] 

		$result = $this->back->get_result();
		$result[0]->full_URI();
		//echo count($result);
		*/
		$res = [];
		for($i = 0;$i <$this->back->index_child(); $i++)
			{
				$this->back->child_node($i);
				$res[] = $this->back->show_ns_attrib('http://www.csv.de/csv#NAME');
				$this->back->parent_node();

				
			}
		
		$this->documents[$id] = $res;

			
		$this->back->go_to_stamp($tmpstamp);
	}
	

	
	
	public function check_Pattern($id, $name)
	{
		if(!isset($this->checked[$name]))$this->checked[$name] = [];
		if(!isset($this->checked[$name][$id]))$this->checked[$name][$id] = null;
			
		if(!is_null($this->checked[$name][$id]))return $this->checked[$name][$id];
		

		if(0 == count(array_diff($this->documents[$id], $this->patterns[$name])))
		
			return json_encode($this->checked[$name][$id] = true);
		else
			return json_encode($this->checked[$name][$id] = false);
	}
	
	
	public function check_All($id)
	{
		$result = true;
		foreach ($this->patterns as $key => $value)
			$result &= $this->check_Pattern($id, $key);
		
		return json_encode($result);
	}

}
?>
