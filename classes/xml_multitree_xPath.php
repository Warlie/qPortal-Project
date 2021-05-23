<?php

/**  Aufstellung der functionen des XML objektes
*    cur_node() :         aktuelle Position
*    list_child_node() :   gibt Array mit liste der nächsten knoten raus
*    index_child() :    gibt die mente der kindknoten wieder
*    child_node(byte index) :        geht zum nächsten knoten
*    parent_node() :      geht zum übergeordneten knoten
*    show_pointer() : zeigt den wegzeiger
*    reset_pointer() : setzt den zeiger, der angibt, welchen weg man zuletzt gegangen ist, auf -1
*    mark_node([$bool]) : markiert einen Knoten und gib seinen zustand zurück
*    set_first_node() : geht zum obersten knoten
*    show_cur_attrib($attrib = null)
*    show_cur_data()
*    position_stamp() : Gibt eine Positionsmarke mit Hash-Kontrollziffer (Ziffer noch nicht funktionsfähig)
*    go_to_stamp(stamp) : Geht zur marke
*
*
*    only_child_node($bool_node) : für seek_node -> sucht dann nur alles unter dem aktuellen knoten
*    seek_node([String $type],assoz array [attrib],string [data]) : sucht einen Knoten
*
*    create_node(stamp,[pos=null]) : erstellt einen neuen knoten
*    set_node_name(name) : gibt einem Knoten einen Namen
*    set_node_attrib(key,value) : vergibt Attribute an einen Knoten
*    set_node_cdata(value,counter) : vergibt daten an einen Knoten
*
*    load(Dateiname) : läd xml.datei
*    load_Stream(String String) :       läd xml zeichenkette
*    save(Dateiname) : überschreibt Datei
*    save_Stream(format) : gibt String zurück
*
*
*    cur_idx() : Aktueller Index
*    change_idx($index)
*    change_URI($index)
*
*   set_node_obj($value)
*   show_cur_obj()
*
*   show_xmlelement()
*   
*/

require_once('xml_multitree_SPARQL.php');


class xml_xPath_sParqle extends xml_sparqle
{
private $cloneList = false;
private $xpath_sec_result = array();

	
	public function &xpath($statement)
	{
		
		if(!$this->complete_list)
		return parent::xpath($statement);
		$this->complete_list(true);
		
		$res_exist = parent::xpath($statement);
		$this->complete_list(false);
		
		if($res_exist)
		{
			if($this->cloneList)
			{
				$res = &$this->getxpathresult();
				
				$prev = null;
				for($i = 0;$i < count($res);$i++)
				{
					$this->xpath_sec_result[$i] = $res[$i]->cloning($prev);
				}
			}
			else
			{
				$this->xpath_sec_result = &$this->getxpathresult();
			}
		
		}

		return (count($this->xpath_sec_result) <> 0);
		//echo $this->xPath_attrib($statement);
		
	}
	
	public function &freexpath($statement, $tree)
	{

		$this->complete_list(true);
		
		$res_exist = parent::freexpath($statement, $tree);
		$this->complete_list(false);
		
		
		if($res_exist)
		{
			if($this->cloneList)
			{
				$res = &$this->getxpathresult();
				
				$prev = null;
				for($i = 0;$i < count($res);$i++)
				{
					
					$this->xpath_sec_result[$i] = &$res[$i]->cloning($prev);
				}
			}
			else
			{
				$this->xpath_sec_result = &$this->getxpathresult();
			}
		
		}
		
		return count($this->xpath_sec_result);
	}
	
	public function cloneResult($boolean)
	{
		$this->cloneList = $boolean;
	}
	
	public function overview_xpath_Result()
	{
		
		
		echo "<br>---------------------------------<br>\n";
		echo "-          Uebersicht           -<br>\n";
		echo "---------------------------------<br>\n";
		echo "Gefunden: " . count($this->xpath_sec_result) . "<br>\n";
		for($i = 0; $i < count($this->xpath_sec_result);$i++)
		{
		  echo $this->xpath_sec_result[$i]->name . " (" . $this->xpath_sec_result[$i]->full_URI() . ") "  . "<br>\n";
		}
		echo "---------------------------------<br>\n";
	        
	        echo "---------------------------------<br>\n";
	
	}
	
	public function get_xpath_Result()
	{
		/*
		echo "<br>---------------------------------<br>\n";
		echo "-          Uebersicht           -<br>\n";
		echo "---------------------------------<br>\n";
		echo "Gefunden: " . count($this->xpath_sec_result) . "<br>\n";
		for($i = 0; $i < count($this->xpath_sec_result);$i++)
		{
		  echo $this->xpath_sec_result[$i]->name . " (" . $this->xpath_sec_result[$i]->full_URI() . ") "  . "<br>\n";
		}
		echo "---------------------------------<br>\n";
	        
	        echo "---------------------------------<br>\n";
	*/
		return $this->xpath_sec_result;
	}
	
	public function free_xpath_Result()
	{
		unset( $this->xpath_sec_result );
		$this->xpath_sec_result = array();
		$this->freexpathresult();
		
	}
	
	public static function get_factory_class($name)
	{
		
	}
	


}

/**
* 
*/

abstract class X_StatementElement
{

private $elementField = array();


	
	public function setStatement($name,&$element)
	{
		if($name == 'set')
		$this->elementField[get_Class($element)] = &$element;
	}
	
	
}
class X_Path extends X_StatementElement
{
	public function __construct($statement)
	{	
	}
}

class X_Statement extends X_StatementElement
{
	public function __construct($statement)
	{	
	}
}

class X_Konjuncton extends X_StatementElement
{
	
}

class X_Attribute extends X_StatementElement
{
	
}
class X_Text extends X_StatementElement
{
	
}
?>
