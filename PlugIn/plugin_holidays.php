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

class Holidays extends plugin 
{

private $is_holiday = 'is_holiday';
private $name_of_holiday = 'holiday_name';
private $country = 'NW';
private $dt = '';
private $dt_format = '';
private $alias;
private $filename; 
private $hash; 
private $output;

var $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	/**
 * Ermittle Feiertage, Arbeitstage und Wochenenden von einem Datum
 *
 * @param $datum als String im Format Y-m-d oder als Timestamp
 * @param string $bundesland<br>
 * 	BW = Baden-Württemberg<br>
 * 	BY = Bayern<br>
 * 	BE = Berlin<br>
 * 	BB = Brandenburg<br>
 * 	HB = Bremen<br>
 * 	HH = Hamburg<br>
 * 	HE = Hessen<br>
 * 	MV = Mecklenburg-Vorpommern<br>
 * 	NI = Niedersachsen<br>
 * 	NW = Nordrhein-Westfalen<br>
 * 	RP = Rheinland-Pfalz<br>
 * 	SL = Saarland<br>
 * 	SN = Sachsen<br>
 * 	ST = Sachsen-Anhalt<br>
 * 	SH = Schleswig-Holstein<br>
 * 	TH = Thüringen
 * @return 'Arbeitstag', 'Wochenende' oder Name des Feiertags als String
 */
 
 public function setColumnNames($is_holiday, $name, $date, $date_format)
 {
 	 if($is_holiday)$this->is_holiday = $is_holiday;
 	 if($name)$this->name_of_holiday = $name;
 	 if($date)$this->dt = $date;
 	 if($date_format)$this->dt_format = $date_format;
 	 
 }
 
public function feiertag ($datum, $bundesland='')
{
    $bundesland = strtoupper($bundesland);

    if (!is_object($datum))
    {
        $datum = date("Y-m-d", $datum);
    }
    $datum = explode("-", $datum->format("Y-m-d"));

    $datum[1] = str_pad($datum[1], 2, "0", STR_PAD_LEFT);
    $datum[2] = str_pad($datum[2], 2, "0", STR_PAD_LEFT);

    if (!checkdate($datum[1], $datum[2], $datum[0])) return false;

    $datum_arr = getdate(mktime(0,0,0,$datum[1],$datum[2],$datum[0]));


    $easter = new DateTime('@' . easter_date($datum[0]));
 
    $easter->setTimezone(new DateTimeZone('Europe/Berlin'));
    
    $easter_d = $easter->format('d');
    $easter_m = $easter->format('m');

    $status = 'Arbeitstag';
    if ($datum_arr['wday'] == 0 || $datum_arr['wday'] == 6) $status = 'Wochenende';

    if ($datum[1].$datum[2] == '0101')
    {
        return 'Neujahr';
    }
    elseif ($datum[1].$datum[2] == '0106'
    	&& ($bundesland == 'BW' || $bundesland == 'BY' || $bundesland == 'ST'))
    {
        return 'Heilige Drei Könige';
    }
    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d-2,$datum[0])))
    {
        return 'Karfreitag';
    }
    elseif ($datum[1].$datum[2] == $easter_m.$easter_d)
    {
        return 'Ostersonntag';
    }
    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+1,$datum[0])))
    {
        return 'Ostermontag';
    }
    elseif ($datum[1].$datum[2] == '0501')
    {
        return 'Erster Mai';
    }
    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+39,$datum[0])))
    {
        return 'Christi Himmelfahrt';
    }
    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+49,$datum[0])))
    {
        return 'Pfingstsonntag';
    }
    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+50,$datum[0])))
    {
        return 'Pfingstmontag';
    }
    elseif ($datum[1].$datum[2] == date("md",mktime(0,0,0,$easter_m,$easter_d+60,$datum[0]))
    	&& ($bundesland == 'BW' || $bundesland == 'BY' || $bundesland == 'HE' || $bundesland == 'NW' || $bundesland == 'RP' || $bundesland == 'SL' || $bundesland == 'SN' || $bundesland == 'TH'))
    {
        return 'Fronleichnam';
    }
    elseif ($datum[1].$datum[2] == '0815'
    	&& ($bundesland == 'SL' || $bundesland == 'BY'))
    {
        return 'Mariä Himmelfahrt';
    }
    elseif ($datum[1].$datum[2] == '1003')
    {
        return 'Tag der deutschen Einheit';
    }
    elseif ($datum[1].$datum[2] == '1031'
    	&& ($bundesland == 'BB' || $bundesland == 'MV' || $bundesland == 'SN' || $bundesland == 'ST' || $bundesland == 'TH'))
    {
        return 'Reformationstag';
    }
    elseif ($datum[1].$datum[2] == '1101'
    	&& ($bundesland == 'BW' || $bundesland == 'BY' || $bundesland == 'NW' || $bundesland == 'RP' || $bundesland == 'SL'))
    {
        return 'Allerheiligen';
    }
    elseif ($datum[1].$datum[2] == strtotime("-11 days", strtotime("1 sunday", mktime(0,0,0,11,26,$datum[0]))) 
    	&& $bundesland == 'SN')
    {
        return 'Buß- und Bettag';
    }
    elseif ($datum[1].$datum[2] == '1224')
    {
        return 'Heiliger Abend (Bankfeiertag)';
    }
    elseif ($datum[1].$datum[2] == '1225')
    {
        return '1. Weihnachtsfeiertag';
    }
    elseif ($datum[1].$datum[2] == '1226')
    {
        return '2. Weihnachtsfeiertag';
    }
    elseif ($datum[1].$datum[2] == '1231')
    {
        return 'Silvester (Bankfeiertag)';
    }
    else
    {
        return $status;
    }
}
	
	public function col($columnname)
	{

	if($this->rst)
	{
		if(!is_null($this->rst->col($this->dt)))
		{
		if($this->is_holiday == $columnname)
			return 'Arbeitstag' != $this->feiertag (DateTime::createFromFormat($this->dt_format, $this->rst->col($this->dt)), $this->country);

		if($this->name_of_holiday == $columnname)
	  	  return $this->feiertag (DateTime::createFromFormat($this->dt_format, $this->rst->col($this->dt)), $this->country);
	  	}

	return $this->rst->col($columnname);
	}
	
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}

	
	public function setDate($column, $format)
	{
		$this->dt = $column;
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
