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
var $next_field = 0;
	//creates a menu
	function createCotrolPanel($name, $link, $xml)
	{
		
		
		
		if(false === strpos($link,'>>'))
		{
			$color = '#663333';
			$corner = '';
		}
		else
		{
			$color = '#996666';
			$link = substr($link,2);
			$corner = '2';
		}
		
		$stampx = $xml->position_stamp();
		$xml->create_node($stampx);
		$xml->set_node_name("div");
		$xml->set_node_attrib("style","position:absolute;left:" . $this->next_field . "px;height:40px;background-color:$color;white-space:nowrap;color:white;");
		$stamp = $xml->position_stamp();
		
		
		
		$xml->create_node($stamp);
		$xml->set_node_name("a");
		$xml->set_node_attrib("style","color:white;text-decoration:none;");
		$xml->set_node_attrib("href","index.php?i=" . $name );
		
		$stamp = $xml->position_stamp();

		$xml->create_node($stamp);
		$xml->set_node_name("img");
		$xml->set_node_attrib("src","img/eckelinks$corner.png" );
		$xml->set_node_attrib("style","border-style:none;");
		//$xml->set_node_cdata(' ',0);
		
		$xml->create_node($stamp);
		$xml->set_node_name("span");
		$xml->set_node_attrib("style","font-size:20px;white-space:nowrap;" ); //top:5px;left:10px;
		$xml->set_node_cdata($link,0);
		
		$xml->create_node($stamp);
		$xml->set_node_name("img");
		$xml->set_node_attrib("src","img/eckerechts$corner.png" );
		$xml->set_node_attrib("style","border-style:none;");
		//$xml->set_node_cdata(' ',0);
		//$this->pic_num++;
		//$xml->create_node($stamp);
		//$xml->set_node_name("IMG");
		//$xml->set_node_attrib("src","img/tab.png" );
		//$xml->set_node_attrib("style","border-style:none;");
		/*$myfont;
		foreach (glob("*.TTF") as $filename) {

			$myfont = $filename;

		}
		
		If(strlen($link) < 3 ) echo "no *.ttf found";
		*/
		//echo "boooh " . CUR_PATH . 'ARIAL.TTF' . " :)";
		//**************hier ist das Problem****************************
		//$font = imagettfbbox(16,20,CUR_PATH . 'ARIAL.TTF',$link); //strlen($link)
		
		if(!$font[0])
		{
			$font[2] = (strlen($link) * 9) ;
		}
		//echo ' 0 ist ' . $font[0] . '<br>';  
		//echo ' 1 ist ' . $font[1] . '<br>';
		//echo ' 2 ist ' . $font[2] . '<br>';
		//echo ' 3 ist ' . $font[3] . '<br>';
		//echo ' 4 ist ' . $font[4] . '<br>';
		//echo ' 5 ist ' . $font[5] . '<br>';
		//echo ' 6 ist ' . $font[6] . '<br>';
		//echo ' 7 ist ' . $font[7] . '<br>';
		
		
		$xml->go_to_stamp($stampx);
		$this->next_field = $this->next_field + (20 + $font[2]);
		
	}

	//inherit 
	function function_use(&$rst,&$xml,&$field ,$stamp)
	{
		
		//tags mit funktionscharakter werden mit unterstrich markiert
		if($this->function_call($rst,$xml,$field ,$stamp))return false;
		
		
		$tmp = $rst->value($field[3]);
		parent::function_use($rst,$xml,$field ,$stamp);
		
		if($tmp)
		{
		
		$stamp = $xml->position_stamp();
		
		$sql = "SELECT tag_collection.id, tag_collection.type, tag_content.content, attrib_collection.name, attrib_collection.value, tag_collection.ref
FROM (
(
tag_collection
 left join connect_collection ON tag_collection.attrib = connect_collection.tagid
)
 left join tag_content ON tag_collection.content_ref = tag_content.id
)
 left join attrib_collection ON connect_collection.attribid = attrib_collection.id
WHERE tag_collection.group = '$tmp' 
ORDER BY tag_collection.order;";
		
		//$sql = "SELECT tag_collection.id, tag_collection.type, tag_collection.content_ref, attrib_collection.name, attrib_collection.value, tag_collection.ref FROM (tag_collection ";
		//$sql .= " LEFT JOIN connect_collection ON tag_collection.attrib = connect_collection.tagid ) LEFT JOIN attrib_collection ON connect_collection.attribid = attrib_collection.id ";
		//$sql .= "WHERE tag_collection.group = '$tmp' ORDER BY tag_collection.order;";
		//echo $sql; 
		$rst2 = $this->dbAccess->get_rst($sql);
		$rst2->first_ds();
		
	
		
		$field = $rst2->db_field_list();
		
		
		
		
		while(!$rst2->EOF())
		{
			$this->function_use($rst2,$xml,$field,$stamp );
			
		}
		//echo $xml->cur_node();
		$xml->parent_node();
		//echo $xml->cur_node();
		}
		
	}
	
	function function_call(&$rst,&$xml,&$field ,$stamp)
	{
		
		if(substr($rst->value($field[1]),0,1)<>"_")return false;
		
		$this->function_beha($rst,$xml,$field ,$stamp);
		$rst->next_ds();
		return true;
	}
	
	//for Objects embeded in db
	function function_beha(&$rst,&$xml,&$field ,$stamp){}
}
?>
