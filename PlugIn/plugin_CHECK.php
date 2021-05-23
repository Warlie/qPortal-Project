<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/
require_once("plugin_interface.php");

class CHECK extends plugin 
{

	//reihe

	function CHECK()
	{
	echo '<br><b>CHECK aktiv!</b><br>';
	}
	
	function set($type, $value)
	{
		parent::set($type, $value);
		//echo $type . ' ' . $value;

		
		if($type == "All_TEMPLATES")
		{
			
			$generator->XMLlist->ALL_URI();
			
			
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
