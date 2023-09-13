<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class JSON_handle extends Interface_handle 
{
	//var $attribute_values = array();
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

			if(!$is_obj)
			{

				$json_array = json_decode($source);
				
				$native_attribute = array();

				foreach ($this->base_object->NAMESPACES[$this->base_object->idx] as $key => $value)
					if('@main' == $key) 
						$native_attribute['xmlns'] = $value;
					else
						$native_attribute['xmlns:' . $key ] = $value;

		//var_dump($this->base_object->TYPE[$this->base_object->idx]);
		//var_dump($setOfArrays);
				$this->base_object->tag_open($this, 'json',$native_attribute );
				$this->parse_body($json_array);				
				$this->base_object->tag_close($this, 'json');
				

				/*
				$allRows = explode($this->end_of_line,$source); //Delimiter falsch
				
				$delimiter = $this->parse_Head($allRows[0]);
				for($i = 1;$i < count($allRows);$i++)
				{
					
					$this->parse_body($allRows[$i],$delimiter,$i);
				} 
				echo "biib";
				*/
			}
			else
			{
				echo "buub";
				
				//erstes setzen
				if(!$this->base_object->PARAMETER[$this->base_object->idx]['LOADED_RECORDS'])$this->base_object->PARAMETER['LOADED_RECORDS'] = 0;
				if($this->base_object->PARAMETER[$this->base_object->idx]['MODUS'])$modus = $this->base_object->PARAMETER[$this->base_object->idx]['MODUS'];


				
				
				while(!$source->eof() && $i < $limit)
				{
					//echo 'booh' . $i . ' ';
					$this->parse_body($source->get_line(),$delimiter,$i++);
				}
					//if($source->eof())echo "geschlossen" . $limit . ' ' ;
				//halfloaded
				if(!$source->eof())
				{
					$this->attribute_values['LOADED_RECORDS'] = $i;
					$this->attribute_values['HEADS'] = $this->heads;
					//$this->attribute_values['MODUS'] == 'SPLIT';
					$this->attribute_values['FILE'] = &$source;
					$this->attribute_values['DELIMITER'] = $delimiter;
					//$this->attribute_values['MODUS3'] = 'booh';
					//echo $modus;
					//$source->close_File();
					
					
				}
				else
				{
					//$this->attribute_values['MODUS'] = $this->base_object->PARAMETER[$this->base_object->idx]['MODUS'];
					$this->attribute_values['LOADED_RECORDS'] = -1;
					$source->close_File();
				}
			}
			
			
			
			
			
			//alle spaltennamen
		//	$this->heads = explode(";",$allRows[0]); 

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
    $text = str_replace($search, $replace, $text);
    return $text;
}
	
	function parse_body($setOfArrays)
	{

		//var_dump($setOfArrays);
		if(is_array($setOfArrays))
			foreach ($setOfArrays as $key => $value)
			{

				var_dump("array", $key , $value);
			}
		elseif(is_object($setOfArrays))
			foreach ($setOfArrays as $key => $value)
			{
				if(is_array($value))
					foreach ($value as $key2 => $value2)
					{
						
						$this->base_object->tag_open($this, $key, array() );
						$this->parse_body($value2);				
						$this->base_object->tag_close($this, $key);

					}
				else
					{

						$this->base_object->tag_open($this, $key, array() );
						$this->base_object->cdata($this,  $this->replaceUmlaute($value));		
						$this->base_object->tag_close($this, $key);						
					}
				
			}
		else
			{
				var_dump("element", $setOfArrays);
			}
		
	}
	
	function convert_to_XML( $String , $format)
        {
                
		
		if($format == '') $format = $this->MIME[$this->idx]['encoding'];
                
                $tmp;
                switch( strtoupper($format) )
                {

                case 'UTF-8':
                       return utf8_encode($String);
                       

                                
                        break;
                case 'ISO-8859-1':
                      //echo $String ."<p>\n";
                       return $String;
                        break;
                default:
                
                }

   
                return $tmp;
   
        }
	
	
	function save_back($format, $send_header = false)
	{
		
      switch ($format)
      {
      case 'HTML': $arg = 'ISO-8859-5';
      break;
      case 'UTF-8': $arg = 'UTF-8';
      }


 
      				$inter_counter = 0;
                                $deep[$this->base_object->idx]=0;
                                
                                $end = false;

                                $reset=true;
                              $this->base_object->set_first_node();

                              //fputs ($fp, "\n<ul>\n");


                                while(!$end){

                                //schaltet den pointer zurück, wenn ein neuer knoten betreten wird
                                if($reset)$this->base_object->reset_pointer();

                                //testet, ob es weitere knoten gibt
                                if($this->base_object->index_child()>0){

                                        //schreibt die eingabe
                                       if(-1 == $this->base_object->show_pointer()){

                                                                       //Knotenname und attribute f�r den Wurzelknoten
					       			       $res .=  '<' .  $this->base_object->cur_node() . $this->base_object->all_attrib_axo($format) . '>';
								       
                                                                       //gibt den ersten datenknoten aus
								       $res .=  trim($this->base_object->setcdata_tag($this->convert_to_XML($this->base_object->show_cur_data(0),$format),$this->base_object->show_curtag_cdata()));
                                                                       $reset = true;
								       //geht in den ersten Kindsknoten
                                                                       $this->base_object->child_node(0);

                                                                       //
                                                                       $deep[$this->base_object->idx]++;
                                                                        }elseif((($this->base_object->index_child()-1) > $this->base_object->show_pointer()) ){
                                                                       
									$res .=  trim($this->base_object->setcdata_tag(
								       						$this->convert_to_XML(
															$this->base_object->show_cur_data($this->base_object->show_pointer()+1,$format)
															,$format)
														,$this->base_object->show_curtag_cdata()
														));
                                                                       $reset = true;
                                                                       $check = $this->base_object->child_node($this->base_object->show_pointer() + 1);
                                                                       $deep[$this->base_object->idx]++;
                                                                                if(!$check){
                                                                                echo 'geht nicht weiter';
                                                                                }

                                                                        }else{

                                                                        $res .=  trim($this->base_object->setcdata_tag($this->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$format),$this->base_object->show_curtag_cdata()));
									
                                                                        $res .=  '</' .  $this->base_object->cur_node() . '>';

                                                                        $end = !$this->base_object->parent_node();

                                                                        $deep[$this->base_object->idx]--;
                                                                        $reset = false;
                                                                        }
                                        }else{

                                                                        if(-1 == $this->base_object->show_pointer()){
										
										if($inter_counter == 0 )$this->heads[count($this->heads)] = $this->base_object->cur_node();
										
										$this->body[$this->base_object->cur_node()][$inter_counter] = 
												trim(
													$this->base_object->show_cur_data() 
													);
												//$this->convert_to_XML(,$format 
												//	,true 
												//	)
												
                                                                        $res .=  '<' .  $this->base_object->cur_node() . $this->base_object->all_attrib_axo($format) ;}
                                                                                if( '' <>( $this->base_object->show_cur_data($this->base_object->show_pointer()+1)) )
                                                                                {
                                                                                $res .=  '>' . $this->base_object->setcdata_tag($this->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$format),$this->base_object->show_curtag_cdata());
                                                                                $res .=  '</' .  $this->base_object->cur_node() . ' >';   // str_repeat (" ", 2*$deep[$this->idx])
                                                                                }
                                                                                else
                                                                                $res .=  '/>';

                                                                        $end = !$this->base_object->parent_node();
                                                                        $deep[$this->base_object->idx]--;
                                                                        $reset = false;
                                                                        //echo 'hallo';

                                        }

                                if( $deep[$this->base_object->idx] == 0 ) $inter_counter++;
				
                                }
				
					if(is_Null($this->attribute_values['HEAD']) || $this->attribute_values['HEAD'])
				        $res = implode($this->heads,";") . "\n";
					
					for($x = 0;$x < $inter_counter;$x++)
					{
						for($y = 0;$y < count($this->heads);$y++)
						{
							//echo $this->heads[$y]  . "\n";
							//echo $x  . "\n";
							
							$res .= $this->body[ $this->heads[$y] ][$x];
							if($y < (count($this->heads)-1))
							$res .= ';';
							else
							$res .= "\n";
						}
					}
				
					
				return $res;
				
	}
	
	function save_stream_back(&$stream,$format, $send_header = false)
	{
		
      switch ($format)
      {
      case 'HTML': $arg = 'ISO-8859-5';
      break;
      case 'UTF-8': $arg = 'UTF-8';
      }


 
      				$inter_counter = 0;
                                $deep[$this->base_object->idx]=0;
                                
                                $end = false;

                                $reset=true;
                              $this->base_object->set_first_node();

                              //fputs ($fp, "\n<ul>\n");


                                while(!$end){

                                //schaltet den pointer zurück, wenn ein neuer knoten betreten wird
                                if($reset)$this->base_object->reset_pointer();

                                //testet, ob es weitere knoten gibt
                                if($this->base_object->index_child()>0){

                                        //schreibt die eingabe
                                       if(-1 == $this->base_object->show_pointer()){

                                                                       //Knotenname und attribute f�r den Wurzelknoten
					       			       $res .=  '<' .  $this->base_object->cur_node() . $this->base_object->all_attrib_axo($format) . '>';
								       
                                                                       //gibt den ersten datenknoten aus
								       $res .=  trim($this->base_object->setcdata_tag($this->convert_to_XML($this->base_object->show_cur_data(0),$format),$this->base_object->show_curtag_cdata()));
                                                                       $reset = true;
								       //geht in den ersten Kindsknoten
                                                                       $this->base_object->child_node(0);

                                                                       //
                                                                       $deep[$this->base_object->idx]++;
                                                                        }elseif((($this->base_object->index_child()-1) > $this->base_object->show_pointer()) ){
                                                                       
									$res .=  trim($this->base_object->setcdata_tag(
								       						$this->convert_to_XML(
															$this->base_object->show_cur_data($this->base_object->show_pointer()+1,$format)
															,$format)
														,$this->base_object->show_curtag_cdata()
														));
                                                                       $reset = true;
                                                                       $check = $this->base_object->child_node($this->base_object->show_pointer() + 1);
                                                                       $deep[$this->base_object->idx]++;
                                                                                if(!$check){
                                                                                echo 'geht nicht weiter';
                                                                                }

                                                                        }else{

                                                                        $res .=  trim($this->base_object->setcdata_tag($this->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$format),$this->base_object->show_curtag_cdata()));
									
                                                                        $res .=  '</' .  $this->base_object->cur_node() . '>';

                                                                        $end = !$this->base_object->parent_node();

                                                                        $deep[$this->base_object->idx]--;
                                                                        $reset = false;
                                                                        }
                                        }else{

                                                                        if(-1 == $this->base_object->show_pointer()){
										
										if($inter_counter == 0 )$this->heads[count($this->heads)] = $this->base_object->cur_node();
										
										$this->body[$this->base_object->cur_node()][$inter_counter] = 
												trim($this->convert_to_XML(
													$this->base_object->show_cur_data() 
													,$format
													));
												
												
                                                                        $res .=  '<' .  $this->base_object->cur_node() . $this->base_object->all_attrib_axo($format) ;}
                                                                                if( '' <>( $this->base_object->show_cur_data($this->base_object->show_pointer()+1)) )
                                                                                {
                                                                                $res .=  '>' . $this->base_object->setcdata_tag($this->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$format),$this->base_object->show_curtag_cdata());
                                                                                $res .=  '</' .  $this->base_object->cur_node() . ' >';   // str_repeat (" ", 2*$deep[$this->idx])
                                                                                }
                                                                                else
                                                                                $res .=  '/>';

                                                                        $end = !$this->base_object->parent_node();
                                                                        $deep[$this->base_object->idx]--;
                                                                        $reset = false;
                                                                        //echo 'hallo';

                                        }

                                if( $deep[$this->base_object->idx] == 0 ) $inter_counter++;
				
                                }
				
				        if(is_Null($this->base_object->PARAMETER[$this->base_object->idx]['HEAD_OFF']) || !$this->base_object->PARAMETER[$this->base_object->idx]['HEAD_OFF'])
					{
					$res = implode($this->heads,";") . "\n";
					}
					else
					{
					$res = '';
					}
					for($x = 0;$x < $inter_counter;$x++)
					{
						for($y = 0;$y < count($this->heads);$y++)
						{
							$res .= $this->body[ $this->heads[$y] ][$x];
							if($y < (count($this->heads)-1))
							$res .= ';';
							else
							$res .= "\n";
						}
					}
				
					
				$stream->write_file($res) ;
				return true;
				
	}
		
function send_header()
{

					header("Content-type: text/html;  charset=iso-8859-1");

}

}



?>
