<?PHP

/**
*FileService
*
* Supports following func_tions.
*	- loding files into request-array
*	- 
*
* @-------------------------------------------
* @title:Upload_File
* @autor:Stefan Wegerhoff
* @description: Loads files over browserupload
*
*/
require_once("plugin_interface.php");

class FileService extends plugin 
{
var $reset_name = "";
var $producer = '';
var $xpath = '';
var $toUse = '';
var $doctype = 'xml';
var $scanner;
var $result_table = array();
var $pos_res = 0;
var $filehandle;
var $save_file;
var $handle;

/* Die oeffentlichen Spaltennamen; links steht, wie ein Dokument die Spalte nennt,
*  rechts der Schluessel, den der Scanner schreibt.
*
*    tag    -> found   das Suchwort, das getroffen hat
*    match  -> tag     die Fundstelle mit dem Rest ihrer Zeile
*    pos    -> pos     Zeilennummer in der Datei, gezaehlt ab null
*    file   -> file    die Datei
*    number -> number  laufende Nummer in der Menge
*
*  ACHTUNG Die beiden ersten kreuzen sich: der Scanner legt seit jeher die
*  FUNDSTELLE unter 'tag' ab (save_entry(substr($lower,$pos1),..)); das Suchwort
*  selbst kam erst als viertes Feld 'found' dazu. Nach aussen tragen die Namen,
*  was wirklich drinsteht.
*  ACHTUNG id_alias() benennt deshalb 'match' um, nicht 'tag'.
*/
var $alias = array('tag'=>'found','match'=>'tag','pos'=>'pos','file'=>'file','number'=>'number');

/* Wurde ueberhaupt nach etwas gesucht? Der Scanner bleibt abwaertskompatibel und
*  schreibt ohne Suchwort den Satz 'no tag parameter' samt Position 0 in jede
*  Zeile - beides Verlegenheiten, keine Aussagen. Uebersetzt wird hier, siehe
*  carried(). */
private $tag_count = 0;
private $scanned_with_tags = false;


	function __construct()
	{
		$this->scanner = new File_Scan();
		
	}
	
	/**
	*@parameter: ADD_PATH = scannes all files in the pathcollection. Use one path, for one parametercall.
	*/
	public function add_path($path)
	{
		if($this->scanner)
		$this->scanner->add_path($path);
	}
	
	/**
	*@parameter: PROHIB_PATH = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function prohib_path($path)
	{
		if($this->scanner)
		$this->scanner->prohib_path($path);
	}
	
	/**
	*  Setzt den oeffentlichen Namen einer Spalte. Der bisherige Name fuer dieselbe
	*  Quelle faellt dabei weg - sonst traegt die Tabelle zwei Schluessel auf einen
	*  Wert und fields() meldete eine Spalte mehr statt einer anderen.
	*/
	private function set_alias($name, $target)
	{
		foreach($this->alias as $key => $val)
			if($val === $target)unset($this->alias[$key]);

		$this->alias[$name] = $target;
	}

	/**
	*@parameter: ADD_TAG = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function add_tag($tag)
	{	
		if($this->scanner)
		{
		$this->scanner->add_tag($tag);
		$this->tag_count++;
		}
	}
	
	/**
	*@parameter: ADD_FIX = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function add_fix($fix)
	{
	
		if($this->scanner)
		$this->scanner->add_fix($fix);	
	}
	
	/**
	*@parameter: RELATIVE_PATH (false/true) = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function relative_path($bool)
	{
		
			if($this->scanner)
			$this->scanner->relative_path( (strtolower($bool) == 'true') );
			
	}
	
	/**
	*@parameter: ID_ALIAS (name) = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function id_alias($name)
	{
			
			$this->set_alias($name, 'tag');
	}
	
	/**
	*@parameter: POS_ALIAS (name) = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function pos_alias($name)
	{
			
			$this->set_alias($name, 'pos');
	}
	
	/**
	*@parameter: FILE_ALIAS (name) = void all files in this pathcollection. Use one path, for one parametercall.
	*/
	public function file_alias($name)
	{
			
			$this->set_alias($name, 'file');
	}
	
	/**
	*@function: START_SCAN = fuehrt die Suche mit den gesetzten Pfaden und Kriterien aus.
	*/
	public function start_scan()
	{
			if($this->scanner)
			{
			$this->scanner->seeking();
			$this->result_table = $this->scanner->result();
			}

			/* flash() setzt fin_list auf null; ein leerer Scan ist eine leere
			*  Menge, kein fehlender Wert. */
			if(!is_array($this->result_table))$this->result_table = array();

			/* Ein neuer Scan faengt vorne an. */
			$this->pos_res = 0;

			/* Womit gescannt wurde, entscheidet, was die Menge aussagen kann.
			*  Festgehalten wird der Stand ZUM SCAN - ein spaeteres add_tag
			*  aendert an der vorliegenden Menge nichts. */
			$this->scanned_with_tags = (0 < $this->tag_count);
	}

	public function many()
	{
			return count($this->result_table);
	}

	/* Die Menge wird nur vorwaerts gelesen (STW): moveFirst setzt den Zeiger auf
	*  den Anfang und sagt, ob dort etwas steht, next rueckt eine Zeile weiter.
	*  Damit traegt das uebliche if(moveFirst()) do{ col(..) }while(next()); und
	*  eine zweite Runde ueber dieselbe Menge faengt wieder vorne an - vorher blieb
	*  der Zeiger stehen und die zweite Runde bekam nur den Rest.
	*  moveLast bleibt leer: rueckwaerts wird hier nicht gelaufen.
	*/
	public function next()
	{
	if(count($this->result_table) > $this->pos_res + 1)
	{
	$this->pos_res++;
	return true;
	}
	else
	return false;
	
	}
	public function moveFirst()
	{
	$this->pos_res = 0;
	return (count($this->result_table) > 0);
	}
    	public function moveLast(){}
    	public function col($columnname)
    	{
    	/* Ein unbekannter Spaltenname ist ein Fehler im Aufruf, kein leerer Wert -
    	*  dieselbe Lesart wie in der Oberklasse. */
    	if(!isset($this->alias[$columnname])
    	|| !in_array($this->alias[$columnname], $this->carried(), true))
    		throw new NotAFieldnameException($columnname . " is not part of this recordset");

    	/* Hinter der letzten Zeile gibt es keinen Wert. */
    	if(!isset($this->result_table[$this->pos_res]))return false;

    	if($this->alias[$columnname] == 'number')return $this->pos_res;
    	return $this->result_table[$this->pos_res][$this->alias[$columnname]];
    	}
    	public function &iter(){return $this;}

    	/* Ein Scanner nimmt keine Menge entgegen, er erzeugt eine. Der Aufruf bleibt
    	*  folgenlos - aber nicht stumm, sonst sucht jemand den Fehler an der
    	*  falschen Stelle. */
    	public function set_list(&$value)
    	{
    		global $logger_class;
    		if(is_object($logger_class))
    			$logger_class->setAssert('FileService.set_list: der Scanner erzeugt seine Menge'
    				. ' selbst und nimmt keine entgegen - der Aufruf bleibt folgenlos'
    				. ' (PlugIn/plugin_fileservice.php:set_list)', 0);
    	}
    	/* Welche inneren Spalten diese Menge wirklich traegt.
    	*  Ohne Suchwort hat der Scanner zu 'tag' und 'pos' nichts zu sagen; statt
    	*  seine Platzhalter ('no tag parameter', 0) als Daten weiterzureichen,
    	*  nennt die Menge die beiden Spalten gar nicht erst. fields() ist damit
    	*  eine Funktion der Konfiguration, nicht eine Eigenschaft der Klasse -
    	*  wer col('match') auf einem Scan ohne Suchwort ruft, bekommt einen
    	*  Fehler statt eines Satzes, der wie ein Fund aussieht. */
    	private function carried()
    	{
    		$res = array('file', 'number');

    		if($this->scanned_with_tags)
    		{
    			$res[] = 'found';
    			$res[] = 'tag';
    			$res[] = 'pos';
    		}

    		return $res;
    	}

    	public function fields()
    	{
    		$carried = $this->carried();
    		$res     = array();

    		foreach($this->alias as $name => $target)
    			if(in_array($target, $carried, true))$res[] = $name;

    		return $res;
    	}

    	/* Was in den Spalten steht. 'number' ist der laufende Zaehler, 'pos' die
    	*  Zeilennummer im Dokument - beide ganzzahlig, der Rest Text. */
    	public function datatype($columnname)
    	{
    		if(!isset($this->alias[$columnname])
    		|| !in_array($this->alias[$columnname], $this->carried(), true))return false;

    		switch($this->alias[$columnname])
    		{
    			case 'number':
    			case 'pos':    return 'integer';
    			default:       return 'string';
    		}
    	}
    	public function getAdditiveSource(){}
}
?>
