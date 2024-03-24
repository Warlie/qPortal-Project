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

require_once('xml_multitree_objex.php');
require_once('xml_multitree_ns.php');
require_once('handles/class_index.php');

class xml_omni extends xml_objex 
{

	var $TYPE = array();
	public $SPECIAL = array();
	var $PARAMETER = array();
	public $NAMESPACES = array();
	
	public function set_special($key, $value)
	{
		$this->SPECIAL[$this->idx][$key]= $value;

	}
	
     /* läd ein XML-Dokument */
      function load_Stream(&$source,$casefolding=1,$special="",$ref='')
        {
        	//echo $special . "\n";
        //echo $source . "\n\n";
        	global $logger_class;
		$logger_class->setAssert('load dokument with identifer "' . $ref . '" and special "' . $special . '"(xml_omni:load_Stream)',1);
		//echo 'load dokument with identifer "' . $ref . '" and special "' . $special . '"(xml_omni:load_Stream)<br>\n';
		if($special <> 'PHP')
		{
                $this->special = $special;
                $this->MIME[$this->idx] = $this->MIME_check($source);
                $this->DOC[$this->idx] = $this->DOC_check($source);
		$this->TYPE[$this->idx] = strtok($special, ";"); //strtok is a tokenizer. TODO Check using
		
		//var_dump($this->MIME, $this->DOC, $this->TYPE, $this->NAMESPACES);
		//echo "\n--------------------------------------------------\n";
		}
                       
                        $encoding = "";
                        if($this->MIME[$this->idx]['encoding'])$encoding = $this->MIME[$this->idx]['encoding'];
			
			
			$this->handle_select($source,$casefolding,$special,$ref);
                        //$this->idx = $this->max_idx++;
                        $this->used_parser = true;
                        
                       
 
                if($special <> 'PHP')
                {
                $this->cur_pos_array[$this->idx] = array(); //
                $this->deep[$this->idx] = 0;
                $this->used_parser = true;
                $this->special = "";
		}
		$this->executed();
   }
   
   function executed()
   {}
   
   function set_definition_context($key,$content)
   {

	if($key == 'MIME')
	$this->MIME[$this->idx] = $this->MIME_check($content);
        if($key == 'DOC')        
   	$this->DOC[$this->idx] = $this->DOC_check($content);
	if($key == 'TYPE')	
	$this->TYPE[$this->idx] = $content;
   }
   
   function handle_select(&$source,$casefolding=1,$special="",$ref='')
   {
	  
	   		if($special == '')
			{
				$special = 'XML';
				$this->TYPE[$this->idx] = 'XML';
			}
	   		$obj = &My_Handle_factory::handle_factory($special);
			if(!is_object($obj))echo "Descriptor \"$special\" can not be assimilated";
			$obj->set_object($this);
			$obj->set_attribute('XML_OPTION_CASE_FOLDING',$casefolding);
			$obj->set_attribute('URI',$ref);
			//echo $special;
			if(!(false === ($tmp = strpos( $special,';' ))))
			{
				$special_array = explode(';',$special);
				//echo $special;
				
				
				for($i = 1 ; $i < count($special_array);$i++)
				{
					$pos = 0;
					$pair = null;
					if(!(false === ($pos = strpos( strtoupper($special_array[$i]) ,'NAMESPACES' ))))
					{
						if(!isset($this->NAMESPACES[$this->idx])) $this->NAMESPACES[$this->idx] = array();
						$structure = explode(',',
						getInnerSubstring($special_array[$i],'(',')')[0]
						);
										
						for($i = 0 ; $i < count($structure);$i++)
						{

						$content = getInnerSubstring($structure[$i],'\'','\'')[0];
							$key =  str_replace(':', '', getInnerSubstring($structure[$i],null,'\'')[0]);


							$pair = explode(':',$key);
							if(strlen($key ) == 0)
							{
								$this->NAMESPACES[$this->idx]['@main'] = $content;
										/*					
								if(!isset($this->prefixes[$this->idx]))$this->prefixes[$this->idx] = array();
								$this->prefixes[$this->idx][] = $content;
							
								$this->prefixes_inv[$content][$this->idx] = ''; */
							}
							else
							{
							$this->NAMESPACES[$this->idx][$key] = $content;
							/*				
							if(!isset($this->prefixes[$key]))$this->prefixes[$key] = array();
							$this->prefixes[$key][] = $content;
						*/
						//$this->prefixes_inv[$value][$this->idx] = $postfix;
							}


							
						}
					//	var_dump($this->NAMESPACES);
					}
					else
					{

					//NAMESPACES
					
					$pair = explode(':',$special_array[$i]);
					$this->SPECIAL[$this->idx][strtoupper($pair[0])] = strtoupper($pair[1]);
					//echo strtoupper($pair[0]) . ' ' . strtoupper($pair[1]);
					$obj->set_attribute(strtoupper($pair[0]),strtoupper($pair[1]));
					}
				}
				
			}
			
			
			$obj->parse_document($source);
			$this->PARAMETER[$this->idx] = $obj->get_attribute();
			
			

   }

      /* redundant speichert als Stream */
function save_stream($format = '',$send_header=false,$stream=null){

return $this->handle_save($format,$send_header,$stream);
}

function save_file_stream(&$stream,$format = '',$send_header=false){

	   	//echo $this->TYPE[$this->idx];
	   		$obj = &My_Handle_factory::handle_factory($this->TYPE[$this->idx]);
			$obj->set_object($this);
			
			
			//var_dump($format);
			return $obj->save_stream_back($stream,$format);
			
	
	
}

function save_file($format = '',$send_header=false, $filename = false)
{

	$success = true;
	if ( $filename )
		$file = $filename;
	else
		$file = $this->PARAMETER[$this->idx]['URI'];
	

	$stream = null;
	if ( file_exists($file))
	{
        touch ($file);
	$stream = fopen($file, 'w');
	$success = $this->save_file_stream($stream,$format,$send_header);
	fclose($stream);
	return $success;
	}
	return false;
	
	
	//echo $this->PARAMETER[$this->idx]['URI'];
	
	//save_file_stream(&$stream,$format = '',$send_header=false)
}

  function handle_save($format,$get_header,$stream)
   {
	global $logger_class;
		

	   		$obj = &My_Handle_factory::handle_factory($this->TYPE[$this->idx]);
	   		//echo count($this->SPECIAL);
			$obj->set_object($this);
			if(is_Array($this->SPECIAL[$this->idx - 1]))
			{
			foreach($this->SPECIAL[$this->idx - 1] as $key => $value)
			{
			//echo $key . ' ' . $value . ' ';
			$obj->set_attribute(strtoupper($key),strtoupper($value));
			}
			}

			$obj->set_attribute('XML_OPTION_CASE_FOLDING',$casefolding);

			if($get_header)$obj->send_header();
			
			        	
			$logger_class->setAssert('save dokument with identifer "' . $this->loaded_URI[$this->idx] . '"(xml_omni:handle_save)',1);
			
			return $obj->save_back($format,$this->MIME[$this->idx],$this->DOC[$this->idx]);
			

   }
}
?>
