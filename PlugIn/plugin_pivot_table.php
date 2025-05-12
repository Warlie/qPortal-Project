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

class Pivot_Table extends plugin_multisource
{



private $tag_name = false;
private $allocation = array();
private $std_default = array();
private $std_null = array();
private $col_list = array();
private $pivot_row='';
private $pivot_show_row='';
private $row_names = array();
private $pivot_column='';
private $pivot_get_col='';
private $big_list;

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
		
		$res = current($this->big_list);
	  return $res[$columnname];
	
	  //throw new ObjectBlockException('Recordset is missing');
	}
	
	/*
	*@param $pvt_row
	*@param $pvt_col
	*@param $rel_col
	*@param $get_col
	*@param $show_row
	*
	*/
	public function collect_data($pvt_row, $show_row, $pvt_col, $get_col, $rel_col)
	{
		$this->col_list = explode(',', $rel_col);
		$this->pivot_row=$pvt_row;
		$this->pivot_show_row=$show_row;
		$this->pivot_column=$pvt_col;
		$this->pivot_get_col=$get_col;
		$this->big_list = $this->intoList($this->create_array());
		
		//var_dump($this->big_list);
		
		
	}
	
	private function intoList($arr)
	{
		
		$table = $arr[0];
		$rows = $arr[1];
		$res = array();
		
		for($i = 0;$i < count($table);$i++)
		{
			for($j = 0;$j < count($table[$i]);$j++)
				for($k = 0;$k < count($table[$i][$j]);$k++)
				{
					//echo "($i, $j), "; 
					$tmp = $table[$i][$j][$k];
					$tmp['x'] = $i;
					$tmp['y'] = $j;
					$tmp['pivot-row-name'] = $rows[$i];
					$res[] = $tmp;
				}
				//echo "\n";
		}		
		return $res;
	}
	
	private function create_array()
	{
		$res = array();$inner = array();
		$line = array('@touched?' => false);
		$column = array();
		$row = array();
		$row_names = array();

		
		
		//create empty lines array of col list 
		foreach( $this->col_list as $value)
			$line[$value] = '';
			
		//expension for other plugins
		if(!($this->rst[1] instanceof plugin)) throw new WrongClassException('pffff');
		$this->rst[1]->moveFirst();
		$idx = $this->get_prefix($this->pivot_get_col);

		//build a column line for the top of the tabel
		do
		{
			$column[] = parent::col($this->pivot_get_col);
			
		}
		while($this->rst[$idx]->next());	
		
		if(!($this->rst[0] instanceof plugin)) throw new WrongClassException('pffff');
		//build the row line for the left side
		$this->rst[0]->moveFirst();
		$this->rst[1]->moveFirst();
		do
		{
			//echo $this->pivot_row . "\n";
			//echo parent::col($this->pivot_row) . "\n";
			$row[] = parent::col($this->pivot_row);
			if($this->pivot_show_row)
			$row_names[] = parent::col($this->pivot_row);

		}
		while($this->rst[0]->next());
		//var_dump($column);
		$row = array_unique($row);
		$column = array_unique($column);
		//var_dump($row,$column);
		$tmp = array();
		//build a empty template for every entry in the pivot table
		foreach($column as $value)
		{
			$tmp[] = $value;
			$inner[] = array($line);
		}
		$column = $tmp;
		$tmp = array();
		$tmp2 = array();
		$i = 1;
		//var_dump($row_names);
		//$row_name = array();
		foreach($row as $key =>$value)
		{
			if($this->pivot_show_row)
				$tmp2[] = $row_names[$key];
			else
				$tmp2[] = $i++;
				
			$tmp[] = $value;
			$res[]= $inner;
		}
	
		$row = $tmp;

		$row_names = $tmp2;
		
		$x = 0;
		$y = 0;
		
		//var_dump($res);
		//var_dump($this->col_list);
		//looks up every entry in rst list and add it to the specific cell
		$this->rst[0]->moveFirst();
		do
		{

				
			
			if(false === ($x = array_search(parent::col($this->pivot_row), $row)))continue;
			if(false === ($y = array_search(parent::col($this->pivot_column), $column)))continue;
			//echo "(" . $this->pivot_row . ", " . $this->pivot_column . ")=(" . parent::col($this->pivot_row) . ", " . parent::col($this->pivot_column) . ")-->($x, $y)";
			//echo "existing entries" . count($res[$x][$y]) . "\n";
			if(!$res[$x][$y][0]['@touched?'])
			{
				foreach( $this->col_list as $value)
					$res[$x][$y][0][$value] = parent::col($value);
				$res[$x][$y][0]['@touched?'] =true;
			}
			else
			{
				$tmp = array();
				foreach( $this->col_list as $value)
				{
					$tmp[$value] = parent::col($value);
					
				}
				//echo "in pivot \n";
				//var_dump($tmp);
				$res[$x][$y][] = $tmp;
			}

		}
		while($this->rst[0]->next());
		//var_dump($res);
		//var_dump($line, $column, $row, $res);
		//array_unique();
		//$my =array($res,$row_names);
		//var_dump($my);
		return array($res,$row_names);
		
	}
	
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	public function prefix($tbl, $prefix)
	{
		$this->column_prefix[$prefix] = $tbl;
	}

	
	
	function getAdditiveSource(){;}
	public function moveFirst()
	{	
		return (reset($this->big_list));	
	}
	
    	public function moveLast()
    	{
		return (end($this->big_list));
	}
    	
	public function next()
	{
		return (next($this->big_list));
		
	}
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