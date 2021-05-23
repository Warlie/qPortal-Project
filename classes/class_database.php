<?PHP

/*
 * li
 * 
 * */
function single_array($array,$assoz){
                $res;
                $len = count($array);
                for($i=0;$i<$len;$i){$res[$i] = $array[$i][$assoz];}
                return $res;
                }
//gibt den tabellennamen aus vollqualifitiertem spaltennamen aus
function isolate_tbl_nm($value){return substr($value,0,strpos($value,'.'));}

//test, ob ein ein Eintrag in einem Array vorhanden ist, dabei wird ber�cksichtig, ob ein Tabellenname vorhanden ist
function sql_in_array($array,$value,$table){

                
        
                $len = count($array);

                $full_qualified = (false == ($pos = strpos($value,'.')))? false : true ;

                $single = ($full_qualified)? substr($value,$pos+1) : $value ; 

                $full = ($full_qualified)? $value : $table . '.' . $value;




                for($i=0;$i<$len;$i++){

                //echo $array[$i] . ' ! ' . $full . ' :' . strcasecmp($single,$array[$i]) .'<p>';
                        if(false === strpos($array[$i],'.')){
                        
                        //echo $array[$i] . ' ! ' . $single . ' :' . strcasecmp($single,$array[$i]) .'<p>';
                        if(0==strcasecmp($single,$array[$i]))return true;
                                
                        }else{


                        //echo $array[$i] . ' ! ' . $full . ' :' . strcasecmp($full,$array[$i])  . '<p>';
                        if(0==strcasecmp($full,$array[$i]))return true;

                        }                
                
                }

        return false;

        }

/**
*
* constuctor : database(<Server = "">, <User = "">, <pwt = "">) : creates db-object, accountdata are optional
* set_db_encode(<codeset>) : Selects codeset to handle with encodings.
* SQL(<SQLString>) : Immidate SQL-Statement.
* create_table(<table>,<fieldname>,<format>) : creates table table as name, fieldname as arraylist of fieldnames and format to join types to these fields
* get_rst(<sql>) : Creates an recordset of class rst
* insert_rst(<rst>) : writes records back to db.
* sResult($num,$fieldname)
*/
	
	
class database {

//db-conntection
var $User = "root";//root
var $pwt = "toor";
var $Server = "localhost"; //localhost
var $db_name = "qportal";
//encoding
var $mycodeset = "UTF-8";
var $open_db;
var $table_db;

var $error_no=0;

var $timestamp;

/** database :constructor
* @param $Server : serveroverride
* @param $User : override
* @param $pwt : override
*
*/

function __construct($Server = "", $User = "", $pwt = ""){
        
	if($Server <> "")$this->Server = $Server;
	if($User <> "")$this->User = $User;
	if($pwt <> "")$this->pwt = $pwt;

        $this->open_db = new mysqli($this->Server, $this->User, $this->pwt);
	$this->error_no = $this->open_db->errno;
	
	if ($this->open_db->connect_error) {
    die('Connect Error (' . $this->open_db->connect_errno . ') '
            . $this->open_db->connect_error);
    	}
	
        if(!$this->open_db->select_db ($this->db_name)){
                echo "datenbank fehlt<p>";
		$this->error_no = mysqli_errno();
                if (!mysqli_query('create database ' . $this->db_name . ';')) 
                //echo "datenbank kann nicht genutzt werden " . mysqli_error() . " " . mysqli_errno();
		$this->error_no = mysqli_errno();
		
		
                }
                

           //mysqli_query($db, "SET CHARACTER SET 'utf8'");


		
        }

	/** errno
	* @return current error number
	*
	*
	*/
	function errno()
	{
		
		return $this->error_no;
	}
	
	/** set_db_encode
	* @param $codeset : changes encoding
	*
	*/
	function set_db_encode($codeset)
		{
			$this->open_db->set_charset ( $codeset );
			
		}
		
	/** get_db_encode
	* @return $codeset 
	*
	*/
	function get_db_encode()
		{
			return$this->open_db->character_set_name ( );

			
		}		

	/** SQL
	* @param $SQLString : SQL
	* Immidiate SQL command
	*/
	function SQL($SQLString){

                        if(is_object($this->table_db)) $this->table_db->free_result();
                        $this->table_db = mysqli_query($this->open_db, $SQLString);
                        if(mysqli_errno($this->open_db)<>0)echo "Fehler ist aufgetreten \n<br>" . '(' . $SQLString . ")\n<br>" . mysqli_error($this->open_db);                        
                                                
                                                }


	public function loadfile($filename)
	{$this->injectSQL(implode('', file ('surface.sql')));}
	
	public function injectSQL($SQLString)
	{
		
		//echo $SQLString;
		
		$teile = explode(";
", $SQLString);
		for($i = 0; count($teile)>$i;$i++)
		{

		if(strlen(trim($teile[$i]))>2)$this->SQL($teile[$i] . ';');
		}

	}

	/** create_table
	* @param $table : String for tablename
	* @param $fieldname : Array of Fieldnames
	* @param $format : Array of fieldtypes
	*
	*/
	function create_table($table,$fieldname,$format){

                $sqlQuery = "create table $table \n( ";
                
                $komma = "";
                
                        for ($i=0;$i<count($fieldname);$i++){
                                $sqlQuery .= $komma . $fieldname[$i] . " " . $format[$i];
                                $komma = " , ";
                                }
                        $sqlQuery .= " );";
                
                $this->table_db = mysql_db_query($this->db_name,$sqlQuery);        
                if(mysqli_errno()<>0)echo "<p>Fehler ist aufgetreten: " . mysql_error() . "<p>bei SQL_String:" . $sqlQuery;
                }



	/** get_rst
	* @param sql : SQL String
	* @return rst Object with selected data
	* creates a rst Object to access db data
	*/
function get_rst($sql){
global $logger_class;
	/* Embedded function extract_table
	* @param sql : SQL String
	* @param low_sql : SQL in lower case
	* @param pos : 
	* @param my_array
	* @param pointer
	* @param mysec
	*/

	$logger_class->setAssert("compute statement: "  . $sql,0);
			
if(!function_exists('extract_table')){

        //findet alle tabellennamen in einem SQL-String
        function extract_table($sql,$low_sql=null,$pos=0,$my_array=null,$pointer=0,$mysec=0){
		
		// first loop is for FROM and rest for JOIN
                $sign = ($pointer==0)?'from':'join';

		// copy sql in lower case
                if(is_null($low_sql))$low_sql = strtolower($sql);
		//position of next join or from
		//echo $low_sql . "\n";
                //$low_sql = str_replace('left join' , '     join', $low_sql);
		//$low_sql = str_replace('right join' , '      join', $low_sql);

		//echo $low_sql . "\n";
		$pos = strpos($low_sql,$sign, $pos)+4;
		
                $tmp = $pos;
		
				
		//geht zur letzten offenen klammer
                do{                
			//rotate
			$pos = $tmp;
			//finds last opening bracket
			$tmp = strpos($sql,'(',$tmp + 1);
                }while(!($tmp===false || ($pos+4)< $tmp));

		//adds 2 to point the tablename
                $pos += 2;
		//
        if(($end_word = strpos($sql,' ',$pos+1)) == false){
        	if(($end_word = strpos($sql,';',$pos+1)) == false)$end_word = strlen($sql);}
       
                $my_array[$pointer]=substr($sql,$pos-1 ,1 +$end_word-$pos);
		

		
                //$my_array[$pointer]=substr($sql,$pos-1 ,1 +$end_word-$pos);
		
                $pointer++;
 
                if(!(false === strpos($low_sql,' join ',$pos))&& $pos > 0 && $mysec<10)return extract_table($sql,$low_sql,$pos,$my_array,$pointer,++$mysec);
                else return $my_array;}

        //extrahiert alle Spaltennnamen in einem SQL-String oder gibt * zurueck
        function extract_col($sql){
                $low_sql = strtolower($sql);
		
		//Distinct abfrage beruecksichtigt
		//echo $low_sql;
		if(false === ($tmp_found = strpos($low_sql,'distinct ')))
		{
			$tmp1 = 7 + strpos($low_sql,'select ');
		}else
		{
			$tmp1 = 9 + $tmp_found;
		}
			
                $tmp2 = strpos($low_sql,'from') - $tmp1;        
        
                $res = substr($sql,$tmp1,$tmp2);
                $res = trim($res);
                if($res == '*')return '*';
        
                $res = explode( ',',$res );
        
                $len = count($res);
		//var_dump($res);
		//erweiterung fuer aliasnamen und functionen
		//alias und funktionsnamen werden mit einem At makiert
                for($i=0;$i<$len;$i++)
		{
			$res[$i] = str_replace('\'','',$res[$i]);
			
			if(false === ($pos = strpos(strtoupper($res[$i]),' AS ')))
			{
				$res[$i]=trim($res[$i]);
			}
			else
			{
				$res[$i] = '@' . trim(substr($res[$i],$pos + 3));
			}
			//echo $res[$i] . "\n";
			
		}
                return $res;}        
                









        //gibt alle dateninhalte in das rst_object ein 
        
        


}

//strpos()





	//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	//                                                 programm
	//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		//$this->timestamp = $this->microtime_float();

                                //erstellt neuen rst()
                                $rst = new rst();
                                                     
                                //uebergibt alle tabellennamen
                                 $rst->table = extract_table($sql); //zeile 125
                                 //$tmp_fields = extract_col($sql);

		 //echo "abfragemessung 0.3 " . ($this->microtime_float() - $this->timestamp) . "\n";	

                                //uebergibt die feldnamen
                                 $rst->field = 
				 /* zeile 254 findet alle eigenschaften der Felder */ 
				 $this->define_rst_fields(
				 $rst,
				 ($cols = /* zeile 151 findet alle felder */ extract_col($sql)),
				 $this->open_db
				 );
				 
				//echo "abfragemessung 0.6 " . ($this->microtime_float() - $this->timestamp) . "\n";
				 
                 $res = /* zeile 445 */ $this->load_field_rst($rst,$sql,($cols == '*')?false:true,$this->mycodeset);
		 
		 return $res;
//$array1[0] = 'mikos2_list_frecency.frecuency';
//$array1[1] = 'mikos2_list_inner_doors.inner_doors';



 //if(sql_in_array($array1,'frecuency','mikos2_list_frecency'))echo '<p>OK</p>';else echo '<p>nicht OK</p>';

//echo 'auch noch kein fehler';

                //$tmp = mysql_result($this->table_db,$num,$fieldname);
                
                  //              echo ''; 
                  //             return $rst; 
                                }

                                
//------------------------------------------------------------------------------------------------------------------------------------------------------
//                                                         Helper functions for get_rst
//------------------------------------------------------------------------------------------------------------------------------------------------------
                                
       /** private function load_field_rst
	* @param rst : Recordset Object
	* @param sql : SQL Statement
	* @param add_pri :  
	* @param encode : encoding for transformation
	*/
private function load_field_rst($rst,$sql,$add_pri,$encode = 'UTF-8'){


          if(is_null($rst->field)){echo '<b>Recordset besitzt noch keine tabellennamen</b>';return false;}
          if(is_null($sql)){echo '<b>Es wurde kein SQL-String uebergeben</b>';return false;}

              $prim = $rst->prim_field();
	      

	      if($add_pri && (false ===(strpos(strtoupper($sql),'DISTINCT'))))
	      {
             	if(!$prim)echo "no primary key definiert \n";
              	$sql = 'SELECT ' . implode(',',$prim) . ',' . substr($sql,7);
	      }
	     //echo $sql;
             //echo '<p>' . implode($prim,',') . '</p>';
                    $fdresult = $this->open_db->query($sql); 
                             if($this->open_db->errno <>0)echo 'Fehler aufgetreten: ' . $this->open_db->errno . '(' . $sql . ")\n";                              
                   //echo 'noch kein fehler<p>';

while ($zeile = $fdresult->fetch_row()) {

	

                reset($rst->field);
		
                
		//for($i = 1 ; $i < count($zeile);$i++)
		while (!is_null($key = key($zeile) )  )
		{
                                         
		 $field_list = $fdresult->fetch_fields(); //$key

                 
		 //echo "$key \n";
	if($field_list[$key]->table)
		 $new_key = $field_list[$key]->table . '.' . $field_list[$key]->name;
         else
         	$new_key = $field_list[$key]->name;
         	
         	$value = $zeile[$key];
		next($zeile);
		 //echo '<p>' . $new_key . ' ' . $value . "</p>\n";
                 
		 /*
                  if($encode == 'UTF-8')
		 $value = utf8_decode($value);
		 */
		 
                                $rst->setValue($new_key,$value,null,true);

                 next($rst->field);}
                                
                  $rst->update();
                                

                                }
                                //$rst->update();



return $rst;
}

      /** private function collect_mysql_col
	* @param table: String with a tablename
	* @param filter_in : 
	* @param array_list :  
	* @param db_obj : encoding for transformation
	*
	* Compares all fields in table to the fields in sql-statement and sorts them
	*
	* @todo has bugs
	*/


        //gleicht auf wunsch felder der datenbank mit den des SQL-befehls zu und sortiert sie !
        private function collect_mysql_col($table,$filter_in=null,$array_list=null, $db_obj){

		// * benoetigt alle tabellen -> filter wird abgestellt
		if($filter_in == '*')$filter_in=null;


                                   $fdresult = $this->open_db->query("SHOW COLUMNS FROM $table;");
                                   //printf("Select returned %d rows.\n", $fdresult->num_rows);
                           if($this->open_db->errno <>0)echo "<p>Fehler ist aufgetreten: " . mysqli_error() . "<p>bei SQL_String: SHOW COLUMNS FROM $table;";
                                 //filterfunction
                                 $filter = (is_null($filter_in))? false : true ;


                                //setzt bestehendes datenfeld vorne an
                                if(!is_null($array_list))$res = $array_list;




                        //if($filter)echo 'filter an';else echo 'filter aus';


                // Die Datensaetze werden einzeln gelesen
                   while($zeile = $fdresult->fetch_assoc())  //
                           {
 //echo $filter_in;
 
				//echo $table . '.' . $zeile['Field'] . ' ' .  $zeile['Type'] . ' ' . $zeile['Key'] . "\n";
                                //fragt ab, ob dieses Feld auch gef                        
                                if($filter)$filter_res = (sql_in_array($filter_in,$zeile['Field'],$table)|| $zeile['Key']=='PRI');
                                        else $filter_res= true;
                                                                
                                if($filter_res){        
                                        //echo $table . '.' . $zeile['Field'] . ' ' .  $zeile['Type'] . ' ' . $zeile['Key'] . "\n";
                                                $res[$table . '.' . $zeile['Field']]['single_name'] = $zeile['Field'];
                                                $res[$table . '.' . $zeile['Field']]['Table_name'] = $table;
                                                $res[$table . '.' . $zeile['Field']]['Type']=$zeile['Type'];
                                                $res[$table . '.' . $zeile['Field']]['Null']=$zeile['Null'];
                                                $res[$table . '.' . $zeile['Field']]['Key']=$zeile['Key'];
                                                $res[$table . '.' . $zeile['Field']]['Default']=$zeile['Default'];
                                                $res[$table . '.' . $zeile['Field']]['Extra']=$zeile['Extra']; 
                                        }    
                           }
                   
			   //Alias fuer join noch nicht gesichert
		if(!is_Null($filter_in))
			   foreach($filter_in as $line )
		            {
 
				  
                                                                                   
                                if(!(false === strpos($line,'@'))){
					
						$line = substr($line,1);
						//$table . '.' . 
                                        //echo $zeile['Field'] . ' ' .  $zeile['Type'] . ' ' . $zeile['Key'] . "\n";
                                                $res[$line]['single_name'] = $line;
                                                $res[$line]['Table_name'] = $table;
                                                $res[$line]['Type']='generic';
                                                $res[$line]['Null']='';
                                                $res[$line]['Key']='';
                                                $res[$line]['Default']='';
                                                $res[$line]['Extra']='generic'; 
                                        }    
                           }			   
			
                  return $res;
 
                }

        /** private function collect_mysql_col
	* @param rst: recordset
	* @param fields_to_add : array 
	* @param db : database object  
	*
	*/
                        //gesicherte Hilfsfuntion. vereinigt die resultate von collect_mysql_col()
        function define_rst_fields($rst,$fields_to_add, $db){
                if(is_null($rst))return null;
                if(is_null($rst->table))return null;

                foreach ($rst->table as $value){$res = $this->collect_mysql_col( $value,$fields_to_add,$res, $db);}

                        return $res;

                }
                
//--------------------------------------
                                
//---------------------------------------------------------------------------------------------------------------
	function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((double)$usec + (double)$sec);
	}

	
	//---------------------------------------------------------------------------------------------------------------

         private function update_data($rst,$pos){
         	 global $logger_class;
         	 $logger_class->setAssert("call update_data ",0);

                         $len = count($rst->edit);
                         $prim = $rst->prim_field($rst->table[$pos]);

			 
//echo "booho";
                    //menge der aufgaben
                     for($k=0;$k < $len;$k++){

//echo $rst->edit[$k]['table']  . '<br>';
                  //�berpr�ft, ob diese Anfrage f�r die aktuelle Tabelle zutrifft
                  if($rst->edit[$k]['table'] == $rst->table[$pos] && $rst->edit[$k]['pos'] < $rst->rst_num()){



			  if($rst->edit[$k]['value'] <> '')
			  {

			  $sign1 = database::sql_type_sign($rst->type($rst->edit[$k]['field']));
			  $sign2 = database::sql_type_sign($rst->type($prim[$pos]));
//str_replace(array('\'',chr(12),chr(13)),array('\'\'','\\r','\\n'),)
//var_dump($this->open_db);
//echo  $this->open_db->real_escape_string($rst->edit[$k]['value']);
                $sql_string = 'update ' . $rst->table[$pos];
                $sql_string .= ' set ' . $rst->edit[$k]['field'] . ' = ' . $sign1 . $this->open_db->real_escape_string($rst->edit[$k]['value']) . $sign1;
                $sql_string .= ' where ' . $prim[0] . ' = ' . $sign2 . $rst->value($rst->edit[$k]['pos'],$prim[0]) . $sign2 . ';';

		
               //speichert einen Datensatz, der neu geschieben wurde
               //echo $sql_string . '
	       //';        
	       $logger_class->setAssert("updates exist data: "  . $sql_string,0);
	       $fdresult = $this->open_db->query($sql_string);
                       if($this->open_db->errno<>0)echo 'Fehler aufgetreten: ' . '(' . $sql_string . ')' . $this->open_db->error;
			  }
                                  }
                                  }
                 }

function insert_rst($rst){
        global $logger_class;

	$logger_class->setAssert("insert_rst: \n" . $rst->get_log() ,0);
	

	//tabellenname �bernommen
        $table = $rst->table;
	
	//alle feldnamen
        //$fields = $rst->db_field_list();
        
	//keine schleife, 
       // $dumdi = $rst->prim_field($table[$i]);
       // $autoinc = (''==$rst->type($dumdi[$i],'Extra'));

        //echo $rst->type($dumdi[$i],'Extra');

//jede tabelle wird einzeln abgefragt
for($i=0;$i<count($table);$i++)
   {
	   
	   
	   //ein prim pro durchlauf
    $prim = $rst->prim_field($table[$i]);
    //var_dump($prim);
   // $type = $rst->type($prim, 'Extra');
   
   //
   $autoinc = (''==$rst->type( $prim[$i] ,'Extra'));

        $sql_string = 'SELECT ' .  implode(', ', $this->filter_table( $rst,$table[$i],true));
        $sql_string .= ' FROM ' . $table[$i];
         $sql_string .= ' ORDER BY ' . $prim[$i] . ';';

             //Vorsicht, hierdurch wird der Primarykey ausgeblendet

             
	
                                           //echo "<br>erlaubt=$autoinc-<br>";
             
					   //for($te=0;$te<count($fields);$te++)echo '<p>' . $fields[$te] . '</p>';

					   //echo $sql_string;
	$fdresult = mysqli_query($this->open_db, $sql_string);

	if(mysqli_errno($this->open_db)<>0)echo 'Fehler aufgetreten (' . $sql_string . '): ' . mysqli_error($this->open_db);

       //Abfrage �ber alle elemente einer tabelle erstellt

       		//ermittelt die anzahl der zeilen
             if(!is_null($prim))
	     { 
		     $loop = mysqli_num_rows($fdresult);
//echo '<h1>' . $loop . '</h1>';
		     //array mit allen primwerten zum abgleichen
		     $j = 0;
		     $keys = explode( '.', $prim[$i] );
		     $pos = count($keys) - 1;
		     $big_list = array();
		     			while($zeile = $fdresult->fetch_assoc())$big_list[$j++] = $zeile[$keys[$pos]];

		     			/*
                                       for($j=0;$j<$loop;$j++)
				       {
					       //nimmt alle prims aus der aktuellen db
					       $big_list[$j] = mysqli_result($fdresult, $j, $prim[$i]);

                                       }*/
                                      //} //end if
                                       //for($te=0;$te<count($big_list);$te++)echo '<p>' . $big_list[$te] . '</p>';
                                               //durchsicht in der datenbank

                                        //x zeichnet auch auf, wieviele elemente gespeichert wurde
                                        $x=0;
                                        //                       echo 'hier1' . $rst->rst_num();
                                        $fields = $this->filter_table( $rst,$table[$i],false);

					//echo implode((filter_table( $rst,$table[$i],false)),', ');
					//echo '<h2>-' . $rst->value(414,'gewerbeimmobilien.gml_layer') . '-</h2>';
					//var_dump($rst->rst_num());
					for($j=0;$j<($rst->rst_num());$j++)
					{
							
                                          //echo '-<b>' . $prim[$i] . '</b>-<br>';
                                          //var_dump($fields);
							if(count($fields)>0)
						       {  //triviale abfrage
							       
							      //echo '-<b>"' . $rst->value($j,$prim[$i]) . '" on pos(' . $j .  ')</b>-<br>';
							      //var_dump($big_list);
							       //testet ob es diesen schl�ssel bereits gibt oder ob er leer ist
							       if(!in_array($rst->value($j,$prim[$i]),$big_list) || is_null($rst->value($j,$prim[$i])) || $rst->value($j,$prim[$i]) == '' )
							       {
								       //echo '----------------------------------------------------------------';
								       $map[$x++]=$j;
							       }//end if


                                                       }
						       else
						       {
							       $map[$x++]=$j;
						       } //end if
                                         

                                       }//end loop

				       //alle positionen  f�r neue prims gefunden
				       //-bei autoincrement sind sie leer
				       
				       //menge aller  existierender felder
                                       $len = count($fields);

                                       
                                       //erstellt SQLs f�r die Datens�tze, die keinen oder einen noch nicht existierenden WErt habe
                                       for($j=0;$j<$x;$j++)
				       {//loop1
					       
					       //echo count($fields) . " " . implode($fields,', ') . '<br>';
					       //SQL-Statement
					       $sql_string = 'insert ' . $table[$i] . ' ( ' . implode(', ', $fields) . ') values (';

					       $komma = '';
                                      //
                                      		for($l=0;$l<count($fields);$l++)
						{
							
							$tmp = $this->sql_type_sign($rst->type($fields[$l]));

							
								$string = $this->open_db->real_escape_string($rst->value($map[$j],$fields[$l]));
							
								if( $tmp == '' && (('' == $string) || is_null( $string )  )) $string = 'NULL';

							if($tmp <> '\'')
							$sql_string .= ' ' . $komma . str_replace(',','.',$string)  . ' ';
							else
							$sql_string .= ' ' . $komma . $tmp . $string . $tmp . ' ';
							
							//echo $l . ': '. $komma . $tmp . $string . $tmp . ' <br>';
                                                 	$komma = ', ';
						}
							$sql_string .= ');';
							//$rst->show_content();

							//
							//echo $sql_string . "<br>\n";
			   				//speichert einen Datensatz, der neu geschieben wurde
			   				
			   				$logger_class->setAssert("saves new data: "  . $sql_string,0);
                           				$fdresult = $this->open_db->query($sql_string);
							if($this->open_db->errno<>0)
							{
								//var_dump($this->open_db);
								echo '<br>Fehler aufgetreten (' . $sql_string . '): ' . $this->open_db->error . '</br>';
								//debug_print_backtrace (0,5);
							}
				       	}//end loop1

         //schleife f�r aktualisieren ge�nderter daten
         $this->update_data($rst,$i);



         
	     }


 


                
//update fp
//   set preis = 300
//   where hersteller = 'IBM';                
//-----------------------------------------------------------------------------------------------------------------------------------------------------
//schleife f�r aktualisieren ge�nderter daten
/*
        $len = count($rst->edit);

        for($i=0;$i < $len;$i++){

                $sign1 = $this->sql_type_sign($rst->type($rst->edit[$i]['field']));
                $sign2 = $this->sql_type_sign($rst->type($rst->prim_field($table[$i])));

                $sql_string = "update " . $table[$i] . " ";
                $sql_string .= 'set ' . $rst->edit[$i]['field'] . ' = ' . $sign1 . $rst->value($rst->edit[$i]['pos'],$rst->edit[$i]['field']) . $sign1;
                $sql_string .= ' where ' . $rst->prim_field($table[$i]) . ' = ' . $sign2 . $rst->value($rst->edit[$i]['pos'],$rst->prim_field()) . $sign2 . ';';

               echo 'hier2';
	       echo $sql_string;

                           $fdresult = mysqli_query($sql_string);
                            if(mysqli_errno()<>0)echo 'Fehler aufgetreten: ' . mysqli_error();

                }
		*/
}
}



         //------------------------------------------------------------------------------------------------------
         //filtert alle spaltennamen heraus, die nichts mit der Tabelle zu tun haben
private  function filter_table($rst,$tablename,$boolPrimary=true){

         $loop = 0;
         $res = [];

	     reset($rst->field);
             while ($key = key($rst->field)  ) {
		

             $except = ($rst->field[$key]['Extra']<>'auto_increment') || $boolPrimary;
            // echo '!!!!!!!!!!!!!!!!!!!!!!!!' . $except . "?$boolPrimary??????????????????????????";
	     
	     			
	     
             if(($rst->field[$key]['Table_name']==$tablename) && $except){$res[$loop++] = $key;}
             next($rst->field);
                               }
                               

             return $res;
             }
             

         //------------------------------------------------------------------------------------------------------
         //





                 
                 
         //function

//--------------------------------------------------------------------------------------------------
//speichert neue Daten
private function newdata($posarray,$rst){

	}





function sql_type_sign($string){
                                //echo '<h1>' . $string . '</h1>';
				switch(substr($string,0,5)){
                                
                                case 'int':
                                         $div = "";                        
                                break;
                                case 'text':
				case 'year':
				case 'datet':
				case 'date':
				case 'time':
				case 'times':
				case 'longt':
				case 'longb':
                                case 'char(': 
				case 'varch':
                                        $div = "'";        
                                break;
                                
                                default :
                                         $div = "";                                                
                                }
        return $div;

        }

function sResult($num,$fieldname){
	
	$col = explode('.', $fieldname);

	if(!is_null($this->table_db))
        {
        	if( 
        		$this->table_db->data_seek($num)
        	)
        	{
        	$zeile = $this->table_db->fetch_assoc();
        	
        		//if(is_null($zeile[$col[1]]))echo $col[1] . " is not a valid key \n"; 
        		// needs entry in log
        		return $zeile[$col[1]];
        	}
        	

        }
        return null;}

function sEffectNum()
{
return mysqli_num_rows($this->table_db);
}

function __toString(){return 'Class:database';}

}

/**
* first_ds() : goes to first dataset
* last_ds() : goes to last dataset
* next_ds() : goes to next dataset
* prev_ds() : goes to previus dataset
* EOF() : End of datasets
* type(<field>,<attrib = type>) : shows attributes
* db_field_list() : shows an array of all fieldnames
* rst_num() : many of all datasets
* rst_cur_num() : position of current dataset
* prim_field( <tablename = null>) : shows the primarykey of the spezific table or shows a array of all primarykeys
* setValue(<Field>,<Value>,<num=null>) : changes the spezific value in field. num selects position.
* value(<field>) : shows value of field
* value(<num>,<field>) : shows value of field on spezific position
* setField(<Field>,<Type>,<Null>,<Key>,<Default>,<Extra>) : add new Fields, is not supported to alter tables, yet.
* getCSV() : gives out a CSV-formated string
* @version 2012-04-02
*/
class rst{
var $tbl;
var $name;
var $field;
var $value;
var $edit=array();
var $tmp = null;
var $var_cur_pos=0;
var $pos_in_array=0;

function find($column, $value)
{
	//Ignores edit
	//var_dump(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1));
	//echo "hier";
	//var_dump($value, $this->value, $column);
	if(is_null($this->value))return false;
	return array_search($value, array_column($this->value, $column)) ;
}

function next_ds(){$this->var_cur_pos++;}

function update(){
    //echo 'update ' . $this->pos_in_array;
       global $logger_class;

	$logger_class->setAssert("update \n",0);
    	if(is_null($this->tmp))
	{
		//??
		$this->pos_in_array++; 
		}
		else
		{
			//echo "\n tmp \n";
			//var_dump($this->tmp);
			//echo "saves " .  count($this->tmp) . " Objects in edit\n";
		for($i= 0 ; $i < count($this->tmp);$i++)
		{
			
			//updates werden abrufbar gemacht
			$this->value[$this->tmp[$i]['pos']][$this->tmp[$i]['field']]=$this->tmp[$i]['value'];

			//
			if(is_array($this->edit))$len = count($this->edit);else$len = 0;

                $this->edit[$len]['pos']=$this->tmp[$i]['pos']; 
                $this->edit[$len]['field']=$this->tmp[$i]['field']; 
                $this->edit[$len]['value']=$this->tmp[$i]['value'];
                $this->edit[$len]['table']=$this->tmp[$i]['table'];
		
                
		
		}
		
		$this->tmp = null;
	}
}

function prev_ds(){$this->var_cur_pos--;}

function first_ds(){$this->var_cur_pos=0;return !$this->EOF();}

function last_ds(){$this->var_cur_pos=$this->pos_in_array;return $this->pos_in_array > 0;}

function EOF(){return !($this->var_cur_pos<$this->pos_in_array);}

function setValue($Field,&$Value,$num=null,$editable=false){

		if(is_object($Value) )
		$conv_value = &$Value;
		else
		$conv_value = $Value;
//if(!$editable) echo 'Invalue in value:<i>' . $this->pos_in_array . '</i><b>' . $Field . '</b> ' . $Value .  "<p>\n";
			//es existieren keine Felder zum bef�llen
        		if(!is_null($this->field)){
								//var_dump($this->field);
                                                                //testet,ob es ein feld gibt
                                                                if(!is_Null($this->field[$Field]))
								{
                                                                        
                                                                        //entscheidet, ob
                                                                        if(($this->value[$field]['Extra']<>'auto_increment') || $editable)
									{        
                                                                       
                                                                                if(is_null($num))
										{
                                                        // echo 'saves in value:<i>' . $this->pos_in_array . '</i><b>' . $Field . '</b> ' . $Value .  "<p>\n";
							 			$this->value[$this->pos_in_array][$Field]=$conv_value;
										
										
										
                                                                                }
										else
										{
							//echo 'saves in tmp:<i>' . $num . '</i><b>' . $Field . '</b> ' . $Value .  "<p>\n";
											if(is_null($this->tmp))$this->tmp = array();
											
											$pos_in_temp = count($this->tmp);
											
                                                                                        $this->tmp[$pos_in_temp]['pos']=$num;
											$this->tmp[$pos_in_temp]['table'] = isolate_tbl_nm($Field);
                                                                                        $this->tmp[$pos_in_temp]['field']=$Field;
                                                                                        $this->tmp[$pos_in_temp]['value']=$conv_value;
											//var_dump($this->tmp);
											//echo "$pos_in_temp -" . $this->tmp[$pos_in_temp]['field']. ' ' . $this->tmp[$pos_in_temp]['value'] .'-<br>';
                                                                                }

                                                                        }
									else
									{
                                                                                echo '<br><b>PrimaryKey is auto_increment</b>!<p>';
                                                                                
                                                                        }
                                                                }
								else
								{
									echo '<br><i>Feld nicht vorhanden:</i><b>' . $Field . '</b>!<p>';
                                                                }
                                                                
                                              }
					      else
					      {
						      echo '<br><b>noch keine Felder definiert</b>!<p>';
					      }

}

function setField($Field,$Type,$Null,$Key,$Default,$Extra){
        //ask arraylenght

        if(!is_Null($this->field[$Field]['Type'])){
                                 echo 'Daten bereits vorhanden ' . $Field . '<p>';
                                 return false;
                }

                //isoliert table und field
                $element = explode ( '.' ,$Field );
                //var_dump($element);
                //if(count$element[1]){
                //    $element[1]=$element[0];
                //    $element[0]=$this->table[0];
                //    }

                //

                                $this->field[$Field]['single_name'] = trim($element[1]);
                                $this->field[$Field]['Table_name'] = trim($element[0]);
                                $this->field[$Field]['Type']=trim($Type);
                                $this->field[$Field]['Null']=trim($Null);
                                $this->field[$Field]['Key']=trim($Key);
                                $this->field[$Field]['Default']=trim($Default);
                                $this->field[$Field]['Extra']=trim($Extra);                                

                                                                return true;
}

function &value($num,$field=null){

       if(is_null($field)){$field=$num;$num=$this->var_cur_pos;}

       if(is_object($this->value[$num][$field]) )
       return $this->value[$num][$field];
       else
       {
       $res = $this->value[$num][$field];
       return $res;
       }

    }

function type($field,$element='Type'){
//echo "$field,$element<br>";
        return $this->field[$field][$element];}

function db_field_list(){
       
                        $run_num = 0;
                        $res = array();
                        reset($this->field);
                        while ($key = key($this->field) ) {
                        $res[$run_num] = $key;
                        $run_num++;
                        next($this->field);
                        }
                        return $res;
                                }

function rst_num(){return $this->pos_in_array; } 

function rst_cur_num(){return $this->var_cur_pos; }

function prim_field($tablename = null){

         $field;
         $pointer=0;
             //echo '<ul>';
                reset($this->field);
                while (!is_null($key = key($this->field)))
                             {
                      //echo '<li>' . $key . ' : ';
                     if(!is_null($tablename)){

                       if($this->field[$key]['Key']=='PRI' && $this->field[$key]['Table_name']==$tablename){$field[$pointer++] = $key;}

                       //echo 'hallo ' . $this->field[$key]['Table_name'] . ' ' . $tablename . ' </li>';
                 }else{
                                                //echo 'hallo2 </li>';
                       if($this->field[$key]['Key']=='PRI')$field[$pointer++] = $key;
                   }

                              next($this->field);}
                           //echo '</ul>';
           if(is_array($field))return $field; else return false;
   }
   
   function  get_log()
   {
   	   $res = "tmp:" . count($this->edit) . "\n maxpos:" . $this->pos_in_array;
   	   return $res;
   }
   
function getCSV()
{//$this->value[$this->pos_in_array][$Field]
$res = "";
$delim = '';
foreach ($this->field as $myfield) 
{
	
    $res .= $delim . $myfield['single_name'];
    $delim = ';';
}

echo count($this->value);
for($i=0; $i< count($this->value); $i++ )
{
$delim = '';
$res .= "\n";
foreach ($this->field as $k => $v)
{

    $res .= $delim . $this->value[$i][$k];
    $delim = ';';
}

}
//$this->field[$Field]

return $res;

}

public function show_content()
	{
		echo "\ntables:\n";
		foreach($this->field as $key => $value)
		{
			echo $key . "=>\t{";
			foreach($value as $key2 => $value2)
				echo '[' . $key2 . ']=>"' . $value2 . '", ';
			echo "}\n";
		}
		


		echo "values:\n";
		foreach($this->value as $key => $value)
		{
			echo $key . "=>\t{";
			foreach($value as $key2 => $value2)
				echo '[' . $key2 . ']=>"' . $value2 . '", ';
			echo "}\n";
		}

		echo "edit:\n";
		foreach($this->edit as $key => $value)
		{
			echo $key . "=>\t{";
			foreach($value as $key2 => $value2)
				echo '[' . $key2 . ']=>"' . $value2 . '", ';
			echo "}\n";
		}
	}
}


?>
