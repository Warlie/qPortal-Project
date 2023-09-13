<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class XML_handle_registry extends Interface_handle 
{

	
	private $parser;
	
	function parse_document(&$source)
	{
	//$this->base_object->test_consistence();
		$is_obj = ($source instanceof FileHandle);
		//$is_obj = is_subclass_of($source, 'FileHandle');
		
		if(!$is_obj)
		
		$this->parser = xml_parser_create(); //'UTF-8'

            xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, $this->attribute_values['XML_OPTION_CASE_FOLDING'] );
			xml_parser_set_option( $this->parser, XML_OPTION_TARGET_ENCODING, 'ISO-8859-1' );
            xml_set_object($this->parser, $this->base_object);
			
			
            xml_set_element_handler($this->parser, "tag_open", "tag_close");
            xml_set_character_data_handler($this->parser, "cdata");
            xml_set_external_entity_ref_handler($this->parser, "tag_entity");
			xml_set_notation_decl_handler($this->parser, "tag_notation");
			xml_set_unparsed_entity_decl_handler($this->parser, "tag_up_entity");
			xml_set_processing_instruction_handler($this->parser, "tag_instruction_entry");
		
			

		
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
		echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" .'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" ><body>';
		$this->base_object->test_consistence();
		echo '</body></html>';
				return "";
		
	}
	
function save_stream_back(&$stream, $format,$send_header = false)
{
				
				return (false !== fwrite($stream, $this->save_back($format)));

}
	
function send_header()
{
	                        if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ||
					stristr($_SERVER["HTTP_USER_AGENT"],"W3C_Validator")) {
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
