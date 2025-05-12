<?PHP

/**
*ContentGenerator
*
* @-------------------------------------------
* @title:Request
* @autor:Stefan Wegerhoff
* @description: Object to get data from sessions, requests and plattformcommands 
*
*
*/
require_once("plugin_interface.php");

class Session extends plugin 
{



	private $max  = 1;
	private $pos = 0;
	private $groups = array();

	function __construct(/* System.Parser */ &$back, /* System.CurRef */ &$treepos)
	{	global $_SESSION;
		$this->back= &$back;
		$this->treepos = &$treepos;
	
//var_dump($_SESSION);
	}
	
			
	/**
	*@function: MOVEFIRST = goes to first record
	*/
		
	public function moveFirst()
	{
		if($this->rst)return $this->rst->moveFirst();
		$this->pos = 0;
		return true;
	
	}

	
	/**
	*@function: MOVELAST = goes to last record
	*/
	public function moveLast()
	{
		if($this->rst)return $this->rst->moveLast();
		$this->pos = $this->max - 1 ;
		return true;
	}
		
	public function next()	
	{

		if($this->rst)return $this->rst->next();
		return false;
	}
	
		
	/**
	*
	*@-------------------------------------------
	*/
	//parameterausgabe
	public function getAdditiveSource(){}
	
	/**
	*@function: HAS_TAG = returns a boolean value refering to the seeking tag, descripted by xpath 
	* TODO muss auf xpath erweitert werden
	*/
	public function has_Tag()
	{
	
		return false;
	}
	

	public function set_list(&$value)
	{
		if(is_object($value))
		{
			$this->rst = &$value;
		}
	}
	
	
	
	
 
public function col($columnName)
{
	global $_SESSION;
	//echo "wupp";
	//$this->max
	
	if(array_key_exists($columnName,$_SESSION))
	{
		return $_SESSION[$columnName];
	}
	
	if($this->rst)
	{
	return $this->rst->col($columnName);
	}
	return null;
}

public function increase($columnName, $by)
{
	global $_SESSION;
	
	$incr = 1;
	if(!is_null($by)) $incr = $by;

	
	
	//$this->max
	if(!is_null($_SESSION[$columnName]))
	{
		if($_SESSION[$columnName] == '') $_SESSION[$columnName] = 0; 


		
		if(is_numeric($_SESSION[$columnName]))
		{
		$_SESSION[$columnName] +=$incr;
		
		}
	}
}

public function compare($arg1, $arg2, $op)
{
	global $_SESSION;
	
	$operator = '=';
	if(!is_null($op)) $operator = $op;

	
	//$this->max
	if(!is_null($_SESSION[trim($arg1)]) && !is_null($_SESSION[trim($arg2)]))
	{
		if($operator == '=')return ($_SESSION[trim($arg1)] == $_SESSION[trim($arg2)]) ? 'true' : 'false';
		if($operator == '<=')return ($_SESSION[trim($arg1)] <= $_SESSION[trim($arg2)]) ? 'true' : 'false';
		if($operator == '>=')return ($_SESSION[trim($arg1)] >= $_SESSION[trim($arg2)]) ? 'true' : 'false';
		if($operator == '<')return ($_SESSION[trim($arg1)] < $_SESSION[trim($arg2)]) ? 'true' : 'false';
		if($operator == '>')return ($_SESSION[trim($arg1)] > $_SESSION[trim($arg2)]) ? 'true' : 'false';
	}
	return 'false';
}

public function sessionOut($session_index)
{
	global $_SESSION;

//var_dump($_SESSION);
		if(!is_null($tmp = $_SESSION[$session_index]))
		{

			return $tmp;
		}
		else
		{
			$tmp = "";
			return $tmp;
		}
		
}

	public function create_group($group, $members)
	{

		$this->groups[$group] = array();
		$args = explode(',',$members);
		foreach( $args as $var)
		{
			array_push($this->groups[$group],trim($var));
		}

	}
	
	public function has_session_value($value, $mode)
	{ 
		$res = true;
		if(!$mode)$mode = 'any';
//var_dump($_SESSION, $this->groups, $value, $mode);
		if(is_array($this->groups[$value]))
		{

			if($mode == 'any')
			{
				$res = false;
				foreach($this->groups[$value] as $var)$res = $res || boolval($_SESSION[$var]);
			}else

				foreach($this->groups[$value] as $var)$res = $res && boolval($_SESSION[$var]);
	
		}
		else
			$res = boolval($_SESSION[$value]);
			
		  return boolval($res) ? 'true' : 'false';

	}

/**
* @function: 	sessionIn 	: overwrites sessions, previous needs NAME to set key to value
* @param: 	$session_index 	: sessionname
* @param:	$value		: new value
*/

public function sessionIn($session_index,$value)
		{
global $_SESSION;
//echo $value;

			$this->back->heap['session'][$session_index] = trim($value);
			$_SESSION[$session_index] = trim($value);
//var_dump($_SESSION);			
		}

	public function ifNotSet($session_index,$value)
		{
			global $_SESSION;
			
			if(!$_SESSION[$session_index])$this->sessionIn($session_index,$value);
			
		}
		
	public function freeSession($session_index)
		{
global $_SESSION;

			if($this->back->heap['session'][$session_index]) unset($this->back->heap['session'][$session_index]);
			if($_SESSION[$session_index]) unset($_SESSION[$session_index]);
			
		}

		public function test()
		{
			global $_SESSION;
			var_dump($_SESSION);
		}
		
		public function fields()
		{
			if($this->rst) $res = $this->rst->fields();else $res = array();}
			
			
			return array_merge(array_keys($_SESSION), $res); 



		}
}
?>
