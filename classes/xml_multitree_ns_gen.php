<?php

/**  Aufstellung der functionen des XML objektes
*    cur_node() :         aktuelle Position
*    list_child_node() :   gibt Array mit liste der nächsten knoten raus
*    index_child() :    gibt die mente der kindknoten wieder
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
*
*
*    only_child_node($bool_node) : für seek_node -> sucht dann nur alles unter dem aktuellen knoten
*    seek_node([String $type],assoz array [attrib],string [data]) : sucht einen Knoten
*
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
*   
*/

require_once('xml_multitree_ns.php');
require_once('ns/class_index.php');

class xml_gen extends xml_ns 
{
	//ein objektarray wird geladen, sobald ein entsprechender NS angegeben wird
	var $namespace_frameworks = array();
	var $prefixes = array();
	var $obj_stack = array();
	var $loadmodus = 0;
	
	
   function &getInstance2($name,$attributes,$obj_arg = null)
   {

	  
 	if($obj_arg)
	return parent::getInstance(
   	$name,
	$attributes,
	$obj_arg);
	else
	{
		//zeigt an, ob es sich um einen Wurzelknoten handelt
		$nativ = false;
	   //findet die namespaces und legt die objekte in einem array ab
	   if(is_array($attributes))
	   {
		   foreach( $attributes as $key => $value)
		   {
			if(!(false === strpos(strtolower(substr($key,0,5)),'xmlns')))
			{
				
				$value = str_replace('#','',$value);
				
				$obj = NameSpace_factory::namespace_factory($attributes[$key]);
				
				if(is_object($obj))
				{

				
				
				$this->namespace_frameworks[$value]['nativ'] = $obj->get_nativ();
				$this->namespace_frameworks[$value]['node'] = $obj->get_nodes();
				$this->namespace_frameworks[$value]['attrib'] = $obj->get_attrib();
				
				//echo get_class($this->namespace_frameworks[$value]['node']['type']);
				}
				if($this->namespace_frameworks[$value])
				
				
				
				$nativ = true; //native knoten definieren einen Namensraum und beenden ihn auch
				
					if(!(false === ($tmp = strpos($key,':'))))
					{
						      
						$prefix = substr(strtolower($key),0,$tmp);
						$postfix = substr(strtolower($key),$tmp + 1);
						
						if(is_array($prefixes[$postfix]))
						{
							
							$this->prefixes[$postfix][count($this->prefixes[$postfix])] = $value;
						}
						else
							$this->prefixes[$postfix][0] = $value;
							
						
						
					}
					else
					{
						
						if(is_array($this->prefixes['_main']))
							$this->prefixes['_main'][count($this->prefixes['_main'])] = $value;
						else
							$this->prefixes['_main'][0] = $value;
					}
				
			}
		   }
		   reset($attributes);
	   } 
	   
	   $node;
	   $null = null;
	   
	if(!(false === ($tmp = strpos($name,':'))))
	{
		
		$prefix = substr(strtolower($name),0,$tmp);
		$nodename = substr(strtolower($name),$tmp + 1);
		
		$full_ns = $this->prefixes[$prefix][count($this->prefixes[$prefix]) - 1];
		
		if($nativ)
			//wenn der Knoten nicht existiert, gibt es einen Standardknoten
			if($this->namespace_frameworks[$full_ns]['nativ'])
			{
				$node = $this->namespace_frameworks[$full_ns]['nativ']->new_Instance();
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $full_ns;
				$node->set_parser($this);
			}
			else
			{
				$node = NameSpace_factory::alt_namespace_factory();
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $full_ns;
				$node->set_parser($this);
			}
		else
			
			if($this->namespace_frameworks[$full_ns]['node'][$nodename])
			{
			
				//echo $full_ns . " gefunden $nodename<br>" . "\n" ;
				$node = $this->namespace_frameworks[$full_ns]['node'][$nodename]->new_Instance();
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $full_ns;
				$node->set_parser($this);
				
			}
			else
			{
				//echo $full_ns . " nicht gefunden $nodename<br>" . "\n";
				$node = NameSpace_factory::alt_namespace_factory();
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $full_ns;
				$node->set_parser($this);
			}
		
	}
	else
	{
		
		$glob_namespace = $this->prefixes['_main'][count($this->prefixes['_main']) - 1];
		$prefix = null;
		$nodename = substr(strtolower($name),$tmp);
		/*
		if($nodename == 'id_haendler')
		{
		echo 'hier ' . $glob_namespace . ' ' . $nodename . "<br>\n";
		echo $this->namespace_frameworks[$glob_namespace]['node'][$nodename]->name;
		}
		*/
		
		if($nativ)
			//wenn der Knoten nicht existiert, gibt es einen Standardknoten
			if($this->namespace_frameworks[$glob_namespace]['nativ'])
			{
				//echo " gefunden in main $nodename<br>" . "\n" ;
				$node = $this->namespace_frameworks[$glob_namespace]['nativ']->new_Instance();
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $this->prefixes['_main'][count($this->prefixes['_main']) - 1];
				$node->set_parser($this);
			}
			else
			{	
				//echo "nicht gefunden in main $nodename<br>" . "\n" ;
				$node = NameSpace_factory::alt_namespace_factory();
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $this->prefixes['_main'][count($this->prefixes['_main']) - 1];
				$node->set_parser($this);
			}
		else
			if($this->namespace_frameworks[$glob_namespace]['node'][$nodename])
			{
				//echo " gefunden in main $nodename<br>" . "\n" ;
				$node = $this->namespace_frameworks[$glob_namespace]['node'][$nodename]->new_Instance();
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $this->prefixes['_main'][count($this->prefixes['_main']) - 1];
				$node->set_parser($this);
			}
			else
			{
				//echo "nicht gefunden in main $nodename<br>" . "\n" ;
				$node = NameSpace_factory::alt_namespace_factory();
				$node->name = $name;
				$node->type = $nodename;
				$node->namespace = $this->prefixes['_main'][count($this->prefixes['_main']) - 1];
				$node->set_parser($this);
			}
		
	}
	//echo $prefix . " : " . $nodename . "<br>\n";
	
	
		
	
	    
		
	
	   
	   
	   
	   if($attributes)
	   if (count($attributes)) {
            foreach ($attributes as $k => $v) 
	    {
		    
		    	if(!(false === ($tmp = strpos($k,':'))))
			{
				
					$prefix = substr(strtolower($k),0,$tmp);
					$attribname = substr(strtolower($k),$tmp + 1);
					
					
					$full_ns = $this->prefixes[$prefix][count($this->prefixes[$prefix]) - 1];
					//echo "$full_ns $prefix $attribname<br>\n";
					if($this->namespace_frameworks[$full_ns]['attrib'][$attribname])
					{
						$attrib = $this->namespace_frameworks[$full_ns]['attrib'][$attribname]->get_Instance();
						$attrib->in($this->convert_from_XML($v));
						$attrib->name  = $k;
						$node->attribute($k,$attrib);
					}
					else
					{
						$node->attribute($k,$this->convert_from_XML($v));
					}
			}
			else
			{
					
					$prefix = null;
					$attribname = substr(strtolower($k),$tmp);
					
					if($this->namespace_frameworks[$full_ns]['attrib'][$attribname])
					{
						$attrib = $this->namespace_frameworks['_main']['attrib'][$attribname]->get_Instance();
						$attrib->in($this->convert_from_XML($v));
						$attrib->name  = $k;
						$node->attribute($k,$attrib);
					}
					else
					{
						$node->attribute($k,$this->convert_from_XML($v));
					}
					
			}

                    
	    }
	}
	
	$this->obj_stack[count($this->obj_stack)] = &$node;
	
	return $node;
	/*
	return parent::getInstance(
   	$name,
	$attributes,
	$node);
	*/
	}
   }


   
   function tag_open_gen($parser, $tag, $attributes)
   {

	
	   
        if(!isset($this->mirror[$this->idx])){

                $num = 0;

                $this->mirror[$this->idx] = $this->getInstance($tag,$attributes);
 
                }else{

                        $num = $this->mirror[$this->idx]->index_max();

                        if(!isset($this->mirror[$this->idx]->next_el)){

                                $var = $this->getInstance($tag,$attributes);
                                $this->mirror[$this->idx]->setRefnext($var);
                                $this->cur_pointer[$this->idx] = &$this->mirror[$this->idx]->getRefnext($this->mirror[$this->idx]->index_max() - 1 ,true);
                                //schliesst cdata ab
                                //$this->cur_pointer[$this->idx]->final_data();
                                $this->cur_pointer[$this->idx]->setRefprev($this->mirror[$this->idx]);
                                
                                
                                
                                
                        }else{

                                $var = $this->getInstance($tag,$attributes);
                                $var->setRefprev($this->cur_pointer[$this->idx]);
                                $this->cur_pointer[$this->idx]->setRefnext($var);
                                //schliesst cdata ab
                                $this->cur_pointer[$this->idx]->final_data();
                                $this->cur_pointer[$this->idx] = &$this->cur_pointer[$this->idx]->getRefnext($this->cur_pointer[$this->idx]->index_max() - 1 ,true);
                                //$this->cur_pointer[$this->idx]->setRefprev(&$this->mirror[$this->idx]);
                                //$cur_pointer = &$this->mirror[$this->idx]->getRefnext();
                
                        }
                        
                        
 
           }
           

   }

   function cdata_gen($parser, $cdata)
   {
   //echo "<b>$cdata</b>";
                                           

   
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
					
					$this->cur_pointer[$this->idx]->setdata($this->convert_from_XML($res),$tmp);
                                        }
   }

   function tag_close_gen($parser, $tag)
   {
   //echo "&lt;/<font color=\"#0000cc\">$tag</font>&gt;";
   
   	if(isset($this->cur_pointer[$this->idx]))
	{$this->cur_pointer[$this->idx]->complete();
        if(isset($this->cur_pointer[$this->idx]->prev_el))        
	 $this->cur_pointer[$this->idx] = &$this->cur_pointer[$this->idx]->getRefprev();
                                                      //$this->cur_pointer[$this->idx]->final_data();
	}

   }

}
?>
