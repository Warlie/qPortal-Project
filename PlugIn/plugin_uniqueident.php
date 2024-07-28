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

class UniqueIdent extends plugin 
{
private $max_id = array();
private $coll_col = array();
private $std_default = array();
private $rst = null;
private $value = 1;
var $back =  null;
var $content = null;




	function __construct (/* System.Parser */ &$back, /* System.Content */ &$content)
	{
		
		
		$this->back= &$back;
		$this->content = &$content;
	
		
	}
	
	public function lookforColumn($col)
	{
		
		$this->coll_col[] = $col; 
		
	} 

	public function col($columnname)
	{
	if($this->rst)
	{
	  return $this->rst->col($columnname);
	}
	  return 'no dataset';
	}
	
	
	public function newUniqueid()
	{

	$this->rst->moveFirst();

		for($j=0;$j<count($this->coll_col);$j++)$this->max_id[$this->coll_col[$j]] = 0;

		
		do
		{

		for($j=0;$j<count($this->coll_col);$j++)
			if($this->rst->col($this->coll_col[$j]) > $this->max_id[$this->coll_col[$j]] )
				{
				$this->max_id[$this->coll_col[$j]] = $this->rst->col($this->coll_col[$j]);
				}
		
		}while($this->rst->next());
		$this->rst->moveFirst();	
		//$this->documentForInsert = $documentName;
		
	}
	


	public function getID($col)
	{

		return $this->max_id[$col] + $this->value;
	}

	public function add($value)
	{
		$this->value = intval($value);
	}

	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	

	
	
	function getAdditiveSource(){;}
	protected function moveFirst(){if($this->rst)return $this->rst->moveFirst(); else return false;}
    	protected function moveLast(){if($this->rst)return $this->rst->moveLast();else return false;}
    	
	public function next(){if($this->rst)return $this->rst->next();else return false;}
    	public function set_list(&$value)
    	{

    	if(is_object($value))
	{		

		$this->rst = &$value;
	}
	else
	return 'no element received';
    	}
    	


}
?>
