<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class RAW_handle extends Interface_handle 
{
	//var $attribute_values = array();
	var $base_object = null;
	var $heads = array();
	var $body = array();
	private $end_of_line = "\n";
	
	private $bool_first_tag = true; 
	
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
	
	function parse_document($source)
	{
	               
		if( is_Object($source))
		
		$is_obj = is_subclass_of($source, 'FileHandle');
		
		else
		
		$is_obj = false;
		
	

    	
		$this->base_object->MIME[$this->base_object->idx]['name'] = 'xml';
		$this->base_object->MIME[$this->base_object->idx]['version'] = '1.0';
		$this->base_object->MIME[$this->base_object->idx]['encoding'] = 'ISO-8859-1'; // was ist das?
		//var_dump($this->base_object->special);



			$this->base_object->tag_open($this, 'RAW',array('xmlns'=>'http://www.raw.de/raw') );
			$this->base_object->cdata($this, $source);
			$this->base_object->tag_close($this, 'RAW');


	}
	
	function parse_Head($line)
	{
		$delimiter = '';
		if(!(false === strpos($line, ',')))$delimiter = ',';
		if(!(false === strpos($line, ';')))$delimiter = ';';
		if(!(false === strpos($line, '|')))$delimiter = '|';
		//if(!(false === strpos($line, '	')))$delimiter = '	';
		//echo $delimiter;
		$this->heads = explode($delimiter,$line);
		return $delimiter;
	}
	
	function parse_body($line,$delimiter,$i)
	{//echo $line . ' ' . $this->base_object->idx . "\n";
				if(trim($line)<>'')
				{
				$this->base_object->tag_open($this, 'ROW',array('NUM'=>$i) );
				$cur_row = explode($delimiter,$line);
				
							for($j = 0;$j<count($cur_row);$j++)
							{
								//echo $this->attribute_values['STORE'];
								if($this->attribute_values['STORE'] == 'GENERIC')
								{
								
								//$this->base_object->tag_open($this, 'FIELD',array('NAME'=>$this->heads[$j]) );
								$this->base_object->tag_open2($this, 'COLUMN', array('NAME'=>trim($this->heads[$j]), 'NUM'=>$j) );
								$this->base_object->cdata2($this, $cur_row[$j] );
								//echo $cur_row[$j];
								$this->base_object->tag_close2($this, $this->heads[$j]);
								}
								else
								{
									//echo $this->heads[$j] . ' ' . $cur_row[$j] . "\n";
								$this->base_object->tag_open($this, 'COLUMN', array('NAME'=>trim($this->heads[$j]), 'NUM'=>($j + 1)) );
								$this->base_object->cdata($this, $cur_row[$j] );
								//echo $cur_row[$j];
								$this->base_object->tag_close($this, $this->heads[$j]);
								}
							}
							
				$this->base_object->tag_close($this, 'ROW');
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
	
        function extract_Columns($row)
        {
        	$res = array();
        	for($i = 0; $i < $row->index_max(); $i++ )
        		{
        			$next = $row->getRefnext($i, true);
        			$name = $next->get_ns_attribute('http://www.csv.de/csv#NAME');
        			$value = $next->getdata();
        			if($name && $value)
        				$res[$name] = $value;
        			unset($next);
        		}
        		
        	return $res;
        }
        
        function write_Column_Values($header, $column)
        {
        	$res = array();
        	foreach ($header as $name)$res[] = $column[$name];
        	return $res;
        }
        
	
	function save_back($format,$send_header = false)
	{
		
		/**
		*
		*
		*/



                 $header = array();
                 $csv = array();
                                
                 $this->base_object->set_first_node();
		
		$this->base_object->complete_list(true);
		$this->base_object->cloneResult(true);
		if(! $this->base_object->xpath("ROW")  )return "";
		$this->base_object->cloneResult(false);
		
		$result = $this->base_object->get_xpath_Result();
		
		
		$header = array_keys($this->extract_Columns($result[0]));
		$res .= implode(';', $header) . "\n";
		foreach ($result as $row)
		{
			//echo $row->full_URI() . "\n";
			
			//$csv[] = $this->write_Column_Values($header, $this->extract_Columns($row));
			$res .= implode(';', 
				$this->write_Column_Values($header, $this->extract_Columns($row))
				) . "\n";
			//get_ns_attribute
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
