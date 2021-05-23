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

class String_Edit extends plugin 
{

private $print = array();
private $string;
private $filename; 
private $hash; 
private $output =array();

var $rst = null;

//var $obj = null;


	function __construct()
	{
		
		
	}
	
	public function col($columnname)
	{

	if($this->rst)
	{
//echo $columnname .  $this->rst->col($columnname) . " \n";
	  if(isset($this->print[$columnname]))
	  {
	  	  
	  	  $col = array();
	  	  foreach($this->output[$columnname] as $val)
	  	  	  $col[] = $this->rst->col($val);
	  	  

	  return vsprintf($this->print[$columnname], $col);
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

		

		
		
	public function edit($columns, $new, $string)
	{
		$this->output[$new] = explode(',', $columns);
		$this->print[$new] = $string;
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
    	
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}

}
?>
