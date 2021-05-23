<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/
require_once("plugin_interface.php");

class TimeDate extends plugin 
{

	//reihe
	var $param = array();
	var $bool = false;
	var $res = array(); 	
var $reqire = array();
var $content = array();
var $obj = null;
var $tag;
//http://www.sight-board.de/_editor/dataProvider/data.php?external=34
	function TimeDate(){}
	
	function set($type, $value)
	{
		parent::set($type, $value);
		//echo $type . ' ' . $value;

		
		if($type == "CURTIME")
		{
			
			$this->param_out(date("H:i:s"));
			
			
		}

		if($type == "CURDATE")
		{
			
			$this->param_out(date("Y-m-d"));
			
			
		}		
		
		if($type == "RUN")
		{
			//$booh = $this->get_GK3(array('Am Grafenwald','10','42859','Remscheid'));
			
			//echo 'x=' . $booh[0] . ',y=' . $booh[1] . ',z=' . $booh[2] . '<br>';
			//
			
		}
	}
	
	function check_type($type)
	{
	if($type == "SQL")return true;
	if($type == "XMLTEMPLATE")return true;
	if($type == "COL")return true;
	//if($type == "")return true;
	return parent::check_type($type);
	}

	function next(){return false;}

	
	function decription(){return "no description avaiable!";}
}
?>
