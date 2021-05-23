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

class FileHash extends plugin 
{
	
private $filename; 
private $hash; 
private $output;

var $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	public function col($columnname)
	{

	if($this->rst)
	{
		
	  if($this->output ===$columnname)
	  {

	  	if(is_file($this->rst->col($this->filename)))
	  	  
	  		if($this->rst->col($this->hash) === md5_file ($this->rst->col($this->filename)))
				return 'true';
			else
				return 'false';
		else
			return 'false';

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
		
		return $this;}

	
	public function check($filename, $hash, $output)
	{
		$this->output = $output;
		$this->hash = $hash;
		$this->filename = $filename; 
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
