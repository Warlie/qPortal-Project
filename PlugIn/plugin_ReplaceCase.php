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

class RegCase extends plugin 
{
private $tag_name = false;
private $allocation = array();
private $check_name = '';
private $mod_name = '';
private $std_default = array();
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
		
	if(strcasecmp ( $columnname , $this->mod_name) == 0)
	{
		
		$res = $this->rst->col($columnname);
		$arr = explode(',', $res);
		if(strcasecmp ( $this->rst->col($this->check_name) , 'herr' ) == 0)
		{
			$res = 'Sehr geehrter Herr ' . trim($arr[0]);
		}
		else
		if(strcasecmp ( $this->rst->col($this->check_name) , 'frau' ) == 0)
		{
			$res = 'Sehr geehrte Frau ' . trim($arr[0]);
		}
		else
		$res = "Sehr geehrte Damen und Herren,";
		
	  return $res;
	}
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

	
	public function modify($check, $mod)
	{
		$this->check_name = $check;
		$this->mod_name = $mod;

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
