<?php
/*
 * Created on 07.03.2005
 *
 * includes some classes
 * Popup_Field ::= 
 */
 
 $id_num=0;
 
 function id_counter(){
 global $id_num;
 return $id_num++;
 }

 //to make a string saveable in javascript
 function No_new_Line($string){
 
 return str_replace("'","' + " . '"' . "'" . '"' . " + '",str_replace("\n","",$string));
 
 }
 
         function merge_it($array1,$array2){
        
                        $var_array = array_merge($array1['var'],$array2['var']);
                        $func_array= array_merge($array1['func'],$array2['func']);
                        $array_res['var']= $var_array;
                        $array_res['func']= $func_array;
                                
        return $array_res;}
 
 class ObjInterface{
 var $title;
 var $my_id = 0;
 var $typ = 'p';
 var $event;
 var $arrayObj;
 var $able = MULTI;
 
 //function ObjInterface(){$this->my_id = id_counter();}
 
 function getID($name){$this->my_id=$name;}

 function clear(){$this->arrayObj = null;}

 function event($event){
             if(is_array($this->event)){
                     $tmp = count($this->event);$this->event[$tmp]=$event;

                     }else{$this->event[0]=$event;}
                     }

 function add($object){

                if(is_array($this->arrayObj)){
                     $tmp = count($this->arrayObj);$this->arrayObj[$tmp]=$object;

                     }else{$this->arrayObj[0]=$object;}
                     }
                     
    function toString(){

        $tmp = count($this->arrayObj);


        //looking for numbers, registred in elements-array of tablecontainer



                     for($i = 0 ; $i<$tmp ; $i++){

                        //looking for empty variable

                        if(!is_null( $this->arrayObj[$i])){



                                        //requests the typ of its object
                                        if(is_object($this->arrayObj[$i])){

                                        $res .= '' . $this->arrayObj[$i]->toString() . '';
                                        }else{

                                        $res .= '' . $this->arrayObj[$i] . '';}


                        }else{
                                $res .= "&nbsp;"; //shows empty fields
                        }
                }



       return $res;
                }

function toScript(){

        $tmp = count($this->arrayObj);
        $array_res;


                for($i = 0 ; $i<$tmp ; $i++){

                if(is_object($this->arrayObj[$i])){

                        if(is_array($array_res)){
                        $array_res = merge_it($array_res, $this->arrayObj[$i]->toScript());
                        }else{
                        $array_res = $this->arrayObj[$i]->toScript();
                        }

                }else{
                                 $res['var']['none'] = '';

                                 }//if
                }//loop
                
                return $array_res;
    }//function
         
 function factory(){return new ObjectInterface;}
 }
 
 
class FormObj extends ObjInterface{
var $type_tag;
var $property;
var $extra;

function Script_array(){

    return "all_my_tags[" . '$-$-$' . "] = new Array(); \n all_my_tags[" . '$-$-$' . "]['type'] = '" . $this->type_tag . "'; \n all_my_tags[" . '$-$-$' . "]['prop'] = '" . $this->property . "'; \n all_my_tags[" . '$-$-$' . "]['extra'] = '" . $this->extra . "'; \n ";
    }


 }
 
 
 

/*------------------------------------------------------------------
        popup-Field
        
*/
 
class Popup_Field extends ObjInterface {

var $title;
var $DB_container;
var $arrayObj;



//constuktor needs its name
function Popup_Field($title){$this->title = $title;        

                                                         }

//add primitive or Object which will be shown in popup
function add($object){if(is_array($this->arrayObj))
                                                                
                     {$tmp = count($this->arrayObj);$this->arrayObj[$tmp]=$object;

                     }else{$this->arrayObj[0]=$object;}
                     }
                                                                        


function toScript(){





                $tmp = count($this->arrayObj);

        $script = 'function changepopup(pop,id)' . "\n";
        $script .= '{' . "\n";
                $script .= "var temppop = pop \n";
        $script .= 'var x=document.getElementById(id).rows' . "\n";
        $script .= 'var y=x[1].cells' . "\n";

                $script .= 'if(temppop==1){' . "\n";
        $script .= 'temppop=(y[0].innerHTML=="") ? true : false;' . "\n";

        $script .= '}' . "\n";

        $script .= 'if(temppop){' . "\n";
        $script .= 'y[0].innerHTML=insert[id];' . "\n";

        $script .= 'if(next_data)' . "\n";;       
        $script .= 'db_view(next_data);' . "\n";;

        $script .= '}else{' . "\n";
        $script .= 'y[0].innerHTML=""}' . "\n";

        $script .= 'if(pop == 1)changepopimg(temppop,id);';
 
        $script .= '}' . "\n";

       /* 
        
        $script = 'function changepopup(pop,id)' . "\n";
        $script .= '{' . "\n";
        $script .= 'var x=document.getElementById(id).rows' . "\n";
        $script .= 'var y=x[1].cells' . "\n";
        $script .= 'if(pop){' . "\n";
        $script .= 'y[0].innerHTML=insert[id];' . "\n";
         $script .= 'if(next_data)' . "\n";;       
        $script .= 'db_view(next_data);' . "\n";;
        $script .= '}else' . "\n";
        $script .= 'y[0].innerHTML=""' . "\n";
        $script .= '';
        $script .= '}' . "\n";
        
        */
        $res['func']['changepopup']=$script;
        //$var = 'var insert = new Array()'. "\n";

        $array_res['var'][$this->my_id]= "";
        
                for($i = 0 ; $i<$tmp ; $i++){
 

                if(is_object($this->arrayObj[$i])){

                        $html .= "<P>" . $this->arrayObj[$i]->toString() . "</P>";

                        $array_res = merge_it($array_res, $this->arrayObj[$i]->toScript());

                        

                                        //echo $array_res['var']["ich"]; 
                }else{
                        $html .= "<P>" . $this->arrayObj[$i] . "</P>";
                        
                }}
//$array_res['var']['test']='test<p>';
        $html = '<table border="0" width="100%" height="100%" cellpadding="10" bgcolor="#FFFFFF" ><tr><td>' . $html . '</td></tr></table>';
        
        $var = "insert['" . $this->my_id . "']='" . No_new_Line($html) . "'";

//echo " -" . $array_res['var']['du'] . " :-)<p>";
        $res['var'][$this->my_id]= $var;
        $array_res = merge_it($array_res,$res);

   //echo "bei :" . $this->my_id . " ->" . implode(";",$array_res['var']) . '<p>';
        return $array_res;                                                                
                                                                        } 

//standard func :: give result
function toString(){

        $res = '<table border="0" width="100%" cellspacing="0" id="' . $this->my_id .  '" >' . "\n";
        $res .="<tr><td>\n";
        $res .='<table border="0" width="100%" cellspacing="0">' . "\n";
        $res .="<tr>\n";
        $res .='<td width="10" background="field.gif" >&nbsp;</td>' . "\n";
        $res .='<td  bgcolor="#3300bb" ><b><FONT FACE="Arial" SIZE=3 COLOR="#EEEEEE">' . $this->title . '</FONT></b></td>' . "\n";
        $res .='<td bgcolor="#3300bb" width="20"><IMG SRC="popdown.gif" onclick="changepopup(false,' . "'" . $this->my_id . "'" . ')"></td>' . "\n";
        $res .='<td bgcolor="#3300bb" width="20"><IMG SRC="popup.gif" onclick="changepopup(true,' . "'" . $this->my_id . "'" . ')" ></td>' . "\n";
        $res .="</tr></table>\n";
        $res .="</td></tr>\n";
        $res .='<tr><td bgcolor = "#1100bb">';
        $res .='';


        /*$tmp = count($this->arrayObj);
        for($i = 0 ; $i<$tmp ; $i++){
 

                if(is_object($this->arrayObj[$i])){

                        $res .= "<P>" . $this->arrayObj[$i]->toString() . "</P>";
                        
                }else{
                        $res .= "<P>" . $this->arrayObj[$i] . "</P>";
                        
                }
        }*/
        
        $res .= "</td></tr></table>";
        
        return $res;}
        
function factory(){return new Popup_Field('');}
}


//-------------------------------------------------------------

class List_Field extends ObjInterface {

var $title;
var $DB_container;
var $arrayObj;
var $my_pic;


//constuktor needs its name
function List_Field($title){$this->title = $title;        

                                                         }

//add primitive or Object which will be shown in popup
function add($object){if(is_array($this->arrayObj))
                                                                
                     {$tmp = count($this->arrayObj);$this->arrayObj[$tmp]=$object;

                     }else{$this->arrayObj[0]=$object;}
                     }
                                                                        
function pic($pic){$this->my_pic = $pic;}

function toScript(){





                $tmp = count($this->arrayObj);

                $script2 = 'function changepopimg(pop,id)' . "\n";
        $script2 .= '{' . "\n";
        $script2 .= 'var obj=document.getElementById(id + "img")' . "\n";
        $script2 .= 'if(obj!=null){' . "\n";
        $script2 .= 'if(pop)' . "\n";
        $script2 .= 'obj.src = img["minus"].src;' . "\n";
 
        $script2 .= 'else' . "\n";
        $script2 .= 'obj.src = img["plus"].src;' . "\n";                 
        $script2 .= '}}' . "\n";
        $res['func']['changepopimg']=$script2;      



                
        $script = 'function changepopup(pop,id)' . "\n";
        $script .= '{' . "\n";
                $script .= "var temppop = pop \n";
        $script .= 'var x=document.getElementById(id).rows' . "\n";
        $script .= 'var y=x[1].cells' . "\n";

                $script .= 'if(temppop==1){' . "\n";
        $script .= 'temppop=(y[0].innerHTML=="") ? true : false;' . "\n";

       
        $script .= '}' . "\n";

        $script .= 'if(temppop){' . "\n";
        $script .= 'y[0].innerHTML=insert[id];' . "\n";

        $script .= 'if(next_data)' . "\n";;       
        $script .= 'db_view(next_data);' . "\n";;

        $script .= '}else{' . "\n";
        $script .= 'y[0].innerHTML=""}' . "\n";

 
        $script .= 'if(pop == 1)changepopimg(temppop,id);' . "\n";
 
 
        $script .= '}' . "\n";
        $res['func']['changepopup']=$script;
 
 
 

        $array_res['var'][$this->my_id]= "";
        
                for($i = 0 ; $i<$tmp ; $i++){
 

                if(is_object($this->arrayObj[$i])){

                        $html .= "<P style='margin-left:10px'>" . $this->arrayObj[$i]->toString() . "</P>";

                        $array_res = merge_it($array_res, $this->arrayObj[$i]->toScript());

                        

                                        //echo $array_res['var']["ich"]; 
                }else{
                        $html .= "" . $this->arrayObj[$i] . "<br>";
                        
                }}
//$array_res['var']['test']='test<p>';
        $html = '<table border="0" width="100%" height="100%" cellspacing="0" cellpadding="0"  ><tr><td>' . $html . '</td></tr></table>';
        
        $var = "insert['" . $this->my_id . "']='" . No_new_Line($html) . "'";

                


        //listfield = 
      $var_pic = 'img["plus"] = new Image(); img["plus"].src = "plus.gif";' . "\n";
            $var_pic .= 'img["minus"] = new Image(); img["minus"].src = "minus.gif";' . "\n";
      
            $res['var']['picture_List_Field']= $var_pic;
            
        $res['var'][$this->my_id]= $var;
        $array_res = merge_it($array_res,$res);

   //echo "bei :" . $this->my_id . " ->" . implode(";",$array_res['var']) . '<p>';
        return $array_res;                                                                
                                                                        } 

//standard func :: give result
function toString(){

        $res = '<table border="0" width="100%" cellspacing="0" cellpadding="0" id="' . $this->my_id .  '" >' . "\n";
        $res .="<tr><td>\n";
        $res .='<table border="0" width="100%" cellspacing="0"  cellpadding="0" >' . "\n";
        $res .="<tr>\n";
        $res .='<td><b><IMG SRC="plus.gif"  id="' . $this->my_id .  'img"  onclick="changepopup(1,' . "'" . $this->my_id . "'" . ')">';

if(!is_null($this->my_pic))$res .='<IMG SRC="' . $this->my_pic . '"  img"  onclick="changepopup(1,' . "'" . $this->my_id . "'" . ')">';
        $res .='<FONT FACE="Arial" SIZE=3 COLOR="#000000">' . $this->title . '</FONT></b></td>' . "\n";
        //$res .='<td bgcolor="#3300bb" width="20"><IMG SRC="popdown.gif" onclick="changepopup(false,' . "'" . $this->my_id . "'" . ')"></td>' . "\n";
        //$res .='<td bgcolor="#3300bb" width="20"><IMG SRC="popup.gif" onclick="changepopup(true,' . "'" . $this->my_id . "'" . ')" ></td>' . "\n";
        $res .="</tr></table>\n";
        $res .="</td></tr>\n";
        $res .='<tr><td>';
        $res .='';


        /*$tmp = count($this->arrayObj);
        for($i = 0 ; $i<$tmp ; $i++){
 

                if(is_object($this->arrayObj[$i])){

                        $res .= "<P>" . $this->arrayObj[$i]->toString() . "</P>";
                        
                }else{
                        $res .= "<P>" . $this->arrayObj[$i] . "</P>";
                        
                }
        }*/
        
        $res .= "</td></tr></table>";
        
        return $res;}
        
function factory($pic=null){
    $tmp = new List_Field('');
if(!is_null($pic)){
    $tmp->pic($pic);
    }else{
    $tmp->pic($this->my_pic);
    }
        return $tmp;}
}



//---------------------------------------------------------------------------

class variable{
        var $Intern;
        var $mirror;
function setElement($X,$Y,$value){$this->Intern[$X][$Y]=$value;$this->mirror[count($this->mirror)] = " X=$X Y=$Y $value";}
function getElement($X,$Y){return $this->Intern[$X][$Y];}
function show_list(){for($i = 0; $i<count($this->mirror);$i++){$res .=$i . " " . $this->mirror[$i] . "<p>";}echo $res;}

}

 /* table_container creates a table to insert objects
  *
  */
class table_Container extends ObjInterface {

var $table;
var $DB_container="hallo";
var $elements; 



function table_Container(){
                                                        $pointer = &$this;
                                                        
                                                        
                                                        
                                                        $this->table = new table_elements(100,100,1,1,&$pointer);
                                                        $this->elements = new variable();
                                                        $this->elements->setElement(1,1,"Start");

                                                        
                                                        
                                                        }



function add($object,$top,$left,$color="#FFFFFF",$align="CENTER",$valign="MIDDLE"){

                        $find = &$this->seek_it($left,$top);
                        
                        if(!is_null($find)){
                        
                                                
                        if(!($find->add($object,$top,$left,$color,$align,$valign)))echo "Element wurde nicht gefunden: X=$left Y=$top  !";
                                                }}



function static_field($top,$left,$width=null,$height=null){

                        $find = &$this->seek_it($left,$top);

                        if(!is_null($find)){

                        if(!is_null($width))$find->$XRange= '#' . $width;
                        if(!is_null($height))$find->$YRange= '#' . $height;

                       }
                       }




function split_table($top,$left,$direction,$double){
        
                        $find = &$this->seek_it($left,$top);
                        
                        if(is_null($find)){echo "Element wurde nicht gefunden: X=$left Y=$top  !";}
                        
                        else
                        
                        {if(!($find->split_table($top,$left,$direction,$double)))
                        {echo "Koordinaten nicht correct  X=$left Y=$top  !";
                        }/*else{
                                if($direction == 0){$this->elements->setElement($left,$top + 1,"HORIZONTAL");
                                }else{
                                $this->elements->setElement($left + 1,$top,"VERTIKAL");}
                        }*/}}

function &seek_it($X,$Y){
                                
                                $pointer = &$this->table;



                                while(!is_null($pointer->Ynum) &&  $pointer->Ynum <> $Y){ $pointer = &$pointer->down;}

                                while(!is_null($pointer->Xnum) &&  $pointer->Xnum <> $X){$pointer = &$pointer->left;}

                                if($pointer->Xnum <> $X || $pointer->Ynum <> $Y){return false;}
                                        
                                        else{return $pointer;}
                                        
                                        }




//give true back, when elements has a shorter Y 
function search_nums($X,$Y){

                                        //echo "requesting element X=$X Y=$Y " . $this->elements->getElement($Y,$X) . " $i <p>";
                                                        for($i=1;$i<$X;$i++){
                                        
                                                                if(!is_null($this->elements->getElement($i,$Y))){
                                                                        //echo $this->elements->getElement($X,$i) . " found on pos $i<p>";

                                                                        return false;}}

                                                                
                                                                return true;}

function toString(){
        //$this->test();

                                
                                return "<table border='0' width='100%' height='100%'  cellpadding='0' cellspacing='0' >\n" . $this->table->toString() . "\n</table>";}
                                
function toScript(){

        $tmp = $this->table->toScript();
        //echo $tmp['var']['ich'];
        return $tmp;}
}


class table_elements{

var $back;

var $down;
var $left;

var $XRange;
var $YRange;

var $Xnum;
var $Ynum;

var $color;
var $align;
var $valign;


var $object;

function table_elements($XRange,$YRange,$Xnum,$Ynum,&$back){

                                                                $this->XRange = $XRange;
                                                                $this->YRange = $YRange;
                                                                $this->Xnum = $Xnum;
                                                                $this->Ynum = $Ynum;
                                                                $this->back = &$back;
                                                                
                                        $this->back->DB_container .= " Xdudavorn $Ynum ";  
        //$this->helper();
                                                                }
                                                                
//function helper(){echo $this->back->DB_container . " in helper <p>";}

function split_table($top,$left,$direction,$double){

                                        if($this->Xnum <> $left || $this->Ynum <> $top){return false;}
                        
                                        $pointer = &$this->back;
                                        $pointer->DB_container .= " splitt ";
                        
                                        switch ($direction){
                                        case 0:
                                                //Horizontal
                                        
                                                $new_range = ($this->YRange * $double);
                                                $this->YRange = 100 - $new_range;
                                                $tmp = new table_elements(
                                                                                        $this->XRange,
                                                                                        $new_range,
                                                                                        $this->Xnum,
                                                                                        $this->Ynum + 1,
                                                                                        &$this->back
                                                                                        );
                                                                                        //echo " horizontal ";

                                
                                                

                                                $this->back->elements->setElement($this->Xnum,$this->Ynum + 1," ($left $top + 1 Horizontal $double) ");
                                                $pointer = &$this->down;
                        
                                                $this->down = &$tmp;
                                                $tmp->down = &$pointer; 
                                                break;
                                                
                                        case 1:
                                                //Vertikal
                                                $new_range = ($this->XRange * $double);
                                                $this->XRange = 100 - $new_range;
                                                $tmp = new table_elements(
                                                                                        $new_range,
                                                                                        $this->YRange,
                                                                                        $this->Xnum + 1,
                                                                                        $this->Ynum,
                                                                                        &$this->back        );
                                                                                        //echo " vertikal ";
                                                
                                        
                                                $this->back->elements->setElement($this->Xnum + 1,$this->Ynum," ($left + 1 $top  Horizontal $double) ");                                                                        
                                                
                                                //echo $this->back->test;
                                                $pointer = &$this->left;
                                                $this->left = &$tmp;
                                                $tmp->left = &$pointer;                                                                                 
                                                break;

                                        }
                                        //echo "<p> abgeschlossen </p>";
                                        return true;
}


function add($object,$top,$left,$color,$align,$valign){

                                        

                                        if($this->Xnum <> $left || $this->Ynum <> $top)return false;
                                        //echo "gefunden!<p>";
                                        
                                        if(is_array($this->object))
                                                {$tmp = count($this->object);
                                                        $this->object[$tmp]=$object;
                                                
                                                }else{$this->object[0]=$object;
                                                        
                                                }


                                        $this->color = $color;
                                        $this->align = $align;
                                        $this->valign = $valign;
                                        return true;
                                        
        }


function toString(){

        $tmp = count($this->object);

        //looking for numbers, registred in elements-array of tablecontainer
        //echo "abfrage1 :";
        if($this->back->search_nums($this->Xnum,$this->Ynum))$res .= "<tr>\n";
        
                if(strpos($this->XRange,'#')===true)echo 'klappt';
        
        $res .='<td width="' . $this->XRange . '%" height="' . $this->YRange . '%" bgcolor="' . $this->color . '" align="' . $this->align .  '" valign="' . $this->valign . '" >'; //starts table

                for($i = 0 ; $i<$tmp ; $i++){

                        //looking for empty variable
                        
                        if(!is_null( $this->object[$i])){
        


                                        //requests the typ of its object 
                                        if(is_object($this->object[$i])){
                                        
                                        $res .= '' . $this->object[$i]->toString() . '';
                                        }else{
                                        $res .= '' . $this->object[$i] . '';}

                                        
                        }else{
                                $res .= "&nbsp;"; //shows empty fields
                        }
                }
                                        
        $res .= "</td>\n"; //close col-container
                                        
        if(!is_null($this->left)){$res .= $this->left->toString();
                                                        
        
                                                        }
        //echo "abfrage2 :";                                                
        if($this->back->search_nums($this->Xnum,$this->Ynum))$res .= "</tr>\n";                                                        

        if(!is_null($this->down))$res .= $this->down->toString();                 


        return $res;
                }
                
        function toScript(){
                
        $tmp = count($this->object);

        
         

        $array_res;


        
                for($i = 0 ; $i<$tmp ; $i++){
 

                if(is_object($this->object[$i])){

                        //$html .= "<P>" . $this->object[$i]->toString() . "</P>";

                        if(is_array($array_res)){
                        $array_res = merge_it($array_res, $this->object[$i]->toScript());
                        }else{
                        $array_res = $this->object[$i]->toScript();        
                        }
                        

                                        //echo $array_res['var']["ich"]; 
                }else{
                        //$html .= "<P>" . $this->object[$i] . "</P>";
                        
                }}



        

if(!is_null($this->left)){$array_res = merge_it($array_res,$this->left->toScript());}

if(!is_null($this->down)){$array_res = merge_it($array_res,$this->down->toScript());}                 
        //echo $array_res['var']['ich'] . $this->Xnum;
        //$array_res = $this->merge_it($array_res,$res);


        return $array_res;        
        
        }
        
}

class element extends ObjInterface {
var $able = SINGLE;
var $space = '1';
var $typ = 'p';
var $inline=false;
var $toolbar;
var $extraprog='';
var $bez;
var $colapse = ' width="100%" ';
var $my_pic;

function element($label=null,$event=null,$space=0,$pic=null){$this->bez = $label;if(!is_null($pic))$this->my_pic = $pic; $this->space = $space; if(!is_null($event))$this->event[0] = $event;}

function colapse(){$this->colapse = '';}

function label($label){$this->bez = $label;}

function pic($pic){$this->my_pic = $pic;}

function toolbar($id){$this->toolbar='onclick="show_popup("' . $id . '",100,false);';$this->extraprog='"';}

function extrafunc($function){$this->extraprog = $function;}

function toString(){

               $loop = count($this->event);
        for($i = 0;$i<$loop;$i++){$event .= $this->event[$i] . " ";}


        $res = '<p id="' . $this->my_id . '" ' . $event . ' style="margin-top:1px;margin-bottom:1px;" ><table border="0" ' . $this->colapse . ' cellspacing="1" cellpadding="0" ' . $this->toolbar . $this->extraprog . '><tr>';

        if(!is_null($this->bez) || !is_null($this->my_pic)){
            if(!is_null($this->my_pic))$pic = '<IMG SRC="' . $this->my_pic . '" >';
            if($this->space == 0)$this->space = 10;
            $bez = 'width="' . $this->space . '"';
            $res .= "<td $bez >" . $pic . $this->bez . '</td>' ;}


        $count = count($this->arrayObj);



        for($i=0;$count > $i;$i++){


            if(is_object($this->arrayObj[$i]))


                         $res .= '<td>' . $this->arrayObj[$i]->toString() . '</td>';
            else
                                              $res .= '<td>' . $this->arrayObj[$i] . '</td>';}

        $res .= '</table></p>';

        return $res; }




function toScript(){

         //$res['var'][$this->my_id]= $var;
         $res['var']['none'] = '';

         return $res;}



function factory($pic=null){
    if(!is_null($pic))$this->pic = $pic;

    if(is_null($this->bez) && is_null($this->my_pic))return new element();
    else return new element($this->bez,null,$this->space,$this->my_pic);


    }
        }
        
class spanelement extends ObjInterface {
var $able = SINGLE;
var $typ = 'span';
var $event;
var $bez;

function element($label=null){$this->bez = $label;}

function event($event){
             if(is_array($this->event)){
                     $tmp = count($this->event);$this->event[$tmp]=$event;

                     }else{$this->event[0]=$event;}
                     }

function label($label){$this->bez = $label;}



function toString(){
        $loop = count($this->event);
        for($i = 0;$i<$loop;$i++){$event .= $this->event[$i] . " ";}


        $res = '<span id="' . $this->my_id . '" ' . $event . ' >' . $this->bez . $this->arrayObj[0];

        $count = count($this->arrayObj);

        for($i=1;$count > $i;$i++){$res .= $this->arrayObj[$i] . '&nbsp;';}

        $res .= '</span>';

        return $res; }




function toScript(){

         //$res['var'][$this->my_id]= $var;
         $res['var']['none'] = '';

         return $res;}



function factory($label=null){if(is_null($this->bez))return new spannelement();else return new spannelement($this->bez);}
        }

class Menue extends ObjInterface {

var $able = SINGLE;
var $event;

var $x;
var $y;
function position($x,$y){$this->x=$x;$this->y=$y;}




function toString(){

        $loop = count($this->arrayObj);
        
        $res = '<Div style="background-color:rgb(60%,90%,75%) ; position:absolute;top:' .  $this->y . 'px;left:' . $this->x . 'px;width:600px;" >&nbsp;';
        $res .= " \n";
        for($i = 0;$i<$loop;$i++){
        $res .= " \n";
        $res .= '<span id="' . $this->arrayObj[$i]->my_id . '" onclick="show_popup(' . "'" . $this->arrayObj[$i]->my_id . "','" . ($this->x + $i * 100 + 15) . "'" . ',true)" style="margin-left:20px;white-space:nowrap;position:absolute;left:' . ($i * 100) . 'px;">' . $this->arrayObj[$i]->toString() . '</span>';
        $res .= " \n";
        }
        return $res . '</div><div id="' . $this->my_id . '" style="background-color:rgb(60%,90%,75%) ;z-index:100;position:absolute;top:' .  ($this->y + 20) . ';left:' . $this->x . ';visibility:hidden;" onMouseup="show_popup(0,0,false)" >&nbsp</div>'; }




function toScript(){
                
        $tmp = count($this->arrayObj);

        
         

        $array_res;


        
                for($i = 0 ; $i<$tmp ; $i++){
 

                if(is_object($this->arrayObj[$i])){

                        //$html .= "<P>" . $this->object[$i]->toString() . "</P>";

                        if(is_array($array_res)){
                        $this->arrayObj[$i]->curid = $this->my_id;

                        $array_res = merge_it($array_res, $this->arrayObj[$i]->toScript());
                        }else{
                        $array_res = $this->arrayObj[$i]->toScript();        
                        }
                        

                                        //echo $array_res['var']["ich"]; 
                }else{
                        //$html .= "<h1>" . $this->object[$i] . "</h1>";
                        
                }}


        $menue = "function show_popup(id,my_left,bool)";
                $menue .= "{\n";

                //$menue .= 'alert(" inhalt ist " + document.getElementById(id).id + " und " + my_left);' . "\n";
                $menue .= '' . "\n";
                $menue .= 'if(bool){' . "\n";
                $menue .= 'document.all.' . $this->my_id . '.style.visibility = "visible";' . "\n";
                $menue .= 'document.all.' . $this->my_id . '.style.left = my_left;' . "\n";
                $menue .= 'document.getElementById(' . "'" . $this->my_id . "'"  . ').innerHTML = insert[id];;' . "\n";
                $menue .= '' . "\n";
                $menue .= '}else{' . "\n";
                $menue .= 'document.all.' . $this->my_id . '.style.visibility = "hidden";}' . "\n";




                $menue .= '' . "\n";
                $menue .= '' . "\n";
                $menue .= '' . "\n";


//                $menue .= 'p.show(window.event.x - 5,window.event.y + 15,200,300,document.body);' . "\n";
//                $menue .= 'alert(pbody.document.all.popX.clientHeight);' . "\n";
//                $menue .= 'p.resizeTo(300,300);' . "\n";
                $menue .= '';
                $menue .= '}' . "\n";
        $array_res['func']['show_popup'] = $menue;
        



        return $array_res;        
        
        }
         
         

function factory(){return new Menue();}
}

class Menue_element extends ObjInterface {

var $curid;

function title($title){$this->title = $title;}

function event($event){
             if(is_array($this->event)){
                     $tmp = count($this->event);$this->event[$tmp]=$event;

                     }else{$this->event[0]=$event;}
                     }
                     
function toString(){

        return $this->title; }




function toScript(){

       $array_res['var']['none']= "";

                $tmp = count($this->arrayObj);
  
  $html = '<table border="0" id="popX">';
  
                for($i = 0 ; $i<$tmp ; $i++){
 

                if(is_object($this->arrayObj[$i])){
                        $this->arrayObj[$i]->toolbar($this->curid);
                        $html .= "<tr><td >" . $this->arrayObj[$i]->toString() . "</tr></td>";

                        //$array_res = merge_it($array_res, $this->arrayObj[$i]->toScript());

                        

                                        
                }else{
                        $html .= "<tr><td>" . $this->arrayObj[$i] . "</tr></td>";
                        
                }}

                $html .= "</table>";

        $html = "insert['" . $this->my_id . "']='" . No_new_Line($html) . "'\n";



        $res['var'][$this->my_id]= $html ;
   


                $menue2 = 'function new_rst()' . "\n";
                $menue2 .= '{' . "\n";
                $menue2 .= "document.write('toScript wurde nicht überschrieben!')" . "\n";
                $menue2 .= '}' . "\n";

         $res['func']['new_rst'] = $menue2;

         return $res;}                     


 }

class combo_Box{}

class Frame{

function Frame (){}
}

class Plattform extends ObjInterface {

Function toString(){

        $loop = count($this->event);
        for($i = 0;$i<$loop;$i++){$event .= $this->event[$i] . " ";}


return '<div id="' . $this->my_id . '" ' . $event . ' >' . parent::toString() . '</div>';
    }


}


class table_list extends ObjInterface{
var $posY;
var $posX;
function add($Object,$posX,$posY){$this->arrayObj[$posX][$posY]=$Object;
                                  if($this->posX <= $posX)$this->posX = $posX;
                                  if($this->posY <= $posY)$this->posY = $posY;
         }

function toString(){

       $loop = count($this->event);
       
              for($i = 0;$i<$loop;$i++){$event .= $this->event[$i] . " ";}


        //looking for numbers, registred in elements-array of tablecontainer

                   $res = '<table id="' . $this->my_id . '" ' . $event . ' width="100%" height="100%" >';

                     for($i = 0 ; $i<=$this->posX ; $i++){
                       $res .= '<tr>';
                            for($j = 0;$j<=$this->posY;$j++){

                        //looking for empty variable

                        if(!is_null( $this->arrayObj[$i][$j])){



                                        //requests the typ of its object
                                        if(is_object($this->arrayObj[$i][$j])){

                                        $res .= '<td>' . $this->arrayObj[$i][$j]->toString() . '</td>';
                                        }else{

                                        $res .= '<td>' . $this->arrayObj[$i][$j] . '</td>';}


                        }else{
                                $res .= '<td>' . "&nbsp;" . '</td>'; //shows empty fields
                        }
                        

                        
                    }//loop $J
                    $res .= '</tr>';
                }//loop $i

                $res .= '</table>';


       return $res;
                }

        function toScript(){

        $tmp = count($this->arrayObj);
        $array_res;


                for($i = 0 ; $i<$tmp ; $i++){

                if(is_object($this->arrayObj[$i])){

                        if(is_array($array_res)){
                        $array_res = merge_it($array_res, $this->arrayObj[$i]->toScript());
                        }else{
                        $array_res = $this->arrayObj[$i]->toScript();
                        }

                }else{
                                 $res['var']['none'] = '';

                                 }//if
                }//loop

                return $array_res;
    }//function

 function factory(){return new table_list();}
 function clear(){parent::clear();$this->posY = ($this->posX = 0);}
}

class Form_Container extends ObjInterface{

var $xml;
var $rst;
var $con;
var $decr;
 function get_sql($sql){

 if(is_null($this->arrayObj)){
   echo "Bitte erst alle Objekte einf&uuml;gen";
}else{

$db = new Database();
$rst=$db->get_rst($sql);

//legt mindestens einen eintrag an.
if($rst->rst_num() == 0){
    $prim = $rst->prim_field();
    $table = $rst->table;
    $db->SQL('insert ' . $table[0] . ' ( ' . $prim[0] . ') values (1);');
    $rst=$db->get_rst($sql);
      }

 $this->rst=$rst;

 $list = $this->con; // $rst->db_field_list();
 $len = count($list);
 $prim = $rst->prim_field();                                                                   echo $this->rst->type($prim[0],'Extra');
 $xml = '<dataset id="' . $this->my_id . '" many="' . $this->rst->rst_num() . '" extra="' . $this->rst->type($prim[0],'Extra') . '" primary="' . $prim[0] .'" consist="' . $len . '" table="' . $this->rst->table[0] . '" >';

// for($i=0;$i<$len;$i++){
// $xml .= '<field>' . $list[$i] . '</field>';
// }

 $rst->first_ds();
 $pos = 0;
 while(!$rst->EOF()){

 for($i=0;$i<$len;$i++){
 //echo $rst->value($list[$i]) . '!';

 $xml .= '<data  field=\"' . $list[$i] . '\" edit="false" type="' . $rst->type($list[$i],'Type') . '" null="' . $rst->type($list[$i],'Null') . '" default="' . $pos . '" num="' . $pos . '" >' . $rst->value($list[$i]) . '</data>';
        }
        $pos++;
        $rst->next_ds();
        }

         $xml .= '</dataset>';

        $this->xml = $xml;

}}

function saveback($window){

    $arg = $window->parameter[$this->my_id];

    if(!is_null($arg)){
    $arg = stripslashes($arg);
    $description['table']= substr($arg,$pos = (strpos($arg,'table="')+7),strpos($arg,'"',$pos + 1) - ($pos));
    $description['many']= substr($arg,$pos = (strpos($arg,'many="')+6),strpos($arg,'"',$pos + 1) - ($pos));
    $description['consist']= substr($arg,$pos = (strpos($arg,'consist="')+9),strpos($arg,'"',$pos + 1) - ($pos));
    $description['primary']= substr($arg,$pos = (strpos($arg,'primary="')+9),strpos($arg,'"',$pos + 1) - ($pos));
    $description['extra']= substr($arg,$pos = (strpos($arg,'extra="')+7),strpos($arg,'"',$pos + 1) - ($pos));
    $tablelist;

$pos = 0;
for($loop=0;$loop<$description['many'];$loop++){
$pos2 = 7;
for($loop2=0;$loop2<$description['consist'];$loop2++){

$tmp = substr($arg,$pos = (strpos($arg,'<data ',$pos+1)+5),strpos($arg,'</data>',$pos + 1) - ($pos ));

$rst_pos = substr($tmp,$pos2 = (strpos($tmp,'num="')+5),strpos($tmp,'"',$pos2 + 1) - ($pos2));
$rst_table = substr($tmp,$pos2 = (strpos($tmp,'field="')+7),strpos($tmp,'"',$pos2 + 1) - ($pos2));

//erstellt liste
 //echo '<b>' . $rst_table . '</b>';
if($loop == 0){
//echo $rst_table;
if (!is_array($tablelist)){
$tablelist[0] = $rst_table;
}else{
$tablelist[count($tablelist)] = $rst_table;
    }
}
//daten werden in array gespeichert
$data[$rst_pos][$rst_table]['value'] = substr($tmp,$pos2 = (strpos($tmp,'>')+1));
$data[$rst_pos][$rst_table]['neu'] = substr($tmp,$pos2 = (strpos($tmp,'neu="')+5),strpos($tmp,'"',$pos2 + 1) - ($pos2));
$data[$rst_pos][$rst_table]['edit'] = substr($tmp,$pos2 = (strpos($tmp,'edit="')+6),strpos($tmp,'"',$pos2 + 1) - ($pos2));
$data[$rst_table]['type'] = substr($tmp,$pos2 = (strpos($tmp,'type="')+6),strpos($tmp,'"',$pos2 + 1) - ($pos2));
$data[$rst_table]['null'] = substr($tmp,$pos2 = (strpos($tmp,'null="')+6),strpos($tmp,'"',$pos2 + 1) - ($pos2));

echo $rst_pos . '!!!!!!!!!!!!!!!!!!!!!!';

//echo " <b>im Text mit:</b> " . str_replace('>',']',$tmp) . " <b>--hier</b> $help <b>;an der posiston:</b> $pos2";

}//innere schleife

    //echo $arg;
    //echo $description['consist'];
} //aussere schleife


$rst = new rst();
$rst->table[0] = $description['table'];
//felder werden organisiert
for($loop2=0;$loop2<$description['consist'];$loop2++){

if($description['primary'] == $tablelist[$loop2]){
    $rst->setField($tablelist[$loop2],$data[$tablelist[$loop2]]['type'],$data[$tablelist[$loop2]]['null'],'PRI','',$description['extra']);
    }else{
    $rst->setField($tablelist[$loop2],$data[$tablelist[$loop2]]['type'],$data[$tablelist[$loop2]]['null'],'','','');
    }
}

//werte
for($loop=0;$loop<$description['many'];$loop++){
    for($loop2=0;$loop2<$description['consist'];$loop2++){

echo '<p><b>' . $data[$loop][$tablelist[$loop2]]['value'] . '</b></p>';

    $rst->setValue(
                   $tablelist[$loop2],
                   $data[$loop][$tablelist[$loop2]]['value'],
                   null,
                   true
                   );
    }
  $rst->update();


 /*
    for($loop=0;$loop<$description['many'];$loop++){
$flash = false;
    for($loop2=0;$loop2<$description['consist'];$loop2++){
    if($data[$loop][$tablelist[$loop2]]['edit']=='true'){
         $rst->setValue(
                   $tablelist[$loop2],
                   $data[$loop][$tablelist[$loop2]]['value'],
                   $loop,
                   false
                   );
               $flash = true;
               }

    }
if($flash){$rst->update();}
}        */
}//function


//raus damit
return $rst;
}
return null;
}

function add($object,$field,$connect,$description){

        if(is_subclass_of($object,'FormObj')){
        $object->getID($field);
        if(is_array($this->con))
        $len = count($this->con);

        else
        $len = 0;
        
        $this->con[$len]=$connect;
        $this->decr[$len]=$description;
        }
        
        parent::add($object);

        }


    function toString(){

        $list = $this->con;
        $len = count($list);

        $tmp = count($this->arrayObj);


                     $res .= '<FORM action="' . $PHP_SELF . '" method="post" id="' . $this->my_id . '" many="0" consist="0" table="" primary="" extra="" ><table border="1" id="' . $this->my_id . '_tbl" counter="1" formname="' . $this->my_id . '" ><tr>' . "\n";

                     for($k=0;$k<$len;$k++){
                     $res .= '<th>' . $this->decr[$k] . '</th>' . "\n";

                     }
                     $res .= '</tr>' . "\n";
                    /*
                     for($i = 0 ; $i<$tmp ; $i++){

                        //looking for empty variable

                        if(!is_null( $this->arrayObj[$i])){



                                        //requests the typ of its object
                                        if(is_object($this->arrayObj[$i])){

                                        if(is_subclass_of($this->arrayObj[$i],'FormObj')){
                                        $this->arrayObj[$i]->value = 'hallo';//$this->rst->value($this->arrayObj[$i]->my_id,0);
                                         }

                                        $res .= '' . $this->arrayObj[$i]->toString() . '';
                                        }else{

                                        $res .= '' . $this->arrayObj[$i] . '';}


                        }else{
                                $res .= "&nbsp;"; //shows empty fields
                        }
                }        */

                     $res .= '</table><button onclick="saveback(document.all.' . $this->my_id . '_tbl,null)">weitere Zeile</button><button onclick="store()" >Speichern</button><button>Verwerfen</button></FORM>' . "\n";

       return $res;
                }
                
function toScript(){
global $glob_form;
        $tmp = count($this->arrayObj);
        $array_res;
        $cur_count=0;

                for($i = 0 ; $i<$tmp ; $i++){

                if(is_object($this->arrayObj[$i])){

                if(is_subclass_of($this->arrayObj[$i],'FormObj')){
                    $javascript_array_tmp = $this->arrayObj[$i]->Script_array();
                    $javascript_array .= str_replace ( '$-$-$', $cur_count++, $javascript_array_tmp );}
                    
                        if(is_array($array_res)){
                        $array_res = merge_it($array_res, $this->arrayObj[$i]->toScript());

                        }else{
                        $array_res = $this->arrayObj[$i]->toScript();
                        }

                }else{
                                 $res['var']['none'] = '';

                                 }//if
                }//loop

                $res = $array_res;

        $list = $this->rst->db_field_list();
        $len = count($list);



$list = '';


        $script = "
var many = 0;
var consist = 0;
var table = 0;
var primary ='';
var extra = '';
function avanti(){
    this.edit = 'true';
    }

var tablelist = new Array;
function saveback(object,add)
{
var all_my_tags = new Array();
var counter = parseInt(object.counter);
var form_obj=document.getElementById(object.formname);
var countup=0;
if(add==null)form_obj.many = parseInt(form_obj.many) + 1;

$javascript_array

var x=object;

x.insertRow(counter)
x = x.rows

x[counter].insertCell(0)

for(var i = 0; i < $len; i++){

//if(add==null){

switch(all_my_tags[i]['type']) {
case \"INPUT\":
//alert('<INPUT ID=\"x\" ' + all_my_tags[i]['prop'] + ((add==null) ? 'value=\"\"' : add[i][0] + ' value=\"' + add[i][1] + '\" ') + ' >');
html_tag = document.createElement(\"INPUT\");
html_tag.setAttributeNode(document.createAttribute(\"field\"));
html_tag.setAttributeNode(document.createAttribute(\"edit\"));
html_tag.setAttributeNode(document.createAttribute(\"num\"));
html_tag.setAttributeNode(document.createAttribute(\"typus\"));
html_tag.setAttributeNode(document.createAttribute(\"zero\"));
html_tag.setAttributeNode(document.createAttribute(\"neu\"));
html_tag.onclick = avanti;

alert();


html_tag.field = tablelist[object.formname][i]['field'];
html_tag.edit = tablelist[object.formname][i]['edit'];
html_tag.num = ((add==null) ? (parseInt(form_obj.many)-1) : add[i]['num']);
alert(html_tag.num);
html_tag.typus = tablelist[object.formname][i]['type'];
html_tag.zero = tablelist[object.formname][i]['null'];
html_tag.id = object.formname + ($len * (parseInt(object.counter)-1)+ i);
html_tag.value = ((add==null) ? '' : add[i]['value'])
html_tag.neu = ((add==null) ? 'true' : 'false')

x[counter].insertCell(i).appendChild(html_tag);

//form_obj = document.getElementById('x');



break;
// case \"2\":

// break;
// case \"3\":

// break;
// case \"4\":

// break;
default:

break;
}

//x[counter].insertCell(i).innerHTML = '<INPUT SIZE=\"10\" MAXLENGTH=\"30\">';
//}else
//x[counter].insertCell(i).innerHTML = '<INPUT SIZE=\"10\" MAXLENGTH=\"30\">';
}

object.counter = ++counter;

      }";


        $obj = "function XML(string)\n{ \n this.text = string;\n}";

        $res['func']['obj_saveback']=$obj;
        $res['func']['saveback']=$script;

//speichert den formularnamen
if(is_array($glob_form)){
$count_form = count($glob_form);
$glob_form[$count_form] = $this->my_id;
}else{
$glob_form[0] = $this->my_id;}
$script2 = "function autostart(){

for (var i=0;i<forms.length;i++){

var obj = document.getElementById(forms[i] + '_tbl');
var form_obj = document.getElementById(forms[i]);
var temp = insert[forms[i]];
var temp2 = '';
var pos = temp.search('<dataset');
var pos2;
if(pos != -1){

var tmp = temp.slice(pos);

pos = tmp.search('>');

var main = tmp.slice(8,pos);

many = main.slice(main.search('many=\"')+6)
many = parseInt(many.slice(0,form_obj.many.search('\"')))
form_obj.many = many;

consist = main.slice(main.search('consist=\"')+9)
consist = parseInt(consist.slice(0,consist.search('\"')))
form_obj.consist = consist;

table = main.slice(main.search('table=\"')+7)
table = table.slice(0,table.search('\"'))
form_obj.table = table;

primary  = main.slice(main.search('primary=\"')+9)
primary  = primary.slice(0,primary.search('\"'))
form_obj.primary = primary;

extra  = main.slice(main.search('extra=\"')+9)
extra  = primary.slice(0,extra.search('\"'))
form_obj.extra = extra;

temp = tmp.slice(pos+1,tmp.search('</dataset>') )

    }

var data = new Array;
var my_count = 0;
var countup = 0;

tablelist[forms[i]] = new Array;

while((pos2=temp.search('<data')) != -1){
pos = temp.search('</data>');

tmp = temp.slice(pos2 + 5,pos);


if(countup==0)tablelist[forms[i]][my_count]= new Array;

data[my_count]= new Array;

data[my_count]['value'] = tmp.slice((pos2 = tmp.search('>')) + 1);
tmp = tmp.slice(0,pos2);

temp2 = tmp.slice((tmp.search('field=\"')) + 7);
data[my_count]['field'] = temp2.slice(0,temp2.search('\"'));

if(countup==0)tablelist[forms[i]][my_count]['field'] = data[my_count]['field'];

temp2 = tmp.slice((tmp.search('edit=\"')) + 6);
data[my_count]['edit'] = temp2.slice(0,temp2.search('\"'));

if(countup==0)tablelist[forms[i]][my_count]['edit'] = data[my_count]['edit'];

temp2 = tmp.slice((tmp.search('num=\"')) + 5);
data[my_count]['num'] = temp2.slice(0,temp2.search('\"'));

if(countup==0)tablelist[forms[i]][my_count]['num'] = data[my_count]['num'];

temp2 = tmp.slice((tmp.search('type=\"')) + 6);
data[my_count]['type'] = temp2.slice(0,temp2.search('\"'));

if(countup==0)tablelist[forms[i]][my_count]['type'] = data[my_count]['type'];

temp2 = tmp.slice((tmp.search('null=\"')) + 6);
data[my_count]['null'] = temp2.slice(0,temp2.search('\"'));

if(countup==0)tablelist[forms[i]][my_count]['null'] = data[my_count]['null'];

if(my_count == (consist - 1)){
saveback(obj,data);
my_count = -1;
data = null;
data = new Array;
}

temp = temp.slice(pos + 7);
my_count++;
}
countup++;
}}";


$script3 = "
function store()
{

for (var i=0;i<forms.length;i++){

var save = document.getElementById(forms[i]);
var XML_STRING = '<dataset many=\"' + save.many + '\" consist=\"' + save.consist + '\" primary=\"' + save.primary + '\" extra=\"' + save.extra + '\" table=\"' + save.table + '\" >';
var rot =  parseInt(save.many) * parseInt(save.consist) - 1;
var data = '';
for (var j=0;j<=rot;j++){

data = document.getElementById(forms[i] + j);

    XML_STRING += '<data id=\"' + data.id + '\", field=\"' + data.field +
               '\" edit=\"' + data.edit + '\" num=\"' + data.num + '\" type=\"' + data.typus +
               '\" neu=\"' + data.neu + '\" null=\"' + data.zero + '\" >' + data.value + '</data>';

}

 if(i == 0){
     save.innerHTML = '<INPUT type=\"hidden\" name=\"' + forms[i] + '\" value=\'' + XML_STRING + '</dataset>' +  '\'>';

     }
}

 document.getElementById(forms[0]).submit();
}
";


        $res['func']['autostart']=$script2;
        $res['func']['store']=$script3;
        $res['var'][$this->my_id]= 'insert[' . "'" . $this->my_id . "'" . '] = ' . "'" . $this->xml . "'" . ';';
        return $res;
        }
}


class Formfield extends FormObj{
var $type_tag;
var $property;
var $extra;

function Formfield(){$this->type_tag = 'INPUT';$this->property = 'SIZE="20" MAXLENGTH="30"';$this->extra = '';}

    function toString(){return '<INPUT SIZE="30" MAXLENGTH="30">';}
}

/*
$script = '
var html_buffer = new Array();
html_buffer[0] = '';
html_buffer[1] = '';
html_buffer[2] = '';
html_buffer[3] = '';
html_buffer[4] = '<table border="0" width="100%"><tr><td colspan="2" class="main" >Staffelpreisliste</td><td><button>weitere Zeile</button></td></tr>';
html_buffer[4] += '<tr><th>Beschreibung</th><th>Netto</th><th>Brutto</th></tr>';
html_buffer[4] += '<tr><td><input type="text" name="desc1" value="" onKeyUp="updateGross()"></td><td><input type="text" name="netto1" value="" onKeyUp="updateGross()"></td><td><input type="text" name="brutto1" value="" onKeyUp="updateGross()"></td></tr></table>';
function saveback()
{


var x=document.all.bigtable.rows

       var y1=x[11].cells;
       var y2=x[12].cells;

       if(document.all.ckbstep.checked){

       html_buffer[0] = y1[0].innerHTML;
       html_buffer[1] = y1[1].innerHTML;
       html_buffer[2] = y2[0].innerHTML;
       html_buffer[3] = y2[1].innerHTML;

       y1[0].colSpan = 2;
       y1[0].innerHTML=html_buffer[4];

       x[11].deleteCell(1);
       x[12].deleteCell(1);
       x[12].deleteCell(0);

       }else{

       html_buffer[4] = y1[0].innerHTML;

       x[11].insertCell(1);
       x[12].insertCell(0);
       x[12].insertCell(1);

              y1[0].colSpan = 1;

       y1[0].innerHTML = html_buffer[0];
       y1[1].innerHTML = html_buffer[1];
       y2[0].innerHTML = html_buffer[2];
       y2[1].innerHTML = html_buffer[3];

       }

//html_buffer[0] = document.all.netto.cells;



}';

</script>
*/


?>
