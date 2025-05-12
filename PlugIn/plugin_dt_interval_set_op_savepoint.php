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
require_once("plugin_interface_multisource.php");
require_once("plugin_finite_automat.php");

class DT_Interval_Set_Op extends plugin_multisource
{

private $alias;
private $filename; 
private $hash; 
private $output;
private $first = true;
private $result = array();
private $search_tree = array();
private $tbl_collection = array();
private $from  = "from";
private $to = "to";
private $from_f = '';
private $to_f = '';
private $config = array();
private $inner_id = 0;
private $hashtbl = array();
private $keywords = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years' );
private $mod_name = '';
private $dimension = 0;
private $automat = null;
private $structure = array();

var $rst = null;

//var $obj = null;



	function __construct()
	{
		
		$this->automat = new FiniteAutomat(); 
		$this->automat->setNodes("start", "ident", "op", "stop"); //
		//$fsa->setStringGraph('start', 'start');
		$this->automat->setStringGraph('ident', 'ident');
		$this->automat->setGraph('start', 'ident', '$', 'next()');
		$this->automat->setGraph('ident', 'start', '+', 'op');
		$this->automat->setGraph('ident', 'start', '*', 'op');
		$this->automat->setGraph('ident', 'start', ':=', 'op');
		$this->automat->setGraph('ident', 'stop', '.' , 'next()');

		//
		//
		//
		//
		
		//
		/*
		
		$fsa->setStringGraph('start', 'hour');
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
		$fsa->setGraph('d_hour', 'start', '}'  , ''); */
		
		
		



		
	}
	

	public function col($columnname)
	{
		
	if($this->rst[0])
	{
		$pre = explode('.', $columnname);
		if(isset(
			$this->column_prefix[$pre[0]]
			))
		{

			$tmp = $this->rst[$this->column_prefix[array_shift($pre)]]->col( implode('.', $pre));
			//echo $tmp . ', ';
			return $tmp;
 }

			//echo $this->rst[0]->col($columnname) . ", ";
	  return $this->rst[0]->col($columnname);
	}
	  throw new ObjectBlockException('Recordset is missing');
	}
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}

	public function intervalConfig($name, $from, $from_f, $to, $to_f, $to_rad, $dim)
	{
		$conf = array();
		$conf['name'] = $name;
		$conf['from'] = array();
		$conf['from']['col'] = $from;
		$conf['from']['format'] = $from_f;
		//$conf['from']['mod'] = in_array(array_pop( explode(' ', trim($from_f)) ), $this->keywords);
		$conf['to'] = array();
		$conf['to']['col'] = $to;
		$conf['to']['format'] = $to_f;
		if($to_rad)
			$conf['to']['to_rad'] = $to_rad;
		else
			$conf['to']['to_rad'] = false;
		$conf['to']['mod'] = in_array(array_pop( explode(' ', trim($to_f)) ), $this->keywords);
		$conf['dimension_id'] = trim($dim); // explode(',',preg_replace("/\s+/", "", $dim));
		$this->config[] = $conf;
		//var_dump($this->config);
	}
		
	
	private function run()
	{
		

		if(count($this->rst) != count($this->config))echo " Config Exception";
		
		//visits all recordsets
		for($i=0;$i < count($this->rst) ;$i++)
		{
			$result = array();
			$col = $this->rst[$i]->fields();
			//foreach ($col as $value2) echo "$value2;";
			//echo "\n";
			$this->search_tree = array();
			
			if(!$this->rst[$i]->moveFirst())continue;
			
			// and walks though them
			do{
					//creates line to collect stuff from one record
					$line = array();
					
					//get names for distinct subsets
					$name = $this->rst[$i]->col($this->config[$i]['dimension_id']);
					//echo $name . " ";
					//echo $this->config[$i]['name']  . " :";
					
					//copy the record
					foreach ($col as $value)
					{
						$line[$value] = $this->rst[$i]->col($value);
						//echo $this->rst[$i]->col($value) . ";";
					}
					//echo "\n";

					//builds a hash storage to avoid redundant data
					$hash = $this->hashValue($line, array());
					if(!$this->hashtbl[$hash])$this->hashtbl[$hash] = $line;
					
					//builds a DateTime for "from"
					$from = DateTime::createFromFormat( 
						$this->config[$i]['from']['format'], 
						$this->rst[$i]->col($this->config[$i]['from']['col'])); 
					

					
					if(!$from)
					{	new Exception("Exception " . $this->config[$i]['name'] . "'" . $this->config[$i]['from']['format'] . "' '" . $this->config[$i]['from']['col'] . "' '" . $this->rst[$i]->col($this->config[$i]['from']['col']));
						return false;
					}
					
					//var_dump($from);
					
					
					//!mod shows, the "to" value is a modifier 
					if($this->config[$i]['to']['mod'])
					{
						// shifts the DateTime for "to" 
						$to = clone $from;
						$to->modify(sprintf($this->config[$i]['to']['format'], $this->rst[$i]->col($this->config[$i]['to']['col'])));
					}
					else
						$to =  DateTime::createFromFormat( $this->config[$i]['to']['format'], $this->rst[$i]->col($this->config[$i]['to']['col']));
					
					//edit grid 
					$to_grid_str = $to->format('Y-m-d H:i:s');
					if($this->config[$i]['to']['to_rad'])
						$to->modify('+' . $this->config[$i]['to']['to_rad'] . ' seconds');	
					
					$from_str = $from->format('Y-m-d H:i:s');
					$to_str = $to->format('Y-m-d H:i:s');
					//echo "From $from_str to $to_str \n";
					
					// add dimension (only 1 dim at this time)
					if(!is_array($result[$name]))$result[$name] = array(); 
					
					$result[$name][$from_str] = array('start', ++$this->inner_id, $hash);
					
					// add start of an interval to index
					if(!is_array($result[$name][$from_str]))
					{
						$result[$name][$from_str] = array('start', ++$this->inner_id, $hash);
					}
					else
					{
						// alter a interval stop into a add point
						if($result[$name][$from_str][0] == 'stop')
						{
							$result[$name][$from_str][0] = 'add';
							$result[$name][$from_str][2] = $hash;
						}
						
						//two start points become one and they get the hashes
						if($result[$name][$from_str][0] == 'start')
						{
							$result[$name][$from_str][] = $hash;
						}
					}
					
					// add a stop point or add point 
					if(!is_array($result[$name][$to_str]))
					{
						$result[$name][$to_str] = array('stop', $this->inner_id);
						$result[$name][$to_str][] = $hash;
					}
					else
					{
						if($result[$name][$from_str][0] == 'start')
						{
							$result[$name][$from_str][0] = 'add';
							$result[$name][$from_str][] = $hash;
						}
					}
					
					

					
			}
			while($this->rst[$i]->next());
			
			// the upper part creates a set of intervals with points in between
			foreach ($result as $key => $value)
			{
				ksort($result[$key]);
				$this->cleanUp($result[$key]);
			}

			
			$this->tbl_collection[$this->config[$i]['name']] = $result;
			unset($result);
			
		}

		

		$raw_parse = $this->automat->getResult();
		$process = array();
		$this->process($raw_parse, $process);
		if(false)
		{
		echo "------------------------------process---------------------------------\n";
		//var_dump($result);
		echo "RAW \n"; 
		var_dump($raw_parse);
		echo "structure \n"; 
		var_dump($process);
		
		foreach ($this->tbl_collection as $key => $value)
			echo "table:" . $key . "\n"; 
		
		echo "---------------------------------------------------------------------\n";
		
		echo "------------------------------result---------------------------------\n";
		//var_dump($result);
		//echo "Show tbl collection \n"; 
		var_dump($this->tbl_collection);
		echo "---------------------------------------------------------------------\n";
		}
		
	}
	
	//Builds up a very weak process list with deep of one and binary operations
	private function process($arg, &$forest)
	{

		$tmp_arg = array();
		$op = "";
		$ident = "";
			foreach ($arg as $key => $value)
			{
				
				if($value['op'] == ":=")
				{
					if($ident == "")
					{
						$ident = $value['ident'];
					}
					else
					{
						
						$this->plant_tree($ident, $op, $tmp_arg, $forest);

						//change ident
						$ident = $value['ident'];
						$op = "";
						$tmp_arg = array();
					}
					continue;
				}
						
				if($value['op'] == "+")$op = "+";
				if($value['op'] == "*")$op = "*";

				array_push($tmp_arg, $value['ident']);
			}
			
			$this->plant_tree($ident, $op, $tmp_arg, $forest);

	}
	
	//writes single trees of definition into the forest
	private function plant_tree($ident, $op, $arg, &$forest)
	{
						$tmp = null;
						$forest[$ident] = array( $op => $arg);
						
						foreach ($arg as $key => $value)
							{
								
								if(!$tmp)$tmp = $this->tbl_collection[$value];
								else
								{
									if($op == "+")$tmp = $this->union($tmp, $this->tbl_collection[$value]);
									if($op == "*")$tmp = $this->intersection($tmp, $this->tbl_collection[$value]);
								}
							}

					$this->tbl_collection[$ident] = $tmp;
	}
		
	
	public function inv ($interval)
	{
		$arg = $interval;
		foreach ($arg as $key => $value)
			if($value[0] == 'start' )$arg[$key][0] = 'stop';
			else $arg[$key][0] = 'start';
		
		array_unshift ($arg, array("0000-01-01 00:00:00"=>array('start', 0)));
		array_push ($arg, array("9999-12-31 23:59:59"=>array('stop', 0)));

		
		return $arg;
	}
	
	public function intersection($interval1, $interval2)
	{
		return $interval1;
	}
	
	public function union($interval1, $interval2)
	{
		$res = array();
		$keys = array_merge(array_keys ( $interval1 ), array_keys ( $interval2 ));

		foreach ($keys as $key)
		{
			if($interval1[$key] && $interval2[$key])	
				$interval1[$key] = array_merge($interval1[$key], $interval2[$key]);
			else
				if($interval2[$key])
					$interval1[$key] = $interval2[$key];

			ksort($interval1[$key]);
			$this->cleanUp($interval1[$key]);
		}
		//echo "#################################################";
	//var_dump($interval1);
	//echo "#################################################";
		return $interval1;
	}
	
	private function hashValue($myarray, $attract)
	{
		if(count($attract) == 0)return hash('sha256', '0', false);
		$str = '';
					foreach ($myarray as $key =>$value)
						if(in_array($key, $attract))
							$str .= $value;
					
		return hash('sha256', $str, false);
	}


	private function cleanUp(&$interval)
	{
		//reset($interval);
		$skey = '';
		$tmp; // = &$interval[key($interval)];
		$id = array();
		$iid = 0;
		$state = 0;
		// for any point in the set of intervals
					foreach ($interval as $key =>$value)
					{
						
						If($value[0] =='start')
						{
							if($state == 0)
							{
								$skey = $key;
								$tmp = &$interval[$key];
								$iid = $value[1];
								$id[$iid] =$iid;
								$state++;
							}
							else
							{

								$list = $interval[$key];
								unset($list[0]);
								unset($list[1]);
								$interval[$skey] =  array_merge($tmp, $list);
								unset($interval[$key]);
								$id[$value[1]] = $value[1];
								$state++;
							}
						}
						
						If($value[0] =='add')
						{
							if($state > 0)
							{
								$list = $interval[$key];
								unset($list[0]);
								unset($list[1]);
								$interval[$skey] =  array_merge($tmp, $list);
								unset($interval[$key]);
							}
						}
						
						If($value[0] =='stop')
						{
							if($state  > 1)
							{
								if(!$id[$value[1]])new Exception("hier exeption");
								else
								{
								unset($interval[$key]);
								$id[$value[1]] = false;
								$state--;
								}
							}
							elseif($state  == 1)
							{
								$interval[$skey] = array_unique($interval[$skey]);
								$state--;
								$id = array();
							}
							else
							{
								new Exception("hier exeption");
							}
						}
						
						
					}
	}

	public function setOperation($str)
	{
		$this->automat->checkString($str  . '.');
		$res = $this->automat->getResult(); 
		//$this->structure
		//echo "------------------------------------------------------------------------\n";
		//var_dump($res);
		//echo "------------------------------------------------------------------------\n";

	}
	
	function getAdditiveSource(){;}
	public function moveFirst(){ if($this->first){$this->first = false;$this->run();} if($this->rst[0])return $this->rst[0]->moveFirst(); else return false;}
    	public function moveLast(){if($this->first){$this->first = false;$this->run();}if($this->rst[0])return $this->rst[0]->moveLast();else return false;}
    	
	public function next(){if($this->rst[0])return $this->rst[0]->next();else return false;}

    	public function set_list(&$value)
    	{

    	if(is_object($value))
	{

		$this->rst[] = &$value;

	}
	else
	return 'no element received';
    	}


}
?>
