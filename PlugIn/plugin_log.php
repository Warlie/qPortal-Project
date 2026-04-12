<?PHP

/**
*ContentGenerator
*
* Generates a logfile
*
* @-------------------------------------------
* @title:Logger
* @autor:Stefan Wegerhoff
* @description: creates a log-file in template folder
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

public static bool $active = false;
public static string $logPath = "template/log.txt";

private static array $log_buffer = ['global' => []];
private static string $current_channel = 'global';

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
			//echo memory_get_peak_usage(true); 
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
		*@parameter: LOG = gives value out in log-file
		*@-------------------------------------------
		*/
		//parameterausgabe
		public function setstart($log)
		{
			

			if(self::$active)			
				return $this->start_file($log,self::$logPath);
			else
				return false;
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
		if($this->importance < $importance)return;
		 $mem = '';
		 $pos = '';
		 if($this->mem)$mem = " [" . memory_get_peak_usage(true) . " Bytes]";
		 if($this->pos)
		  {
			$data = debug_backtrace();
			$pos = "\n(" . $data[0]['file'] . " )";
		  }

		$entry = $log . $pos . $mem;

		// always buffer in memory — available to any channel consumer
		self::$log_buffer[self::$current_channel][] = ['msg' => $entry, 'level' => $importance];

		// physical file write only for hard-crash debugging
		$this->setlog($entry);
		}


	public function moveFirst(){}
	public function moveLast(){}
	public function set_list(&$value){}
				

	function write_file($content,$pos){
       $fs = fopen($pos,'a');

if(!$fs){
	if(!file_exists($pos)){echo $pos . " isn't available"; return false;}
	if(!is_writable($pos)){echo $pos . " isn't writable"; return false;}
	return false;
}
       $bool = fwrite($fs,$content . "\n");

		fclose($fs);
		
		return $bool;
	}
	
	function start_file($content,$pos){
        $fs = fopen($pos,'w');

if(!$fs){
	if(!file_exists($pos)){echo $pos . " isn't available"; return false;}
	if(!is_writable($pos)){echo $pos . " isn't writable"; return false;}
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

	function next(){return false;}

	
	function decription(){return "no description avaiable!";}
}
?>
