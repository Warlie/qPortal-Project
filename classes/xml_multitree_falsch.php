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
*    show_cur_attrib($attrib = null) :Zeigt Attribute an
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
*	ALL_URI()
*    cdata(bool)
*
* (C) Stefan Wegerhoff
*/


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

   //errorlevel
   var $err = 0;
   var $MIME = array();

   //config
   var $only_child_node = false;
   
   function tag_cdata($bool){$this->tagcdata = $bool;}
   
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
   function parent_node(){
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
                if(!is_null($attrib))return $this->pointer[$this->idx]->attrib[$attrib];
                else return $this->pointer[$this->idx]->attrib;
        }

	/* gibt die Menge der einzelnen Zeilen eines DatenFelds eines Knotens aus.*/
	function many_cur_data(){
           if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
	   return count($this->pointer[$this->idx]->data);
	}
	
        /* gibt die Daten eines Knotens aus. Sie sind sortiert in der Reihenfolge, wie sie auch zwischen Knoten stehen */
   function show_cur_data($counter = null){
              if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}
              if(is_array($res = $this->pointer[$this->idx]->data))

                if(is_null($counter))
                        return trim(implode($res,''));
                else
                        return trim($res[$counter]);
                
              else
                return $res;
        }


        /* geht zum obersten Knoten über rekursion */
   function set_first_node(){

              if(!isset($this->pointer[$this->idx])){$this->pointer[$this->idx] = &$this->mirror[$this->idx];}

        if($this->parent_node())
               $this->set_first_node();


                }


        /* beschreibt einen ganz bestimmten Knoten in einem Baum. Hashfunktion noch nicht implimentiert! */
   function position_stamp(){
        $string = '';

        if(!is_null($this->cur_pos_array[$this->idx]))
                $string = implode($this->cur_pos_array[$this->idx],'.');

   return '0000.' . $this->idx . '.' . $string;}

        /* geht zu einem bestimmten Knoten */
   function go_to_stamp($stamp){

                $stamp_array = explode('.',$stamp);

                $this->change_idx($stamp_array[1]);

                $this->set_first_node();

                        for($f = 2; count($stamp_array) > $f;$f++){

				
                                $this->child_node($stamp_array[$f]);
				//echo "<hr>" . $stamp_array[$f] . " " . $this->cur_node();

                        }
			//echo " " . $this->cur_node() . "<p>";
                }

        /* erstellt einen Knoten im Baum*/
   function create_node($stamp,$pos=null){


	//echo '<b>' . $this->cur_node() . '</b> ';
	//echo $stamp . "=";
        $this->go_to_stamp($stamp);
	//echo $stamp . "<br>";
//	echo 'get in <b>' . $this->cur_node() . '</b> mit Stamp ';
//        echo $stamp . ' nach ';
                                if(is_null($pos)){

                                        $var = $this->getInstance();
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
     function clear_node_cdata(){
        $this->pointer[$this->idx]->data = null;
        }
        /* schreibt Daten in einen Knoten */
   function set_node_cdata($value,$counter = null){$this->pointer[$this->idx]->setdata($value,$counter);}


   /* einstellung für Suche, durchsucht alles über dem aktuellen Knoten */
   function only_child_node($bool_node){$this->only_child_node=$bool_node;}

   /* durchsucht den Baum nach Inhalten (keine direkte optimierung)*/
   function seek_node($type = null,$attrib = null,$data = null){

                //*
   
                        //erfragt, ob alle Knoten durchforstet werden, oder nur der aktuelle Ast
                        if($this->only_child_node)
                                //setzt eine markierung
                                $this->mark_node(true);

                        else
                                //geht zum ersten Feld
                                $this->set_first_node();


                                //sicherheitscounter
                                $i = 10000;
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
						
							
							
                                               
//echo $this->position_stamp() . $this->cur_node() . ' attrib:' . $key . '*' . $this->show_cur_attrib($key) . '-' . $type . "\n";
                                                        

							$hit = (($this->show_cur_attrib($key)==$value) && $hit);

                                                        }}
                                        else $hit = true;

                                        if($hit && ($this->show_cur_data()==$data || is_null($data)))
					{

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

   function xml()
   {
       //$this->parser = xml_parser_create();
       //xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, true);
       //xml_set_object($this->parser, $this);
       //xml_set_element_handler($this->parser, "tag_open", "tag_close");
       //xml_set_character_data_handler($this->parser, "cdata");
        $this->used_parser = true;
   }
//-------------------------------------------------------------------------------------------
     /* läd ein XML-Dokument */
      function load_Stream($source)
        {

		$this->MIME[$this->idx] = $this->MIME_check($source);
		

                if($this->used_parser)
                        {
				
                        $this->parser = null;
			$encoding = "";
			if($this->MIME[$this->idx][encoding])$encoding = $this->MIME[$this->idx][encoding];
                        $this->parser = xml_parser_create($encoding); //'UTF-8'

                        xml_set_object($this->parser, $this);
                        xml_set_element_handler($this->parser, "tag_open", "tag_close");
                        xml_set_character_data_handler($this->parser, "cdata");
                        $this->idx = $this->max_idx++;
                        }
			
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

   }

   /* läd aus Datei */
   function load($ref)
   {
   $res;


        //echo $ref;
	//RDF:about func
        if(!(($token = strpos($ref,'#'))===False))
        {
        $tmp = strtolower(substr($ref,0, - strlen($ref) + $token ));
        }
        else
        {
        $tmp = strtolower($ref);
        }

	$tmp = str_replace('&amp;','&',$tmp);
	
        for($i=0;$i<count($this->loaded_URI);$i++)
        {
        if($this->loaded_URI[$i] == $tmp)
                {
                $this->change_idx($i);
                return $i;
                }

        }

	
	
       $this->max_idx = count($this->loaded_URI);

        $this->loaded_URI[$this->max_idx] = $tmp;

        $this->idx =  $this->max_idx;


	
	
        if (!($fp = fopen($tmp, "r"))) {
                $this->err = 1;
		        $this->load_Stream('<?xml version="1.0" encoding="iso-8859-1" standalone="yes" ?><ERR />');
                return $pos_in_array;
        }


        while ($data = fread($fp, 4096)) {


        $res .= $data;

        }
        
	
	//save new file

        $this->load_Stream($res);
        //$this->max_idx = $pos_in_array;
        //echo $pos_in_array . '+++++++++';
        //$this->change_idx($pos_in_array);
        //echo $pos_in_array . ' -+--: ' . $this->URI($pos_in_array);
        return $pos_in_array;

   }
//-----------------------------------------------------------------


//----------------Datei laden--------------------------------------
//----------------http laden---------------------------------------
function PostToHost($host, $path, $referer, $data_to_send) {
  $fp = fsockopen($host, 80);
  printf("Open!\n");
  fputs($fp, "POST $path HTTP/1.1\r\n");
  fputs($fp, "Host: $host\r\n");
  fputs($fp, "Referer: $referer\r\n");
  fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
  fputs($fp, "Content-length: ". strlen($data_to_send) ."\r\n");
  fputs($fp, "Connection: close\r\n\r\n");
  fputs($fp, $data_to_send);
  printf("Sent!\n");
  while(!feof($fp)) {
      $res .= fgets($fp, 128);
  }
  printf("Done!\n");
  fclose($fp);

  return $res;
}
//---------------------------------------------------------------------

//MIME check
function MIME_check($String)
	{
		
		$res = array();
		$token = strpos($String,"?>",0);
	//echo '<i>' . $String . '</i>';
		if($token === false)return array();
		
		//
		
		$mime = substr($String,2,$token - 2 );
		$mime = str_replace("\"","'",$mime);
//echo $mine . '<br>';
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

//-----------------------------------------------------------------
   // Aktueller Index
   function cur_idx(){return $this->idx;}


   function change_idx($index)
   {
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
   
     function change_URI($index)
   {
	   
	$search = strtolower($index);
 
	for($i=0;count($this->loaded_URI) > $i;$i++)
        {
		
		
                if($this->loaded_URI[$i] == $search)
		{
			
			$this->idx = $i;
			return true;
		}
        }

        return false;
 
   }
   
   
   function ALL_URI()
   {
        echo '<ol>';
        for($i=0;count($this->loaded_URI) > $i;$i++)
        {

                echo '<li>' . $this->loaded_URI[$i] . '</li>';

        }
        echo '</ol>';

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
function save_stream($format = ''){

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

                                                                       $res .=  '<' .  $this->cur_node() . ' ' . $this->all_attrib_axo($format) . '>';
                                                                       $res .=  $this->setcdata_tag($this->convert_to_XML($this->show_cur_data(0),$format)) . "\n";
                                                                       $reset = true;

                                                                       $this->child_node(0);

                                                                       //fputs ($fp, "<ul>\n");
                                                                       $deep[$this->idx]++;
                                                                        }elseif((($this->index_child()-1) > $this->show_pointer()) ){
                                                                       $res .=  $this->setcdata_tag($this->convert_to_XML($this->show_cur_data($this->show_pointer()+1,$format) ,$format));
                                                                       $reset = true;
                                                                       $check = $this->child_node($this->show_pointer() + 1);
                                                                       $deep[$this->idx]++;
                                                                                if(!$check){
                                                                                echo 'geht nicht weiter';
                                                                                }

                                                                        }else{

                                                                        $res .=  $this->setcdata_tag($this->convert_to_XML($this->show_cur_data($this->show_pointer()+1) ,$format));

                                                                        $res .=  '</' .  $this->cur_node() . '>' . "\n";

                                                                        $end = !$this->parent_node();

                                                                        $deep[$this->idx]--;
                                                                        $reset = false;
                                                                        }
                                        }else{

                                                                        if(-1 == $this->show_pointer()){
                                                                        $res .=  '<' .  $this->cur_node() . ' ' . $this->all_attrib_axo($format) ;}
                                                                                if( '' <>( $this->show_cur_data($this->show_pointer()+1)) )
                                                                                {
                                                                                $res .=  '>' . $this->setcdata_tag($this->convert_to_XML($this->show_cur_data($this->show_pointer()+1) ,$format));
                                                                                $res .=  '</' .  $this->cur_node() . ' >' . "\n";   // str_repeat (" ", 2*$deep[$this->idx])
                                                                                }
                                                                                else
                                                                                $res .=  '/>' . "\n";

                                                                        $end = !$this->parent_node();
                                                                        $deep[$this->idx]--;
                                                                        $reset = false;
                                                                        //echo 'hallo';

                                        }

                                $i--;
                                }




return $res;
}

	function setcdata_tag($param)
	{
		
		if(!$this->tagcdata || (trim($param) == ""))
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
                                        return $res;}

   function getInstance(){return new XMLelement();}

   function tag_open($parser, $tag, $attributes)
   {

        if(!isset($this->mirror[$this->idx])){

                $num = 0;

                $this->mirror[$this->idx] = $this->getInstance();
                $this->mirror[$this->idx]->name = $tag;

                           if (count($attributes)) {
                               foreach ($attributes as $k => $v) {
				       
				       
				       
                                        $this->mirror[$this->idx]->attribute($k,$this->convert_from_XML($v));
                                   }
                           }

                }else{

                        $num = $this->mirror[$this->idx]->index_max();

                        if(!isset($this->mirror[$this->idx]->next_el)){

                                $var = $this->getInstance();
                                $this->mirror[$this->idx]->setRefnext($var);
                                $this->cur_pointer[$this->idx] = &$this->mirror[$this->idx]->getRefnext($this->mirror[$this->idx]->index_max() - 1 ,true);
                                //schliesst cdata ab
                                //$this->cur_pointer[$this->idx]->final_data();
                                $this->cur_pointer[$this->idx]->setRefprev($this->mirror[$this->idx]);
                                $this->cur_pointer[$this->idx]->name = $tag;
                                
                                
                                
                        }else{

                                $var = $this->getInstance();
                                $var->setRefprev($this->cur_pointer[$this->idx]);
                                $this->cur_pointer[$this->idx]->setRefnext($var);
                                //schliesst cdata ab
                                $this->cur_pointer[$this->idx]->final_data();
                                $this->cur_pointer[$this->idx] = &$this->cur_pointer[$this->idx]->getRefnext($this->cur_pointer[$this->idx]->index_max() - 1 ,true);
                                //$this->cur_pointer[$this->idx]->setRefprev(&$this->mirror[$this->idx]);
                                $this->cur_pointer[$this->idx]->name = $tag;
                                //$cur_pointer = &$this->mirror[$this->idx]->getRefnext();
                
                        }
                        
                        
                           if (count($attributes)) {
                               foreach ($attributes as $k => $v) {
                                        $this->cur_pointer[$this->idx]->attribute($k,$this->convert_from_XML($v));
                                   }
                           }
           }
           

   }

   function cdata($parser, $cdata)
   {
   //echo "<b>$cdata</b>";
   

   
                                        if(isset($this->cur_pointer[$this->idx])){
                                        $this->cur_pointer[$this->idx]->setdata($this->convert_from_XML($cdata));

                                        }
   }

   function tag_close($parser, $tag)
   {
   //echo "&lt;/<font color=\"#0000cc\">$tag</font>&gt;";
        if(isset($this->cur_pointer[$this->idx]->prev_el))        $this->cur_pointer[$this->idx] = &$this->cur_pointer[$this->idx]->getRefprev();
                                                      //$this->cur_pointer[$this->idx]->final_data();


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
function convert_from_XML($String)
	{
		/*
		echo '<b>' . $this->MIME[$this->idx]['encoding'] . '</b>bei ' . str_replace(
                        array('�','�','�','�','�','�'),
                        array('&Uuml;','&Auml;','&Ouml;','&uuml;','&auml;','&ouml;'),
                        $String
                        ) . '<br>';
			
		*/
		//echo strtoupper($this->MIME[$this->idx]['encoding']);
		
		switch (strtoupper($this->MIME[$this->idx]['encoding']))
		{
		case 'UTF-8' :
		$res = utf8_decode($String);
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
		
		return $res;
	}
function convert_to_XML( $String , $format)
        {
		if($format == '') $format = $this->MIME[$this->idx]['encoding'];
		
                $tmp;
                switch( strtoupper($format) )
                {

                case 'UTF-8':
                       $tmp = utf8_encode($String);
		       

		$tmp = str_replace(
                        array('&'),
                        array('&amp;'),
                        $tmp);

		
		$tmp = str_replace(
                        array('"','<','>'),
                        array('&quot;','&lt;','&gt;'),
                        $tmp);
		       
                        break;
                case 'ISO-8859-1':
                      //echo $String ."<p>\n";
		$tmp = str_replace(
                        array('&'),
                        array('&amp;'),
                        $String);

		
		$tmp = str_replace(
                        array('"','<','>'),
                        array('&quot;','&lt;','&gt;'),
                        $tmp);
			
                        break;
                default:
                $tmp = $String;
                
                }

                if(true)
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
        

} // end of class xml
//hilfsklasse
class XMLelement {

var $next_el;
var $prev_el;
var $name = '';
var $attrib;
var $data;

var $index = 0;

var $position=-1;
var $mark = false;

function &getRefnext($index,$bool_set=false){if(!$bool_set)$this->position = $index; return $this->next_el[$index];}
function &getRefprev(){return $this->prev_el;}

function index_max()
        {
        if(is_array($this->next_el))
        return count($this->next_el);

        return 0;
        }

function setRefnext(&$ref){

                if(is_array($this->next_el))

                        $index = count($this->next_el);

                else

                        $index = 0;

                $this->next_el[$index] = &$ref;


                }

function setRefprev(&$ref){$this->prev_el = &$ref;}

function attribute($name,$value){
        $this->attrib[$name] = $value;
        }

function setdata($data,$pos = null){



                if(is_null($pos))

                        $index = $this->index;
                        
                else
                        $index = $pos;

                $this->data[$index] = (
                                        (substr($data,strlen($data)-1)<>' ') &&
                                        (substr($data,strlen($data)-2)==' ')
                                        )? trim($this->data[$index] .= $data) : ltrim($this->data[$index] .= $data);


        }

function final_data(){$this->index++;}



} // end class element
/*
$txt = '<?xml version=\'1.0\'?>
<root>
        <index>
                <tree name="Hauptseite" pos="0">
                        <tree name="Hauptseite2" pos="4"></tree>
                </tree>
                <tree name="Hauptseite2" pos="1"></tree>
        </index>
<page>
</page>
</root>';


$xml_parser = new xml();
$xml_parser->load_Stream($txt);
echo $xml_parser->cur_node();
$my_list = $xml_parser->list_child_node();

echo '<ul>';
for($i=0;$i<count($my_list);$i++)echo '<li>' . $my_list[$i] . '</li>';
echo '</ul>';

echo $xml_parser->next_node(0);


$my_list = $xml_parser->list_child_node();
echo '<ul>';
for($i=0;$i<count($my_list);$i++)echo '<li>' . $my_list[$i] . '</li>';
echo '</ul>';
*/
?>
