<?PHP

/**
* service_create_account
* ----------------------------------------------------
* @param engine : Object to generate
* @param URL : spezific URL to load
*/

function service_create_account( &$system , $user , $keyword , $forename , $surname, $url, $altUrl)
{

//extract db and xmlobject
$db = $system->getSQLObj();
$xml = $system->getXMLObj();


//$xml->child_node(1);
//$xml->child_node(1);
//echo $xml->show_cur_data(0) . ' ';

//echo $xml->save_stream();
//convert to MD5 Hash	
$decode_key = md5($keyword);

//detects redundant entries and blocks on invalid name pass combinations
$db->SQL('SELECT `ID`  FROM `tbl_user_management` WHERE (`User` = "' . $user . '" AND `Key` = "' . $decode_key . '" ) ;');

if(is_Null($tmp = $altUrl))$tmp = "";

if($db->sEffectNum() == 0)
{ 




/*
SELECT * FROM (`tbl_user_management` LEFT JOIN `tbl_user_to_group` ON `tbl_user_management`.id=`tbl_user_to_group`.user_id ) LEFT JOIN `tbl_group_management` ON `tbl_user_to_group`.group_id=`tbl_group_management`.id ;
*/


//writes userdata to database
	$entry = 'INSERT INTO `tbl_user_management` (
`ID` ,
`User` ,
`Key` ,
`forename` ,
`surname`,
`securityclass`
)
VALUES (
NULL , \'' . $user . '\', \'' . $decode_key . '\', \'' . $forename . '\', \'' . $surname . '\', 1
);';

$db->SQL($entry);

//fetchs id of the standard group
//$db->SQL('SELECT `ID` FROM `tbl_group_management` WHERE `groupname` = "stdUser" ;');
//$group_id = $db->sResult(0,'ID');

//fetchs userid
//$db->SQL('SELECT `ID` FROM `tbl_user_management` WHERE (`User` = "' . $user . '" AND `Key` = "' . $decode_key . '" ) ORDER BY `ID` DESC LIMIT 0 , 5 ;');


//$user_id = $db->sResult(0,'ID');


//saves user group combination
/*
$entry = 'INSERT INTO `tbl_user_to_group` (
`user_id` ,
`group_id` 
)
VALUES (
' . $user_id . ', ' . $group_id . '
);';
$db->SQL($entry);
   */                            
if(is_Null($tmp = $url))$tmp = "";
}

                                if($_SESSION['@_mod']=='edit')
				$system->setXMLstructur('template/edit.xml');
                                else
				$system->setXMLstructur('template/xml.xml');
				
				$system->setboolPanel(true);
                                $system->setControlElement("div",  array('id'=>"bars"));

                                $system->setTreeNodeName($tmp);
                                if(!$system->generate())
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
 
                                print($system->getoutput(SEND_HEADER,'ISO 8859-1'));
}

/**
* service_createCode
* ----------------------------------------------------
* @param engine : Object to generate
* @param URL : spezific URL to load
*/

function service_createCode(&$system, $groups, $slevel, $url)
{

	//INSERT newtable (user,age,os) SELECT tbl_group_management.ID,table1.age,table2.os FROM tbl_group_management  WHERE table1.user=table2.user;

//extract db and xmlobject
$db = $system->getSQLObj();
$xml = $system->getXMLObj();

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randstring = '';
    for ($i = 0; $i < 20; $i++) {
        $randstring = $characters[rand(0, strlen($characters))];
    }


	$entry = "INSERT INTO `tbl_marked_for_group` (
`code` ,
`groups` ,
`seclevel`
)
VALUES (
 $randstring, $groups , $slevel
);";

$_SESSION['http://www.auster-gmbh.de/surface#groupcode'] = $randstring;

$db->SQL($entry);


                               if($_SESSION['@_mod']=='edit')
				$system->setXMLstructur('template/edit.xml');
                                else
				$system->setXMLstructur('template/xml.xml');
				
				$system->setboolPanel(true);
                                $system->setControlElement("div",  array('id'=>"bars"));

                                $system->setTreeNodeName($tmp);
                                if(!$system->generate())
				{
					unset($_SESSION['http://www.auster-gmbh.de/surface#groupcode'] );
					       
						if (!($fp = fopen('./error/404.html', "r"))) {
                
							print("This page is not supported");
							return false;
						}


						while ($data = fread($fp, 4096)) {


							print($data);

						}
						
						return false;
						
					
					
				}
 
                                print($system->getoutput(SEND_HEADER,'ISO 8859-1'));

}

/**
* service_createCode
* ----------------------------------------------------
* @param engine : Object to generate
* @param URL : spezific URL to load
*/

function service_applyCode(&$system, $code, $url)
{
global $_SESSION;
	//INSERT newtable (user,age,os) SELECT tbl_group_management.ID,table1.age,table2.os FROM tbl_group_management  WHERE table1.user=table2.user;
	
//extract db and xmlobject
$db = $system->getSQLObj();
$xml = $system->getXMLObj();

if(!($id = $_SESSION['http://www.auster-gmbh.de/surface#id']))return false;


$db->SQL('SELECT tbl_marked_for_group.groups, tbl_marked_for_group.to_person FROM tbl_marked_for_group WHERE `code` = "' . $code . '" ;');

if(($max = $db->sEffectNum()) == 0)return false;


$groups_str = $db->sResult(0,'tbl_marked_for_group.groups');
$to_person = $db->sResult(0,'tbl_marked_for_group.to_person');
$groups = explode(';', $groups_str);


foreach ($groups as $group) {

	
	$db->SQL("INSERT INTO tbl_user_to_group (user_id, group_id) SELECT  $id,  tbl_group_management.ID FROM tbl_group_management WHERE groupname = '$group';");
	
}
 	
if($to_person)$db->SQL("INSERT INTO tbl_user_to_person (user_id, person_id) VALUES ($id, $to_person);"); 

$db->SQL('DELETE FROM tbl_marked_for_group WHERE `code` = "' . $code . '" ;');




                               if($_SESSION['@_mod']=='edit')
				$system->setXMLstructur('template/edit.xml');
                                else
				$system->setXMLstructur('template/xml.xml');
				
				$system->setboolPanel(true);
                                $system->setControlElement("div",  array('id'=>"bars"));

                                $system->setTreeNodeName($url);
                                if(!$system->generate())
				{
					unset($_SESSION['http://www.auster-gmbh.de/surface#groupcode'] );
					       
						if (!($fp = fopen('./error/404.html', "r"))) {
                
							print("This page is not supported");
							return false;
						}


						while ($data = fread($fp, 4096)) {


							print($data);

						}
						
						return false;
						
					
					
				}
 
                                print($system->getoutput(SEND_HEADER,'ISO 8859-1'));

}

/**
* service_log_in
* ----------------------------------------------------
* @param engine : Object to generate
* @param URL : spezific URL to load
*/

function service_log_in( &$system , $user , $key)
{
	                        
				global $_SESSION;
				global $_REQUEST;
                               
                                $unlocked = login($system, $user, $key);
                                

                                if($_SESSION['@_mod']=='edit')
				$system->setXMLstructur('template/edit.xml');
                                else
				$system->setXMLstructur('template/xml.xml');
				

                               if($unlocked && is_Null($tmp = $_REQUEST['URL']))$tmp = "";
                               if(!$unlocked && is_Null($tmp = $_REQUEST['URLalt']))$tmp = "";

                                $system->setTreeNodeName($tmp);
                                if(!$system->generate())
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
 
                                print($system->getoutput(SEND_HEADER,'ISO 8859-1'));
}

function service_log_out( &$system)
{
	                        
				global $_SESSION;
				global $_REQUEST;
                               
                                
                                
                                
                                
                                
                               logout($system, $user, $key);
                                

                                if($_SESSION['@_mod']=='edit')
				$system->setXMLstructur('template/edit.xml');
                                else
				$system->setXMLstructur('template/xml.xml');
				
				$system->setboolPanel(true);
                                $system->setControlElement("div",  array('id'=>"bars"));

                                $system->setTreeNodeName('');
                               if(is_Null($tmp = $_REQUEST['URL']))$tmp = "";
                               
                                $system->setTreeNodeName($tmp);
                                if(!$system->generate())
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
 
                                print($system->getoutput(SEND_HEADER,'ISO 8859-1'));
}

/**
* service_view_doc
* ----------------------------------------------------
* @param engine : Object to generate
* @param URL : spezific URL to load
*/

function service_view_doc( &$system , $URL )
{
	//echo 'hallo ' . $URL;
	//extract db and xmlobject
$db = $system->getSQLObj();
$xml = $system->getXMLObj();

	$xml->setNewTree('http://www.auster-gmbh.de/system');
	$xml->set_definition_context('TYPE','XML');
	$xml->set_definition_context('MIME','<?xml version="1.0" encoding="UTF-8"?>');
	$xml->set_definition_context('DOC','');
	
		$namespace = array();
		$namespace['xmlns'] = 'http://www.auster-gmbh.de/system';
		$namespace['xmlns:owl'] = 'http://www.w3.org/2002/07/owl';
		$namespace['xmlns:rdf'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';
		$namespace['xmlns:rdfs'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:xsd'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:pedl'] = 'http://www.w3.org/2006/05/pedl-lib';
		$namespace['xmlns:ate'] = 'http://www.auster-gmbh.de/2010/08/anttree-lib';
		
		//echo get_Class($xml);
	
		
		$xml->createTree('http://www.tr-script.de/regsys','rdf:RDF', $namespace);
		
				$xml->set_first_node();
		$stamp = $xml->position_stamp();
		
		$tmpstamp = $stamp;
		
		$xml->use_ns_def_strict(true);
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system');
		$xml->tag_open($this, "owl:Ontology", $attrib);
		$xml->tag_close($this, "owl:Ontology");
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system#SystemUser');
		$xml->tag_open($this, "rdfs:Class", $attrib);
				
				$attrib = array('rdf:resource' => 'http://www.w3.org/2000/01/rdf-schema#Class');
				$xml->tag_open($this, "rdfs:subClassOf", $attrib);
				$xml->tag_close($this, "rdfs:subClassOf");
				


		$xml->tag_close($this, "rdfs:Class");

		$xml->use_ns_def_strict(false);
		
		print($xml->save_Stream('UTF-8',true));
}

/**
* service_call_ontology_tagstructur
* ----------------------------------------------------
* @param engine : Object to generate
* @param URL : spezific URL to load
*/
function  service_call_ontology_tagstructur( &$system , $URI )
{
$db = $system->getSQLObj();
$xml = $system->getXMLObj();

	$xml->setNewTree('http://www.auster-gmbh.de/system');
	$xml->set_definition_context('TYPE','XML');
	$xml->set_definition_context('MIME','<?xml version="1.0" encoding="UTF-8"?>');
	$xml->set_definition_context('DOC','');
	
		$namespace = array();
		$namespace['xmlns'] = 'http://www.auster-gmbh.de/system';
		$namespace['xmlns:owl'] = 'http://www.w3.org/2002/07/owl';
		$namespace['xmlns:rdf'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';
		$namespace['xmlns:rdfs'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:xsd'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:pedl'] = 'http://www.w3.org/2006/05/pedl-lib';
		
		//echo get_Class($xml);
	
		
		$xml->createTree('http://www.tr-script.de/regsys','rdf:RDF', $namespace);
		
				$xml->set_first_node();
		$stamp = $xml->position_stamp();
		
		$tmpstamp = $stamp;
		
		$xml->use_ns_def_strict(true);
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system');
		$xml->tag_open($this, "owl:Ontology", $attrib);
		$xml->tag_close($this, "owl:Ontology");
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system#SystemUser');
		$xml->tag_open($this, "rdfs:Class", $attrib);
				
				$attrib = array('rdf:resource' => 'http://www.w3.org/2000/01/rdf-schema#Class');
				$xml->tag_open($this, "rdfs:subClassOf", $attrib);
				$xml->tag_close($this, "rdfs:subClassOf");
				
		$xml->tag_close($this, "rdfs:Class");

		$xml->use_ns_def_strict(false);
		
		print($xml->save_Stream('UTF-8',true));
}


/**
* service_call_ontology
* ----------------------------------------------------
* @param engine : Object to generate
* @param URL : spezific URL to load
* Verwendung nicht gesichert
*/

function service_call_ontology( &$system , $URI )
{
$db = $system->getSQLObj();
$xml = $system->getXMLObj();

$query = "SELECT * FROM tbl_surface_doc_overview WHERE tbl_surface_doc_overview.txt_doc_URI = '" . $URI . "';";

$rst = $db->get_rst($query);

$rst->first_ds();

if($rst->rst_num() > 0)
{

	if(is_file( $rst->value('tbl_surface_doc_overview.txt_doc_URL'))) 
	{

				$system->setXMLstructur($rst->value('tbl_surface_doc_overview.txt_doc_URL'));
				
				$system->setboolPanel(false);

                if(is_Null($tmp = $_REQUEST['r']))$tmp = "";
                                
                                $system->setTreeNodeName($tmp);
                                if(!$system->generate())
                                {
					       
						if (!($fp = fopen('./error/404.html', "r"))) {
                
							print("This page is not supported");
							return false;
						}


						while ($data = fread($fp, 4096)) 
						{


							print($data);

						}
						return false;
						
					
					
				}
 				
 				$xmlobj = $system->getXMLObj();

 				$xmlobj->set_special('modus', 'trace');

                print($system->getoutput(SEND_HEADER,'UTF-8')); //ISO 8859-1

	//$xml->load( $rst->value('tbl_surface_doc_overview.txt_doc_URL'),0);
		//	$xml->use_ns_def_strict(false);
		
	//print($xml->save_Stream('UTF-8',true));
	}
	else
	{
	echo $rst->value('tbl_surface_doc_overview.txt_doc_URL') . " gib it nich";
	}
}
//echo $query . " " . $rst->value('tbl_surface_doc_overview.txt_doc_URL') . " " . $rst->rst_num();

/*

	$xml->setNewTree('http://www.auster-gmbh.de/system');
	$xml->set_definition_context('TYPE','XML');
	$xml->set_definition_context('MIME','<?xml version="1.0" encoding="UTF-8"?>');
	$xml->set_definition_context('DOC','');
	
		$namespace = array();
		$namespace['xmlns'] = 'http://www.auster-gmbh.de/system';
		$namespace['xmlns:owl'] = 'http://www.w3.org/2002/07/owl';
		$namespace['xmlns:rdf'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';
		$namespace['xmlns:rdfs'] = 'http://www.w3.org/2000/01/rdf-schema';
		//$namespace['xmlns:xsd'] = 'http://www.w3.org/2000/01/rdf-schema';
		//$namespace['xmlns:pedl'] = 'http://www.w3.org/2006/05/pedl-lib';
		$namespace['xmlns:ate'] = 'http://www.auster-gmbh.de/2010/08/anttree-lib';
		$namespace['xmlns:svg'] = 'http://www.w3.org/2000/svg';
		$namespace['xmlns:xlink'] = 'http://www.w3.org/1999/xlink';
		
		//echo get_Class($xml);
	
		
		$xml->createTree('http://www.tr-script.de/regsys','rdf:RDF', $namespace);
		
				$xml->set_first_node();
		$stamp = $xml->position_stamp();
		
		$tmpstamp = $stamp;
		
		$xml->use_ns_def_strict(true);
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system');
		$xml->tag_open($this, "owl:Ontology", $attrib);
		
			$attrib = array();
			$xml->tag_open($this, "rdfs:label", $attrib);
			$name = 'System';
			$xml->cdata_ref($this,$name);
			$xml->tag_close($this, "rdfs:label");
			

			$attrib = array( 'rdf:resource' => 'http://www.auster-gmbh.de/2010/08/anttree-lib' );
			$xml->tag_open($this, "owl:imports", $attrib);
			$xml->tag_close($this, "owl:imports");

			//http://www.auster-gmbh.de/2010/08/anttree-lib
		
		$xml->tag_close($this, "owl:Ontology");
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system#SystemUser');
		$xml->tag_open($this, "rdfs:Class", $attrib);
				
			$attrib = array();
			$xml->tag_open($this, "rdfs:label", $attrib);
			$name = 'SystemUser';
			$xml->cdata_ref($this,$name);
			$xml->tag_close($this, "rdfs:label");
				
			$attrib = array('rdf:resource' => 'http://www.w3.org/2000/01/rdf-schema#Class');
			$xml->tag_open($this, "rdfs:subClassOf", $attrib);
			$xml->tag_close($this, "rdfs:subClassOf");
			
			$attrib = array();
			$xml->tag_open($this, "ate:isDisplayed2DImage", $attrib);
					
				$attrib = array('svg:width'=>'20','svg:height'=>'20','xlink:href'=>'img/user.png');					
				$xml->tag_open($this, "svg:image", $attrib);
				$xml->tag_close($this, "svg:image");

			$xml->tag_close($this, "ate:isDisplayed2DImage");
				
		$xml->tag_close($this, "rdfs:Class");
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system#SystemGroup');
		$xml->tag_open($this, "rdfs:Class", $attrib);
				
				$attrib = array();
				$xml->tag_open($this, "rdfs:label", $attrib);
				$name = 'SystemGroup';
				$xml->cdata_ref($this,$name);
				$xml->tag_close($this, "rdfs:label");
				
				$attrib = array('rdf:resource' => 'http://www.w3.org/2000/01/rdf-schema#Class');
				$xml->tag_open($this, "rdfs:subClassOf", $attrib);
				$xml->tag_close($this, "rdfs:subClassOf");
				
				$attrib = array();
				$xml->tag_open($this, "ate:isDisplayed2DImage", $attrib);
					
				$attrib = array('svg:width'=>'20','svg:height'=>'20','xlink:href'=>'img/group.png');					
				$xml->tag_open($this, "svg:image", $attrib);
				$xml->tag_close($this, "svg:image");

			$xml->tag_close($this, "ate:isDisplayed2DImage");
				
				
		$xml->tag_close($this, "rdfs:Class");
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system#Surname');
		$xml->tag_open($this, "rdfs:Literal", $attrib);
				
				$attrib = array();
				$xml->tag_open($this, "rdfs:label", $attrib);
				$name = 'Surname';
				$xml->cdata_ref($this,$name);
				$xml->tag_close($this, "rdfs:label");
				
				
		$xml->tag_close($this, "rdfs:Literal");
		
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system#Forename');
		$xml->tag_open($this, "rdfs:Literal", $attrib);
				
				$attrib = array();
				$xml->tag_open($this, "rdfs:label", $attrib);
				$name = 'Forename';
				$xml->cdata_ref($this,$name);
				$xml->tag_close($this, "rdfs:label");
				
				
		$xml->tag_close($this, "rdfs:Literal");
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system#hasSurname');
		$xml->tag_open($this, "rdf:Property", $attrib);
					$attrib = array();
			$xml->tag_open($this, "rdfs:label", $attrib);
			$name = 'hasSurname';
			$xml->cdata_ref($this,$name);
			$xml->tag_close($this, "rdfs:label");
				
			$attrib = array('rdf:resource' => 'http://www.auster-gmbh.de/system#SystemUser');
			$xml->tag_open($this, "rdfs:domain", $attrib);
			$xml->tag_close($this, "rdfs:domain");

			$attrib = array('rdf:resource' => 'http://www.auster-gmbh.de/system#Surname');
			$xml->tag_open($this, "rdfs:range", $attrib);
			$xml->tag_close($this, "rdfs:range");
			
			$attrib = array('rdf:resource' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property');
			$xml->tag_open($this, "rdfs:subPropertyOf", $attrib);
			$xml->tag_close($this, "rdfs:subPropertyOf");
			
		$xml->tag_close($this, "rdf:Property");
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system#hasForename');
		$xml->tag_open($this, "rdf:Property", $attrib);
					$attrib = array();
			$xml->tag_open($this, "rdfs:label", $attrib);
			$name = 'hasForename';
			$xml->cdata_ref($this,$name);
			$xml->tag_close($this, "rdfs:label");
				
			$attrib = array('rdf:resource' => 'http://www.auster-gmbh.de/system#SystemUser');
			$xml->tag_open($this, "rdfs:domain", $attrib);
			$xml->tag_close($this, "rdfs:domain");

			$attrib = array('rdf:resource' => 'http://www.auster-gmbh.de/system#Forename');
			$xml->tag_open($this, "rdfs:range", $attrib);
			$xml->tag_close($this, "rdfs:range");
			
			$attrib = array('rdf:resource' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property');
			$xml->tag_open($this, "rdfs:subPropertyOf", $attrib);
			$xml->tag_close($this, "rdfs:subPropertyOf");
			
		$xml->tag_close($this, "rdf:Property");
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system#SystemUser_' . 
		$_SESSION['http://www.auster-gmbh.de/surface#user']);
		$xml->tag_open($this, "SystemGroup", $attrib);
				
				$attrib = array();
				$xml->tag_open($this, "rdfs:label", $attrib);
				$name = $_SESSION['http://www.auster-gmbh.de/surface#user'];
				$xml->cdata_ref($this,$name);
				$xml->tag_close($this, "rdfs:label");
				
				
		$xml->tag_close($this, "SystemGroup");
*/		
		/*
		$attrib = array('rdf:ID' => 'PhpClass');
		$xml->tag_open($this, "pedl:Object_Class", $attrib);
		$xml->tag_close($this, "pedl:Object_Class");
		
		$attrib = array('rdf:ID' => 'PhpMethod');
		$xml->tag_open($this, "pedl:Object_Funktion", $attrib);
		$xml->tag_close($this, "pedl:Object_Funktion");
		
		$attrib = array('rdf:ID' => 'PhpConstructor');
		$xml->tag_open($this, "pedl:Object_Constructor", $attrib);
		$xml->tag_close($this, "pedl:Object_Constructor");
		
		$attrib = array('rdf:ID' => 'PhpParameter');
		$xml->tag_open($this, "pedl:Object_Parameter", $attrib);
		$xml->tag_close($this, "pedl:Object_Parameter");
		
		$attrib = array('rdf:ID' => 'System');
		$xml->tag_open($this, "PhpClass", $attrib);
		$xml->tag_close($this, "PhpClass");
		
		$attrib = array('rdf:ID' => 'Variable');
		$xml->tag_open($this, "PhpParameter", $attrib);
		$xml->tag_close($this, "PhpParameter");
		
		$attrib = array('rdf:ID' => 'System.Parser');
		$xml->tag_open($this, "Variable", $attrib);
		$xml->tag_close($this, "Variable");
		
		$attrib = array('rdf:ID' => 'System.FuncTree');
		$xml->tag_open($this, "Variable", $attrib);
		$xml->tag_close($this, "Variable");
		
		$attrib = array('rdf:ID' => 'System.Content');
		$xml->tag_open($this, "Variable", $attrib);
		$xml->tag_close($this, "Variable");
		
		$attrib = array('rdf:ID' => 'System.CurRef');
		$xml->tag_open($this, "Variable", $attrib);
		$xml->tag_close($this, "Variable");
		
		$attrib = null;
		$xml->tag_open($this, "System", $attrib);
				
				$xml->tag_open($this, "System.Parser", $attrib);
				$xml->cdata_ref($this,$xml);
				$xml->tag_close($this, "System.Parser");
				
				$xml->tag_open($this, "System.FuncTree", $attrib);
				$xml->cdata_ref($this,$this);
				$xml->tag_close($this, "System.FuncTree");
				
				$xml->tag_open($this, "System.Content", $attrib);
				$xml->cdata_ref($this,$system);
				$xml->tag_close($this, "System.Content");
				
				$xml->tag_open($this, "System.CurRef", $attrib);
				$xml->cdata($this,null);
				$xml->tag_close($this, "System.CurRef");
		$xml->tag_close($this, "PhpParameter");
		
		$attrib = array('rdf:ID' => 'Class_Collection');
		$xml->tag_open($this, "owl:Class", $attrib);
		$xml->tag_close($this, "owl:Class");
		
		$attrib = array('rdf:ID' => 'Class_Instance');
		$xml->tag_open($this, "owl:Class", $attrib);
		$xml->tag_close($this, "owl:Class");
		
		
		//$xml->create_Ns_Node("owl:Class");
		//$xml->set_node_attrib('rdf:ID','Class_Collection');
		//$xml->parent_node();
		
		$attrib = array('rdf:about' => '#has_method');
		$xml->tag_open($this, "rdf:Property", $attrib);
		
			$attrib = array('rdf:resource' => '#PhpClass');
			$xml->tag_open($this, "rdfs:domain", $attrib);
			$xml->tag_close($this, "rdfs:domain");

			$attrib = array('rdf:resource' => '#PhpMethod');
			$xml->tag_open($this, "rdfs:range", $attrib);
			$xml->tag_close($this, "rdfs:range");
			
		$xml->tag_close($this, "rdf:Property");
		
		$attrib = array('rdf:about' => '#has_constructor');
		$xml->tag_open($this, "rdf:Property", $attrib);
		
			$attrib = array('rdf:resource' => '#PhpClass');
			$xml->tag_open($this, "rdfs:domain", $attrib);
			$xml->tag_close($this, "rdfs:domain");

			$attrib = array('rdf:resource' => '#PhpConstructor');
			$xml->tag_open($this, "rdfs:range", $attrib);
			$xml->tag_close($this, "rdfs:range");
			
		$xml->tag_close($this, "rdf:Property");

		$attrib = array('rdf:about' => '#has_parameter');
		$xml->tag_open($this, "rdf:Property", $attrib);
		
			$attrib = array('rdf:resource' => '#PhpMethod');
			$xml->tag_open($this, "rdfs:domain", $attrib);
			$xml->tag_close($this, "rdfs:domain");

			$attrib = array('rdf:resource' => '#PhpParameter');
			$xml->tag_open($this, "rdfs:range", $attrib);
			$xml->tag_close($this, "rdfs:range");
			
		$xml->tag_close($this, "rdf:Property");
		
		
		$attrib = null;
		$xml->tag_open($this, "Class_Collection", $attrib);
		
			$xml->tag_open($this, "rdf:Bag", $attrib);
			$xml->tag_close($this, "rdf:Bag");

		$xml->tag_close($this, "Class_Collection");
		
		$xml->tag_open($this, "Class_Instance", $attrib);
		
			$xml->tag_open($this, "rdf:Bag", $attrib);
			$xml->tag_close($this, "rdf:Bag");

		$xml->tag_close($this, "Class_Instance");
		*/
	//	$xml->use_ns_def_strict(false);
		
	//	print($xml->save_Stream('UTF-8',true));
}

function service_create_node_ontology( &$system , $content )
{

//echo "$URI, $stamp, $new_node, $label, $comment";

$db = $system->getSQLObj();
$xml = $system->getXMLObj();

$csv = new xml_ns();
$csv->load_Stream($content,0,"CSV");

$input = array(9);
$uri = array();
$uri[] = 'http://www.csv.de/csv#command';
$uri[] = 'http://www.csv.de/csv#id';
$uri[] = 'http://www.csv.de/csv#ns';
$uri[] = 'http://www.csv.de/csv#URI';
$uri[] = 'http://www.csv.de/csv#goto';
$uri[] = 'http://www.csv.de/csv#attib';
$uri[] = 'http://www.csv.de/csv#stamp';
$uri[] = 'http://www.csv.de/csv#newstamp';
$uri[] = 'http://www.csv.de/csv#success';
$res;
$point = array(10);
$point[0] = 0;

$list_of_subnodes = array();
$catched = array();

for($i = 0;$i < 9; $i++)
if(!$csv->seek_node($uri[$i]))
echo "Ungueltige CSV Abfrage, " . $uri[$i] . " ist nicht vorhanden.";
else
$point[$i + 1] = count($csv->get_result());

$res = &$csv->get_result();
$iter = 0;
for($i = 0;$i < 9; $i++)
{
  $iter = 0;
  $input[$i] = array(); 
  for($j = $point[$i];$j < $point[$i + 1]; $j++)
  {
	$input[$i][$iter++] = &$res[$j];
	if($i == 4 )
	{
	$int = intval($res[$j]->getdata(0));
	  if(!isset($list_of_subnodes[$int]))
	  {
	    $list_of_root_nodes[$int] = array();
	    $list_of_root_nodes[$int][] = $iter - 1;
	  }
	  else
	    $list_of_root_nodes[$int][] = $iter - 1;  
	}
  }
}

level_tasklist($list_of_root_nodes,$input,$catched,0, $system , $content );


//$input[0] = &$csv->get_result();
//$point[0] = 
//$csv->flash_result();
//echo $res[0]->getdata(0);

//if($csv->seek_node()){$input[1] = &$csv->get_result();$csv->flash_result();}
//if($csv->seek_node()){$input[2] = &$csv->get_result();$csv->flash_result();}
//if($csv->seek_node()){$input[3] = &$csv->get_result();$csv->flash_result();}
//if($csv->seek_node()){$input[4] = &$csv->get_result();$csv->flash_result();}
//if($csv->seek_node()){$input[5] = &$csv->get_result();$csv->flash_result();}
//if($csv->seek_node()){$input[6] = &$csv->get_result();$csv->flash_result();}
//if($csv->seek_node()){$input[7] = &$csv->get_result();$csv->flash_result();}
//if($csv->seek_node()){$input[8] = &$csv->get_result();$csv->flash_result();}

//echo count($input[6]);
$csv->flash_result();
echo $csv->save_stream();

$j = 0;
do{
$xml->save_file();
}
while($xml->change_idx($j++));
//$csv->save_stream();
//load($this->getdata(),1,$doc_type);
/*
$query = "SELECT * FROM tbl_surface_doc_overview WHERE tbl_surface_doc_overview.txt_doc_URI = '" . $URI . "';";

$rst = $db->get_rst($query);

$rst->first_ds();

if($rst->rst_num() > 0)
{
	
	if(is_file( $rst->value('tbl_surface_doc_overview.txt_doc_URL'))) 
	{

				$system->setXMLstructur($rst->value('tbl_surface_doc_overview.txt_doc_URL'));
				
				$system->setboolPanel(false);
                                
                                if(is_Null($tmp = $_REQUEST['r']))$tmp = "";
                                
                                $system->setTreeNodeName($tmp);
                                if(!$system->generate())
				{
					       
						if (!($fp = fopen('./error/404.html', "r"))) {
                
							print("This page is not supported");
							return false;
						}


						while ($data = fread($fp, 4096)) 
						{


							print($data);

						}
						return false;
						
					
					
				}
 				
 				
 				if($xml->go_to_stamp($stamp))
					$xml->create_Ns_Node($new_node); //
					$hash = $xml->position_hash_pos();
				//echo 
 				//$xmlobj->set_special('modus', 'trace');
 				echo "ns;tag;attrib;onpos;stamp;success\n";
				echo $URI . ';' . $new_node . ';null;' . $stamp . ';' . $hash . ';';                               
                               if($system->saveoutput(SEND_HEADER,'ISO 8859-1'))
                               //$this->XMLlist->save_file($out,$set_header); -->bool
                                echo 'true';
                               else
			        echo 'false';

}
}
*/
 				//echo $content;
}

/**
* helpfunc for service_create_node_ontology
* @param &$levellist : 2D array of indexes in table, sorted by goto value
* @param &$table : 2D array of csv file (not assoc)
* @param $curpos : single int value for current parent-node 
*/
function level_tasklist(&$levellist,&$table,&$catched,$curpos,  &$system , $content )
{

  $csv_param = nul;
  
  
  $xml = $system->getXMLObj();
	
  if(isset($levellist[$curpos]) && !in_array($curpos, $catched))
  {
  $catched[] = $curpos;
  $stored = false;
  $pos = 0;
  $false = 'false';

  	for($i = 0; $i < count($levellist[$curpos]); $i++)
  	{
    	$id = intval($table[1][$levellist[$curpos][$i]]->getdata(0));
    
    	
    //TODO Fehlerbehandlung, DAtei nicht gefunden
    //if(!is_object($table[1][$levellist[$curpos][$i]]) )echo "ist kein objekt!\n";
    //if(!isset($table[1][$levellist[$curpos][$i]]) )echo "ist nicht gesetzt!\n";
    	//echo "$curpos -> id= '$id' auf index(" . $levellist[$curpos][$i] . ") \n";
    	if($pos = find_and_load_uri(
    		$system , 
    		$table[2][$levellist[$curpos][$i]]->getdata(0) ))
    	{
    	$stamp = $table[6][$levellist[$curpos][$i]]->getdata(0);
    	$node = $table[3][$levellist[$curpos][$i]]->getdata(0); 

	  /**
	  *  creates a list of attributes for a new node
	  *
	  *
	  */
	  $csv_param = new xml_ns();
	  $csv_param->load_Stream($table[5][$levellist[$curpos][$i]]->getdata(0) ,0,"CSV;line_end='~'");
	  $csv_param->set_first_node();
	  
	  $attrib = array();
	  $data = array();
	  
	  for($k = 0;$k < $csv_param->index_child();$k++)
	  {
	  $csv_param->child_node($k);
	  
	  $val = array();
	  
	  	for($l = 0;$l < $csv_param->index_child();$l++)
	  	{
	  	$csv_param->child_node($l);
	  	
	  	switch ($csv_param->cur_node()) 
	  	{
		case 'uri':
	          $val[0] = $csv_param->show_cur_data(0);
	        break;
		case 'qName':
        	  $val[1] = $csv_param->show_cur_data(0);
        	break;
    		case 'value':
        	  $val[2] = $csv_param->show_cur_data(0);
        	break;
		}
		$csv_param->parent_node();
	  	}
	  	
	  if($val[0] == 'data')	
	  {
	  $data[intval($val[1])] = $val[2];
	  }
	  else
	  {
	  $attrib[$val[0] . '#' . $val[1]] = $val[2];
	  }

	  
	  $csv_param->parent_node();
	  }

	  /* end of part*/
	  
	  /**
	  *  if a stamp exists, than
	  *  replaces first number after hashcode
	  *  and goes to new stamp.
	  *  is needed for current treelist
	  */


    		if(strlen($stamp) > 1)
    		{
		$expl = explode( '.', $stamp );
		$expl[1] = strval($pos);
		$stamp = implode('.', $expl);
    		$xml->go_to_stamp($stamp);
		}
    	/* end of part*/

		if($xml->create_Ns_Node($node ,null ,$attrib) !== false)
		{
		$true = 'true';
		$table[8][$levellist[$curpos][$i]]->setdata($true,0);
		
		for($p = 0;$p < count($data); $p++)$xml->set_node_cdata($data[$p],$p);
		
		$hash = $xml->position_hash_pos();
		$table[7][$levellist[$curpos][$i]]->setdata($hash,0);
		}
		else
		{

		$table[8][$levellist[$curpos][$i]]->setdata($false,0);		
		}
		 
    	 }
    	 else
		$table[8][$levellist[$curpos][$i]]->setdata($false,0);    	    	
    	    
    	    
    	 level_tasklist($levellist,$table,$catched,$id,$system , $content );
  	}
  }


//intval($input[1][$iter - 1]->getdata(0))
}

/**
* helpfunc for service_create_node_ontology
* @param &$system : contains db and xml-obj
* @param $content : 2D array of csv file (not assoc)
* @param $curpos : single int value for current parent-node 
*/
function find_and_load_uri(&$system, $URI )
{
$db = $system->getSQLObj();
$xml = $system->getXMLObj();

$query = "SELECT * FROM tbl_surface_doc_overview WHERE tbl_surface_doc_overview.txt_doc_URI = '" . $URI . "';";

$rst = $db->get_rst($query);

$rst->first_ds();

if($rst->rst_num() > 0)

	
	if(is_file( $rst->value('tbl_surface_doc_overview.txt_doc_URL')))
	{ 	  
	//echo $xml->position_hash_pos() . ' ';
	
	return $xml->load($rst->value('tbl_surface_doc_overview.txt_doc_URL'),0,'XML'); 
	//$xml->set_first_node();
	
	}
	
return false;
}


function isLocked(&$system, $sector, $state)
{


}

function login(&$system, $user, $key)
{

global $_SESSION;
global $logger_class;

$decode_key = md5($key);

//extract db and xmlobject
$db = $system->getSQLObj();

//detects redundant entries and blocks on invalid name pass combinations
$db->SQL('SELECT 
	tbl_user_management.ID, 
	tbl_user_management.User,
	tbl_user_management.forename,
	tbl_user_management.surname,
	tbl_user_management.securityclass,
	tbl_group_management.groupname,
	tbl_group_management.groupdescription,
	tbl_group_management.sector
	FROM (`tbl_user_management` LEFT JOIN `tbl_user_to_group` ON `tbl_user_management`.id=`tbl_user_to_group`.user_id ) LEFT JOIN `tbl_group_management` ON `tbl_user_to_group`.group_id=`tbl_group_management`.id WHERE (`User` = "' . $user . '" AND `Key` = "' . $decode_key . '" ) ;');
if(($max = $db->sEffectNum()) == 0)return false;


$_SESSION['http://www.auster-gmbh.de/surface#id'] = $db->sResult(0,'tbl_user_management.ID');

$_SESSION['http://www.auster-gmbh.de/surface#user'] = $db->sResult(0,'tbl_user_management.User');

$_SESSION['http://www.auster-gmbh.de/surface#forename'] = $db->sResult(0,'tbl_user_management.forename');
$_SESSION['http://www.auster-gmbh.de/surface#surname'] = $db->sResult(0,'tbl_user_management.surname');
$_SESSION['http://www.auster-gmbh.de/surface#fullname'] = $db->sResult(0,'tbl_user_management.forename') . ' ' . $db->sResult(0,'tbl_user_management.surname');

$_SESSION['http://www.auster-gmbh.de/surface#securityclass'] = $db->sResult(0,'tbl_user_management.securityclass');
$_SESSION['http://www.auster-gmbh.de/surface#group'] = $db->sResult(0,'tbl_group_management.groupname');
$_SESSION['http://www.auster-gmbh.de/surface#groupdescription'] = $db->sResult(0,'tbl_group_management.groupdescription');
$_SESSION['http://www.auster-gmbh.de/surface#sector'] = ';' . $db->sResult(0,'tbl_group_management.sector') . ';';
for($i = 1; $max > $i ; $i++ )
{
$_SESSION['http://www.auster-gmbh.de/surface#group'] .= ';' . $db->sResult($i,'tbl_group_management.groupname');
$_SESSION['http://www.auster-gmbh.de/surface#groupdescription'] .= ';' . $db->sResult($i,'tbl_group_management.groupdescription');
$_SESSION['http://www.auster-gmbh.de/surface#sector'] .= $db->sResult($i,'tbl_group_management.sector') . ';';
}


                 	      $logger_class->setAssert("Set log in:\n ", 0) ;
				foreach( $_SESSION as $key => $value ) {
				     $logger_class->setAssert("SESSION[$key]=$value\n ", 0) ;
				} 

return true;
}

function logout()
{
unset($_SESSION['http://www.auster-gmbh.de/surface#id'] );
unset($_SESSION['http://www.auster-gmbh.de/surface#user']);
unset($_SESSION['http://www.auster-gmbh.de/surface#forename']);
unset($_SESSION['http://www.auster-gmbh.de/surface#surname']);
unset($_SESSION['http://www.auster-gmbh.de/surface#group']);
unset($_SESSION['http://www.auster-gmbh.de/surface#securityclass']);
unset($_SESSION['http://www.auster-gmbh.de/surface#groupdescription']);
unset($_SESSION['http://www.auster-gmbh.de/surface#sector']);
}

function boolString($bValue = false) {                      // returns string
  return ($bValue ? 'true' : 'false');
}



/**
* service_call_ontology_structure
* ----------------------------------------------------
* @param engine : Object to generate
*/
function service_call_ontology_structure( &$system , $URI )
{

$db = $system->getSQLObj();
$xml = $system->getXMLObj();


$tbl2 = $db->get_rst('SELECT id, txt_doc_name, txt_doc_URL, txt_doc_URI, txt_doc_label, txt_doc_comment FROM tbl_surface_doc_overview WHERE txt_doc_name = \'' . $URI . '\';');

$tbl2->first_ds();

$tbl1 = $db->get_rst('SELECT * FROM tbl_surface_doc_ref;');


for($i = 0;$i < 1;$i++)
{


}

$tbl1 = $db->get_rst('SELECT * FROM tbl_surface_doc_ref;');
$tbl2 = $db->get_rst('SELECT id, txt_doc_name, txt_doc_URL, txt_doc_URI, txt_doc_label, txt_doc_comment FROM tbl_surface_doc_overview;');


$tbl1->first_ds();

	$xml->setNewTree('http://www.auster-gmbh.de/system');
	$xml->set_definition_context('TYPE','XML');
	$xml->set_definition_context('MIME','<?xml version="1.0" encoding="UTF-8"?>');
	$xml->set_definition_context('DOC','');
	
		$namespace = array();
		$namespace['xmlns'] = 'http://www.auster-gmbh.de/system';
		$namespace['xmlns:owl'] = 'http://www.w3.org/2002/07/owl';
		$namespace['xmlns:rdf'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns';
		$namespace['xmlns:rdfs'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:xsd'] = 'http://www.w3.org/2000/01/rdf-schema';
		$namespace['xmlns:pedl'] = 'http://www.w3.org/2006/05/pedl-lib';
	
			$xml->createTree('http://www.tr-script.de/regsys','rdf:RDF', $namespace);
		
				$xml->set_first_node();
		$stamp = $xml->position_stamp();
		
		$tmpstamp = $stamp;
		
		$xml->use_ns_def_strict(true);
		
		$attrib = array('rdf:about' => 'http://www.auster-gmbh.de/system');
		$xml->tag_open($this, "owl:Ontology", $attrib);
		$xml->tag_close($this, "owl:Ontology");
		
		$attrib = array('rdf:about' => '#CSVlist');
		$xml->tag_open($this, "rdfs:Literal", $attrib);				
		$xml->tag_close($this, "rdfs:Literal");
		
		$attrib = array('rdf:about' => '#ontology_order');
		$xml->tag_open($this, "CSVlist", $attrib);				
		$xml->tag_close($this, "CSVlist");
		
		$attrib = array('rdf:about' => '#ontology_list');
		$xml->tag_open($this, "CSVlist", $attrib);				
		$xml->tag_close($this, "CSVlist");
		
		
		$xml->tag_open($this, "ontology_order", $attrib);	
		$xml->cdata($this,$tbl1->getCSV());			
		$xml->tag_close($this, "ontology_order");
		
		
		$xml->tag_open($this, "ontology_list", $attrib);	
		$xml->cdata($this,$tbl2->getCSV());			
		$xml->tag_close($this, "ontology_list");


while(!$tbl2->EOF())
{
	if($URI == $tbl2->value('tbl_surface_doc_overview.txt_doc_name'))
	{
	break;
	}
	$tbl2->next_ds();
}
	//$help = $tbl2->db_field_list();
	
	//for($i = 0;$i<count($help);$i++)
	//{
	//	echo $help[$i] . '(' . $tbl2->value($help[$i]) . '), '; 
	//}
	
	
	
	//echo $tbl2->value('tbl_surface_doc_overview.id') . '-' . $tbl2->value('tbl_surface_doc_overview.txt_doc_name');
	//$help = $tbl1->db_field_list();
	//for($i = 0;$i<count($help);$i++)
	//{
	//	echo $help[$i] . '(' . $tbl1->value($help[$i]) . '), '; 
	//}




		
		//echo get_Class($xml);
	
		


		$xml->use_ns_def_strict(false);
		
		print($xml->save_Stream('UTF-8',true));
				
}

function create_from_existing_Ontology()
{}

function create_system()
{}

function trim_with_null($element)
{
	if(is_null($element))
		return "";
	else
		return trim($element);
}

/**
used from 
https://stackoverflow.com/questions/29176716/php-extract-string-between-delimiters-allow-duplicates
TODO modify for more finds in array with missing torkens
*/
function getInnerSubstring($string,$start = null, $end = null){
	
	$addStart = 0;
	$addEnd = 0;
	if(is_null($start)){$start = substr($string, 0, 1); $addStart -= 1;}
	if(is_null($end)){$end = substr($string, strlen($string) - 1, 1);$addEnd += 1;}
//	var_dump($start , $addStart, $end, $addEnd, $string);
//	echo "---------------------\n";
    $s = array();
        do
         {
             $startpos = strpos($string, $start) + strlen($start) + $addStart;
             $endpos = strpos($string, $end, $startpos) + $addEnd;
             $s[] = substr($string, $startpos, $endpos - $startpos);
                //remove entire occurance from string:
                $string =   str_replace(substr($string, strpos($string, $start), strpos($string, $end) +strlen($end)), '', $string);
//var_dump($startpos, $endpos, $s, $string);
//echo "#####################################\n";
        }
    while (strpos($string, $start)!== false && strpos($string, $end)!== false);


    return $s;

    }

?>
