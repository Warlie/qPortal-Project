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

class Equation extends plugin 
{

private $compare = array();
private $except = array();
private $iftrue = null;
private $itfalse = null;

var $rst = null;

//var $obj = null;
var $back =  null;
var $content = null;



var $param = array();

var $tag;

	function __construct(){}
	
	public function col($columnname)
	{

	if($this->rst)
	{
		
	  if(isset($this->compare[$columnname]))
	  {

	  	  reset($this->compare[$columnname]);
	  	  //echo $columnname . " ";
	  	  if($out1 = $this->forExcept($columnname) //$this->rst->col($this->compare[$columnname][0])
	  	  	  ==
	  	  $out2 = $this->forExcept($columnname))
	  	  {
	  	  	//echo  $out2 . "=" . $out1 . "\n";
	  	  return $this->iftrue;
	  	  }
	  	  //else
	  	  //echo  $out2 . "=" . $out1 . "\n";
	  	  return $this->itfalse;

	  }
	
	  return $this->rst->col($columnname);
	}
	  throw new ObjectBlockException('Recordset is missing');
	}
	
	public function configuration($json)
	{
		$confi = json_decode($json, true, 512, JSON_THROW_ON_ERROR); // TODO Exception for NULL
		
		if(array_key_exists("config",$confi))
			foreach ($confi as $function => $set)
			{
				//var_dump($set);
				
				switch ($function) {
				case "compare":
					$this->compare($column1, $column2, $result);
					break;
				case "setCDATAmode":
					$this->setValue($column, $value);
					break;
				case "setEmptyCaseText":
					$this->setEmptyCaseText($set);
					break;

				}
				
			}
		
/*

	public function setTrue($value)
	{
	  	  $this->iftrue = $value;	
	}
	
	public function setFalse($value)
*/
	}
	
	private function forExcept($columnname)
	{
		$tmp = null;
		
		if(!$this->except[current($this->compare[$columnname])])
		{
			//echo current($this->compare[$columnname]) . "=" . $this->rst->col(current($this->compare[$columnname])) . "\n";
			$tmp = $this->rst->col(current($this->compare[$columnname])) ;
		}
		else
		{
			//echo current($this->compare[$columnname]) . "=" . $this->except[current($this->compare[$columnname])]. "\n";
			$tmp = $this->except[current($this->compare[$columnname])];
		}	
			next($this->compare[$columnname]);
				  	  //echo $tmp . "\n";
			return $tmp;
	}
	
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{

		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	public function compare($column1, $column2, $result)
	{ 
		$this->compare[$result] = array($column1, $column2);
	}
	
	public function setValue($column, $value)
	{
		
		$this->except[$column] = $value;
	}

	
	public function setTrue($value)
	{
	  	  $this->iftrue = $value;	
	}
	
	public function setFalse($value)
	{
	  	  $this->itfalse = $value;
	}
	  	  
	
	
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
    	
    	public function test()
    	{
    		
    	}

    	 public function fields(){if($this->rst[0]) return $this->rst[0]->fields();else return array();}
    	
}
?>
