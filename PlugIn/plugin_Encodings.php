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

class Encodings extends plugin 
{
private $tag_name = false;
private $allocation = array();
private $encodingFrom = '';
private $encodingTo = '';
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

	//echo $this->rst->col($columnname) . " enc to:" .  $this->encodingTo . " enc from:" . $this->encodingFrom;
	  return mb_convert_encoding($this->rst->col($columnname), $this->encodingTo, $this->encodingFrom);
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

	
	public function encoding($from, $to)
	{
	  	 $this->encodingFrom = $from;
	  	 $this->encodingTo = $to;

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
