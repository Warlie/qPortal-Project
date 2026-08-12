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
	
	function parse_document($source)
	{
		global $logger_class;
		$corr_xml_file = str_replace(".php", ".pedl", $this->attribute_values['URI']);
		
		$ws = null; //TODO option for external ns
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

		/* PEDL_FORCE_REBUILD ignores an existing .pedl even when its stored hash still
		*  matches, so a changed generator does not keep handing out the old result.
		*/
		$force_rebuild = defined('PEDL_FORCE_REBUILD') && PEDL_FORCE_REBUILD;

		if($force_rebuild && is_file($corr_xml_file))
			$logger_class->setAssert("INFO forced rebuild of " . $corr_xml_file, 0);

		if(!$force_rebuild && is_file($corr_xml_file))
		{

			if($this->use_PEDL_file($this->base_object, $corr_xml_file))
			{
				$this->base_object->use_ns_def_strict(false);
						//echo $this->base_object->index_consistence();
				$this->base_object->go_to_stamp($xmlPos);
				return;
			}
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
			
			/* Answers the old TODO on this spot - a syntax tree instead of substring
			*  matching. The scanner that used to run here turned prose and commented out
			*  code into registry nodes ("class " inside a sentence, //public function ..)
			*  and split one parameter into two whenever a default value held a comma.
			*  The entry shape is unchanged, so everything below this line stays as it was.
			*
			*  Includes are no longer followed: classes from an included file are already
			*  registered and were dropped by the void list anyway - every generated .pedl
			*  holds exactly one PhpClass.
			*
			*  A source PHP itself rejects is reported and left alone; writing half a .pedl
			*  for a file that cannot even be loaded helps nobody.
			*/
			require_once(__DIR__ . '/PHP_ast_scan.php');

			try
			{
				$result = PHP_Ast_Scan::scan($str_source, $this->attribute_values['URI']);
			}
			catch (\PhpParser\Error $e)
			{
				$logger_class->setAssert("ERROR " . $this->attribute_values['URI']
					. " is not parsable, no pedl file written: " . $e->getMessage(), 0);

				$this->base_object->use_ns_def_strict(false);
				$this->base_object->go_to_stamp($xmlPos);
				return;
			}
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
	
	
	/**
	If referenced file has the same hash as mentioned, it will be cloned to the parsers position
	Sideeffect it mentioned a missmatch to the log
	@param xmlparser with position
	@param a path of a file
	@return boolean
	*/
	private function use_PEDL_file( $parser, string $xml_file)
	{
		global $logger_class;
		$myPrivateModel = new xml_xPath_sParqle();
		 $myPrivateModel ->load($xml_file, false);
		 $myPrivateModel->set_first_node();
		 $parser->set_first_node();
		
		 	$code_resources = $myPrivateModel->array_Of_Objects_Related_To_Tag_Name('http://www.w3.org/2006/05/pedl-lib#hasCodeResource');
		 	if(count($code_resources) === 0) return false;

		 	foreach($code_resources as $source)
		 	{
		 		if(false !== ($hash = $source->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#hash')))
		 		{
		 			if(false !== ($src = $source->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#src')))
		 			{
		 				if(hash_file('sha256', $src) != $hash)
		 				{
		 					$logger_class->setAssert("INFO " . $src . " has no or an altered pedl file.", 0);
		 					return false;
		 				}
		 			}
		 			else
		 				return false;

		 		}
		 		else
		 		{
		 			return false;
		 		}
		 	}
		 
		 /* walk through seeAlso entries for preload relevant data */
		 	foreach($myPrivateModel->array_Of_Objects_Related_To_Tag_Name('http://www.w3.org/2000/01/rdf-schema#seeAlso') as $seeAlso)
		 		if(false !== ($data = $seeAlso->get_ns_attribute('http://www.w3.org/2006/05/pedl-lib#src')) && 
		 			file_exists($data))
		 				if(!$this->use_PEDL_file( $parser, $data))return false;

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

		return true;
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
				/* An entry produced from the syntax tree says what it is. Entries without
				*  that information are still recognised by their text, so a plain
				*  File_Scan result keeps working here.
				*/
				$kind = $value['meta']['kind'] ?? null;

				if(is_null($kind))
					$kind = (false === stripos($value['tag'],'class '))
						? ((false === stripos($value['tag'],'function ')) ? null : 'method')
						: 'class';

				if('class' === $kind || 'interface' === $kind || 'trait' === $kind)
				{
					$this->cur_node = null;

					$this->cur_node = new Obj_Class( $value, $this->list_of_resources );
					if(!in_array($this->cur_node->get_name(), $void_list))
					{
						$this->collection_Array[] = $this->cur_node;
						$name_to_path_list[$this->cur_node->get_name()] = $this->cur_node->get_Path_URL();
					}

					continue;
				}

				if(is_null($kind))continue;

				if(!is_Object($this->cur_node))
				{
					//a member without a class before it cannot be placed
					echo "Error occurs on entry" . $value['tag'] . "" ;
					continue;
				}

				if('method' === $kind)   $this->cur_node->add_function($value,$xml_model);
				if('property' === $kind) $this->cur_node->add_member($value, 'PhpProperty');
				if('constant' === $kind) $this->cur_node->add_member($value, 'PhpConstant');
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
	private $memberList = array();
	private $my_list_of_resources = [];

	/* class | interface | trait - decides which registry tag is written */
	private $kind = 'class';
	private $implements = [];
	private $isAbstract = false;
	private $isFinal = false;

	public function __construct($array_tag, &$list_of_resources)
	{
		$this->my_list_of_resources = &$list_of_resources;
		$this->php_path = $array_tag['file'];
		$this->xml_path = str_replace(".php", ".pedl", $this->php_path);
		$this->num = $array_tag['pos'];

		if(isset($array_tag['meta']))
		{
			$meta = $array_tag['meta'];
			$this->kind       = $meta['kind'];
			$this->name       = $meta['name'];
			$this->subClassOf = ('' === $meta['extends']) ? null : $meta['extends'];
			$this->implements = $meta['implements'];
			$this->isAbstract = $meta['abstract'];
			$this->isFinal    = $meta['final'];
		}
		else
		{
			//entry without structural information: read it off the text, as before
			$name = explode(' ', $array_tag['tag']);
			$this->name = trim($name[1]);

			if(isset($name[2]) && $name[2] == "extends")$this->subClassOf = $name[3];
		}

		$this->my_list_of_resources[$this->name] = $this->xml_path;
	}

	public function add_function($array_tag ,  xml_ns &$xml_model)
	{
		$this->functionList[count($this->functionList)] = new Obj_Function($array_tag,$xml_model);
	}

	/* properties and class constants; both are named slots and differ only in the tag */
	public function add_member($array_tag, string $tagname)
	{
		$this->memberList[] = new Obj_Member($array_tag, $tagname);
	}

	/* PhpClass | PhpInterface | PhpTrait */
	private function registry_tag() : string
	{
		if('interface' === $this->kind)return 'PhpInterface';
		if('trait' === $this->kind)    return 'PhpTrait';

		return 'PhpClass';
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


		$tagname = $this->registry_tag();

		$attrib = array('rdf:ID' => $this->name , 'pedl:name' => $this->name);

		if($this->isAbstract)$attrib['pedl:abstract'] = 'true';
		if($this->isFinal)   $attrib['pedl:final']    = 'true';

		$xml_model->tag_open($this, $tagname, $attrib);

		/* Implemented interfaces sit next to the inheritance edge. Both are answerable
		*  from the tree afterwards, which is what makes "is a multisource plugin" a
		*  question to the registry rather than to the source text.
		*/
		foreach($this->implements as $interface)
		{
			$attrib = array('rdf:resource' => trim($interface));
			$xml_model->tag_open($this, "pedl:implements", $attrib);
			$xml_model->tag_close($this, "pedl:implements");
		}

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
			$attrib = array('pedl:src' => $this->php_path, 'pedl:hash'=>hash_file('sha256', $this->php_path));
			$xml_model->tag_open($this, "pedl:hasCodeResource", $attrib);
			$xml_model->tag_close($this, "pedl:hasCodeResource");
		

		
		$attrib = array();

		
			$attrib = array();

			
		
			foreach( $this->memberList as $value)
				$value->create_rdf_entry($xml_model, $this->name);

			foreach( $this->functionList as $value)
			{

				$prim = &$value->create_rdf_entry($xml_model,$this->name);
				if($prim) $this->constructor = &$prim;

			}

		$xml_model->tag_close($this, $tagname);
		

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
	
	/* filled from the syntax tree; stays at its default for a plain File_Scan entry */
	private $visibility = '';
	private $isStatic = false;
	private $isAbstract = false;
	private $isFinal = false;
	private $returnType = '';
	private $isConstructor = null;

	public function __construct($array_tag, &$parser)
	{

		//saves standardinput
		$this->php_path = $array_tag['file'];
		$this->num = $array_tag['pos'];
		$this->parser = &$parser;

		if(isset($array_tag['meta']))
		{
			$meta = $array_tag['meta'];

			$this->name          = $meta['name'];
			$this->gives_out_ref = $meta['byRef'];
			$this->visibility    = $meta['visibility'];
			$this->isStatic      = $meta['static'];
			$this->isAbstract    = $meta['abstract'];
			$this->isFinal       = $meta['final'];
			$this->returnType    = $meta['returnType'];
			$this->isConstructor = $meta['constructor'];

			$counter = 0;
			foreach($meta['params'] as $param)
				$this->parameterList[] = new Obj_Parameter($param, $counter++);

			return;
		}

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

		/* "implicit" records that the source names no visibility at all - php treats it as
		*  public, but the distinction is what tells an old plugin from a maintained one.
		*/
		if('' !== $this->visibility)$attrib['pedl:visibility'] = $this->visibility;
		if($this->isStatic)         $attrib['pedl:static']     = 'true';
		if($this->isAbstract)       $attrib['pedl:abstract']   = 'true';
		if($this->isFinal)          $attrib['pedl:final']      = 'true';
		if($this->gives_out_ref)    $attrib['pedl:byRef']      = 'true';
		if('' !== $this->returnType)$attrib['pedl:returns']    = $this->returnType;

		$is_constructor = is_null($this->isConstructor)
			? ( $name == trim($this->name) || '__construct' == trim($this->name) )
			: $this->isConstructor;

		$tagname = $is_constructor ? "PhpConstructor" : "PhpMethod";

		$xml_model->tag_open($this, $tagname, $attrib);

			foreach( $this->parameterList as $value)
			{
				$value->create_rdf_entry($xml_model,$name,trim($this->name));
			}
			$res = null;
			if( $name == trim($this->name))$res = &$xml_model->show_xmlelement() ;

		/* closed under the name it was opened with; tag_close ignores the name, but a
		*  PhpConstructor that closes as PhpMethod is a trap waiting for the day it does not
		*/
		$xml_model->tag_close($this, $tagname);
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
	
	/* filled from the syntax tree; empty for a parameter read off a text line */
	private $decl_type = '';
	private $is_variadic = false;
	private $is_nullable = false;
	private $default_value = null;

	public function __construct($string_param,$counter)
	{
		$this->num = $counter;

		/* the syntax tree hands over a described parameter instead of a text fragment */
		if(is_array($string_param))
		{
			$this->name          = $string_param['name'];
			$this->decl_type     = $string_param['type'];
			$this->is_nullable   = $string_param['nullable'];
			$this->gives_out_ref = $string_param['byRef'];
			$this->is_variadic   = $string_param['variadic'];
			$this->default_value = $string_param['default'];

			$this->value_content = $string_param['refersTo'];
			$this->has_value     = ('' !== $string_param['refersTo']);

			return;
		}

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

		if('' !== $this->decl_type)      $attrib['pedl:type']     = $this->decl_type;
		if($this->is_nullable)           $attrib['pedl:nullable'] = 'true';
		if($this->gives_out_ref)         $attrib['pedl:byRef']    = 'true';
		if($this->is_variadic)           $attrib['pedl:variadic'] = 'true';

		//a default value is php source and may hold quotes; all_attrib_axo escapes them
		if(!is_null($this->default_value))$attrib['pedl:default'] = $this->default_value;

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

/** A named slot on a class: a property or a class constant.
*
*   Both are the same shape - a name, a visibility, and something optional attached - and
*   differ only in the registry tag they are written as, so one class serves both.
*   Only reachable from an entry that carries structural information; a text line never
*   produced properties or constants at all.
*/
class Obj_Member
{
	private $tagname;
	private $name;
	private $visibility;
	private $isStatic = false;
	private $isReadonly = false;
	private $decl_type = '';
	private $value = null;

	public function __construct($array_tag, string $tagname)
	{
		$meta = $array_tag['meta'];

		$this->tagname    = $tagname;
		$this->name       = $meta['name'];
		$this->visibility = $meta['visibility'];

		if('PhpProperty' === $tagname)
		{
			$this->isStatic   = $meta['static'];
			$this->isReadonly = $meta['readonly'];
			$this->decl_type  = $meta['type'];
			$this->value      = $meta['default'];
		}
		else
		{
			$this->value = $meta['value'];
		}
	}

	public function get_name(){return $this->name;}

	public function create_rdf_entry( xml_ns &$xml_model, $class_name)
	{
		$attrib = array('rdf:ID' => trim($class_name . '.' . $this->name), 'pedl:name' => $this->name);

		if('' !== $this->visibility)      $attrib['pedl:visibility'] = $this->visibility;
		if($this->isStatic)               $attrib['pedl:static']     = 'true';
		if($this->isReadonly)             $attrib['pedl:readonly']   = 'true';
		if('' !== $this->decl_type)       $attrib['pedl:type']       = $this->decl_type;
		if(!is_null($this->value))        $attrib['pedl:default']    = $this->value;

		$xml_model->tag_open($this, $this->tagname, $attrib);
		$xml_model->tag_close($this, $this->tagname);
	}
}

?>
