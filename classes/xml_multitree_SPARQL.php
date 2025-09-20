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


class xml_sparqle extends xml_ns
{
private $query = null;
	/*
	public function xpath($statement)
	{
		$logger_class->setAssert('          Statement:' . $statement . '(xml_sparqle:constructor)' ,10);
	}
	*/
	
	
	/**
	*
	*  <p></p>
	*  
	*  @author Stefan Wegerhoff
	*  @version 2012-04-02
	*  @method void multitree( object &$multi)
	*  @method void seach()
	*  
	*/
	public function sparql_command($statement)
	{
		//$this->test_consistence();
		//$this->index_consistence();
		$this->query = new Statement($statement);
		$this->query->set_Multitree($this);
		$this->query->init();
		
	}
	
	public function &sparql_result()
	{
		return $this->query->get_result();
	}

	  
}

/** <h1>Triplet</h1>
*
*  <p>is a request for just on statement and contains a subject, predicate and object.
*  otherwise, it is not valid</p>
*  @author Stefan Wegerhoff
*  @version 2012-04-02
*  @method void multitree( object &$multi)
*  @method void seach()
*  
*/

class Triplet
{
	private $subj = null;
	private $pred = null;
	private $obj = null;
	private $multi = null;
	private $connect_to_group = null;

	
	/** 
	*
	*  @param String $sub
	*  @param String $pred
	*  @param String $obj
	*  @param GroupElement &$group
	*/
	public function __construct($sub,$pred,$obj, &$group)
	{
	$this->subj = new P_Subject($sub);
	$this->pred = new P_Predicat($pred);
	$this->obj = new P_Object($obj);
	$this->connect_to_group = &$group;
	
	$tmp = &$group->get('SELECTElement');
	
	
	if($this->subj->is_indeterminate())
		for($i = 0; $i < count($tmp);$i++) 
	 		if( $tmp[$i]->getName() == $this->subj->getURI() )
	 			$this->subj->getIndeterminateValue( $tmp[$i]);
	
	if($this->pred->is_indeterminate())
		for($i = 0; $i < count($tmp);$i++) 
	 		if( $tmp[$i]->getName() == $this->pred->getURI() )
	 			$this->pred->getIndeterminateValue( $tmp[$i]);
	
	if($this->obj->is_indeterminate())
		for($i = 0; $i < count($tmp);$i++) 
	 		if( $tmp[$i]->getName() == $this->obj->getURI() )
	 			$this->obj->getIndeterminateValue( $tmp[$i]);
			
			
			
 
	
	$this->pred->setSub($this->subj);
	$this->pred->setObj($this->obj);
	

	
	
	}
	
	
	/** 
	*
	*  @param multitree_ns $multi
	*  @return GroupElement &$group
	*/
	public function multitree(&$multi){$this->multi = &$multi;}
	
	/** 
	* 
	*  
	*/
	public function seach()
	{
		
		$this->pred->seach($this->multi);
	}
}

/** <h1>Gramma_Function</h1>
*
*  <p>grammar element to inherit from.</p>
*  @author Stefan Wegerhoff
*  @version 2012-04-02
*  @method String getURI()
*  @method integer getMany() 
*  @method Interface_node &getByIndex(integer $index)
*  @method void deleteByIndex(integer $index)
*  @method void process_complete()
*  @method boolean findObjInArray( Interface_node &$obj)
*  @method boolean setForContainment( Interface_node &$obj_ref)
*/
class Gramma_Function
{	
	private $uri = '';
	private $unknown = false;
	private $res = array();
	protected $delete = array();
	private $select_element = NULL;

	public function __construct($name)
	{
		$this->uri = $name;
		$this->unknown = ('?' == substr($name,0,1));
	}
	
	public function getURI(){return $this->uri;}
	protected function getMany(){return count($this->res);}
	protected function &getByIndex($index){return $this->res[$index];}
	protected function deleteByIndex($index){$this->delete[$index] = true;}
	protected function process_complete(){if(!is_null($this->select_element))$this->select_element->complete(); }
	protected function findObjInArray(&$obj)
	{
		for($i = 0;$i < count($this->res);$i++)
		{
			if($this->res[$i] === $obj)return true;;
		}
	}
	
	protected function setForContainment(&$obj_ref)
	{
		
		if(!is_null($this->select_element))
		{
			if($this->select_element->is_first_set())
			{	//echo ' wird gespeichert ' . $obj_ref . ' (' . get_Class($obj_ref) . ') ' . " <br>\n";
				$this->res[] = &$obj_ref;
				return true;
			}
			else
			{
				
				return $this->findObjInArray($obj_ref);
			}
			
		
		}
		else
		{
		
		return $obj_ref->is_Node($this->uri);
		}

	
	}
	
	public function is_indeterminate(){return (substr( $this->uri , 0, 1 ) == '?');}
	
	public function getIndeterminateValue(&$selectel)
	{ 
	
	$this->res = &$selectel->array_ref();
	$this->select_element = &$selectel;
	//echo get_Class($this->select_element) . ' ';
	}
	
	public function &result(){return $this->res;}
	
	protected function &find_all_subclasses(&$base)
	{
		$collection = array();
		$h = 0;
		$obj = &$base->get_Class_of_Namespace($this->uri);
		$collection[$h++] = &$obj;
		
		if(is_object($obj) && is_object($obj->linkToClass()))
		{
			$res = &$this->collect_subclasses($obj->linkToClass());
			for($i = 0; $i < count($res);$i++)
			{
				$collection[$h++] = &$res[$i];
			}
		}
		
		return $collection;
	}
	
	private function collect_subclasses(&$tree)
	{
				//$obj->linkToClass()->giveOutOverview();
		$arr = &$tree->get_in_ref(); //->linkToClass()
		$res = array();
		$h = 0;
			for($i = 0;$i < count($arr);$i++)
			{	
				
				
				
				if(!is_object($tmp = &$arr[$i]->getRefprev()))
				{
				echo "Inkonsistenz festgestellt (xml_multitree_SPARQL.php:170)";
				//TODO Exception
				countinue;
				}
				if(!is_object($tmp2 = &$tmp->getRefprev()))
				{
				echo "Inkonsistenz festgestellt (xml_multitree_SPARQL.php:170)";
				//TODO Exception
				countinue;
				}
				
				
				if($tmp2->ManyInstance() != 1)
				{
				echo "Inkonsistenz festgestellt (xml_multitree_SPARQL.php:170)";
				//TODO Exception
				countinue;
				}
				
				//$tmp2->linkToInstance(0)->giveOutOverview();
				
				$res[$h++] = &$tmp2->linkToInstance(0);
				
				$collection = &$this->collect_subclasses($tmp2);
				
				for($j = 0;$j < count($collection);$j++)
				{
				$res[$h++] = &$collection[$j];
				}
				
				unset($tmp);
				unset($tmp2);
			}
		return $res;
	}
	
	protected function &collect_all_Instances(&$array)
	{
		$res = array();
		$h = 0;
		
		for($i = 0; $i < count($array);$i++)
		{
			for($j = 0; $j < $array[$i]->ManyInstance();$j++)
			{
				$res[$h++] = &$array[$i]->linkToInstance($j);	
			}
		}
		
		return $res;
	}
	
	
	public function seach(&$base)
	{	//echo '-->' . $this->uri . "\n";
		/*
		$base->flash_result();
		$base->seek_node( $this->uri );
		$tmp = &$base->get_result();
		for($i = 0;$i< count($tmp);$i++)
		$this->res[$i] = &$tmp[$i];
		
		for($i = 0;$i< count($this->res);$i++)
		  $this->delete[$i] = false;
		$base->flash_result();
		*/
		$collection = &$this->find_all_subclasses($base);
		$this->res = &$this->collect_all_Instances($collection);
		
			for($i = 0; $i < count($this->res);$i++)
			{
				$this->delete[$i] = false;
				//$instances[$i]->giveOutOverview();
			}
		
	}
}

/** Subject
*
*  classical grammar Subject 
*  inherits from Gramma_Function
*  @author Stefan Wegerhoff
*  @version 2012-04-02
*  
*/
class P_Subject extends Gramma_Function{}

/** Predicat
*
*  classical grammar Predicat
*  inherits from Gramma_Function
*  @author Stefan Wegerhoff
*  @version 2012-04-02  
*/
class P_Predicat extends Gramma_Function
{
	private $sub = null;
	private $obj = null;
	
	public function setSub(&$sub){$this->sub = &$sub;}
	public function setObj(&$obj){$this->obj = &$obj;}
	
	
	public function seach(&$base)
	{	
		
		if(!$this->is_indeterminate() )
		{
		parent::seach($base);
		$bool_statement = array();
		$bool_statement[0] = $this->sub->is_indeterminate();
		$bool_statement[1] = $this->obj->is_indeterminate();
		
		
		if($bool_statement[0] && !$bool_statement[1])echo "erster";
		if(!$bool_statement[0] && $bool_statement[1])
		{	
//getMany(){return count($this->res);}
//&getByIndex($index){return $this->res[$index];}
//deleteByIndex($index){$this->delete[$index] = true;}
			for($i = 0;$i < $this->getMany();$i++)
			{
				//echo  $this->getByIndex($i)->full_URI() . ' ';
				if($this->sub->setForContainment($this->getByIndex($i)->getRefprev()))
				{
					//$this->getByIndex($i)->giveOutOverview();
					if( $this->getByIndex($i)->index_max() != 0 )
					for($j = 0; $j < $this->getByIndex($i)->index_max(); $j++)
					$this->obj->setForContainment($this->getByIndex($i)->getRefnext($j));
					else
					
					for($j = 0; $j < $this->getByIndex($i)->data_many(); $j++)
					//echo 'jo';
					//echo $this->getByIndex($i)->getdata($j) . ' type:'  . gettype($this->getByIndex($i)->getdata($j)) . '  name:' .  get_class( $this->getByIndex($i)->getdata($j) ) . "<br> \n";
					$this->obj->setForContainment( $this->getByIndex($i)->getdata($j) );
					
					$this->deleteByIndex($i);
				}
			}
			
		//echo $this->getMany() . ' ';
		//setForContainment // setForContainment
		}
		if($bool_statement[0] && $bool_statement[1])echo "beide";
		
		//echo $this->getMany() . ' ';
		//$this->sub->askForContainment($this);
		//$this->obj->askForContainment($this);
		}
		elseif(!$this->sub->is_indeterminate() )
		{;}
		elseif(!$this->obj->is_indeterminate() )
		{;}
		

	}
}

/** Object
*
*  classical grammar Object
*  inherits from Gramma_Function
*  @author Stefan Wegerhoff
*  @version 2012-04-02  
*  
*/
class P_Object extends Gramma_Function{}


/** StatementElement
*
*  abstract element for warranted methods
*  inherits from Gramma_Function
*  @author Stefan Wegerhoff
*  @version 2012-04-02
*  @method String stuffInBraces(String $string)
*  
*/
abstract class StatementElement
{

	private $object_col = array();	
	protected $parentRef = null;
	
	
	abstract protected function &setRef();
	abstract protected function init();
	protected function stuffInBraces($string)
	{
	 $pos;
	 $open = 0;
	 $lines = array();
	 $res = array();
	 $iter = 0;
		for($i = 0; $i < strlen($string);$i++)
		{
			if(substr( $string, $i, 1 ) == '{' )
			{
				
				if( !is_array($lines[$iter]) )$lines[$iter] = array();
				if($open == 0)
				{
				
				$lines[$iter][0] = $i + 1;
				}
				 
				$open++;
				
			}
			
			
			
			if(substr( $string, $i, 1 ) == '}' )
			{
				
				$open--;
				
				if($open == 0)
				{
				$lines[$iter++][1] = $i;
				
				}
				
			}
		
		
		}
		
		for($j = 0; $j < count($lines);$j++)
		{
			
					
					$res[] = substr($string, $lines[$j][0], $lines[$j][ 1 ] - $lines[$j][ 0 ] );
		}
		
		return $res;
	}

	protected function hasBraces($string)
	{}

	protected function add(&$obj)
	{
		

		if(!is_Array($this->object_col[get_class($obj)]))$this->object_col[get_class($obj)] = array();
		$this->object_col[get_class($obj)][] = &$obj;
		$obj->parentRef = &$this->setRef();
		
	}
	
	public function &get($name)
	{
		return $this->object_col[$name];
	}
	

	
	
	
}

class Statement extends StatementElement
{

	private $multitree_ref = null;
	 

	public function __construct($statement)
	{	
	
	$base = '';	
	$usedNamespace = array();
	$select_requests = array();
	$where_request = array();
	
	$prefix =  explode ( 'PREFIX' , $statement  );
	
	
	
	$select =  explode ( 'SELECT' , $prefix[count($prefix) - 1]  );
	
	if(!(false === ($pos = strpos($prefix[count($prefix) - 1],'SELECT')))) $prefix[count($prefix) - 1] = substr($prefix[count($prefix) - 1], 0, $pos);
	
	for($i = 0; $i< count($prefix); $i++)$prefix[$i] = trim( $prefix[$i] );
	
	$where =   explode ( 'WHERE' , $select[count($select) - 1]  );
	
	if(!(false === ($pos = strpos($select[count($select) - 1],'WHERE')))) $select[count($select) - 1] = substr($select[count($select) - 1], 0, $pos);
	
	for($i = 0; $i< count($select); $i++)$select[$i] = trim( $select[$i] );
	
	for($i = 0; $i< count($where); $i++)$where[$i] = trim( $where[$i] );
	
	for($i = 0; $i< count($prefix); $i++)
	{
	
	$prefix[$i] = trim( $prefix[$i] );
	if($prefix[$i] <> '')
	{
	
	if(!(false === strpos($prefix[$i],'BASE')))
	{
	  $str = strpos($prefix[$i],'<') + 1; 
	  $sto = strpos($prefix[$i],'>');
	  $base = substr($prefix[$i], $str, $sto - $str );
	  continue;
	}
	 
	
	$tmp = explode(' ', $prefix[$i]);
	$tmp[0] = str_replace( ':' , '', $tmp[0]);
	$usedNamespace[trim($tmp[0])] = trim($tmp[1]);
	}
	}
	
	$h = 0;
	for($i = 0; $i< count($where); $i++)
	{
	
	$where[$i] = trim( $where[$i] );
	if($i == 0)
	{
	
	$tmp = explode(' ', $where[$i]);
	for($j = 0; $j < count($tmp);$j++)
	$select_requests[$h++] = trim($tmp[$j]);
	}
	else
	{
	$where_request = $this->stuffInBraces(trim($where[$i]));
	}
	}
	
	$where2 = $this->stuffInBraces(trim($where_request[0]));
	
	
	
	$this->add( new PrefixElement( $usedNamespace , $base));
	for($i = 0;$i < count($select_requests);$i++)
	$this->add( new SELECTElement( $select_requests[$i], $this ));
	$this->add( new GroupElement($where_request[0], $this ));
	//$prefix, $select, $where,
	
	
	
	
	}
	
	public function init()
	{
	
		$select = &$this->get('SELECTElement');
		
		for($i = 0; $i < count($select); $i++)
		$select[$i]->init();
	
		$group = &$this->get('GroupElement');
		
		for($i = 0; $i < count($group); $i++)
		$group[$i]->init();
		
	}
	
	public function &get_result()
	{
		$res = new rst();
		$empty_string = '';
		//$res->setField($Field,$Type,$Null,$Key,$Default,$Extra);
	
		$test = &$this->get('SELECTElement');
		$max = 0;
		for($j = 0; $j < count($test);$j++)
		{
		$res->setField('sparql.' . $test[$j]->getName(),'Object',true,false,'','sparql');
		if( $max < $test[$j]->getMax() ) $max = $test[$j]->getMax();
		}
		
		
		
		for($j = 0; $j < $max;$j++)
		{
		for($i = 0; $i < count($test);$i++)
		{
		if($test[$i]->getMax() > $j)
		{
		//echo ' (' . $test[$i]->getMax() . ') sparql.' . $test[$i]->getName() . ' ' . $test[$i]->get_value($j) . " \n";
		$res->setValue('sparql.' . $test[$i]->getName(),$test[$i]->get_value($j),null,true);
		}
		else 
		{
		//echo 'sparql.' . $test[$i]->getName() . ' ' . " \n";
		$res->setValue('sparql.' . $test[$i]->getName(),$empty_string,null,true);
		
		}
		
		}
		$res->update();
		//$test[$j]->test();
		//echo $test[$j]->getName() . ' ';
		}
		
	return $res;
	}
	
	protected function &setRef(){return $this;}
	
	public function set_Multitree(&$multi)
	{
		$this->multitree_ref = &$multi;
	}
	
	public function &get_Multitree()
	{
		return $this->multitree_ref;
	}
	
	

}

class BaseElement extends StatementElement
{
	protected function &setRef(){return $this;}
	protected function init(){}
}

class PrefixElement extends StatementElement
{
	private $prefixes = array();
	private $namespaces = array();
	private $base = false;

	public function __construct(&$prefix, $mybase)
	{
	
	$this->base = $mybase;
	
	 $this->prefixes[] = ':';
	 $this->namespaces[] =  '#';
	
	 $this->prefixes[] = '.';
	 $this->namespaces[] =  '*';

	
	  foreach ($prefix as $key => $value)
	  {
	  	$this->prefixes[] = $key;
	  	$this->namespaces[] =  str_replace(array('<', '>'), array('', ''), $value);
	  }
	}
	
	public function addPrefixes($tiple)
	{
		return str_replace($this->prefixes, $this->namespaces, $tiple);
	}
	
	public function removeChars($tiple)
	{
		return str_replace( $this->namespaces, $this->prefixes, $tiple);
	}
	
	protected function init(){}
	
	protected function &setRef(){return $this;}
}

$running = 0;

class SELECTElement extends StatementElement
{
	private $select;
	private $base = null;
	private $contains = array();
	private $first_set = true;
	public $num = 0;

	public function __construct($select, &$base)
	{
		global $running;
		$this->num = $running++;
		$this->base = &$base;
		$this->select = $select;
	}
	
	public function getName(){return $this->select;}
	public function getMax(){return count($this->contains);}
	public function complete(){$this->first_set = false;}
	public function is_first_set(){return $this->first_set;}
	public function test()
	{
		echo 'Das SelectElement \'' . $this->select . '\' enthaelt ' . count($this->contains) . " Eintraege<br>\n";
		//for($i = 0;$i < count($this->contains);$i++)
		//echo "\n -" . $this->contains[$i]->full_URI() . " ";
	}
	
	protected function init()
	{ 
	
	//$this->base->get_Multitree()->;
	//echo $this->getName() . ' contains ' . count($this->contains) . ' ';
	//echo $this->select;
	
	}
	
	protected function &setRef(){return $this;}
	public function &array_ref(){return $this->contains;}
	public function &get_value($pos){return $this->contains[$pos];}
}

class GroupElement extends StatementElement
{
	private $group_string = '';
	private $tribs = array();
	private $base = null;
	private $pref_ref = null;
	private $triplet = array();
	
	public function __construct(&$group, &$base)
	{
	$this->base = &$base;
	
	$pref_arr = $base->get( 'PrefixElement' );
	
	if(count($pref_arr) > 0)
		{
		$this->pref_ref = $pref_arr[0];
		$this->group_string = $this->pref_ref->addPrefixes($group);
		
		
		}
	//var_dump($this->group_string);
	}

	
	protected function init()
	{
	
		//TODO validierung beim parsen fehlt
	$copy = str_replace ( '*' , '* ' , $this->group_string );
	$tmp =  explode ( ' ' , $copy  );
	$h = 0;
	$j = 0;
	for($i = 0; $i < count($tmp);$i++)
	{
		$tmp[$i] = trim($tmp[$i]);
		if($tmp[$i] == '*')
		{
		
		$this->triplet[] = new Triplet(
		$this->tribs[$h][0],
		$this->tribs[$h][1],
		$this->tribs[$h][2],
		$this->base);
		
		$h++;
		if($j != 3)
		throw new Exception("Triple $h (" . $this->tribs[$h - 1][$j - 1] . ") is not well formed triple ");		
		$j = 0;

		}
		if(!is_array($this->tribs[$h]))$this->tribs[$h] = array();
		if(strlen($tmp[$i]) > 1)
		$this->tribs[$h][$j++] = $tmp[$i];
		

		
		
		
		
	}
	
	unset($this->tribs[count($this->tribs ) - 1]);
	
	for($i = 0; $i < count($this->triplet);$i++)
	{
	$this->triplet[$i]->multitree( $this->base->get_Multitree() );
	$this->triplet[$i]->seach();
	
	}
		
	//var_dump($this->tribs);
	}
	
	protected function &setRef(){return $this;}
}

?>
