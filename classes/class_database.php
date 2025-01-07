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
        
enum WorkModes {
    case WMUpdateAndInsert;
    case WMUpdate;
    case WMInsert;
    case WMDelete;
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
	
	//TODO style:  first letter needs to be upper dings
class database {

//db-conntection
var $User = "root";//root
var $pwt = "";
var $Server = "localhost"; //localhost
var $db_name = "qportal";
//encoding
var $mycodeset = "UTF-8";
var $db;
var $table_db;

var $error_no=0;

private $profiles = [];

var $timestamp;


/** database :constructor
* @param $Server : serveroverride
* @param $User : override
* @param $pwt : override
*
*/

function __construct($Server = "", $User = "", $pwt = "", $db_name = false, $codeset = false){
        
	// set SQL Server settings
	if($Server <> "")$this->Server = $Server;
	if($User <> "")$this->User = $User;
	if($pwt <> "")$this->pwt = $pwt;
	if($db_name)$this->db_name = $db_name;
	if($codeset)$this->mycodeset = $codeset;
	

	if(($Server <> "")  &&
		($User <> ""))
			$this->open_db($Server, $User, $pwt, $db_name, $codeset );
	
        }

        
        public function db_profiles($collection){$this->profiles =  $collection;}
        
        
        public function change_profile($name)
        {
        	
        	$this->close_db();
        	
        	
        	if(array_key_exists($name, $this->profiles) )
        	{
        		$this->open_db(
        			$this->profiles[$name]["URL"], 
        			$this->profiles[$name]["User"], 
        			$this->profiles[$name]["PWST"], 
        			$this->profiles[$name]["db_name"], 
        			$this->profiles[$name]["codeset"]);
        	}
        	else
        	throw new RuntimeException("Profilename '$name' does not exist!");
        	
        }
        
        private function open_db($Server, $User, $pwt, $db_name = false, $codeset = false)
        {
//var_dump($Server, $User, $pwt, $db_name, $codeset);
        	// opens a server connection
        	$this->db = new mysqli($Server, $User, $pwt);
         	
        	if ($this->db->connect_error) 
        		throw new RuntimeException("Connect failed: %s\n", mysqli_connect_error());
	
        	

        	/*
        	// dies with a error message
        	// TODO replace with a good old exception
        	if ($this->db->connect_error) {
        		die('Connect Error (' . $this->db->connect_errno . ') '
        			. $this->db->connect_error);
        	}

    	// uses specific db with error handling
        if(!$this->db->select_db ($this->db_name)){
                echo "datenbank fehlt<p>";
		$this->error_no = mysqli_errno();
                if (!mysqli_query('create database ' . $this->db_name . ';')) 
                //echo "datenbank kann nicht genutzt werden " . mysqli_error() . " " . mysqli_errno();
		$this->error_no = mysqli_errno();
		
		
                }
                */
                
                if(!$this->db->select_db ($db_name))
                	throw new RuntimeException("no such database: %s\n", mysqli_errno());

                if($codeset)
                	$this->db->set_charset ( $codeset );
                
                return $this->db;
        }
        
        private function close_db()
        {
        	$this->db->close();
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

			$this->db->set_charset ( $codeset );
			
		}
		
	/** get_db_encode
	* @return $codeset 
	*
	*/
	function get_db_encode()
		{
			return$this->db->character_set_name ( );

			
		}		

	/** SQL
	* @param $SQLString : SQL
	* Immidiate SQL command
	*/
	function SQL($SQLString){

                        if(is_object($this->table_db)) $this->table_db->free_result();
                        

                            // SQL-String escapen
                        $escapedSQLString = $SQLString;//mysqli_real_escape_string($this->db, $SQLString);
                            try {
        $this->table_db = mysqli_query($this->db, $escapedSQLString);
    } catch (mysqli_sql_exception $e) {
        // Fehler fangen und detaillierte Informationen ausgeben
        echo "Fehler bei der Ausführung der SQL-Abfrage: \n<br>";
        echo "Fehlercode: " . $e->getCode() . "\n<br>";
        echo "Fehlermeldung: " . $e->getMessage() . "\n<br>";
        echo "SQL-Abfrage: " . $SQLString . "\n<br>"; // Nur in der Entwicklung anzeigen!
        return ["Effected_rows" => 0, "Last_ID" => 0]; 
    }

                  //      $this->table_db = mysqli_query($this->db, $escapedSQLString);
                        if(mysqli_errno($this->db)<>0)echo "Fehler ist aufgetreten \n<br>" . '(' . $SQLString . ")\n<br>" . mysqli_error($this->db);                        

                        return ["Effected_rows" => mysqli_affected_rows($this->db), "Last_ID" => mysqli_insert_id($this->db)];
                                                }


    //load bulk 
	public function loadfile($filename)
	{$this->injectSQL(implode('', file ('surface.sql')));}
	
	/**
	* processes a bunch of requests with no response
	*
	* @param $SQLString string : SQL string
	*/
	public function injectSQL($SQLString)
	{
		
		//echo $SQLString;
		
		$teile = explode(";\n", $SQLString);
		for($i = 0; count($teile)>$i;$i++)
		{

		if(strlen(trim($teile[$i]))>2)$this->SQL($teile[$i] . ';');
		}

	}

	/**
	* processes a bunch of requests with no response
	*
	* @param $SQLString string : SQL string
	*/
	public function injectArraySQL($SQLArray)
	{
		
		//echo $SQLString;
		
		foreach($SQLArray as &$line )
		{
			   
			$line['ID'] = $this->SQL($line['SQL'])['Last_ID'];
		}
		
		return $SQLArray;

		/*
		$teile = explode(";\n", $SQLString);
		for($i = 0; count($teile)>$i;$i++)
		{

		if(strlen(trim($teile[$i]))>2)$this->SQL($teile[$i] . ';');
		}
*/
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



	/** gives back a rst object, based on the sql request
	* @param sql : SQL String
	* @return rst Object with selected data
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

			
if(!function_exists('extract_table')){

        //findet alle tabellennamen in einem SQL-String
        function extract_table($sql,$low_sql=null,$pos=0,$my_array=null,$pointer=0,$mysec=0){
		
		// first loop is for FROM and rest for JOIN
                $sign = ($pointer==0)?'from':'join';

		// copy sql in lower case
		if(is_null($sql))$sql = ";";
                if(is_null($low_sql))$low_sql = strtolower($sql);
		//position of next join or from

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
        if(($end_word = strpos($sql,' ',$pos+1)) == false)
        	if(($end_word = strpos($sql,';',$pos+1)) == false)$end_word = strlen($sql);
       
                $my_array[$pointer]=substr($sql,$pos-1 ,1 +$end_word-$pos);
		

		
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


                 //erstellt neuen rst()
                 $rst = new rst($listOfTBLs = extract_table($sql)
                 	 , 	$this->define_rst_fields(
                 	 	 $listOfTBLs,
                 	 	 ($cols = /* zeile 151 findet alle felder */ extract_col($sql)),
                 	 	 $this->db
                 	 	 )
                 	 ,	$this->db
                 	 );
                                                     
                 //uebergibt alle tabellennamen
                 //$rst->table = extract_table($sql); //zeile 125

				 
                 $res = /* zeile 445 */ $this->load_field_rst($rst,$sql,($cols == '*')?false:true,$this->mycodeset);
                 
                 
		 //$rst->show_content();
		 return $res;
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

          $prim = $rst->prim_field(); // prim problem
	      
	      if($add_pri && (false ===(strpos(strtoupper($sql),'DISTINCT'))))
	      {
             	if(!$prim)throw InvalidArgumentException("no primary key definiert");
              	$sql = 'SELECT ' . implode(',',$prim) . ',' . substr($sql,7);
	      }

	      // sql request with worse error handling
          $fdresult = $this->db->query($sql); 
          if($this->db->errno <>0)echo 'Fehler aufgetreten: ' . $this->db->errno . '(' . $sql . ")\n";                              

          // fetch all over the result
          while ($zeile = $fdresult->fetch_row()) {

                reset($rst->field);

                while (!is_null($key = key($zeile) )  )
                {
                                         
                	$field_list = $fdresult->fetch_fields(); //$key

                	if($field_list[$key]->table)
                		$new_key = $field_list[$key]->table . '.' . $field_list[$key]->name;
                	else
                		$new_key = $field_list[$key]->name;
         	
                	$value = $zeile[$key];
                	next($zeile);

                    $rst->setValue($new_key,$value,null,true);

                 	next($rst->field);}
                                
                 	$rst->update();
          		}

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
private function collect_mysql_col($table,$filter_in=null,$array_list=null, $db_obj=null){

		// * benoetigt alle tabellen -> filter wird abgestellt
		if($filter_in == '*')$filter_in=null;

		$fdresult = $this->db->query("SHOW COLUMNS FROM $table;");
		if($this->db->errno <>0)echo "<p>Fehler ist aufgetreten: " . mysqli_error() . "<p>bei SQL_String: SHOW COLUMNS FROM $table;";
                                 //filterfunction
        $filter = (is_null($filter_in))? false : true ;

        //setzt bestehendes datenfeld vorne an
        if(!is_null($array_list))$res = $array_list;

        // Die Datensaetze werden einzeln gelesen
        while($zeile = $fdresult->fetch_assoc())
        {

        	//fragt ab, ob dieses Feld auch gef                        
        	if($filter)
        		$filter_res = (sql_in_array($filter_in,$zeile['Field'],$table)|| $zeile['Key']=='PRI');
        	else 
         		$filter_res= true;
                                                                
         		if($filter_res){        
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
function define_rst_fields($tbl,$fields_to_add, $db){
	//if(is_null($rst))return null;
	//if(is_null($rst->table))return null;

	foreach ($tbl as $value){$res = $this->collect_mysql_col( $value,$fields_to_add,$res, $db);}

	return $res;
}
                
//--------------------------------------
                                
//---------------------------------------------------------------------------------------------------------------


	
	//---------------------------------------------------------------------------------------------------------------

private function update_data($rst,$pos){

	$len = count($rst->edit);
	$prim = $rst->prim_field($rst->table[$pos]);

    //menge der aufgaben
    for($k=0;$k < $len;$k++){

    	//checks the request to be relevant for the current table
    	if($rst->edit[$k]['table'] == $rst->table[$pos] && $rst->edit[$k]['pos'] < $rst->rst_num()){

    		if($rst->edit[$k]['value'] <> '')
    		{

    			$sign1 = database::sql_type_sign($rst->type($rst->edit[$k]['field']));
    			$sign2 = database::sql_type_sign($rst->type($prim[0]));

    			$sql_string = 'update ' . $rst->table[$pos];
    			$sql_string .= ' set ' . $rst->edit[$k]['field'] . ' = ' . $sign1 . $this->db->real_escape_string($rst->edit[$k]['value']) . $sign1;
    			$sql_string .= ' where ' . $prim[0] . ' = ' . $sign2 . $rst->value($rst->edit[$k]['pos'],$prim[0]) . $sign2 . ';';
      
//echo $sql_string;
    			$fdresult = $this->db->query($sql_string);
    			if($this->db->errno<>0)echo 'Fehler aufgetreten: ' . '(' . $sql_string . ')' . $this->db->error;
    		}
    	}
	}
}

function insert_rst($rst){
//echo "--------------------result-------------------------------\n";
//var_dump($rst->get_insert_array_statements());
//var_dump($rst->get_update_array_statements());
//var_dump($rst->get_insert_statements());
//var_dump($rst->get_update_statements());
//var_dump($rst->get_delete_statements());

	$res = array_merge(
		$this->injectArraySQL($rst->get_insert_array_statements()),
		$this->injectArraySQL($rst->get_update_array_statements()),
		$this->injectArraySQL($rst->get_delete_array_statements()));
	
    //    $this->injectSQL($rst->get_insert_statements());
    //    $this->injectSQL($rst->get_update_statements());
    //    $this->injectSQL($rst->get_delete_statements());
    //    var_dump($res);
        return $res ;
        
//var_dump($rst->edit);
	//tabellenname �bernommen
        $table = $rst->table;

	   $prim_tbl = $rst->prim_field2(); //$table[$i]
        
    //jede tabelle wird einzeln abgefragt
	for($i=0;$i<count($table);$i++)
	{

	   //ein prim pro durchlauf
	   $prim = $rst->prim_field(); //$table[$i]

	   $autoinc = (''==$rst->type( $prim[$i] ,'Extra'));

       $sql_string = 'SELECT ' .  implode(', ', $this->filter_table( $rst,$table[$i],true));
       $sql_string .= ' FROM ' . $table[$i];
       $sql_string .= ' ORDER BY ' .implode(', ', $prim_tbl[$table[$i]] ) . ';'; //$sql_string .= ' ORDER BY ' . $prim[$i] . ';';

					   //echo $sql_string;
	$fdresult = mysqli_query($this->db, $sql_string);

	if(mysqli_errno($this->db)<>0)echo 'Fehler aufgetreten (' . $sql_string . '): ' . mysqli_error($this->db);

       //Abfrage �ber alle elemente einer tabelle erstellt

       //ermittelt die anzahl der zeilen	
    	if(!is_null($prim_tbl[$table[$i]]))
	    { 
	    	// abschnitt 
		     $loop = mysqli_num_rows($fdresult); //amount of rows
		     
		     //prepares all the big_list for containing the values of the primary keys

		     //array mit allen primwerten zum abgleichen
		     $j = 0;
		     $keys = explode( '.', $prim[$i] );

		     $pos = count($keys) - 1;
		     $big_list = array();
		     while($zeile = $fdresult->fetch_assoc())$big_list[$j++] = $zeile[$keys[$pos]];


             //x zeichnet auch auf, wieviele elemente gespeichert wurde
             $x=0; //non speaking name variable
             // easier way with while and foreach

            // var_dump($rst->db_field_list()[1]);
             
             $fields = $this->filter_table( $rst,$table[$i],false);
//var_dump($fields);
			for($j=0;$j<($rst->rst_num());$j++)
			{
				if(count($fields)>0)
				{  //triviale abfrage

					//testet ob es diesen schl�ssel bereits gibt oder ob er leer ist
					if(!in_array($rst->value($j,$prim[$i]),$big_list) || 
						is_null($rst->value($j,$prim[$i])) || 
						$rst->value($j,$prim[$i]) == '' )
					{

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

				//SQL-Statement
				$sql_string = 'insert ' . $table[$i] . ' ( ' . implode(', ', $fields) . ') values (';
				$komma = '';
                                      //
                for($l=0;$l<count($fields);$l++)
				{
					$tmp = $this->sql_type_sign($rst->type($fields[$l]));
					$string = $this->db->real_escape_string($rst->value($map[$j],$fields[$l]));
							
					if( $tmp == '' && (('' == $string) || is_null( $string )  )) $string = 'NULL';

					if($tmp <> '\'')
						$sql_string .= ' ' . $komma . str_replace(',','.',$string)  . ' ';
					else
						$sql_string .= ' ' . $komma . $tmp . $string . $tmp . ' ';

					$komma = ', ';
				}
				
				$sql_string .= ');';

				
				
				//var_dump($sql_string);
				
			   	//speichert einen Datensatz, der neu geschieben wurde
                $fdresult = $this->db->query($sql_string);
				if($this->db->errno<>0)
				{

					echo '<br>Fehler aufgetreten (' . $sql_string . '): ' . $this->db->error . '</br>';

				}
			}//end loop1

         //schleife f�r aktualisieren ge�nderter daten
         $this->update_data($rst,$i);

	    }
	}
}



         //------------------------------------------------------------------------------------------------------
         //filtert alle spaltennamen heraus, die nichts mit der Tabelle zu tun haben
private  function filter_table($rst,$tablename,$boolPrimary=true)
{
	$loop = 0;
	$res = [];

	reset($rst->field);
	while ($key = key($rst->field)  ) 
	{
		
		$except = ($rst->field[$key]['Extra']<>'auto_increment') || $boolPrimary;

       	if(($rst->field[$key]['Table_name']==$tablename) && $except)
       		{$res[$loop++] = $key;}
       	next($rst->field);
	}
	return $res;
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

        		// needs entry in log
        		return $zeile[$col[1]];
        	}
        }
	return null;
}


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
var $new = [];
var $prim_fields;
var $tmp = null;
var $var_cur_pos=0;
var $pos_in_array=0;
public $table;
private $index;
private $db_connection;

private $workmode = WorkModes::WMUpdateAndInsert;

private $update = [];
private $insert = [];
private $delete = [];

function __construct($tbl, $fields, $db_connection) {
	$this->db_connection = $db_connection;
	$this->table = $tbl;
	$this->field = $fields;
	
	foreach($this->table as $value)
	{
		$this->index[$value] = [];
		$this->update[$value] = [];
		$this->insert[$value] = [];
		$this->delete[$value] = [];
	}
	$this->prim_fields = $this->prim_field2(); 
}

public function set_mode($mode){$this->workmode = $mode;}

private function set_index(array $record, int $pos)
{
	
	foreach($this->table as $value)
	{
		
		$idx = '';
		foreach($this->prim_fields[$value] as $prims)
		{
			if(is_null($record[$prims]))break;
			$idx .= "{\"" .  $record[$prims] . "\":";

		}
		
		$idx .= strval($pos);
		$idx .= str_repeat("}",count($this->prim_fields[$value]));

		if(!is_null($arr = json_decode($idx,true)))
			$this->index[$value] = array_merge_recursive_distinct($this->index[$value], $arr);
		 //if($value =='tbl_orga_EmplToJob') var_dump($idx, $arr,  $this->index[$value]);
	}
	

}

/**
*	uses the index to shortcut the table
*
*/
private function get_row_position(array $record, string $tableName)
{
	$next_element = function ($arr, $key ) { return $arr[ $key ];};
	

	$idx = $this->index[$tableName];

//if($tableName =='tbl_orga_EmplToJob') var_dump($idx);	
			foreach($this->prim_fields[$tableName] as $prims)
			{
				if(is_null($record[$prims]) ||is_null($idx = $next_element($idx, $record[$prims])))
				{

					return false;
				}
			}
					
	return $idx;
	
}

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

    if(is_null($this->tmp))
	{
		
		$this->set_index( $this->value[$this->pos_in_array] , $this->pos_in_array);
		
		$this->pos_in_array++; 
		}
		else
		{
			//echo "\n tmp \n";
			$completeRow = [];
			$sortedRow = [];
			foreach($this->table as $value)
				$sortedRow[$value] = [ 'pos'=>0, 'tbl'=>[] ];
			//echo "saves " .  count($this->tmp) . " Objects in edit\n";
		for($i= 0 ; $i < count($this->tmp);$i++)
		{
			
			//updates werden abrufbar gemacht
			//$this->value[$this->tmp[$i]['pos']][$this->tmp[$i]['field']]=$this->tmp[$i]['value'];

			//
			//if(is_array($this->edit))$len = count($this->edit);else$len = 0;

			
				//$completeRow[$this->tmp[$i]['field']] = $this->tmp[$i]['value'];
				
				$sortedRow[$this->tmp[$i]['table']]
				['tbl']
				[$this->tmp[$i]['field']] =  $this->tmp[$i]['value'];
				
/*
		
                $this->edit[$len]['pos']=$this->tmp[$i]['pos']; 
                $this->edit[$len]['field']=$this->tmp[$i]['field']; 
                $this->edit[$len]['value']=$this->tmp[$i]['value'];
                $this->edit[$len]['table']=$this->tmp[$i]['table'];
		
  */              
		
		}
		
		//var_dump($this->workmode);
		
		$myUpdate = [];
		
		foreach($this->table as $value)
		{
			

			if(false !== ($pos = $this->get_row_position($sortedRow[$value]['tbl'] , $value)))
			{

				if($this->workmode == WorkModes::WMUpdateAndInsert ||
					$this->workmode == WorkModes::WMUpdate){ 

					$this->update[$value][] =  $sortedRow[$value]['tbl'];

					}

				if($this->workmode == WorkModes::WMDelete )
					$this->delete[$value][] =  $sortedRow[$value]['tbl'];				
				
				$sortedRow[$value]['pos'] = $pos;
				//var_dump($sortedRow[$value]);
			}
			else
			{

				if($this->workmode == WorkModes::WMUpdateAndInsert ||
					$this->workmode == WorkModes::WMInsert )
				{
					$this->set_index($sortedRow[$value]['tbl'], -1);
					$this->insert[$value][] = $sortedRow[$value]['tbl'];
				}
				
				if($this->workmode == WorkModes::WMDelete )
					$this->delete[$value][] =  $sortedRow[$value]['tbl'];				
				

				
				$sortedRow[$value]['pos'] = false;
				//var_dump($sortedRow[$value]);
			}
		}
		
		$this->tmp = null;
//var_dump($this->value);
	}
//	            "@attributes": {
//                "id": "9"
//            },

}

function prev_ds(){$this->var_cur_pos--;}

function first_ds(){$this->var_cur_pos=0;return !$this->EOF();}

function last_ds(){$this->var_cur_pos=$this->pos_in_array;return $this->pos_in_array > 0;}

function EOF(){return !($this->var_cur_pos<$this->pos_in_array);}

function BOF(){return !($this->var_cur_pos>0);}

function setValue($Field,&$Value,$num=null,$editable=false){
	 //if($Value == '36')throw new RuntimeException("booh ");
//var_dump($Field,$Value,$num,$editable);
		if(is_object($Value) )
		$conv_value = &$Value;
		else
		$conv_value = $Value;
//if(!$editable) echo 'Invalue in value:<i>' . $this->pos_in_array . '</i><b>' . $Field . '</b> ' . $Value .  "<p>\n";
			//es existieren keine Felder zum bef�llen
        if(is_null($this->field)) throw new RuntimeException("noch keine Felder definiert");

                                //testet,ob es ein feld gibt
        if(is_Null($this->field[$Field])) throw new RuntimeException("Feld nicht vorhanden: $Field ");
								
                                                                        
                                     //entscheidet, ob
        if(!($this->value[$field]['Extra']<>'auto_increment' || $editable)) throw new RuntimeException("PrimaryKey is auto_increment");
									       
    // $new                                                                   
        if($editable) //if(is_null($num))
		{//throw new RuntimeException(" neu ");
			 //echo 'saves in value:<i>' . $this->pos_in_array . '</i><b>' . $Field . '</b> ' . $Value .  "<p>\n";
			 //var_dump($this->prim_fields[isolate_tbl_nm($Field)]);
			//if(in_array($Field,$this->prim_fields[isolate_tbl_nm($Field)]))echo "$Field\n"; 
            $this->value[$this->pos_in_array][$Field]=$conv_value;
		}
		else
		{

			if(is_null($this->tmp))$this->tmp = array();
											//var_dump($Field,$Value,$num,$editable);
			$pos_in_temp = count($this->tmp);
            $this->tmp[$pos_in_temp]['pos']=$num;
            $this->tmp[$pos_in_temp]['table'] = isolate_tbl_nm($Field);
            $this->tmp[$pos_in_temp]['field']=$Field;
            $this->tmp[$pos_in_temp]['value']=$conv_value;

         }


}

function setField($Field,$Type,$Null,$Key,$Default,$Extra){


        if(!is_Null($this->field[$Field]['Type']))
        	throw new RuntimeException("Daten bereits vorhanden $Field ");


                //isoliert table und field
                $element = explode ( '.' ,$Field );


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

        return $this->field[$field][$element];}

function db_field_list(){
       

                        $res_all = [];
                        $res_sorted_by_tbl = [];
                       // reset($this->field);
                        foreach($this->field as $key => $value ) {
                        	
                        	$tbl = $value['Table_name'];
                        
                        	if(!$res_sorted_by_tbl[$tbl])
                        		$res_sorted_by_tbl[$tbl] = [$key];
                        	else
                        		$res_sorted_by_tbl[$tbl][] = $key;
                        	
                        		$res_all[] = $key;

                        //next($this->field);
       }
       return [$res_all, $res_sorted_by_tbl];
     }

function rst_num(){return $this->pos_in_array; } 

function rst_cur_num(){return $this->var_cur_pos; }

function prim_field($tablename = null){

	$field;
	$pointer=0;

	reset($this->field);
	while (!is_null($key = key($this->field))){

		if(!is_null($tablename)){
			//all tables
        	if($this->field[$key]['Key']=='PRI' && $this->field[$key]['Table_name']==$tablename){$field[] = $key;}

        }else{
        	//specific table
        	if($this->field[$key]['Key']=='PRI')$field[] = $key;
        }

	next($this->field);}

	if(is_array($field))return $field; else return false;
}

function prim_field2(){
	$res = [];
	reset($this->field);
	while (!is_null($key = key($this->field))){
		$tbl = current($this->field)['Table_name'];
		if(!is_array($res[$tbl]))$res[$tbl] = [];
		
        if($this->field[$key]['Key']=='PRI')
        	{$res[$tbl][] = $key;}
        	
		next($this->field);
	}
	
	return $res;
}

public function get_insert_array_statements()
{

	$res = [];
	foreach($this->insert as $key => $value) 
	{
		
		//var_dump('insert',$key, $value , count($value));
		try {
		
		foreach($value as $statement)
		{		

			//empty statements are useless and needed to be ignored
			if(count($statement) == 0)continue;
			
 //$db_connection
            		$value = array_filter($statement, [$this, 'isValidPrime'], ARRAY_FILTER_USE_BOTH);

            		array_walk(
            			$value
            			, [$this, 'applyNotationFormat']);
//function() use (&$value) {
//    return $value++;
//  };


		$res[] = ['SQL' => "INSERT IGNORE INTO $key (" . implode( ', ' , array_keys($value))
			.  ") VALUES (" . implode( ', ' , array_values($value)) . ");", 'prim'=>$this->prim_field2()[$key], 'tbl'=>$key, 
			'transaction'=>'INSERT'];


		}
		
        } catch (RuntimeException $e) {
            //var_dump(get_class($e), $e->getMessage());
            continue;
        }
		
	}
	
	
	$list = [];
	
	return array_filter($res, function($mylines) use (&$list) {
    if (in_array($mylines['SQL'], $list)) return false;
    	
   $list[] = $mylines['SQL'];
   return true;

    });	
}

public function get_update_array_statements()
{

		$res = [];
	foreach($this->update as $key => $value) 
	{
		foreach($value as $statement)
		{		
			$useless = false;
			array_walk($statement, [$this, 'applyNotationFormat'] );
			$filtered = array_filter($statement, [$this, 'isNotPrime'], ARRAY_FILTER_USE_KEY);
			if(count($filtered) == 0)continue;
			//var_dump($statement);
			$line = "UPDATE $key SET ";
			$komma = '';
			foreach($filtered as $name => $tblValue)
			{
				$line .= $komma . "$name = $tblValue ";
				$komma = ", ";
			}
			
		$line .= " WHERE ";
		$komma = '';
		
		foreach($this->prim_fields[$key] as $prims)
		{
			$useless = $useless || is_null($statement[$prims]) || 0 == strlen($statement[$prims]); 
			$line .=  $komma . " $prims = " . $statement[$prims] . " ";
			$komma = ", ";
		}
		$line .= ";";
		if(!$useless)
		{
			$res[] = ['SQL' => $line, 'prim'=>$this->prim_fields[$key], 'tbl'=>$key, 
			'transaction'=>'UPDATE'];
		}
		}
	}
	$list = [];
	
	return array_filter($res, function($mylines) use (&$list) {
    if (in_array($mylines['SQL'], $list)) return false;
    	
   $list[] = $mylines['SQL'];
   return true;

    });
	
}

public function get_delete_array_statements()
{
	/*
	
DELETE FROM table_name WHERE condition;
	*/

	$res = [];
	
	
	foreach($this->delete as $key => $value) 
	{
		foreach($value as $statement)
		{		
			$useless = false;
			array_walk($statement, [$this, 'applyNotationFormat'] );
			//$filtered = array_filter($statement, [$this, 'isNotPrime'], ARRAY_FILTER_USE_KEY);
			//if(count($filtered) == 0)continue;
			//var_dump($statement);
			$line = "DELETE FROM $key WHERE ";

			$komma = '';
		
			
				
			$prime_field = [];
			$prime_value = [];
		foreach($this->prim_fields[$key] as $prims)
		{

			$useless = $useless || is_null($statement[$prims]) || 0 == strlen($statement[$prims]);

			$prime_field[] = $prims;
			$prime_value[] = $statement[$prims];
						
			//$line .=  $komma . " $prims = " . $statement[$prims] . " ";
			//$komma = ", ";
		}
		
		if(count($this->prim_fields[$key])> 1)
		{
			$line .= " (" . implode(', ', $prime_field) . ") = (" .  implode(', ', $prime_value) . ")";
		}
		else
			$line .= " " . implode(', ', $prime_field) . " = " .  implode(', ', $prime_value) . " ";		
		
		$line .= ";";
		if(!$useless)
		{
			$res[] = ['SQL' => $line, 'prim'=>$this->prim_fields[$key], 'tbl'=>$key, 
			'transaction'=>'DELETE'];
		}
		}
	}
	
	$list = [];
	
	return array_filter($res, function($mylines) use (&$list) {
    if (in_array($mylines['SQL'], $list)) return false;
    	
   $list[] = $mylines['SQL'];
   return true;

    });
}

public function get_update_statements()
{
	/*
	
	UPDATE table_name
SET column1 = value1, column2 = value2, ...
WHERE condition;
array_walk($fruits, 'test_alter', 'fruit'); applyNotationFormat
	*/

	$res = [];
	foreach($this->update as $key => $value) 
	{
		foreach($value as $statement)
		{		
			$useless = false;
			array_walk($statement, [$this, 'applyNotationFormat'] );
			$filtered = array_filter($statement, [$this, 'isNotPrime'], ARRAY_FILTER_USE_KEY);
			if(count($filtered) == 0)continue;
			//var_dump($statement);
			$line = "UPDATE $key SET ";
			$komma = '';
			foreach($filtered as $name => $tblValue)
			{
				$line .= $komma . "$name = $tblValue ";
				$komma = ", ";
			}
			
		$line .= " WHERE ";
		$komma = '';
		
		foreach($this->prim_fields[$key] as $prims)
		{
			$useless = $useless || is_null($statement[$prims]) || 0 == strlen($statement[$prims]); 
			$line .=  $komma . " $prims = " . $statement[$prims] . " ";
			$komma = ", ";
		}
		$line .= ";";
		if(!$useless)$res[] = $line;
		}
	}
	return implode("\n", array_unique($res));		
}

public function get_delete_statements()
{
	/*
	
DELETE FROM table_name WHERE condition;
	*/

	$res = [];
	
	
	foreach($this->delete as $key => $value) 
	{
		foreach($value as $statement)
		{		
			$useless = false;
			array_walk($statement, [$this, 'applyNotationFormat'] );
			//$filtered = array_filter($statement, [$this, 'isNotPrime'], ARRAY_FILTER_USE_KEY);
			//if(count($filtered) == 0)continue;
			//var_dump($statement);
			$line = "DELETE FROM $key WHERE ";

			$komma = '';
		
			
				
			$prime_field = [];
			$prime_value = [];
		foreach($this->prim_fields[$key] as $prims)
		{

			$useless = $useless || is_null($statement[$prims]) || 0 == strlen($statement[$prims]);

			$prime_field[] = $prims;
			$prime_value[] = $statement[$prims];
						
			//$line .=  $komma . " $prims = " . $statement[$prims] . " ";
			//$komma = ", ";
		}
		
		if(count($this->prim_fields[$key])> 1)
		{
			$line .= " (" . implode(', ', $prime_field) . ") = (" .  implode(', ', $prime_value) . ")";
		}
		else
			$line .= " " . implode(', ', $prime_field) . " = " .  implode(', ', $prime_value) . " ";		
		
		$line .= ";";
		if(!$useless)$res[] = $line;
		}
	}
	return implode("\n", array_unique($res));	
}

function isNotPrime($key)
{

return $this->type($key,'Key')!='PRI' ;
}

function isValidPrime($value, $key ) //
{
	if($this->type($key,'Key')=='PRI')
	{
		
		
		if($this->type($key,'Extra')=='auto_increment' && (is_null($value) || strlen($value) == 0 || $value === false))
		{
			return false;
		}
		else
		{
			if( $value === false )
				throw new RuntimeException("Invalid Primary Key");
		}

	}

	return true;
}

function applyNotationFormat ( &$value, string $key) //, object $db_connection
        {
        	/* -----------------replace for numbers ------------------------------ */
        	$find = array(",");
        	$replace = array(".");
        	
        	$string = $this->field[$key]['Type'];
        					
        	if(is_null($value))
        		 if($this->field[$key]['Null'] == 'NO')

        		 	throw new RuntimeException("NULL is not allowed in field: $key" );

        		else
        		{
        			$value = 'NULL';
        			return;
        		}

        	
        	switch(substr($string,0,5)){
                                
        			case 'date':

        				$timestamp = strtotime($value);
        				$value = "'" . date('Y-m-d', $timestamp) . "'";
        				return;
            	case 'text':
            	case 'year':
				case 'datet':
				case 'time':
				case 'times':
				case 'longt':
				case 'longb':
                case 'char(': 
                case 'varch':
                	$value = "'" . $this->db_connection->real_escape_string( $value )  . "'";
                	return;
                break;
            	case 'decim':

            		$value = str_replace($find,$replace,$value);
            }
                
                // if empty than Default
                if(strlen($value) == 0)
                	$value = $this->field[$key]['Default'];

                // valid numbers are fine here
                if(is_numeric($value))
                {
                	return;
                }
                
                //test for null as valid
                if($this->field[$key]['Null'] != 'NO')
        		 	throw new RuntimeException("\"" . $key . "\":Number expected (\"" . $value . "\"), empty string given and null not allwoed");
        		else
        		{
        			$value = 'NULL';
        			return;
        		}
                                          
                                
        }

public function show_content()
	{
		var_dump($this->update, $this->insert, $this->delete);
	}
        
}


?>
