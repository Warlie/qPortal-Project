<?PHP

/**
* @-------------------------------------------
* @title:Upload_File
* @autor:Stefan Wegerhoff
* @description: Loads files over Browserupload
* @func_tion: NAME = Select name of Uploadfield 
* @func_tion: CLIENT = gives out clientfilename
* @func_tion: SIZE = gives out filesize
* @func_tion: SERVER = gives out name of file on Server
* @func_tion MOVE = moves file from temp to an other path
* @-------------------------------------------
*/
require_once("plugin_interface.php");

class Upload_File extends plugin 
{

	//reihe
	var $param = array();
	var $bool = false;
	var $res = array(); 	
var $reqire = array();
var $content = array();
var $obj = null;
var $tag;
var $request_name = "i";
var $page_id = "";



	function Upload_File(/* System.CurRef */ &$treepos)
	{}
	
	public function set_name($name)
	{
	$this->page_id = $value;
	}
	
	public function get_client()
	{
	if(!is_null($tmp = $_FILES[$this->page_id]['name']))
		{
		return $tmp;
		}	
		return false;
	}
	
	public function get_size()
	{
	if(!is_null($tmp = $_FILES[$this->page_id]['size']))
		{
		return $tmp;
		}	
		return false;
	}
	
	public function get_server()
	{
	if(!is_null($tmp = $_FILES[$this->page_id]['tmp_name']))
		{
		return $tmp;
		}	
		return false;
	}
	
	public function move($value)
	{
	if(move_uploaded_file  ( $_FILES[$this->page_id]['tmp_name']  , $value  ))
		{
		$_FILES[$this->page_id]['tmp_name'] = $value;
		}	
	}	


	public function moveFirst()
	{$this->pos = 0;}	

	public function moveLast()
	{$this->pos = count($this->table) - 1;}
	
		

	public function getAdditiveSource(){}
	
	public function set_list(&$value)
	{
			
	if(is_object($value))
	{
		$this->rst = &$value;
	}
	}
	

	function next(){return false;}

	
	function decription(){return "no description avaiable!";}
}
?>
