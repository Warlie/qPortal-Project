<?PHP

/**
*
*
* creates a menu
* @-------------------------------------------
* @title:DBO
* @autor:Stefan Wegerhoff
* @description: Databaseobject, needs only a columndefinition to receive data from other object
*
*/
require_once("plugin_interface.php");

class LoadStream extends plugin 
{
private $tag_name = false;
private $allocation = array();
private $std_default = array();
private $std_null = array();
private $to_null = array();
private array $injection = [''=>'lat'];
private array $projection = [''=>'lat'];
private array $row = [];
private array $tagNames = [['amenity',0],
	['house_number',0], 
	['road',0], 
	['amenity',0],
	['quarter',0],
	['suburb',0],
	['city_district',0],
	['city',0],
	['town',0],
	['county',0],
	['municipality',0],
	['state',0],
	['ISO3166-2-lvl4',0],
	['postcode',0],
	['country',0],
	['country_code',0],
	['lat',1],
	['lon',1],
	['boundingbox',1]
	];
private array $fail = [];
protected $rst = null;
var $into = array();
//var $obj = null;
var $back =  null;
var $content = null;
var $computeNull = false;


var $param = array();
var $images = array();
var $tag;

	function __construct()
	{
		

		
	}
	
	public function setInjection(array $assArray) : void
	{
		$this->injection = $assArray;
	}
	
	public function setProjection(array $assArray) : void
	{
		$this->projection = $assArray;
	}
	
	public function setFail(array $assArray) : void
	{
		 $this->fail = $assArray;
	}
	
	public function configuration($json)
	{
		$confi = json_decode($json, true);
		$this->setInjection($confi['injection']);
		$this->setProjection($confi['projection']);
		if(array_key_exists('fail', $confi))
			$this->setFail($confi['fail']);
	}
	
	public function col($columnname)
	{
		if(array_key_exists($columnname, $this->projection))
		
			if(array_key_exists($this->projection[$columnname], $this->row))
				return $this->row[$this->projection[$columnname]];
			else
			{
				if(array_key_exists($columnname, $this->fail))
					return $this->fail[$columnname];
			}

		return $this->rst->col($columnname);
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

		
	private function collectArray()
	{
		$result = [];
		foreach ($this->injection as $name)
		{
			if(!is_null($tmp = $this->rst->col($name)) && ($tmp != ''))
			$result[] = $tmp;
		}
		return $result;
	}
		
	public function load(array $queryArray){
		
		$result = [];
		
		if(count($queryArray) == 0)
			    $this->row = [];
		
	array_walk($queryArray, function (&$value) { $value = urlencode($value);});
		
	$search_url = "https://nominatim.openstreetmap.org/search?q=" . implode( '+' ,$queryArray) . "&format=xml&polygon=0&addressdetails=1";

$httpOptions = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Nominatim-Test"
    ]
];

$streamContext = stream_context_create($httpOptions);


   $xml = new DOMDocument();

 
	if(is_null($search_url) || is_null($streamContext))
			    $this->row = [];
    // Load the url's contents into the DOM

    $xml->loadXML(file_get_contents($search_url, false, $streamContext));






$xpath = new DOMXpath($xml);
$places = $xml->getElementsByTagName('place');

foreach ($places as $place) {


	
	
	foreach ($this->tagNames as $tagName)
	{
		if($tagName[1] == 0)
		{
			$element = $xpath->query('./' . $tagName[0], $place);
			if($element->length > 0) $result[$tagName[0]] = $element->item(0)->nodeValue;
		}
		else
		{
			$result[$tagName[0]] = $place->getAttribute($tagName[0]);
		}
		
	
    }


}


    $this->row = $result;
//print_r($xml->saveXML());

//print_r($xml->saveXML());

	} 
	
	
	function getAdditiveSource(){;}
	protected function moveFirst(){if($this->rst && $this->rst->moveFirst()){ $this->Load($this->collectArray()); return true;} else return false;}
    protected function moveLast(){if($this->rst)return $this->rst->moveLast();else return false;}
    	
	public function next(){if($this->rst  && $this->rst->next()){$this->Load($this->collectArray());return true;}else return false;}
    	public function set_list(&$value)
    	{

    	if(is_object($value))
	{
		$this->rst = &$value;
	}
	else
	return 'no element received';
    	}
    	
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}

}
?>
