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
	
		$corr_xml_file = str_replace(".php", ".pedl", $this->attribute_values['URI']);
		
		$ws = null;
		if(!$ws)$ws = "surface_tree_engine";
		
		$this->base_object->set_first_node();
		
		$xmlPos = $this->base_object->position_stamp();
		$this->base_object->go_to_stamp(
					$this->base_object->getControlUnit( $ws)->getPositionStampReg()
				);		

		
		$list = [];

		foreach($this->base_object->array_Of_Objects_Related_To_Tag_Name('@registry_surface_system#PhpClass') as $value)
			if($name = $value->get_ns_attribute("http://www.w3.org/2006/05/pedl-lib#name"))
				$list[] =$name;

		if(is_file($corr_xml_file))
		{

			$this->use_PEDL_file($this->base_object, $corr_xml_file);
			$this->base_object->use_ns_def_strict(false);
						//echo $this->base_object->index_consistence();
			$this->base_object->go_to_stamp($xmlPos);
			return;
		}


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
			
			// TODO Reflection could do this job better because of it's allways up to date parser
			$filescanner = new File_Scan();
			//$mtime = hrtime(true);
			$filescanner->insert_str($str_source, $this->attribute_values['URI']);
			//$filescanner->add_path('/');
			$filescanner->add_tag('class ');
			$filescanner->add_tag('function ');
			$filescanner->switch_cross_seek(array('include("','")'));
			$filescanner->switch_cross_seek(array('require("','")'));
			$filescanner->switch_cross_seek(array('require_once("','")'));
			$filescanner->seeking();
			$result = $filescanner->result();
			//echo hrtime(true) -$mtime  . "\n";
			/*
			foreach( $result as $value)
			{
				echo $value['tag'] . "\n";
			}
			*/			
			

			//var_dump($result );

			
			/*
			*	This class builds up a xml image of a php document's structure 
			*/
			$obj_class = new Obj_Class_Collection($result,$this->base_object, $list); //finds pre defined values
			
			$this->base_object->use_ns_def_strict(true);



			//here we create our entries in our registry
			$obj_class->create_rdf_entry($this->base_object);

			$this->create_new_PEDL_file($obj_class, $corr_xml_file);

		//$myPrivateModel->save_file("UTF-8",false, $corr_xml_file);
		//var_dump($handle->save_back("UTF-8"));
			
		$handle = &My_Handle_factory::handle_factory('XML');
		$handle->set_object($this->base_object);
		$handle->set_attribute('XML_OPTION_CASE_FOLDING',false);
		//var_dump($handle->save_back("UTF-8"));
		 //unset($handle);

		
			$this->base_object->use_ns_def_strict(false);
						//echo $this->base_object->index_consistence();
			$this->base_object->go_to_stamp($xmlPos);
			// $this->base_object->show_index();

	}
	

	private function create_new_PEDL_file(Obj_Class_Collection $obj_class, string $xml_file)
	{ 
		foreach ($obj_class as  $pedl_class) {

			//unset($myPrivateModel);
		$myPrivateModel = new xml_xPath_sParqle();
		$myPrivateModel->setNewTree('@registry_surface_system');
		$myPrivateModel->set_definition_context('TYPE','XML');
		$myPrivateModel->set_definition_context('MIME','<?xml version="1.0" encoding="UTF-8"?>');
		$myPrivateModel->set_definition_context('DOC','');
			
			
		$namespace = array();
		$namespace['xmlns'] = '@registry_surface_system';
		$namespace['xmlns:owl'] = 'http://www.w3.org/2002/07/owl';
		$namespace['xmlns:rdf'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';
		$namespace['xmlns:rdfs'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:xsd'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:pedl'] = 'http://www.w3.org/2006/05/pedl-lib';
		
		//echo get_Class($this->my_Xml_Object);
	
		
		$myPrivateModel->createTree('@registry_surface_system','rdf:RDF', $namespace);
		$myPrivateModel->set_first_node();
		$pedl_class->create_rdf_entry($myPrivateModel);
		//unset($handle);
		$handle = &My_Handle_factory::handle_factory('XML');
		$handle->set_object($myPrivateModel);
		$handle->set_attribute('XML_OPTION_CASE_FOLDING',false);
		$corr_xml_file = str_replace(".php", ".pedl", $pedl_class->get_Path_URL());
		//var_dump($handle->save_back("UTF-8"));
		//echo $corr_xml_file . "\n";
		file_put_contents($corr_xml_file, $handle->save_back("UTF-8"));
		}
	}
	
	private function use_PEDL_file( $parser, string $xml_file)
	{
		$myPrivateModel = new xml_xPath_sParqle();
		 $myPrivateModel ->load($xml_file, false);
		 $myPrivateModel->set_first_node();
		 $parser->set_first_node();
		 		 
		 /* walk through seeAlso entries for preload relevant data */
		 	foreach($myPrivateModel->array_Of_Objects_Related_To_Tag_Name('http://www.w3.org/2000/01/rdf-schema#seeAlso') as $seeAlso)
		 		if(false !== ($data = $seeAlso->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#src')) && 
		 			file_exists($data))
		 				$this->use_PEDL_file( $parser, $data);

		 /* -------------------------------------------------------------------------- */
		 
		 $myBranches = $myPrivateModel->array_Of_Objects_Related_To_Tag_Name('@registry_surface_system#PhpClass') ;

		 if(count($myBranches)> 0 )
		 	 $myBranches[0]->cloning($parser->show_xmlelement());

		 /*
		 $handle = &My_Handle_factory::handle_factory('XML');
		$handle->set_object($myPrivateModel);
		$handle->set_attribute('XML_OPTION_CASE_FOLDING',false);
				var_dump($handle->save_back("UTF-8"));
		 unset($handle);
		$handle = &My_Handle_factory::handle_factory('XML');
		$handle->set_object($parser);
		$handle->set_attribute('XML_OPTION_CASE_FOLDING',false);

		var_dump($handle->save_back("UTF-8"));
		*/

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
		//  needs implementation
	}
	
	function save_stream_back(&$stream,$format, $send_header = false)
	{
		// needs Implementation
	}
		
function send_header()
{

	// needs implementation

}

}


/*

*/
class Obj_Class_Collection implements \IteratorAggregate, \Countable
{
	private $collection_Array = array(); //prints all lies als objects
	private $cur_node;
	private $list_of_resources = [];

	
	/**
	* Collects all entries to describe a plenty of classes
	* @param array of strings showing class and function lines
	* @param parser for injecting php document description into 
	*
	*/
	public function __construct( array $structure, xml_ns &$xml_model, array $void_list = []) 
	{

		$class_ref;
		

		
			foreach( $structure as $value)
			{
				
				if(!(false === stripos($value['tag'],'class ')))
				{
					//echo $value['tag'] . " as Class<br>\n";
					$this->cur_node = null;
					
					
					//$void_list
					
					$this->cur_node = new Obj_Class( $value, $this->list_of_resources );
					if(!in_array($this->cur_node->get_name(), $void_list))
					{
						$this->collection_Array[] = $this->cur_node;
						$name_to_path_list[$this->cur_node->get_name()] = $this->cur_node->get_Path_URL();
					}
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
	
	    /**
     * IteratorAggregate interface: return an iterator over the classes
     *
     * @return \ArrayIterator<Obj_Class>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->collection_Array);
    }

    /**
     * Countable interface: return number of classes collected
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->collection_Array);
    }

    /**
     * Convenience: filter classes by a callback
     *
     * @param callable(Obj_Class): bool $callback
     * @return self
     */
    public function filter(callable $callback): self
    {
        $filtered = array_filter($this->collection_Array, $callback);
        $clone = clone $this;
        $clone->collection_Array = array_values($filtered);
        return $clone;
    }

    /**
     * Convenience: map over classes
     *
     * @param callable(Obj_Class): mixed $callback
     * @return array
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->collection_Array);
    }

}


/**
*	
*/
class Obj_Class 
{
	private $name;
	private $php_path;
	private $xml_path;
	private $num;
	private $constructor = null;
	private $subClassOf = null;
	
	private $functionList = array();
	private $my_list_of_resources = [];
	
	public function __construct($array_tag, &$list_of_resources)
	{
		$this->my_list_of_resources = &$list_of_resources;
		$this->php_path = $array_tag['file'];
		$this->xml_path = str_replace(".php", ".pedl", $this->php_path);
		$this->num = $array_tag['pos'];
		$name = explode(' ', $array_tag['tag']);
		$this->name = trim($name[1]);
		
		
		
		$this->my_list_of_resources[$this->name] = $this->xml_path;
		
		if($name[2] == "extends")$this->subClassOf = $name[3];

	}
	
	public function add_function($array_tag ,  xml_ns &$xml_model)
	{
		$this->functionList[count($this->functionList)] = new Obj_Function($array_tag,$xml_model);
	}
	
	private function create_connection( xml_ns &$xml_model)
	{
		
	}
	
	public function get_Path_URL()
	{
		return $this->php_path;
	}
	
	public function get_name()
	{
		return  trim($this->name);
	}
	
	public function create_rdf_entry( xml_ns &$xml_model)
	{


		$attrib = array('rdf:ID' => $this->name , 'pedl:name' => $this->name);
		//var_dump($attrib);
		$xml_model->tag_open($this, "PhpClass", $attrib);


		if(!is_Null($this->subClassOf))
		{
			$attrib = array('rdf:resource' => trim($this->subClassOf));
			$xml_model->tag_open($this, "rdfs:subClassOf", $attrib);
			$xml_model->tag_close($this, "rdfs:subClassOf");
			
			// gives seeAlso for the 
			if(array_key_exists(trim($this->subClassOf), $this->my_list_of_resources))
			{
				$attrib = array('pedl:src' => $this->my_list_of_resources[trim($this->subClassOf)]);
				$xml_model->tag_open($this, "rdfs:seeAlso", $attrib);
				$xml_model->tag_close($this, "rdfs:seeAlso");
			}
			
			

		}
			$attrib = array('pedl:src' => $this->php_path);
			$xml_model->tag_open($this, "pedl:hasCodeResource", $attrib);
			$xml_model->tag_close($this, "pedl:hasCodeResource");
		

		
		$attrib = array();

		
			$attrib = array();

			
		
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
								/* Here comments will be collected */
								$comment2 = stripos($myval,'*/',$comment);
								$mycomment = substr($myval,$comment + 2 , $comment2 - ($comment + 2) );
								$myparam = substr($myval,0 ,$comment  ) .
								substr($myval,$comment2 + 2 );

							}
							//echo $mycomment . "\n";
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
	private $gives_out_ref = false; //way to decide how to use the Ref sign
	private $has_value = false;
	private $pre_value = null;
	private $value_content = "";
	
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
		$this->value_content = $res;
		//echo get_Class($refParser) . ': ';
		/*
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
		*/
	}
	
	public function create_rdf_entry( xml &$xml_model, $class_name, $function_name)
	{
		//echo  $xml_model->cur_node();
		$attrib = array('rdf:ID' => trim($class_name . '.'  . $function_name . '.' . $this->name),'pedl:name' => trim($this->name));
		if($this->has_value )$attrib['pedl:refersTo'] = $this->value_content ;
		$xml_model->tag_open($this, "PhpParameter", $attrib);
		//if($this->has_value && !$this->gives_out_ref)$xml_model->cdata($this,$this->pre_value);
		//if($this->has_value && $this->gives_out_ref)$xml_model->cdata_ref($this,$this->pre_value);


		$xml_model->tag_close($this, "PhpParameter");
		//$xml_model->create_Ns_Node("PhpParameter");
		//$xml_model->set_node_attrib('rdf:ID',trim($class_name . '.'  . $function_name . '.' . $this->name));
		//$xml_model->set_node_attrib('pedl:name',trim($this->name));
		//
		//$xml_model->parent_node();
	}
}

?>
