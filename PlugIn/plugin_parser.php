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

class Parser extends plugin 
{
private $tag_name = false;
private $allocation = array();
private $std_default = array();
private $std_null = array();
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
	  if(isset($this->allocation[$columnname]))
	  {
	  	  	  	  //echo $columnname . " "  . $this->rst->col($columnname) . "\n";
	  	  if(!$computeNull && is_null($this->rst->col($columnname)))
	  	  	  if(isset($this->std_null[$columnname]))
	  	  	 	 return $this->std_default[$columnname];
	  	  	 	else
	  	  	 	return null;
	  	  
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
	  throw new ObjectBlockException('Recordset is missing');
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	public function saveOnFile($id,$file) 
	{
		
		$stamp = $this->back->position_stamp();
		$this->back->change_URI( $this->content->get_template($id));
		$this->back->save_file('',false, $file);
		$this->back->go_to_stamp($stamp);
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
