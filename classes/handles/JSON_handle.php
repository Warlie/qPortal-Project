<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class JSON_handle extends Interface_handle 
{

	
	private $parser;
	
	var $base_object = null;
	var $heads = array();
	var $body = array();
	private $end_of_line = "\\n";

	
	function check_format($example)
	{
		return false;
	}
	
	/**
	/*
	/* XML_OPTION_CASE_FOLDING : {0,1}
	*/
	//function set_attribute($key,$value)
	//{
	//	$this->attribute_values[$key] = $value;
	//}
	function set_object(&$obj)
	{
		$this->base_object = &$obj;
	}
	
	function parse_document(&$source)
	{
	    /* filehandle vs string */           
		if( is_Object($source))
		$is_obj = is_subclass_of($source, 'FileHandle');
		else
		$is_obj = false;
		
		/* gives information to MIME note */
		
		$this->base_object->MIME[$this->base_object->idx]['name'] = 'json';
		$this->base_object->MIME[$this->base_object->idx]['version'] = '1.0';
		$this->base_object->MIME[$this->base_object->idx]['encoding'] = 'ISO-8859-1';
		//var_dump($this->base_object->special);

		//looks up for line restriction
		if(false !== $pos_in = strrpos($this->base_object->TYPE[$this->base_object->idx],'line_end'))
		{
		$pos_in = explode('\'', $this->base_object->TYPE[$this->base_object->idx], $pos_in);
		$this->end_of_line = $pos_in[1];
		}
		
		/* TYPE contains special */
		$end_of_line =  $this->base_object->TYPE[$this->base_object->idx] . ' ';		

//var_dump($source);
				$json_array = json_decode($source, true);

				if(is_null($json_array))echo json_last_error_msg();
				
				$native_attribute = array();

				if(is_null($this->base_object->NAMESPACES[$this->base_object->idx]) == 0)
					$native_attribute[] = $native_attribute['xmlns'] = 'json';
				else
				foreach ($this->base_object->NAMESPACES[$this->base_object->idx] as $key => $value)
					if('@main' == $key) 
						$native_attribute['xmlns'] = $value;
					else
						$native_attribute['xmlns:' . $key ] = $value;


				$this->base_object->tag_open($this, 'json',$native_attribute );
				$this->parse_body($json_array);				
				$this->base_object->tag_close($this, 'json');
				



	}
	


	
	function parse_Head($line)
	{
		$delimiter = '';
		if(!(false === strpos($line, ',')))$delimiter = ',';
		if(!(false === strpos($line, ';')))$delimiter = ';';
		if(!(false === strpos($line, '|')))$delimiter = '|';
		if(!(false === strpos($line, '	')))$delimiter = '	';
		//echo $delimiter;
		$this->heads = explode($delimiter,$line);
		return $delimiter;
	}
	
	private function replaceUmlaute($text) {
    $search = array('Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß');
    $replace = array('AE', 'OE', 'UE', 'ae', 'oe', 'ue', 'ss');
   // $text = str_replace($search, $replace, $text);
    return $text;
}
	
	function arrayToXml($array, &$xml){
		foreach ($array as $key => $value) {
			if(is_int($key)){
				$key = "e";
			}
				if(is_array($value)){
					$label = $xml->addChild($key);
					$this->arrayToXml($value, $label);
				}
				else {
					$xml->addChild($key, $value);
				}
    }
    }

	function parse_body($setOfArrays, $arrayKey = null)
	{

		$attributes = [];
		$key2;
		

		
			foreach ($setOfArrays as $key => $value)
			{

				
				if(is_array($value))
					foreach ($value as $key2 => $value2)
					{
						if($key2 == '@attributes')
							$attributes = $value2;
						
						if(is_numeric($key2))
						{

							$this->parse_body($value, $key);
							break;
						}
					}

				if(is_numeric($key2))
					break;

				if($key == '@attributes')
					continue;

				elseif(is_array($value))
					{
						
						$this->base_object->tag_open($this, (is_null($arrayKey)? $key: $arrayKey), $attributes );
						$this->parse_body($value);				
						$this->base_object->tag_close($this, (is_null($arrayKey)? $key: $arrayKey));
						$attributes = [];

					}
				else
					{
						//
						$this->base_object->tag_open($this, $key, $attributes );
						$this->base_object->cdata($this,  $value);		
						$this->base_object->tag_close($this, $key);
						$attributes = [];
					}
				
			}

		
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
      
       $res = '<?';        
      
              foreach ($this->base_object->MIME[$this->base_object->idx] as $key => $ele)
                {
                        if($key == 'name')$res .= strtolower($ele) . ' ';
                        else
                                $res .= $key . '="' . $ele . '" ';
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
//echo $this->base_object->cur_node() . "\n";
    $res .=  '<' .  $this->base_object->cur_node() . $this->base_object->all_attrib_axo($format) . $this->positionstamp($modus) . '>';
    $res .=  $this->base_object->setcdata_tag($this->base_object->convert_to_XML($this->base_object->show_cur_data(0),$format),$this->base_object->show_curtag_cdata());
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
                                                                       
       $res .=  $this->base_object->setcdata_tag($this->base_object->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1,$format) ,$format),$this->base_object->show_curtag_cdata()); 
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

        $res .=  $this->base_object->setcdata_tag($this->base_object->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$format),$this->base_object->show_curtag_cdata());

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
                                                                                $res .=  '>' . $this->base_object->setcdata_tag($this->base_object->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$format,$this->base_object->show_curtag_cdata()),$this->base_object->show_curtag_cdata());
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

	$filechange = str_replace(array("\n", "\r", "\t"), '', $res);
# The trailing and leading spaces are trimmed to make sure the XML is parsed properly by a simple XML function.
$filetrim = trim(str_replace('"', "'", $filechange));
# The simplexml_load_string() function is called to load the contents of the XML file.
$resultxml = simplexml_load_string($filetrim);
# The final conversion of XML to JSON is done by calling the json_encode() function.
$resultjson = json_encode($resultxml);
	
				return $resultjson;
		
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
					header("Content-Type: application/json");
				}
}
		
}

?>
