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

class Add_Entry extends plugin 
{

private $add = array();
private $counter = 0;
private $isNull = false;


var $rst = null;

//var $obj = null;
var $back =  null;
var $content = null;



var $param = array();

var $tag;

	function __construct(){}
	
	public function col($columnname)
	{

	if($this->counter < count($this->add))return $this->add[$this->counter][$columnname];
		
	if($this->rst)
	{
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

	
	public function add_entry($columns, $entries, $delimiter)
	{ 
		$keys = explode ( $delimiter , $columns);
		$values = explode ( $delimiter , $entries);
		$tmp = array();
		for($i = 0;$i < min( count( $keys ), count($values)); $i++ )
		{
			$tmp[$keys[$i]] = $values[$i];
		}
		
		$this->add[] =&$tmp;
	}

	

	  	  
	
	
	function getAdditiveSource(){;}
	public function moveFirst()
	{
	$this->counter = 0;
	if($this->rst)return $this->rst->moveFirst(); else return false;
	}
	
    	public function moveLast()
    	{
    	$this->counter = count($this->add);
    	if($this->rst)return $this->rst->moveLast();else return false;}
    	
	public function next()
	{
	if($this->counter < count($this->add) )
	{
		$this->counter++;
		return true;
	}
	else
	{
	if($this->rst)
	return $this->rst->next();
	else return false;
	}
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
    	


}
?>
