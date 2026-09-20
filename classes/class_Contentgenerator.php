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
            /* Den Namen MIT vom Stapel nehmen: createScope prueft ihn auf Eindeutigkeit,
            *  also muss er beim Verlassen wieder frei werden. Sonst wirft derselbe
            *  <sub src="..."> ein zweites Mal im selben Request "existiert bereits".
            *  Solange der Scope offen ist, schuetzt die Pruefung weiter vor Kreisen. */
            unset($this->scopes[array_pop($this->scopeStack)]);
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

	/* Die Sektoren dieses Laufs, wenn sie nicht aus der Sitzung kommen. */
	private $sector_override = null;

	/** Die Sektor-Klammer. Spiegelbild zu clearance_stack — siehe pushSectors(). */
	private array $sector_stack = [];

	/**
	*	Die Sektoren setzen — fuer einen Schluessel, der welche mitbringt.
	*
	*	Semikolongetrennt, dieselbe Form wie in der Sitzung (mod_lib.php:1440 schreibt
	*	";alpha;beta;"). Absichtlich NICHT in $_SESSION geschrieben: das wuerde ueber
	*	den Request hinaus stehenbleiben und beim naechsten Cookie-Aufruf weitergelten.
	*/
	public function setSectors(string $sektoren)
	{
		$this->sector_override = $sektoren;
	}

	/** Die geltenden Sektoren — Klammer vor Schluessel vor Sitzung. */
	public function sectors(): string
	{
		if(!empty($this->sector_stack))
			return end($this->sector_stack);

		if(!is_null($this->sector_override))
			return $this->sector_override;

		$str = $_SESSION['http://www.auster-gmbh.de/surface#sector'] ?? null;

		return is_null($str) ? '' : $str;
	}

	/** Semikolonform zu einer Menge — ";alpha;beta;" wie ";a;b;" wie "a;b". */
	private static function sektor_menge(string $roh): array
	{
		$res = array();

		foreach(explode(';', $roh) as $s)
			if('' !== ($s = trim($s))) $res[$s] = true;

		return $res;
	}

	/**
	*	Eine Sektor-Ebene betreten. Das Gegenstueck ist popSectors().
	*
	*	⚠ Die Richtung ist die UMGEKEHRTE zur Stufe, und das ist kein Versehen. Bei der
	*	Stufe klammert min() nach unten: weniger duerfen ist immer harmlos. Beim Sektor
	*	heisst "mehr" aber nicht "mehr duerfen", sondern "mehr SEHEN" — was nicht im
	*	Sektor liegt, existiert nicht. Deshalb:
	*
	*	    einengen (Schnitt)    immer erlaubt, jederzeit
	*	    hinzunehmen (Union)   nur ab Stufe 10
	*
	*	STW (2026-09-13): "Es gibt zwei Sektoren mit jeweils den Modi. Mit der Auswahl des
	*	Sektors kann man das frei geben. 10 braucht keinen Sektor, da es sich selbst einen
	*	geben kann." Genau das ist die 10 hier: die Stufe, die nach STWs Modell "Zugang
	*	vergeben" heisst. Damit gilt fuer Stufe und Sektor derselbe Satz — einengen darf
	*	jeder, erweitern nur, wer Zugang vergeben darf.
	*
	*	⚠ Wer sich ueber <access> auf 10 gehoben hat, darf danach auch hinzunehmen. Das
	*	ist die Folge von setuid und kein Loch daneben: wer als Eigentuemer laeuft, ist
	*	Eigentuemer. Die Frage, WER sich heben darf, wird bei pushClearance entschieden.
	*
	*	@return bool  false, wenn hinzugenommen werden sollte und die Stufe nicht reicht.
	*	              Die Ebene wird trotzdem betreten — dann eben nur mit dem Schnitt.
	*/
	public function pushSectors(string $sektoren, bool $hinzu = false): bool
	{
		global $logger_class;

		$neu  = self::sektor_menge($sektoren);
		$alt  = self::sektor_menge($this->sectors());
		$darf = $hinzu && ($this->clearance() >= 10);

		if($hinzu && !$darf && is_object($logger_class))
			$logger_class->setAssert('Sektor NICHT hinzugenommen: "' . $sektoren
				. '" verlangt Stufe 10, der Aufrufer hat ' . $this->clearance()
				. ' - es bleibt beim Schnitt', 0);

		$ergebnis = $darf ? ($alt + $neu) : array_intersect_key($alt, $neu);

		$this->sector_stack[] = implode(';', array_keys($ergebnis));

		return !$hinzu || $darf;
	}

	/** Die Sektor-Ebene wieder verlassen. */
	public function popSectors(): void
	{
		array_pop($this->sector_stack);
	}

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
	public function pushClearance(int $stufe, bool $setzen = false)
	{
		if(!$setzen)
		{
			$this->clearance_stack[] = min($stufe, $this->clearance());
			return;
		}

		/* SETZEN statt klammern — die Stufe darf hier auch STEIGEN (STW, 2026-09-13:
		*  "Sie darf heben und muss sogar. Sonst sind wir schnell fertig").
		*
		*  Das ist setuid: ein Dokument haelt Befehle, die auf Stufe 4 gerufen werden
		*  duerfen, und braucht innen eine 10, um ein Dokument bereitzustellen. Das Recht
		*  haengt am WEG, nicht am Aufrufer — wie passwd, das /etc/shadow schreibt,
		*  obwohl ich es nicht darf.
		*
		*  ⚠ Nur <access> ruft so. Ein <tree securitylevel> klammert weiter nach unten
		*  (Vorgabefall oben): ein Zweig ist Navigation, ein Zugang ist eine Erklaerung.
		*
		*  ⚠ Was das traegt, ist eine Aussage UEBER DAS DOKUMENT, nicht im Dokument:
		*  dass nur Stufe 10 es schreiben darf. Lokal steht dahinter __save_back
		*  (addSecurity 10) und das Dateisystem. Ein ueber src hereingereichtes Dokument
		*  hat diese Zusage NICHT — STW nimmt das bewusst in Kauf ("Externe Dokumente
		*  sind eine bloede Idee, aber ich will sie nicht verbieten"). Darum wird ein
		*  Heben laut geloggt, statt still zu geschehen. */
		if($stufe > $this->clearance())
		{
			global $logger_class;

			if(is_object($logger_class))
				$logger_class->setAssert('Stufe GEHOBEN von ' . $this->clearance()
					. ' auf ' . $stufe, 0);
		}

		$this->clearance_stack[] = $stufe;
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
			$result = in_array($tmp, explode(';', trim($this->sectors(), '; ')));

		if($tmp = intval($this->attrib_of($node, 'http://www.trscript.de/tree#securitylevel')))
		{
			/* Die Weiche je Anfrageart (STW 2026-09-16) - dieselbe Reihenfolge, die
			*  sectors() fuer den Sektor schon hat: Klammer vor Schluessel vor Sitzung.
			*
			*  Kommt die Anfrage mit einem Schluessel (clearance_base) oder steht ein
			*  <access> offen (clearance_stack), gilt clearance(). Vorher las diese Stelle
			*  die Sitzung direkt; ueber den Intern-Kanal gibt es keine Anmeldung, also
			*  galt ein Schluessel der Stufe 10 hier als -1 - gemessen: 16 von 55 Routen
			*  aus main.xml (alle geschuetzten) waren unsichtbar, und die Stufen in
			*  intern.xml selbst griffen ebenso wenig.
			*
			*  Der Webbetrieb ohne Schluessel und ohne Klammer fragt clearance() gar nicht:
			*  Sitzungsklasse wie bisher, ohne Anmeldung -1, die Marke "nicht angemeldet".
			*  Die beiden Grundlinien (0 in clearance(), -1 hier) bleiben so getrennt. */
			if(!empty($this->clearance_stack) || !is_null($this->clearance_base))
				$sec = $this->clearance();
			elseif($_SESSION['http://www.auster-gmbh.de/surface#securityclass'] ?? null)
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
	
	/* ⚠ Bis 2026-09-20 stand hier ein echo. Es stammte aus einer Zeit, in der nur STW
	*  mitlas; heute landet es mitten in der Antwort und zerstoert JSON_RESPONSE und jede
	*  Serialisierung. STW: "Werf sie weg oder werfe dort einen Fehler. Vermutlich werde ich
	*  in naechster Zukunft kaum noch was selbst schreiben."
	*
	*  LEERER Name ist kein Fehler, sondern der Erbfall: ein Unterdokument ohne eigenes
	*  <main> uebernimmt das Ausgabedokument des Aufrufers (196 Dokumente im Bestand haben
	*  <content> ohne <main>). Ein GENANNTER Name, der nicht im Register steht, ist dagegen
	*  ein Tippfehler - und der soll nicht still ins falsche Dokument schreiben. */
	function set_out_template($id) 
	{
		if('' === (string) $id)
		{
			$this->out_template = null;
			return;
		}

		if(!($this->heap['template'][$id] ?? null))
			throw new \RuntimeException('Ausgabedokument "' . $id . '" steht nicht im '
				. 'Template-Register. Der Name kommt von <main> oder <add id="...">.');

		$this->out_template = $this->heap['template'][$id];
	}
	
	function set_current_template($id) 
	{
		//var_dump( $this->heap);

		/* Siehe set_out_template: leer = Erbfall, genannt-und-unbekannt = Tippfehler.
		*  ⚠ tree_content.php:113 und tree_addtree.php:67 geben hier eine URI statt einer
		*  id herein (get_out_template()); das traegt, weil <main> sich unter seinem
		*  eigenen Pfad eintraegt. Ohne <main> ist der Wert leer - der Erbfall. */
		if('' === (string) $id)
		{
			$this->cur_template = null;
			return;
		}

		if(!($this->heap['template'][$id] ?? null))
			throw new \RuntimeException('Template "' . $id . '" steht nicht im Register. '
				. 'Der Name kommt von <add id="...">, <new id="..."> oder <main>.');

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
		/* Ein Array darf Knoten enthalten - in einer Befehlskette bleiben sie Objekte
		*  (__query legt seine Treffer so ins Ereignis, STW 2026-09-15). Benannt werden sie
		*  HIER, am Rand; json_encode eines Knotens gaebe seinen ganzen Objektgraphen. */
		if(is_array($wert))
			return ['value' => $this->shape_inner($wert), 'serialised' => true];

		if(!is_object($wert))
			return ['value' => $wert, 'serialised' => true];

		$form = ['class' => get_class($wert), 'serialised' => false];

		if(method_exists($wert, 'full_URI'))       $form['uri']   = $wert->full_URI();
		/* Derselbe vollstaendige Stempel wie in shape_inner - go_to_stamp findet ihn wieder. */
		if(method_exists($wert, 'full_stamp'))         $form['stamp'] = $wert->full_stamp('external');
		elseif(method_exists($wert, 'position_stamp')) $form['stamp'] = $wert->position_stamp();
		if(method_exists($wert, 'get_ns_attribute'))
		{
			$about = $wert->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#about');
			if($about !== false && !is_null($about)) $form['about'] = $about;
		}
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

	/**
	*	Ein Wert INNERHALB eines Arrays nach aussen: Knoten als uri/name/stamp, andere
	*	Objekte als ihre Selbstdarstellung, Arrays rekursiv. Schluessel bleiben stehen.
	*
	*	  uri    der TYP (full_URI) - was fuer ein Knoten
	*	  name   tree:name, false wenn keiner
	*	  stamp  WO: der vollstaendige Stempel, den go_to_stamp wiederfindet - mit stamp_key
	*	         verschluesselt (external), sonst mit Dateinamen (absolute). Seit
	*	         2026-09-15; vorher der baumlokale ".0.0", der nach der instanzweiten
	*	         Abfrage zwei Baeume nicht mehr unterschied.
	*	  about  WAS: rdf:about, nur wenn der Knoten eine Identitaet traegt
	*/
	private function shape_inner($wert)
	{
		if(is_array($wert))
			return array_map([$this, 'shape_inner'], $wert);

		if($wert instanceof Interface_node)
		{
			$form = ['uri'   => $wert->full_URI(),
			         'name'  => $wert->get_ns_attribute('http://www.trscript.de/tree#name'),
			         'stamp' => $wert->full_stamp('external')];

			$about = $wert->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#about');
			if($about !== false && !is_null($about))
				$form['about'] = $about;

			return $form;
		}

		if(is_object($wert))
			return method_exists($wert, '__toString') ? (string) $wert : get_class($wert);

		return $wert;
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

		/* Das Log kommt aus dem SPEICHER (STW, 2026-09-10), nicht mehr aus der Datei.
		*  Die Datei ist nur noch die Absturzkopie ([log] active) - faellt das ganze System,
		*  gibt es ohnehin keine Antwort, sondern eine 500. __give_log mit level gibt das
		*  Array seines eigenen Zuhoerers aus. */
		if($this->outputMode == LOG)
		{
			/* Das Log ist Text. Ohne Kopf schickte PHP text/html, und das liest
			*  sich wie eine gerenderte Seite statt einer Antwort. */
			if($set_header && !headers_sent())
				header('Content-Type: text/plain; charset=' . $type);
			return Logger::giveLogText(Logger::$giveLogName);
		}

	/* Ein Intern-Aufruf bekommt eine eigene Antwort. Hat kein Befehl geantwortet
	*  und kein start ein Ausgabedokument bestimmt (tree_main setzt out_template,
	*  output_doc zusaetzlich doc_out_template), waere der Rueckfall der
	*  serialisierte INTERN-Baum - eine Seite, die wie eine Antwort aussieht und
	*  keine ist. Stattdessen wird gesagt, dass nichts kam. Ein start, der eine
	*  Seite rendert, laeuft weiter unten durch: dann IST das Dokument die Antwort. */
	if($this->injectedLine && !$this->doc_out_template && !$this->out_template)
	{
		if($set_header && !headers_sent())
			header('Content-Type: application/json; charset=' . $type);

		$aufruf = json_decode($this->injectedLine, true);

		return json_encode([
			'answered' => false,
			'command'  => $aufruf['Command']['Name'] ?? null,
			'note'     => 'Der Befehl lief, aber nichts wurde zurueckgegeben. Ein Ergebnis'
			            . ' geht mit __to_owner nach aussen, das Protokoll mit __give_log.'
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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

		/* Hier lag ein Seiten-Cache: die fertige Ausgabe ging in die Tabelle precache
		*  (name, best_before, value) und wurde beim naechsten Mal statt des Laufs
		*  zurueckgegeben. Entfernt 2026-09-13 samt Tabelle (STW: "Das Cachen hat seit
		*  einem Jahrzehnt keinen mehr interessiert").
		*
		*  Er lief ohnehin nie: $this->rst wurde nirgends zugewiesen und war nicht einmal
		*  als Eigenschaft deklariert, die Bedingung also immer falsch. best_before wurde
		*  nirgends gelesen. Die einzige Zeile in der Tabelle stammte vom 25.10.2008.
		*
		*  ⚠ Und die Form waere heute falsch, nicht nur ungenutzt. Sie stammt aus TYPO3,
		*  wo eine Seite INHALT ist: teuer zu rendern, selten geaendert. Hier ist ein
		*  Dokument ein PROGRAMM. Ein Treffer im Cache gaebe die Ausgabe zurueck OHNE den
		*  Lauf - kein first, kein once, keine Tabelle angelegt, kein Fuehler befragt.
		*
		*  Was von der Idee bleibt: cachen ist erlaubt, wo etwas WIRKUNGSFREI ist, und
		*  genau das koennen die Tuerschilder sagen (desc:effect="keiner, liest nur").
		*  STW denkt es eine Ebene hoeher weiter - eigene Befehle koennen einen Befehl
		*  auch UEBERSCHREIBEN, dort waere der Platz dafuer. Nicht hier.
		*/
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
