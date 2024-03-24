<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
* not_a_fieldname_exception.php
*/



abstract class plugin 
{

var $back = null;
var $treepos = null;
var $id = "";
protected $args = array();
protected $internal_table_values = [];
protected $internal_table_field_types = [];
private $throwTo = array();


var $out = "";
	protected function param_out(&$param){$this->out = &$param;}
	protected function &xml(){return $this->treepos;}

	abstract public function set_list(&$value);
	
	protected function moveFirst(){return reset($this->internal_table_values);}
	protected function moveLast(){return end($this->internal_table_values);}
    	//is never used
    	//abstract public function getAdditiveSource();

    public function configuration($json){echo "not implemented yet!";}
	public function prev(){return prev($this->internal_table_values);}
	public function next(){return next($this->internal_table_values);}
	public function reset(){return reset($this->internal_table_values);}
	public function col($columnName)
	{


		
		if(!is_null($res = current($this->internal_table_values)) && array_key_exists($columnName, $res) )
			return $res[$columnName];
		
		throw new NotAFieldnameException($columnName . ' is not part of this recordset');
	}
	public function datatype($columnname){return false;}
	public function fields(){return array();}
	public function &out(){return $this->out;}
	public function __toString(){return 'Plug_in:' . get_Class($this);}


}
?>
