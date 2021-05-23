<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/
require_once("plugin_interface.php");

class XML_BUILD extends plugin 
{
var $reset_name = "";
var $producer = '';
var $xpath = '';
var $toUse = '';
var $doctype = 'xml';
var $scanner;
var $result_table = array();
var $pos_res = 0;
var $filehandle;
var $save_file;
var $handle;

var $alias = array('tag'=>'tag','pos'=>'pos','file'=>'file','number'=>'number');


	function __construct()
	{
		$this->scanner = new File_Scan();
		
	}
	

	public function many()
	{
			if($this->scanner)
			return count($this->result_table);
	}
	
	//$pos_res
	public function next()
	{
	return false;
	
	}
	public function moveFirst(){return (count($this->result_table) > 0);}
    	public function moveLast(){}
    	public function col($columnname)
    	{
    	if($this->alias[$columnname] == 'number')return $this->pos_res;
    	return $this->result_table[$this->pos_res][$this->alias[$columnname]];
    	}
    	public function iter(){return $this;}
    	public function set_list(&$value){}
    	public function fields(){return array_keys($this->alias);}
    	public function getAdditiveSource(){}
}
?>
