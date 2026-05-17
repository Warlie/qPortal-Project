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
require_once("PlugIn/plugin_interface.php");

class ReplaceCharacters extends plugin 
{

private $column = "";
private $from = null;
private $into = null;

protected $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	public function col($columnname)
	{
		//echo $columnname . "\n";
	if($this->rst)
	{
		
	  if($this->column  ==  $columnname)
	  	  return str_replace($this->from, $this->into,$this->rst->col($columnname));

	else
	return $this->rst->col($columnname);
	}
	
	  throw new \RuntimeException("ReplaceCharacters: set_list went wrong, the argument value isn't an object");
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}

	
	public function column($column)
	{
		$this->column = $column;
	}
	
	
	public function jsonReplace($from, $into)
	{
		$this->from = json_decode($from, true, 512, JSON_THROW_ON_ERROR);
		$this->into = json_decode($into, true, 512, JSON_THROW_ON_ERROR);

	}
	
	private function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
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

}
?>
