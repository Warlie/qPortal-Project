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

class Sum extends plugin 
{

private $sum = array();
private $max = array();
private $count = array();
private $group_col = '';
private $columns = array();
private $out_column = array();
private $format =array();
private $filename; 
private $hash; 
private $output;
private $ignore = true;
private $group_output = null;     // Ausgabename der Gruppenspalte
private $sum_outputs = array();   // Ausgabename => Spalte der Quelle, in Reihenfolge der Angabe
private $sorted = false;          // sortGroups(): Gruppen nach ihrem Wert ordnen

var $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	public function col($columnname)
	{

	if($this->rst)
	{


		
		$tmp = current($this->sum);

		// die Gruppenspalte unter ihrem Ausgabenamen. Bis 2026-10-12 lief sie ueber
		// out_column[''] (undefinierter Schluessel, Warnung) und kam erst ganz unten an.
		if($columnname === $this->group_output) return $tmp[$this->group_col];
		
		//if(strtolower($columnname) == 'sum_max')return $this->max;
	
		$col_array = explode('.', $columnname);
		
		$com = array_pop($col_array);
		$col_name = $this->out_column[implode('.', $col_array)] ?? null;

		if(strtolower($com) == 'count')
		return $tmp[$col_name]['count'];

		if(strtolower($com) == 'sum')
		return $this->result_format($this->format[$col_name] ,$tmp[$col_name]['sum']);

		if(strtolower($com) == 'max_sum')
		return $this->result_format($this->format[$col_name] ,$this->max[$col_name]['sum']);
	
		if(strtolower($com) == 'max_count')
		return $this->max[$col_name]['count'];

		return $tmp[$this->out_column[$columnname]];
	//return $this->rst->col($columnname);
	}
	
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}

	
	public function groups($column, $output)
	{
		if(!$output)$output = $column;
		$this->group_col = $column;
		$this->group_output = $output;
		$this->out_column[$output] = $column;
	}
	
	public function sum($column, $output, $format, $special)
	{
		
		if(!$output)$output = $column;
		$this->columns[] = $column;
		if($format)
			$this->format[$column] = $format;
		else
			$this->format[$column] = 'float';
		//$this->format[$column] = 'float';
		$this->out_column[$output] = $column;
		$this->sum_outputs[$output] = $column;
	}
	
	public function ignoreEmpty(){$this->ignore = false;}

	/** die Gruppen nach ihrem Wert ordnen (Jahr, Monat) statt in der Reihenfolge des ersten Auftretens -
	*   die Quelle ist ja unsortiert. Ohne Argument: ein Remote-Parameter bliebe stehen. */
	public function sortGroups(){$this->sorted = true; $this->sum = array();}
		
	private function neutral_format($format)
	{
		switch ($format) {
			case 'int':
				return 0;
        		break;
			case 'float':
				return 0.0;
        		break;
			case 'interval':

				return new DateInterval('P0Y0DT0H0M0S');
        		break;
        	}
	}

	private function add_format($format,$value1, $value2 )
	{
		switch ($format) {
			case 'int':
				return $value1 + intval($value2);
        		break;
			case 'float':
				return $value1 + floatval($value2);
        		break;
			case 'interval':

				$d = new DateTime('@0');
				$d->add($value1);
				$di = new DateInterval($value2);		
				$d->add(new DateInterval($value2));
				$diff = new DateTime('@0');
				return $diff->diff($d);
        		break;
        	}
	}
	
	private function result_format($format,$value)
	{
		switch ($format) {
			case 'int':
				return strval($value);
        		break;
			case 'float':
				return strval($value);
        		break;
			case 'interval':

				return $value->format('%d-%h:%i');
        		break;
        	}
	}
	
	private function collect_data()
	{

		if($this->rst)
		{
			$this->rst->moveFirst();
			
			foreach ($this->columns as $value)
			{


				$this->max[ $value] = array();
				$this->max[ $value]['sum'] = $this->neutral_format( $this->format[$value] );
				$this->max[ $value]['count'] = 0;
			}
			
			do
			{
				$tmp = $this->rst->col($this->group_col );
				if(!($tmp) && !$this->ignore)continue;
				
				if(!isset( $this->sum[	$tmp ] ))
				{
						 $this->sum[ $tmp ] = array();
						 $this->sum[ $tmp ][$this->group_col] = $tmp;
						 
					foreach ($this->columns as $value)
					{

						$this->sum[$tmp][ $value] = array();
						$this->sum[$tmp][ $value]['sum'] = $this->neutral_format( $this->format[$value] );
						$this->sum[$tmp][ $value]['count'] = 0;
					}
						 
				}
				
				
					foreach ($this->columns as $value)
					{

						$this->sum[$tmp][ $value]['sum'] = $this->add_format($this->format[$value] ,$this->sum[$tmp][ $value]['sum'] , $this->rst->col($value) );
						$this->sum[$tmp][ $value]['count']++;
						
						$this->max[ $value]['sum']  = $this->add_format($this->format[$value] ,$this->max[ $value]['sum'] , $this->rst->col($value) );
						//+= floatval( $this->rst->col($value) );
						$this->max[ $value]['count']++;
					}
						

				
				
				
			}
			while($this->rst->next());
			if($this->sorted) ksort($this->sum, SORT_STRING);
			//var_dump($this->sum);
	  //return 'no dataset';
		
	}
	}
	
	function getAdditiveSource(){;}
	
	public function moveFirst()
		{

			if(count($this->sum) == 0)$this->collect_data();
				
				return (reset($this->sum) !== false); 

		}
		
    	public function moveLast()
    	{
			if(count($this->sum) == 0)$this->collect_data();
				
				return (end($this->sum) !== false);
    	}
    	
	public function next()
	{
			if(count($this->sum) == 0)$this->collect_data();
				
				return (next($this->sum) !== false);
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
    	
    	/**
    	* Die Spalten, die col() liefert: die Gruppe und je Summe <name>.sum, .count, .max_sum,
    	* .max_count. Bis 2026-10-12 die Spalten der QUELLE - ein Konsument, der danach fragt
    	* (Formula, Aggregation), fand die eigenen nicht.
    	*/
    	public function fields()
    	{
    		$res = array();
    		if($this->group_output !== null) $res[] = $this->group_output;
    		foreach($this->sum_outputs as $output => $column)
    			foreach(array('sum', 'count', 'max_sum', 'max_count') as $com) $res[] = $output . '.' . $com;
    		return $res;
    	}

    	/** in der Sprache der Datenbank wie DBO - Zahlen, damit Aggregation summiert */
    	public function datatype($columnname)
    	{
    		if($columnname === $this->group_output)
    			return ($this->rst && method_exists($this->rst, 'datatype') && ($type = $this->rst->datatype($this->group_col))) ? $type : 'varchar(255)';
    		$col_array = explode('.', $columnname);
    		$com = strtolower(array_pop($col_array));
    		$output = implode('.', $col_array);
    		if(!isset($this->sum_outputs[$output])) return false;
    		if($com === 'count' || $com === 'max_count') return 'int(11)';
    		if($com !== 'sum' && $com !== 'max_sum') return false;
    		switch($this->format[$this->sum_outputs[$output]])
    		{
    			case 'int':      return 'bigint(20)';
    			case 'interval': return 'varchar(32)';
    			default:         return 'decimal(20,6)';
    		}
    	}

}
?>
