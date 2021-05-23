<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/
require("plugin_interface.php");

class save_form extends plugin 
{
var $field_for_sort;
	

	function save_form()
	{

	}
	
	function set($type, $value)
	{
		//echo $type;
		parent::set($type, $value);
		if($type == "IN")
		{
			
			$sqlab = "insert events";
			$sqlab .= "(ort, datum, veranstalter,";
			$sqlab .= " uhrzeit, name, art, info) values ";
			$sqlab .= "('" . $this->back->heap['request']["ort"] . "', '" . $this->back->heap['request']["datum"] . "', '";
			$sqlab .= $this->back->heap['request']["veranstalter"] . "', '" . $this->back->heap['request']["uhrzeit"] . "', '" . $this->back->heap['request']["name"] . "', '";
			$sqlab .= $this->back->heap['request']["art"] . "', '" . $this->back->heap['request']["Kurzinfo"] . "')";
			$this->back->dbAccess->SQL($sqlab);
			//echo $sqlab;
		}
		if($type == "SORT")$this->field_for_sort = $value;
		if($type == "OUT")
		{
		echo "hier";	
			$sqlab = "SELECT  ort, datum, veranstalter,";
			$sqlab .= " uhrzeit, name, art, info FROM events WHERE art = '" . $this->field_for_sort . "';";
			
			$rst = $this->back->dbAccess->get_rst($sqlab);
		$stampx = $xml->position_stamp();
		echo $this->back->XMLlist->cur_node();
		//$xml->create_node($stampx);
		//$xml->set_node_name("DIV");
		//$xml->set_node_attrib("STYLE","position:absolute;top:" . (100 + ($this->pic_num - 1) * 42) . "px;");
		//$stamp = $xml->position_stamp();
		//$this->back->XMLlist	
			//echo $sqlab;
		}
		//echo $type . ' ' . $value;
	}
	
	function check_type($type)
	{
	if($type == "IN")return true;
	if($type == "OUT")return true;
	if($type == "SORT")return true;
	if($type == "RUN")return true;
	if($type == "SQL")return true;
	if($type == "XMLTEMPLATE")return true;
	if($type == "COL")return true;
	//if($type == "")return true;
	return parent::check_type($type);
	}

	function decription(){return "no description avaiable!";}
}
?>
