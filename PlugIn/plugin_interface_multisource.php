<?PHP
abstract class plugin_multisource extends plugin
{
protected $columns = array();
protected $column_prefix = array();
protected $rst = array();
    	//abstract protected function moveFirst();
    	//abstract protected function moveLast();
    	//abstract protected function get_list(&$value);
    	
    	public function prefix($tbl, $prefix)
	{
		$this->column_prefix[$prefix] = $tbl;
	}
	
	public function get_prefix($columnname)
	{
		
		$pre = explode('.', $columnname);
		if(isset(
			$this->column_prefix[$pre[0]]
			))return $this->column_prefix[$pre[0]];
			return 0;

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

	 public function set_list(&$value)
    	{

    	if(is_object($value))
	{
		$this->rst[] = &$value;
	}
	else
	return 'no element received';
    	}
    	
   	public function fields()
   	{
   		if(!$this->rst[0])return array(); 
    		$res = $this->rst[0]->fields();
    		
    		for($i=1;$i < count($this->rst) ;$i++)
    		{
    			$add = $this->rst[$i]->fields();
    			    	for($j=1;$j < count($add) ;$j++)$add[$j] =  $this->column_prefix[$i] . "." . $add[$j];
    			$res = array_merge($res, $add);
    		}
    		return $res;
    	}
}
?>
