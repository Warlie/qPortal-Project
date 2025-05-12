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

class Todo_Overview extends plugin 
{

private $counter_all; 
private $counter_rel; 
private $all_name = "all";
private $rel_name = "rel";
private $percent = "percent";
private $text = '$$';
private $first = true;




private $array = [];

var $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	public function scanCol()
	{
		$addOne = 0;
		if($this->rst && $this->first )
		{
			$this->rst->moveFirst();
			do
				{
					//var_dump( ($this->rst->col($this->counter_rel) ? 1 : 0) );
					
					$addOne = ($this->rst->col($this->counter_rel) ? 1 : 0) ;
					
					if(!isset($this->array[$this->rst->col($this->counter_all)]))
					{
						$this->array[$this->rst->col($this->counter_all)] = [];
						$this->array[$this->rst->col($this->counter_all)]['all'] = 1;
						$this->array[$this->rst->col($this->counter_all)]['rel'] = $addOne;
					}
					else
					{
						$this->array[$this->rst->col($this->counter_all)]['all']++;
						$this->array[$this->rst->col($this->counter_all)]['rel'] += $addOne;
					}
				}
			while($this->rst->next());
			
			$this->rst->moveFirst();
			$this->first = false;
		}
	
	}
	
	public function col($columnname)
	{

	if($this->rst)
	{
		
		if(strcmp($this->all_name, $columnname) == 0)
		{
			
			return $this->array[$this->rst->col($this->counter_all)] ['all'];
		}
		
		if(strcmp($this->rel_name, $columnname) == 0)
		{
			return $this->array[$this->rst->col($this->counter_all)] ['rel'];
		}
		
		if(strcmp($this->percent, $columnname) == 0)
		{
			$repace = floor(
				((float)($this->array[$this->rst->col($this->counter_all)] ['rel'])  * 100.0) / (float) ($this->array[$this->rst->col($this->counter_all)] ['all'])
				);
			return str_replace('$$', $repace, $this->text);
		}
		
		
		
	return $this->rst->col($columnname);
	}
	
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function countCol($columnname)
		{
		
		$this->counter_all = $columnname;
		}
	

	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function countbool($columnname)
		{
		$this->counter_rel = $columnname;
		
		}
		
	public function percent($columnname, $text = '$$')
		{
		$this->percent = $columnname;
		$this->text = $text;
		}
		
		
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}
	
	
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
