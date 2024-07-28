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
		
		//if(strtolower($columnname) == 'sum_max')return $this->max;
	
		$col_array = explode('.', $columnname);
		
		$com = array_pop($col_array);
		$col_name = $this->out_column[implode('.', $col_array)];

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
	}
	
	public function ignoreEmpty(){$this->ignore = false;}
		
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
			//var_dump($this->sum);
	  //return 'no dataset';
		
	}
	}
	
	function getAdditiveSource(){;}
	
	protected function moveFirst()
		{

			if(count($this->sum) == 0)$this->collect_data();
				
				return (reset($this->sum) !== false); 

		}
		
    	protected function moveLast()
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
    	
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}

}
?>
