<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class PHP_handle extends Interface_handle 
{
	public $attribute_values = array();
	var $base_object = null;
	var $heads = array();
	var $body = array();
	
	function __construct(){$this->attribute_values['workspaces'] = 'surface_tree_engine';}

	function check_format($example)
	{
		return $this->attribute_values[$key];
	}
	
	/**
	/*
	/* XML_OPTION_CASE_FOLDING : {0,1}
	*/
	function set_attribute($key,$value)
	{
		$this->attribute_values[$key] = $value;
	}
	
	function set_object(&$obj)
	{
		$this->base_object = &$obj;
	}
	
	function parse_document(&$source)
	{
	
	               
		//var_dump($source);
		$is_obj = ($source instanceof FileHandle );
		
		$str_source;	
		
			if(!$is_obj)
			{
				$str_source = $source;
			}
			else
			{
				
				
				while(!$source->eof())
				{
					//echo 'booh' . $i . ' ';
					$str_source .= $source->get_line();
				}
					$source->close_File();

			}
			//echo $this->attribute_values['URI'];
			$filescanner = new File_Scan();
			
			$filescanner->insert_str($str_source, $this->attribute_values['URI']);
			//$filescanner->add_path('/');
			$filescanner->add_tag('class ');
			$filescanner->add_tag('function ');
			$filescanner->switch_cross_seek(array('include("','")'));
			$filescanner->switch_cross_seek(array('require("','")'));
			$filescanner->switch_cross_seek(array('require_once("','")'));
			$filescanner->seeking();
			$result = $filescanner->result();
			
			/*
			foreach( $result as $value)
			{
				echo $value['tag'] . "\n";
			}
			*/			
			
			$this->base_object->set_first_node();
			

			
			//echo count($result);
			//echo $this->base_object->cur_idx() . '!! ';
			

			
			
			//needed for update spezific workspace 
			//$workspace_list = explode(';',$this->attribute_values['workspaces']);
			
			$ws = null;
			if(!$ws)$ws = "surface_tree_engine";
			
			$document_stamp = $this->base_object->getControlUnit( $ws)->getPositionStampReg();
//echo $document_stamp;
//$this->base_object->ALL_URI();
			 
			
			$xmlPos = $this->base_object->position_stamp();
			$this->base_object->go_to_stamp($document_stamp);
			//echo $this->base_object->position_stamp() . '!! ' . $this->base_object->cur_node() . ' ';
			$obj_class = new Obj_Class_Collection($result,$this->base_object); //finds pre defined values
			
			$this->base_object->use_ns_def_strict(true);


			//	$this->base_object->go_to_stamp($document_stamp);
				$obj_class->create_rdf_entry($this->base_object);

			$this->base_object->use_ns_def_strict(false);
			
			$this->base_object->go_to_stamp($xmlPos);
			// $this->base_object->show_index();
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

                                                                       //Knotenname und attribute für den Wurzelknoten
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
				
					if(is_Null($this->attribute_values['HEAD']) || $this->attribute_values['HEAD'])
				        $res = implode($this->heads,";") . "\n";
					
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

class Obj_Class_Collection
{
	private $collection_Array = array();
	private $cur_node;
	
	/**
	* Collects all entries to describe a plenty of classes
	*
	*/
	public function __construct( array $structure, xml_ns &$xml_model) 
	{
		$class_ref;
		
			foreach( $structure as $value)
			{
				
				if(!(false === stripos($value['tag'],'class ')))
				{
					//echo $value['tag'] . " as Class<br>\n";
					$this->cur_node = null;
					
					
					
					$this->collection_Array[count($this->collection_Array)] = $this->cur_node = new Obj_Class( $value );
				}
				
				if(!(false === stripos($value['tag'],'function ')))
				{
					//echo $value['tag'] . " as function <br>\n";
					if(is_Object($this->cur_node))
					{$this->cur_node->add_function($value,$xml_model);
					}
					else
					{
						echo "Error occurs on entry" . $value['tag'] . "" ;
					}
					
				}
				

			}
			
			reset($structure);
			

	}
	
	/**
	* updates the list in registry
	*/
	public function create_rdf_entry( xml_ns &$xml_model)
	{
		foreach( $this->collection_Array as $value)
			{
				
				$value->create_rdf_entry($xml_model);
				
			
			}
	}
}

class Obj_Class 
{
	private $name;
	private $php_path;
	private $num;
	private $constructor = null;
	private $subClassOf = null;
	
	private $functionList = array();
	
	public function __construct($array_tag)
	{
		
		$this->php_path = $array_tag['file'];
		$this->num = $array_tag['pos'];
		$name = explode(' ', $array_tag['tag']);
		$this->name = trim($name[1]);
		
		if($name[2] == "extends")$this->subClassOf = $name[3];
	}
	
	public function add_function($array_tag ,  xml_ns &$xml_model)
	{
		$this->functionList[count($this->functionList)] = new Obj_Function($array_tag,$xml_model);
	}
	
	private function create_connection( xml_ns &$xml_model)
	{
		
	}
	
	public function create_rdf_entry( xml_ns &$xml_model)
	{
		
		$attrib = array('rdf:ID' => $this->name , 'pedl:name' => $this->name);
		$xml_model->tag_open($this, "PhpClass", $attrib);
		
		//echo  $xml_model->cur_node();
		//$xml_model->create_Ns_Node("PhpClass");
		//$xml_model->set_node_attrib('rdf:ID',trim($this->name));
		//$xml_model->set_node_attrib('pedl:name',trim($this->name));
		if(!is_Null($this->subClassOf))
		{
			$attrib = array('rdf:resource' => trim($this->subClassOf));
			$xml_model->tag_open($this, "rdfs:subClassOf", $attrib);
			$xml_model->tag_close($this, "rdfs:subClassOf");
			
		//$xml_model->create_Ns_Node("rdfs:subClassOf");
		//$xml_model->set_node_attrib('rdf:resource',trim($this->subClassOf));
		//$xml_model->parent_node();
		}
		
			$attrib = array('pedl:src' => $this->php_path);
			$xml_model->tag_open($this, "pedl:hasCodeResource", $attrib);
			$xml_model->tag_close($this, "pedl:hasCodeResource");
		
		//$xml_model->create_Ns_Node("pedl:hasCodeResource");
		//$xml_model->set_node_attrib('pedl:src',$this->php_path);
		//$xml_model->parent_node();
		
		$attrib = array();
		//$xml_model->tag_open($this, "pedl:hasFunktions", $attrib);
		
			$attrib = array();
			//$xml_model->tag_open($this, "pedl:Funktions", $attrib);
			
		
			foreach( $this->functionList as $value)
			{
				$prim = &$value->create_rdf_entry($xml_model,$this->name);
				if($prim) $this->constructor = &$prim;
			}
			
			//$xml_model->tag_close($this, "pedl:Funktions");
			
		//$xml_model->tag_close($this, "pedl:hasFunktions");
			
		$xml_model->tag_close($this, "PhpClass");
	}
}

class Obj_Function 
{
	
	private $name;
	private $php_path;
	private $num;
	private $gives_out_ref;
	private $parameterList = array();
	private $parser;
	
	public function __construct($array_tag, &$parser)
	{

		//saves standardinput
		$this->php_path = $array_tag['file'];
		$this->num = $array_tag['pos'];
		$this->parser = &$parser;
		//gets name
		$name = substr( $array_tag['tag'] , 
			$posme = (stripos($array_tag['tag'],'function') + 8),
			stripos($array_tag['tag'],'(') - $posme) . "\n";
		
		//gets ref
		$this->gives_out_ref = !(false === ($posAmp = stripos($name,'&')));
		
		//saves name
		$this->name = substr($name, $posAmp + 1);
		
		
		//gets parameter for function
		$counter = 0;
		if(!(false === ($pos1 = stripos($array_tag['tag'],'('))))
			{				
				
				if(!(false === ($pos2 = stripos($array_tag['tag'],')',$pos1))))
				{
					if(strlen(trim($tmp = substr($array_tag['tag'],$pos1 + 1, $pos2 - ($pos1 + 1) ))) > 1)
					{
					
					
					$tmp = explode(',', $tmp);
					$parameter_Obj = null;					

					foreach($tmp as $myval)
						{
						$mycomment = '';
						$myparam = $myval;

							if(!(false === ($comment = stripos($myval,'/*'))))
							{

							$comment2 = stripos($myval,'*/',$comment);
							$mycomment = substr($myval,$comment + 2 , $comment2 - ($comment + 2) );
							$myparam = substr($myval,0 ,$comment  ) .
 							substr($myval,$comment2 + 2 );

							}
 							$parameter_Obj = new Obj_Parameter(trim($myparam),$counter++);
							$parameter_Obj->setPresetValues($mycomment,$this->parser);
							$this->parameterList[count($this->parameterList)] = &$parameter_Obj;
							unset($parameter_Obj);
						}
					
					}
				}

			}
		
	}

	public function &create_rdf_entry( xml_ns &$xml_model, $name)
	{
		//echo  $xml_model->cur_node();
		$attrib = array('rdf:ID' => $name  . '.' . trim($this->name),'pedl:name' => trim($this->name));
		
		if( $name == trim($this->name) || '__construct' == trim($this->name) )
		{
			$xml_model->tag_open($this, "PhpConstructor", $attrib);
			//$xml_model->create_Ns_Node("PhpConstructor");
			//$res = &$xml_model->show_xmlelement() ;
		}
		else
		{
			$xml_model->tag_open($this, "PhpMethod", $attrib);
			//$xml_model->create_Ns_Node("PhpMethod");
		}
		
		if(count($this->parameterList) > 0)
		{		
		$attrib = array();
		//$xml_model->tag_open($this, "pedl:hasParameter", $attrib);
		
			$attrib = array();
			//$xml_model->tag_open($this, "pedl:ParameterCollection", $attrib);	
		}	
			
		//$xml_model->set_node_attrib('rdf:ID',$name  . '.' . trim($this->name));
		//$xml_model->set_node_attrib('pedl:name',trim($this->name));
				
			foreach( $this->parameterList as $value)
			{
				$value->create_rdf_entry($xml_model,$name,trim($this->name));
			}
			$res = null;
			if( $name == trim($this->name))$res = &$xml_model->show_xmlelement() ;
		
		if(count($this->parameterList) > 0)
		{		
		
			//$xml_model->tag_close($this, "pedl:ParameterCollection");
		//$xml_model->tag_close($this, "pedl:hasParameter");
		}	
		$xml_model->tag_close($this, "PhpMethod");
			//$xml_model->parent_node();
		
		return $res;
	}
	
}

class Obj_Parameter
{
	private $type;
	private $name;
	private $num;
	private $gives_out_ref = false;
	private $has_value = false;
	private $pre_value = null; 
	
	public function __construct($string_param,$counter)
	{
	
		$this->gives_out_ref = !(false === stripos($string_param,'&'));

		$param = explode(' ',$string_param);
		
		$name = '';
		
		if(count($param) == 1)$name = trim($param[0]);
		if(count($param) == 2)
			{
			$name = trim($param[1]);
			$this->type = trim($param[0]);
			}
		if(count($param) > 2)
			{
				//$this->type = $param[0];
				for($i = 0; $i < count($param);$i++)
				{
				
					if(strlen($param[$i]) > 1)
					{
						$name = trim($param[$i]);
						break;
					}
				}
			}
		if(count($param) > 3)
			{
				$this->type = $param[0];
				for($i = 1; $i < count($param);$i++)
				{
					if(strlen($param[$i]) > 0)
					{
						$name = trim($param[$i]);
						break;
					}
				}
			}
		
		
		$this->name  = substr($name, stripos($string_param,'$') + 1 );
		
		
		
		
	}
	
	public function setPresetValues($preSet,&$refParser)
	{
		$res = trim($preSet);
		$this->has_value = (strlen($res) > 0);
		//echo get_Class($refParser) . ': ';
		
		//echo $refParser->cur_idx();
		if($this->has_value && $refParser)
		{
		$stamp = $refParser->position_stamp();
		$URI = '@registry_surface_system#' . $res;
			if($refParser->seek_node($URI,null,null))
			{
				//echo "gefunden";
				$this->pre_value = &$refParser->show_xmlelement()->getdata(0);
				
			}
			else
			{
				echo "nicht gefunden: $URI";

			}
		//$refParser->go_to_stamp($stamp);
		//$refParser->flash_result();
		}
		
	}
	
	public function create_rdf_entry( xml &$xml_model, $class_name, $function_name)
	{
		//echo  $xml_model->cur_node();
		$attrib = array('rdf:ID' => trim($class_name . '.'  . $function_name . '.' . $this->name),'pedl:name' => trim($this->name));
		$xml_model->tag_open($this, "PhpParameter", $attrib);
		if($this->has_value && !$this->gives_out_ref)$xml_model->cdata($this,$this->pre_value);
		if($this->has_value && $this->gives_out_ref)$xml_model->cdata_ref($this,$this->pre_value);

		$xml_model->tag_close($this, "PhpParameter");
		//$xml_model->create_Ns_Node("PhpParameter");
		//$xml_model->set_node_attrib('rdf:ID',trim($class_name . '.'  . $function_name . '.' . $this->name));
		//$xml_model->set_node_attrib('pedl:name',trim($this->name));
		//
		//$xml_model->parent_node();
	}
}

?>
