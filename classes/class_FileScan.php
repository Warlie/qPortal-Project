<?php

/**
/* add_path(<path>) : 
/* prohib_path(<path>):
/* add_tag(<tag>):
/* add_fix(<myfix>):
/* switch_cross_seek(<bool=true>)
/* result()
/* switch_cross_seek($bool=true)
/* flash()
*/
  
class File_Scan
{

//Verzeichniss-Liste
var $path;

//Tag-Liste
var $tag;

//Liste der erlaubten Dateibeschreibungen
var $fix;
//liste von Verzeichnissen, die nicht durchsucht werden
var $prohib;
//seperater DokumentenString
private $document = null;

//cross_seek
private $cross_seek = array();

//Variablen f�r den durchlauf
var $start_path;
var $table;
var $fin_list;
var $path_parameter;

//----------------------------------------------------------------------
//Selectors
//----------------------------------------------------------------------

  // +-----------------------------------------------------------------------
  // | function add_path
  // +-----------------------------------------------------------------------

        //f�gt einen Pfad hinzu, der durchsucht werden soll
        function add_path($path)
        {


                $path = implode('/',
                                $this->reduce_array(  //normiert den pfadname f�r bessere Vergleichbarkeit
                                explode('/',$path)
                                ));

                //validiert das Verzeichnis auf g�ltigkeit und Redundanz
                if(is_dir($this->start_path . $path)){

                        $this->paste_check($this->start_path . $path,true);
                }else
                        echo "<p><b>" . $this->start_path . $path . "</b> is not an correct directory!<p>";
        }

  // +-----------------------------------------------------------------------
  // | function prohib_path
  // +-----------------------------------------------------------------------

        //f�gt einen Pfad hinzu, der nicht durchsucht werden soll
        function prohib_path($path)
        {

                if(!is_dir($this->start_path . $path))echo "<p><b>" . $this->start_path . $path . "</b> is not an correct directory!<p>";
                if(is_array($this->prohib))
                {
                        $this->prohib[ count($this->prohib) ] = $this->start_path . $path;

                }else
                        $this->prohib[0]= $this->start_path . $path;
        }

  // +-----------------------------------------------------------------------
  // | function add_tag
  // +-----------------------------------------------------------------------

        //F�gt der Suchwortliste ein Suchwort hinzu
        function add_tag($tag)
        {
                if(is_array($this->tag))
                        $this->tag[count($this->tag)]=$tag;
                else
                        $this->tag[0]=$tag;
        }

  // +-----------------------------------------------------------------------
  // | function add_fix
  // +-----------------------------------------------------------------------
        //f�gt suchkriterien, wie "*.*,*.php,index.php,index.*" der liste hinzu
        function add_fix($myfix)
        {
		
                if(is_array($this->fix))
		{
                        $this->fix[count($this->fix)]=$myfix;
                }
		else
		{
                        $this->fix[0]=$myfix;
		}
        }

  // +-----------------------------------------------------------------------
  // | function switch_cross_seek
  // +-----------------------------------------------------------------------
        //spezialfunktion: nutzt links in php-dateien um die verlinkten Dateien zu durchsuchen
        function switch_cross_seek( array $array )
        {
                $this->cross_seek[count($this->cross_seek)] = $array;
        }

  // +-----------------------------------------------------------------------
  // | function result
  // +-----------------------------------------------------------------------
        //liefert ein ergebniss, welches noch erweitert werden kann!
        function result()
        { 
                return $this->fin_list;
        }
  // +-----------------------------------------------------------------------
  // | function flash
  // +-----------------------------------------------------------------------
        //l�scht das bisherige ergebniss
        function flash()
        {
                $this->fin_list = null;
                $this->tag = array();
                $this->fix = array();
        }

  // +-----------------------------------------------------------------------
  // | function relative_path
  // +-----------------------------------------------------------------------
        //ber�cksichtigt alle Angeben unter relativem oder absolutem Bezug
        function relative_path($bool)
        {
                if(!$bool)
                {

                        $this->start_path = str_repeat(        //gibt einen Verzeichnissr�cklauf an
                                                '../',   //r�cklauf um ein verzeichniss
                                                -1 +     //basisverzeichniss ist menge minus 1
                                                count(   //menge der verzeichnisse z�hlen
                                                explode( //verzeichnisse aufsplitten
                                                ((false === strpos(getcwd(),chr(92)))? chr(47):chr(92)) //testet typ nach / u. \
                                                ,getcwd()   //
                                                )
                                                )
                                                )
                                                ;

                }
                else
                {
                        $this->start_path = '';
                }
        }

	

  // +-----------------------------------------------------------------------
  // | function insert_str
  // +-----------------------------------------------------------------------
        //speichert einen text
	
	public function insert_str($str,$path)
	{
		$this->document = $str;
		$this->path_parameter = $path;
	}
	
	
  // +-----------------------------------------------------------------------
  // | function seeking
  // +-----------------------------------------------------------------------
        //f�rt die Suche mit den eingestellten Parametern durch
        function seeking()
        {


		if(is_Null($this->document))
		{
			$max = count($this->path);
			for($i=0;$i<$max;$i++)
			{
				$this->directory_listing($this->path[$i]);
			}


			//besorgt sich eine auflistung aller dateien in den angegebenen Verzeichnissen
			$this->table = $this->file_listing();

			for($i=0;$i<count($this->table);$i++)
			{
				//durchsucht jede angegebene Datei nach tags
				$this->seek_in_file($this->table[$i]);
			}

		}
		else
		{
			$this->table[0] = null;
			
			for($i=0;$i<count($this->table);$i++)
			{
		       
				//durchsucht jede angegebene Datei nach tags
				$this->seek_in_file($this->table[$i]);
			}
		}
        }

//+---------------------------------------------------------------------------
//| Save and Loadfunktion
//+---------------------------------------------------------------------------

function loading_size($value)
{
	ini_set('memory_limit', "$valueM");
}



//+----------------------------------------------------------------------------
//| Systemintern algorithm
//+----------------------------------------------------------------------------

  // +-----------------------------------------------------------------------
  // | function file_listing
  // +-----------------------------------------------------------------------
        //listet alle zu durchsuchenden Dateien auf
        function file_listing()
        {       //doppelte iteration
        	
                $table_array;
                for($i=0;$i<count($this->path);$i++)
                {
			
                        for($j=0;$j<count($this->fix);$j++)
                        {
				
                                //listet alle dateien auf
                                foreach (glob( $this->start_path . $this->path[$i] . $this->fix[$j] ) as $filename)
                                {
                                           //weiche f�r arraybildung
                                           if(is_array($table_array))

                                                $table_array[count($table_array)] = $filename;

                                           else
                                           
                                                $table_array[0] = $filename;

                                }

                        }
                }
                return $table_array;
        }

  // +-----------------------------------------------------------------------
  // | function directory_listing
  // +-----------------------------------------------------------------------
        //listet alle zu durchsuchenden Verzeichnisse auf
        function directory_listing($path)
        {
     
        // '' wird nicht gefunden
        if('' == $path)$path = './';

        $tmp = true;

        if(is_array($this->prohib))$tmp = in_array($path,$this->prohib);

        $obj = dir($path);

                while (false !== ($file = $obj->read())) {
                	

                        if ($file != "." && $file != ".." && is_dir( $path . $file)) {
				
                                if($tmp)$this->add_path($path . $file . '/');
                                $this->directory_listing($path . $file . '/');
                        }

                }

        $obj->close();




        }




  // +-----------------------------------------------------------------------
  // | function reduce_array
  // +-----------------------------------------------------------------------
        //tricky, rekursives Reduzieren von �berfl�ssigen Verzeichniselementen
        // zb.  ../v1/v2/../index.php zu  ../v1/index.php
        function reduce_array($folder_array,$pos=null)
        {
        if(is_null($pos))$pos=(count($folder_array)-1);
        $switch = 1;
        $res = null;

                //stoppt die rekursion
                if($pos < 0)return $res;

                //weiche f�r vor oder r�cklauf
                if($folder_array[$pos] == '..')
                {
                        for($i=($pos-1);$i> -1 ; $i--)
                        {
                                $switch = $switch + (($folder_array[$i]== '..')? 1 : -1);
                                if($switch==0)break;
                        }
                }else{
                       for($i=($pos + 1);$i< count($folder_array) ; $i++)
                        {
                                $switch = $switch + (($folder_array[$i]<> '..')? 1 : -1);
                                if($switch==0)break;
                        }
                }
                
                //wenn switch die 0 erreicht hat, wird das Element nicht mit ausgegeben
        $res = $this->reduce_array($folder_array,$pos-1);
                if($switch<>0)
                {
                        if(is_array($res))
                                $res[count($res)] = $folder_array[$pos];
                        else
                                $res[0] = $folder_array[$pos];
                }
        return $res;
        }


  // +-----------------------------------------------------------------------
  // | function seek_in_file
  // +-----------------------------------------------------------------------
        //Sucht inhalte in der Datei
        function seek_in_file($file_uri)
        {
		
                if(is_null($file_uri))
		{
			$file_content = explode("\n",$this->document);
			$file_uri = $this->path_parameter;
			
		}
		else
		{

			if(false === ($file_content = file($file_uri)))echo '<b>' . $file_uri . ' is not a valid URL! Current path is "' . getcwd ( ) . "\"</b><br>\n";
			
                }
		
                //trivial case, no tag exists
		if(count($this->tag) == 0)
		{
			//echo $file_uri . ' -- ';
			$this->save_entry('no tag parameter',0,$file_uri);
		}

                        $idx0=0;

                        for($r=0;$r<count($file_content);$r++)
                        {
                        	
                                $this->add_cross_seek($file_content[$r],$file_uri);
				
				//tauscht R_quire mit I_nclude aus. Die Klasse soll sich nicht  selber finden!
                                $lower = $file_content[$r];

				

                                do{
                                        for($h=0;$h<count($this->tag);$h++)
                                        {

					//echo $this->tag[$h] . "\n";
                                       //f�gt Linktags der suchliste �ber parse_check hinzu

                                                if(!(false===($pos1 = stripos($lower,$this->tag[$h],$idx0))))
                                                {


                                                        if(($idx0 = $pos1 + strlen($this->tag[$h]))>strlen($lower))
                                                                $idx0 = strlen($lower) - 1;



                                                        $this->save_entry(substr($lower,$pos1),$r,$file_uri);
                                                break;


                                                }
                                        }

                                 $con++;
                                }while(!($pos1===false));   //wiederholt den vorgang f�r Konstrukte wie : i_nclude("xxx");i_nclude("yyy");
                                $idx0=0;
                                $pos1=0;
                                
                                //k�nnte ausgelagert werden, um sie zum Vererben zu �berschreiben
                                /*for($h=0;$h<count($this->tag);$h++) //alle tags werden auf die Zeile ausgef�hrt (iterativ)
                                {

                                        if(!(false===($posx = strpos($lower,strtolower($this->tag[$h]) ))))
                                        {
                                                     save_entry(substr($lower,$posx),$r,$file_uri);
                                        }
                                        
                                } */
                        }

        }

	private function add_cross_seek($string,$path)
	{
		
	if(count($this->cross_seek) == 0)return false;
	foreach( $this->cross_seek as $array)
		{
			if(!(false === ($tmp = strpos(strtolower($string),strtolower($array[0])))))
				{
					
					$tmp += strlen($array[0]);
					if(!(false === ($tmp2 = strpos(strtolower($string),strtolower($array[1]),$tmp))))
					{
						$tmp2 -= $tmp;
						//echo $path ." was ist das? ";
						
						/** TODO korrekte Validierung erforderlich */
						if(false === ($add_path = strrpos($path,'/')))
						{
							$add_path = '/';
						}
						else
						{
							$add_path = substr($path,0,$add_path + 1);
						}
						if(false === strrpos($string,'/'))
							$this->table[count($this->table)] = $add_path . substr($string,$tmp,$tmp2);
						else
							$this->table[count($this->table)] = substr($string,$tmp,$tmp2);
					}
					
				}
		}
	}
	
  // +-----------------------------------------------------------------------
  // | function save_entry
  // +-----------------------------------------------------------------------
        //auslagerung der Wertespeicherung
        function save_entry($tag,$pos,$file)
        {
                if(is_array($this->fin_list))
                                                             {
                                                                        $el_pos = count($this->fin_list);
                                                                        $this->fin_list[$el_pos]['tag']= trim($tag);
                                                                        $this->fin_list[$el_pos]['pos']= $pos;
                                                                        $this->fin_list[$el_pos]['file']= $file;
                                                             }
                                                                else
                                                                {
                                                                        $this->fin_list[0]['tag']=$tag;//substr($lower,$posx);
                                                                        $this->fin_list[0]['pos']= $pos;
                                                                        $this->fin_list[0]['file']= $file;
                                                                 }
        }

  // +-----------------------------------------------------------------------
  // | function paste_check
  // +-----------------------------------------------------------------------
        //Sucht redundante inhalte in in den prohiblisten und den path-listen, funktion auch f�r Dateien verf�gbar
        function paste_check($file_name_uri,$bool_dir=false)
        {

             if($bool_dir)
             {
                //erlaubt hinzuf�gen des Verzeichnisses
                $write_enable = true;
                //pr�ft auf redundanzen in path
                if(is_array($this->path))$write_enable = !in_array($file_name_uri,$this->path);
                //pr�ft auf bad-list
                if(is_array($this->prohib) && $write_enable)$write_enable = !in_array($file_name_uri,$this->prohib);
                if($write_enable)
                {
			
                  if(is_array($this->path))
                        {
                                $this->path[ count($this->path) ] = $file_name_uri;

                        }else
                                $this->path[0]= $file_name_uri;
                }
             }
             else
             { //f�r Dateien

                if(!in_array($file_name_uri,$this->table) &&
                        is_file($file_name_uri) &&
                        !in_array($file_name_uri,$this->prohib)
                        )
                {
                  $table_array[count($table_array)] = $file_name_uri;
                }
             }
        }

  // +-----------------------------------------------------------------------
  // | function split_uri
  // +-----------------------------------------------------------------------
        //spaltet eine Uri in einen Array mit dateiname und Pfad
        function split_uri($file_name_uri)
        {
                $tmp = explode('/',$file_name_uri);
                $res['data'] = $tmp[(count($tmp)-1)];
                $tmp[(count($tmp)-1)] = '';
                $res['path'] = implode('/',$tmp);
                return $res;
        }
}//end of class


class FileHandle
{

	var $fs = null;
	var $url;
function open_URL($URL,$modus = 'r')
{
	//if(!is_file  ( $URL  ) )echo "file not exists: " . $URL;
	
	
	$this->fs = &fopen($URL,$modus);
	


	//if(!$this->fs) echo 'Path ' . $URL . ' not exist!';
}

function read_File($byte = 4096)
{
	return fread($this->fs,$byte);
}

function eof()
{

	if(!$this->fs) echo 'no filepointer exists.';
	return feof($this->fs);
}

function get_line()
{
	return fgets  ( $this->fs  );
}

function toPos($pos)
{
	return 0 == fseek ( $this->fs, $pos );
}

function close_File()
{
	fclose($this->fs);
	unset($this->fs);
}
function load_File(){

        
if(!$this->fs)
{
	echo 'no filepointer exists.';
	return false;
}

        while(!feof($this->fs)) {
                $content .= fread($this->fs,4096);
        }
	fclose($this->fs);

	return $content;

	}
	
	

	
function write_file($content){

        //$fs = fopen($pos,'w+');


                $bool = fwrite($this->fs,$content);


}
}

?>
