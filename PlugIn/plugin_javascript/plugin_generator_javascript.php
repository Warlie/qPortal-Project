<?PHP

/**
*ContentGenerator
*
* Generates a logfile
*
* @-------------------------------------------
* @title:Logger
* @autor:Stefan Wegerhoff
* @description: creates a log-file in template folder
*/
//require_once("../plugin_interface.php");

class JSgenerator_svg_rdf extends plugin 
{



	function JSgenerator_svg_rdf()
	{
	
	}


		


		
	public function moveFirst(){}
	public function moveLast(){}
	public function set_list(&$value){}
				


	
	function check_type($type)
	{
	if($type == "OUT")return true;
	if($type == "IN")return true;
	if($type == "NAME")return true;
	if($type == "ATTRIB")return true;
	
	return parent::check_type($type);
	}

	function next(){return false;}

	
	function decription(){return "no description avaiable!";}
	
	public function __toString(){return 'rabusch';}	
}
?>
