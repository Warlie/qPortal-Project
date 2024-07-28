<?php

/*
*-----------------------------------------------------------------------
*
*-----------------------------------------------------------------------
*/
require_once('PlugIn/plugin_log.php');

$logger_class = new Logger();

function get_Clazz(&$clazz)
{
	return get_Class($clazz);
}



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
*    show_cur_attrib($attrib = null) :Zeigt Attribute an
*    show_ns_attrib($attrib = null)
*    many_cur_data()
*    show_cur_data([position]) :Gibt einen bestimmten Datenknoten oder alle Datenknoten an
*    many_cur_data() :Gibt die menge der Datenknoten an
*
*    position_stamp() : Gibt eine Positionsmarke mit Hash-Kontrollziffer (Ziffer noch nicht funktionsfähig)
*    go_to_stamp(stamp) : Geht zur marke
*
*
*    only_child_node($bool_node) : für seek_node -> sucht dann nur alles unter dem aktuellen knoten
*    seek_node([String $type],assoz array [attrib],string [data]) : sucht einen Knoten
*
*    create_Ns_Node($prefix_Q_name, $stamp = null, array $attrib = null )
*    create_node(stamp,[pos=null]) : erstellt einen neuen knoten
*    set_node_name(name) : gibt einem Knoten einen Namen
*    set_node_attrib(key,value) : vergibt Attribute an einen Knoten
*    set_node_cdata(value,counter) : vergibt daten an einen Knoten
*    clear_node_cdata($pos=null) :loescht cdata inhalt
*
*    load(Dateiname) : läd xml.datei
*    load_Stream(String String) :       läd xml zeichenkette
*    save(Dateiname) : überschreibt Datei
*    save_Stream(format) : gibt String zurück
*
*
*    cur_idx() : Aktueller Index
*    max_idx() : max index
*    change_idx($index)
*    change_URI($index)
*    uriToIndex($index)
*        ALL_URI()
*    doc_many()
*    alltag_cdata(bool)
*    curtag_cdata(bool) : gibt die daten von einem Tag in cdata notation aus
*
* (C) Stefan Wegerhoff
*/
require_once('classes/dynamic.php');
require_once('classes/class_FileScan.php');

class xml  {
   var $parser;
   var $used_parser;
   var $max_idx=0;
   var $idx=0;
   var $mirror = array();
   var $cur_pointer;
   var $tagcdata = false;

   var $loaded_URI = array();
   var $pointer;

   var $cur_pos_array; //wert für Stamp
   var $deep;          //tiefe

   var $special;
   
   private $looking_index = array();
   
   //collects controlunits
   private $controlUnits = array();
   
   //errorlevel
   var $err = 0;
   var $MIME = array();
   var $DOC = array();
   var $INSTR = array();
   
   //config
   var $only_child_node = false;
   public $heap=[];
   
   
   
   public function setControlUnit(ControlUnit &$unit)
   {
	   $this->controlUnits[$unit->getName()] = &$unit;
   }
   
   public function &getControlUnit( $name )
   {
	   if(is_null($tmp = &$this->controlUnits[$name])) return false;
	   return $tmp;
   }
   
   public function &getListControlUnit()
   {
	   return $this->controlUnits;
   }
   
   function alltag_cdata($bool){$this->tagcdata = $bool;}
   function curtag_cdata($bool){
   $this->pointer[$this->idx]->set_bolcdata($bool);
    }
   function show_curtag_cdata(){return $this->pointer[$this->idx]->get_bolcdata();}
   
   function URI($index)
   {

   if(count($this->loaded_URI) > $index)
        {
                $this->idx = $index;
                return $this->loaded_URI[$index];
                
        }
   else
        {
                return false;
        }

   }

   function error_num(){return $this->err;}

   function error_desc()
   {
        switch ($this->err)
        {
        case 0: return '';
        case 1: return 'not able to open file!';
        case 2: return 'no valid XML-Format!';} }


   /* gibt den Namen des aktuellen Knoten aus */
   function cur_node(){ //current node name

   if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
   
   return $this->pointer[$this->idx]->name;

   }

    /* gibt einen Array aller Kinderknoten aus */
   function list_child_node(){

   if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}


        if(!isset($this->pointer[$this->idx]->next_el))return false;

        $counting = $this->pointer[$this->idx]->index_max();
        for($i=0;$counting>$i;$i++){
        $list_tree[$i] = $this->pointer[$this->idx]->next_el[$i]->name;
        }
        return $list_tree;
        }

   /* gibt die Menge der Kindsknoten aus*/
   function index_child(){
   
           if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
           
           //if(!$this->pointer[$this->idx])echo "Pointer hat kein object![xml_multitree.php]";
           if( $this->pointer[$this->idx]->name == ""  )return false;
          // echo $this->pointer[$this->idx]->name . "." . $this->idx . ".";
           return $this->pointer[$this->idx]->index_max();
        }

   /* geht zu einem Kindsknoten */
   function child_node($num){

   if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}

        if(!isset($this->pointer[$this->idx]->next_el[$num]))return false;


        $this->pointer[$this->idx] = &$this->pointer[$this->idx]->getRefnext($num);

        //erstellt stamp

        $this->pos_stamp_func(0,$num,"child_node");

        return true;
        }

        /* geht einen Schritt zurück */
   public function parent_node(){
   if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];return false;}

        if(!isset($this->pointer[$this->idx]->prev_el))return false;

        $this->pointer[$this->idx] = &$this->pointer[$this->idx]->getRefprev();
        //erstellt stamp

        $this->pos_stamp_func(1,0,"parent_node");

        
        return true;
        }

        /* zeigt den Index eines Kindsknoten an, der letzten gewählt wurde */
   function show_pointer(){
           if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}

                   return $this->pointer[$this->idx]->position;

        }

        /* stellt den pointer auf -1 zurück */
   function reset_pointer(){
           if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}

                   $this->pointer[$this->idx]->position = -1;

   }

        /* markiert einen Knoten */
   function mark_node($bool = null){
              if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}

                                  if(is_bool($bool))$this->pointer[$this->idx]->mark = $bool;

                   return $this->pointer[$this->idx]->mark;

   }


        /* gibt die Attribute eines Knotens aus */
   function show_cur_attrib($attrib = null){
              if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
                if(!is_null($attrib))return $this->pointer[$this->idx]->get_attribute($attrib);
                else return $this->pointer[$this->idx]->get_attribute();
        }

          /* gibt die Attribute eines Knotens aus */
   function show_ns_attrib($attrib = null){
              if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
                if(!is_null($attrib))return $this->pointer[$this->idx]->get_ns_attribute($attrib);
                else return $this->pointer[$this->idx]->get_ns_attribute();
        }

        /* gibt die Menge der einzelnen Zeilen eines DatenFelds eines Knotens aus.*/
        function many_cur_data($ignore_empty_strings = true){
           if(!isset($this->pointer[$this->idx]))
           {
           $this->pointer[$this->idx] = &$this->mirror[$this->idx];
           }
           if(!is_null($this->pointer[$this->idx]->data))
           {
           	   if(is_array($this->pointer[$this->idx]->data))
           	   {
           	   	   $lines = 0;
           	   	   if(!$ignore_empty_strings)
           	   	   {
           	   	   	  
           	   	   	   for($f = 0; count($this->pointer[$this->idx]->data) > $f;$f++)
           	   	   	   {
           	   	   	   	   if(array_key_exists($f, $this->pointer[$this->idx]->data))
           	   	   	   	   {
           	   	   	   	   	   if(is_object($this->pointer[$this->idx]->data[$f]))
           	   	   	   	   	   	   $lines += strlen(get_class($this->pointer[$this->idx]->data[$f]));
           	   	   	   	   	   else
           	   	   	   	   	   		$lines += strlen(trim($this->pointer[$this->idx]->data[$f]));
           	   	   	   	   }
           	   	   	   }
           	   	   if(!$lines)
           	   	   	   return 0; 
           	   	   }
           	   	   	   
           	   	   return count($this->pointer[$this->idx]->data);
           	   }
           	   else
           	   		return 0;
           }
           else
           return 0;
           
        }
        
        /* gibt die Daten eines Knotens aus. Sie sind sortiert in der Reihenfolge, wie sie auch zwischen Knoten stehen */
   function show_cur_data($counter = null){
              if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
              
	      return $this->pointer[$this->idx]->getdata($counter);
	      
        }


        /* geht zum obersten Knoten über rekursion */
   function set_first_node(){

              //if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}

	      $this->pointer[$this->idx] = &$this->mirror[$this->idx];
	      /*
        if($this->parent_node())
               $this->set_first_node();

*/
                }


        /* beschreibt einen ganz bestimmten Knoten in einem Baum. Hashfunktion noch nicht implimentiert! */
   function position_stamp(){
	   if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
        $string = '';

//        if(!is_null($this->cur_pos_array[$this->idx]))
//                $string = implode($this->cur_pos_array[$this->idx],'.');
/*
<<<<<<< HEAD
	if(is_null($this->pointer[$this->idx]))echo $this->idx; 
=======
	if(!is_object($this->pointer[$this->idx]))return '0000.' . $this->idx . '.0';
>>>>>>> a612fd4651a533137033b16aa12e793158a61ee7
*/
	$string = $this->pointer[$this->idx]->position_stamp();
   return '0000.' . $this->idx . $string;}
   
        /* beschreibt einen ganz bestimmten Knoten in einem Baum. Hashfunktion noch nicht implimentiert! */
   function position_hash_pos(){
	   if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
//echo "booooh";
     $std_stamp = '';
     $return_num = strval($this->pointer[$this->idx]->position_hash_map($std_stamp) % 997);
     //parse_str( % 997)
     //str_repeat()
     
//   return  $this->pointer[$this->idx]->position_hash_map();
   return str_repeat('0',4 - strlen($return_num)) . $return_num . '.' . $this->idx . $std_stamp;}
   
        /* gibt den letzten Wert der aktuellen position zurueck */
   public function last_pos_stamp(){ return  $this->pointer[$this->idx]->position_last_stamp(); }
   

        /* geht zu einem bestimmten Knoten */
   function go_to_stamp($stamp){
		$back = null;
                $stamp_array = explode('.',$stamp);

		if($stamp_array[0] != "0000")
		{
		
		$back = &$this->pointer[$this->idx];
		}
		

                $this->change_idx($stamp_array[1]);

                $this->set_first_node();

                        for($f = 2; count($stamp_array) > $f;$f++){

                                
                                $this->child_node($stamp_array[$f]);
                                //echo "<hr>" . $stamp_array[$f] . " " . $this->cur_node();

                        }
                        //echo " " . $this->cur_node() . "<p>";
		if($stamp_array[0] != "0000" && $stamp != $this->position_hash_pos())
		{
		
		$this->pointer[$this->idx] = &$back;
		return false;
		}
		return true;
                }
   
   
   private $prim = array();
   private $primto = 2;             
   private function prim_gen($n)
   {
   
   	if($this->primto <= $n )
   	{
   	$this->primto = $n;
   	$this->prim = array();
   	$test = array();
   	
   	$test[0] = 0;
   	$test[1] = 0;
   	
   	for($i = 2;$i < $n ;$i++)
   	$test[$i] = 1;
   	
   	$test[0] = 0;
   	
   	
   	for($i = 2; $i < $n; $i++)
   	{
   	  if($test[$i] == 1)
   	  {
   	  $this->prim[count($this->prim)] = $i;
   	  
   	  
   	  for($j = $i * $i; $j  < $n ;$j += $i )
   	  {
   	
		$test[$j] = 0;
   	
   	  }
   	  }
   	}
   	
   	
   	}
   }
                
           /* erstellt einen Hash */
   function calculate_Hash_stamp(){

	$map = explode('.',$this->position_path_map());
	$stamp = $this->position_stamp();

/*
                $stamp_array = explode('.',$stamp);

		$this->prim_gen(10000);
		$hash = 0;
		
		
		
                $this->change_idx($stamp_array[1]);

		

                $this->set_first_node();

                        for($f = 2; count($stamp_array) > $f;$f++){

				if($this->prim[count($this->prim) - 1] > 0)
				$hash = ($hash + $this->prim[count($this->prim) - ( $this->index_child() + 2 )] + $this->prim[$f]) % $this->prim[count($this->prim) - 1];
                                $this->child_node($stamp_array[$f]);
                                
                                
                                //echo "<hr>" . $stamp_array[$f] . " " . $this->cur_node();

                        }
                        
                        $res = '.X';
                        
                        for($i = 2; $i > count($stamp_array); $i++)$res .= '.' . $stamp_array[$i];  
                         
                        return $hash . $res;
*/
                }



        /* erstellt einen Knoten im Baum*/
   function create_node($stamp=null,$pos=null){

echo 'booh';
        //echo '<b>' . $this->cur_node() . '</b> ';
        //echo $stamp . "=";
        if(!is_null($stamp))$this->go_to_stamp($stamp);
        //echo $stamp . "<br>";
//        echo 'get in <b>' . $this->cur_node() . '</b> mit Stamp ';
//        echo $stamp . ' nach ';
                                if(is_null($pos)){

                                        $var = $this->getInstance("",null);
                                        $var->setRefprev($this->pointer[$this->idx]);
                                        $this->pointer[$this->idx]->setRefnext($var);
                                

                                $this->child_node($help = ($this->index_child()-1));
                                //$this->pos_stamp_func(0,$help,"create_node");
                                 }
                                else
                                {
                                //echo 'hallo#dddd';

                                $reduce = 0;
                                $temp = &$this->pointer[$this->idx]->next_el;
                                unset($this->pointer[$this->idx]->next_el);
                                //if(is_Array($temp))echo 'array';
                                for($i=0;count($temp)>($i+$reduce);$i++){

                                                if($i==$pos){

                                                $var = $this->getInstance();
                                                $var->setRefprev($this->pointer[$this->idx]);
                                                $this->pointer[$this->idx]->setRefnext($var);
                                                $reduce++;

                                                }

                                                else{
                                        
                                                $this->pointer[$this->idx]->setRefnext($temp[$i-$reduce]);
                                                }

                                        }
                                                                $this->child_node($pos);
                                }
                                
                                


        }

/*   function delete_node($stamp,$pos=null){



        $this->go_to_stamp($stamp);


                                       //echo $this->pointer[$this->idx]->name;
                                        $var = new element();
                                        $var->setRefprev(&$this->pointer[$this->idx]);
                                        $this->pointer[$this->idx]->setRefnext(&$var);


                                $this->child_node($this->index_child()-1);


        } */
      /* gibt den Knoten einen Namen oder benennt ihn um */
     function set_node_name($name){$this->pointer[$this->idx]->name = $name;}
        /* setzt Eigenschaften */
     function set_node_attrib($key,$value){$this->pointer[$this->idx]->attribute($key,$value);}
        /*löscht Daten eines Knotens*/
     function clear_node_cdata($pos=null)
     {
              $this->pointer[$this->idx]->free_data($pos);
        }
        /* schreibt Daten in einen Knoten */
   function set_node_cdata($value,$counter = null){$this->pointer[$this->idx]->setdata($value,$counter);}


   /* einstellung für Suche, durchsucht alles über dem aktuellen Knoten */
   function only_child_node($bool_node){$this->only_child_node=$bool_node;}

   

// respos changed of 1 to 0
   function seek_node($type = null,array $attrib = null,array $data = null, $respos = 0){
             
   
                        //erfragt, ob alle Knoten durchforstet werden, oder nur der aktuelle Ast
                        if($this->only_child_node)
                                //setzt eine markierung
                                $this->mark_node(true);

                        else
                                //geht zum ersten Feld
                                $this->set_first_node();


                                //sicherheitscounter
                                $i = 1000000;
                                
                                if(is_null($number = $attrib['@number']))
                                        $number = $respos;
				
                                
                                $position = 1;
                                
                                $end = false;

                                $reset=true;

                                while(!$end && ($i>0)){

                                //variable für treffer
                                $hit=false;
                                //suche nach type
                                //echo $this->cur_node(). '<br>';
                                
                                if(strtoupper($this->cur_node())==strtoupper($type) || is_null($type)){

                                        //suche nach attrib
                                        if(!is_null($attrib)){
                                                $hit = true;
                                                foreach ($attrib as $key => $value) {
                                                //echo ":: " . $this->show_cur_attrib($key) . " $key $value . <br>";

                                                        $hit = ((($this->show_cur_attrib($key)==$value)||($key == '@number')) && $hit);

                                                        }}
                                        else $hit = true;

                                        if($hit && ($this->show_cur_data()==$data || is_null($data)))
                                                if($number == $position++){
                                                        //echo $this->cur_node() . " " . $this->show_cur_data() . "\n";
                                                        return true;
                                                }
                                
                                                                         }//if end

                                //schaltet den pointer zurück, wenn ein neuer knoten betreten wird
                                if($reset)$this->reset_pointer();

                                //testet, ob es weitere knoten gibt
                                if($this->index_child()>0){

                                                //schreibt die eingabe
                                                if(-1 == $this->show_pointer()){

                                                                        //get zum ersten pointer
                                                                       $reset = true;
                                                                       $this->child_node(0);

                                                                       //
                                                                        }elseif(($this->index_child() - 1) > $this->show_pointer()){

                                                                        //
                                                                       $reset = true;
                                                                       $check = $this->child_node($this->show_pointer() + 1);

                                                                                if(!$check){
                                                                                echo 'geht nicht weiter';
                                                                                }

                                                                        }else{


                                                                        //mark abfrage
                                                                        if($this->mark_node() && $this->only_child_node)return $this->mark_node(false);

                                                                        $end = !$this->parent_node();
                                                                        $reset = false;

                                                                        }
                                        }else{



                                                                        //mark abfrage
                                                                        if($this->mark_node() && $this->only_child_node)return $this->mark_node(false);

                                                                        $end = !$this->parent_node();
                                                                        $reset = false;
                                                                        //echo 'hallo';

                                        }

                                $i--;
                                }



   }

   function __construct()
   {
       //$this->parser = xml_parser_create();
       //xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, true);
       //xml_set_object($this->parser, $this);
       //xml_set_element_handler($this->parser, "tag_open", "tag_close");
       //xml_set_character_data_handler($this->parser, "cdata");
        $this->used_parser = false;
   }
//-------------------------------------------------------------------------------------------
//seems to be never used again omni_handle does an override
		/* läd ein XML-Dokument */
      function load_Stream(&$source,$casefolding=1,$special="",$ref='')
        {



		$this->idx = $this->max_idx;
                $this->special = $special;
                 $this->MIME[$this->idx] = $this->MIME_check($source);
                $this->DOC[$this->idx] = $this->DOC_check($source);

                if($this->used_parser)
                        {
                               xml_parser_free($this->parser); 
			       
			}
                       
                        $encoding = "";
                        if($this->MIME[$this->idx][encoding])$encoding = $this->MIME[$this->idx][encoding];
                        $this->parser = xml_parser_create('ISO-8859-1'); //'UTF-8'

                        xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, $casefolding );
                        xml_set_object($this->parser, $this);
                        xml_set_element_handler($this->parser, "tag_open", "tag_close");
                        xml_set_character_data_handler($this->parser, "cdata");
                        $this->idx = $this->max_idx++;
                        $this->used_parser = true;
                        
                $allRows = explode("\n",$source);        
 
                if (!xml_parse($this->parser, $source)) {
                        $this->err = 2;
                                
                        $lineNum = xml_get_current_line_number($this->parser);
                             echo xml_error_string(xml_get_error_code($this->parser));
                             echo $lineNum;
                             echo ' in rowcontent:' . $allRows[$lineNum-1] . '<br>';


                }
                
                $this->cur_pos_array[$this->idx] = array(); //
                $this->deep[$this->idx] = 0;
                $this->used_parser = true;
                $this->special = "";
                return $this->idx;
   }

   /**
   * createTree
   *
   * @param : 
   */
   function createTree($identifire, $tag, $attributes)
   {
	   $this->idx = $this->max_idx;
           $this->special = 'XML';
           $this->MIME[$this->idx]['name'] = 'XML';
           $this->DOC[$this->idx] = array();
	   $this->max_idx++;
	   
	   $obj = null;
	   
	   $this->tag_open($this, $tag, $attributes);
	   $this->tag_close($this, $tag);
	
   }
   
   /* läd aus Datei */
   /**
   load new reasource
   @param ref : path in directory
   @param case_folder : parses case sensitive
   @param spezial : specific type of resource
   
   
   */
   function load($ref,$case_folder = 1,$spezial = 'XML')
   {
   global $logger_class;
   $res;

//echo '--' . $ref . "---\n\n";

if(false === stripos($ref,'.php'))
{
        //echo $ref;
        //RDF:about func
        $tmp2 = $ref;
        $tmp = $ref;

        
        //looking for allready existing file name
        for($i=0;$i<count($this->loaded_URI);$i++)
        {
        if($this->loaded_URI[$i] == $tmp2)
                {
                $this->change_idx($i);
                return $i;
                }

        }

        
   //creates a new identifire to index correlation
	$this->setNewTree($tmp2);


}
else
{

$tmp = $ref;
	reset($this->controlUnits);
	//echo '$"' . current($this->controlUnits)->getIDX() . '"$' ;
	
	$this->idx = current($this->controlUnits)->getIDX();
	//echo $this->idx;
	
}//End   
        // TODO throwing exception would be more useful
        if (!($fp = fopen($tmp, "r"))) {
                $this->err = 1;
                		$res = '<?xml version="1.0" encoding="iso-8859-1" standalone="yes" ?><html>Error</html>';
                        $this->load_Stream($res);
                return $pos_in_array;
        }

	
        while ($data = fread($fp, 4096)) {
	
		
		
        $res .= $data;

        }
        
        //echo "\n\n" . $res . "\n\n";
        //save new file

        $this->load_Stream($res,$case_folder,$spezial,$ref);

        
        	//looks unnessesary, makes no difference if commented out
           if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
        	
        return $this->idx;

   }
//-----------------------------------------------------------------
//--------------------------setNewTree-----------------------------

function setNewTree($ident, $namespaces = [])
{
	$this->max_idx = count($this->loaded_URI);

        $this->loaded_URI[$this->max_idx] = $ident;

        $this->idx =  $this->max_idx;
        
       // $this->NAMESPACES[$this->base_object->idx] = $namespaces;
}
//----------------Datei laden--------------------------------------
//----------------http laden---------------------------------------
function PostToHost($addr, $referer, $treename,$encoding = '') {

        $stamp = $this->position_stamp();
        //echo $treename;
        if (!$this->change_URI($treename)) echo "XML zum senden nicht gefunden: $treename!";
        
        $toSend = $this->save_stream($encoding); //rawurlencode(
        
        //echo $toSend;
        
        $pos = strpos($addr,"/",8);
        if(!($pos === false))
        $host = substr($addr,0, $pos ); 
        $path = substr($addr, $pos); 
        
        
        
        
        $bool_found = false; 
        $posinURI = 0;
        
        for($i=0;$i<count($this->loaded_URI);$i++)
        {
        if($this->loaded_URI[$i] == strtolower($addr) )
                {
                $posinURI = $i;
                $bool_found = true;
                }

        }

        if($bool_found)
        {
                $this->pointer[$posinURI] = null;

                $this->idx =  $posinURI;
        }
        else
        {
        
                

        $this->max_idx = count($this->loaded_URI);
        
        $this->loaded_URI[$this->max_idx] = strtolower($addr);
//        echo implode($this->loaded_URI,",\n");
        //echo $this->max_idx . "boooh";
        $this->idx =  $this->max_idx;

        }

        //$this->all_uri();

        
        $myInterface = createXML(HOST_KV, METHOD_POST);


 


        $xml = $myInterface->getResponse($toSend, true);





//        $toSend = urlencode($toSend);
//        $fp = fsockopen($host, 80);
  //printf("Open!\n");
//  fputs($fp, "POST $path HTTP/1.1\r\n");
//  fputs($fp, "Host: $host \r\n");
  //fputs($fp, "Referer: $referer\r\n");
//  fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
  //fputs($fp, "Content-Type: text/xml\\r\\n");
//  fputs($fp, "Content-length: ". strlen($toSend) ."\r\n");
//  fputs($fp, "Connection: close\r\n\r\n");
//  fputs($fp, 'PaxXMLRequest='  . $toSend);
  //printf("Sent!\n");
//  while(!feof($fp)) {
//      $res .= fgets($fp, 128);
//  }
  //printf("Done!\n");
//  fclose($fp);

 // echo $toSend;
 // echo "<hr>";
 // echo $res;
 
 //echo $xml;
   $this->load_Stream($xml,1,"urlencode");

   $this->set_first_node();
   
   //echo $this->save_stream();
   
   $this->go_to_stamp($stamp);

//die();
}
//---------------------------------------------------------------------

//MIME check
function MIME_check($String)
        {
                
		if(is_object($String))
		{
			$collect = "";
			$elem = "";
			$i = 0;
			while($String->eof() && (false == ($elem = (strpos($elem = $String->get_line(),'>')))) && $i++ <> 0)
			{
			$collect .= $elem;
			}
			
			unset($String);
			$String = $collect;
		}
		
                $res = array();
                $token = strpos($String,"?>",0);
        //echo '<i>' . $String . '</i>';
                if($token === false)return array();
                
                //
                
                $mime = substr($String,2,$token - 2 );
                $mime = str_replace("\"","'",$mime);
//echo "mime:" . $mine . "<br>\n";
                $elements = explode(" ",trim($mime));
                foreach ($elements as $key => $ele)
                {
                        
                        if($key == 0)
                        {
                                $res['name'] = $ele;
                        }
                        else
                        {
                        $attrib = explode("=" , $ele);
                        $res[ strtolower($attrib[0]) ] = str_replace('\'','',$attrib[1]);
                        
                        }
                }
                return $res;
                
        }

        //MIME check
function DOC_check($String)
        {

		if(is_object($String))
		{
			$collect = "";
			$elem = "";
			$i = 0;
			while($String->eof() && (false == ($elem = (strpos($elem = $String->get_line(),'>')))) && $i++ <> 0)
			{
			$collect .= $elem;
			}
			
			unset($String);
			$String = $collect;
		}
		
                $res = array();
                $token = strpos($String,"<!DOCTYPE",0);
        //echo '<i>' . $String . '</i>';
        
                if($token === false)return '';
                
                $nexttoken = strpos($String,"<",$token);
                
                $testinnerbrackets = strpos($String,"[",$token);
                
                
                $endtoken = strpos($String,">",$token);
                
		if($endtoken === false)return '';

                if( ($testinnerbrackets === false) || $testinnerbrackets > $endtoken)
                {
                	//echo "doc: " . substr($String,$token ,$endtoken - $token + 1) . "\n";
                	return substr($String,$token ,$endtoken - $token + 1);
                }
                
                
                
                while($nexttoken < $endtoken)
                {
                $endtoken = strpos($String,">",$nexttoken);
                $nexttoken = strpos($String,"<",$endtoken + 1);
                $endtoken = strpos($String,">",$endtoken + 1);              
                }
                

		$doc = substr($String,$token ,$endtoken - $token + 1);
		//echo "doc: $doc \n";
                return $doc;
                
        }
        
//-----------------------------------------------------------------
   // Aktueller Index
   function cur_idx(){return $this->idx;}

   function cur_URI(){
   	   var_dump($this->$this->loaded_URI[intval($this->idx)], $this->loaded_URI, intval($this->idx));
   return $this->loaded_URI[intval($this->idx)] ;
   }
   
   function max_idx(){return $this->max_idx; }

   function change_idx($index)
   {
  
   	if(!is_numeric($index))throw new ErrorException("Value for cur_idx($index) has to be a number",0,75,'xml_multitree.php',0);
        if($index <= $this->max_idx)
        {
        $this->idx = $index;
        return true;
        }
        else
        {
        return false;
        }
   }
   
     function change_URI($index, $case = false)
   {
       

   	   if(is_null($index))return false;

       $search = $index;
 
        for($i=0;count($this->loaded_URI) > $i;$i++)
        {
                
        	if(!$case)
        	{
   	   //echo strtolower($this->loaded_URI[$i]) . "==" . strtolower($search) . "\n";        		
                if(strtolower($this->loaded_URI[$i]) == strtolower($search))
                {

                        $this->idx = $i;
                        return true;
                }
        	}
            else
                if($this->loaded_URI[$i] == $search)
                {

                        $this->idx = $i;
                        return true;
                }
        }

        return false;
 
   }
   
   public function uriToIndex($index)
   {
           
        $search = strtolower($index);
 
        for($i=0;count($this->loaded_URI) > $i;$i++)
        {
                
                //echo $this->loaded_URI[$i] . "<br>n";
                if($this->loaded_URI[$i] == $search)
                {

                        return $i;
                }
        }

        return false;
 
   }

   public function indexToUri($i)
   { return $this->loaded_URI[$i];}
   
   function ALL_URI()
   {
        echo "<ol>\n";
        for($i=0;count($this->loaded_URI) > $i;$i++)
        {

                echo '<li>' . $i . ':' . $this->loaded_URI[$i] . "</li>\n";

        }
        echo "</ol>\n";

   }



   function doc_many()
   {
   	  	return $this->max_idx;
          //return count($this->loaded_URI);
   }


   /* speichert als dokument */
   function save($ref=null)
   {
    if(is_null($ref))$ref = $this->URI($this->idx);


      $fp = fopen($ref,"w");
   if ($fp)
   {
      flock($fp,2);
      $nl = chr(13) . chr(10);
       fputs ($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>$nl");


                                $deep[$this->idx]=0;
                                $i = 30000;
                                $end = false;

                                $reset=true;
                              $this->set_first_node();

                              //fputs ($fp, "\n<ul>\n");


                                while(!$end && ($i>0)){

                                //schaltet den pointer zurück, wenn ein neuer knoten betreten wird
                                if($reset)$this->reset_pointer();

                                //testet, ob es weitere knoten gibt
                                if($this->index_child()>0){

                                        //schreibt die eingabe
                                       if(-1 == $this->show_pointer()){

                                                                       fputs ($fp, '<' .  $this->cur_node() . ' ' . $this->all_attrib_axo('UTF-8') . ' >');
                                                                       fputs ($fp,        $this->convert_to_XML($this->show_cur_data(0),'UTF-8') . "\n");
                                                                       $reset = true;

                                                                       $this->child_node(0);

                                                                       //fputs ($fp, "<ul>\n");
                                                                       $deep[$this->idx]++;
                                                                        }elseif((($this->index_child()-1) > $this->show_pointer()) ){
                                                                       fputs ($fp,$this->convert_to_XML($this->show_cur_data($this->show_pointer()+1) ,'UTF-8'));
                                                                       $reset = true;
                                                                       $check = $this->child_node($this->show_pointer() + 1);
                                                                       $deep[$this->idx]++;
                                                                                if(!$check){
                                                                                echo 'geht nicht weiter';
                                                                                }

                                                                        }else{

                                                                        fputs ($fp,   $this->convert_to_XML($this->show_cur_data($this->show_pointer()+1) ,'UTF-8'));

                                                                        fputs ($fp,'</' .  $this->cur_node() . ' >' . "\n");

                                                                        $end = !$this->parent_node();

                                                                        $deep[$this->idx]--;
                                                                        $reset = false;
                                                                        }
                                        }else{

                                                                        if(-1 == $this->show_pointer()){
                                                                        fputs ($fp,'<' .  $this->cur_node() . ' ' . $this->all_attrib_axo('UTF-8') );}
                                                                                if( '' <>( $this->show_cur_data($this->show_pointer()+1)) )
                                                                                {
                                                                                fputs ($fp,' >' . $this->convert_to_XML($this->show_cur_data($this->show_pointer()+1) ,'UTF-8'));
                                                                                fputs ($fp,'</' .  $this->cur_node() . ' >' . "\n");   // str_repeat (" ", 2*$deep[$this->idx])
                                                                                }
                                                                                else
                                                                                fputs ($fp,' />' . "\n");

                                                                        $end = !$this->parent_node();
                                                                        $deep[$this->idx]--;
                                                                        $reset = false;
                                                                        //echo 'hallo';

                                        }

                                $i--;
                                }




      flock($fp,3);
      fclose($fp);
   }
   else
   {
      echo "Datei konnte nicht zum";
      echo " Schreiben geöffnet werden";
   }
   }

   /* redundant speichert als Stream */
function save_stream($format = '',$send_header = false){

      switch ($format)
      {
      case 'HTML': $arg = 'ISO-8859-5';
      break;
      case 'UTF-8': $arg = 'UTF-8';
      }


      $nl = chr(13) . chr(10);
      
       $res = '<?';        
      
              foreach ($this->MIME[$this->idx] as $key => $ele)
                {
                        if($key == 'name')$res .= $ele . ' ';
                        else
                                $res .= $key . '="' . $ele . '" ';
                }
      
      $res .= "?>$nl";
      if($this->DOC[$this->idx] <> '')$res .= $this->DOC[$this->idx];

                                $deep[$this->idx]=0;
                                $i = 30000;
                                $end = false;

                                $reset=true;
                              $this->set_first_node();

                              //fputs ($fp, "\n<ul>\n");


                                while(!$end && ($i>0)){

                                //schaltet den pointer zurück, wenn ein neuer knoten betreten wird
                                if($reset)$this->reset_pointer();

                                //testet, ob es weitere knoten gibt
                                if($this->index_child()>0){

                                        //schreibt die eingabe
                                       if(-1 == $this->show_pointer()){

                                                                       $res .=  '<' .  $this->cur_node() . $this->all_attrib_axo($format) . '>';
                                                                       $res .=  $this->setcdata_tag($this->convert_to_XML($this->show_cur_data(0),$format),$this->show_curtag_cdata());
                                                                       $reset = true;

                                                                       $this->child_node(0);

                                                                       
                                                                       $deep[$this->idx]++;
                                                                        }elseif((($this->index_child()-1) > $this->show_pointer()) ){
                                                                       $res .=  $this->setcdata_tag($this->convert_to_XML($this->show_cur_data($this->show_pointer()+1,$format) ,$format),$this->show_curtag_cdata());
                                                                       $reset = true;
                                                                       $check = $this->child_node($this->show_pointer() + 1);
                                                                       $deep[$this->idx]++;
                                                                                if(!$check){
                                                                                echo 'geht nicht weiter';
                                                                                }

                                                                        }else{

                                                                        $res .=  $this->setcdata_tag($this->convert_to_XML($this->show_cur_data($this->show_pointer()+1) ,$format),$this->show_curtag_cdata());

                                                                        $res .=  '</' .  $this->cur_node() . '>';

                                                                        $end = !$this->parent_node();

                                                                        $deep[$this->idx]--;
                                                                        $reset = false;
                                                                        }
                                        }else{

                                                                        if(-1 == $this->show_pointer()){
                                                                        $res .=  '<' .  $this->cur_node() . $this->all_attrib_axo($format) ;}
                                                                                if( '' <>( $this->show_cur_data($this->show_pointer()+1)) )
                                                                                {
                                                                                $res .=  '>' . $this->setcdata_tag($this->convert_to_XML($this->show_cur_data($this->show_pointer()+1) ,$format),$this->show_curtag_cdata());
                                                                                $res .=  '</' .  $this->cur_node() . ' >';   // str_repeat (" ", 2*$deep[$this->idx])
                                                                                }
                                                                                else
                                                                                $res .=  '/>';

                                                                        $end = !$this->parent_node();
                                                                        $deep[$this->idx]--;
                                                                        $reset = false;
                                                                        //echo 'hallo';

                                        }

                                $i--;
                                }




return $res;
}

        function setcdata_tag($param,$local_cdata)
        {
                
                if((!$this->tagcdata || (trim($param) == ""))&&!$local_cdata)
                {
                        return $param;
                }
                else
                {
                        return '<![CDATA[' . $param . ']]>';
                }
        }

        // hilfsfunction
        function all_attrib_axo($format){
                                $res = '';

                                $attrib_array = $this->show_cur_attrib();

                                if(is_null($attrib_array))return '';

                                        foreach ($attrib_array as $key => $value){



                                                        $res .= $key . '="' . $this->convert_to_XML($value,$format) . '" ';



                                        }
                                       return ' ' . $res;}

   function get_attribute_many()
   {
   	$attrib_array = $this->show_cur_attrib();
                                  
        if(is_null($attrib_array))return 0;
	
	return count($attrib_array);

   	
   

   }
   //Aenderung f�r NS				    
   function &getInstance($name,$attributes,$arg_obj = null)
   {
	   if($arg_obj)
	   $obj = $arg_obj;
	   else
	   $obj = new XMLelement();
	   
	   $obj->name = $name;
	   
	   if($attributes)
	   if (count($attributes)) {
            foreach ($attributes as $k => $v) {
             $obj->attribute($k,$this->convert_from_XML($v));
                                   }
                           }
	   
   return $obj;
   }
//seems to be never used again override in ns
   function tag_open($parser, $tag, $attributes)
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

   function cdrata($parser, $cdata) // outdated
   {
  
                                           
echo "fick dich";
   
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
					
					if(is_Object($cdata))
					$this->cur_pointer[$this->idx]->setdata($cdata,$tmp);
					else
					$this->cur_pointer[$this->idx]->setdata("",$tmp); // $this->convert_from_XML($res)
                                        }
   }

   function tag_c6lose($parser, $tag)
   {
   //echo "&lt;/<font color=\"#0000cc\">$tag</font>&gt;";
   	$this->cur_pointer[$this->idx]->comlete();
        if(isset($this->cur_pointer[$this->idx]->prev_el))        
	 $this->cur_pointer[$this->idx] = &$this->cur_pointer[$this->idx]->getRefprev();
                                                      //$this->cur_pointer[$this->idx]->final_data();


   }

//function for entities
   function tag_entity($parser, $open_entity_names , $base, $system_id, $public_id )
   {
// var_dump($parser, $open_entity_names, $base, $system_id, $public_id);
   }

//function for entities
   function tag_notation($parser, $open_entity_names , $base, $system_id, $public_id )
   {
// var_dump($parser, $open_entity_names, $base, $system_id, $public_id);
   }
   
//function for entities
   function tag_up_entity($parser, $open_entity_names , $base, $system_id, $public_id )
   {
 var_dump($parser, $open_entity_names, $base, $system_id, $public_id);
   }
   
//function for instruction entries
   function tag_instruction_entry($parser, $target, $data)
   {
   	   if(!array_key_exists($this->idx, $this->INSTR))$this->INSTR[$this->idx] = array();
   	   array_push($this->INSTR[$this->idx],array('target'=>$target, 'data'=>$data));

   }   

   function check(){
   if(isset($this->mirror[$this->idx])){
   
                echo $this->mirror[$this->idx]->name . '<br>';

                $counting = 3;
                   //
                $var = $this->mirror[$this->idx];
                while(isset($var->next_el) && $counting>0){

                $var = $var->getRefnext(0);
                echo ':' . $var->getRefprev() . '::';
                        echo $var->name . '<br>';
                        echo '-';
                        $counting--;
                        }


        }

   }
function &convert_from_XML($myString)
        {
                /*
                echo '<b>' . $this->MIME[$this->idx]['encoding'] . '</b>bei ' . str_replace(

                        array('&Uuml;','&Auml;','&Ouml;','&uuml;','&auml;','&ouml;'),
                        $String
                        ) . '<br>';
                        
                */
		
                $String = (is_null($myString)? '': $myString);
                //echo strtoupper($this->MIME[$this->idx]['encoding']);
                if (strtoupper($this->special) == "URLENCODE") $String = urldecode($String);
                
                //echo strtoupper($this->MIME[$this->idx]['encoding']) . $myString . "\n\r";
                switch (strtoupper($this->MIME[$this->idx]['encoding']))
                {
                case 'UTF-8' :
        //        	$res = $String;
		$res = mb_convert_encoding($String,'utf-8');
                //echo 'ausgang bei utf8:' .$res . ";\n";
                break;
                case 'ISO-8859-1' :
                $res = $String;
                //echo 'ausgang bei ISO-8859-1:' .$res . ";\n";
                break;
                default:
                $res = $String;
                //echo 'ausgang bei sonstiges:' .$res . ";\n";
                break;
                }
                
                //$res = utf8_decode($String);
                return $res;
        }
function convert_to_XML( $String , $format, $void = false)
        {
               
		if(is_null($String) || $void)return $String;		
		if($format == '') $format = $this->MIME[$this->idx]['encoding'];
                
                $tmp;
                switch( strtoupper($format) )
                {

                case 'UTF-8':
                       $tmp = utf8_encode($String);
                       
/*
                $tmp = str_replace(
                        array('&'),
                        array('&amp;'),
                        $tmp);

                
                $tmp = str_replace(
                        array('"','<','>'),
                        array('&quot;','&lt;','&gt;'),
                        $tmp);
                       */
                        break;
                case 'ISO-8859-1':
                      //echo $String ."<p>\n";
                $tmp = $String;
                /*
		$tmp = str_replace(
                        array('&'),
                        array('&amp;'),
                        $String);

                
                $tmp = str_replace(
                        array('"','<','>'),
                        array('&quot;','&lt;','&gt;'),
                        $tmp);
                  */      
                        break;
                default:
                $tmp = $String;
                /*
                $tmp = str_replace(
                        array('&'),
                        array('&amp;'),
                        $String);

                
                $tmp = str_replace(
                        array('"','<','>'),
                        array('&quot;','&lt;','&gt;'),
                        $tmp);
                */
                }

                if(false)
                return $tmp;
                else
                {
                        //,'&' ,'&amp;'
                
                $tmp = str_replace(
                        array('&'),
                        array('&amp;'),
                        $tmp
                );
                
                return str_replace(
                        array('"','<','>'),
                        array('&quot;','&lt;','&gt;'),
                        $tmp
                );
                
                }
        }

        function pos_stamp_func($updown,$num = 0,$position = '')
        {
                //echo "<h1>" . $this->deep[$this->idx] . "</h1>";
                if($updown == 0)
                {
//echo "hoch";
                $this->cur_pos_array[$this->idx][$this->deep[$this->idx]]= $num;
                $this->deep[$this->idx]++;

                }
                else
                {
                $this->deep[$this->idx]--;
                        //echo "runter" . $this->cur_pos_array[$this->idx][$this->deep[$this->idx]];
                        unset($this->cur_pos_array[$this->idx][$this->deep[$this->idx]]);

                }
                //echo "<h2>" . $this->deep[$this->idx] . " bei " . $this->cur_node() . " stamp " . $this->position_stamp() . "</h2>";
        }
        
       	public function __toString(){return "Class:xml";}

} // end of class xml
//hilfsklasse


?>
