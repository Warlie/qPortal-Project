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

class Aggregation extends plugin 
{

private $alias;
private $filename; 
private $hash; 
private $output;

var $rst = null;
private $criteria = [];
//var $obj = null;
private $group = [];
private $dataSet = [];
private $done = false;
private $end = false;


	function __construct()
	{
		
		
	}
	
	
	/*
	*	@param columnname : name of the specific column as criteria
		@param type : TEXT
	**/
	public function token ( $columnname, $add )
	{
		
		$this->criteria[] = array( "name" => $columnname, "datatype" => $this->datatype($columnname), "add" => $add );
		
	}
	
	public function group($columnname)
	{
		$this->group[] = ["name" => $columnname, "value" => false];
		//$this->group[$columnname] = false;
	}
	
	public function aggregate()
	{
		
	}
	
	public function col($columnname)
	{
		//echo $columnname . "\n";
	if($this->rst)
	{
		
		if(!$this->done)
		{
		$this->init();
		$this->collect_to_next();
		}
		

		
	$filteredData = array_filter($this->dataSet, function($element) use ($columnname) {
			return $element["name"] === $columnname;
		});
		
	
	$res = array_shift($filteredData);
//var_dump($res);

	if(!is_null($res))
	{
		//echo $this->transform($res['value'], $this->datatype($columnname)) . " \n";	
		return $this->transform($res['value'], $this->datatype($columnname));	
	}

	return "";
	}
	
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}


	function getAdditiveSource(){;}
	public function moveFirst()
	{
		if($this->rst){
			$res = $this->rst->moveFirst();
			return $res; 
			
		}else return false;
	}
    	
	public function next()
	{
		if($this->rst)
		{
			return $this->collect_to_next();
			//return $this->rst->next();
		}
		else 
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
    	
    	public function datatype($columnname){return $this->rst->datatype($columnname);}
    	
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}
    	
    	private function collect_to_next()
    	{
    		// in case the first collection is the last one, the func_tion will return false with skiping datacollection
    		if($this->end)return false;
    		
    		$end = true;
    		
    		// collects a new dataset
    		$this->dataSet = [];
    		$fields = $this->rst->fields();
    		
    		foreach ($fields as $key => $data)
			$this->dataSet[$key] = ["name" => $data, "value" => []];
		
		// fills it with a collection until next is false or group characteristica change
    		do
    			{
    				for($i = 0; $i < count($this->dataSet); $i++)
    				{
    					
    					$this->dataSet[$i]['value'][] = $this->rst->col($this->dataSet[$i]["name"]);
    					//echo "name: " . $data["name"] . " value:" . $this->rst->col($data["name"]) . ";\n ";
    				}
    				
    				
    				//echo "next\n ";
    				$this->end = !$this->rst->next();

    			} // nextCollectionBreak will check group characteristica and will return false in case, it has changed
    			while(!$this->end && $this->nextCollectionBreak());

    			//var_dump($this->dataSet);
    			// shows first time run
    			$this->done = true;
    			
    			return !$this->end;
    	}
    	
    	
    	private function transform($valueArray, $format)
    	{
    		switch(strtoupper(strtok($format, '('))
    			)
	  	  {
	  	  case 'varchar' :
	  	  case 'VARCHAR':
	  	  case 'date' :
	  	  case 'DATE':
	  	  	  
			$res = array_unique($valueArray);
	  	  	return implode(',', $res);   

	  	  	  break;
	  	  case 'TINYINT' :
	  	  case 'SMALLINT' :
	  	  case 'INT' :
	  	  case 'BIGINT' :
	  	  	  return  array_sum(array_map('intval', $valueArray));
	  	  	  break;
	  	  case 'DECIMAL' :
	  	  case 'NUMERIC' :
	  	  case 'FLOAT' :
	  	  case 'REAL' :
	  	  	  return array_sum(array_map('floatval', $valueArray));
	  	  	  break;
	  	  default:
			$res = array_unique($valueArray);
	  	  	return implode(',', $res);   
	  	  }
    	}
    	private function init()
    	{
    		for($i = 0; $i < count($this->group); $i++)
    			{
    				$this->group[$i]['value'] = $this->rst->col($this->group[$i]['name']);
    			}
    	}
    	
    	private function nextCollectionBreak()
    	{
    		$res = true;
    		for($i = 0; $i < count($this->group); $i++)
    				{
    					// if inequal, the return will be false and the value will be updated
    					if($this->group[$i]['value'] != $this->rst->col($this->group[$i]['name']))
    						{
    							$res = false;
    							$this->group[$i]['value'] = $this->rst->col($this->group[$i]['name']);
    						}
    				}
    		return $res;
    	}
}
?>
