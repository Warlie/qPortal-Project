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

class Alias extends plugin 
{

private $alias;
private $newColumn;

protected $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	public function col($columnname)
	{

	if($this->rst)
	{
		
	  if(isset($this->alias[$columnname]))
	  {


	  return $this->rst->col($this->alias[$columnname]);
	}
	else
	return $this->rst->col($columnname);
	}
	
	  throw new \RuntimeException("alias: recordset is missing");
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}

	
	public function alias($column, $alias)
	{
		$this->newColumn = $alias;
		$this->alias[$alias] = $column;
	}
	
	
	function getAdditiveSource(){;}
	public function moveFirst(){if($this->rst)return $this->rst->moveFirst(); else return false;}
    
	public function moveLast(){if($this->rst)return $this->rst->moveLast();else return false;}
    	
	public function next(){if($this->rst)return $this->rst->next();else return false;}
	
    public function set_list(&$value)
    {
    	if(is_object($value))
    		$this->rst = &$value;
		else
			throw new \RuntimeException("alias: set_list went wrong, the argument value isn't an object");
    }
    	
    public function fields(){if($this->rst) return array_merge($this->rst->fields(), [$this->newColumn]);else return array();}

}
?>
