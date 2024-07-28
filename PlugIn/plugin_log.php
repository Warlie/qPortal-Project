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
			
			
			return $this->write_file($log,"template/log.txt");
		}
		
		/**
		*@parameter: LOG = gives value out in log-file
		*@-------------------------------------------
		*/
		//parameterausgabe
		public function setstart($log)
		{
			

			
			//return $this->start_file($log,"template/log.txt");
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

		$this->setlog($log . $pos . $mem);
		}


	public function moveFirst(){}
	public function moveLast(){}
	public function set_list(&$value){}
				

	function write_file($content,$pos){
		return false;
       $fs = fopen($pos,'a');


                $bool = fwrite($fs,$content . "\n");
if(!$bool)echo "nö";
		fclose($fs);
		
		return $bool;
	}
	
	function start_file($content,$pos){
		return false;
        $fs = fopen($pos,'w');


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
