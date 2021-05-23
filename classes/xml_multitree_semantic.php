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

require_once('xml_multitree_xPath.php');


class xml_presave_semantic extends xml_xPath_sParqle
{
	private $switch = false;
	private $direkt = true;


	public function load_Stream(&$source,$casefolding=1,$special="",$ref='')
	{
	
	
		parent::load_Stream($source,$casefolding,$special,$ref);
	}

	public function tag_open($parser, $tag, $attributes, $pos = -1)
	{
	   
		if($this->switch || $this->direkt)parent::tag_open($parser, $tag, $attributes, $pos);
		if(!$this->switch)$this->tag_access_open($parser, $tag, $attributes);
	}
	   
	public function cdata($parser, $cdata)
   	{
   		if($this->switch || $this->direkt)parent::cdata($parser, $cdata);
   		if(!$this->switch)$this->access_cdata($parser, $cdata);
	}
	
	public function tag_close($parser, $tag)
	{
		if($this->switch || $this->direkt)parent::tag_close($parser, $tag);
		if(!$this->switch)$this->tag_access_close($parser, $tag);
	}
	
	private function tag_access_open($parser, $tag, $attributes)
	{
		
	}
	
	private function access_cdata($parser, $cdata)
	{
		
	}
	
	public function tag_access_close($parser, $tag)
	{
		
	}
	
	public function showCurrentTree()
	{
		
	}
}

