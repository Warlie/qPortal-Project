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
require_once("PlugIn/plugin_interface.php");

class ConcatStrings extends plugin 
{

private $column = "";
private $front = "";
private $end = "";

protected $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	public function col($columnname)
	{
		//echo $columnname . "\n";
	if($this->rst)
	{
		
	  if($this->column  ==  $columnname)
	  	  return  $this->front . $this->rst->col($columnname) . $this->end;

	else
	return $this->rst->col($columnname);
	}
	
	  throw new \RuntimeException("ReplaceCharacters: set_list went wrong, the argument value isn't an object");
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}

	
	public function column($column)
	{
		$this->column = $column;
	}
	
	
	public function concat($front = null, $end = null)
	{
		if(!is_null($front))$this->front = $front;
		if(!is_null($end))$this->end = $end;
	}

	
	function getAdditiveSource(){;}
	public function moveFirst(){if($this->rst)return $this->rst->moveFirst(); else return false;}
    	public function moveLast(){if($this->rst)return $this->rst->moveLast();else return false;}
    	
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
    	
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}

}
?>
