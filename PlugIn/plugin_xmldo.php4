<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/
require_once("plugin_interface.php");

class XMLDO extends plugin 
{
var $template;
var $rst;
var $tag = array();
var $cur;
var $order = array();
var $collection = null; //tag, unter dem gesucht wird
var $table = null; //tabelle an gesammelten inhalten
var $pos = 0;
var $cdata = false;
	function XMLDO(){}
	
	function set($type, $value)
	{
	$generator = &$this->generator();
		parent::set($type, $value);
		
		if($type == "XMLTEMPLATE")
		{
			
			$this->template = $generator->heap['template'][$value];
			if(is_null($this->template))echo '<br><b>das Template ist nicht verf&uuml;gbar:' . $value . '</b><br>';
		}
		if($type == "LIST")
		{
			
			if(is_object($value))
			{
				$this->rst = &$value;
				
			}
		}

		if($type == "COLLECTION" )
		{
			$this->collection = $value;
		}
		if($type == "CDATA")
		{
			
			$this->cdata = true;
			
			
		}
		
		if($type == "TAG_IN")
		{
			
			$this->cur = $value;
			$this->order[count($this->order)] = $value;
			
		}
		if($type == "TAG_OUT")
		{
			
			$this->cur = '';
			
			
		}
		if($type == "XPATH")
		{
			
			$this->tag[$this->cur]['xpath']=$value;
			
			
		}
		if($type == "ATTRIB")
		{
			
			$this->tag[$this->cur]['pos']='ATTRIB';
			$this->tag[$this->cur]['name']=$value;
			
		}
		if($type == "DATA")
		{
			//echo 'boooh' . $this->cur;
			$this->tag[$this->cur]['pos']='DATA';
			
			
		}
		if($type == "CONTENT")
		{
			
			$this->tag[$this->cur]['content']=$value;
			
			
		}
		if($type == "VALUE")
		{
			//$this->tag[$this->cur]['pos']='VALUE';
			$this->tag[$this->cur]['value']=$value;
			
			
		}


		
		if($type == "COL")
		{
			
			if(is_null($this->table))echo '<br><b>Keine Tabelle erstellt!</b><br>';
			$tmp=$this->table[$this->pos][$value];
			$this->param_out($tmp);
			//echo "<br><b>" . $value . '</b> ' . $tmp . ' <i>' . $this->pos . '</i>';
		}
		
		if($type == "ITER"){$this->param_out($this);}
		
		if($type == "MANY"){$this->param_out(count( $this->table ));}
		
		if($type == "ERR")
			{
				$tmp = $generator->XMLlist->error_num();
			$this->param_out($tmp );
			}
		
		if($type == "ERRDESC"){$this->param_out($generator->XMLlist->error_desc() );}
		
		if($type == "RUN")
		{
			if(!is_Null($this->rst))
			{
				
			
				
				//function nicht vllstaändig xpath fehlt
				do{
					
					
					if(is_null($this->template))return false;
					
					$generator->XMLlist->change_URI($this->template);
					$generator->XMLlist->set_first_node();
					
					
					//$generator->XMLlist->seek_node($this->tag[$this->order[$i]]['xpath']);
					$orginal = &$generator->XMLlist->show_xmlelement();
				
					
				
					$generator->XMLlist->change_URI($generator->template); //vorsicht ist ausgabexml
				
					$import = &$generator->XMLlist->show_xmlelement();
					
					$generator->XMLlist->change_URI($generator->structur);
					$generator->XMLlist->tag_cdata($this->cdata);
					
					$clone_root = &$orginal->cloning($import);
					
					
				
				for($i=0;$i < count($this->order);$i++)
				{
					$clone = &$this->find($clone_root,$this->tag[$this->order[$i]]['xpath']);	
					if(is_null($clone->name))echo '<br>Das Element <b>&quot;' . $this->tag[$this->order[$i]]['xpath'] . '"&quot;</b> wurde nicht im Baum gefunden!<br>';
					//echo  $clone->name . ' - ' . $this->tag[$this->order[$i]]['xpath'];
					if(!is_null($this->order[$i]['content']))
					{
					
					
					
						
					$this->rst->set('COL',$this->tag[$this->order[$i]]['content']);
					echo $this->tag[$this->order[$i]]['pos'];
					if($this->tag[$this->order[$i]]['pos']=='ATTRIB')
					{
						$clone->attrib[$this->tag[$this->order[$i]]['name']] = $this->rst->out();
						
						
					}
					if($this->tag[$this->order[$i]]['pos']=='DATA')
					{
					
					$clone->data[0] = $this->rst->out();
					
					}	
					
					}
					else
					{
					
										
					if($this->tag[$this->order[$i]]['pos']=='ATTRIB')
					$clone->attrib[$this->tag[$this->order[$i]['name']]] = $this->order[$i]['value'];
					
					if($this->tag[$this->order[$i]]['pos']=='DATA')
					$clone->data[0] = $this->order[$i]['value'];
					}
					
					unset($clone);
				}
					
					
					unset($orginal);
					unset($import);
				}while($this->rst->next());
				
			}
			
			//bearbeitung für ausgabeabfragen
			if(!is_Null($this->collection))
			{
				

				$generator->XMLlist->change_URI($this->template);
				
				$generator->XMLlist->set_first_node();
				//echo $generator->XMLlist->ALL_URI() . $generator->XMLlist->cur_idx() . $generator->XMLlist->cur_node();
				
				if(!$generator->XMLlist->seek_node($this->collection))
				{
					echo '<br><b>Fehler</b> Element:' . $this->collection . ' nicht im Baum ' . $this->template . ' gefunden <br>';
					return false;
				}
				
				for($j = 0;$j < $generator->XMLlist->index_child(); $j++)
				{
					
					$generator->XMLlist->child_node($j);
					$stamp3 = $generator->XMLlist->position_stamp();
					//echo $j . ' ' . $generator->XMLlist->cur_node() .  '<p>';
					
				for($i=0;$i < count($this->order);$i++)
				{
					//echo $j . ' ' . $i . ' ' . $generator->XMLlist->cur_node() .  '<p>';
					$stamp4 = $generator->XMLlist->position_stamp();
					$generator->XMLlist->only_child_node(true);
					if($generator->XMLlist->seek_node($this->tag[$this->order[$i]]['xpath']))
					{
						
						if($this->tag[$this->order[$i]]['pos']=='ATTRIB')
						{
						$this->table[$j][$this->order[$i]] = $generator->XMLlist->show_cur_attrib($this->tag[$this->order[$i]]['name']);
						
						}
						
						//echo $this->tag[$this->order[$i]]['pos'] . ' <b>' . $this->order[$i] . '</b><br>';
						
						if($this->tag[$this->order[$i]]['pos']=='DATA')
						{
						//echo $generator->XMLlist->cur_node();
							$this->table[$j][$this->order[$i]] = $generator->XMLlist->show_cur_data();
						//echo '<b>' . $this->table[$j][$this->order[$i]] . '</b>';
						//echo ' ' . $j . ' ' . $this->order[$i] . '<br>';
						}
						
						if($this->tag[$this->order[$i]]['pos']=='VALUE')
						{
						//echo $generator->XMLlist->cur_node();
							$this->table[$j][$this->order[$i]] = $this->tag[$this->order[$i]]['value'];
						//echo '<b>' . $this->table[$j][$this->order[$i]] . '</b>';
						//echo ' ' . $j . ' ' . $this->order[$i] . '<br>';
						}
						//$this->table[$j][$this->order[$i]]
						
					}
					
					$generator->XMLlist->go_to_stamp($stamp4);
					$generator->XMLlist->only_child_node(false);
				}
					
					
					
			//echo $this->XMLlist->cur_node(); content
					$generator->XMLlist->go_to_stamp($stamp3);
					$generator->XMLlist->parent_node();
				}
			}
			
		}	
	}
	
	function &find(&$xml_obj,$name)
	{
		
		if($xml_obj->name <> $name)
		{
			//echo $xml_obj->index_max() . ' <br>';
			for($i=0;$i < $xml_obj->index_max(); $i++)
			{
				//echo $i . ' <br>';
				$tmp = &$this->find($xml_obj->getRefnext($i),$name);
			
				if($tmp->name == $name)
				{
					
					return $tmp;
				}
			}
		}
		else
		{
			
			//echo '<br>Das Element <b>&quot;' . $name . '"&quot;</b> wurde nicht im Baum gefunden!<br>';
			return $xml_obj;
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
	
	function decription(){return "no description avaiable!";}
}
?>
