<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/
require_once("plugin_interface.php");

class SIS extends plugin 
{

	//reihe

var $obj = null;
var $db;
var $tag;
//http://www.sight-board.de/_editor/dataProvider/data.php?external=34
	function SIS(){}
	
	function set($type, $value)
	{
		parent::set($type, $value);
		//echo $type . ' ' . $value;

		
		if($type == "LIST")
		{
			
			if(is_null($this->obj))$this->obj = &$value;

		}	
		if($type == "DBLIST")
		{

			$this->obj->set("ERR",null);
			$err = $this->obj->out();
if($err == 0) return false;
			
			$this->db = &$value;
if( get_class($this->db) == '')return false;
//			echo get_class($this->db);

$value->set('COL','variablen.Wert');
$this->obj->set("ERRDESC",null);
			$empfaenger = $value->out();
			$betreff = 'Error ocured';
$nachricht = "This mail in generated!
At " . date("Y-m-d") . ' um ' . date("H:i:s") . ' this error ocured:
' . $this->obj->out() . '


Thank you

';
$header = 'From: service@modifyme.com' . "\r\n" .
   'X-Mailer: PHP/' . phpversion();


if(mail($empfaenger, $betreff, $nachricht, $header))echo 'sended successfull';
			
			
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
