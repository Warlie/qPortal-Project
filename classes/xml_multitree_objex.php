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
*   set_xmlelement(&$node)
*/
require_once('xml_multitree.php');

class xml_objex extends xml {

protected $resultlist = array();
protected $complete_list = false;


	
        /* gibt das Object eines Knotens aus */
   function &show_cur_obj(){
              if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
              return $this->pointer[$this->idx]->getobj();
               
        }

	/* gibt die xmlelemente aus*/
   function &show_xmlelement(){return $this->pointer[$this->idx];}	
   
   	/* pendant zu show_xmlelement() */
  function set_xmlelement(&$node)
  {
  	unset($this->pointer[$this->idx]);
  	$this->pointer[$this->idx] = &$node;
  }


  
        /* schreibt Daten in einen Knoten */
   function set_node_obj(&$value){$this->pointer[$this->idx]->setobj($value);}


   function &getInstance($name,$attributes,$obj_arg = null)
   {
  
 	if($obj_arg)
	return parent::getInstance(
   	$name,
	$attributes,
	$obj_arg);
	else
	return parent::getInstance(
   	$name,
	$attributes,
	new XMLelement_objex());
   }

   /**
   *in depending to the complete_list func, it returns the first found object or a boolean expression of success
   *the created list is receivable about the get_result func
   * @param xpath: reduced xpath expression in the way name[@attribname='value']
   */
   public function &xpath($xpath)
   {
   	$this->resultlist = array();
   	if(!$this->complete_list)
   	{
   		
	 	return $this->xfind($this->pointer[$this->idx],$xpath);
	}else{
		
		$this->resultlist = array();
		$this->xfind2($this->pointer[$this->idx],$xpath);
		

		
		
		return (count($this->resultlist) <> 0);
	}
		
   }
   
   public function freexpath($xpath, $tree)
   {
   	
   	//if(!$this->resultlist)$this->resultlist = array();
	
		//echo "\n ---------Suche ($xpath)-------------\n\n";
		//echo count($this->resultlist) . ' ddd ';
		//$this->resultlist = array();
		$this->xfind2($tree,$xpath);
		//echo "suche abgeschlossen" . count($this->resultlist) . "\n\n";
		return (count($this->resultlist) <> 0);

		
   }
         /* controls the way the xpath func works */  
   	public function complete_list($bool)
	{
		$this->complete_list = $bool;
	}
   
   	/* offers a list of found objects*/  
   	public function &getxpathresult()
   	{
   		return $this->resultlist;
   	}
   	
   	/* offers a list of found objects*/  
   	public function freexpathresult()
   	{
   		//for($i = (count($this->resultlist) - 1);$i < 1;$i--)
   		//  unset($this->resultlist[$i]);
   		  unset($this->resultlist);
   		  $this->resultlist = array();
   	}
   
   	protected function &xfind(&$xml_obj,$name)
	{	
		
		if(!$xml_obj)
			{
				echo "knoten nicht gefunden:" . $this->xPath_name($name) . "<br>\n";
				return false;
			}
			
		//echo $name . ' = ' . $xml_obj->name . ' : ' . $xml_obj->attrib['CLASS'] . ' <br>';
//		echo "xpath : $name\n";
		$attrib = $this->xPath_attrib($name);
				$hit = (count($attrib) == 0);
			//	echo "---------\n";
		foreach ($attrib as $key => $value) {
			//echo $key . ' :' . $value . "\n";
			//echo " $key  :" . $xml_obj->get_ns_attribute($key) . " in Objekt " .  $xml_obj->name . "  \n";
			//echo " $key  :" . $xml_obj->get_attribute($key) . " in Objekt " .  $xml_obj->name . "  \n";
			if ($xml_obj->get_attribute($key) == $value) $hit = true;
                                                    }
//echo "---------\n";
//if($hit ) echo "found something \n";
		if($xml_obj->name <> $this->xPath_name($name) || !$hit)
		{

			

			
			
			//echo $xml_obj->index_max() . ' <br>';
			for($i=0;$i < $xml_obj->index_max(); $i++)
			{
				//echo $i . ' �bergebe n�chstes Element<br>';
				$tmp = &$this->xfind($xml_obj->getRefnext($i),$name);
				//echo "raus";
				if($tmp->name == $this->xPath_name($name))return $tmp;
			}
		}
		else
		{
			//echo '<br>Das Element <b>&quot;' . $name . '"&quot;</b> wurde nicht im Baum gefunden!<br>';
			return $xml_obj;
		}
	}
   	
   	protected function &xfind2(&$xml_obj,$name)
	{	
		
		
		$hit = true;
		$attrib = $this->xPath_attrib($name);
		//echo "in xfind2 ";
		//var_dump($attrib);
		//echo " \n";
		//echo $xml_obj->name . "<br>\n\n";
		
		if(count($attrib) <> 0)
		{
					
		foreach ($attrib as $key => $value) {
			//echo $key . ' ' . $value . "\n";
		/*	if($xml_obj->attrib[$key])
			echo "untersucht:" . $xml_obj->name . "=>[$key]'" . $xml_obj->get_attribute($key) . "'  \n";
			else
			echo "untersucht:" . $xml_obj->name . "\n";
		*/	
			
			$hit =  (($xml_obj->get_attribute($key) == $value) && $hit);
                                                    }
 
		
		if($xml_obj->name == $this->xPath_name($name) && $hit)
                {
                //echo "speichert(hit):" . $xml_obj->name . " (" . $xml_obj->full_URI() . ") " . "\n";
			
                	//count($this->resultlist)
                	$this->resultlist[] = &$xml_obj;
                }
	
                }
                else
                {
               
                if($xml_obj->name == $this->xPath_name($name))
                {
                //echo "speichert:" . $xml_obj->name . "\n";
                	//count($this->resultlist)
                	$this->resultlist[] = &$xml_obj;
                	 
                }
                }
                	
                                             
			if(!$xml_obj)
			{
				echo "knoten nicht gefunden:" . $this->xPath_name($name) . "<br>\n";
				return false;
			}
			
                //if(($xml_obj->name <> $this->xPath_name($name)))$this->resultlist[count($this->resultlist)] = &$xml_obj;
                	//echo "Ebene hoheer\n";
                	for($i=0;$i < $xml_obj->index_max(); $i++)
			{
				$this->xfind2($xml_obj->getRefnext($i),$name);
			}
			//echo "ebene Tiefer " . $xml_obj->name . "\n";

	}
	
	private function xPath_attrib($string)
		{
		
			
			if(false === ($tmp = strpos($string,'[')))
			{
				return array();
			}else
			{
				
				$attrib = substr($string,$tmp);
				//echo $attrib . ' ';
				$attrib = substr($attrib,1,1);
				
				
				if(is_numeric($attrib))
				{
				return array('@number' => $attrib);
				}
				
				$key = substr($string,$pos1 = (strpos($string,'@') + 1),strpos($string,'=') - $pos1);
				$value = substr($string,$pos2 = ((strpos($string,"'") + 1 )),strpos($string,"'",$pos2) - ($pos2));
				return array($key => $value);
			}
		}
	
	private function xPath_name($string)
		{
			if(false === ($tmp = strpos($string,'[')))
			{
				return $string;
			}else
			{
				
				return substr($string,0,$tmp);
			}
		}
   
   
} // end of class xml



?>
