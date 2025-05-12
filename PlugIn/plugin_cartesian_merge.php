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
require_once("plugin_interface_multisource.php");

class Cartesian_Merge extends plugin_multisource
{


private $tag_name = false;
private $allocation = array();
private $std_default = array();
private $std_null = array();
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

	return parent::col($columnname);
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	public function prefix($tbl, $prefix)
	{ 
		$this->column_prefix[$prefix] = $tbl;
	}

	
	
	function getAdditiveSource(){;}
	public function moveFirst()
	{
		if(count($this->rst) == 0) 
			return false;
		$res = true;
		for($i=0;$i < count($this->rst);$i++)
			$res = $res && $this->rst[$i]->moveFirst();
	
		return $res;	
	}
	
    	public function moveLast()
    	{
    		if(count($this->rst) == 0) 
			return false;
		$res = true;
		for($i=0;$i < count($this->rst);$i++)
			$res = $res && $this->rst[$i]->moveLast();
	
		return $res;	
	}
    	
	public function next()
	{
		//echo "\n";
		//if($this->sec-- == 0)return false;
		
		if(count($this->rst) == 0) 
			return false;

		$res = true;
		for($i=count($this->rst)-1;$i > 0 ;$i--)
			if($this->rst[$i]->next())
				return true;
			else
			{
				$this->rst[$i]->moveFirst();
			}
	
		return $this->rst[0]->next();	
		
		
	}
    	public function set_list(&$value)
    	{

    	if(is_object($value))
	{
		$this->rst[] = &$value;
	}
	else
	return 'no element received';
    	}
    	
 

}
?>
