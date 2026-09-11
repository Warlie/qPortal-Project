<?PHP
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
//definiert Startseite
require_once __DIR__ . '/vendor/autoload.php';
define('CONFIG','config/config.ini');
define('CONFIG_DEFAULT','config/default.ini');

require_once('PlugIn/plugin_log.php');

$logger_class = new Logger();

include('mod_lib.php');


$list_of_depending_param_names = ['--p', '--m', '-i', '-u','-p'];
$list_of_placeholders = [
	'from' => [
		'__DIR', 
		'__URL'] ,
	'to' => [
		__DIR__,
		(array_key_exists('REQUEST_URI', $_SERVER)?$_SERVER['REQUEST_URI'] : "")
		]];
$list_of_configuration_parameters = [
	'SERVER_URL' =>  ['runtime', 'SERVER_URL'],
	'START_PAGE' => ['runtime', 'START_PAGE'],
	'STD_URL' => ['runtime', 'STD_URL'],
	'CUR_PATH' => ['runtime', 'CUR_PATH'],
	'ROOT_DIR' => ['runtime', 'ROOT_DIR'],
	// resolved before the entries below so they can use __PROGRAM_DIR
	'PROGRAM_DIR' => ['runtime', 'PROGRAM_DIR'],
	'PLUG_IN_FOLDER' => ['runtime', 'PLUG_IN_FOLDER'],
	'REGISTRY_VOCABULARY' => ['runtime', 'REGISTRY_VOCABULARY'],
	'FRONTEND_INDEX' => ['runtime', 'FRONTEND_INDEX'],
	'EDIT_INDEX' => ['runtime', 'EDIT_INDEX'],
	'INTERN' => ['runtime', 'INTERN'],
	'LANGUAGE_INPUT_DEFAULT' => ['default', 'LANGUAGE_INPUT'],
	'LANGUAGE_OUTPUT_DEFAULT' => ['default', 'LANGUAGE_OUTPUT'],
	'LOG_PATH' => ['log', 'path'],
	'LOG_LEVEL' => ['log', 'level'],

	'XML_CASE_FOLDING_DEFAULT' => ['default', 'XML_CASE_FOLDING'],
	'XML_SCHEMA_DEFAULT' => ['runtime', 'XML_SCHEMA_DEFAULT'],
	
	'DATABASE_URL' => ['database', 'URL'],
	'DATABASE_DB_NAME' => ['database', 'db_name'],
	'DATABASE_USER' => ['database', 'User'],
	'DATABASE_PWST' => ['database', 'PWST'],
	'DATABASE_CODESET' => ['database', 'codeset'],
	
	'CONSTANTS' => ['constants', 'constant'],

	'PLUGINS' => ['short', 'plugin'],

	'SECURITY_CIPHER'    => ['security', 'cipher'],
	'SECURITY_STAMP_KEY' => ['security', 'stamp_key'],

	'INTERN_KEYS' => ['intern', 'key'],

	'INTERN_ANONYMOUS' => ['intern', 'anonymous'],

	'QUERY_PARAM' => ['runtime', 'QUERY_PARAM'],

	'PEDL_FORCE_REBUILD' => ['runtime', 'PEDL_FORCE_REBUILD']
	];

// Parse ini with sections.
// config.ini is laid over default.ini instead of replacing it: entries the
// installation does not set stay inherited, and additions to default.ini
// (new plugin shortcuts in [short]) reach existing installations.
$ini_array = file_exists(CONFIG_DEFAULT) ? parse_ini_file_multi(CONFIG_DEFAULT, true) : [];
if(file_exists(CONFIG))
	$ini_array = array_replace_recursive($ini_array, parse_ini_file_multi(CONFIG, true));
	
	$shortCut = [];

//
//print_r($ini_array); 
//print_r(parse_ini_file_multi(CONFIG_DEFAULT, true));
/*
                define('START_PAGE',$ini_array["runtime"]["START_PAGE"]);
                define('STD_URL','index.php?i=%s');
                define('CUR_PATH','');
                define('PLUG_IN_FOLDER',$ini_array["runtime"]['PLUG_IN_FOLDER']);
				define('FRONTEND_INDEX',$ini_array["runtime"]["FRONTEND_INDEX"]);
                define('EDIT_INDEX',$ini_array["runtime"]["EDIT_INDEX"]);
                define('ROOT_DIR',($ini_array["runtime"]["ROOT_DIR"]!=""? $ini_array["runtime"]["ROOT_DIR"] : __DIR__));
                
                define('LANGUAGE_INPUT_DEFAULT', ($ini_array["default"]["LANGUAGE_INPUT"]!=""? $ini_array["default"]["LANGUAGE_INPUT"] : "XML"));
                define('LANGUAGE_OUTPUT_DEFAULT', ($ini_array["default"]["LANGUAGE_OUTPUT"]!=""? $ini_array["default"]["LANGUAGE_OUTPUT"] : "XML"));

                define('XML_CASE_FOLDING_DEFAULT', ($ini_array["default"]["XML_CASE_FOLDING"]!=""? $ini_array["default"]["XML_CASE_FOLDING"] : "1"));
*/

	$results = [];
	
	array_push(
		$results,
	createConfigFromINIFile($ini_array, $list_of_configuration_parameters,
	$list_of_placeholders,
	getSystemArgument('--p', $list_of_depending_param_names, $_REQUEST)
	));

	if(count($results[0] )> 0)
	{
		array_push(
			$results,
			createConfigFromINIFile($ini_array, $list_of_configuration_parameters,
				$list_of_placeholders
				));
	

		if(count($results[1] ) > 0)
		{
			array_push(
				$results,
				createConfigFromINIFile(parse_ini_file_multi(CONFIG_DEFAULT, true), $list_of_configuration_parameters,
					$list_of_placeholders
					));	

	if(count($results[2]) > 0) throw new Exception( "Following stack of missing ini Entries:\n" .   implode("\n", $results[2]) . "\n" );
		}
	}

				define('INSTALL',false);
				define('REPORT',LOG_LEVEL); //Reportlevel [0,5]
				define('MEMORY_USAGE', true);
				define('TRACE', true);

				set_time_limit(300);
				
				error_reporting($ini_array["error"]["ERROR"]);
				ini_set('display_errors',$ini_array["error"]["SHOW_ERRORS"]);

				//ini_set('error_log','phplog.log');
				//ini_set('memory_limit', '255M');
				//error_reporting(E_ERROR); // | E_WARNING | E_PARSE
				//ini_set('display_er8ors','On');
				//ini_set('memory_limit', '8M');

				require_once('classes/finite_state_machine/enums.php');
				require_once('classes/finite_state_machine/class_Transducer.php');
				require_once('classes/finite_state_machine/class_Acceptor.php');
				require_once('classes/finite_state_machine/class_Mealy.php');
				require_once('classes/connection_profile.php');
				require_once('classes/search_model/index_model.php');
				require_once('classes/NameSpaceBehaviorRegistry.php');
//$reg = new NameSpaceBehaviorRegistry();
//require_once('config/behavior.php');
				
				require_once('classes/fs_parser/qp_workflow.php');
				require_once('classes/array_merge_recursive_distinct.php');
				//require_once('classes/class_compute_internal_statements.php');
				require_once('classes/exceptions/not_a_fieldname_exception.php');
				require_once('classes/exceptions/not_existing_branch_exception.php');
				require_once('classes/exceptions/empty_tree_exception.php');
				require_once('classes/exceptions/source_not_found_exception.php');
				require_once('classes/exceptions/no_permission_exception.php');
				require_once('classes/exceptions/Not_defined_Namespace_exception.php');
				//require_once('classes/exceptions/program_block_exception.php');
				require_once('classes/exceptions/wrong_class_exception.php');
				require_once('classes/class_REST.php');
                                include('classes/class_Contentgenerator.php');

                                

                Logger::$active = $ini_array["log"]["active"];
                Logger::$logPath = $ini_array["log"]["path"]; //"template/log.txt";
                /* Obergrenze fuer das Level eines Zuhoerers - leer heisst: wie level. */
                $_listen_max = trim((string) ($ini_array["log"]["listen_max"] ?? ''));
                Logger::$listenMax = intval($_listen_max !== '' ? $_listen_max : ($ini_array["log"]["level"] ?? 5));
                /* Log nur, wenn es jemand will (STW): steht __give_log VORN im Rumpf, wird
                *  ab hier gesammelt - vor der Kopfzeile, damit auch die fruehen Zeilen
                *  ankommen. Sonst, bei [log] active aus, entsteht gar kein Eintrag.
                *  php://input ist mehrfach lesbar; der Intern-Zweig liest es unten wieder. */
                $_rumpf = json_decode((string) file_get_contents('php://input'), true);
                if(is_array($_rumpf) && array_is_list($_rumpf)) $_rumpf = $_rumpf[0] ?? null;
                Logger::$collect = is_array($_rumpf) && (($_rumpf['Command']['Name'] ?? null) === '__give_log');
                
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
				if($_REQUEST['i'] == '__intern')
				{
					$_SESSION['@_mod'] = 'intern';
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
                              
               $collect_data_for_log = "All Sessiondata:\n ";
               foreach( $_SESSION as $key => $value ) {
				     $collect_data_for_log .= "SESSION[$key]=$value\n ";
				}                 
               
                $logger_class->setAssert($collect_data_for_log, 0) ;

				//WTF Concept horrible (and 15 years old XD) 
				//TODO 
                                $content = new ContentGenerator(
                                	$ini_array["database"]["URL"],
                                	$ini_array["database"]["User"],
                                	$ini_array["database"]["PWST"],
                                	$ini_array["database"]["db_name"],
                                	$ini_array["database"]["codeset"]
                                	);
                                //load node behavior
                                foreach (glob(__DIR__ . "/behavior/*.php") as $file) {
                                	require_once $file;
                                	}
                                	
                               $content->getSQLObj()->db_profiles($ini_array["database"]["ext"]);
                               
                               $content->set_Schema(XML_SCHEMA_DEFAULT);
                                
                                
				                 //SearchingModelObject::$treeRef = $content;
                                 /* Die Gegenstellen zuerst: ein Suchmodell nennt nur den
                                 *  Profilnamen, aufgeloest wird er ueber ConnectionProfile. */
                                 ConnectionProfile::set_collection($ini_array["connection"] ?? []);
                                 SearchingModelObject::set_config($ini_array["search"] ?? []);
				
                                
                if($_REQUEST['i'] == '__system')
				{
					
					$filteredRequest = array_diff_key($_REQUEST, array_flip(['modus']));

					// Zu einem String im Format "key1=value1, key2=value2" zusammenbauen
					$debugString = implode(', ', array_map(
						function ($v, $k) { return sprintf("%s=%s", $k, $v); },
						$filteredRequest,
						array_keys($filteredRequest)
						));
					
					$logger_class->setAssert("System Request with modus:" . $_REQUEST['modus'] . " arguments;" . $debugString, 0) ;
					
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
						htmlspecialchars((is_null($_REQUEST['CODE'])? '': $_REQUEST['CODE']) ), 
						htmlspecialchars((is_null($_REQUEST['URL'])? '': $_REQUEST['URL'])));

					
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
						/* qportal.sql, nicht surface.sql: surface ist lange abgeloest (Anttree), und die
						*  Datei gab es nicht mehr - file() lieferte false, implode('', false) ist unter
						*  PHP 8 ein TypeError, und genau daran starb das Install. */
						$load = implode('', file ('qportal.sql'));
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
				/* Achsenfolge kommt jetzt aus QUERY_PARAM, gefuellt in generate() */
                                
                                //$content->setXMLTemplate('template/text1.htm');
                $_intern_call  = false;
                $_intern_token = null;
				$_auth_header  = $_SERVER['HTTP_AUTHORIZATION'] ?? apache_request_headers()['Authorization'] ?? '';
				if (preg_match('/^Bearer\s+(.+)$/i', $_auth_header, $_m)) $_intern_token = $_m[1];
				$_intern_keys  = intern_key_list(INTERN_KEYS);
				$_has_session  = isset($_SESSION['@_mod']) && $_SESSION['@_mod'] === 'intern';

				/* Der passende Schluessel, nicht nur "irgendeiner stimmt": an ihm
				*  haengen Stufe und Sektoren. hash_equals, damit die Laufzeit des
				*  Vergleichs nichts ueber den Treffer verraet. */
				$_intern_entry = null;

				foreach($_intern_keys as $_k)
					if(!empty($_intern_token) && hash_equals($_k['token'], $_intern_token))
						{ $_intern_entry = $_k; break; }

				$_token_valid  = !is_null($_intern_entry);

				/* Ohne Schluessel ist der Endpunkt offen (Stufe 0). Steht einer, ist
				*  nichts mehr anonym - es sei denn, anonymous = 1 laesst den Weg
				*  ausdruecklich offen, fuer Dienste und Sensoren ohne Schluesselbund. */
				$_intern_anon  = empty($_intern_keys)
				              || (defined('INTERN_ANONYMOUS') && 1 === intval(INTERN_ANONYMOUS));

                if (!empty($_intern_token) && !$_token_valid) {
                    http_response_code(401);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Unauthorized']);
                    exit;
                }

                if ($_token_valid || ($_has_session && $_intern_anon))
                {
				/* Die Stufe kommt vom Schluessel. Ohne Schluessel bleibt es bei der
				*  Vorgabe des ContentGenerators - der Sitzungsklasse, sonst 0. */
				if($_token_valid)
				{
					$content->setClearance($_intern_entry['level']);

					if('' !== $_intern_entry['sector'])
						$content->setSectors($_intern_entry['sector']);
				}

				$_intern_call = true;
				$content->setXMLstructur(INTERN);
				// {"Identifire":"*","Command":{"Name":"__find_node","Attribute":{"json":"{\"name\":\"http:\/\/www.trscript.de\/tree#final\"}"},"Value":{"Identifire":"*","Command":{"Name":"start"},"Attribute":{"i":""}}}}
				$content->commandLineInjection(file_get_contents('php://input'));
				}
                                elseif(isset($_SESSION['@_mod']) && $_SESSION['@_mod']=='edit')
				$content->setXMLstructur(EDIT_INDEX);
                                else
                                {
				$content->setXMLstructur(FRONTEND_INDEX);
				}
				
				$content->setboolPanel(true);
                                $content->setControlElement("div",  array('id'=>"bars"));
                                if(is_Null($tmp = $_REQUEST['i']))$tmp = "";
                                $content->setTreeNodeName($tmp);
                                
                                $mtime = hrtime(true);
                                
                                            try {
         
                if(!$content->generate())
				{
						// Niemand hat gerendert - die Seite gibt es nicht. Das muss auch
						// im Statuscode stehen, sonst meldet der Server einen Erfolg.
						if (headers_sent($hdr_file, $hdr_line))
							error_log("404 konnte nicht gesetzt werden, Ausgabe lief schon ab $hdr_file:$hdr_line");
						else
							http_response_code(404);

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

						/* Ein Intern-Aufruf wird nicht umgeleitet: er bekommt die
						*  Abweisung als Antwort, in derselben Form wie das 401 oben. */
						if($_intern_call)
						{
							http_response_code(403);
							header('Content-Type: application/json');
							echo json_encode(['error' => 'Forbidden', 'note' => $e->getMessage()]);
							return false;
						}

							

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

				
					//echo "	computed time " . hrtime(true) -$mtime  . "\n";	
						
                                //print($content->getoutput(SEND_HEADER));
                                 //echo "	Complete  time " . hrtime(true) -$mtime  . "\n";
				//print($content->getSystemDocument(SEND_HEADER,'ISO-8859-1'));
				print($content->getoutput(SEND_HEADER));
				
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
				
				
				
				/*
				$boobibooh = '';
				foreach( $_SESSION as $key => $value ) {
				     $boobibooh .= $key . "=(" . $_SESSION[$key] . "); " ;
				} 
				
				$logger_class->setAssert("Ends with Sessiondata:\n ", 0) ;
				foreach( $_SESSION as $key => $value ) {
				     $logger_class->setAssert("SESSION[$key]=$value ", 0) ;
				} 
				*/

                        
?>
