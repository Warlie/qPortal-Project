<?PHP

/**
* @title:Accumulation
* @autor:Stefan Wegerhoff
* @description: expect an object of same kind in rst. It adds the value of one column in reference to another column 
*
*/

class Accumulation extends plugin 
{

private $accumName = "";
private $accum = [];
private $preAccum = [];
private $group = "";
private $groupValue = 0.0;



	function __construct()
	{
		
		
	}
		
	/**
	*	@param columnname : name of the specific column
	*	
	*	This column should contain only numerical data. For every step, this value will be added to the next value.
	*	
	*/
	public function forAccumulation($columnname)
	{
		$this->accumName = $columnname;
	}
	
	
	/**
	*	@param columnname : name of the column 
	*	
	*	this column differentiates several accumulations
	*	
	*/
	public function group($columnname)
	{
		$this->group = $columnname;
	}
	
	/**
	* @see plugin::col()
	*/
	public function col($columnname)
	{

	if($this->rst)
	{
		
		if($this->accumName == $columnname)
		{
			

			$groupValue = $this->rst->col($this->group);

			if(!array_key_exists($groupValue, $this->accum ))
				$this->accum[$groupValue] = 0.0;
			
			if(!array_key_exists($groupValue, $this->preAccum ))
				$this->preAccum[$groupValue] = 0.0;			

			$preSave  = floatval($this->rst->col($columnname)) + $this->accum[$groupValue] ;

			$this->preAccum[$groupValue] = $preSave;

			return $preSave;
		}
		else
		return $this->rst->col($columnname);
		
	}
	
	  return 'no dataset';
	}
	
	/**
	* @see plugin::iter()
	*/
	public function &iter()
		{
		
		return $this;}

	
	/**
	* @see plugin::getAdditiveSource()
	*/
	function getAdditiveSource(){;}
	public function moveFirst()
	{
		
		$this->accum = [];
		$this->preAccum = [];
		
		if($this->rst){
			$res = $this->rst->moveFirst();

			return $res; 
			
		}else return false;
	}
	
	/**
	* @see plugin::moveLast()
	*/
    	public function moveLast(){if($this->rst)return $this->rst->moveLast();else return false;}

    	/**
	* @see plugin::next()
	*/
	public function next(){/*echo "next\n";*/

		foreach ($this->preAccum as $group => $value) {
			$this->accum[$group] = $value; // Updates or adds the value for the group
		}
		$this->preAccum = [];
		
		if($this->rst)return $this->rst->next();
		else return false;
	}

	/**
	* @see plugin::set_list()
	*/
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
	* @see plugin::datatype()
	*/
    	public function datatype($columnname){return $this->rst->datatype($columnname);}
    	
    	/**
	* @see plugin::fields()
	*/
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}
    	
}
?>
