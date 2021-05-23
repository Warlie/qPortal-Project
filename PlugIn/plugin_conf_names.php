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

class Conf_Name extends plugin 
{
	private $surname = '';
	private $forename = '';
	private $column = '';

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
	  if(strtoupper($this->column) == strtoupper($columnname))
	  {//echo $columnname . " :"  . strtoupper(substr($this->rst->col($this->forename),0,1)) . "." . ucfirst($this->rst->col($this->surname)) . "(Converter:1)\n";
	  	  
	  	  $for = strtoupper(substr($this->rst->col($this->forename),0,1));
	  	  $sur = ucfirst($this->rst->col($this->surname));
	  	  
	  	  if($for)
	  	  	  if($sur)
	  	  	  	return $for  . "." . $sur;
	  	  	  else
	  	  	  	return $for;
	  	  else
	  	  	  if($sur)
	  	  	  	return $sur;
	  	  	  else
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

	
	public function set_name($surname,$forename,$column)
	{
			$this->surname = $surname;
			$this->forename = $forename;
			$this->column = $column;
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
    	
    	public function fields(){if($this->rst[0]) return $this->rst[0]->fields();else return array();}

}
?>
