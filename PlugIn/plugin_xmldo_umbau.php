<?PHP

/**
*ContentGenerator
*
* Generates content by reading and writing trees
*
* @-------------------------------------------
* @title:XMLDO
* @autor:Stefan Wegerhoff
* @description: Treeobject, transforms trees to tables and back
*/
require_once("plugin_interface.php");

class XMLDO extends plugin 
{
var $template;
var $rst;
var $tag = array();

var $cur;
var $order = array();
var $pickdata = array();
var $insert = 0;
var $collection = null; //tag, unter dem gesucht wird
var $table = null; //tabelle an gesammelten inhalten
var $pos = 0;
var $numOfOut = 1000000;
var $cdata = false;
var $void = null;
var $error = true;
var $error_text = '';
var $modus = 'LIST';
var $prevValue = array();



	function XMLDO(/* System.Parser */ &$back, /* System.CurRef */ &$treepos)
	{
		//$this->back= &$back;
		//$this->treepos = &$value;
		//$this->id = $value; , &$id
	}
	
	
	function set($type, $value)
	{
		/*
		If(!is_object( $value )){
			echo $type . ' ' . $value . "\n";
		}else 
		echo $type . "\n";
		*/
		$generator = &$this->generator();
	}

		
		
		
		/**
		*@function: MOVEFIRST = goes to first record
		*/
		
		public function moveFirst()
		{$this->pos = 0;}
		
		
		/**
		*@function: MOVELAST = goes to last record
		*/
		public function moveLast()
		{$this->pos = count($this->table) - 1;}
		
		/**
		*@parameter: XMLTEMPLATE = selects a tree with this id
		*/
		public function xmlTemplate($value)
		{
			
			$this->template = $this->generator()->heap['template'][$value];
			
			if(is_null($this->template) && $this->error )echo '<br><b>das Template ist nicht verf&uuml;gbar:' . $value . '</b><br>';
		}
		
		/**
		*@function: HAS_TAG = returns a boolean value refering to the seeking tag, descripted by xpath 
		* TODO muss auf xpath erweitert werden
		*/
		public function has_Tag()
		{
			//echo "aufgerufen " . $this->id . " " . $this->template . "\n" ;
					if(is_null($this->template))
					{
						return 'false';
					}
					//echo "bearbeitet" . "\n";
					
					
					$tmpstamp = $this->generator()->XMLlist->position_stamp();
					
					$this->generator()->XMLlist->change_URI($this->template);
					$this->generator()->XMLlist->set_first_node();
					//$generator->XMLlist->cur_idx(). "id \n";
					
					//$generator->XMLlist->seek_node($this->tag[$this->order[$i]]['xpath']);
					$orginal = &$this->generator()->XMLlist->show_xmlelement();
					$clone = &$this->find($orginal,$value);
					if(is_null($clone->name) )
					{
						$mytemp = "false";
						return $mytemp;
					}
					else
					{
						$mytemp = "true";
						return $mytemp;
					}	
					$generator->XMLlist->go_to_stamp($tmpstamp);
		}
		
		/**
		*@parameter: LIST = gets an object to receive data
		*/
		public function get_list(&$value)
		{
			
			if(is_object($value))
			{
				$this->rst = &$value;
				
			}
		}

		/**
		*@parameter: COLLECTION = defines a tag, which is a root of a subtree. All direct childnodes to this node will be taken for rows in a table
		*/
		public function COLLECTION($value)
		{
			$this->collection = $value;
		}
		
		/**
		*@parameter: CDATA = Empty tag to give out all in cdata notation
		*/
		public function set_on_CDATA()
		{
			
			$this->cdata = true;
			
			
		}
		/**
		*@parameter: USE_TAG = optional, it only scan those tags to receive data
		*/
		public function use_tag($value)
		{
			
			
			$this->void = $value;
			
		}
		
		/**
		*@parameter: ERROR = on "no" error out to browser is disabled
		*/
		public function setError($value)
		{
			
			
			if(strtolower($value)=='no')$this->error = false;
			
		}
		
		/**
		*@parameter: ERROR_TEXT = in most cases an error ocurs, when there is no data to generate trees, so it is able to create a error text in tree
		*/
		public function setErrorText($value)
		{
			
			
			$this->error_text = $value;
			
		}
		
		/**
		*@---------sequence---------
		*@parameter: TAG_IN = opens a definition for a column, which is available about the value name.
		*/
		public function setcurTag($value)
		{
			
			$this->cur = $value;
			$this->order[count($this->order)] = $value;
			
		}
		
		/**
		*
		*@parameter: TAG_OUT = close the current columndefinition. Needs no value.
		*/
		public function clearcurTag()
		{
			
			$this->cur = '';
			
			
		}
		
		/**
		*
		*@parameter: XPATH = xpath, which descibes a node in a subtree defined by COLLECTION or an inserttree to create new subtrees. Belongs to a reading and a generatingsequence.
		*/
		public function xpath($value)
		{
			
			$this->tag[$this->cur]['xpath']=$value;
			
			
		}
		/**
		*@parameter: ATTRIB = when a node in a generating subtree needs its information in a attribute, it needs the attributename in its sequence. Belongs to a generating sequencee
		*/
		public function setAttribute($value)
		{
			
			$this->tag[$this->cur]['pos']='ATTRIB';
			$this->tag[$this->cur]['name']=$value;  
			$this->tag[$this->cur]['pick']=$generator->XMLlist->show_cur_attrib('INSERT');
			
			
			
		}
		/**
		*@parameter: ATTRIB = when a node in a generating subtree needs its information in a dataarea, it needs only <data/> in its sequence. Belongs to a generating sequencee
		*/
		public function setData()
		{
			//echo 'boooh' . $this->cur;
			$this->tag[$this->cur]['pos']='DATA';
			
			
		}
		/**
		*@parameter: CONTENT = columnname for the emitter. Belongs to a reading sequence.
		*/
		public function setContent($value)
		{
			
			$this->tag[$this->cur]['content']=$value;
			
			
		}

		/**
		*@parameter: VALUE = direct value in using as emitter. Offers a static value to a columnname. Belongs to a reading sequence.
		*/
		public function setValue($value)
		{
			if(!$this->tag[$this->cur]['pos'])$this->tag[$this->cur]['pos']='VALUE';
			$this->tag[$this->cur]['value']=$value;
			
			
		}
		
		/**
		*@parameter: PREFIX = fixed text previous to data. Belongs to a generating sequence
		*/
		public function setPrefix($value)
		{
			

			
			$this->tag[$this->cur]['prefix'] = $value;
		}
		
		/**
		*@parameter: POSTFIX = fixed text next to data. Belongs to a generating sequence.
		*/
		public function setPostfix($value)
		{
			

			
			$this->tag[$this->cur]['postfix'] = $value;
		}
		
		/**
		*@parameter: DATAPOS = is needed for choose position between childnodes in dataarea. i.e. <span> <hr/>is position 1</span> . Belongs to a generating sequence.
		*/
		public function setDataPos($value)
		{
			
			$this->tag[$this->cur]['datapos']=$value;
			
			
		}

		/**
		*@parameter: COL = gives out data to an field
		*/
		public function setCol($value)
		{
			
			
			if(is_null($this->table) && $this->error)echo '<br><b>Keine Tabelle erstellt in Objekt ' . $this->id . '!</b><br>';
			$tmp=$this->table[$this->pos][$value];
			$this->param_out($tmp);
			//echo "<br><b>" . $value . '</b> ' . $tmp . ' <i>' . $this->pos . '</i>';
		}
		
		/**
		*@function: ITER = gives out a object to LIST-parameter
		*/
		public function &iter ()
		{return $this;}
			
		/**
		*
		*@function: MANY = many of rows
		*
		*/
		public function many()
		{
		
		return count( $this->table );
		}
		
		/**
		*
		*@parameter: INSERT = inserts an empty subtree in XMLTEMPLATE to current position as often as value is
		*
		*/
		public function insert($value){$this->insert = $value;}
		
		/**
		*
		*@parameter: NEW = creates a node, in a subtree
		*
		*/
		public function setNew($value){$this->tag[$this->cur]['new']=$value;}
		
		/**
		*
		*@parameter: MODUS = "list"(default) or "tree" to build up a list like <table><tr><td>booh1</td></tr>...</table> or a tree like <me><myfirst><one/><two/><myfirst><mysec> ... (is in development)
		*
		*/
		public function setModus($value){$this->modus=strtoupper($value);}
		
		/**
		*
		*@parameter: OUTPUT = Max many of rows to give out
		*
		*/
		public function setOutput($value){$this->numOfOut = $value - 1;}
		
		/**
		*
		*@parameter: ERR = error num on error
		*
		*/
		public function getErr()
			{
				return $generator->XMLlist->error_num();
			//$this->param_out($tmp );
			}
		
		/**
		*
		*@parameter: ERRDESC = error description
		*
		*/
		public function getErrDisc(){ return $generator->XMLlist->error_desc();}
		
		public function run()
		{//echo "\n ------------" . $this->template . "--------------- \n";
//echo "beginne " . $this->id . " \n";
			if($this->insert > 0)
			{
						
					if(is_null($this->template))return false;
					
					$generator->XMLlist->change_URI($this->template);
					$generator->XMLlist->set_first_node();
					$generator->XMLlist->cur_idx(). "id \n";
					
					//$generator->XMLlist->seek_node($this->tag[$this->order[$i]]['xpath']);
					$orginal = &$generator->XMLlist->show_xmlelement();
				
					
				
					$generator->XMLlist->change_URI($generator->template); //vorsicht ist ausgabexml
				
					$import = &$generator->XMLlist->show_xmlelement();
					
					$generator->XMLlist->change_URI($generator->structur);
					$generator->XMLlist->curtag_cdata($this->cdata);
					//echo $generator->XMLlist->cur_node();
					$clone_root = &$orginal->cloning($import);
					
					
				
				for($i=0;(($i < count($this->order)) || ($i < $this->insert ) && ($i < $this->numOfOut));$i++)
				{
					
					$clone = &$this->find($clone_root,$this->tag[$this->order[$i]]['xpath']);	
					/*
					if(is_null($clone->name))echo '<br>Das Element <b>&quot;' . $this->tag[$this->order[$i]]['xpath'] . '"&quot;</b> wurde nicht im Baum ' . $this->template . ' gefunden!<br>';
					//echo  $clone->name . ' - ' . $this->tag[$this->order[$i]]['xpath'];
					if($this->order[$i]['content'])
					{
					
					
					
						
					$this->rst->set('COL',$this->tag[$this->order[$i]]['content']);
					
					//echo $this->order[$i] . '-' . $this->tag[$this->order[$i]]['value'];
					if($this->tag[$this->order[$i]]['pos']=='ATTRIB')
					{
						
						if(!$this->tag[$this->order[$i]]['value'])
						$clone->attrib[$this->tag[$this->order[$i]]['name']] = $this->rst->out();
						else
						$clone->attrib[$this->tag[$this->order[$i]]['name']] = $this->tag[$this->order[$i]]['value'];
						
					}
					if($this->tag[$this->order[$i]]['pos']=='DATA')
					{
						$my_pos = 0;
						if($this->tag[$this->order[$i]]['datapos'])$my_pos = $this->tag[$this->order[$i]]['datapos'];
						if($this->rst->out() <> "")$clone->data[$my_pos] = $this->rst->out();
					
					}	
					
					}
					else
					{
					
										
					if($this->tag[$this->order[$i]]['pos']=='ATTRIB')
					$clone->attrib[$this->tag[$this->order[$i]['name']]] = $this->order[$i]['value'];
					
					if($this->tag[$this->order[$i]]['pos']=='DATA')
					
					$my_pos = 0;
					if($this->tag[$this->order[$i]]['datapos'])$my_pos = $this->tag[$this->order[$i]]['datapos'];
					$clone->data[$my_pos] = $this->order[$i]['value'];
					
					}
					*/
					unset($clone);
				}
					
					
					unset($orginal);
					unset($import);
			}
			if(!is_Null($this->rst))
			{
				//echo "bin beim uebertragen \n";
			$this->rst->set('MANY',null);
			if($this->rst->out()==0)
			{
				
				return false;
			}
				
				
			//echo "die Menge ist " . $this->rst->out() . "! \n";
			$numoflines = 0;
				do{
					
					
					if(is_null($this->template))return false;
					
					$generator->XMLlist->change_URI($this->template);
					$generator->XMLlist->set_first_node();
					$generator->XMLlist->cur_idx(). "id \n";
					
					//$generator->XMLlist->seek_node($this->tag[$this->order[$i]]['xpath']);
					$orginal = &$generator->XMLlist->show_xmlelement();
				
					
				
					$generator->XMLlist->change_URI($generator->template); //vorsicht ist ausgabexml
				
					
					//{
					//	return false;
					//}
					
					$import = &$generator->XMLlist->show_xmlelement();
					
					$generator->XMLlist->change_URI($generator->structur);
					$generator->XMLlist->curtag_cdata($this->cdata);
					//echo $generator->XMLlist->cur_node();
					
					
					
					
					

					$create_new_subtree = true;
					
					
				
				for($i=0;(($i < count($this->order)) || ($i < $this->insert ) );$i++)
				{
					
					$this->rst->set('COL',$this->tag[$this->order[$i]]['content']);
					
					//prohibits to create a new subtree, when the first value is equal to the previous
					$create_new_subtree = !($this->modus == 'TREE' && $prevValue[0] == $this->rst->out()) && $create_new_subtree;
					//echo $this->rst->out() . ' fuer uns alle' . "\n"; 
					if($create_new_subtree)
					{
						unset($clone_root);
						$clone_root = &$orginal->cloning($import);
						$create_new_subtree = false;
					}
					//------------------------------------------------------------------------------------
					
					//finds node in subtree
					$clone = &$this->find($clone_root,$this->tag[$this->order[$i]]['xpath']);	
					if(is_null($clone->name) && $this->modus <> 'TREE')echo '<br>Das Element <b>&quot;' . $this->tag[$this->order[$i]]['xpath'] . '"&quot;</b> wurde nicht im Baum ' . $this->template . ' gefunden!<br>';
					//echo  $clone->name . ' - ' . $this->tag[$this->order[$i]]['xpath'];
					
					
					if($this->order[$i]['content'])
					{
						
						//registers current value for next loop
						$prevValue[$i] = $this->rst->out();
						//-------------------------------------
					
					if($this->rst->out() <> null){
						
					//on param:new = String, create new node
					if($this->tag[$this->order[$i]]['new'] )
					{
						
						//&& $prevValue[$i] <> $this->rst->out()
						$newObj = $generator->XMLlist->getInstance($this->tag[$this->order[$i]]['new'],null);
						$clone->setRefnext($newObj);
						$newObj->setRefprev($clone);
						$clone->final_data();
						unset($clone);
						$clone = &$newObj;
						unset($newObj);
						
					}
					
					

					
					
					
						
					
					
					//
					//{
						//echo $this->rst->out() . "<br>\n";
					//echo $this->order[$i] . '-' . $this->tag[$this->order[$i]]['value'];
					
						if($this->tag[$this->order[$i]]['pos']=='ATTRIB')
							{
						
							$pre = '';
							$post = '';
						
							if($pick = $this->tag[$this->order[$i]]['pick'])
							{
								$pick = strtoupper($pick);
								if($pick = 'ADD')
								{
									$pre = $clone->attrib[$this->tag[$this->order[$i]]['name']];
									$post = '';
								}
							}
							
							
							
							if($this->tag[$this->order[$i]]['prefix'])$pre .= $this->tag[$this->order[$i]]['prefix'];
							if($this->tag[$this->order[$i]]['postfix'])$post .= $this->tag[$this->order[$i]]['postfix'];
						
							if(!$this->tag[$this->order[$i]]['value'])
							$clone->attrib[$this->tag[$this->order[$i]]['name']] = $pre . $this->rst->out() . $post;
							else
							$clone->attrib[$this->tag[$this->order[$i]]['name']] = $pre . $this->tag[$this->order[$i]]['value'] . $post;
						
							}
						if($this->tag[$this->order[$i]]['pos']=='DATA')
								{
								$my_pos = 0;
								if($this->tag[$this->order[$i]]['datapos'])$my_pos = $this->tag[$this->order[$i]]['datapos'];
								if($this->rst->out() <> "")$clone->data[$my_pos] = $this->rst->out();
					
								}	
							}//null sperre
							}
							else
							{
					
										
								if($this->tag[$this->order[$i]]['pos']=='ATTRIB')
								$clone->attrib[$this->tag[$this->order[$i]['name']]] = $this->order[$i]['value'];
					
								if($this->tag[$this->order[$i]]['pos']=='DATA')
					
								$my_pos = 0;
								if($this->tag[$this->order[$i]]['datapos'])$my_pos = $this->tag[$this->order[$i]]['datapos'];
								$clone->data[$my_pos] = $this->order[$i]['value'];
					
							}
						
				
					
						unset($clone);
					}
					
					
					unset($orginal);
					unset($import);
					
				}while($this->rst->next() && $numoflines++ < $this->numOfOut);
				
				//leeren
				$this->rst->reset();
				$this->reset();
				//echo "erledigt\n";
			}
			
			//bearbeitung für ausgabeabfragen
			if(!is_Null($this->collection))
			{
				//echo "bin beim sammeln \n";
				
				
				$generator->XMLlist->change_URI($this->template);
				
				$generator->XMLlist->set_first_node();
				
				
				//echo $generator->XMLlist->ALL_URI() . $generator->XMLlist->cur_idx() . $generator->XMLlist->cur_node();
				//echo $generator->XMLlist->cur_node();
				if(!$generator->XMLlist->seek_node($this->collection))
				{
					if($this->error)echo '<br><b>Fehler</b> Element:' . $this->collection . ' nicht im Baum ' . $this->template . ' gefunden <br>';
					return false;
				}
				//echo $generator->XMLlist->cur_node() . " = " . $generator->XMLlist->index_child() . "\n";
				
				//echo $generator->XMLlist->cur_node() . " " . $generator->XMLlist->index_child(). "\n";
				$increm = 0;
				for($j = 0;($j < $generator->XMLlist->index_child()) && ($j < $this->numOfOut); $j++)
				{
					
					$generator->XMLlist->child_node($j);
					$stamp3 = $generator->XMLlist->position_stamp();
					//echo $j . ' ' . $generator->XMLlist->cur_node() .  '<p>';
					
				
					
				if(is_null($this->void) || ($generator->XMLlist->cur_node() == $this->void))
				{
					
				for($i=0;$i < count($this->order);$i++)
				{
					//echo $j . ' ' . $i . ' ' . $generator->XMLlist->cur_node() .  '<p>';
					$stamp4 = $generator->XMLlist->position_stamp();
					$generator->XMLlist->only_child_node(true);
					
					
					if($generator->XMLlist->seek_node(
						$this->xPath_name($this->tag[$this->order[$i]]['xpath']),
						$this->xPath_attrib($this->tag[$this->order[$i]]['xpath'])))
					{
						
						if($this->tag[$this->order[$i]]['pos']=='ATTRIB')
						{
						
							
							$this->table[$increm][$this->order[$i]] = $generator->XMLlist->show_cur_attrib($this->tag[$this->order[$i]]['name']);
						
						}
						
						//echo $this->tag[$this->order[$i]]['pos'] . ' <b>' . $this->order[$i] . '</b><br>';
						
						if($this->tag[$this->order[$i]]['pos']=='DATA')
						{
						
							//echo $generator->XMLlist->cur_node();
							$this->table[$increm][$this->order[$i]] = $generator->XMLlist->show_cur_data();
						//echo '<b>' . $this->table[$j][$this->order[$i]] . '</b>';
						//echo ' ' . $j . ' ' . $this->order[$i] . '<br>';
						}
						
						if($this->tag[$this->order[$i]]['pos']=='VALUE')
						{
						//echo $generator->XMLlist->cur_node();
							$this->table[$increm][$this->order[$i]] = $this->tag[$this->order[$i]]['value'];
						//echo '<b>' . $this->table[$j][$this->order[$i]] . '</b>';
						//echo ' ' . $j . ' ' . $this->order[$i] . '<br>';
						}
						//$this->table[$j][$this->order[$i]]
						
					}
					
					
					
					$generator->XMLlist->go_to_stamp($stamp4);
					$generator->XMLlist->only_child_node(false);
				}
				
				$increm++; //zählt auf den nächsten satz hoch
				}

					
			//echo $this->XMLlist->cur_node(); content
					$generator->XMLlist->go_to_stamp($stamp3);
					$generator->XMLlist->parent_node();
				}
				
				$this->collection = null;
			}
			
		}	
	
	
	function &find(&$xml_obj,$name)
	{	
//		echo $name . ' ' . $xml_obj->name . ' ' . $xml_obj->attrib['CLASS'] . ' <br>';
		//echo $name;
		$attrib = $this->xPath_attrib($name);
				$hit = (count($attrib) == 0);
		foreach ($attrib as $key => $value) {
			//echo $key . ' ' . $value . "\n";
			//echo $xml_obj->attrib[$key] . "als $key \n";
			if ($xml_obj->attrib[$key] == $value) $hit = true;
                                                    }


		if($xml_obj->name <> $this->xPath_name($name) || !$hit)
		{

			
			if(!$xml_obj)
			{
				echo "knoten nicht gefunden:" . $this->xPath_name($name) . "<br>\n";
				return false;
			}
			
			//echo $xml_obj->index_max() . ' <br>';
			for($i=0;$i < $xml_obj->index_max(); $i++)
			{
				//echo $i . ' übergebe nächstes Element<br>';
				$tmp = &$this->find($xml_obj->getRefnext($i),$name);
				//echo "raus";
				if($tmp->name == $this->xPath_name($name))return $tmp;
			}
		}
		else
		{
			//echo '<br>Das Element <b>&quot;' . $name . '"&quot;</b> wurde nicht im Baum gefunden!<br>';
			return $xml_obj;
		}
	}
	
	function xPath_attrib($string)
		{
		
			
			if(false === ($tmp = strpos($string,'[')))
			{
				return array();
			}else
			{
				
				$attrib = substr($string,$tmp);
				
				$attrib = substr($attrib,1,1);
				
				
				if(is_numeric($attrib))
				{
				return array('@number' => $attrib);
				}
				
				$key = substr($string,$pos1 = (strpos($string,'@') + 1),strpos($string,'=') - $pos1);
				$value = substr($string,$pos2 = ((strpos($string,"'") + 1 )),strpos($string,"'",$pos2) - ($pos2));
				return array($key => $value);
			}
		}
	
	function xPath_name($string)
		{
			if(false === ($tmp = strpos($string,'[')))
			{
				return $string;
			}else
			{
				return substr($string,0,$tmp);
			}
		}
	
	function check_type($type)
	{
	if($type == "SQL")return true;
	if($type == "XMLTEMPLATE")return true;
	if($type == "COL")return true;
	//if($type == "")return true;
	return parent::check_type($type);
	}

	function next()
	{
	
	return (count($this->table) > ++$this->pos);
	}
	
	function reset()
	{
		$this->table = null;
		$this->tag = array();
		$this->rst = null;
		$this->pos = 0;
	}
	
	function decription(){return "no description avaiable!";}
}
?>
