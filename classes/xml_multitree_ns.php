<?php

/**  Aufstellung der functionen des XML objektes
*    cur_node() :         aktuelle Position
*    list_child_node() :   gibt Array mit liste der nächsten knoten raus
*    index_child() :    gibt die Menge der kindknoten wieder
*    child_node(byte index) :        geht zum nächsten knoten
*    parent_node() :      geht zum übergeordneten knoten
*    show_pointer() : zeigt den wegzeiger
*    reset_pointer() : setzt den zeiger, der angibt, welchen weg man zuletzt gegangen ist, auf -1
*    mark_node([$bool]) : markiert einen Knoten und gib seinen zustand zurück
*    set_first_node() : geht zum obersten knoten
*    show_cur_attrib($attrib = null)
*    show_cur_data()
*    position_stamp() : Gibt eine Positionsmarke mit Hash-Kontrollziffer (Ziffer noch nicht funktionsfähig)
*    go_to_stamp(stamp) : Geht zur marke
*    show_xmlelement() : 
*
*
*    only_child_node($bool_node) : für seek_node -> sucht dann nur alles unter dem aktuellen knoten
*    seek_node([String $type],assoz array [attrib],string [data], int [pos]) : is looking for a list of nodes, offering in resultarray
*    get_result() : returns resultarray of last seeking
*    flash_result() :truncate resultarray
*
*    public function create_Ns_Node($prefix_Q_name, $stamp = null, array $attrib = null ) : creates a new node as an instance of a classobject
*    create_node(stamp,[pos=null]) : erstellt einen neuen knoten
*    set_node_name(name) : gibt einem Knoten einen Namen
*    set_node_attrib(key,value) : vergibt Attribute an einen Knoten
*    set_node_cdata(value,counter) : vergibt daten an einen Knoten
*
*    load(Dateiname) : läd xml.datei
*    load_Stream(String String) :       läd xml zeichenkette
*    save(Dateiname) : überschreibt Datei
*    save_Stream(format) : gibt String zurück
*
*
*    cur_idx() : Aktueller Index
*    change_idx($index)
*    change_URI($index)
*
*   set_node_obj($value)
*   show_cur_obj()
*
*   show_xmlelement()
*   set_xmlelement()
*   append_xmlelement($toAppend, $bool_clone)
*
*   get_Object_to_Namespace($fullns)
*   get_NS_Full()
*   get_NS_QName()
*   get_NS()
*   set_Object_to_Namespace($ns,&obj)
*   set_Namespace($ns)
*   public ticketEvent($eventOnFullNS , Interface_node &$listener)
*   use_ns_def_strict(boolean $strict)
*   get_ListenerList()
*/

require_once('exceptions/exception_collection.php');
require_once('xml_multitree_omni_handle.php');
//require_once('xml_multitree_ns_gen.php');
require_once('class_EventObject.php');
require_once('ns/class_index.php');
//require_once('exceptions/Not_defined_Namespace_Exception.php');
$inkrement = 0;
class xml_ns extends xml_omni 
{
	//ein objektarray wird geladen, sobald ein entsprechender NS angegeben wird
	var $namespace_frameworks = array();
	var $prefixes = array();
	private $prefixes_inv = array();
	private $cur_ns;
	var $obj_stack = array();
	var $loadmodus = 0;
	
	private $context_generator;
	private $has_new_node = false;
	private $use_def_strict = false;
	
	private $ticketlist = array();
	private $looking_index = array();
	private $result_nodes = array();
	
	private $exception_collection;
	

	function __construct( ContentGenerator $context = null)
	{
		$this->context_generator = &$context;
		$this->exception_collection = new ExceptionCollection($this);
	}
	
	public function &get_ExceptionManager(){return $this->exception_collection;}
	public function catchException(Exception &$exc){$this->exception_collection->catchException(exc);}
	
	public function get_ListenerList()
	{
	
		return $this->pointer[$this->idx]->listOfListeners_stamp('0000.');
	}
	
	public function get_Classstamp()
	{
	
		return $this->pointer[$this->idx]->Class_stamp('0000.');
	}

	public function get_Class_stamp()
	{
	//echo $this->pointer[$this->idx]->get_classes();
		return ''; //$this->pointer[$this->idx]->listOfClass_stamp('0000.');
	}
	
	public function &get_context_generator()
	{
		return $this->context_generator;
	}	

	private function has_got_new_undef_node()
	{
		
		return $this->has_new_node;
	}
	
	public function use_ns_def_strict( $strict)
	{
		$this->use_def_strict = $strict;
	}
	
	public function get_wayOut_Many()
	{
		
	}
	
	public function show_index()
	{
	echo '<h1>Full index</h1>';
		for($i = 0;$i < count($this->looking_index);$i++)
		{
		echo '<h2>' . $this->loaded_URI[$i] . '</h2>';
			foreach($this->looking_index[$i] as $key => $value)
			{
				echo '<h3 style="color:#0000FF" >' . $key . '</h3>';
				if(!is_Null($value))
					for($j = 0;$j < count($this->looking_index[$i][$key]);$j++)
					{
					echo '<b>' . $j . '</b><span style="color:#7fff00" >' . $this->looking_index[$i][$key][$j]->full_URI() . '"</span><br>'; 
					}
					else
				echo 'fehler';
			}
		}
		
	}
	
	
	/* */
	public function posOfPrev()
	{
		return $this->pointer[$this->idx]->posInPrev();
	} 
	
	   /* durchsucht den Baum nach Inhalten (keine direkte optimierung)*/
   	   public function seek_node( $type = null, array $attrib = null, array $data = null, $respos = 0)
	   {
		   //echo 'start' . $type . ' ' . count($this->looking_index[$this->idx][$type]) .  ' in ' . $this->idx .  "<br>\n";
		   $toAdd = count($this->result_nodes);

		   if(!is_null($type) && (false === strpos($type,'#')))echo "please use full URI for '" . $type . "'<br>\n";
		   
		   $arg = null;
			
		   //hashsuche
		   if(!is_null($type))
		   {

		   	//echo "many entries " . count($this->looking_index[$this->idx][$type]) . ",\n";
		   
			   if(($arg = &$this->looking_index[$this->idx][$type]) == null)
			   {
			  /* echo $type;
			   
		   	foreach($this->looking_index[$this->idx] as $key => $value )
		   	{
		   		for($k = 0;$k < count($this->looking_index[$this->idx][$key]);$k++)
		   		{ 
		   			echo "(" . $this->idx . "):" .  $this->looking_index[$this->idx][$key][$k]->full_URI() . "\n" ;
		   		}
		   	}*/
		   		
				   return false;
			   }

			   
		   
			   
		   }
		   else
		   {
		   $cur_num = 0;
		   	foreach($this->looking_index[$this->idx] as $key => $value )
		   	{
		   		for($k = 0;$k < count($this->looking_index[$this->idx][$key]);$k++)
		   		{ 
		   			$arg[$cur_num++] = &$this->looking_index[$this->idx][$key][$k];
		   		}
		   	}
			   
			   
			  if(!$arg)echo "und raus";
			   if(!$arg)return false;

		   
		   }

		    $check = true;
		    $value = null; 
//echo $type . ' ' . count($arg) . '' . "<br>\n";
		   //-----------------------
		   //echo $type . ' ' . count($arg) . "<br>\n";
		   for($i = 0; count($arg) > $i ; $i++)
		   {
		   	 
			if(!is_null($attrib))
				foreach($attrib as $att_key => $att_value )
				   {
				   	   //echo '"' . $att_key . '"=>"' . $att_value;
				   	   if(is_string($arg[$i]) )
				   	   {
				   	   	   echo "invalid string element found for: $type (" . $this->idx . ":$i) }\n";
				   	   	   return false;
				   	   }
					   //echo '" == "' . $arg[$i]->get_ns_attribute($att_key) . '" in "' . $arg[$i]->full_URI() . "\";<br>\n"; 
					   //$arg[$i]->get_ns_attribute($att_key);

						   $check = ($value = $arg[$i]->get_ns_attribute($att_key));
						   
						   //if($check) echo "attribname taken <br>\n";
						   
						   if(!is_Null($att_value))
						   {
							   $check = $check && ($value == $att_value);
						   }
					   //if($check) echo "attribvalue taken complete <br>\n";   
				   }
				  /* echo "element to check " . $arg[$i]->full_URI() . " <br>\n"; 
				   
				   echo $type . ' ' . count($arg) . '';
				   if($check)
					   echo 'true';
				   else
				   	echo 'false';
					   
					   echo  '' . "<br>\n";
				*/	   
				
					   
				if($check)
				{
				//echo "element saved " . $arg[$i]->full_URI() . " <br>\n";  
				$this->result_nodes[count($this->result_nodes)] = &$arg[$i];
				}
				    
				$check = true;   
		   }
		   

		   		
		   
			    if(count($this->result_nodes) > ($respos + $toAdd ))
			    {
				    //echo $this->result_nodes[$respos + $toAdd]->full_URI() . " xxx<br>\n";
				    $this->pointer[$this->idx] = &$this->result_nodes[($respos + $toAdd )];
			    }
			    //echo "hier " . count($this->result_nodes) . " " ;
		   return (count($this->result_nodes) > 0);
	

	   }
	   
	   public function &get_result()
	   {
		   return $this->result_nodes;
	   }
	   
	   public function flash_result()
	   {
		   $this->result_nodes = array();
	   }
		
	
function delete_index($index)
   {
	   //echo ':' . $this->mirror[$index]->name . 'ist hier bei ' . $index; 
	   unset($this->mirror[$index]);
	   $this->mirror[$index] = null;
	   //echo ':' . $this->mirror[$index]->name . 'ist hier bei ' . $index; 
	   
	   if($this->cur_pointer[$index])
	   $logger_class->setAssert($this->cur_pointer[$index]->name . " will be deleted" ,3);
	   else
	   $logger_class->setAssert($index . " is not a valid index" ,3);
	   
	   unset($this->cur_pointer[$index]);
	   $this->cur_pointer[$index] = null;
	   $this->idx = $index;
   }
	
   function &getInstance($name,$attributes,$obj_arg = null)
   {
	global $logger_class;
	
	$logger_class->setAssert(' create node of type "' . $name . '"(xml_ns:getInstance)' ,2);
	
	//echo '-' . $name . "<br>\n";
 	if($obj_arg)
	return parent::getInstance(
   	$name,
	$attributes,
	$obj_arg);
	else
	{
		
		/*
		* an native nodes specific properties are its namespaces
		* all these properties are stored in attributes with xmlns at start of the key,
		* in a way of expanded namespaces sparate to a ':'.
		* all these namespaces will be collected to an multidimensional assoziativ array named 'namespace_frameworks.
		*
		*/
		$nativ = false;
	   //findet die namespaces und legt die objekte in einem array ab
	   if(is_array($attributes))
	   {
		   //starts collecting namespaces
		   foreach( $attributes as $key => $value)
		   {
		  
			if(!(false === stripos(substr($key,0,5),'xmlns')))
			{
				 
				$value = str_replace('#','',$value);
				
				$obj = My_NameSpace_factory::namespace_factory($value);
				
				if(is_object($obj) && !is_array($this->namespace_frameworks[$value]) )
				{

				/*
				echo $value . " " . get_Class($obj) . "\n";
				
				foreach($obj->get_nodes() as $key3 => $value3)
				{
				echo $key3 . ', ';
				}
				*/
				
				$this->namespace_frameworks[$value]['nativ'] = $obj->get_nativ();
				$this->namespace_frameworks[$value]['node'] = $obj->get_nodes();
				$this->namespace_frameworks[$value]['attrib'] = $obj->get_attrib();
				
				
				

				}
				
				//has to be checked
				//if($this->namespace_frameworks[$value])
				$nativ = true; //native knoten definieren einen Namensraum und beenden ihn auch
				
					/*
					* defines prefixes 
					* all namespaces should be saved in a seperate objects
					*/
					if(!(false === ($tmp = strpos($key,':'))))
					{

						     // echo $prefix . ':' . $postfix . "\n";
						$prefix = substr(strtolower($key),0,$tmp);
						$postfix = substr(strtolower($key),$tmp + 1);
				
					//echo "$key : $value und $prefix:$postfix (360 xml_multitree_ns) \n";
						
						if(is_array($this->prefixes[$postfix]))
						{
							$this->prefixes[$postfix][count($this->prefixes[$postfix])] = $value;
							
							$this->prefixes_inv[$value][$this->idx] = $postfix;
						}
						else
						{
						$this->prefixes[$postfix][0] = $value;
						
						$this->prefixes_inv[$value][$this->idx] = $postfix;
						}	
						
						
					}
					else
					{
						//$this->idx
						
						if(is_array($this->prefixes[$this->idx]))
						{

							//var_dump($this->prefixes);
							$this->prefixes[$this->idx][] = $value;
							
							$this->prefixes_inv[$value][$this->idx] = '';
						}
						else
						{
							$this->prefixes[$this->idx][0] = $value;
							
							$this->prefixes_inv[$value][$this->idx] = '';
						}
					}
				
			}
		   }
		   reset($attributes); //sets arraypointer to start
	   } 
	   
	   $node;
	   $null = null;
	   
	   
	   /*
	   *
	   */
	   
	if(!(false === ($tmp = strpos($name,':'))))
	{

		$prefix = substr($name,0,$tmp);
		$nodename = substr($name,$tmp + 1);
		
		//prefixes are in pair to full namespaces
		$full_ns = $this->prefixes[$prefix][count($this->prefixes[$prefix]) - 1];
		
		//
		if($nativ)
		{
			//wenn der Knoten nicht existiert, gibt es einen Standardknoten
			if($this->namespace_frameworks[$full_ns]['nativ'])
			{
				$node = $this->namespace_frameworks[$full_ns]['nativ']->new_Instance();
				$node->name = $name;
				$node->type = $nodename;
				$node->set_idx($this->idx);
				$node->namespace = $full_ns;
				$node->set_parser($this);
				//echo "nativ: " . $node->full_URI() . " \n";
			}
			else
			{
				/*
				$this->namespace_frameworks[$full_ns]['nativ'] = My_NameSpace_factory::alt_namespace_factory();
				$node = My_NameSpace_factory::alt_namespace_factory();
				
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $full_ns;
				$node->set_parser($this);
				*/
				if($this->use_def_strict)throw new ErrorException('actual namespace for "' .$full_ns . '#" for natives is not defined.', 433, 75);
				echo "fehler xml_multitree_ns.php Zeile 272, $full_ns nicht gefunden!";
			}
		}
		else
		{	
			//creates a namespaceentry
			if($this->namespace_frameworks[$full_ns]['node'][$nodename])
			{
			
				//echo $full_ns . " gefunden $nodename<br>" . "\n" ;
				$node = $this->namespace_frameworks[$full_ns]['node'][$nodename]->new_Instance();
				
				$this->has_new_node = false;
				
			}
			else
			{
				/*
				if($this->use_def_strict)
				{
					$this->test_consistence();
					throw new ErrorException('actual namespace for "' .$full_ns . '#' . $nodename . '" for a node is not defined for "' . $name . '" in prefix sector.', 255, 75);
				}
				*/
				//echo $full_ns . " nicht gefunden $nodename<br>" . "\n";
				$this->namespace_frameworks[$full_ns]['node'][$nodename] = My_NameSpace_factory::alt_namespace_factory();
				$this->has_new_node = true;
				$node = $this->namespace_frameworks[$full_ns]['node'][$nodename]->new_Instance();

			}
			
			//creates a spezific node
			$node->name = $name;
			$node->type = $nodename;
			$node->set_idx($this->idx);
			$node->namespace = $full_ns;
			$node->set_parser($this);
			//echo "nicht nativ: " . $node->full_URI() . " \n";
		}	
			
	}
	else
	{
		
		if($this->prefixes[$this->idx])
			$glob_namespace = $this->prefixes[$this->idx][count($this->prefixes[$this->idx]) - 1];
		else
			$glob_namespace = '';

		$prefix = null;
		$nodename = substr($name,$tmp);

		
		if($nativ)
		{
			
			//wenn der Knoten nicht existiert, gibt es einen Standardknoten
			if($this->namespace_frameworks[$glob_namespace]['nativ'])
			{
				//echo " gefunden in main $nodename<br>" . "\n" ;
				$node = $this->namespace_frameworks[$glob_namespace]['nativ']->new_Instance();
				$node->name = $name;
				$node->type = $nodename;
				$node->set_idx($this->idx);
				$node->namespace = $glob_namespace;
				$node->set_parser($this);
				//echo "nativ: " . $node->full_URI() . " (2)\n";
			}
			else
			{	
				if($this->use_def_strict)throw new ErrorException('actual namespace for "' .$full_ns . '#" for natives is not defined.', 255, 75);
				echo "fehler xml_multitree_ns.php Zeile 328";
				/*
				//echo "nicht gefunden in main $nodename<br>" . "\n" ;
				$node = My_NameSpace_factory::alt_namespace_factory();
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $this->prefixes['_main'][count($this->prefixes['_main']) - 1];
				$node->set_parser($this);
				*/
			}
		}
		else
		{
			if($this->namespace_frameworks[$glob_namespace]['node'][$nodename])
			{
				//echo " gefunden in main $nodename<br>" . "\n" ;
				$node = $this->namespace_frameworks[$glob_namespace]['node'][$nodename]->new_Instance();
				$this->has_new_node = false;
			}
			else
			{
				/*
				if($this->use_def_strict)
				{
					$this->test_consistence();
								if(!$this->namespace_frameworks[$full_ns])
									echo 'Namespace not known!';
								else
								         echo 'Namespace known!';
					throw new ErrorException('actual namespace for "' .$full_ns . '#' . $nodename . '" for a node is not defined for "' . $name . '"', 255, 75);
				}
				*/
				$this->namespace_frameworks[$glob_namespace]['node'][$nodename] = My_NameSpace_factory::alt_namespace_factory();
				$this->has_new_node = true;
				$node = $this->namespace_frameworks[$glob_namespace]['node'][$nodename]->new_Instance();

			}
			
			$node->name = $name;
			$node->type = $nodename;
			$node->set_idx($this->idx);
			$node->namespace = $glob_namespace;
			$node->set_parser($this);
			//echo "nicht nativ: " . $node->full_URI() . " (2)\n";
		}
		
	}
	//echo $prefix . " : " . $nodename . "<br>\n";
	

		
	
	   
	   $asd = 0;
	   
	   /* attributes in specific node */
	   if($attributes)
	   if (count($attributes)) {
            foreach ($attributes as $k => $v) 
	    {
		   
		   if(!(false === ($tmp = strpos($k,'#'))))
		   {
		   	$prefix = substr($k,0,$tmp);
			$attribname = substr($k,$tmp + 1);
			$k = $this->get_Prefix($prefix) . ':' . $attribname;
			
		   }
		   
		    	if(!(false === ($tmp = strpos($k,':'))))
			{
				
					$prefix = substr($k,0,$tmp);
					$attribname = substr($k,$tmp + 1);
					
					//var_dump($this->prefixes, $prefix, $this->prefixes[$prefix]);
					if('xmlns' == $prefix)$prefix = 0;
					if('xml' == $prefix)$prefix = 0;
					
					if(!is_null($this->prefixes[$prefix]))
						$full_ns = $this->prefixes[$prefix][count($this->prefixes[$prefix]) - 1];
					else 
						$full_ns = $this->prefixes[$prefix][0];
					//echo "ns:$full_ns pre:$prefix attrib:$attribname ist ausgabe<br>\n";
					if($this->namespace_frameworks[$full_ns]['node'][$attribname])
					{
						$logger_class->setAssert("Attrib ($k) full_ns=\"$full_ns\" attribname=\"$attribname\" " ,3);
						$attrib = &$this->namespace_frameworks[$full_ns]['node'][$attribname]->new_Instance();

					}
					else
					{/*
						if($this->use_def_strict)
							{
								$this->test_consistence();
								if(!$this->namespace_frameworks[$full_ns])
									echo 'Namespace not known!';
								else
								         echo 'Namespace known!';
									 
								throw new ErrorException('actual namespace for "' .$full_ns . '#' . $attribname . '" for an attribute is not defined for "' . $k . '" in prefix sector.', 255, 75);
							} */
						$this->namespace_frameworks[$full_ns]['node'][$attribname] = &My_NameSpace_factory::alt_namespace_factory();
						$attrib = &$this->namespace_frameworks[$full_ns]['node'][$attribname]->new_Instance();
					}
					//	echo 	$k . "\n";
					$logger_class->setAssert("Attrib name=\"$k\" " ,3);
					$attrib->setdata($this->convert_from_XML($v),0);
					$attrib->set_NodeType(1);
					$attrib->name  = $k;
					$attrib->type = $attribname;
					$attrib->set_idx($this->idx);
					$attrib->namespace = $full_ns;
					$attrib->set_parser($this);
					$node->attribute($k,$attrib);
					unset($attrib);
					
			}
			else
			{
					
					$prefix = null;
					$attribname = $k;
					
					if($this->prefixes[$this->idx])
						$full_ns = $this->prefixes[$this->idx][count($this->prefixes[$this->idx]) - 1];
					else
						$full_ns = '';
					
					if($this->namespace_frameworks[$full_ns]['node'][$attribname])
					{
						$attrib = &$this->namespace_frameworks[$full_ns]['node'][$attribname]->new_Instance();

					}
					else
					{if($this->use_def_strict)
						{
							
							$this->test_consistence();
							
							throw new ErrorException('actual namespace for "' .$full_ns . '#' . $attribname . '" for an attribute is not defined for "' . $k . '".', 255, 75);
						}
						$this->namespace_frameworks[$full_ns]['node'][$attribname] = &My_NameSpace_factory::alt_namespace_factory();
						$attrib = &$this->namespace_frameworks[$full_ns]['node'][$attribname]->new_Instance();
						
					}
					//echo 	$k . "\n";
					$attrib->setdata($this->convert_from_XML($v),0);
					$attrib->name  = $k;
					$attrib->type = $attribname;
					$attrib->set_idx($this->idx);
					$attrib->namespace = $full_ns;
					$attrib->set_parser($this);
					$node->attribute($k,$attrib);
					unset($attrib);
			}

                    
	    }
	}

	$this->obj_stack[count($this->obj_stack)] = &$node;
	
	if(!$this->looking_index[$this->idx][$node->full_URI()])
		$this->looking_index[$this->idx][$node->full_URI()] = array();
	
	//echo "element:" . $node->full_URI() . "\n";
	$this->looking_index[$this->idx][$node->full_URI()][count($this->looking_index[$this->idx][$node->full_URI()])] = &$node;
	
	 if(!$node->get_parser())echo $node->full_URI() . " has no parser \n";
	
	return $node;
	/*
	return parent::getInstance(
   	$name,
	$attributes,
	$node);
	*/
	}
   }


   
   public function set_new_index(&$node)
   {
	   	if(!$this->looking_index[$node->get_idx()][$node->full_URI()])
		$this->looking_index[$node->get_idx()][$node->full_URI()] = array();
	
	
	$this->looking_index[$node->get_idx()][$node->full_URI()][count($this->looking_index[$node->get_idx()][$node->full_URI()])] = &$node;
   }
   

   
   function executed()
   {
	   
	   for($i = 0;count($this->obj_stack) > $i ; $i++ )
	   {
		   $no_context = null;
		   
		   $this->obj_stack[$i]->event('*?parse_complete',new EventObject('',$this,$no_context));
		   //echo $this->obj_stack[$i]->full_URI() . "<br>\n";
	   
	   }
	   
	   $this->obj_stack = array();
   
   
   }
   
   function tag_open($parser, $tag, $attributes, $pos = -1)
   {

	//echo "&lt;<font color=\"#00dd00\">$tag</font>&gt;\n";
	   
        if(!isset($this->mirror[$this->idx])){

                $num = 0;

                $this->mirror[$this->idx] = $this->getInstance($tag,$attributes);
 
                }else{

                        $num = $this->mirror[$this->idx]->index_max();

                        if(!isset($this->mirror[$this->idx]->next_el)){

                                $var = $this->getInstance($tag,$attributes);
                                $this->mirror[$this->idx]->setRefnext($var,$pos);
                                $this->cur_pointer[$this->idx] = &$this->mirror[$this->idx]->getRefnext($this->mirror[$this->idx]->index_max() - 1 ,true);
                                //schliesst cdata ab
                                //$this->cur_pointer[$this->idx]->final_data();
                                $this->cur_pointer[$this->idx]->setRefprev($this->mirror[$this->idx]);
                                
                                
                                
                                
                        }else{

                                $var = $this->getInstance($tag,$attributes);
                                $var->setRefprev($this->cur_pointer[$this->idx]);
                                $this->cur_pointer[$this->idx]->setRefnext($var,$pos);
                                //schliesst cdata ab
                                $this->cur_pointer[$this->idx]->final_data();
                                $this->cur_pointer[$this->idx] = &$this->cur_pointer[$this->idx]->getRefnext($this->cur_pointer[$this->idx]->index_max() - 1 ,true);
                                //$this->cur_pointer[$this->idx]->setRefprev(&$this->mirror[$this->idx]);
                                //$cur_pointer = &$this->mirror[$this->idx]->getRefnext();
                
                        }
                        
                        
 
           }
           

   }

   function cdata($parser, $cdata)
   {
   //echo "<b>$cdata</b>" . "\n";
     //if(trim($cdata) != '') echo trim($cdata) . "\n\n";                                      

   
                                        if(isset($this->cur_pointer[$this->idx])){
                                                $tmp = $this->cur_pointer[$this->idx]->index_max();
                                                
                                        //$this->cur_pointer[$this->idx]->setdata($this->convert_from_XML($cdata),$this->cur_pointer[$this->idx]->index_max());
                                        //echo $this->convert_from_XML($cdata);
					
					$res;
						if(is_Null($cdata))
						{
							$res = "";
						}
						else
						{
							$res = $cdata;
						}
					
					$expand_data = false;
					$text_type_name = '';
					
					if( $this->cur_pointer[$this->idx] instanceof RDF_Property)
					{
					if(is_Object($clazz = &$this->cur_pointer[$this->idx]->linkToClass()))
					{
					//definierendes_object
					if(is_Object($clazz2 = &$clazz->linkToClass()))
					{
					
					for($i = 0;$i < $clazz2->index_max();$i++)
					{
					
					if($clazz2->getRefnext($i)->full_URI() == 'http://www.w3.org/2000/01/rdf-schema#range' )
					{
					
					//$clazz2->getRefnext($i)->giveOutOverview();
					$resource = &$clazz2->getRefnext($i)->get_ns_attribute_obj('http://www.w3.org/1999/02/22-rdf-syntax-ns#resource');
					
						if( is_object( $resource  ))
						{
						//$resource->giveOutOverview();
						$resource_tag = &$resource->get_out_ref();
						if(is_array($resource_tag))
							{
							 
							if(count($resource_tag) == 1 )
							{
							if($resource_tag[0]->ManyInstance() == 1)
							{
								//$text_type_name = $resource_tag[0]->linkToInstance(0)->full_URI();
								
								$data = &$resource_tag[0]->linkToInstance(0);
								//echo $data->name  . ' ' . $data->type . ' ' . $data->namespace . ' ---------';
								
								$newobj = &$resource_tag[0]->linkToInstance(0)->new_Instance();
								$newobj->name = $data->name;
								$newobj->type = $data->type;
								$newobj->set_idx($this->idx);
								$newobj->namespace = $this->cur_pointer[$this->idx]->namespace;
								$newobj->set_parser($this);
								$newobj->setRefprev($this->cur_pointer[$this->idx]);
								$newobj->setdata($this->convert_from_XML($res),$tmp);
								//$newobj->giveOutOverview();
								
								$this->cur_pointer[$this->idx]->setdata($newobj,$tmp);

								$expand_data = true;
								
								
							}
							
							
							}
							
							}
						}
						
					
					}
					

					}
					
					}
					}
					}
					
					if(!$expand_data)
					{
					$this->cur_pointer[$this->idx]->setdata($this->convert_from_XML($res),$tmp);
                                        }

                                        
                                        }
   }
   
   function cdata_ref($parser, &$cdata)
   {
   //echo "<b>$cdata</b>" . "\n";
                                           

   
                                        if(isset($this->cur_pointer[$this->idx])){
                                                $tmp = $this->cur_pointer[$this->idx]->index_max();
                                                
                                        //$this->cur_pointer[$this->idx]->setdata($this->convert_from_XML($cdata),$this->cur_pointer[$this->idx]->index_max());
                                        //echo $this->convert_from_XML($cdata);
					
					$this->cur_pointer[$this->idx]->setdata($cdata,$tmp);
                                        }
   }

   function tag_close($parser, $tag)
   {
   
   
   	if(isset($this->cur_pointer[$this->idx]))
	{$this->cur_pointer[$this->idx]->complete();
        if(isset($this->cur_pointer[$this->idx]->prev_el))        
	 $this->cur_pointer[$this->idx] = &$this->cur_pointer[$this->idx]->getRefprev();
                                                      //$this->cur_pointer[$this->idx]->final_data();
	}
	//echo "&lt;/<font color=\"#0000cc\">$tag</font>&gt;<br>\n";

   }

   function &get_Object_of_Namespace($full_ns)
   {
	   if(is_String($full_ns))
	   {
		   $ns = explode($full_ns,'#');
		   
		   if($ns[0] && $ns[1])
		   {
			   if($tmp = &$this->namespace_frameworks[$ns[0]]['node'][$ns[1]]->new_Instance())
			   return $this->namespace_frameworks[$ns[0]]['node'][$ns[1]]->new_Instance();
			   else
			   return My_NameSpace_factory::alt_namespace_factory();
		   }
		   else
		   {
			   
			   return My_NameSpace_factory::alt_namespace_factory();
		   }
	   }
   }
   
   function &get_Class_of_Namespace($full_ns)
   {
	   if(is_String($full_ns))
	   {
		   $ns = explode('#',$full_ns);
		   //echo $ns[0] . "-" . $ns[1] . "\n";
		   if($ns[0] && $ns[1])
		   {
			   
			   
			   if($tmp = &$this->namespace_frameworks[$ns[0]]['node'][$ns[1]])
			   return $tmp;
			   else
			   throw new ErrorException('actual namespace for ' . $full_ns . ' is not defined.', 255, 75);
		   }
		   else
		   {
			   
			   throw new ErrorException('actual namespace for ' . $full_ns . ' is not defined.', 255, 75);
		   }
	   }
   }
 

   function set_Object_to_Namespace($ns,&$obj)
   {
   

	   $namespace = $ns;
	   if(is_String($ns))
	   {
		   if(is_null($obj))echo "needs a class in line 637";
		   $ns = explode('#',$ns);
		   if($ns[0] && $ns[1])
		   {

			   //echo 'add:' . $ns[0] . "#" . $ns[1] . "<br>\n";
			$this->namespace_frameworks[$ns[0]]['node'][$ns[1]] = &$obj;
		   }
		   else
		   {
			   //echo 'add2:' . $ns[0] . "#" . $ns[1] . "<br>\n";
			   $this->namespace_frameworks[$ns[0]]['node'][$ns[1]] = &$obj;
			   
		   }
		   
		   //for ticketevent
		  if(is_Array($this->ticketlist[$namespace]))
		  {
			  $this->fireTicketEvent($obj,$this->ticketlist[$namespace]);
			  unset($this->ticketlist[$namespace]);
		  }
	   }
	   //$this->set_new_index($obj); //echo $obj->full_URI() . "\n";
//$this->test_consistence();
	   
   }

   protected function fireTicketEvent(&$ticketObject , array &$listener)
   {
	   for($i = 0; count($listener) > $i;$i++)
		   {
			   $listener[$i]->TicketEvent($ticketObject);
		   }
   }
   
   public function ticketEvent($eventOnFullNS , Interface_node &$listener)
   {
	  
	if(is_Array($this->ticketlist[$eventOnFullNS]))
	$this->ticketlist[$eventOnFullNS][count($this->ticketlist[$eventOnFullNS])] = &$listener;
   	else
	$this->ticketlist[$eventOnFullNS][0] = &$listener;
   	
   }
   
   function test_consistence()
   {
   	   echo "<h1> uebersicht </h1>";
	   foreach($this->namespace_frameworks as $key => $value)
		   {
			   echo "&lt;<font color=\"#00dd00\">$key</font>&gt;<br>\n";
			   	   foreach($value as $key2 => $value2)
				   {
					   if($key2 == 'nativ')
						   {
							   echo "&#x2003;&lt;<font color=\"#00dd00\">$key2</font>&gt;";
							   echo $value2->get_QName();
							   echo "&lt;/<font color=\"#0000ee\">$key2</font>&gt;<br>\n";
						   }
					    if($key2 == 'node')
					   {
						   echo "&#x2003;&lt;<font color=\"#00dd00\">$key2</font>&gt;<br>\n";
					   
					   foreach($value2 as $key3 => $value3)
					   {
					   
					   
		   				echo "&#x2003;&#x2003;&lt;<font color=\"#00dd00\">$key3</font>&gt;";
						if(get_Class($value3)!='')
						echo $value3->get_QName() . ' (' .  get_Class($value3) . ")";
						echo "&lt;/<font color=\"#0000ee\">$key3</font>&gt;<br>\n";
					      
					   }
					   echo "&#x2003;&lt;/<font color=\"#0000ee\">$key2</font>&gt;<br>\n";
					   }
					   
				   }
			   
				   echo "&lt;/<font color=\"#0000ee\">$key</font>&gt;<br>\n";
		   }
		   //array_walk(debug_backtrace(),create_function('$a,$b','print "{$a[\'function\']}()(".basename($a[\'file\']).":{$a[\'line\']}); ";'));
   }
   
   function index_consistence()
   {
   	   echo "\n uebersicht \n";
	   foreach($this->looking_index as $key => $value)
		   {
			   echo $key . "=>{";
			   	   foreach($value as $key2 => $value2)
				   {
		
						  
					   
						   echo $key2 . "=>{ \n";
					   foreach($value2 as $key3 => $value3)
					   {
		   
						   echo "{" . $key3 . '=' . $value3->full_URI() . ' (' . get_Class($value3) . ")}\n";
					   }
					  
					   echo "\n } \n";
				   }
			   
				   echo "\n } \n";
		   }
		   echo "\n ende \n";
   }
   
   function set_Namespace($ns)
   {
   $this->cur_ns = $ns;
   if(!is_array($this->namespace_frameworks[$ns]['node']))
   $this->namespace_frameworks[$ns]['node'] = array();
   }
   
   function get_URI()
   {
	   return $this->cur_pointer[$this->idx]->full_URI();
   }
   function get_NS_QName()
   {
	   return $this->cur_pointer[$this->idx]->get_QName();
   }
   
   /*
   *	TODO don't look right to return a namespace
   */
   function get_NS($namespace = null,$id = -1)
   {
	   if(is_null($namespace))
	   {
	   if(is_null($this->cur_pointer[$this->idx]))
	   {
	   return 'no_ns';
	   }else
	   return $this->cur_pointer[$this->idx]->get_NS();
	   }
	   else
	   {
		   if($id < 0)$id = $this->idx;
		   if($namespace == '')
		   {
		   	   
		   	   //var_dump($this->prefixes);
		   	   //if(!isset($this->prefixes[$id]))echo "nope!";
		   	   if(is_null($this->prefixes[$id]))throw  new ErrorException('native namespace is missing', 1124, 75);
		   	   $inMyPos = count($this->prefixes[$id]) - 1;
			   return $this->prefixes[$id][$inMyPos];
		   }
		   else
		   {
			   return $this->prefixes[$namespace][count($this->prefixes[$namespace]) - 1];
		   }
	   }
   }
   
   function get_Prefix($namespace,$id = -1)
   {
	   if($id < 0)$id = $this->idx;
	   return $this->prefixes_inv[$namespace][$id];
   }
   
   public function create_Ns_Node($prefix_Q_name, $stamp = null, array $attrib = null,$pos = -1 )
   {
  
  	   if(false !== strrpos ( $prefix_Q_name , '#' ))
  	   {
  	   $ns = explode('#',$prefix_Q_name);
  	   $prefix_Q_name = $this->get_Prefix($ns[0]) . ':' . $ns[1];
  	   }
  	   	
  
	   if(is_null($attrib))$attrib = array();
	   
	  if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
	  if(!isset($this->cur_pointer[$this->idx])){$this->cur_pointer[$this->idx] = &$this->mirror[$this->idx];}
	   if(!is_null($stamp))$this->go_to_stamp($stamp);
	   //saves current reference, on which the open and close method ist based
	   $save = &$this->cur_pointer[$this->idx];
	   //overwrites it with reference on which all the other methods deals with.
	  unset($this->cur_pointer[$this->idx]);
	  $this->cur_pointer[$this->idx] = &$this->pointer[$this->idx];
	  unset($this->pointer[$this->idx]);
	   //creates new node
	   
	   $this->tag_open($this,$prefix_Q_name,$attrib, $pos);
	   //saves its reference
	   $obj = &$this->cur_pointer[$this->idx];
	   //finalize it
	   $this->tag_close($this, $prefix_Q_name);
	   

	   
	   //set working reference on created node
	   $this->pointer[$this->idx] = &$obj;
	   //restore buildup reference
	   $this->cur_pointer[$this->idx] = &$save;

	   if($this->has_got_new_undef_node())
	   {
		   return false;
	   }
	   else
	   {
		   return $this->position_stamp();
	   }
	   
	   //$this->obj_stack[count($this->obj_stack)] = &$obj;

	
	   
	   /**
	                        $var = $this->getInstance($prefix_Q_name,array());
				echo $var->name;
			if(!isset($this->mirror[$this->idx]->next_el)){

                                
                                $this->mirror[$this->idx]->setRefnext($var);
                                $this->cur_pointer[$this->idx] = &$this->mirror[$this->idx]->getRefnext($this->mirror[$this->idx]->index_max() - 1 ,true);
                                //schliesst cdata ab
                                //$this->cur_pointer[$this->idx]->final_data();
                                $this->cur_pointer[$this->idx]->setRefprev($this->mirror[$this->idx]);
                                
                                
                                
                                
                        }else{

 				$var->setRefprev($this->cur_pointer[$this->idx]);
                                $this->cur_pointer[$this->idx]->setRefnext($var);
                                //schliesst cdata ab
                                $this->cur_pointer[$this->idx]->final_data();
                                $this->cur_pointer[$this->idx] = &$this->cur_pointer[$this->idx]->getRefnext($this->cur_pointer[$this->idx]->index_max() - 1 ,true);
                
                        }
			$this->cur_pointer[$this->idx]->complete();
*/

   }
   
   
   public function set_Ns_to_Listener($positionstampToListen = null)
   {
	   if(is_Null($positionstampToListen))
	   {
		  
		   $this->pointer[$this->idx]->to_listener();
	   }
	   else
	   {
		   
		   $pos = &$this->pointer[$this->idx];
		   
		   $this->go_to_stamp($positionstampToListen);
		   
		   $add = &$this->pointer[$this->idx];
		   
		   $pos->set_to_out($add);
		   
		   $this->pointer[$this->idx] = &$pos;
		   
	   }
   }
   
           /* setzt Eigenschaften */
     function set_node_attrib($key,$value)
     {
     	 	//echo "new attibute $name : $value \n";
	   $attrib = array();
	   $val_obj =  &$this->getInstance($key,$attrib);
	   $val_obj->setdata($value);
     $this->pointer[$this->idx]->attribute($key,$val_obj);
     }

public function goto_Attribute($uri)
   {
	$this->pointer[$this->idx] = &$this->pointer[$this->idx]->get_ns_attribute_obj($uri);
   }

     function append_xmlelement(&$node, $clone = true)
  {
  	  //echo $this->pointer[$this->idx]->position_stamp() . ' ' . $this->pointer[$this->idx]->name . "\n";		
  	  if($clone)
  	  	  $this->pointer[$this->idx] = &$node->cloning(
  	  	  	  $this->pointer[$this->idx]
  	  	  	  );
  	  	  
  	  else
  	  echo "append ohne clone ist noch nicht Implementiert";
  }
   
	public function get_Statistics()
	{
		
	}

    public function __toString()
     {
         return 'xml_ns';
     }

}
?>
