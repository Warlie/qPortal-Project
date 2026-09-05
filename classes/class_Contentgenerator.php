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

define('LOG','LOG');
define('OUTPUT','OUTPUT');
/* Antwort statt Dokument. Ein Skript laeuft nur, wenn es ein start bekommt -
*  Befehle wie __info sind Aspekte daneben und antworten selbst. JSON_RESPONSE
*  und nicht JSON, weil ein derart allgemeiner Konstantenname zu leicht kollidiert.
*/
define('JSON_RESPONSE','JSON_RESPONSE');

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
private $paramIDX = [];
var $spezial = array();
public $XMLlist = null;
public $TreeObj = null;
private $registry = null;
private $namespace_reg = null;
private $schema = false;
private $injectedLine = false;
private $outputMode = OUTPUT;
private $responseBuffer = null;
private $responseMime = 'application/json';
private $result_for_output = []; // external call could return data by calling __to_owner

private $foundAPage = false;

// scope
    private array $scopes = [];
    private array $scopeStack = [];

var $namespace_main = '';

var $heap = array(); //muss überarbeitet werden, namenskonflikte


	function __construct( $URL, $User, $PWST, $db_name = "", $codeset = "")
	{
		
		$this->dbAccess = new Database($URL, $User, $PWST, $db_name, $codeset);
		$this->XMLlist = new xml_semantic($this);
		$this->registry = new NameSpaceBehaviorRegistry();

	}
	
	/**
	*	commandline injection
	*/
	
	public function commandLineInjection(string $json)
	{
		$this->injectedLine = $json;
	}

	public function switchOutput($myout = LOG)
	{
		$this->outputMode = $myout;
	}

	/* Legt die Antwort ab und schaltet die Ausgabe darauf um. Ein zweiter Aufruf
	*  haengt an - eine Befehlsliste darf mehrfach antworten.
	*/
	public function setResponse(string $body, string $mime = 'application/json')
	{
		$this->responseBuffer = is_null($this->responseBuffer) ? $body : $this->responseBuffer . $body;
		$this->responseMime   = $mime;
		$this->switchOutput(JSON_RESPONSE);
	}
	/*
	*	Quick and dirty variable management
	*
	*/

    /**
     * Erstellt einen neuen Scope und macht ihn zum aktuellen.
     * Wenn kein Name angegeben wird, wird ein eindeutiger Name generiert.
     */
    public function createScope(?string $name = null): string
    {
        if ($name === null) {
            $name = uniqid('scope_', true);
        }

        if (array_key_exists($name, $this->scopes)) {
            // Namen müssen eindeutig sein, hier könnte man eine Exception werfen.
            throw new \Exception("Scope '{$name}' existiert bereits.");
        }

        $this->scopes[$name] = [
            'params' => [],
            'results' => []
        ];

        // Den neuen Scope auf den Stack legen
        array_push($this->scopeStack, $name);
        
        return $name;
    }

    /**
     * Fügt einen Parameter als Schlüssel-Wert-Paar zum aktuellen (oder einem bestimmten) Scope hinzu.
     */
    public function addParamToScope($param_name, $param_value, ?string $name = null): bool
    {
        $scopeName = $name ?? end($this->scopeStack);

        if ($scopeName === false || !array_key_exists($scopeName, $this->scopes)) {
            return false; // Scope existiert nicht
        }

        $this->scopes[$scopeName]['params'][$param_name] = $param_value;
        return true;
    }

    /**
     * Fügt ein Ergebnis zum aktuellen (oder einem bestimmten) Scope hinzu.
     */
    public function addResultToScope($result, ?string $name = null): bool
    {
        $scopeName = $name ?? end($this->scopeStack);

        if ($scopeName === false || !array_key_exists($scopeName, $this->scopes)) {
            return false; // Scope existiert nicht
        }

        array_push($this->scopes[$scopeName]['results'], $result);
        return true;
    }

    /**
     * Verlässt den aktuellen Scope, indem er vom Stack genommen wird.
     */
    public function leaveScope(): bool
    {
        if (count($this->scopeStack) > 0) {
            array_pop($this->scopeStack);
            return true;
        }
        return false; // Kein Scope mehr auf dem Stack
    }

	/**
	*	delivers parameters belonging to the scope
	*/
	public function getParam($param_name)
	{
		$scopeName = end($this->scopeStack);
		return $this->scopes[$scopeName]['params'][$param_name];
	}
	
	/**
	*	delivers result belonging to the scope
	*/
	public function getResult()
	{
		$scopeName = end($this->scopeStack);
		return $this->scopes[$scopeName]['results'];
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
	
	/* Die Grundstufe dieses Laufs — aus dem API-Schluessel gesetzt, sonst aus der
	*  Sitzung abgeleitet. Nicht zu verwechseln mit dem Stapel darueber. */
	private $clearance_base = null;

	/* Der Stapel. Ein Nutzer ruft einen Agenten, der einen Agenten ruft, der einen
	*  Agenten ruft — jede Ebene bekommt ihre eigene Stufe und gibt sie danach
	*  zurueck (STW). Er wohnt in tree:access: dort wird betreten und verlassen. */
	private array $clearance_stack = [];

	/**
	*	Welche Sicherheitsstufe gilt gerade?
	*
	*	Oben auf dem Stapel, sonst die Grundstufe, sonst die Klasse aus der Sitzung.
	*
	*	⚠ Grundlinie 0, nicht -1: Stufe 0 heisst "starten erlaubt, keine
	*	Systemabfragen", und genau das darf auch ein Anonymer. Das ist NICHT dieselbe
	*	Grundlinie wie im Baum-Waechter (mayEnter), die weiterhin -1 ist und noch
	*	nicht entschieden — bewusst getrennt, statt sie still zu koppeln.
	*/
	public function clearance(): int
	{
		if(!empty($this->clearance_stack))
			return end($this->clearance_stack);

		if(!is_null($this->clearance_base))
			return $this->clearance_base;

		return isset($_SESSION['http://www.auster-gmbh.de/surface#securityclass'])
			? intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass'])
			: 0;
	}

	/**
	*	Die Grundstufe des Laufs setzen — beim EINTRITT, aus dem API-Schluessel.
	*
	*	Das ist die einzige Stelle, an der eine Stufe steigen kann. Sie liegt
	*	ausserhalb der Dokumente, und das ist der Punkt: was die Grenze definiert,
	*	darf nicht innerhalb der Grenze stehen.
	*/
	public function setClearance(int $stufe)
	{
		$this->clearance_base = $stufe;
	}

	/**
	*	Eine Ebene betreten.
	*
	*	⚠ NIEMALS hoeher als das, was gerade gilt — darum das min(). Ohne die
	*	Klammer koennte ein Dokument sich mit <access securitylevel="20"> selbst
	*	zum Eigentuemer erklaeren, und die ganze Einstufung waere Zierde. Senken
	*	ist erlaubt und nuetzlich: so gibt man einem untergeordneten Agenten
	*	weniger Recht, als man selbst hat.
	*/
	public function pushClearance(int $stufe)
	{
		$this->clearance_stack[] = min($stufe, $this->clearance());
	}

	/** Die Ebene wieder verlassen. */
	public function popClearance()
	{
		array_pop($this->clearance_stack);
	}

	/** Wie tief steht der Stapel? Fuer Diagnose und Pruefstand. */
	public function clearance_depth(): int
	{
		return count($this->clearance_stack);
	}

	/**
	*	Ein Attribut lesen — vom uebergebenen Knoten oder, ohne ihn, von der
	*	aktuellen Parserposition. Damit koennen Menue (Position) und Baum-Waechter
	*	(Knoten in der Hand) dieselbe Pruefung benutzen.
	*/
	private function attrib_of($node, $uri)
	{
		return is_object($node) ? $node->get_ns_attribute($uri)
		                        : $this->XMLlist->show_ns_attrib($uri);
	}

	/**
	*	DARF BETRETEN WERDEN? Sektor und Sicherheitsstufe, sonst nichts.
	*
	*	Herausgeloest aus getAccess(), weil das vier verschiedene Fragen beantwortet:
	*	Sektor und Stufe sind ZUTRITT, `value` und der fuehrende Punkt sind
	*	SICHTBARKEIT im Menue, `device` ist Darstellung. Fuers Menue faellt das
	*	zusammen — dort heisst alles "nicht anzeigen". Fuer den Baum-Waechter nicht:
	*	ein Dienst ohne `value` oder mit fuehrendem Punkt (.view, .save_doc) ist
	*	unsichtbar und trotzdem aufrufbar. Wer dort die ganze Funktion nimmt, sperrt
	*	die halbe Instanz aus.
	*
	*	Der Sektor ist eine EXISTENZaussage und wird von keiner Stufe ueberstimmt —
	*	darum die Klammerung, die in den beiden Baumkopien gefehlt hat.
	*/
	public function mayEnter($node = null)
	{
		$result = true;

		if($tmp = $this->attrib_of($node, 'http://www.trscript.de/tree#sector'))
			$result = in_array($tmp, explode(';',
				(is_null($str = $_SESSION['http://www.auster-gmbh.de/surface#sector']) ? "" : trim($str, ';'))
				));

		if($tmp = intval($this->attrib_of($node, 'http://www.trscript.de/tree#securitylevel')))
		{
			if($_SESSION['http://www.auster-gmbh.de/surface#securityclass'])
				$sec = intval($_SESSION['http://www.auster-gmbh.de/surface#securityclass']);
			else
				$sec = -1;

			/* -1 ist die Marke "nur fuer Nichtangemeldete": nach der Anmeldung
			*  verschwindet der Knoten wieder. */
			$result = $result && ((($tmp == -1) && ($sec == -1)) || (($tmp != -1) && ($sec >= $tmp)));
		}

		return $result;
	}

	/**
	*	DARF IM MENUE ERSCHEINEN? Zutritt plus Sichtbarkeit plus Geraet.
	*	Verhalten unveraendert; der Zutrittsteil steht jetzt in mayEnter().
	*/
	function getAccess($node = null)
	{
		$result = $this->mayEnter($node);

		$result = $result && (false !== $this->attrib_of($node, 'http://www.trscript.de/tree#value'));

		if(!(false === ($hidden = strpos((string) $this->attrib_of($node, 'http://www.trscript.de/tree#name'), '.')))
		&& intval($hidden) == 0) $result = false;

		// Abfrage client
		if($tmp = $this->attrib_of($node, 'http://www.trscript.de/tree#device'))
			$result = $result && ((strtoupper($tmp) == 'MOBILE') xor !$this->isMobileDevice());

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
	
	function &getRegObj()
	{
		return $this->registry;
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
	
	function setLexicalOrderParam($next)
	{
		$this->paramIDX[] = $next;
	}
	
	function getLexicalOrderParam()
	{
		$ArrayObject = new ArrayObject($this->paramIDX);
		return $ArrayObject->getArrayCopy();
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
	
	function set_Schema($path)
	{
		$this->XMLlist->set_schema($path);
	}
	
	
	function get_Eff_Branch($branch)
	{
	
	}
	
	//-----------END functions for nodes-----------
	
	function generate()
	{
		
		
		//if(is_Null($this->template))return false;
		
		if(is_Null($this->structur))return false;

		//if(is_Null($this->nodeName))return false;

		$this->heap['template'] = null;
		
		$this->TreeObj = $treeEngine = new TreeEngine($this);
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
		//echo json_encode(["Identifire"=>"*", "Command"=> ["Name"=> "__find_node", "Attribute"=>["json"=>'{"name":"http://www.trscript.de/tree#final"}'], "Value"=> ["Identifire"=>"*", "Command"=> ["Name"=> "start" ], "Attribute"=>$this->param] ]] );

		if($cur_obj = $this->XMLlist->show_xmlelement())
		{
//$this->param
		$path = [];
		$open = true;

		foreach (array_map('trim', explode(',', QUERY_PARAM)) as $p) {
			if (!empty($this->param[$p])) $path[] = $this->param[$p];

			/* Achsenfolge fuer das Menue: alle belegten Parameter plus den
			   ersten freien - der ist die Abzweigung, die das Menue variiert */
			if ($open) {
				$this->setLexicalOrderParam($p);
				$open = !empty($this->param[$p]);
			}
		}

		//var_dump($path);
		
		if($this->injectedLine)
		{
			$cur_obj->hold_messages($this->injectedLine,$this->start_event($booh));
			if($this->id_output_template)
				$this->doc_out_template = $this->heap['template'][$this->id_output_template];
			return true;
		}

		$cur_obj->hold_messages(
				["Identifire"=>"http://www.trscript.de/tree#indextree", "Command"=> ["Name"=> "start" ], "Attribute"=>$path]
				,$this->start_event($booh));

		
		/* Kein Knoten hat gerendert -> Route nicht aufgeloest.
		   index.php:420 nimmt das false und liefert error/404.html aus. */
		if(!$this->foundAPage) return false;
		
		if($this->id_output_template)
		$this->doc_out_template = $this->heap['template'][$this->id_output_template];

		return true;
		}
}
	

	/**
	*	Das Ereignis, mit dem ein Lauf beginnt.
	*
	*	Requester und Owner sind hier dasselbe Objekt und trotzdem zwei Dinge: der
	*	Requester ist, WER fragt (und bleibt es ueber den ganzen Lauf, tree_tree.php:218
	*	greift darauf durch), der Owner ist, WEM die Antwort gehoert. Auf dem Weg von
	*	aussen — Intern-Kanal wie Seitenaufruf — ist beides diese Instanz. Ein Knoten,
	*	der selbst etwas erfragt, setzt den Owner auf sich und bekommt die Antwort
	*	statt ihrer. Zugestellt wird ueber __to_owner (behavior/std.php).
	*/
	private function start_event(&$context)
	{
		$ereignis = new EventObject('', $this, $context);
		//$eigner   = $this;

		//$ereignis->set_owner($eigner);

		return $ereignis;
	}
	
	/**
	*	Der ContentGenerator nimmt Daten entgegen wie ein Knoten.
	*
	*	Dieselbe Signatur wie Interface_node::setdata, damit __to_owner (und
	*	__get_data) einen Aufrufer von aussen genauso bedienen wie einen Knoten den
	*	naechsten — ein Aufruf, keine Fallunterscheidung im Befehl.
	*
	*	Hier wird nur GESAMMELT. Ob die Antwort das Dokument ersetzt, entscheidet
	*	getoutput() an einer Stelle — sobald mindestens ein Eintrag vorliegt. Damit
	*	haengt die Umschaltung am Inhalt und nicht an einem Seiteneffekt im Setter,
	*	und sie steht an derselben Stelle wie die des Logs.
	*
	*	⚠ Kein var_dump hier. Er landet vor der Antwort und macht sie unbrauchbar —
	*	genau die Regel aus CLAUDE.md. Zum Mitlesen dient das Log.
	*/
	function setdata($data,$pos = null, $add = false, $alter_sensity = true)
	{
		global $logger_class;

		$this->result_for_output[] = $data;

		if($logger_class)
			$logger_class->setAssert('ContentGenerator nimmt einen Wert entgegen ('
				. (is_object($data) ? get_class($data) : gettype($data)) . '), '
				. count($this->result_for_output) . ' insgesamt', 5);

	}

	/**
	*	Was von einem Wert nach aussen geht.
	*
	*	⚠ Ein Knoten ist ein ZUSAMMENGESETZTER Wert, und qPortal kennt das
	*	Serialisieren noch nicht. Darum geht heute nur, was sich benennen laesst —
	*	und das Feld "serialised" sagt es an. Eine halbe Antwort, die sich als ganze
	*	ausgibt, waere schlimmer als die benannte Luecke.
	*/
	private function answer_shape($wert)
	{
		if(!is_object($wert))
			return ['value' => $wert, 'serialised' => true];

		$form = ['class' => get_class($wert), 'serialised' => false];

		if(method_exists($wert, 'full_URI'))       $form['uri']   = $wert->full_URI();
		if(method_exists($wert, 'position_stamp')) $form['stamp'] = $wert->position_stamp();
		if(method_exists($wert, 'getdata'))
		{
			$daten = $wert->getdata();
			if(!is_object($daten)) $form['value'] = $daten;
		}

		/* NICHT-KNOTEN: hat keine der Knotenmethoden etwas geliefert, ist die
		*  Selbstdarstellung das Einzige, was der Wert ueber sich sagen kann — die
		*  wird dann zurueckgegeben (STW).
		*  ⚠ serialised bleibt false: eine Zeichenkette ueber ein Objekt ist eine
		*  Darstellung, nicht das Objekt. Bei einem Knoten greift der Zweig gar
		*  nicht erst — dessen __toString ist "My name is: <uri> with", eine
		*  Debug-Zeile, die als Antwortwert wie ein Datum aussaehe. */
		if(count($form) == 2 && method_exists($wert, '__toString'))
			$form['value'] = trim((string) $wert);

		return $form;
	}

	function found_relevant_page()
	{
		$this->foundAPage = true;
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
		/* Der Befehl hat selbst geantwortet - das Ausgabedokument wird gar nicht
		*  erst serialisiert. Der Content-Type gehoert dazu: ein 200 mit text/html
		*  laesst eine Antwort echt aussehen, die keine ist.
		*/
		if($this->outputMode == JSON_RESPONSE)
		{
			if($set_header && !headers_sent())
				header('Content-Type: ' . $this->responseMime . '; charset=' . $type);
			return $this->responseBuffer;
		}

		/* Hat jemand dem Fragenden etwas gegeben (__to_owner / __get_data ueber
		*  setdata), dann IST das die Antwort — das Dokument wird gar nicht erst
		*  serialisiert. Die Umschaltung haengt am Inhalt: mindestens ein Eintrag.
		*  Dieselbe Stelle und dieselbe Form wie beim Log eine Zeile weiter. */
		if(count($this->result_for_output) > 0)
		{
			if($set_header && !headers_sent())
				header('Content-Type: application/json; charset=' . $type);

			return json_encode(
				array_map([$this, 'answer_shape'], $this->result_for_output),
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		if($this->outputMode == LOG)
			if (file_exists(LOG_PATH)) {
				$inhalt = file_get_contents(LOG_PATH);
				return $inhalt;
			} else {
				return "File wasn't found";
			}

	if($this->doc_out_template)$this->out_template = $this->doc_out_template;
	//echo $this->out_template . " " . $this->XMLlist->ALL_URI();//
	if($this->out_template)
		if(!$this->XMLlist->change_URI($this->out_template))echo "Das Dokument: '" . $this->out_template . "' nicht gefunden!(getoutput)";
		
		//if($type == "")$out = 'UTF-8';
		//else $out = $type;
		$out = $type;

		// Serialisieren = Lesen, nicht Ausführen: unterdrückt readdata-Nebeneffekt
		// (geerbte, un-ausgeführte Funktionsknoten wie DBO.set_list). Analog getSystemDocument.
		$this->XMLlist->prevent_read_event(true);

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

		$this->XMLlist->prevent_read_event(false);

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
        return ['contentgen'];
    }

}
?>
