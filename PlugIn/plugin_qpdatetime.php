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

class QPDateTime extends plugin 
{

private $rel = array();
private $source;



	function __construct(){}
	
	public function col($col_name)
	{

	if($this->rst)
	{
	  if(isset($this->rel[$col_name]))
	  {
	  	 
	  	  if($this->source === '')return "need source";
	  	  $date = new DateTime($this->rst->col($this->source));
	  	  return $date->format($this->rel[$col_name]);


	  }
	  return $this->rst->col($col_name);
	}
	  return 'no dataset';
	}
	
	public function setsource($column){ $this->source = $column; }
	public function setcolumn($column, $format){ $this->rel[$column] = $format; }
	


	
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