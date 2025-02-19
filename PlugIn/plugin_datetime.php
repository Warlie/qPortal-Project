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
private $mod = array();
private $source;

private $year = null;
private $month = null;
private $day = null;
private $hour = null;
private $minute = null;



	function __construct(){}
	
	public function toDateTime($format, $year, $month, $day, $hour, $minute, $second)
	{
		//echo "$year, $month, $day, $hour, $minute, $second \n";
		$arg1 = '';
		$arg2 = '';
		if(is_numeric($year) )
		{
			$arg1 .= "Y";
			$arg2 .= str_pad($year, 4, "2010", STR_PAD_LEFT) ;
		}
			
		if(is_numeric($month) )
		{
			$arg1 .= '-m';
			$arg2 .= '-' .  str_pad($month, 2, "0", STR_PAD_LEFT);
		}
			
		if(is_numeric($day) )
				{
					$arg1 .= '-d';
					$arg2 .= '-' . str_pad($day, 2, "0", STR_PAD_LEFT);
				}
			
			
		
		if(is_numeric($hour) )
		{
			$arg1 .= ' H';
			$arg2 .= str_pad($hour, 2, "0", STR_PAD_LEFT) ;
		}
			
		if(is_numeric($minute) )
			{
			$arg1 .= ':i';
			$arg2 .= ':' . str_pad($minute, 2, "0", STR_PAD_LEFT) ;
			}
				
		if(is_numeric($second) )
			{
					$arg1 .= ':s ';
					$arg2 .= ':' . str_pad($second, 2, "0", STR_PAD_LEFT) ;
			}

		//throw new Exception('Division durch Null.');
		//echo " $arg1, $arg2 ";
		$date = DateTime::createFromFormat($arg1, $arg2);
		return $date->format($format);
	}
	
	public function now($format)
	{
		$date = new DateTime('NOW');
		return $date->format($format);
	}

	/*
	public set_atomic_col($year, $month, $day, $hour, $minute, $td)
	{
		
	}
	*/
	/**
	TODO 
	if source is commaseperated, than collect (yy, mm, dd, hh, ii, ss )
	*/
	
	public function col($columnname)
	{

	if($this->rst)
	{

	  if(isset($this->rel[$columnname]))
	  {		//TODO has to throw an exception
	  	  if($this->source === '')return "need source";
	  	  if(false === strpos($this->source, ','))
	  	  {
	  	  	  
	  	  $date = new DateTime($this->rst->col($this->source));
	  	  if($this->mod[$columnname])$date->modify($this->mod[$columnname]);
	  	  return $date->format($this->rel[$columnname]);
	  	  }
	  	  else
	  	  {
	  	  	  $arr = explode(',', $this->source);
	  	  	  $tmp = $this->toDateTime(
	  	  	  	  $this->rel[$columnname], 
	  	  	  	  $this->rst->col((count($arr) > 0 ? $arr[0] : '')), 
	  	  	  	  $this->rst->col((count($arr) > 1 ? $arr[1] : '')),
	  	  	  	  $this->rst->col((count($arr) > 2 ? $arr[2] : '')),
	  	  	  	  $this->rst->col((count($arr) > 3 ? $arr[3] : '')),
	  	  	  	  $this->rst->col((count($arr) > 4 ? $arr[4] : '')),
	  	  	  	  $this->rst->col((count($arr) > 5 ? $arr[5] : '')));
	  	  	  //echo $tmp;
	  	  	  return $tmp;
	  	  }

	  }
	  $booja = $this->rst->col($columnname);
	  //echo $booja . " \n"; 
	  return $booja;
	}
	  return 'no dataset';
	}
	
	public function setsource($column){ $this->source = $column; }
	public function setcolumn($column, $format, $modify){ $this->rel[$column] = $format; if($modify)$this->mod[$column] = $modify;}
	



	
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
    	
    	public function fields(){if($this->rst[0]) return $this->rst[0]->fields();else return array();}

}