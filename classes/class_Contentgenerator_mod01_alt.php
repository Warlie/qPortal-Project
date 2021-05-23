<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
* (C) Stefan Wegerhoff
*/
require("class_Contentgenerator.php");

class ContentGeneratorMod01 extends ContentGenerator
{

var $pic_num = 1;
	//creates a menu
	function createCotrolPanel($name, $link, $xml)
	{
		$stampx = $xml->position_stamp();
		$xml->create_node($stampx);
		$xml->set_node_name("DIV");
		$xml->set_node_attrib("STYLE","position:absolute;top:" . (100 + ($this->pic_num - 1) * 42) . "px;");
		$stamp = $xml->position_stamp();
		
		
		
		$xml->create_node($stamp);
		$xml->set_node_name("A");
		$xml->set_node_attrib("HREF","index.php?i=" . $name );
		
		$stamp = $xml->position_stamp();

		$xml->create_node($stamp);
		$xml->set_node_name("SPAN");
		//$xml->set_node_attrib("STYLE","position:absolute;font-size:20px;white-space:nowrap;" ); //top:5px;left:10px;
		$xml->set_node_cdata($link,0);
		$this->pic_num++;
		//$xml->create_node($stamp);
		//$xml->set_node_name("IMG");
		//$xml->set_node_attrib("src","img/bar_" . $this->pic_num++ . ".jpg" );
		//$xml->set_node_attrib("style","border-style:none;");
		
		$xml->go_to_stamp($stampx);
		
		
	}

	//inherit 
	function function_use(&$rst,&$xml,&$field ,$stamp)
	{
		
		//tags mit funktionscharakter werden mit unterstrich markiert
		if($this->function_call(&$rst,&$xml,&$field ,$stamp))return false;
		
		
		$tmp = $rst->value($field[3]);
		parent::function_use(&$rst,&$xml,&$field ,$stamp);
		
		if($tmp)
		{
		
		$stamp = $xml->position_stamp();
		$sql = "SELECT tag_collection.id, tag_collection.type, tag_collection.content, attrib_collection.name, attrib_collection.value, tag_collection.ref FROM (tag_collection ";
		$sql .= " LEFT JOIN connect_collection ON tag_collection.attrib = connect_collection.tagid ) LEFT JOIN attrib_collection ON connect_collection.attribid = attrib_collection.id ";
		$sql .= "WHERE tag_collection.group = '$tmp' ORDER BY tag_collection.order;";
		
		$rst2 = $this->dbAccess->get_rst($sql);
		$rst2->first_ds();
		
	
		
		$field = $rst2->db_field_list();
		
		
		
		
		while(!$rst2->EOF())
		{
			$this->function_use($rst2,$xml,$field,$stamp );
		}
		}
		
	}
	
	function function_call(&$rst,&$xml,&$field ,$stamp)
	{
		
		if(substr($rst->value($field[1]),0,1)<>"_")return false;
		
		$this->function_beha(&$rst,&$xml,&$field ,$stamp);
		$rst->next_ds();
		return true;
	}
	
	//for Objects embeded in db
	function function_beha(&$rst,&$xml,&$field ,$stamp){}
}
?>
