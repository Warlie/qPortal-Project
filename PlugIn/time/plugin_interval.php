<?PHP

/**
* @-------------------------------------------
* @title:Interval
* @autor:Stefan Wegerhoff
* @description: Zeitraster. Erzeugt Plaetze in einem Zeitraum (gleichmaessige Schritte mit
*               setInterval oder eine Beschreibung mit setConfigString) und legt Datensaetze
*               einer Quelle (set_list) auf den Platz mit demselben Zeitpunkt. Gelesen wird wie
*               eine Ergebnismenge.
*
* Abkunft (2026-10-11): Kopie von PlugIn/plugin_dt_interval.php (DT_Interval). Eigener
* Klassenname, damit alte und neue Fassung im selben Request geladen werden koennen. Behoben:
*  - getDate mit Format uebernahm die aktuelle Uhrzeit: das Raster lief je nach Tageszeit einen
*    Platz zu weit (gemessen: +4 month gab 5 Monate). Jetzt '!' vor dem Format.
*  - catchLevel reichte current() per Referenz weiter: eine Notice je Rasterplatz
*  - collect_data berechnete interval-in_seconds und ueberschrieb es eine Zeile spaeter
*  - interval-in_seconds rechnete mit 30 Tagen je Monat und 365 je Jahr - jetzt die echten
*    Sekunden zwischen Beginn und Ende
*  - setIntervalFormat(..., 'start_end') setzte das falsche Feld
*  - weekly mit Feiertag ('ho') uebersprang den ersten Feiertag; current() per Referenz
*  - weekly{ ho{...} } lieferte die Feiertage UND jeden Wochentag des Laufbeginns (fehlendes break)
*  - setNodes trug den Knoten " d_modify" mit Leerzeichen ein: Warnungen des Automaten je Lauf
*  - die Plaetze standen in der Reihenfolge des Anlegens, nicht der Zeit: ein Datensatz vor dem
*    Raster (anderes Jahr) kam ans Ende. Jetzt je Ebene geordnet, nur wo noetig (catchLevel)
*  - writeTree: $note ungesetzt, end() auf leerem Platz, die Zeile stand zusaetzlich unter [0]
*  - setColumns: weniger Vorgaben als Spalten gab Warnungen; col() ohne Platz ebenso
*  - fields() nannte ohne Quelle nichts, mit Quelle deren Spalten statt der eines Platzes;
*    datatype() fehlte ganz - Aggregation und Accumulation konnten Interval nicht lesen
*/
require_once(__DIR__ . '/../plugin_interface.php');
require_once(__DIR__ . '/../plugin_finite_automat.php');
require_once(__DIR__ . '/../plugin_holidays.php');

class Interval extends plugin 
{
private $columns = array();
private $main_col = '';
private $main_length = '';
private $unique_sign = '';
private $note = "note";
private $running_id = 'running_id';
private $running_counter = 0;
private $default = array();
private $cur_date;
private $prev_date;
private $next_date;
private $offset = 0;
private $full_time = array();
private $search_tree = array();
private $cur_pos = 0;
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
	
private $hours = array();
private $max = array();
private $count = array();
private $starts = '';
private $lastforformat = 'to_seconds';
private $lasttodatetime = '+ %d second';
private $start_end= 'H:i';

private $listOfDates = array();

private $filename; 
private $hash; 
private $output;

var $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	public function col($columnname)
	{



		$tmp = current($this->full_time);
		if(!is_array($tmp) || !isset($tmp[$this->cur_pos]) || !array_key_exists($columnname, $tmp[$this->cur_pos]))
			return null;
		return $tmp[$this->cur_pos][$columnname];

		
		

	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}


	private function theFirst($datetime)
	{
		$datetime->modify( '- ' . (intval( $datetime->format('d') ) - 1) . ' days' );
		//var_dump($datetime->format('Y-m-d H:i:s'));
		//$datetime->modify($modifier);
	}
		
	/**
	CUR_MONTH
	CUR_DAY
	CUR_YEAR
			cur_month
		cur_day
		cur_year
	
	*/
	public function getDate($datetime, $format, $to, $from = "first day of this month")
	{	
			/*
			$test = strtolower(trim($datetime));
			if($test=='cur_month')
			elseif($test=='cur_day')
			elseif($test=='cur_year')
			else*/
			if(!$from)$from = "first day of this month";
			//var_dump( $format, $datetime);
			// '!' setzt Ungenanntes (die Uhrzeit) auf null - sonst haengt das Ende des Rasters an der Tageszeit
			if($format)
				$date = DateTime::createFromFormat( ( $format[0] == '!' || $format[0] == '|' ? '' : '!') . $format, $datetime);
			else
				$date =  new DateTime($datetime);
				
			//if($datetime ==)
			if(!$to)$to = '+1 months';
			if(!$date)$date = new DateTime("first day of now");
			//var_dump($date);
			//$date
			//$this->theFirst($date);
			$this->cur_date = $date; 
			$this->prev_date = clone $this->cur_date;
			$this->prev_date->modify($from);
			
			$prev= clone $this->prev_date;
			$prev->modify($to);
			$this->next_date = $prev; 
			//var_dump($this->next_date);


			
	}
	
	// brauche noch einen Konfigstring
	public function setInterval($order, $hours)	
	{
		
		$this->hours[$order -1] = $hours;
	}
	
	public function setIntervalFormat($format, $used = 'last_out')
	{
		//echo $format . "\n";
		if($used == 'last_out')$this->lastforformat = $format;
		if($used == 'last_in')$this->lasttodatetime = $format;
		if($used == 'start_end')$this->start_end = $format;
	}
	/**
	<marks> ::= <mark> | <mark> <marks> 
	<mark> ::= <monthly> | <weekly> | <daily>
	<daily> ::= daily{<time>}
	<weekly> ::= weekly{<weekday>}
	<monthly> ::= monthly{<day>}
	<time> ::= time{<clock>}
	<weekday> ::= so{<time>} | mo{<time>} | di{<time>} | mi{<time>} | do{<time>} | fr{<time>} | sa{<time>}|ho{<time>}
	<day> ::= <day_in_month>{<time>}
	<clock> ::= bsp. 17:30( + 5 hours)
	
	
	
bsp: weekly{ mo{18:00(+10hours) } tu{18:00(+10hours) } we{18:00(+10hours) 8:00(+10hours)  } th{18:00(+10hours)} fr{ 18:00(+10hours) }sa{ 16:00(+10hours) } so{ 16:00(+10hours) } }
monthly{1{9:00(+10hours) }2{9:00(+10hours) }}daily{8:00(+10hours)}
	
	*/
	public function setConfigString($config)
	{//echo "setConfigString" . $config . "\n";
		if(!$config)$config = "daily{8:00(+10hours) 18:00(+14hours)}";

		$week = array('mo','tu', 'we', 'th', 'fr', 'sa', 'so', 'ho');
		
		
		//echo "$config \n";
		$fsa = new FiniteAutomat();
		$fsa->setNodes("start,monthly,m_body,m_day,m_modify,m_hour,m_minute,m_note,weekly,w_body,w_day,w_modify,w_hour,w_note,w_minute,daily,d_hour,d_minute,d_modify,d_note");
		$fsa->setGraph('start', 'monthly', 'monthly', 'mode');
		$fsa->setGraph('monthly', 'm_body', '{', '');
		for($i=1;$i < 32;$i++)$fsa->setGraph('m_body', 'm_day', strval($i) , 'day');
		$fsa->setGraph('m_day', 'm_hour', '{' , '');
		$fsa->setStringGraph('m_hour', 'hour');
		$fsa->setGraph('m_hour', 'm_minute', ':'  , '');
		
		$fsa->setGraph('m_minute', 'm_note', '['  , '');
		$fsa->setGraph( 'm_note', 'm_minute', ']'  , '');
		$fsa->setStringGraph('m_note', 'note');
		
		$fsa->setStringGraph('m_minute', 'minute');
		$fsa->setGraph('m_minute', 'm_modify', '('  , '');
		$fsa->setStringGraph('m_modify', 'modify');
		$fsa->setGraph('m_modify', 'm_hour', ')'  , 'next()');
		$fsa->setGraph('m_hour', 'm_body', '}'  , '');
		$fsa->setGraph('m_body', 'start', '}'  , '');
		
		$fsa->setGraph('start', 'weekly', 'weekly', 'mode');
		$fsa->setGraph('weekly', 'w_body', '{', '');
		$fsa->setGraph('w_body', 'w_day', $week , 'day');
		$fsa->setGraph('w_day', 'w_hour', '{' , '');
		$fsa->setStringGraph('w_hour', 'hour');
		$fsa->setGraph('w_hour', 'w_minute', ':'  , '');		
		$fsa->setStringGraph('w_minute', 'minute');
		
		$fsa->setGraph('w_minute', 'w_note', '['  , '');
		$fsa->setGraph( 'w_note', 'w_minute', ']'  , '');
		$fsa->setStringGraph('w_note', 'note');
		
		$fsa->setGraph('w_minute', 'w_modify', '('  , '');
		$fsa->setStringGraph('w_modify', 'modify');
		$fsa->setGraph('w_modify', 'w_hour', ')'  , 'next()');
		$fsa->setGraph('w_hour', 'w_body', '}'  , '');
		$fsa->setGraph('w_body', 'start', '}'  , '');
		
		$fsa->setGraph('start', 'daily', 'daily', 'mode');
		$fsa->setGraph('daily', 'd_hour', '{', '');
		$fsa->setStringGraph('d_hour', 'hour');
		$fsa->setGraph('d_hour', 'd_minute', ':'  , '');		
		$fsa->setStringGraph('d_minute', 'minute');
		
		$fsa->setGraph('d_minute', 'd_note', '['  , '');
		$fsa->setGraph( 'd_note', 'd_minute', ']'  , '');
		$fsa->setStringGraph('d_note', 'note');
		
		$fsa->setGraph('d_minute', 'd_modify', '('  , '');
		$fsa->setStringGraph('d_modify', 'modify');
		$fsa->setGraph('d_modify', 'd_hour', ')'  , 'next()'); 
		$fsa->setGraph('d_hour', 'start', '}'  , '');
		
		
		

		
		/*
		
		$fsa->setGraph('start', 'monthly', 'monthly', '$mode');
		$fsa->setGraph('start', 'monthly', 'monthly', '$mode'); */

		$fsa->checkString($config);
		$res = $fsa->getResult();
		//var_dump($res);
		$run= clone $this->prev_date;
		//$run->modify('first day of this month');

		$ends = clone $this->next_date;
		$ends->setTime(00, 00, 00);
		$run->setTime(00, 00, 00);
		
		$has_holiday = (false !== array_search('ho', array_column($res, 'day')));
		
		//if($has_holiday !== false)echo "gefunden";
		
		$list_of_holidays = array();
		$list_of_holidays_dt = array();
		
		$hol = clone $run;
		$holiday = new Holidays();
		//var_dump($res);
		if($has_holiday)
			while($hol < $ends )
			{
				//var_dump($hol);
				$hday = $holiday->feiertag ($hol);
				if($hday != 'Arbeitstag' && $hday != 'Wochenende')
				{
					$list_of_holidays_dt[] = clone $hol;
					$list_of_holidays[] = $hol->format('d') . $hol->format('m') ;  
				}
				$hol->modify('+1day');

			}
		
			//var_dump($list_of_holidays);
			//var_dump($list_of_holidays_dt);
			
		do
		{
		switch (current($res)['mode']) {
			case 'monthly':
				$tmp = clone $run;
				
				$tmp->modify('+' . (intval(current($res)['day']) - 1) . 'day'); 
				$tmp->setTime(current($res)['hour'], current($res)['minute'], 00);
				
				if(!($has_holiday 
					&& 
					(false !==array_search( $tmp->format('d') . $tmp->format('m'), $list_of_holidays )))
					)				
				$this->listOfDates[] = array($tmp, current($res)['modify'], current($res)['note']) ;  
				unset($tmp);
				
				//echo "monthly";
			break;
			case 'weekly':
			$tmp = clone $run;
			$weekday = array_search( current($res)['day'], $week ) + 1;
			//$tmp->modify('+1day');
			
			//echo " Weekday: $weekday, format:" .  $tmp->format( 'N' ) . " , day:" . $tmp->format( 'd' ) . "-" . $tmp->format( 'D' ) . " und das j macht" . $tmp->format( 'j' ) . "\n";
			
			if($weekday<8)
			
			if($weekday  < $tmp->format( 'N' ))
			{
				//echo "upper\n";
				$tmp->modify('+1week');
				$tmp->modify('-' .  ($tmp->format( 'N' ) -  $weekday) . 'day');
				
			}
			else
			{
				//echo "lower\n";
				//if($tmp->format( 'j' ) - ($weekday - $tmp->format( 'N' ) ) < 1)$tmp->modify('+1week');
				$tmp->modify('+' .  ($weekday - $tmp->format( 'N' ) ) . 'day');
			}
			
			else
			{
					
					foreach($list_of_holidays_dt as $toClone)
					{
						$myhol = clone $toClone;
						$myhol->setTime(current($res)['hour'], current($res)['minute'], 00);

						$this->listOfDates[] = array($myhol, current($res)['modify'], current($res)['note']) ;
					}
					// nur die Feiertage - ohne break lief es weiter in die Wochenschleife und trug
					// zusaetzlich JEDEN Wochentag des Laufbeginns ein (gemessen: Mai 2024 alle Mittwoche)
					break;
				
			}
			

			//echo " After set Weekday $weekday, " .  $tmp->format( 'N' ) . " , day:" . $tmp->format( 'd' ) . "-" . $tmp->format( 'D' ) . "\n";
			
			$tmp->setTime(current($res)['hour'], current($res)['minute'], 00);

			
			while($tmp < $ends )
			{
				if(!($has_holiday 
					&& 
					(false !==array_search( $tmp->format('d') . $tmp->format('m'), $list_of_holidays )))
					)
				$this->listOfDates[] = array(clone $tmp, current($res)['modify'], current($res)['note']) ;  

				$tmp->modify('+1week');

			}

			break;
			case 'daily':
			$tmp = clone $run;
			//var_dump(current($res)['hour'], current($res)['minute']);
			$tmp->setTime(current($res)['hour'], current($res)['minute'], 00);
			while($tmp < $ends )
			{

				if(!($has_holiday 
					&& 
					(false !==array_search( $tmp->format('d') . $tmp->format('m'), $list_of_holidays )))
					)
						$this->listOfDates[] = array(clone $tmp, current($res)['modify'], current($res)['note']) ;
						
				$tmp->modify('+1day');

			}
			break;
		}
		
		}
		while(next($res) !== false);
		
		//var_dump($this->listOfDates);
		//$fsa->showAll();
		/*
		$week = array();
		$weekday = array('so', 'mo', 'tu', 'we', 'th', 'fr', 'sa');
		
		$arr = sscanf ( $config , "weekly{ %s }" );
		foreach( $arr as $value1)
			{
				echo "$value1 \n";
				
				foreach( $weekday as $cday)
				{
					echo $cday  . "{%s}";
				$week = array_merge($week, sscanf ( $value1 , $cday  . "{%s}"));
				}
			}
		var_dump($week);
		*/
		//var_dump($arr);
	}
	
	public function setOffset($offset)
	{
		$this->offset = $offset;
	}
	
	public function setColumns($dt_col, $until, $delimiter, $columns, $default, $note)
	{
		$this->main_col = $dt_col;
		$this->main_length = $until;
		$this->unique_sign = $delimiter;
		$keys = explode ( $delimiter , $columns);
		$values = explode ( $delimiter , $default);
		for($i = 0;$i < count( $keys ); $i++ )
		{
			$this->columns[$keys[$i]] = isset($values[$i]) ? $values[$i] : '';
		}
		if($note)$this->note = $note;
		
	}
	
	public function setRunningID($name)
	{
		$this->running_id = $name;
	}
	
	private function &findintree(&$date)
	{
				$arg = explode(',', $date->format('Y,n,j,H,i,s'));
		
		
		$tmp = &$this->search_tree;
		$swap;
		for($i = 0;$i < count($arg ); $i++)
		{
			$val = intval($arg[$i]);
		if(!isset($tmp[ $val ]))
			$tmp[$val] = array();
			$swap = &$tmp[$val];
			unset($tmp);
			$tmp = &$swap;
			unset($swap);
		}
		return $tmp;
	}
	
	private  function addTree(&$date, $cycle, $note)
	{



		$tmp = &$this->findintree($date);
		$to = clone $date;
		//var_dump($this->hours[$cycle]);
		$lastfor = $date->diff($to->modify($cycle)); 

		
		$values = array( $this->unique_sign . 'default' => true, $this->running_id => $this->running_counter++, $this->main_col => $date->format('Y-m-d H:i:s'));
		$values['interval-in_seconds'] = $to->getTimestamp() - $date->getTimestamp();
               		//echo $this->lastforformat;
		foreach($this->columns as $key => $value)
		{	
			
			//echo $lastfor->format($this->lastforformat) . "\n";
			/*
			if($value == '<timeinterval>')
				$values[$key]  =  $date->format($this->start_end) . '-' . $to->format($this->start_end); */
			if($value == '<lastfor>')
				if($this->lastforformat =='in_seconds')
					$values[$key]  =  $values['interval-in_seconds'];
				else
					$values[$key]  =  $lastfor->format($this->lastforformat);
			else
				$values[$key] = $value;
		}
		

		$values[$this->note]  =  $note;
		
		$values['interval-duration-year']  =  $lastfor->format('%y');
		$values['interval-duration-month']  =  $lastfor->format('%m');
		$values['interval-duration-day']  =  $lastfor->format('%d');
		$values['interval-duration-hour']  =  $lastfor->format('%h');
		$values['interval-duration-minute']  =  $lastfor->format('%i');
		$values['interval-duration-second']  =  $lastfor->format('%s');
		$values['interval-duration']  =   $lastfor->format('P%yY%mM%dDT%hH%iM%sS') ;



		$values['interval-time']  =  $date->format($this->start_end) . '-' . $to->format($this->start_end);
		$values['start-dt']  =  $date->format('Y-m-d H:i:s');
		$values['start-minute']  =  $date->format('i');
		$values['start-hour']  =  $date->format('H');
		$values['start-day']  =  $date->format('j');
		$values['start-month']  =  $date->format('n');
		$values['start-year']  =  $date->format('Y');
		$values['end-dt']  =  $to->format('Y-m-d H:i:s');
		$values['end-minute']  =  $to->format('i');
		$values['end-hour']  =  $to->format('H');
		$values['end-day']  =  $to->format('j');
		$values['end-month']  =  $to->format('n');
		$values['end-year']  =  $to->format('Y'); 

		$tmp[] = $values;
		
		//var_dump($values);
		
		
		
		
	}
	
	private  function writeTree(&$date, $array)
	{
		$tmp = &$this->findintree($date);
		$note = null;

		// liegt auf dem Platz der Vorgabewert des Rasters, ersetzt ihn der Datensatz; die Notiz bleibt
		$last = end($tmp);
		if(is_array($last) && !empty($last[$this->unique_sign . 'default']))
		{
			$el = array_pop($tmp);
			$note = isset($el[$this->note]) ? $el[$this->note] : null;
		}

		$ref = $array;

		//note is always on default, when on grid
		if($note)$ref[$this->note] = $note;

		$tmp[] = $ref;
		
		
	}
	
	private function buildList()
	{
		//var_dump($this->search_tree);
		$this->catchLevel($this->search_tree, 6);
		
		/*
		$res = true;
		for($i=count($this->rst)-1;$i > 0 ;$i--)
			if($this->rst[$i]->next())
				return true;
			else
			{
				$this->rst[$i]->moveFirst();
			}
	
		return $this->rst[0]->next();	
		*/
		//var_dump($this->full_time);
	}
	
	private function catchLevel(&$arr, $deep)
	{
		if($deep > 0)
		{
		if(!is_array($arr) || count($arr) == 0)
			return false;
		// Zeitlich ordnen: der Baum (Jahr, Monat, Tag, Stunde, Minute, Sekunde) haelt die Schluessel in
		// der Reihenfolge des Anlegens - ein Datensatz vor dem Raster stand sonst am Ende. Erst pruefen,
		// dann sortieren: eine schon geordnete Ebene (der Normalfall) kostet nur einen Durchgang.
		$keys = array_keys($arr);
		for($i = 1; $i < count($keys); $i++)
			if($keys[$i] < $keys[$i - 1])
			{
				ksort($arr, SORT_NUMERIC);
				$keys = array_keys($arr);
				break;
			}
		foreach($keys as $key)
			$this->catchLevel($arr[$key], $deep - 1);
		}
		else
			$this->full_time[]  = &$arr;
	}
	
	private function for_cycling_time()
	{
				$run= clone $this->cur_date;
		$ends = clone $this->next_date;

		$run->setTime(00, 00, 00);
		$i = 0;

		if($this->offset)
		{
			$run->modify($this->offset);
			$ends->modify($this->offset);
		}

		while($run < $ends )
		{
		$this->addTree($run, $this->hours[$i],"");

		//var_dump($this->next_date, $run);

		$run->modify($this->hours[$i]);
		
		$i = ($i + 1 ) % count($this->hours);
		}

	}
	/**
	 * 
	*/
	private function collect_data()
	{

//echo 'collect data' . "\n";
//var_dump($this->listOfDates);
		
		if(count($this->listOfDates) == 0)
		{
			if(count($this->hours) > 0)$this->for_cycling_time();
			
		}
		else
			foreach($this->listOfDates as $data)
			{
				$this->addTree($data[0], $data[1], $data[2] );
				
						//var_dump($this->search_tree);
			}
		
		reset($this->full_time);
		//var_dump($this->full_time);
		
		
		if($this->rst && $this->rst->moveFirst())
		{
			
			
			
			do
			{

				//echo $this->main_col . " " . $this->main_length . " \n";
				$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $this->rst->col($this->main_col));
				if(!$date)throw new Exception($this->rst->col($this->main_col) . " is not a valid date\n");

				$to = clone $date;
  

				if($this->main_length)
				{
					$to->modify(sprintf($this->lasttodatetime, $this->rst->col($this->main_length)));
					//echo sprintf($this->lasttodatetime, $this->rst->col($this->main_length));
				}
				
				$lastfor = $date->diff($to); 
				//sprintf($format, $anzahl, $ort);
				$cur = array( $this->unique_sign . 'default' => false,  $this->running_id => $this->running_counter++, $this->main_col => $this->rst->col($this->main_col), $this->note => $this->rst->col($this->note));
				// erst NACH dem Anlegen - vorher wurde es von $cur = array(...) ueberschrieben
				$cur['interval-in_seconds'] = $to->getTimestamp() - $date->getTimestamp();
//<lastfor>
//var_dump($this->columns);
					foreach($this->columns as $key => $value)
						$cur[$key] = $this->rst->col($key);
					
		// $this->note			
		$cur['interval-duration-year']  =  $lastfor->format('%y');
		$cur['interval-duration-month']  =  $lastfor->format('%m');
		$cur['interval-duration-day']  =  $lastfor->format('%d');
		$cur['interval-duration-hour']  =  $lastfor->format('%h');
		$cur['interval-duration-minute']  =  $lastfor->format('%i');
		$cur['interval-duration-second']  =  $lastfor->format('%s');
		$cur['interval-duration']  =  $lastfor->format('P%yY%mM%dDT%hH%iM%sS') ;

		$cur['interval-time']  =  $date->format($this->start_end) . '-' . $to->format($this->start_end);
		$cur['start-dt']  =  $date->format('Y-m-d H:i:s');
		$cur['start-minute']  =  $date->format('i');
		$cur['start-hour']  =  $date->format('H');
		$cur['start-day']  =  $date->format('j');
		$cur['start-month']  =  $date->format('n');
		$cur['start-year']  =  $date->format('Y');
		$cur['end-dt']  =  $to->format('Y-m-d H:i:s');
		$cur['end-minute']  =  $to->format('i');
		$cur['end-hour']  =  $to->format('H');
		$cur['end-day']  =  $to->format('j');
		$cur['end-month']  =  $to->format('n');
		$cur['end-year']  =  $to->format('Y'); 
						//var_dump($cur);
					$this->writeTree($date, $cur);
				
				
				
			}
			while($this->rst->next());
			//var_dump($this->sum);
	  //return 'no dataset';
	  //var_dump($this->full_time);

	 
		
	}
	//var_dump($this->search_tree);
	
	$this->buildList();
	//var_dump($this->full_time);
	}
	
	function getAdditiveSource(){;}

	public function moveFirst()
		{

			if(count($this->full_time) == 0)$this->collect_data();

				$this->cur_pos = 0;
				return (reset($this->full_time) !== false);

					
				
			
				

		}
		
    	public function moveLast()
    	{
			if(count($this->full_time) == 0)$this->collect_data();
			
			if(end($this->full_time) !== false)
			{
				
				$tmp = current($this->full_time);
				if(count($tmp) == 0)return false;
				$this->cur_pos = count($tmp) - 1;
				return true;
			}	
			return false;
				
			
    	}
    	
	public function next()
	{
			if(count($this->full_time) == 0)$this->collect_data();
 

				$tmp = current($this->full_time);

				$this->cur_pos++;
				
				if($tmp && count($tmp) > $this->cur_pos)
					return true;
				else
				{
					$this->cur_pos=0;
				}
				return (next($this->full_time) !== false);
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

	/** die Spalten, die Interval selbst erzeugt - je Platz, ob Raster oder Datensatz */
	private static $own_columns = array(
		'interval-in_seconds'       => 'bigint(20)',
		'interval-duration-year'    => 'int(11)',
		'interval-duration-month'   => 'int(11)',
		'interval-duration-day'     => 'int(11)',
		'interval-duration-hour'    => 'int(11)',
		'interval-duration-minute'  => 'int(11)',
		'interval-duration-second'  => 'int(11)',
		'interval-duration'         => 'varchar(32)',
		'interval-time'             => 'varchar(32)',
		'start-dt'                  => 'datetime',
		'start-minute'              => 'int(11)',
		'start-hour'                => 'int(11)',
		'start-day'                 => 'int(11)',
		'start-month'               => 'int(11)',
		'start-year'                => 'int(11)',
		'end-dt'                    => 'datetime',
		'end-minute'                => 'int(11)',
		'end-hour'                  => 'int(11)',
		'end-day'                   => 'int(11)',
		'end-month'                 => 'int(11)',
		'end-year'                  => 'int(11)',
	);

	/**
	* Die Spalten eines Platzes - genau die, die col() liefert. Bis 2026-10-11 ohne Quelle leer und
	* mit Quelle deren Spalten, von denen ein Platz nur die erklaerten (setColumns) traegt: ein
	* Konsument wie Aggregation, der seine Zeilen aus fields() baut, bekam nichts oder Leeres.
	*/
	public function fields()
	{
		return array_merge(
			array($this->main_col, $this->running_id, $this->unique_sign . 'default'),
			array_keys($this->columns),
			array($this->note),
			array_keys(self::$own_columns));
	}

	/**
	* Der Typ einer Spalte, in der Sprache der Datenbank (wie DBO: 'decimal(10,2)', 'int(11)', ...),
	* damit Aggregation summiert, wo es Zahlen sind.
	*  - eigene Spalten: fest (start-dt datetime, interval-in_seconds bigint, ...)
	*  - erklaerte Spalten: der Typ der Quelle, wenn sie einen kennt; sonst aus dem Vorgabewert -
	*    eine Zahl ist decimal, <lastfor> je nach Ausgabeformat bigint oder varchar, sonst varchar
	*  - unbekannt: false (wie plugin::datatype)
	*/
	public function datatype($columnname)
	{
		if(isset(self::$own_columns[$columnname])) return self::$own_columns[$columnname];
		if($columnname === $this->running_id) return 'int(11)';
		if($columnname === $this->unique_sign . 'default') return 'tinyint(1)';

		$declared = array_key_exists($columnname, $this->columns);
		if(!$declared && $columnname !== $this->main_col && $columnname !== $this->note) return false;

		// die Quelle zuerst - sie weiss es
		if($this->rst && method_exists($this->rst, 'datatype'))
		{
			$type = $this->rst->datatype($columnname);
			if($type) return $type;
		}

		if($columnname === $this->main_col) return 'datetime';
		if(!$declared) return 'varchar(255)';

		$default = $this->columns[$columnname];
		if($default === '<lastfor>') return ($this->lastforformat == 'in_seconds' ? 'bigint(20)' : 'varchar(32)');
		if(is_numeric($default)) return 'decimal(20,6)';
		return 'varchar(255)';
	}
    	
}
