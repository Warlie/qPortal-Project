<?PHP

/**
* @-------------------------------------------
* @title:Aggregation
* @autor:Stefan Wegerhoff
* @description: eine Zeile je Gruppe (wie GROUP BY). Die Eingabe ist nach den Gruppenspalten
*               vorsortiert. Zahlenspalten werden summiert, Text verschiedenwertig mit Komma
*               verbunden - entschieden nach datatype() der Quelle.
*
* Gelesen wird wie eine Ergebnismenge: moveFirst() sammelt die erste Gruppe, next() die naechste
* und gibt false, wenn keine mehr kommt; col() liest die aktuelle.
*
* Behoben 2026-10-11 (plugin_aggregationTest):
*  - die LETZTE Gruppe ging verloren: beim Einsammeln meldete next() schon false, obwohl ihre
*    Daten gerade gesammelt waren (traf die Cashflow-API: der letzte Teilnehmer fehlte still)
*  - moveFirst() setzte den Zustand nicht zurueck: ein zweiter Durchlauf war falsch
*/

class Aggregation extends plugin 
{

private $alias;
private $filename; 
private $hash; 
private $output;

var $rst = null;
private $criteria = [];
//var $obj = null;
private $group = [];
private $dataSet = [];
private $done = false;
private $end = false;


	function __construct()
	{
		
		
	}
	
	
	/*
	*	@param columnname : name of the specific column as criteria
		@param type : TEXT
	**/
	public function token ( $columnname, $add )
	{
		
		$this->criteria[] = array( "name" => $columnname, "datatype" => $this->datatype($columnname), "add" => $add );
		
	}
	
	public function group($columnname)
	{
		$this->group[] = ["name" => $columnname, "value" => false];
		//$this->group[$columnname] = false;
	}
	
	public function aggregate()
	{
		
	}
	
	public function col($columnname)
	{
		//echo $columnname . "\n";
	if($this->rst)
	{
		
		if(!$this->done)
		{
		$this->init();
		$this->collect_to_next();
		}
		

		
	$filteredData = array_filter($this->dataSet, function($element) use ($columnname) {
			return $element["name"] === $columnname;
		});
		
	
	$res = array_shift($filteredData);
//var_dump($res);

	if(!is_null($res))
	{
		//echo $this->transform($res['value'], $this->datatype($columnname)) . " \n";	
		return $this->transform($res['value'], $this->datatype($columnname));	
	}

	return "";
	}
	
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}


	function getAdditiveSource(){;}
	public function moveFirst()
	{
		$this->done = false;
		$this->end = false;
		$this->dataSet = [];
		if(!$this->rst || !$this->rst->moveFirst())
		{
			$this->end = true;
			return false;
		}
		$this->init();
		$this->collect_to_next();
		return true;
	}

	/**
	* naechste Gruppe. $this->end heisst: die Quelle ist erschoepft - die zuletzt gesammelte
	* Gruppe ist dann schon gelesen worden, eine weitere gibt es nicht.
	*/
	public function next()
	{
		if(!$this->rst || $this->end)
			return false;
		$this->collect_to_next();
		return true;
	}
	
    	public function set_list(&$value)
    	{

    	if(is_object($value))
	{
		$this->rst = &$value;
		
		
	}
	else
	return 'no element received';
    	}
    	
    	public function datatype($columnname){return $this->rst->datatype($columnname);}
    	
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}
    	
    	/**
    	* sammelt EINE Gruppe ab der aktuellen Zeile der Quelle. Danach steht die Quelle auf der
    	* ersten Zeile der naechsten Gruppe, oder $this->end ist gesetzt.
    	*/
    	private function collect_to_next()
    	{
    		// collects a new dataset
    		$this->dataSet = [];
    		$fields = $this->rst->fields();
    		
    		foreach ($fields as $key => $data)
			$this->dataSet[$key] = ["name" => $data, "value" => []];
		
		// fills it with a collection until next is false or group characteristica change
    		do
    			{
    				for($i = 0; $i < count($this->dataSet); $i++)
    				{
    					
    					$this->dataSet[$i]['value'][] = $this->rst->col($this->dataSet[$i]["name"]);
    					//echo "name: " . $data["name"] . " value:" . $this->rst->col($data["name"]) . ";\n ";
    				}
    				
    				
    				//echo "next\n ";
    				$this->end = !$this->rst->next();

    			} // nextCollectionBreak will check group characteristica and will return false in case, it has changed
    			while(!$this->end && $this->nextCollectionBreak());

    			// shows first time run
    			$this->done = true;
    	}
    	
    	
    	private function transform($valueArray, $format)
    	{
    		switch(strtoupper(strtok($format, '('))
    			)
	  	  {
	  	  case 'varchar' :
	  	  case 'VARCHAR':
	  	  case 'date' :
	  	  case 'DATE':
	  	  	  
			return $this->join_texts($valueArray);

	  	  	  break;
	  	  case 'TINYINT' :
	  	  case 'SMALLINT' :
	  	  case 'INT' :
	  	  case 'BIGINT' :
	  	  	  return  array_sum(array_map('intval', $valueArray));
	  	  	  break;
	  	  case 'DECIMAL' :
	  	  case 'NUMERIC' :
	  	  case 'FLOAT' :
	  	  case 'REAL' :
	  	  	  // auf 6 Stellen wie Formula: 661 x 8265.88 gab sonst 5463746.6799999 (2026-10-18)
	  	  	  return round(array_sum(array_map('floatval', $valueArray)), 6);
	  	  	  break;
	  	  default:
			return $this->join_texts($valueArray);
	  	  }
    	}
    	/**
    	* Texte verschiedenwertig mit Komma verbinden. Leere Werte (auch nur Leerzeichen) zaehlen
    	* nicht: bis 2026-10-12 stand ein Komma vorn, sobald eine Zeile der Gruppe leer war
    	* (Ledger: die Soll-Zeilen haben keinen Verwendungszweck).
    	*/
    	private function join_texts($valueArray)
    	{
    		$res = array();
    		foreach($valueArray as $value)
    			if(trim((string)$value) !== '') $res[] = $value;
    		return implode(',', array_unique($res));
    	}

    	private function init()
    	{
    		for($i = 0; $i < count($this->group); $i++)
    			{
    				$this->group[$i]['value'] = $this->rst->col($this->group[$i]['name']);
    			}
    	}
    	
    	private function nextCollectionBreak()
    	{
    		$res = true;
    		for($i = 0; $i < count($this->group); $i++)
    				{
    					// if inequal, the return will be false and the value will be updated
    					if($this->group[$i]['value'] != $this->rst->col($this->group[$i]['name']))
    						{
    							$res = false;
    							$this->group[$i]['value'] = $this->rst->col($this->group[$i]['name']);
    						}
    				}
    		return $res;
    	}
}
?>
