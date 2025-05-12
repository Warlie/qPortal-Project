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

class Exceptions extends plugin 
{
private $tag_name = false;
private $allocation = array();
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
private $ref;

	function __construct(/* System.Parser */ &$back)
	{
		

		$this->ref = &$back->get_ExceptionManager();
		
	}
	
	public function col($columnname)
	{
	if($this->rst)
	{
	  if(isset($this->allocation[$columnname]))
	  {
	  	  
	  	  if(!$computeNull && is_null($this->rst->col($columnname)))return null;
	  	  //echo $columnname . " "  . $this->rst->col($columnname) . "\n";
	  	 //if(isset($this->allocation[$columnname][$this->rst->col($columnname)]))
	  	 //echo $this->allocation[$columnname][$this->rst->col($columnname)];	
	  	 $res;
	  	//var_dump($this->allocation);
	  	if(isset($this->allocation[$columnname][$this->rst->col($columnname)]))
	  	{
	  	  $res = $this->allocation[$columnname][$this->rst->col($columnname)];
	  	  
	  	  return $res;
	  	}
	  	elseif(isset($this->std_default[$columnname]))
	  	{
	  	  return $this->std_default[$columnname];
	  	}
	  }
	
	  return $this->rst->col($columnname);
	}
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	function getPlainException()
	{
			$res = $this->ref->many() . " Exceptions occured\n";
			$this->ref->first();
			while(!$this->ref->EOF())
				{
					$res .= $this->ref->getException()->getCode();
					$res .= '-';
					$res .= $this->ref->getException()->getMessage();
					$res .= "\n";
					$res .= $this->ref->getException()->getTraceAsString();
					$res.= "\n";
					$this->ref->next();
				}
			
		
		return $res;
	}
	
	/*
		public function first(){$this->counter = 0;}
	public function next(){if($this->counter < count($this->list)$this->counter++;}
	public function EOF(){return !($this->counter < count($this->list);}
	public function &getException(){return $this->list[$this->counter];}
	*/
	
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

}
?>
