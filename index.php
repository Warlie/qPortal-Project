<?PHP
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//definiert Startseite

define('CONFIG','config/config.ini');
define('CONFIG_DEFAULT','config/default.ini');


// Parse ini with sections
if(file_exists(CONFIG))
	$ini_array = parse_ini_file(CONFIG, true);
else
	$ini_array = parse_ini_file(CONFIG_DEFAULT, true);

//print_r($ini_array);

                define('START_PAGE',$ini_array["runtime"]["START_PAGE"]);
                define('STD_URL','index.php?i=%s');
                define('CUR_PATH','');
                define('PLUG_IN_FOLDER',$ini_array["runtime"]['PLUG_IN_FOLDER']);
				define('FRONTEND_INDEX',$ini_array["runtime"]["FRONTEND_INDEX"]);
                define('EDIT_INDEX',$ini_array["runtime"]["EDIT_INDEX"]);

                
				define('INSTALL',false);
				define('REPORT',5); //Reportlevel [0,5]
				define('MEMORY_USAGE', true);
				define('TRACE', true);

				error_reporting($ini_array["error"]["ERROR"]);
				ini_set('display_errors',$ini_array["error"]["SHOW_ERRORS"]);

				//ini_set('error_log','phplog.log');
				//ini_set('memory_limit', '255M');
				//error_reporting(E_ERROR); // | E_WARNING | E_PARSE
				//ini_set('display_er8ors','On');
				//ini_set('memory_limit', '8M');

                                include('classes/class_Contentgenerator.php');
                                include('classes/init.php');
                                include('mod_lib.php');
                                
				$logger_class->setImportance(REPORT, false, MEMORY_USAGE, TRACE);

                              
                                /* setzen der Cacheverwaltung auf 'private' */

                                session_cache_limiter('public');
                                $cache_limiter = session_cache_limiter();

                                /* setzen der Cache-Verfallszeit auf 30 Minuten */
                                session_cache_expire(30);
                                $cache_expire = session_cache_expire();
//$logger_class->setAssert("start(load) with ref=\"$ref\", case_folder=\"$case_folder\", spezial=\"$spezial\"" ,0);
                                /* starten der Session */
				//$SID = session_id();
				//if(empty($SID))
                                session_start();

/*
                                if(isset($_SESSION['besucht'])) {
                                	echo "Du hast die Seite zuvor besucht";
                                	unset($_SESSION['besucht']);
                                } else {
                                	echo "Du hast die Seite zuvor NICHT besucht";
                                	$_SESSION['besucht'] = true;
                                }	
*/


                                // Verwenden Sie bei PHP 4.0.6 oder niedriger $HTTP_SESSION_VARS
                                if (!isset($_SESSION['zaehler'])) {
                                        $_SESSION['zaehler'] = 0;
} else {
    $_SESSION['zaehler']++;
}
				//
				if(!isset($_REQUEST['i']))$_REQUEST['i'] = '';
				if(!isset($_REQUEST['i']))$_REQUEST['i'] = '';
				if($_REQUEST['i'] == '__edit')
				{
					$_SESSION['@_mod'] = 'edit';
					$_REQUEST['i'] = '';
				}
				if($_REQUEST['i'] == '__install' && INSTALL)
				{
					
					$_SESSION['@_mod'] = 'install';
					$_REQUEST['i'] = '';
				}
				if($_REQUEST['i'] == '__main')
				{
					$_SESSION['@_mod'] = '';
					$_REQUEST['i'] = '';
				}
				


                                $logger_class->setstart("-----------------------------\nlog from " 
                                . date("l dS of F Y h:i:s A") 
                                . " Usercount:" . $_SESSION['zaehler'] . "\n-----------------------------");
                                //content
                                
                  	      $logger_class->setAssert("All Sessiondata:\n ", 0) ;
				foreach( $_SESSION as $key => $value ) {
				     $logger_class->setAssert("SESSION[$key]=$value\n ", 0) ;
				} 

                                $content = new ContentGenerator(
                                	$ini_array["database"]["URL"],
                                	$ini_array["database"]["User"],
                                	$ini_array["database"]["PWST"],
                                	$ini_array["database"]["db_name"],
                                	$ini_array["database"]["codeset"]
                                	);
				
                                
                if($_REQUEST['i'] == '__system')
				{

					//$_SESSION['@_mod'] = '';
					if($_REQUEST['modus'] == 'CREATE_ACCOUNT')
					service_create_account( $content ,
					htmlspecialchars($_REQUEST['user']) ,
					htmlspecialchars($_REQUEST['key']),
					htmlspecialchars($_REQUEST['forename']),
					htmlspecialchars($_REQUEST['surname']), 
					htmlspecialchars($_REQUEST['URL']),
					htmlspecialchars($_REQUEST['URLalt'])
					);
					
					if($_REQUEST['modus'] == 'LOG_IN')
					service_log_in( $content ,
					htmlspecialchars($_REQUEST['user']) ,
					htmlspecialchars($_REQUEST['key']));
					
					if($_REQUEST['modus'] == 'LOG_OUT')
					service_log_out( $content );
					
					if($_REQUEST['modus'] == 'GET_DOC')
					service_view_doc( $content ,htmlspecialchars($_REQUEST['URI']) );
					
					if($_REQUEST['modus'] == 'ONTOLOGY')
					service_call_ontology( $content ,htmlspecialchars($_REQUEST['URI']) );

					if($_REQUEST['modus'] == 'COMMAND_LIST')
					service_create_node_ontology( $content 
					,htmlspecialchars($_REQUEST['content']));
										
					
					if($_REQUEST['modus'] == 'ONTOLOGY_STRUCTUR')
					service_call_ontology_structure( $content,htmlspecialchars($_REQUEST['URI']) );				
					
					if($_REQUEST['modus'] == 'APPLY_CODE')
					service_applyCode($content,
						htmlspecialchars($_REQUEST['CODE']), 
						htmlspecialchars($_REQUEST['URI']));

					
					if($_REQUEST['modus'] == 'CREATE_NEW_CODE')
					service_createCode($content, 
						htmlspecialchars($_REQUEST['GROUPS']), 
						htmlspecialchars($_REQUEST['SECLEVEL']), 
						htmlspecialchars($_REQUEST['URI']));
					
				}
                else if(isset($_SESSION['@_mod']) && $_SESSION['@_mod']=='install')
				{
					/*
					*	Hell no
					* TODO center config into a specific file  and find a more nicer solution for the db
					*/
						$load = implode('', file ('surface.sql'));
					$content->injectSQL($load);
					
						
						$lines = file ('index.php');
						
												// Durchgehen des Arrays und Anzeigen des HTML Source inkl. Zeilennummern
						foreach ($lines as $line_num => $line) {
							if(!(false  === ($tmp = strpos($line,'define(\'INSTALL\','))))
							{
								$start = (strpos($line,',',$tmp) + 1);
								$stop = strpos($line,')',$start);
								$lines[$line_num]= substr($line, 0, $start) . 'false' . substr($line, $stop);
							
							}
							
							if(!(false  === ($tmp = strpos($line,'define(\'CUR_PATH\','))))
							{
								$start = (strpos($line,',',$tmp) + 1);
								$stop = strpos($line,')',$start);
								$lines[$line_num]= substr($line, 0, $start) . "'" . $_REQUEST['directory'] . "'" . substr($line, $stop);
							
							}
							}
							
													
						   $fp = fopen("index.php","w");
						   if ($fp)
						   {
							   flock($fp,2);
							   fputs ($fp, implode('',$lines));


							   flock($fp,3);
							   fclose($fp);
							   
							   $_SESSION['@_mod'] = '';
							   //way to system
							   echo "<h1>Surface XML Generator</h1>
							   <p>Now it is running, do you want to go to </p>
							   <p><a href='index.php?i=__edit' >backend</a> or</p>
							   <p><a href='index.php?i=__main' >frontend</a></p>";
							   
							   return null;
						
						   }
						
					}
				
				
				else if($content->errno() == 0 )
				{
				
				$content->setPageParam($_REQUEST);
                                
                                //$content->setXMLTemplate('template/text1.htm');
                                if(isset($_SESSION['@_mod']) && $_SESSION['@_mod']=='edit')
				$content->setXMLstructur(EDIT_INDEX);
                                else
                                {
				$content->setXMLstructur(FRONTEND_INDEX);
				}
				
				$content->setboolPanel(true);
                                $content->setControlElement("div",  array('id'=>"bars"));
                                if(is_Null($tmp = $_REQUEST['i']))$tmp = "";
                                $content->setTreeNodeName($tmp);
                                
                                            try {

                                
                                if(!$content->generate())
				{
					       
						if (!($fp = fopen('./error/404.html', "r"))) {
                
							print("This page is not supported");
							return false;
						}


						while ($data = fread($fp, 4096)) {


							print($data);

						}
						return false;
						
					
					
				}
 
				             
						} catch (NoPermissionException $e) {
							

						header('Cache-Control: no-cache, no-store, must-revalidate');
						header('Pragma: no-cache');
						header('Expires: 0');
						
						if (!($fp = fopen('./error/redirect.html', "r"))) {
                
							print("This page is not supported");
							return false;
						}


						while ($data = fread($fp, 4096)) {


							print($data);

						}
						return false;
						
						}

				
                                print($content->getoutput(SEND_HEADER,'ISO 8859-1'));
				
				}
                else
				{
					

					
					if($_REQUEST['user'] )
					{
					
						$lines = file ('classes/class_database.php');
						
						// Durchgehen des Arrays und Anzeigen des HTML Source inkl. Zeilennummern
						foreach ($lines as $line_num => $line) {
							if(!(false  === ($tmp = strpos($line,'var $User'))))
							{
								$start = (strpos($line,'"',$tmp) + 1);
								$stop = strpos($line,'"',$start);
								$lines[$line_num]= substr($line, 0, $start) . $_REQUEST['user'] . substr($line, $stop);
							
							}
							if(!(false  === ($tmp = strpos($line,'var $pwt'))))
							{
								$start = (strpos($line,'"',$tmp) + 1);
								$stop = strpos($line,'"',$start);
								$lines[$line_num]= substr($line, 0, $start) . $_REQUEST['pwst'] . substr($line, $stop);
							
							}
							if(!(false  === ($tmp = strpos($line,'var $Server'))))
							{
								$start = (strpos($line,'"',$tmp) + 1);
								$stop = strpos($line,'"',$start);
								$lines[$line_num]= substr($line, 0, $start) . $_REQUEST['con'] . substr($line, $stop);
							
							}
							if(!(false  === ($tmp = strpos($line,'var $db_name'))))
							{
								$start = (strpos($line,'"',$tmp) + 1);
								$stop = strpos($line,'"',$start);
								$lines[$line_num]= substr($line, 0, $start) . $_REQUEST['name'] . substr($line, $stop);
							
							}							
							
						}

						
						   $fp = fopen("classes/class_database.php","w");
						   if ($fp)
						   {
							   flock($fp,2);
							   fputs ($fp, implode('',$lines));


							   flock($fp,3);
							   fclose($fp);
							   
							   
							   //way to system
							   echo "<h1>qPortal XML Generator</h1>
							   <p>This system needs some db-tables for running. Follow the link to create them.</p>
							   <p><a href='index.php?i=__install' >Install</a></p>";
							   
						   }
						   else
						   {
							   echo "Datei konnte nicht zum";
							   echo " Schreiben ge�ffnet werden";
						   }

						
						
						
					}
					else
					{
					
					echo "<h1>qPortal XML Generator</h1>
					<p>Thank you for using qPortal. A databaseconnection is necessary to run this Programm.<br/>
					Please insert a valid accound to your Database and the databasename you want to use for.</p>
					<form action='index.php' method='post'>
					Connection<input value='localhost' name='con' /><br/>
					User<input value='root' name='user' /><br/>
					Passwort<input value='' name='pwst' />
					<p/>
					DB-Name<input value='surface' name='name' /><br/>
					<p/>
					Position in directory<input value='/' name='directory' /><br/>
					<input type='submit' />
					</form>";
					}
				}
				
				$boobibooh = '';
				foreach( $_SESSION as $key => $value ) {
				     $boobibooh .= $key . "=(" . $_SESSION[$key] . "); " ;
				} 
				
				$logger_class->setAssert("Ends with Sessiondata:\n ", 0) ;
				foreach( $_SESSION as $key => $value ) {
				     $logger_class->setAssert("SESSION[$key]=$value ", 0) ;
				} 
				$logger_class->setAssert($content->get_System_Overview() ,0);
                        
?>
