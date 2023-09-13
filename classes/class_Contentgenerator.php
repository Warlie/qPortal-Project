<?PHP

/**
*ContentGenerator vers. 1.0
*
* Generates content by reading XML and DB-entries
*
* (C) Stefan Wegerhoff
* vers. 1.0101
*/
require("class_database.php");
require("TreeEngine.php");
require("xml_multitree_semantic.php");


define("SEND_HEADER",true);
define("SEND_NO_HEADER",false);

class ContentGenerator
{

var $timestamp = 0;
var $timestamp2 = 0;
var $aktion = "init";
var $show_time = false;
	
//to stop running processes
var $eject = false;
var $array_incomming_list = array(); //defines a startpoint for parsing
var $incomming_find = true;

var $dbAccess = null;
var $template = null;
var $id_output_template = null;
var $doc_out_template = null;
var $out_template = null;
var $cur_template = null;
var $maintemplate = null;
var $structur = null;
var $control = null;
var $nodeName = null;
var $panel = true;
var $static = false;
var $menu = true;
var $param = array();
var $spezial = array();
public $XMLlist = null;
private $namespace_reg = null;

var $namespace_main = '';

var $heap = array(); //muss überarbeitet werden, namenskonflikte


	function __construct( $URL, $User, $PWST, $db_name = "", $codeset = "")
	{
		
		$this->dbAccess = new Database($URL, $User, $PWST, $db_name, $codeset);
		$this->XMLlist = new xml_presave_semantic($this);

	}
	
	function errno()
	{
		
		return $this->dbAccess->errno();
	}
	
	function getUser()
	{
		if($_SESSION['http://www.auster-gmbh.de/surface#user'])
			return $_SESSION['http://www.auster-gmbh.de/surface#user'];
		else
			return 'nobody';
	}
	
	function getForename()
	{
		if($_SESSION['http://www.auster-gmbh.de/surface#forename'] )
			return $_SESSION['http://www.auster-gmbh.de/surface#forename'] ;
		else
			return 'no';
	}
	
	function getSurname()
	{
		if($_SESSION['http://www.auster-gmbh.de/surface#surname'] )
			return $_SESSION['http://www.auster-gmbh.de/surface#surname'] ;
		else
			return 'body';
	}
	
	function getGroups()
	{
		if($_SESSION['http://www.auster-gmbh.de/surface#sector'] )
			return $_SESSION['http://www.auster-gmbh.de/surface#sector'] ;
		else
			return '';
	}
	
	function getAccess()
	{

		$result = true;

		if($tmp = $this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#sector') )	
			$result = in_array($tmp, explode(';',
				(is_null($str = $_SESSION['http://www.auster-gmbh.de/surface#sector']) ? "" : trim($str, ';'))
				));

			//var_dump($tmp, $_SESSION['http://www.auster-gmbh.de/surface#sector'],  $result);
				
		if($tmp =  intval($this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#securitylevel')) )
		{
			
			if($_SESSION['http://www.auster-gmbh.de/surface#securityclass'])
				$sec = intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']);
			else
				$sec = -1;
				
			
			$result = $result  &&  ((($tmp == -1) &&  ($sec == -1)) || (($tmp != -1) &&  ($sec >= $tmp))) ; 
		}	
			
		if ( !(false === ($hidden = strpos( $this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#name'), '.' ) ) )
		&& intval($hidden) == 0 )
		{


		$result =false;
		
		
		}
		
		// Abfrage client
		if($tmp = $this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#device') )		
			$result = $result && ((strtoupper($tmp) == 'MOBILE') xor  !$this->isMobileDevice() ) ;
		

		

		//if($result)echo $this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#device');
		//echo $this->isMobileDevice();
	return $result;
	}
	
	private function isMobileDevice(){
		$aMobileUA = array(
			'/iphone/i' => 'iPhone', 
			'/ipod/i' => 'iPod', 
			'/ipad/i' => 'iPad', 
			'/android/i' => 'Android', 
			'/blackberry/i' => 'BlackBerry', 
			'/webos/i' => 'Mobile'
			);

		//Return true if Mobile User Agent is detected
		foreach($aMobileUA as $sMobileKey => $sMobileOS){
			if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
				return true;
			}
		}
		//Otherwise return false..  
		return false;
	}
	
	
	function &getSQLObj()
	{
		
		return $this->dbAccess;

	}
	
	function &getXMLObj()
	{
		return $this->XMLlist;
	}
	
	function injectSQL($SQLString)
	{
		
		//echo $SQLString;
		
		$teile = explode(";
", $SQLString);
		for($i = 0; count($teile)>$i;$i++)
		{

		if(strlen(trim($teile[$i]))>2)$this->dbAccess->SQL($teile[$i] . ';');
		}

	}
	
	function setboolPanel($bool)
	{
		$this->panel = $bool;
	}
	function setstaticPanel($bool)
	{
		$this->static = $bool;
	}
	
	function setPageParam($param)
	{
		$this->heap['request'] = $param;
		$this->param = $param;
		
	}

	function setSessionParam($param)
	{
		$this->heap['session'] = $param;
	}
	
	function setXMLTemplate($URL)
	{
		//$this->template = $URL;
	}
	
	function setXMLStructur($URL)
	{
		$this->structur = $URL;
	}

	function getXMLStructur()
	{
		return $this->structur;
	}
	
	function setControlElement($name,$attrib)
	{
		$this->control[0] = $name;
		$this->control[1] = $attrib;
	}
	
	function setTreeNodeName($name="")
	{
		$this->nodeName = $name;
	}
	

	//----------functions for nodes --------------
	
	function set_template($id,$uri)
	{
		$this->heap['template'][$id] = $uri;
	}
	
	function get_template($id) //:URI
	{
		return $this->heap['template'][$id];
	}
	
	function set_out_template($id) 
	{

		if(!$this->heap['template'][$id])echo " $id nicht verfuegbar";
		$this->out_template = $this->heap['template'][$id];
		
		
		
	}
	
	function set_current_template($id) 
	{
		//var_dump( $this->heap);

		if(!$this->heap['template'][$id])echo " '$id' nicht verfuegbar \n";
		$this->cur_template = $this->heap['template'][$id];
		//echo $this->cur_template;
		
		
		
	}
	
	function get_out_template() //:URI
	{
		return $this->out_template;
	}
	
	function get_current_template() //:URI
	{
		return $this->cur_template;
	}
	
	function set_doc_out($id) //:URI
	{
		//echo 'ausgabe "' . $id . '" -' .  $this->heap['template'][$id] . '- Ende';
		$this->id_output_template = $id;
		//$this->doc_out_template = null;
	}
	
	function set_Main_NS($ns)
	{
		$this->namespace_main = $ns;
	}
	
	function get_Main_NS()
	{
		return $this->namespace_main;
	}
	
	function set_Reg_NS($ns)
	{
		$this->namespace_reg = $ns;
	}
	
	function get_Reg_NS()
	{
		return $this->namespace_reg;
	}
	
	public function get_System_Overview()
	{
		$res = ''; //$this->XMLlist->show_index();
		return $res . "\n";
	}
	//-----------END functions for nodes-----------
	
	function generate()
	{
		
		
		if($this->show_time)
		{
		$this->timestamp = $this->microtime_float();
		$this->timestamp2 = $this->microtime_float();
		}
		
		//if(is_Null($this->template))return false;
		
		if(is_Null($this->structur))return false;
		/*
		if(is_Null($this->control))
		{
		echo "booh2";
		return false;
		}
		*/
		if(is_Null($this->nodeName))return false;

		$this->heap['template'] = null;
		
		$treeEngine = new TreeEngine($this);
		//$this->XMLlist->load($this->template);
		$treeEngine->load_structur($this->structur,'@registry_surface_system');
		
		
				
		if($this->nodeName == "")
		$this->XMLlist->seek_node('http://www.trscript.de/tree#final');
		else
		if(!$this->XMLlist->seek_node(null,array('http://www.trscript.de/tree#name'=>$this->nodeName)))return false;
		$booh = null;
		

		if(!$this->XMLlist->show_xmlelement())return true;
		
	//	try {

			$this->XMLlist->show_xmlelement()->event_message_in('*?start',new EventObject('',$this,$booh));
			
	//	} catch (Exception $e) {
   // echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
    	//	}
    		
		
		
		//$this->XMLlist->ALL_URI();		
		//$this->out_template = '@registry_surface_system'; 
		//$this->out_template = 'template/xml.xml'; 
		//$this->out_template = 'template/controlfiles/start_auster.xml';
		//$this->out_template = 'stdworkspace';
		//echo $this->XMLlist->cur_node() . ' - ' . get_Class($this->XMLlist->show_xmlelement())  ;
		
		
		/* alters outputtemplate */
		//var_dump($this->heap['template']);
		if($this->id_output_template)
		$this->doc_out_template = $this->heap['template'][$this->id_output_template];
		
		return true; //EnD
		
		//erweiterte Funktionen
		if($cache = $this->XMLlist->show_cur_attrib('PRECACHE'))
		{
			if($cache == 'ONCE')
			{
				$this->dbAccess->set_db_encode("ISO-8859-1");
				$this->rst = $this->dbAccess->get_rst('SELECT * FROM precache WHERE Name = \'' . $this->XMLlist->show_cur_attrib('NAME') . '\'');

				if(0 < $this->rst->rst_num())
				{
				
					return true;
				}
				else
				{
					$this->rst->setValue('precache.name',$this->XMLlist->show_cur_attrib('NAME'));
					$this->rst->setValue('precache.best_before',date("Y-m-d H:i:s",time()));
					
				}
			}
			
			if(substr($cache,0,12) == 'BEST_BEFORE(')
			{
				
				$minutes = substr($cache,12,strlen($cache) - 13);
				
				$this->dbAccess->set_db_encode("ISO-8859-1");
				   $sqlab = "delete from precache where";
				   $sqlab .= " precache.best_before < '" . date("Y-m-d H:i:s",time()) . "';";
				
				$this->dbAccess->SQL($sqlab);
				$this->rst = $this->dbAccess->get_rst('SELECT * FROM precache WHERE Name = \'' . $this->XMLlist->show_cur_attrib('NAME') . '\'');
				       //echo $this->rst->value()
				if(0 < $this->rst->rst_num())
				{
				
					return true;
				}
				else
				{
					$this->rst->setValue('precache.name',$this->XMLlist->show_cur_attrib('NAME'));
					$this->rst->setValue('precache.best_before',date("Y-m-d H:i:s",time() + (60 * round($minutes)) ));
				}
			}
			
			
		}
		
		//�bergreifende Scripte
		
		
		//outsourcings keeps controlcommands out of maindocument
		if($newtemp = $this->XMLlist->show_cur_attrib('SRC'))
		{
			$this->XMLlist->load($newtemp);
			$this->structur = $newtemp;
			$this->XMLlist->seek_node('FINAL');
		}
		
		$stamp = $this->XMLlist->position_stamp();
		
		//collects all templates
		$this->collect_templates($this->XMLlist);

		
		
		//DAs sollte man als muell bezeichnen
		//static tree
		if($this->static && false)
		{
			echo $this->XMLlist->cur_node();
		//$this->XMLlist->seek_node('FINAL');
				for($i = 0;$i < $this->XMLlist->index_child(); $i++)
				{
				$this->XMLlist->child_node($i);
		if($this->XMLlist->cur_node() == "TREE" && (strtolower($this->XMLlist->show_cur_attrib('HIDDEN')) <> "hidden") )
		//echo $this->XMLlist->show_cur_attrib('NAME');
		$this->insertTree($this->XMLlist->show_cur_attrib('NAME')
				,$this->XMLlist->show_cur_attrib('VALUE'));
				$this->XMLlist->parent_node();
				}
		}
		
		
		$this->XMLlist->go_to_stamp($stamp);
		
		//
		
		//echo $this->XMLlist->cur_node();
		for($i = 0;$i < $this->XMLlist->index_child(); $i++)
		{
			
			$this->XMLlist->child_node($i);
			$stamp = $this->XMLlist->position_stamp();
			
			
			if($this->XMLlist->cur_node() == "TREE" && (strtolower($this->XMLlist->show_cur_attrib('HIDDEN')) <> "hidden") )
				$this->insertTree($this->XMLlist->show_cur_attrib('NAME')
				,$this->XMLlist->show_cur_attrib('VALUE')
				,true); //!$this->static
			
			if($this->XMLlist->cur_node() == "CONTENT" || $this->XMLlist->cur_node() == "LOAD_TEMPLATE" || $this->XMLlist->cur_node() == "PROGRAMM")
				
				$this->insertContent();
				
			//echo $this->XMLlist->cur_node(); content
			$this->XMLlist->go_to_stamp($stamp);
			$this->XMLlist->parent_node();
		}
		
		if($this->XMLlist->cur_node() <> "FINAL")
		{
			$this->XMLlist->parent_node();
			$this->insertTree($this->XMLlist->show_cur_attrib('NAME')
						,'>>' . $this->XMLlist->show_cur_attrib('VALUE')
						,!$this->static);
		}
		if($this->show_time)echo ($this->microtime_float() - $this->timestamp);
		
		return true;
	}
	
	function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((double)$usec + (double)$sec);
	}
	/* darf weg*/
	function collecdt_templates(&$xml_obj)
	{

		$tmp = $xml_obj->list_child_node();
		for($i=0;$i<count($tmp);$i++)
		{
			
			if($tmp[$i] == "TEMPLATE")
			{
				$xml_obj->child_node($i);
					for($j = 0;$j < $xml_obj->index_child(); $j++)
						{
			
								$xml_obj->child_node($j);
							
								if($xml_obj->cur_node() == "MAIN")
									{
										$this->template = $xml_obj->show_cur_data();
										$this->maintemplate = $xml_obj->show_cur_data();
										$this->out_template = $xml_obj->show_cur_data();
										if(is_Null($xml_obj->show_cur_attrib('DOCTYPE')))
										$doc_type = 'XML';
										else
										$doc_type = $xml_obj->show_cur_attrib('DOCTYPE');
										
										
										
										
										if($xml_obj->show_cur_attrib('CASE_FOLDING')=="0")
										{
											$preload = $xml_obj->show_cur_attrib('DOCTYPE_OUT');
											
											//echo 'no-casefold';
											
											$xml_obj->load($this->template,0,$doc_type);
											
											
											
											if($preload)
												{
													
													$xml_obj->TYPE[$xml_obj->idx] = $preload;
											
												}
											
										}
										else
										{
											$xml_obj->load($this->template,1,$doc_type);
										}
										$this->XMLlist->set_first_node();
										$xml_obj->change_URI($this->structur);
										

										//$this->TYPE[$this->idx]
										
									}
								
								if($xml_obj->cur_node() == "ADD")
									{
										//Es koennen auch dynamische Dokumente geladen werden
										
										
										if( '' == $this->heap['template'][$xml_obj->show_cur_attrib('ID')] = $xml_obj->show_cur_data())
										{
											
											if(0 < $xml_obj->index_child())
											{
												$xml_obj->child_node(0);
												
												
												$this->insertContent();
																								
												
												
												$obj = null;
												
												if(
												!is_Null($obj = $this->XMLlist->show_cur_obj())
												)
												{
						
													
													//if(is_subclass_of($obj,'plugin'))
													$xml_obj->parent_node();
													$xml_obj->set_node_cdata($obj->out(),0);
													
								
							

												}
												
												
												
											}
											else
											$xml_obj->set_node_cdata($this->template,0); //Dokumente werden nicht doppelt geladen
											
											
											$this->heap['template'][$xml_obj->show_cur_attrib('ID')] = $xml_obj->show_cur_data();
											
											if($xml_obj->show_cur_attrib('DOCTYPE_OUT')<>"")
											{
												$xml_obj->TYPE[$this->idx] = $xml_obj->show_cur_attrib('DOCTYPE_OUT');
											}
											
										}
										
										
										
																	
										
										
										if(is_Null($xml_obj->show_cur_attrib('DOCTYPE')))
										$doc_type = 'XML';
										else
										$doc_type = $xml_obj->show_cur_attrib('DOCTYPE');
										
										var_dump($xml_obj->show_cur_attrib('OUTPUT'));
										if($xml_obj->show_cur_attrib('OUTPUT'))$this->out_template = $xml_obj->show_cur_data();
										
										
										
										
										if($xml_obj->show_cur_attrib('CASE_FOLDING')=="0")
										{
										$xml_obj->load($xml_obj->show_cur_data(),0,$doc_type);
										}
										else
										{
										$xml_obj->load($xml_obj->show_cur_data(),1,$doc_type);
										}
										$xml_obj->set_first_node();
										$xml_obj->change_URI($this->structur);
										
										
									}
							
								$xml_obj->parent_node();
						}
			return true;
			}
		}
		if($xml_obj->parent_node())
			$this->collect_templates($xml_obj);
		else
		return false;
	}
	
	
	function insertTree($name,$value,$on = true)
	{
		
		$this->XMLlist->change_URI($this->template);
		
		if($this->XMLlist->seek_node($this->control[0],$this->control[1]))
		
		//$this->XMLlist->cur_node();
		if ($this->panel && $on)$this->createCotrolPanel($name, $value, $this->XMLlist);
		
		$this->XMLlist->change_URI($this->structur);
	}
//insert content in xhtml-template, position is depending on attribute class
	//private function for selecting 
	function allowed_idcs($level)
	{
		if($level == 0 || $this->incomming_find)
		{
			return 0;
		}
		else
		{
			echo "new index " . $this->array_incomming_list[$level + 1] . " in deeplevel " . ($level + 1) . "\n";
			if(count($this->array_incomming_list) == ($level + 2 )) $this->incomming_find = 'true';
			return $this->array_incomming_list[$level + 1];
		}
	}

	//creates Content by reading treecontent
	function insertContent($commingin = '',$eject = '',$level = 0)
	{
		
		//echo "info " . $this->XMLlist->cur_node() . '(' .  $this->XMLlist->show_cur_attrib('ID') . ')' . "[" . $this->XMLlist->position_stamp() . "] $commingin, $eject, $level \n";
		
		if($commingin <> '')
		{
			//echo $commingin . '=' . $this->XMLlist->position_stamp() . "\n";
			/*
			echo $commingin . '=' . $this->XMLlist->position_stamp() . "\n";
			
			$len1 = strlen($commingin);
			$len2 = strlen($this->XMLlist->position_stamp());
			if($len1 < $len2)
			{
				$min = strlen($len1);
				
			}
			else
			{
				$min = strlen($len2);
			}
			*/
			
			//looking for starttree
			if($level == 0)
			{
				$this->incomming_find = false; //to find spezific stamp
				
				$this->XMLlist->go_to_stamp($commingin);
			
				while(!($this->XMLlist->cur_node() == 'TREE') && !($this->XMLlist->cur_node() == 'FINAL'))
				{
					//echo $this->XMLlist->cur_node();
					if(!$this->XMLlist->parent_node())return false;
				}
			
				
				
				$deep_level = count($kaki = explode('.',$this->XMLlist->position_stamp())) - 1;
				/*
				echo $this->XMLlist->position_stamp();
				for($k = 0;$k < count($kaki); $k++)
				{
					echo $kaki[$k] . "\n";
				}
				*/
				$this->array_incomming_list = explode('.',$commingin);
				/*
				for($j = 0;$j < count($this->array_incomming_list); $j++)
				{
					echo $this->array_incomming_list[$j] . "\n";
				}
				*/
				
				for($i = $this->allowed_idcs($deep_level);$i < $this->XMLlist->index_child(); $i++)
				{
			
					$this->XMLlist->child_node($i);
					$stamp = $this->XMLlist->position_stamp();
			
					
										
					if($this->XMLlist->cur_node() == "CONTENT" || $this->XMLlist->cur_node() == "LOAD_TEMPLATE" || $this->XMLlist->cur_node() == "PROGRAMM")
				
					$this->insertContent($commingin,$eject,$deep_level + 1);
				
			
				$this->XMLlist->go_to_stamp($stamp);
				$this->XMLlist->parent_node();
				}
			}
			/*
			else
			{
				$array_list = explode(',',$commingin);
				$sub_stamp = $array_list[0];
				
				for($i = 1; $i < count($array_list);$i++)
				{
					$sub_stamp .= $array_list[$i];
				}
			}
			*/
			
			
			/*
			if($this->XMLlist->cur_node() == "CONTENT" || $this->XMLlist->cur_node() == "LOAD_TEMPLATE" || $this->XMLlist->cur_node() == "PROGRAMM")
			{	
				$this->insertContent();

			}
			
			
			echo substr($commingin,0,$min) . ':' . substr($this->XMLlist->position_stamp(),0,$min) . "\n";
			If(substr($commingin,0,$min) == substr($this->XMLlist->position_stamp(),0,$min) )
			{
				
				

			}
			*/
		}
		
		if($eject <> '')
		{
			//echo $eject . '=' . $this->XMLlist->position_stamp() . "\n";
			If($eject == $this->XMLlist->position_stamp())
			{
				
				//echo "steige aus \n";
				$this->eject = true;
			}
		}
		
		
if($this->show_time)
{
echo ($this->microtime_float() - $this->timestamp2);
echo  " " . $this->aktion . " ";
echo "<br>\n";

$this->aktion = $this->XMLlist->cur_node() . " name=" . $this->XMLlist->show_cur_attrib('NAME') . " id=" . $this->XMLlist->show_cur_attrib('ID');
$this->timestamp2 = $this->microtime_float();
}

		//activate objects in parametertags
		if($this->XMLlist->cur_node() == "PARAM" && !$this->eject)
		{
		for($i = $this->allowed_idcs($level);$i < $this->XMLlist->index_child(); $i++)
		{
			$this->XMLlist->child_node($i);
			$stamp = $this->XMLlist->position_stamp();
			if(
				$this->XMLlist->cur_node() == "ELEMENT"
				||
				$this->XMLlist->cur_node() == "OBJECT"
				||
				$this->XMLlist->cur_node() == "LITERAL"
				||
				$this->XMLlist->cur_node() == "PROGRAMM"

				
			  )
				$this->insertContent($commingin,$eject,$deep_level + 1);

				
			//echo $this->XMLlist->cur_node(); content
			$this->XMLlist->go_to_stamp($stamp);
			$this->XMLlist->parent_node();
		}
		}
		
		//local variable for cur node in stuctur (xml_multitree shows malfunction in later prozess)
		$my_structur = $this->XMLlist->cur_node();
		
		
		//----------------PROGRAMM-------------------------
		if($this->XMLlist->cur_node() == "PROGRAMM" && !$this->eject)
		{
			$void = false;
			for($i = $this->allowed_idcs($level);$i < $this->XMLlist->index_child(); $i++)
			{

			
			$this->XMLlist->child_node($i);
			$stamp = $this->XMLlist->position_stamp();
			
			if(
					$this->XMLlist->cur_node() == "PARAM"
					)
					{
					
												//---------------------------------------------------	
						$find_idx = 0;
						$content = $this->XMLlist->show_cur_data($find_idx++);
						//echo $this->XMLlist->show_cur_attrib('NAME') . $this->XMLlist->index_child();
						for($gh = 0;$gh < $this->XMLlist->index_child(); $gh++)
						{
							
							$this->XMLlist->child_node($gh);
							$stampX = $this->XMLlist->position_stamp();
							if(
							$this->XMLlist->cur_node() == "OBJECT"

				
							)
							
							$this->insertContent();
							//$this->writes_object_content($this->XMLlist);
							
							
							
							//echo $this->XMLlist->cur_node(); content
							$this->XMLlist->go_to_stamp($stampX);
							
							//object ermittelt
							if(
							!is_Null($obj = $this->XMLlist->show_cur_obj())
							)
							{
						
								//echo get_class($obj) . "<p>";
								//if(is_subclass_of($obj,'plugin'))
								//$content[$gh] = $obj->out();
								$content .= $obj->out();
								//echo $content;
							

							}
							
							$this->XMLlist->parent_node();
							$content .= $this->XMLlist->show_cur_data($find_idx++);
							
							
						}
						
					$key = $this->XMLlist->show_cur_attrib('NAME');
					$cont = $content;
					
					if(strtoupper($key) == 'IF')
					//echo $cont;
					//Backdoor
					$void = !eval('return '
					. str_replace(
					array('$',';'),
					array(' ','' ),
					$cont)	. ';');
					}
			
			
			if(
				!$void
				&&
				(
				$this->XMLlist->cur_node() == "CONTENT"
				||
				$this->XMLlist->cur_node() == "PROGRAMM"
				||
				$this->XMLlist->cur_node() == "ELEMENT"
				||
				$this->XMLlist->cur_node() == "OBJECT"
				||
				$this->XMLlist->cur_node() == "LITERAL"
				)

			  )
			  {
				  
				$this->insertContent($commingin,$eject,$deep_level + 1);
				
			  }	
				
			//echo $this->XMLlist->cur_node(); content
			$this->XMLlist->go_to_stamp($stamp);
			$this->XMLlist->parent_node();

		}
			
	
			
		}		
		
		//----------------LOAD_TEMPLATE-------------------------
		//echo $this->XMLlist->cur_node() . " \n";
		if($this->XMLlist->cur_node() == "LOAD_TEMPLATE" && !$this->eject)
		{
			
			$loading_id = $this->XMLlist->show_cur_attrib('ID');
			$loading_use = $this->XMLlist->show_cur_attrib('USE');
			$loading_method = $this->XMLlist->show_cur_attrib('METHOD');
			
			$this->heap['template'][$loading_id] = $this->XMLlist->show_cur_data();
			
			//$this->XMLlist->PostToHost($this->XMLlist->show_cur_data(), "HTTP://www.XXXX.de", $this->heap['template'][$loading_use],"UTF-8");
			
			
		}
		
		//!!! ben�tige Objekt
		//set both positions in xml-trees		
//----------------Content-------------------------
		if($this->XMLlist->cur_node() == "CONTENT" && !$this->eject)
		{
		
			//changes maintemplate, to edit an non maintemplate
			if ($other_template = $this->XMLlist->show_cur_attrib('ID'))
			{
			
				if($this->template = $this->heap['template'][$other_template])
				{
					
				}	
				else
				{
				echo "Der Name $other_template konnte nicht bei den Templates gefunden werden";
				$this->template = $this->maintemplate;
				}
			}
		
		
		
			
		$tag_name = $this->XMLlist->show_cur_attrib('NAME');
		
				$void = false;
		
				for($i = $this->allowed_idcs($level);$i < $this->XMLlist->index_child(); $i++)
				{
					$this->XMLlist->child_node($i);
					$stamp3 = $this->XMLlist->position_stamp();
					if(
					$this->XMLlist->cur_node() == "PARAM"
					)
					{
					
												
						
						//---------------------------------------------------	
						for($gh = 0;$gh < $this->XMLlist->index_child(); $gh++)
						{
							$this->XMLlist->child_node($gh);
							$stampX = $this->XMLlist->position_stamp();
							if(
							$this->XMLlist->cur_node() == "OBJECT"

				
							)
							
							$this->insertContent();
							//$this->writes_object_content($this->XMLlist);
							
							
							
							//echo $this->XMLlist->cur_node(); content
							$this->XMLlist->go_to_stamp($stampX);
							
							//object ermittelt
							if(
							!is_Null($obj = $this->XMLlist->show_cur_obj())
							)
							{
						
								//echo get_class($obj) . "<p>";
								//if(is_subclass_of($obj,'plugin'))
								//$content[$gh] = $obj->out();
								echo $obj->out();
							

							}
							
							$this->XMLlist->parent_node();
							
							
						}
						
					$key = $this->XMLlist->show_cur_attrib('NAME');
					$cont = $this->XMLlist->show_cur_data();
					
					if(strtoupper($key) == 'IF')
					{
					$void = eval('return' . str_replace(
					array('$','(',')',';'),
					array(' ',' ( ', ' ) ','' ),
					$cont) . ';');
					}
					else
					//{
					$tag_array[$this->XMLlist->show_cur_attrib('NAME')] = $this->XMLlist->show_cur_data();
					//}
					
					}
			//echo $this->XMLlist->cur_node(); content
					$this->XMLlist->go_to_stamp($stamp3);
					$this->XMLlist->parent_node();
				}
				
				
				$this->XMLlist->change_URI($this->structur);
		
			
				
		$this->XMLlist->change_URI($this->template);
		//echo $tag_name . ' ' . $tag_array['id'] . '<br>';
		$this->XMLlist->seek_node($tag_name,$tag_array);
		//echo $this->XMLlist->cur_node() . '<br>';
		//echo "werde aufgerufen". $this->XMLlist->show_cur_attrib('CLASS');
		
		$this->XMLlist->change_URI($this->structur);
		
		for($i = $this->allowed_idcs($level);$i < $this->XMLlist->index_child(); $i++)
		{

			
			$this->XMLlist->child_node($i);
			$stamp = $this->XMLlist->position_stamp();
			if(
				!$void
				&&
				(
				$this->XMLlist->cur_node() == "ELEMENT"
				||
				$this->XMLlist->cur_node() == "OBJECT"
				||
				$this->XMLlist->cur_node() == "LITERAL"
				)

			  )
			  
				$this->insertContent($commingin,$eject,$deep_level + 1);
				
	
				
				if($this->XMLlist->cur_node() == "ELEMENT" && $this->XMLlist->show_cur_attrib('VALUE'))
				{
				//	echo $this->XMLlist->cur_node() . ' : ' . $this->XMLlist->show_cur_attrib('TYPE') . '-' . $this->XMLlist->show_cur_attrib('VALUE') . '::';
				$this->XMLlist->change_URI($this->template);
				//echo $this->XMLlist->cur_node() . '<br>';
				$this->XMLlist->parent_node();
				$this->XMLlist->change_URI($this->structur);
				}
				
			//echo $this->XMLlist->cur_node(); content
			$this->XMLlist->go_to_stamp($stamp);
			$this->XMLlist->parent_node();
				if ($this->XMLlist->cur_node() == "CONTENT" && $this->XMLlist->show_cur_attrib('ID') && ($this->XMLlist->show_pointer()  == ($this->XMLlist->index_child() - 1)))
					{
			
					$this->template = $this->maintemplate;
					}
		}
			
		}
//----------------End Content-------------------------
		//an object will be called looks function writes_object_content
		if($this->XMLlist->cur_node() == "OBJECT" && !$this->eject)
		{

			//echo "call the object '" . $this->XMLlist->show_cur_attrib('ID') . "' in pos " . $this->XMLlist->position_stamp() . "<br>\n";
			$this->writes_object_content($this->XMLlist);
		}


		
//----------------Element-------------------------
		//insert an simle Element or group of elements
		
		if($this->XMLlist->cur_node() == "ELEMENT" && !$this->eject)
		{
		

			//$this->XMLlist->change_idx(0);
			//echo "<p>" . $this->XMLlist->cur_node();
			//$this->XMLlist->change_idx(1);
			
			$type = $this->XMLlist->show_cur_attrib('TYPE');
			//id takes a tag of Db. see writes_id_content
			
			if($type == "id")
			{
			$id = $this->XMLlist->show_cur_attrib('ID');
			$this->writes_id_content($id,$this->XMLlist);
			}
			//group can include several tags. see writes_group_content
			if($type == "group")
			{
			
				$id = $this->XMLlist->show_cur_attrib('ID');
			//echo $id;
				$this->writes_group_content($id,$this->XMLlist);
			}
			//xhtml creates a tag direct without DB. no individual function
			if($type == "xhtml")
			{

				$mode = explode(';',strtoupper($this->XMLlist->show_cur_attrib('MODE')));
				
				//finds name and content of the tag
				$value = $this->XMLlist->show_cur_attrib('VALUE');
				$attrib = $this->XMLlist->show_cur_attrib(); //maybe not nessesary
				
				//$data2 = $this->XMLlist->show_cur_data(); //ermittelt den inhalt des knotens
			//echo $this->XMLlist->show_cur_data(0);
				
				//creates a tag in outputdocument
				$this->XMLlist->change_URI($this->template);
				$stamp = $this->XMLlist->position_stamp();
				//to edit current element 
				if($value <> "" )
				{
				$this->XMLlist->create_node($stamp);
				$this->XMLlist->set_node_name($value);
				}
				
				
				

				
				
				$pos_of_object = 0;
				//$obj_content = array();
				$tagdata[0] = 'hallo';
				//creates attributes depending of all params in tag
				$this->XMLlist->change_URI($this->structur);
				
				//
				//echo $this->XMLlist->show_cur_data();
				//Bearbeitung aller unterknoten
				//gegenl�ufiger z�hler
				$data_array;
				$real_pos = 0;
				$data_array[0] = '';  
				$i;
				//$data_array[0] .= $this->XMLlist->show_cur_data(0);
				for($i = $this->allowed_idcs($level);$i < $this->XMLlist->many_cur_data() || $i < $this->XMLlist->index_child(); $i++)
				{
				
					//echo "zaehler1:" . $real_pos . " zaehler2:" . $i . " loops:" . $this->XMLlist->index_child() . " content:" . $this->XMLlist->show_cur_data($i) .  "<br>\n"; 
				if(is_Null($this->XMLlist->show_cur_data($i)))
				{
					//echo "null $real_pos " . $this->XMLlist->index_child();
					$data_array[$real_pos] .= "";
					//echo $data_array[$real_pos];
				}
				else
				{
					//echo $this->XMLlist->show_cur_data($i) . " $real_pos " . $i . " " . $this->XMLlist->index_child();

						$data_array[$real_pos] .= $this->XMLlist->show_cur_data($i);
					
				}
				

					
					if(
					$this->XMLlist->cur_node() == "PARAM" //parameter f�r attribute
					)
					{ //Param list
					//$real_pos--; //warum
					//$real_pos++;
						$content = "";								
						
						//---------------------------------------------------	
						for($gh = 0;$gh < $this->XMLlist->index_child(); $gh++)
						{
							$this->XMLlist->child_node($gh);
							$stampX = $this->XMLlist->position_stamp();
							if(
							$this->XMLlist->cur_node() == "OBJECT" //verarbeitet alle objekte 

				
							)
							$this->insertContent();
							//$this->writes_object_content($this->XMLlist);
							
							
							
							//kehrt zur�ck zu objekt event unnuetz
							$this->XMLlist->go_to_stamp($stampX);
							
							//object ermittelt
							if(
							!is_Null($obj = $this->XMLlist->show_cur_obj())
							)
							{
						
								//echo get_class($obj) . "<p>";
								if(is_subclass_of($obj,'plugin'))
								
								$content[$gh] = $obj->out();
							

							}
							
							$this->XMLlist->parent_node();
							
							
						}
					
						//objekte in param abgearbeitet
						
						$modi = '';
						for($xt = 0;$xt <= $this->XMLlist->index_child(); $xt++)
						{
						
						$modi = $this->XMLlist->show_cur_data(($xt ));
						$this->XMLlist->clear_node_cdata($xt);
						//echo $modi . 'booh';
						//echo "$modi" . $content[$xt];
						$this->XMLlist->set_node_cdata($modi . $content[$xt],$xt);
						//
						//$this->XMLlist->set_node_cdata($content[$xt],$xt);
						//clear_node_cdata($pos=null)
						}
						
					
					//-----------------------------------------------------------------
						
					$key = $this->XMLlist->show_cur_attrib('NAME');
					$cont = $this->XMLlist->show_cur_data();
					
					$this->XMLlist->change_URI($this->template);
					$this->XMLlist->set_node_attrib($key,$cont);
					$this->XMLlist->change_URI($this->structur);

					} //param list end
			//echo $this->XMLlist->cur_node(); content
			
			}
					
					if(
					$this->XMLlist->cur_node() <> "PARAM" || //parameter f�r attribute
					$this->XMLlist->cur_node() <> "OBJECT"
					)
					{
						$real_pos++;
					}
			
			
					$this->XMLlist->go_to_stamp($stamp3);
					
					$this->XMLlist->parent_node();
					
					
					//echo implode($data_array,',');
					//if($data2 <> "")$this->XMLlist->set_node_cdata($data2,0); //needs to be updated, sicks all rows to one
					
					
					
				//$data[$i+1] = $this->XMLlist->show_cur_data($i+1);
				}
				
				//echo "wieder raus $real_pos-------------------<br>\n";
				//$data_array[$real_pos] .= $this->XMLlist->show_cur_data($this->XMLlist->many_cur_data() - 1);
				
				//for($zah = 0;$zah < $this->XMLlist->many_cur_data();$zah++){echo $this->XMLlist->show_cur_data($zah) . " :: $zah<br>\n";}
				
				//for($za = 0;$za < count($data_array);$za++){echo $data_array[$za] . ":: $za ::" . count($data_array) . "<br>\n";}
				

			}
		//$this->XMLlist->change_idx(0);
		
		//echo "werde aufgerufen". $this->XMLlist->cur_node();
		
		
		//calls all elements or literals in last tag
		for($i = $this->allowed_idcs($level);$i < $this->XMLlist->index_child(); $i++)
		{
			$this->XMLlist->child_node($i);
			$stamp = $this->XMLlist->position_stamp();
			if(
				$this->XMLlist->cur_node() == "ELEMENT"
				||
				$this->XMLlist->cur_node() == "LITERAL"
				||
				$this->XMLlist->cur_node() == "OBJECT"
				

			  )
				$this->insertContent($commingin,$eject,$deep_level + 1);
				
				
				
				//kontrolle
				//echo $this->XMLlist->cur_node() . '/n';
				
				if($this->XMLlist->cur_node() == "ELEMENT" && $this->XMLlist->show_cur_attrib('VALUE'))
				{
				$this->XMLlist->change_URI($this->template);
				$this->XMLlist->parent_node();
				$this->XMLlist->change_URI($this->structur);
				}
				
			//leafs current tag
			//echo $this->XMLlist->cur_node(); content
			$this->XMLlist->go_to_stamp($stamp);
			
			
			$this->XMLlist->parent_node();
		}		
		
		//hier muss die abfrage fuer den Kontext hin, da hier bereits alle nicht Parmetertags abgearbeitet wurden
		//request for Kontext is needed to be here, because of all subnodes, whicht are allready actualised.
		
		//echo $my_structur ;
		if($type == "xhtml" && $my_structur == "ELEMENT"  && !$this->eject)
		{
			/*
		for($i = $this->allowed_idcs($level); $i < $this->XMLlist->index_child(); $i++)
		{
				//if a node exist
				if($this->XMLlist->index_child() > $i)
				{
					$this->XMLlist->child_node($i);
					$stamp3 = $this->XMLlist->position_stamp();
					
					//*
					if(
					$this->XMLlist->cur_node() == "OBJECT" //objekte f�r die textknoten
					)
					{
					//$real_pos++;
							//$this->insertContent(); //!
							//$this->writes_object_content($this->XMLlist);
												
							echo $this->XMLlist->show_cur_attrib('NAME') . ' - ';
							echo $this->XMLlist->show_cur_obj() . "<br>";
							
							//object ermittelt
							if(
							!is_Null($obj = $this->XMLlist->show_cur_obj())
							)
							//{
						
								echo get_class($obj) . "<p>";
								//if(is_subclass_of($obj,'plugin'))
								//{
								//$data_array[$real_pos] .= $obj->out();
								//echo $obj->out() . $obj->decription();

								//}
							

					//$pos_of_object++;		
					}
		
				}
				
			$this->XMLlist->go_to_stamp($stamp3);
			$this->XMLlist->parent_node();
		}// end for
		*/
				//writes cdata from tree-doc to specific cdata in tag of cur doc
				$this->XMLlist->change_URI($this->template);
				
				if(trim(implode($data_array,',')) <> "")
				{
					if(in_array("TRUNCATE",$mode))	$this->XMLlist->clear_node_cdata();		
					$this->XMLlist->set_node_cdata( 'me' . $this->trim_func(implode($data_array,''),in_array("NOTRIM",$mode)),0); //needs to be updated, sicks all rows to one
				}
				//for($gh = 0;$gh < count($data); $gh++)
				//{
				//	echo $data[$gh];
				//	$this->XMLlist->set_node_cdata($data[$gh],$gh);
				//}
								
				$this->XMLlist->change_URI($this->structur);
		
		}//end if
		
		
		
//----------------End Element-------------------------
	}
	
	
	//creates on tag by db
	function writes_id_content($id,$xml)
	{
$sql = "SELECT tag_collection.id, tag_collection.type, tag_content.content, attrib_collection.name, attrib_collection.value, tag_collection.ref
FROM (
(
tag_collection
LEFT JOIN connect_collection ON tag_collection.attrib = connect_collection.tagid
)
LEFT JOIN tag_content ON tag_collection.content_ref = tag_content.id
)
LEFT JOIN attrib_collection ON connect_collection.attribid = attrib_collection.id
WHERE tag_collection.id = '$id'
ORDER BY tag_collection.order;";

		//$sql = "SELECT tag_collection.id, tag_collection.type, tag_collection.content_ref, attrib_collection.name, attrib_collection.value, tag_collection.ref FROM (tag_collection ";
		//$sql .= " LEFT JOIN connect_collection ON tag_collection.attrib = connect_collection.tagid ) LEFT JOIN attrib_collection ON connect_collection.attribid = attrib_collection.id ";
		//$sql .= "WHERE tag_collection.id = '$id' ORDER BY tag_collection.order;";

		//$sql = "SELECT * FROM tag_collection ";
		//$sql .= " LEFT JOIN attrib_collection ON tag_collection.attrib = attrib_collection.id WHERE tag_collection.id = '$id';";
		
		$rst = $this->dbAccess->get_rst($sql);
		$rst->first_ds();
		
		$field = $rst->db_field_list();
		
		
		$xml->change_URI($this->template);
		$stamp = $xml->position_stamp();
		$xml->create_node($stamp);
		$xml->set_node_name($rst->value($field[1]));
		$xml->set_node_cdata($rst->value($field[2]),0);
		
		while(!$rst->EOF())
		{
		if($rst->value('attrib_collection.name') <> null)$xml->set_node_attrib($rst->value('attrib_collection.name'),$rst->value('attrib_collection.value'));
		$rst->next_ds();
		}
		$this->XMLlist->change_URI($this->structur);
	}

	
		function writes_group_content($id,$xml)
	{
		
/*		SELECT tag_collection.id, tag_collection.type, tag_collection.content, attrib_collection.name, attrib_collection.value
FROM (tag_collection 
LEFT JOIN connect_collection ON tag_collection.attrib = connect_collection.tagid )
LEFT JOIN attrib_collection ON connect_collection.attribid = attrib_collection.id
WHERE tag_collection.group =  'beton'
ORDER  BY tag_collection.order */
		
		$sql = "SELECT tag_collection.id, tag_collection.type, tag_content.content, attrib_collection.name, attrib_collection.value, tag_collection.ref FROM ((tag_collection ";
		$sql .= " LEFT JOIN connect_collection ON tag_collection.attrib = connect_collection.tagid ) LEFT JOIN tag_content ON tag_collection.content_ref = tag_content.id )LEFT JOIN attrib_collection ON connect_collection.attribid = attrib_collection.id ";
		$sql .= "WHERE tag_collection.group = '$id' ORDER BY tag_collection.order;";
		//echo $sql;
		$rst = $this->dbAccess->get_rst($sql);
		$rst->first_ds();
		
		
		
		$field = $rst->db_field_list();
		
		
		$xml->change_URI($this->template);
		$stamp = $xml->position_stamp();
		while(!$rst->EOF())
		{
			$this->function_use($rst,$xml,$field,$stamp );
		}
		
		$this->XMLlist->change_URI($this->structur);
	}
	
	//deals with objects. is implement in class_Contentgenerator1
	function writes_object_content(&$xml)
		{
			
			$this->obj_beha($xml);
		}	
	
	function obj_beha(&$xml){;}
	

	//subfunction of writes_group_content	
	function function_use(&$rst,&$xml,&$field ,$stamp)
	{
			//first fieldname in list	
			$tmp = $rst->value($field[0]);
			//echo implode(",",$field);
			//templatedocument
			$xml->create_node($stamp);
			$xml->set_node_name($rst->value($field[1]));
			$xml->set_node_cdata($rst->value($field[5]),0);
			
			
			while(!$rst->EOF())
			{
				if($rst->value($field[7]) <> null && trim($rst->value($field[8])) <> "")
				{
					
					$xml->set_node_attrib($rst->value($field[7]),$rst->value($field[8]));
				}

				$rst->next_ds();
				
				
				
				if($tmp <> $rst->value($field[0]))
				{
				
					$tmp = $rst->value($field[0]);
					break;
				}
				
			}
		
			//$rst->next_ds();
	}
	

	function getoutput($set_header,$type = "",$special = "")
	{
	//echo memory_get_usage(true);
	//echo memory_get_peak_usage(true);
//echo  '<b>' . $this->heap['object']['reciepe']->lock . '</b><br>';

	if($this->doc_out_template)$this->out_template = $this->doc_out_template;
	//echo $this->out_template . " " . $this->XMLlist->ALL_URI();//
	if($this->out_template)
		if(!$this->XMLlist->change_URI($this->out_template))echo "Das Dokument: '" . $this->out_template . "' nicht gefunden!(getoutput)";
		
		if($type == "")$out = 'UTF-8';
		else $out = $type;
		
		
		
		if($special == "HTML")
		{
			
		$res = str_replace(
			array(	'�','�',
				'�','�',
				'�','�'
				),
			array(	'&uuml;','&Uuml;',
				'&ouml;','&Ouml;',
				'&auml;','&Auml;'
				),

			$this->XMLlist->save_Stream($out,$set_header)
			);
		}
		else
		
			$res = $this->XMLlist->save_Stream($out,$set_header);
		
		if(!is_null($this->rst))
		{
		if(0 == $this->rst->rst_num())
			{
				
				$this->rst->setValue('precache.value',$res);
				$this->rst->update();
				$this->dbAccess->insert_rst($this->rst);
			}
		if(0 < $this->rst->rst_num())
			{
				$this->rst->first_ds();
				return $this->rst->value('precache.value');
			}
		}
		return $res;
	}


	function saveoutput($set_header,$type = "",$special = "")
	{
	//echo memory_get_usage(true);
	//echo memory_get_peak_usage(true);
//echo  '<b>' . $this->heap['object']['reciepe']->lock . '</b><br>';

	if($this->doc_out_template)$this->out_template = $this->doc_out_template;
		if($this->out_template)
		if(!$this->XMLlist->change_URI($this->out_template))echo "Das Dokument: '" . $this->out_template . "' nicht gefunden!";
		
		if($type == "")$out = 'UTF-8';
		else $out = $type;
		
		
		

		
		return $this->XMLlist->save_file($out,$set_header);
	}

	
	//helpfunction
	function trim_func($string_trim,$bool_on)
	{
		if($bool_on)
		{
			return trim($string_trim);
		}
		else
		{
			return $string_trim;
		}
	}
//--------------------------------
	function createCotrolPanel($name, $link, $xml)
	{
		;
	}

	public function __toString()
	{
		return 'contextgenerator';
	}

}
?>
