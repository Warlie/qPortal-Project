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
*
*/
require_once("plugin_interface.php");

class Remove extends plugin 
{
private $tag_name = false;
private $string = array();
private $from = array();
private $to = array();
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
		if(strcasecmp ( $columnname , $this->tag_name) == 0)
		{
			$res = $this->rst->col($columnname);
					$this->from[] = $from;
		$this->to[] = $to;
			for($i = 0; count($this->from) > $i; $i++) 
			{
				$pos = strpos($res, $this->from[$i]);
				if($pos !== false)
					{	
						$end= strpos($res, $this->to[$i], $pos);
						if($end !== false)
							$res = substr($res, 0, $pos) . substr($res, $end + 1);
						else	
							$res = substr($res, 0, $pos);
					
					}
			}
			
				return str_replace($this->string, '', $res);
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
	<object id="rm" name="Remove" src="PlugIn/plugin_remove.php">
	<remote name="Remove.set_list.value" ><object id="alt" ><remote name="Alternativ.iter" /></object></remote>
	<remote name="Remove.set_list" />
	<remote name="Remove.incol.name" >tbl_mailing.Oname</remote>
	<remote name="Remove.incol" />	
	<remote name="Remove.characters.string" >DWS </remote>
	<remote name="Remove.characters" />
	<remote name="Remove.interval.from" >(</remote>
	<remote name="Remove.interval.to" >)</remote>
	<remote name="Remove.interval" />
	</object>
	*/
	
	public function incol( $name )
	{
		$this->tag_name = $name;
	}
	
	public function characters($string)
	{
		$this->string[] = $string;

	}
	
	public function interval($from, $to)
	{
		$this->from[] = $from;
		$this->to[] = $to;
		
	}


	
	function getAdditiveSource(){;}
	public function moveFirst(){
	unset($this->expl);
	if($this->rst)return $this->rst->moveFirst(); else return false;}
    	public function moveLast(){
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
