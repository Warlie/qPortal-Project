<?PHP

/**
*ContentGenerator
*
* Generates a logfile
*
* @-------------------------------------------
* @title:Logger
* @autor:Stefan Wegerhoff
* @description: haelt das Log im Speicher, schreibt es auf Wunsch in eine Datei und
*               meldet Zuhoerer an, die ihr eigenes Log mit eigenem Level fuehren
*
*	Drei Abnehmer (STW, 2026-09-10):
*
*	  Datei       nur, wenn [log] active - die Absturzkopie. Faellt das ganze System,
*	              gibt es ohnehin keine Antwort, sondern eine 500.
*	  Speicher    der globale Puffer. Gesammelt wird nur, wenn es jemand will: die
*	              Datei ist an, oder __give_log steht vorn im Rumpf (index.php schaut
*	              nach, bevor die erste Zeile geschrieben wird). Sonst wird gar kein
*	              Eintrag gebaut - ein Eintrag kostet Zeit (debug_backtrace).
*	  Zuhoerer    angemeldet per Plugin-Aufruf mit Name und Level:
*
*	                <object id="lausch" name="Logger" src="PlugIn/plugin_log.php">
*	                  <remote name="Logger.listen.name">mein_log</remote>
*	                  <remote name="Logger.listen.level">6</remote>
*	                  <remote name="Logger.listen" />
*	                </object>
*
*	              Jeder Zuhoerer hat sein EIGENES Array und sein eigenes Level,
*	              unabhaengig vom globalen - nach oben begrenzt durch [log] listen_max
*	              (leer = wie level): die Zeilen geben Einblick. Wer sich unter einem
*	              schon angemeldeten Namen anmeldet, faengt mit einem leeren Array an -
*	              vermutlich wird ein Skript ein weiteres Mal aufgerufen, und den Neuen
*	              interessieren die Ergebnisse seines Handelns.
*	              Gelesen wird klassisch: moveFirst(), col('msg'|'level'|'index'),
*	              next() - so, wie XMLDO eine Ergebnismenge durchlaeuft. Jeder Zuhoerer
*	              hat seinen eigenen Zeiger; was spaeter dazukommt, sieht next() auch.
*/
require_once("plugin_interface.php");

class Logger extends plugin 
{

	//reihe
	var $param = array();
	var $bool = false;
	var $res = array(); 	
var $reqire = array();
var $content = array();
var $obj = null;
var $tag;
var $request_name = "i";
var $doctype = 'xml';
var $case_folding = 0;
var $page_id = "";
var $setOutput = false;
var $importance = 50;
private $time = false;
private $mem = false;
private $pos = false;

/* Der Zuhoerer, fuer den DIESES Objekt steht, und sein Zeiger. */
private $listen_name = null;
private $cursor = -1;

public static bool $active = false;      // Datei
public static bool $collect = false;     // Speicher - jemand will das Log (__give_log)
public static int $listenMax = 5;        // Obergrenze fuer das Level eines Zuhoerers
public static string $logPath = "template/log.txt";
public static ?string $giveLogName = null; // welchen Zuhoerer __give_log ausgibt, null = global

private static array $log_buffer = ['global' => []];
private static string $current_channel = 'global';
private static array $listeners = [];     // name => ['level' => int, 'entries' => [...]]

public static function openChannel(string $name): void
{
    self::$log_buffer[$name] = [];
}

public static function setChannel(string $name): void
{
    if (!array_key_exists($name, self::$log_buffer))
        self::$log_buffer[$name] = [];
    self::$current_channel = $name;
}

public static function getChannel(string $name): array
{
    return self::$log_buffer[$name] ?? [];
}

public static function closeChannel(string $name): void
{
    unset(self::$log_buffer[$name]);
    if (self::$current_channel === $name)
        self::$current_channel = 'global';
}

/**
*	Einen Zuhoerer anmelden - Name und Level. Ein schon bekannter Name faengt mit einem
*	leeren Array an. Das Level wird auf listenMax gekappt; zurueck kommt das, was gilt.
*/
public static function register(string $name, $level): int
{
    $level = min(intval($level), self::$listenMax);
    self::$listeners[$name] = ['level' => $level, 'entries' => []];
    return $level;
}

/** Der Zuhoerer hoert auf zu sammeln; was er hat, bleibt lesbar. */
public static function silence(string $name): void
{
    if (isset(self::$listeners[$name]))
        self::$listeners[$name]['level'] = -1;
}

public static function unlisten(string $name): void
{
    unset(self::$listeners[$name]);
}

public static function listened(string $name): array
{
    return self::$listeners[$name]['entries'] ?? [];
}

/** Der Text, den __give_log ausgibt - Zeile fuer Zeile wie frueher in der Datei. */
public static function giveLogText(?string $name = null): string
{
    $zeilen = is_null($name) ? (self::$log_buffer['global'] ?? []) : self::listened($name);
    $text = '';
    foreach ($zeilen as $z)
        $text .= $z['msg'] . "\n";
    return $text;
}

	function __construct(){}

		/**
		*@parameter: imp = gives value of importance
		*@-------------------------------------------
		*/
		//parameterausgabe
		public function setImportance($imp, $time=false, $memory=false, $pos = false)
		{
			$this->importance = $imp;
			$this->time = $time;
			$this->mem = $memory;
			$this->pos = $pos;
		}

		/**
		*@parameter: LOG = gives value out in log-file
		*@-------------------------------------------
		*/
		//parameterausgabe
		public function setlog($log)
		{
			
			if(self::$active)
				return $this->write_file($log,self::$logPath);
			else
				return false;
		}
		
		/**
		*	Der Kopf eines Laufs. Er faengt den globalen Puffer neu an - aber nur, wenn
		*	jemand das Log will; sonst entsteht auch keine Kopfzeile.
		*/
		public function setstart($log)
		{
			if(!self::$active && !self::$collect)
				return false;

			self::$log_buffer[self::$current_channel] = [['msg' => $log, 'level' => 0]];

			if(self::$active)
				return $this->start_file($log,self::$logPath);

			return true;
		}
		
		
		/**
		*
		*@-------------------------------------------
		*/
		//parameterausgabe
		public function getAdditiveSource(){}
		
		/**
		* @parmeter LOG = write to logfile
		* @parameter importance = number from 0 to infinity(ok 50) lower numbers a more important
		*/

		public function setAssert($log,$importance)
		{
		/* Wer will diesen Eintrag? Der globale Puffer (und die Datei) nach dem globalen
		*  Level - aber nur, wenn ueberhaupt gesammelt wird. Jeder Zuhoerer nach seinem
		*  eigenen. Will ihn niemand, wird er gar nicht erst gebaut. */
		$global = ($this->importance >= $importance) && (self::$active || self::$collect);

		$zuhoerer = [];
		foreach (self::$listeners as $name => $l)
			if ($l['level'] >= $importance)
				$zuhoerer[] = $name;

		if(!$global && !$zuhoerer)
			return;

		 $mem = '';
		 $pos = '';
		 if($this->mem)$mem = " [" . memory_get_peak_usage(true) . " Bytes]";
		 if($this->pos)
		  {
			$data = debug_backtrace();
			$pos = "\n(" . $data[0]['file'] . " )";
		  }

		$entry = $log . $pos . $mem;

		if($global)
		{
			self::$log_buffer[self::$current_channel][] = ['msg' => $entry, 'level' => $importance];
			$this->setlog($entry);
		}

		foreach ($zuhoerer as $name)
			self::$listeners[$name]['entries'][] = ['index' => count(self::$listeners[$name]['entries']),
			                                         'msg'   => $entry,
			                                         'level' => $importance];
		}

		/**
		*	Als Zuhoerer anmelden (Plugin-Aufruf: Logger.listen.name, Logger.listen.level,
		*	Logger.listen). Dieses Objekt liest danach das Array unter diesem Namen.
		*/
		public function listen($name, $level)
		{
			global $logger_class;

			$name = trim((string) $name);
			if($name === '')
				return false;

			/* erst die Zeile, dann die Anmeldung - sonst stuende sie als erster Eintrag
			*  im eigenen Array, und der gehoert den Folgen des Handelns */
			$gilt = min(intval($level), self::$listenMax);
			if(is_object($logger_class))
				$logger_class->setAssert('Logger: Zuhoerer "' . $name . '" angemeldet, level ' . $gilt
					. (intval($level) > $gilt ? ' (gekappt von ' . intval($level) . ', listen_max)' : ''), 5);

			self::register($name, $level);
			$this->listen_name = $name;
			$this->cursor = -1;

			return true;
		}

	/* Klassisch lesen - dieselbe Folge wie bei einer Ergebnismenge:
	*  if(moveFirst()) do { col(...) } while(next()); */
	private function eintraege(): array
	{
		return is_null($this->listen_name) ? [] : self::listened($this->listen_name);
	}

	public function moveFirst()
	{
		$this->cursor = 0;
		return isset($this->eintraege()[0]);
	}

	public function moveLast()
	{
		$this->cursor = count($this->eintraege()) - 1;
		return $this->cursor >= 0;
	}

	public function reset()
	{
		return $this->moveFirst();
	}

	public function prev()
	{
		if($this->cursor > 0)
			$this->cursor--;
		return isset($this->eintraege()[$this->cursor]);
	}

	/* Rueckt nur vor, wenn dort ein Eintrag steht. Sonst bliebe der Zeiger nach dem
	*  Ende der Leseschleife HINTER dem Platz stehen, an dem der naechste Eintrag
	*  landet - und ein spaeteres next() uebersprange ihn. So sieht ein Zuhoerer auch,
	*  was nach seinem letzten Lesen dazukam. */
	function next()
	{
		if(!isset($this->eintraege()[$this->cursor + 1]))
			return false;
		$this->cursor++;
		return true;
	}

	public function col($columnName)
	{
		$e = $this->eintraege()[$this->cursor] ?? null;
		if(is_null($e))
			return false;
		if(!array_key_exists($columnName, $e))
		{
			$text = $columnName . ' is not part of this recordset (index, msg, level)';
			if(class_exists('NotAFieldnameException'))
				throw new NotAFieldnameException($text);
			throw new Exception($text);
		}
		return $e[$columnName];
	}

	public function fields()
	{
		return ['index', 'msg', 'level'];
	}

	/* Ein Logger nimmt keine Quelle entgegen. */
	public function set_list(&$value){}
				

	/* ⚠ Kein echo: es landete mitten in der Antwort (CLAUDE.md). */
	function write_file($content,$pos){
       $fs = @fopen($pos,'a');

if(!$fs){
	error_log('Logger: ' . $pos . (file_exists($pos) ? " isn't writable" : " isn't available"));
	return false;
}
       $bool = fwrite($fs,$content . "\n");

		fclose($fs);
		
		return $bool;
	}
	
	function start_file($content,$pos){
        $fs = @fopen($pos,'w');

if(!$fs){
	error_log('Logger: ' . $pos . (file_exists($pos) ? " isn't writable" : " isn't available"));
	return false;
}
        
                $bool = fwrite($fs,$content . "\n");

		fclose($fs);
		
		return $bool;
	}
	
	function check_type($type)
	{
	if($type == "OUT")return true;
	if($type == "IN")return true;
	if($type == "NAME")return true;
	if($type == "ATTRIB")return true;
	
	return parent::check_type($type);
	}

	
	function decription(){return "no description avaiable!";}
}
?>
