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

class Calendar extends plugin 
{
private $tag_name = false;
private $allocation = array();
private $std_default = array();
private $std_null = array();
private $cal_week_add = 0;
private $cal_first = 0;
private $cur_month = 1;
private $cal_cur_month = 30;
private $cal_pre_month = 31;
private $cal_fst_cw = 1;
private $cal_entries;
private $array_counter =0;
private $month_format = 'F-Y';
private $myDate;
private $mode = 'calendar';
var $rst = null;
var $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
//var $obj = null;
var $back =  null;
var $content = null;
var $computeNull = false;


var $param = array();
var $images = array();
var $tag;

	function __construct(/* System.Parser */ &$back, /* System.Content */ &$content)
	{
		
		$this->cal_entries = array_fill(0,31, array());
		//var_dump($this->cal_entries);
		$this->back= &$back;
		$this->content = &$content;
		
	}
	
	
	private function theFirst($datetime)
	{
		$datetime->modify( '- ' . (intval( $datetime->format('d') ) - 1) . ' days' );
		//var_dump($datetime->format('Y-m-d H:i:s'));
		//$datetime->modify($modifier);
	}
	public function setMode($mode)
	{
		$this->mode= $mode;
	}
	
	public function getDate($datetime, $format)
	{
			if($format)
				$date = DateTime::createFromFormat( $format, $datetime);
			else
				$date =  new DateTime($datetime);
				
			if(!$date)$date = new DateTime("first day of now");
				
			$date->modify('first day of');
			$date->setTime(00, 00, 00);
			//var_dump( $date);
			//if(!$to)$to = '+1 months';
			//$date = DateTime::createFromFormat( $format, $datetime);
			//$date
			//$this->theFirst($date);
			$this->myDate = $date;
			$prev= clone $date;
			$prev->modify('first day of next month');
			//var_dump( $prev);
			//$prev->modify('-1 months');
			//var_dump($prev->format('Y-m-d H:i:s'));
			$this->cal_first = $date->format('N');
			$this->cur_month = $date->format('n');
			$this->cal_cur_month = $date->format('t');
			//var_dump($this->cal_first, $this->cur_month);
			If($this->cur_month =='1' )
				$this->cal_fst_cw  = '1';
			else
				$this->cal_fst_cw  = $date->format('W');
			//var_dump($this->cal_fst_cw);
			$this->cal_pre_month = $prev->format('t');

			
	}
	
	public function collect_data($dt_col, $rel_col)
	{
		
	if($this->rst)
	{
		if(!$this->rst->moveFirst())return;
		
		do
		{

			$date = new DateTime($this->rst->col($dt_col));
		if($this->cur_month == ($date->format('n')))
			{
				
				$arr = explode(',', $rel_col);
				$tmp = array();
				$tmp[$dt_col] = $this->rst->col($dt_col);
				foreach ($arr as &$value) {

					//echo intval($date->format('j')) - 1 . " $value = " . $this->rst->col($value) . "\n";
					$tmp[$value] = $this->rst->col($value);

				}
				//echo $date->format('j') . " wird beschrieben \n";
				$this->cal_entries[intval($date->format('j')) - 1][]  = &$tmp;//= array( $value => $this->rst->col($value));
				unset($tmp);
				//$this->cal_entries[]
			}
		
	/*  if(isset($this->rel[$columnname]))
	  {		//TODO has to throw an exception
	  	  if($this->source === '')return "need source";
	  	  if(false === strpos($this->source, ','))
	  	  {
	  	  $date = new DateTime($this->rst->col($this->source));
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

	  return $booja; */
	  
	  	}while($this->rst->next());
	  	//var_dump($this->cal_entries);
	}
	  return 'no dataset';
	}
	
	private function calc_week(){return floor($this->cal_week_add / 7);}
	private function calc_wday(){return floor($this->cal_week_add % 7);}
	private function calc_this_month()
	{
		//echo $this->cal_week_add .  "-" .  ($this->cal_first + $this->cal_cur_month) . "(" . $this->cal_first . "/" . $this->cal_cur_month . ")\n";
				if((($this->cal_week_add + 2) -  $this->cal_first) > 0 
					&& 
				(($this->cal_week_add + 1) -  ($this->cal_first + $this->cal_cur_month)) < 0)
				return true;
				//if((($this->cal_week_add + 2) -  $this->cal_first) > 0 ) //&& (($this->cal_week_add + 1) -  ($this->cal_first + $this->cal_cur_month ))
				//	return  ((($this->cal_week_add + 1) -  $this->cal_first) % $this->cal_cur_month) + 1;
				//else 
					return false;
	}
	private function calc_day()
	{
		//echo $this->cal_week_add . " " . $this->cal_first . "\n";
				if((($this->cal_week_add + 2) -  $this->cal_first) > 0)
					return  ((($this->cal_week_add + 1) -  $this->cal_first) % $this->cal_cur_month) + 1;
				else
					return $this->cal_pre_month + (($this->cal_week_add + 2) -  $this->cal_first);
	}

	
	public function col($columnname)
	{

		if(strtolower($columnname) == 'cal_date')
			return $this->myDate->format($this->month_format); //$date->format($this->month_format);
		
		if(strtolower($columnname) == 'cal_week')
		{
			//echo $this->calc_week() . " " . $this->cal_fst_cw . "\n";
			return $this->calc_week() + $this->cal_fst_cw;
		}
		if(strtolower($columnname) == 'cal_wday')
			return $this->calc_wday();
	
		if(strtolower($columnname) == 'cal_day') //$this->cal_first +
		//	if(false !== ($tmp = $this->calc_wday()))
	//			return  $tmp;
	//	else
				return $this->calc_day();
				
		if(strtolower($columnname) == 'cal_wdayname')
			return $this->days[$this->calc_wday()];

		if(strtolower($columnname) == 'cal_thismonth')
			return ($this->calc_this_month()? 'true': 'false');
		
		if($this->calc_this_month())
		{
			//echo "   {Day:" . $this->calc_day() . " Counter:" . $this->array_counter . "  ($columnname) " . $this->cal_entries[$this->calc_day() - 1][$this->array_counter][$columnname] . "}, ";
			//var_dump($this->cal_entries[$this->calc_day() - 1]);
		return $this->cal_entries[$this->calc_day() - 1][$this->array_counter][$columnname];
		}

	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	
	function getAdditiveSource(){;}
	public function moveFirst()
	{
		if($this->mode == 'calendar')
		{
	$this->cal_week_add = 0;
	$this->array_counter = 0;
		}else
		{
			
	$this->cal_week_add = $this->cal_first - 1;
	$this->array_counter = 0;
		
		}
	return true;}
    	public function moveLast(){$this->cal_week_add = 34;return true;}
    	
    	
	public function next()
	{
		//echo "\n";
		$is_calendar = ($this->mode == 'calendar');
	// && ($this->mode == 'calendar')
	//echo $this->calc_this_month();
	if(!$this->calc_this_month() && $this->cal_week_add < 41 ) //34
		{
			$this->cal_week_add++;
		//echo "[(arraycounter1)" . str_pad($this->array_counter, 2, "0", STR_PAD_LEFT) . "/" . str_pad(count($this->cal_entries[$this->calc_day() - 1]) - 1, 2, "0", STR_PAD_LEFT)   . ", (tag)" . str_pad($this->calc_day(), 2, "0", STR_PAD_LEFT)  . ", (Position in tabelle)" . str_pad($this->cal_week_add, 2, "0", STR_PAD_LEFT)  . "]";
			return $is_calendar;
		}
		//var_dump($this->cal_entries);
// Dicker Fehler, den muss ich mal untersuchen
		if($this->calc_day() > 31)return false;
		if( $this->calc_day() > count($this->cal_entries)   )throw new ObjectBlockException('To many days in a month (out of bound) ' . $this->calc_day()  .  "-" .  ( count($this->cal_entries) - 1 ) ); //. $this->calc_day() - 1
		$cal_ent = $this->cal_entries[$this->calc_day() - 1];
		
		//var_dump($this->calc_day() );
		if(is_Null($cal_ent))throw new ObjectBlockException('Get Null Element on day ' . $this->calc_day()  .  " " .  count($this->cal_entries) ); //. $this->calc_day() - 1
		if($this->array_counter < (count($cal_ent) - 1) )
		{
			$this->array_counter++;
			
		//echo "[(arraycounter2)" . str_pad($this->array_counter, 2, "0", STR_PAD_LEFT)  . "/" . str_pad(count($this->cal_entries[$this->calc_day() - 1]) - 1, 2, "0", STR_PAD_LEFT) . ", (tag)" . str_pad($this->calc_day(), 2, "0", STR_PAD_LEFT)  . ", (Position in tabelle)" . str_pad($this->cal_week_add, 2, "0", STR_PAD_LEFT)  . "]";
			return true;	
		}
		elseif($this->cal_week_add < 41  )
		{
			$this->array_counter = 0;
			$this->cal_week_add++; 

		//echo "[(arraycounter3)" . str_pad($this->array_counter, 2, "0", STR_PAD_LEFT)  . "/" . str_pad(count($this->cal_entries[$this->calc_day() -1]) - 1, 2, "0", STR_PAD_LEFT) . ", (tag)" . str_pad($this->calc_day(), 2, "0", STR_PAD_LEFT)  . ", (Position in tabelle)" . str_pad($this->cal_week_add, 2, "0", STR_PAD_LEFT)  . "]";
		return ($is_calendar || $this->calc_this_month());
		}
		//echo "[(arraycounter)" . str_pad($this->array_counter, 2, "0", STR_PAD_LEFT)  . "/" . str_pad(count($this->cal_entries[$this->calc_day() - 1]) - 1, 2, "0", STR_PAD_LEFT)  . ", (tag)" . str_pad($this->calc_day(), 2, "0", STR_PAD_LEFT)  . ", (Position in tabelle)" . str_pad($this->cal_week_add, 2, "0", STR_PAD_LEFT)  . "]";
		return false;
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
