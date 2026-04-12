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
        	/*
        	$res = array();
        	foreach ($header as $name)$res[] = $column[$name];
        	var_dump($res, "help");
        	return $res;
*/
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
	

		
function send_header()
{

					header("Content-type: text/html;  charset=iso-8859-1");

}

}



?>
