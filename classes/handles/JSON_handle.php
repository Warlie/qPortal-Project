<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

function xmlNodeToPhp($node) {
    $result = [];
    // Falls Leaf‑Node (keine Kind-Elemente), direkt Wert casten
    if ($node->count() === 0) {
        $text = (string)$node;
        $type = (string)$node->attributes()['datatype'] ?? 'string';
        switch ($type) {
            case 'integer': return (int)$text;
            case 'number':  return (float)$text;
            case 'boolean': return filter_var($text, FILTER_VALIDATE_BOOLEAN);
            default:        return $text;
        }
    }
    // Ansonsten: über alle Kinder iterieren
    foreach ($node->children() as $child) {
        $name  = $child->getName();
        $value = xmlNodeToPhp($child);
        // Mehrfach-Vorkommen als Array abbilden
        if (isset($result[$name])) {
            if (!is_array($result[$name]) || array_keys($result[$name]) !== range(0, count($result[$name]) - 1)) {
                $result[$name] = [ $result[$name] ];
            }
            $result[$name][] = $value;
        } else {
            $result[$name] = $value;
        }
    }
    return $result;
}


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
//$out = $source;
//var_dump($out);
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
				echo "---- key:value---\n";

				if(is_int($value))
					$attributes['datatype'] = "integer";  //xs:integer
				if(is_float($value))
					$attributes['datatype'] = "float";  //xs:integer
				if(is_string($value))
					$attributes['datatype'] = "string";  //xs:integer
				
				echo "---------------------\n";
				if(is_array($value))
					foreach ($value as $key2 => $value2)
					{
						if($key2 == '@attributes')
							$attributes = $value2;
						
						if(is_numeric($key2))
						{
							// this could be an element to create an array
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

		$handle = &My_Handle_factory::handle_factory('XML');
		$handle->set_object($this->base_object);
		$handle->set_attribute('XML_OPTION_CASE_FOLDING',false);
		
		$xml_output = $handle->save_back("UTF-8");
			
		
		
		/**
		*var_dump($xml_output);
		*
		*/


	$filechange = str_replace(array("\n", "\r", "\t"), '', $xml_output);
# The trailing and leading spaces are trimmed to make sure the XML is parsed properly by a simple XML function.
$filetrim = trim( $filechange); //str_replace('"', "'", )
var_dump($filetrim);
# The simplexml_load_string() function is called to load the contents of the XML file.
$resultxml = simplexml_load_string($filetrim);
var_dump($resultxml);
$typedXml = xmlNodeToPhp($resultxml);
var_dump($resultxml, $typedXml);
# The final conversion of XML to JSON is done by calling the json_encode() function.
$resultjson = json_encode($resultxml, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
var_dump($resultjson);
if (!$resultjson) {
    throw new \RuntimeException("Ungültiges XML");
}

	//throw new ErrorException("---");
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
