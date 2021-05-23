<?PHP

/**
*ContentGenerator modi 2 vers. 1.00
*
* Generates content by reading XML and DB-entries
*
* (C) Stefan wegerhoff
*/
require("class_Contentgenerator_mod01.php");
require_once("PlugIn/plugin_Request.php");
require_once("PlugIn/plugin_fileservice.php");


class ContentGeneratorMod02 extends ContentGeneratorMod01
{

//
function function_beha(&$rst,&$xml,&$field ,$stamp)
{
	
		if(strtoupper(substr($rst->value($field[1]),1))=="REQUIRE")
		{
			
			require_once("../PlugIn/" . $rst->value($field[2]));
		}elseif(strtoupper(substr($rst->value($field[1]),1))=="OBJECT")
		{
			
			
			eval('$this->heap[\'object\']["' . $rst->value($field[0]) . '"] = new ' . $rst->value($field[2]) . '();');
			$this->heap['object'][$rst->value($field[0])]->set("BACK",$this);
			$this->heap['object'][$rst->value($field[0])]->set("TREEPOS",$tmp->show_xmlelement());
			//echo $this->heap[$rst->value($field[0])];
			//$this->heap[$rst->value($field[0])]->set('ich','du');
			//if( $this->heap[$rst->value($field[0])] == null )echo "<b>Fehler bei Objekt " . $rst->value($field[0]) . " </b>";

		}else{
		
		if($this->heap['object'][$rst->value($field[3])]){
			
			if($this->heap['object'][$rst->value($field[3])]->check_type(strtoupper(substr($rst->value($field[1]),1))))
			{
								
				$this->XMLlist->go_to_stamp($xml->position_stamp());
								
				$this->heap['object'][$rst->value($field[3])]->set(strtoupper(substr($rst->value($field[1]),1)),$rst->value($field[2]));
			}
		}
		}

	
}


function obj_beha(&$xml)
{
//echo "-------------------<p>";


	$tmp = null;
	
	//läd *.php
	if(!is_Null($src = $xml->show_cur_attrib("SRC")))
	{
		//echo $src;
		
			if(strtoupper(substr($src,4))=="HTTP")
			{
				require_once($src);
			}
			else
			{
				require_once("PlugIn/" . $src);
			}
				
	}

	if(!is_Null($id = $xml->show_cur_attrib("ID")) && !is_Null($this->heap['object'][$id]))
		{
			$tmp = &$this->heap['object'][$id];
			$xml->set_node_obj($tmp);
			if(is_null($tmp))echo '<br><b>' . $id . ' ist kein g&uuml;ltiger Bezeichner bei ID-Aufruf</b><br>';	
		}
	//if(is_null($tmp))echo '<br><b>' . $id . ' ist kein g&uuml;ltiger bezeichner bei ID-Aufruf</b><br>';		
	//erstellt Objekte
	if(!is_Null($name = $xml->show_cur_attrib("NAME")) && is_Null($tmp))
	{
		
		
		eval('$tmp = new ' . $name . '();');
		//übergibt das hauptmodul
		$tmp->set("BACK",$this);
		//speichert das neue Object im Baum
		$xml->set_node_obj($tmp);
		//echo get_class($tmp) . ' ' . $xml->cur_node() . ' ' . $xml->position_stamp();
		//übergibt die Baumposition
		$tmp->set("TREEPOS",$xml->show_xmlelement());
		$stamp = $xml->position_stamp();
	
		//übergibt id, wenn vorhanden
		if(!is_Null($id = $xml->show_cur_attrib("ID")))
		{
			
			$this->heap['object'][$id] = &$tmp;
			$tmp->set("ID",$id);
			
			if(is_null($tmp))echo '<br><b>' . $id . ' konnte nicht erstellt werden</b><br>';
		}
		
	}
	
		//wird immer ausgeführt, durchläuft alle parameter
		for($i = 0;$i < $xml->index_child(); $i++)
		{
		
			$xml->child_node($i);
			$stamp = $this->XMLlist->position_stamp();
			
			//ruft alles mit Param auf
			if(
				$xml->cur_node() == "PARAM"

			){
				// ermittelt den Typ des parameters
				$name = $xml->show_cur_attrib("NAME");
			  	//echo $name . ' ' . $xml->position_stamp() . ' called in ' .   $tmp->id .  "\n";
				//ruft die 
				$this->insertContent();
				
				//echo 'object end' . "\n";
				$p = 0;
				$data_save = array();
				while((($data_save[$p] = $xml->show_cur_data($p)) || $p==0 ) && $p++ < 10000);

				
				
				$res = null;
				$res .= $data_save[0];
				
				//echo "<b>" . $xml->cur_node() . "</b>" . $this->XMLlist->index_child() . "<br>";
				for($j = 0;$j < $xml->index_child(); $j++)
				{
					//echo $data_save[$j] . $i . "\n";
					//$data_save[$j];
					$this->XMLlist->child_node($j);
					$stamp3 = $this->XMLlist->position_stamp();
					
					//echo "<b>" . $this->XMLlist->cur_node() . "_xx</b>" . $this->XMLlist->position_stamp() . "<br>";
					//echo get_class($this->XMLlist->show_cur_obj());
					
					
					if(!is_subclass_of($this->XMLlist->show_cur_obj(),'plugin'))echo "ja";
					
					if(
					!is_Null($obj = $this->XMLlist->show_cur_obj())
					)
					{
						
						//echo $obj->id . ":" . get_class($obj) . "=";
						if(is_subclass_of($obj,'plugin'))
						$content = &$obj->out();
						//if(!is_object($content)){echo $content . "<p>\n";}
						//else
						//{
						//echo get_class($content) . "<p>\n";
						//}
							if(is_object($content))
							{
								//echo 'aufruf in schleife:' . strtoupper($name) . ' stamp:' . $xml->position_stamp() . ' id:' . $xml->show_cur_attrib('ID') . '<br>';
								if(is_subclass_of($content,'plugin'))$tmp->set(strtoupper($name),$content);
							}else
							{
								//echo $content;
								$res .= $content;
							}
							
							if(!is_null($data_save[$j + 1]))$res .= $data_save[$j + 1];
							//echo "<br>";
					
					}
					else
					{
					
						
						if(!is_null($data_save[$j + 1]))$res .= $data_save[$j + 1];
						
					}
					
			//echo $this->XMLlist->cur_node(); content
					$this->XMLlist->go_to_stamp($stamp3);
					$this->XMLlist->parent_node();
				}
				//echo '<br>';
				//echo get_class($tmp) . ' ' . $name;
				//if(!is_null($tmp))echo 'aufruf direkt:' . strtoupper($name) . ' stamp:' . $xml->position_stamp() . ' id:' . $xml->show_cur_attrib('ID') . '<br>';
				
				if(!is_null($tmp))$tmp->set(strtoupper($name),$res);
			}
				
			//echo $this->XMLlist->cur_node(); content
			$this->XMLlist->go_to_stamp($stamp);
			$this->XMLlist->parent_node();
			
			
		}
		
		/* durchlauf richtung root, vermutlich unbrauchbar
		while((!($xml->cur_node() == "TREE" || $xml->cur_node() == "FINAL")) && $xml->parent_node() )

		if($xml->cur_node() == "PARAM")
			{
				if($obj = &$xml->show_xmlelement())
				{
					$obj->set("BACK",$this);
				}
				break;
			}
			
		if($xml->cur_node() == "OBJECT")
			{
				if($obj = &$xml->show_xmlelement())
				{
					$obj->set("BACK",$this);
				}
				break;
			}
		*/
		

		
	
	
	
	/*
	
				$this->XMLlist->change_URI($this->structur);
				for($i = 0;$i < $this->XMLlist->index_child(); $i++)
				{
					$this->XMLlist->child_node($i);
					$stamp3 = $this->XMLlist->position_stamp();
					if(
					$this->XMLlist->cur_node() == "PARAM"
					)
					{
					
					$key = $this->XMLlist->show_cur_attrib('NAME');
					$cont = $this->XMLlist->show_cur_data();
					
					

					}
			//echo $this->XMLlist->cur_node(); content
					$this->XMLlist->go_to_stamp($stamp3);
					$this->XMLlist->parent_node();
				}
				
				
				$this->XMLlist->change_URI($this->structur);
	

	*/
	$empty = "";
	if($tmp)
	{
	$tmp->set('RUN',$empty);
	}
	else
	{
		echo "objekt nicht gefunden:" . $id;
	}
//echo "<br>" . get_class($tmp) . '<br>-------------------------';	
}

}
?>
