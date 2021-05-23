<?php
/*
 * Created on 07.03.2005
 *
 * take data, add it and give String to browser
 */

 //Status
 $glob_status;
 //Formular
 $glob_form;

class HTML_Builder{
var $arrayObj;
var $System_out = '';
var $counter = 0;
var $title;
var $parameter;
var $shoot_down=false;

//--------------------------------------------
//|          Speichert Seitentitel           |
//--------------------------------------------
function title($title){$this->title=$title;}

//--------------------------------------------
//|          Speichert Seitentitel           |
//--------------------------------------------

function add($html){


                
        $this->System_out[$this->counter] = $html;
                $this->counter++;
        }

//--------------------------------------------
//|          Speichert Seitentitel           |
//--------------------------------------------
	
function set_param($parameter){$this->parameter = $parameter;}

//--------------------------------------------
//|          Speichert Seitentitel           |
//--------------------------------------------

function get_param($name){return $this->parameter;}

//--------------------------------------------
//|          Speichert Seitentitel           |
//--------------------------------------------

function ext_prog($progname){

         $db = new database();
         $sql_main_string = "SELECT prog FROM mikos2_prog_collection WHERE prog_name = '$progname';";
         $rst = $db->get_rst($sql_main_string);

         if(!is_array($this->arrayObj))
         $tmp = 0;
         else
         $tmp = count($this->arrayObj);


         $this->arrayObj[$tmp] = $rst->value(0,'mikos2_prog_collection.prog');
         //echo $rst->value(0,'mikos2_prog_collection.prog') . $rst->rst_num() . $progname;
    }

//--------------------------------------------
//|          Speichert Seitentitel           |
//--------------------------------------------
    
function indim_close(){
     $this->shoot_down = true;
    }

//--------------------------------------------
//|          Speichert Seitentitel           |
//--------------------------------------------
    
function out(){
if($this->shoot_down){
echo '<HTML><script language="JavaScript" type="text/javascript">window.close()</script></HTML>';
}else{
global $glob_form;
        
//erstellt functionen und variablen
	function check(&$cur,&$name,$add,$look){


        reset($add);
        while ($key = key($add[$look]) ) {
        //echo $name[$look][$key];

        if(is_null($name[$look][$key]))
                {
                $name[$look][$key]="!";
                $cur[$look] .= "\n" . $add[$look][$key] . "\n";

                }
                else{
                //echo "redundanter functionsname gefunden: $key " . $add[$look][$key] . "!<p>";
                }
        
        next($add[$look]);
        }


//        if(is_null($cur[$look][$i]["name"]))$res;
        

        }

        $script['func']="";
        $script['var']="";
        $script_names['func']['no'] = "";
        $html="";
        
        //Standard
        $main = "<html>\n";

        $main .= '<head>' . "\n";
        $main .= '<meta http-equiv="Content-Language" content="de" />' . "\n";
        $main .= '<meta name="GENERATOR" content="Surface 0.1" />'  . "\n";
        $main .= '<meta http-equiv="cache-control" content="no-cache">'  . "\n";
        $main .= '<meta http-equiv="pragma" content="no-cache">'  . "\n";
        $main .= '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />'  . "\n";
        $main .= "<title>". $this->title . "</title>\n";
        $main .= '<link rel="stylesheet" type="text/css" href="data.css">';
        $main .= '<style type="text/css">';
        $main .= '</style>';


/*

        
        <meta name="GENERATOR" content="PHPEclipse 1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <title>title</title>
        <script language="JavaScript" type="text/javascript"> */
	//alle zu ladenen programme
        $tmp = count($this->arrayObj);
        $array_res;

		//schreibt alle programmzeilen hintereinander
                for($i = 0 ; $i<$tmp ; $i++){
                     $script['func'] .= $this->arrayObj[$i] . "\n";}



        //geht den angefügten array durch und verarbeitet alle eingaben
        for($i=0;$i<count($this->System_out);$i++){

                if(is_object($this->System_out[$i])){
                
                //html from object
                $html .= $this->System_out[$i]->toString();
                //
                $info1 = $this->System_out[$i]->toScript();
        
                //echo $info1['var']['ich'];

                
                if(!is_null($info1['func']))check(&$script,&$script_names,$info1,'func');
                if(!is_null($info1['var']))check(&$script,&$script_names,$info1,'var');
                //echo                 $script_names['var']['ich'];
                        }
                else
                {        
                //html from primitive
                $html .= $this->System_out[$i];


                } }
                
                $info1['func']['autostart'] = 'function autostart(){}';
                if(!is_null($info1['func']))check(&$script,&$script_names,$info1,'func');
                $info1['func']['finalize'] = 'function finalize(){}';
                if(!is_null($info1['func']))check(&$script,&$script_names,$info1,'func');

$script_res = '<script language="JavaScript" type="text/javascript">' . "\n";
$script_res .= 'var img = new Array()'. "\n";
$script_res .= 'var insert = new Array()'. "\n";
$script_res .= 'var forms = new Array()' . "\n";


$count_form = count($glob_form);
for($i=0;$i<$count_form;$i++)
$script_res .= "forms[$i] = '" . $glob_form[$i] . "';" . "\n";

   //if(autostart()!=null)autostart()
   //if(finalize()!=null)finalize();

$script_res .= $script['var'] . "\n";
$script_res .= $script['func'] . "\n</script>\n";

echo $main . $script_res . '</head><body class="main" onload="autostart();" onUnload="finalize();" >' . $html . "\n</body>\n</html>";

}}
}
?>
