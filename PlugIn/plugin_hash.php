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

class Hash extends plugin 
{
private $algo = "md5";
private $salt = "chilli";
private $tag_name = "";
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

	function __construct()
	{

		
	}
	
	public function col($columnname)
	{
		
	if($this->rst)
	{
		 if($this->tag_name == $columnname)
		 	 return hash( $this->algo, $this->rst->col($columnname) .  $this->salt, false);
		else
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

	public function columnName($name){$this->tag_name = $name;}	
	public function algo($name){$this->algo = $name;}
	public function salt($salt){$this->salt = $name;}

	

	
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
