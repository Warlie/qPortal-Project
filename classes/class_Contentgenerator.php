<?PHP

/**
*ContentGenerator vers. 1.0
*
* Generates content by reading XML and DB-entries
*
* (C) Stefan Wegerhoff
* vers. 1.0101
*/
require("class_database.php");
require("TreeEngine.php");
require("xml_multitree_semantic.php");


define("SEND_HEADER",true);
define("SEND_NO_HEADER",false);

class ContentGenerator
{

var $timestamp = 0;
var $timestamp2 = 0;
var $aktion = "init";
var $show_time = false;
	
//to stop running processes
var $eject = false;
var $array_incomming_list = array(); //defines a startpoint for parsing
var $incomming_find = true;

var $dbAccess = null;
var $template = null;
var $id_output_template = null;
var $doc_out_template = null;
var $out_template = null;
var $cur_template = null;
var $maintemplate = null;
var $structur = null;
var $control = null;
var $nodeName = null;
var $gotos = [];
var $panel = true;
var $static = false;
var $menu = true;
var $param = array();
var $spezial = array();
public $XMLlist = null;
private $namespace_reg = null;

var $namespace_main = '';

var $heap = array(); //muss überarbeitet werden, namenskonflikte


	function __construct( $URL, $User, $PWST, $db_name = "", $codeset = "")
	{
		
		$this->dbAccess = new Database($URL, $User, $PWST, $db_name, $codeset);
		$this->XMLlist = new xml_xPath_sParqle($this);

	}
	
	function errno()
	{
		
		return $this->dbAccess->errno();
	}
	
	function getUser()
	{
		if($_SESSION['http://www.auster-gmbh.de/surface#user'])
			return $_SESSION['http://www.auster-gmbh.de/surface#user'];
		else
			return 'nobody';
	}
	
	function getForename()
	{
		if($_SESSION['http://www.auster-gmbh.de/surface#forename'] )
			return $_SESSION['http://www.auster-gmbh.de/surface#forename'] ;
		else
			return 'no';
	}
	
	function getSurname()
	{
		if($_SESSION['http://www.auster-gmbh.de/surface#surname'] )
			return $_SESSION['http://www.auster-gmbh.de/surface#surname'] ;
		else
			return 'body';
	}
	
	function getGroups()
	{
		if($_SESSION['http://www.auster-gmbh.de/surface#sector'] )
			return $_SESSION['http://www.auster-gmbh.de/surface#sector'] ;
		else
			return '';
	}
	
	function getAccess()
	{

		$result = true;

		if($tmp = $this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#sector') )	
			$result = in_array($tmp, explode(';',
				(is_null($str = $_SESSION['http://www.auster-gmbh.de/surface#sector']) ? "" : trim($str, ';'))
				));

			//var_dump($tmp, $_SESSION['http://www.auster-gmbh.de/surface#sector'],  $result);
				
		if($tmp =  intval($this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#securitylevel')) )
		{
			
			if($_SESSION['http://www.auster-gmbh.de/surface#securityclass'])
				$sec = intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']);
			else
				$sec = -1;
				
			
			$result = $result  &&  ((($tmp == -1) &&  ($sec == -1)) || (($tmp != -1) &&  ($sec >= $tmp))) ; 
		}	
			//var_dump($this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#name'));
		$result = $result && ( false !== $this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#value'));
			
		if ( !(false === ($hidden = strpos( $this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#name'), '.' ) ) )
		&& intval($hidden) == 0 )$result =false;
		
		// Abfrage client
		if($tmp = $this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#device') )		
			$result = $result && ((strtoupper($tmp) == 'MOBILE') xor  !$this->isMobileDevice() ) ;
		

		//var_dump($result);

		//if($result)echo $this->XMLlist->show_ns_attrib('http://www.trscript.de/tree#device');
		//echo $this->isMobileDevice();
	return $result;
	}
	
	private function isMobileDevice(){
		$aMobileUA = array(
			'/iphone/i' => 'iPhone', 
			'/ipod/i' => 'iPod', 
			'/ipad/i' => 'iPad', 
			'/android/i' => 'Android', 
			'/blackberry/i' => 'BlackBerry', 
			'/webos/i' => 'Mobile'
			);

		//Return true if Mobile User Agent is detected
		foreach($aMobileUA as $sMobileKey => $sMobileOS){
			if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
				return true;
			}
		}
		//Otherwise return false..  
		return false;
	}
	
	
	function &getSQLObj()
	{
		
		return $this->dbAccess;

	}
	
	function &getXMLObj()
	{
		return $this->XMLlist;
	}
	
	function injectSQL($SQLString)
	{
		
		//echo $SQLString;
		
		$teile = explode(";
", $SQLString);
		for($i = 0; count($teile)>$i;$i++)
		{

		if(strlen(trim($teile[$i]))>2)$this->dbAccess->SQL($teile[$i] . ';');
		}

	}
	
	function setboolPanel($bool)
	{
		$this->panel = $bool;
	}
	function setstaticPanel($bool)
	{
		$this->static = $bool;
	}
	
	function setPageParam($param)
	{
		$this->heap['request'] = $param;
		$this->param = $param;
		
	}

	function getHeap(){return $this->heap;}
	
	function setSessionParam($param)
	{
		$this->heap['session'] = $param;
	}
	
	function setXMLTemplate($URL)
	{
		//$this->template = $URL;
	}
	
	function setXMLStructur($URL)
	{
		$this->structur = $URL;
	}

	function getXMLStructur()
	{
		return $this->structur;
	}
	
	function setControlElement($name,$attrib)
	{
		$this->control[0] = $name;
		$this->control[1] = $attrib;
	}
	
	function setTreeNodeName($name="")
	{
		$this->nodeName = $name;
	}
	

	//----------functions for nodes --------------
	
	function set_template($id,$uri)
	{
		$this->heap['template'][$id] = $uri;
	}
	
	function get_template($id) //:URI
	{
		return $this->heap['template'][$id];
	}
	
	function set_out_template($id) 
	{

		if(!$this->heap['template'][$id])echo " $id nicht verfuegbar";
		$this->out_template = $this->heap['template'][$id];
		
		
		
	}
	
	function set_current_template($id) 
	{
		//var_dump( $this->heap);

		if(!$this->heap['template'][$id])echo " '$id' nicht verfuegbar \n";
		$this->cur_template = $this->heap['template'][$id];
		//echo $this->cur_template;
		
		
		
	}
	
	function show_templates(){var_dump($this->heap);}
	
	function get_out_template() //:URI
	{
		return $this->out_template;
	}
	
	function get_current_template() //:URI
	{
		return $this->cur_template;
	}
	
	function set_doc_out($id) //:URI
	{
		//echo 'ausgabe "' . $id . '" -' .  $this->heap['template'][$id] . '- Ende';
		$this->id_output_template = $id;
		//$this->doc_out_template = null;
	}
	
	function set_Main_NS($ns)
	{
		$this->namespace_main = $ns;
	}
	
	function get_Main_NS()
	{
		return $this->namespace_main;
	}
	
	function set_Reg_NS($ns)
	{
		$this->namespace_reg = $ns;
	}
	
	function get_Reg_NS()
	{
		return $this->namespace_reg;
	}
	

	
	
	//-----------END functions for nodes-----------
	
	function generate()
	{
		
		
		//if(is_Null($this->template))return false;
		
		if(is_Null($this->structur))return false;

		if(is_Null($this->nodeName))return false;

		$this->heap['template'] = null;
		
		$treeEngine = new TreeEngine($this);
		//$this->XMLlist->load($this->template);
		
		
		$treeEngine->load_structur($this->structur,'@registry_surface_system');
				
		
				
		$this->XMLlist->cur_node();
		
		if( $this->XMLlist->get_URI() != 'http://www.trscript.de/tree#indextree')
		{
			//echo $this->XMLlist->get_URI() . " ";
			return true;
		}
		//echo $this->XMLlist->get_URI() . " ";
//var_dump();
				$booh = null;
		

		if($cur_obj = $this->XMLlist->show_xmlelement())
		{
//model=xpath_model&query="wubb"& //model=xpath_model,namespace=\'\',query=\'wubb\'
// '*?__find_node(json=' . base64_encode( '{"name":"http://www.trscript.de/tree#final"}' ) . ')=' . base64_encode( '*?start' )
		if($this->nodeName == "")
			
			$cur_obj->event_message_check( 
				["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>'{"name":"http://www.trscript.de/tree#final"}'], "Value"=> ["Identifire"=>"*", "Command"=> ["Name"=> "start" ], "Attribute"=>$this->param] ]] //["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>'{"name":"http://www.trscript.de/tree#final"}'], "Value"=> '*?start']]
				,new EventObject('',$this,$booh));
			else
/*
'*?__find_node(json=' . 
				base64_encode( '{"name":"http://www.trscript.de/tree#tree", "attribute":{ "http://www.trscript.de/tree#name":"' . $this->nodeName . '" }}' ) . ')=' . 
				base64_encode( '*?start' )
*/
			try{
				//echo "jojo\n";
			$cur_obj->event_message_check(
				["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>'{ "attribute":{"http://www.trscript.de/tree#name":"' . $this->nodeName . '"}}'], "Value"=> ["Identifire"=>"*", "Command"=> ["Name"=> "start" ], "Attribute"=>$this->param]  ]]
				,new EventObject('',$this,$booh)); //  "name":"http://www.trscript.de/tree#tree",
			//echo "donedone\n";
			}  //$this->XMLlist->show_xmlelement()->event_message_in('*?start',new EventObject('',$this,$booh));
			catch(NotExistingBranchException $e)
			{
				//var_dump($this->XMLlist->show_xmlelement());
				$cur_obj->event_message_check( 
				["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>'{"name":"http://www.trscript.de/tree#final"}'], "Value"=> '*?start']]
				,new EventObject('',$this,$booh));
			}
			catch(EmptyTreeException $e)
			{
				//var_dump($this->XMLlist->show_xmlelement());
				$cur_obj->event_message_check( 
				["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>'{"name":"http://www.trscript.de/tree#final"}'], "Value"=> '*?start']]
				,new EventObject('',$this,$booh));
			}
		}
		
    		
		//

		
		
		/* alters outputtemplate */
		//var_dump($this->heap['template']);
		if($this->id_output_template)
		$this->doc_out_template = $this->heap['template'][$this->id_output_template];
		
		return true; //EnD
}
	

//function setSystemDocument($set_header,$type = 'UTF-8')

	function getSystemDocument($set_header,$type = 'UTF-8')
	{
		$this->out_template = '@registry_surface_system';
		$this->XMLlist->prevent_read_event(true);
		if(!$this->XMLlist->change_URI($this->out_template))echo "Das Dokument: '" . $this->out_template . "' nicht gefunden!(getoutput)";
		$res = $this->XMLlist->save_Stream($out,$set_header);
		$this->XMLlist->prevent_read_event(false);
		//$this->XMLlist->test_consistence();
		return $res;
	}

	function getoutput($set_header,$type = "UTF-8",$special = "")
	{
	//echo memory_get_usage(true);
	//echo memory_get_peak_usage(true);
//echo  '<b>' . $this->heap['object']['reciepe']->lock . '</b><br>';

	if($this->doc_out_template)$this->out_template = $this->doc_out_template;
	//echo $this->out_template . " " . $this->XMLlist->ALL_URI();//
	if($this->out_template)
		if(!$this->XMLlist->change_URI($this->out_template))echo "Das Dokument: '" . $this->out_template . "' nicht gefunden!(getoutput)";
		
		//if($type == "")$out = 'UTF-8';
		//else $out = $type;
		$out = $type;
		
		
		if($special == "HTML")
		{
			
		$res = str_replace(
			array(	'ü','Ü',
				'ö','Ö',
				'ä','Ä'
				),
			array(	'&uuml;','&Uuml;',
				'&ouml;','&Ouml;',
				'&auml;','&Auml;'
				),

			$this->XMLlist->save_Stream($out,$set_header)
			);
		}
		else
		
			$res = $this->XMLlist->save_Stream($out,$set_header);
		
		if(!is_null($this->rst))
		{
		if(0 == $this->rst->rst_num())
			{
				
				$this->rst->setValue('precache.value',$res);
				$this->rst->update();
				$this->dbAccess->insert_rst($this->rst);
			}
		if(0 < $this->rst->rst_num())
			{
				$this->rst->first_ds();
				return $this->rst->value('precache.value');
			}
		}
		return $res;
	}


	function saveoutput($set_header,$type = "",$special = "")
	{
	//echo memory_get_usage(true);
	//echo memory_get_peak_usage(true);
//echo  '<b>' . $this->heap['object']['reciepe']->lock . '</b><br>';

	if($this->doc_out_template)$this->out_template = $this->doc_out_template;
		if($this->out_template)
		if(!$this->XMLlist->change_URI($this->out_template))echo "Das Dokument: '" . $this->out_template . "' nicht gefunden!";
		
		if($type == "")$out = 'UTF-8';
		else $out = $type;
		
		
		

		
		return $this->XMLlist->save_file($out,$set_header);
	}

//--------------------------------


	public function __toString()
	{
		return 'contextgenerator';
	}
	
    public function __debugInfo() {
        return 'contentgen';
    }

}
?>
