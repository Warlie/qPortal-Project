<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/



abstract class plugin 
{

var $back = null;
var $treepos = null;
var $id = "";
protected $args = array();
private $throwTo = array();

var $out = "";
	protected function param_out(&$param){$this->out = &$param;}
	//protected fun---ction &generator(){return $this->back;}
	protected function &xml(){return $this->treepos;}
    	abstract protected function moveFirst();
    	abstract protected function moveLast();
    	abstract public function getAdditiveSource();
    	abstract public function set_list(&$value);
/*    	
    	protected function &getFirstArgs()
    	{	
    		if(count($this->args)>0) return $this->args[0]; 
    	}
    	protected function &getSecondArgs()
    	{	
    		if(count($this->args)>1) return $this->args[1]; 
    	}
   */
   /* 
    	public function set_list(&$value)
    	{
   		
    	if($value instanceof plugin )
    	{
    		$this->args[] = &$value;
    		$throwTo[] =  &$value
    	}
    	else
    	{
    		
    		if(is_object($value))
    		{
			$this->args[] = &$value;
		}
		else
		return 'no element received';
	} 
    	}*/
	public function next(){return false;}
	public function reset(){}
	public function col($columnname){return 'no dataset';}
	public function datatype($columnname){return false;}
	public function fields(){return array();}
	public function &out(){return $this->out;}
	public function __toString(){return 'Plug_in:' . get_Class($this);}


}
?>
