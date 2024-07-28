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
	<object id="reg" name="RegCase" src="PlugIn/plugin_RegCase.php">
	<remote name="RegCase.set_list.value" ><object id="exp" ><remote name="Explode.iter" /></object></remote>
	<remote name="RegCase.set_list" />
	<remote name="RegCase.modify.check" >tbl_mailing.anrede</remote>
	<remote name="RegCase.modify.mod" >tbl_mailing.client</remote>
	<remote name="RegCase.modify" />
	<remote name="RegCase.addsource.name" >/tbl_mailing.surname</remote>
	<remote name="RegCase.addsource.symbol" >#surename,</remote>
	<remote name="RegCase.addsource" />	
	<remote name="RegCase.cases.checkreg" >/[Ff]rau/</remote>
	<remote name="RegCase.cases.modstring" >Sehr geehrte Frau #surename,</remote>
	<remote name="RegCase.cases" />
	<remote name="RegCase.cases.checkreg" >/[Hh]err/</remote>
	<remote name="RegCase.cases.modstring" >Sehr geehrter Herr #surename,</remote>
	<remote name="RegCase.cases" />
	<remote name="RegCase.default.modstring" >Sehr geehrte Damen und Herren,</remote>
	<remote name="RegCase.default" />

*/
require_once("plugin_interface.php");

class RegCase extends plugin 
{
private $tag_name = false;
private $allocation = array();
private $source_name = array();
private $source_symbol = array();
private $check_name = '';
private $mod_name = '';
private $std_default = array();
private $result_array;
private $default = '';
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
		
	if(strcasecmp ( $columnname , $this->mod_name) == 0)
	{
		$this->build_array();
		foreach ($this->allocation as $value)
		if(preg_match($value[0], $this->rst->col($this->check_name)))
		{
			return str_replace($this->source_symbol, $this->result_array, $value[1]);
		}


		$res = str_replace($this->source_symbol, $this->result_array, $this->default);
		
	  return $res;
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

	
	public function modify($check, $mod)
	{
		$this->check_name = $check;
		$this->mod_name = $mod;

	}
	
	public function addsource($name, $symbol)
	{
		$this->source_name[] = $name;
		$this->source_symbol[] = $symbol;
	}
	
	
	public function cases($checkreg, $modstring)
	{
		$this->allocation[] = array($checkreg, $modstring);
		
	}
	public function default($modstring){$this->default = $modstring;}

	private function build_array()
	{
		if(!isset($this->result_array))
		{
			$this->result_array = array();
			foreach ($this->source_name as $value)
			$this->result_array[] = $this->rst->col($value); //$this->rst->col($value)
		//var_dump($this->result_array);
		}
	}
	
	
	function getAdditiveSource(){;}
	protected function moveFirst()
	{
		unset($this->result_array);
		if($this->rst)return $this->rst->moveFirst(); else return false;
	}
    	protected function moveLast()
    	{
    		unset($this->result_array);
    		if($this->rst)return $this->rst->moveLast();else return false;
    	}
    	
	public function next()
	{
		unset($this->result_array);
		if($this->rst)return $this->rst->next();else return false;
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
