<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class XML_handle extends Interface_handle 
{

	
	private $parser;
	
	function parse_document(&$source)
	{
	//$this->base_object->test_consistence();
		$is_obj = ($source instanceof FileHandle);
		//$is_obj = is_subclass_of($source, 'FileHandle');
		
//		if(!$is_obj)
		
		$this->parser = xml_parser_create(); //'UTF-8'

            xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, $this->attribute_values['XML_OPTION_CASE_FOLDING'] );
			//xml_parser_set_option( $this->parser, XML_OPTION_TARGET_ENCODING, 'ISO-8859-1' );
            //xml_set_object($this->parser, $this->base_object);
			
			
            xml_set_element_handler($this->parser, [$this->base_object, "tag_open"], [$this->base_object, "tag_close"]);
            xml_set_character_data_handler($this->parser, [$this->base_object, "cdata"]);
            xml_set_external_entity_ref_handler($this->parser, [$this->base_object, "tag_entity"]);
			xml_set_notation_decl_handler($this->parser, [$this->base_object, "tag_notation"]);
			xml_set_unparsed_entity_decl_handler($this->parser, [$this->base_object, "tag_up_entity"]);
			xml_set_processing_instruction_handler($this->parser, [$this->base_object, "tag_instruction_entry"]);
		
			

		
		if(!$is_obj)
			{

				$allRows = explode("\n",$source); 
				if(!xml_parse($this->parser, $source)){
                        
                                
					$lineNum = xml_get_current_line_number($this->parser);
					echo xml_error_string(xml_get_error_code($this->parser));
					echo $lineNum;
					echo ' in rowcontent:' . $allRows[$lineNum-1] . '<br>';
					return 2;


				}
				
			}
			else
			{
				if(!$source->toPos(0))echo 'Error on reseting pointer in "CSV_handle" in line 61!';
				$i = 1;
				
				while($source->eof())
				{
				
				if (!xml_parse($this->parser, $source->get_line())) {
                        
                                
					$lineNum = xml_get_current_line_number($this->parser);
					echo xml_error_string(xml_get_error_code($this->parser));
					echo $lineNum;
					echo ' in rowcontent:' . $allRows[$lineNum-1] . '<br>';
					return 2;


				}
				$i++;
				}
				$source->close_File();
			}
		
		
		xml_parser_free($this->parser); 
		unset($this->parser);
		return 0;
		
	}
	
	
	private $bool_first_tag = true; 
	
	private function positionstamp($mod)
	{
	
	//if($mod)
	if($mod <> 'TRACE')return '';
	if($this->bool_first_tag)
	{
	$this->bool_first_tag = false;
	
		return ' xmlns:sg="http://www.auster-gmbh.de/surface-generator-lib" sg:p="' . $this->base_object->position_hash_pos() . '" ';
	
	}
	//echo $this->base_object->position_hash_pos() . "\n";
	// echo $this->back->position_path_map(); $this->base_object->position_stamp()
	return ' sg:p="' . $this->base_object->position_hash_pos() . '" ';
	}
	
	function save_back($format,$send_header = false)
	{
		
		$encoding = $format;
		
		/**
		*
		*
		*/
		$printall = false;
		if( $this->attribute_values['OUTPUT'] == 'ALL')
		{
		$printall = true;
		
		}
		
      $modus = "";
      if($this->attribute_values['MODUS']) $modus = $this->attribute_values['MODUS'];
		
      switch ($format)
      {
      case 'HTML': $arg = 'ISO-8859-5';
      break;
      case 'UTF-8': $arg = 'UTF-8';
      }

	

      $nl = chr(13) . chr(10);
            
       $res = '<?xml';        
      
              foreach ($this->base_object->MIME[$this->base_object->idx] as $key => $ele)
                {
                        if($key == 'name'); //$res .= strtolower($ele) . ' ';
                        else
                        {
                        	if($key == "encoding")
                        	{
                        		// TODO format can be null 
                        		if($encoding == '')
                        			$encoding = $ele;
                        		
                        		
                        		$res .= ' ' . $key . '="' . $encoding  . '"';

                        	}
                        	else
                        		$res .= ' ' . $key . '="' . $ele  . '"';
 
                        }
                }
      
      $res .= "?>$nl";

      
              foreach ($this->base_object->INSTR[$this->base_object->idx] as $key => $ele)
                {
                	$res .= '<?' . $ele['target'] . " " . $ele['data'] . "?>$nl";

                }

      
      //if($this->base_object->DOC[$this->base_object->idx] <> '')echo $this->base_object->DOC[$this->base_object->idx];
      if(($this->base_object->DOC[$this->base_object->idx] <> '') && !is_array($this->base_object->DOC[$this->base_object->idx]))$res .= $this->base_object->DOC[$this->base_object->idx];


/**
*
*
*/
if($printall)
{
$res .=  '<root>';
$start = 0;
$stop = $this->base_object->doc_many() - 1;
//echo $this->base_object->doc_many();
}
else
{
$start = $this->base_object->cur_idx();
$stop = $this->base_object->cur_idx() + 1;
}
//echo $this->base_object->doc_many();

for($ic = $start;$ic < $stop ; $ic++)
{
$end = false;
	$this->base_object->change_idx($ic);

	                        $deep[$this->base_object->idx]=0;
                                $i = 30000;
                                $end = false;

                                $reset=true;

/* */
                              $this->base_object->set_first_node();

                              //fputs ($fp, "\n<ul>\n");


$myhelp = 0;
 while(!$end){
 //schaltet den pointer zurück, wenn ein neuer knoten betreten wird
 if($reset)$this->base_object->reset_pointer();

  //testet, ob es weitere knoten gibt
  if($this->base_object->index_child()>0){

   //schreibt die eingabe
   if(-1 == $this->base_object->show_pointer()){
   	   
   	//   set_read_event
//echo $this->base_object->cur_node() . "\n";
    $res .=  '<' .  $this->base_object->cur_node() . $this->base_object->all_attrib_axo($format) . $this->positionstamp($modus) . '>';
    $res .=  $this->base_object->setcdata_tag($this->base_object->show_cur_data(0),$this->base_object->show_curtag_cdata());
    $reset = true;

     if(!$this->base_object->child_node(0)) 
      {
       if(is_null($this->base_object->show_xmlelement()))$this->base_object->parent_node();
        $this->base_object->show_xmlelement()->giveOutOverview();
        throw new ErrorException('Consistence-Error in ' 
         . $this->base_object->cur_node() . ' on position-stamp ' 
         . $this->base_object->position_stamp() . ' current historypointer is on "'
         . $this->base_object->show_pointer() . '" and '
         . $this->base_object->index_child() .  ' child(s) ' . "\n", 0,1,'XML_handle.php',1);
      }

                                                                       
        $deep[$this->base_object->idx]++;
      }elseif((($this->base_object->index_child()-1) > $this->base_object->show_pointer()) ){
                                                                       
       $res .=  $this->base_object->setcdata_tag($this->base_object->show_cur_data($this->base_object->show_pointer()+1,$format),$this->base_object->show_curtag_cdata()); 
       $reset = true;
       $check = $this->base_object->child_node($this->base_object->show_pointer() + 1);
       $deep[$this->base_object->idx]++;
                                                                                
       if(!$check){
       //echo 'booh';
       $res .=  '<!-- ' . "unerlaubte aktion, fehler beim konvertieren:" . $this->base_object->cur_node() . ' -->';
       //die("unerlaubte aktion, fehler beim konvertieren:" . $this->base_object->cur_node());
                                                                        $end = !$this->base_object->parent_node();
                                                                        $deep[$this->base_object->idx]--;
                                                                        $reset = false;        
             
                                                                   }

       }else{

        $res .=  $this->base_object->setcdata_tag($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$this->base_object->show_curtag_cdata());

        $res .=  '</' .  $this->base_object->cur_node() . '>';

        $end = !$this->base_object->parent_node();

        $deep[$this->base_object->idx]--;
        $reset = false;
        }
                                        
        }else{

                                                                        if(-1 == $this->base_object->show_pointer()){
                                                                        $res .=  '<' .  $this->base_object->cur_node() . $this->base_object->all_attrib_axo($format) . $this->positionstamp($modus) ;}
                                                                                if( '' <>( $this->base_object->show_cur_data($this->base_object->show_pointer()+1)) )
                                                                                {
                                                                                $res .=  '>' . $this->base_object->setcdata_tag($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$this->base_object->show_curtag_cdata());
                                                                                $res .=  '</' .  $this->base_object->cur_node() . '>';   // str_repeat (" ", 2*$deep[$this->idx])
                                                                                }
                                                                                else
                                                                                $res .=  '/>';
                                                                        $end = !$this->base_object->parent_node();
                                                                        $deep[$this->base_object->idx]--;
                                                                        $reset = false;
                                                                        //echo 'hallo';

                                        }

                                }
                                $res .= "\n";
}
if($printall) $res .=  '</root>';


	$this->bool_first_tag = true;

	//convert_to_XML(

				return $this->base_object->convert_to_XML($res, $encoding, true);
		
	}
	
function save_stream_back(&$stream, $format,$send_header = false)
{
				
				return (false !== fwrite($stream, $this->save_back($format)));

}
	
function send_header()
{
	                        if (
	                        	(array_key_exists("HTTP_ACCEPT", $_SERVER) && stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml")) ||
					(array_key_exists("HTTP_ACCEPT", $_SERVER) && stristr($_SERVER["HTTP_USER_AGENT"],"W3C_Validator"))) {
					header("Content-type: application/xhtml+xml");
					header('Cache-Control: no-cache, no-store, must-revalidate');
					header('Pragma: no-cache');
					header('Expires: 0');
				} else {
					header("Content-type: text/html");
				}
}
		
}

?>
