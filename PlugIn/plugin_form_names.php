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

class FormNames extends plugin 
{
private $names;
private $count;

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
		$this->count = 0;
		
	}
	
	public function col($columnname)
	{
	if($this->rst)
	{
	  if(in_array($columnname, $this->names))
	  {
	  	  
	  	  return  $columnname . '[' . $this->count . ']';
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
		return $this;}


	public function setNames($names)
	{
		$this->names = explode(',',$names);

	}
		
	function getAdditiveSource(){;}
	public function moveFirst(){if($this->rst){ $this->count =0 ;return $this->rst->moveFirst(); }else return false;}
    	public function moveLast()
    	{
    		if($this->rst)return false;
    		$this->moveFirst();
    		while($this->next());
    			return true;
    		
    	}
    	
	public function next()
	{
		if($this->rst)
		{
			if($this->rst->next())
			{
				$this->count++;
				return true;
			}
			else
			return false;
		}
		else return false;
	}
	
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
