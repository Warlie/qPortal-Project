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

class Converter extends plugin 
{
private $tag_name = false;
private $allocation = array();
private $std_default = array();
private $std_null = array();
private $to_null = array();
var $rst = null;
var $into = array();
//var $obj = null;
var $back =  null;
var $content = null;
var $computeNull = false;


var $param = array();
var $images = array();
var $tag;

	function __construct(/* System.Parser */ &$back, /* System.Content */ &$content)
	{
		
		
		$this->back= &$back;
		$this->content = &$content;
		
	}
	
	public function col($columnname)
	{

	if($this->rst)
	{
		//echo $columnname . " :"  . $this->rst->col($columnname) . "(Converter:2b)\n";
	  if(isset($this->allocation[$columnname]))
	  {
	  	  	  	  //echo $columnname . " "  . $this->rst->col($columnname) . "\n";
	  	  if(!$computeNull && is_null($this->rst->col($columnname)))
	  	  {
	  	  	  if(isset($this->std_null[$columnname]) && !is_null($this->std_null[$columnname]))
	  	  	 	 return $this->std_default[$columnname];
	  	  	 	else
	  	  	 	return null;
	  	  	 	
	  	  }
	  	  
	  	  
	  	  //echo $columnname . " "  . $this->rst->col($columnname) . "\n";
	  	 //if(isset($this->allocation[$columnname][$this->rst->col($columnname)]))
	  	 //echo $this->allocation[$columnname][$this->rst->col($columnname)];	
	  	 $res;
	  	//var_dump($this->allocation);
	  	if(isset($this->allocation[$columnname][$this->rst->col($columnname)]))
	  	{

	  	  $res = $this->allocation[$columnname][$this->rst->col($columnname)];
	  	  //echo $columnname . " "  . $res . "(Converter:2)\n";
	  	  return $res;
	  	}
	  	elseif(isset($this->std_default[$columnname]))
	  	{
	  	  return $this->std_default[$columnname];
	  	}

	  		
	  }
	
	  if((($this->to_null[$columnname])) && $this->rst->col($columnname) == '' )
	  {
	  	  //echo $columnname . " :"  . $this->rst->col($columnname) . "(Converter:2b)\n";
	  	  return null;
	  }
	  //echo $columnname . " :"  . $this->rst->col($columnname) . "(Converter:3)\n";
	  return $this->rst->col($columnname);
	}
	  throw new ObjectBlockException('Recordset is missing');
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	public function replace($name){$this->tag_name = $name;}
	public function in($from, $to)
	{
		//TODO 0 is ""

	if(strlen($from) == 0)$from = 0;
	if(!($this->tag_name === ""))
	  {
	  	
	  	if(!isset($this->allocation[$this->tag_name]))
	  	  $this->allocation[$this->tag_name] = array();
	  	$this->allocation[$this->tag_name][$from] = $to;
	  }
	}
	
	public function r_default($value)
	{
	  if(!($this->tag_name === false))
	  {
	  	 if(!isset($this->allocation[$this->tag_name]))
	  	  $this->allocation[$this->tag_name] = array();
	  	  
	  	$this->std_default[$this->tag_name] = $value;
	  }
	
	}
	
	public function r_null($value)
	{
	  if(!($this->tag_name === false))
	  $this->std_null[$this->tag_name] = $value;
	
	}
	
	public function r_strToNull()
	{
	  if(!($this->tag_name === false))
	  $this->to_null[$this->tag_name] = true;
	
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
    	
    	public function datatype($columnname){return $this->rst->datatype($columnname);}
    	
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}

}
?>
