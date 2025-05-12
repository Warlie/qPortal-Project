<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/
require_once("plugin_interface.php");

class TimeDate extends plugin 
{

	private $alias;
private $filename; 
private $hash; 
private $output;

var $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	public function curdate($format){return date($format);} 
	
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
	
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}

	
	public function alias($column, $alias)
	{
		$this->alias[$alias] = $column;
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
    	/*
	//reihe
	var $param = array();
	var $bool = false;
	var $res = array(); 	
var $reqire = array();
var $content = array();
var $obj = null;
var $tag;
//http://www.sight-board.de/_editor/dataProvider/data.php?external=34
	function TimeDate(){}
	
	function set($type, $value)
	{
		parent::set($type, $value);
		//echo $type . ' ' . $value;

		
		if($type == "CURTIME")
		{
			
			$this->param_out(date("H:i:s"));
			
			
		}

		if($type == "CURDATE")
		{
			
			$this->param_out(date("Y-m-d"));
			
			
		}		
		
		if($type == "RUN")
		{
			//$booh = $this->get_GK3(array('Am Grafenwald','10','42859','Remscheid'));
			
			//echo 'x=' . $booh[0] . ',y=' . $booh[1] . ',z=' . $booh[2] . '<br>';
			//
			
		}
	}
	
	function check_type($type)
	{
	if($type == "SQL")return true;
	if($type == "XMLTEMPLATE")return true;
	if($type == "COL")return true;
	//if($type == "")return true;
	return parent::check_type($type);
	}

	function next(){return false;}

	
	function decription(){return "no description avaiable!";}
	
	public function moveFirst(){}
	public function moveLast(){}
	public function getAdditiveSource(){}
	public function set_list(&$value){}
    	
	*/
}
?>
