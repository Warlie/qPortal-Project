<?PHP

/**
*FileService
*
* Supports following func_tions.
*	- loding files into request-array
*	- 
*
* @-------------------------------------------
* @title:Upload_File
* @autor:Stefan Wegerhoff
* @description: Loads files over browserupload
*
*/
require_once("plugin_interface.php");

class FileService extends plugin 
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
	
	/**
	*@parameter: ADD_PATH = scannes all files in the pathcollection. Use one path, for one parametercall.
	*/
	public function add_path($path)
	{
		if($this->scanner)
		$this->scanner->add_path($path);
	}
	
	/**
	*@parameter: PROHIB_PATH = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function prohib_path($path)
	{
		if($this->scanner)
		$this->scanner->prohib_path($path);
	}
	
	/**
	*@parameter: ADD_TAG = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function add_tag($tag)
	{	
		if($this->scanner)
		$this->scanner->add_tag($tag);
	}
	
	/**
	*@parameter: ADD_FIX = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function add_fix($fix)
	{
	
		if($this->scanner)
		$this->scanner->add_fix($fix);	
	}
	
	/**
	*@parameter: RELATIVE_PATH (false/true) = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function relative_path($bool)
	{
		
			if($this->scanner)
			$this->scanner->relative_path( (strtolower($bool) == 'true') );
			
	}
	
	/**
	*@parameter: ID_ALIAS (name) = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function id_alias($name)
	{
			
			$this->alias[$name] = 'tag';
	}
	
	/**
	*@parameter: POS_ALIAS (name) = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function pos_alias($name)
	{
			
			$this->alias[$name] = 'pos';
	}
	
	/**
	*@parameter: FILE_ALIAS (name) = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function file_alias($name)
	{
			
			$this->alias[$name] = 'file';
	}
	
	/**
	*@function: START_SCAN = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function start_scan()
	{
			if($this->scanner)
			{
			$this->scanner->seeking();
			$this->result_table = $this->scanner->result();
			}
	}

	public function many()
	{
			if($this->scanner)
			return count($this->result_table);
	}
	
	//$pos_res
	public function next()
	{
	if(count($this->result_table) > $this->pos_res + 1)
	{
	$this->pos_res++;
	return true;
	}
	else
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
