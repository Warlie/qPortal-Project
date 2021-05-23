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

class Explode extends plugin 
{
private $tag_name = false;
private $delimiter = false;
private $string = false;
private $allocation = array();
private $list_of_col=array();
private $expl = false;
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
		unset($this->expl);
		
	}
	
	public function col($columnname)
	{
	if($this->rst)
	{

	if(in_array($columnname,$this->list_of_col))
	{
		
		if(!isset($this->expl))$this->expl = explode($this->delimiter, $this->rst->col($this->tag_name));
		//var_dump($this->allocation);
		return trim($this->expl[$this->allocation[$columnname] ]);
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
/*
		<remote name="Explode.explode.delimiter" >,</remote>
	<remote name="Explode.explode.string" >tbl_mailing.client</remote>
	<remote name="Explode.explode" />
	<remote name="Explode.newcol.pos" >0</remote>
	<remote name="Explode.newcol.name" >tbl_mailing.surname</remote>
	<remote name="Explode.newcol" />
	<remote name="Explode.newcol.pos" >1</remote>
	<remote name="Explode.newcol.name" >tbl_mailing.forename</remote>
	<remote name="Explode.newcol" />
	*/
	public function explode($delimiter, $string)
	{
		$this->tag_name = $string;
		$this->delimiter = $delimiter;
	}
	
	public function newcol($pos, $name)
	{
		$this->list_of_col[] = $name;
		$this->allocation[$name] = intval($pos);
		
	}


	
	function getAdditiveSource(){;}
	protected function moveFirst(){
	unset($this->expl);
	if($this->rst)return $this->rst->moveFirst(); else return false;}
    	protected function moveLast(){
    	unset($this->expl);
    	if($this->rst)return $this->rst->moveLast();else return false;}
    	
	public function next(){
	unset($this->expl);
	if($this->rst)return $this->rst->next();else return false;}
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
