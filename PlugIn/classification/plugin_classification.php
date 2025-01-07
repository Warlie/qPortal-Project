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

class Classification extends plugin 
{

private $alias;
private $filename; 
private $hash; 
private $output;

var $rst = null;
private $criteria = [];
//var $obj = null;



	function __construct()
	{
		
		
	}
	
	
	/*
	*	@param columnname : name of the specific column as criteria
		@param $columnname : Name in column
		@param $add : json for deferentiating classification
		@param $datatype : optional standard database formats
		
		datatype will be drawn from source
		
	**/
	public function token ( $columnname, $add , $datatype = null)
	{
		if(is_null($datatype))
		$this->criteria[] = array( "name" => $columnname, "datatype" => $this->datatype($columnname), "add" => $add );
		else
		$this->criteria[] = array( "name" => $columnname, "datatype" => $datatype, "add" => $add );
		
	}
	
	public function col($columnname)
	{
		//echo $columnname . "\n";
	if($this->rst)
	{
		$found_key = array_search($columnname, array_column($this->criteria, "name" ));
	  if($found_key !== false)
	  {
	  	  $element = $this->rst->col($columnname);
	  	  $add = $this->criteria[$found_key]['add'];
	  	  $res = $element;

	  	  switch(strtok($this->criteria[$found_key]['datatype'], '('))
	  	  {
	  	  case 'varchar' :
	  	  	  if(is_null($element))
	  	  	  {
	  	  	  	  $res = null;
	  	  	  }
	  	  	  	else
	  	  	  {
	  	  	  preg_match($add, $element, $matches);
	  	  	  $res = $matches[1];
	  	  	  }
	  	  	  break;
	  	  case 'date' :
	  	  	  $date=date_create($element);
	  	  	  $res = date_format($date,$add);

	  	  	  break;
	  	  default:
	  	  	  var_dump($this->criteria[$found_key]['datatype']);
	  	  }

	  	  

	  return $res;
	  }
	
	else
	return $this->rst->col($columnname);
	}
	
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}

	
	public function alias($column, $alias)
	{
		$this->alias[$alias] = $column;
	}
	
	
	function getAdditiveSource(){;}
	protected function moveFirst()
	{
		
		if($this->rst){
			$res = $this->rst->moveFirst();
			//$this->extract_array();
			return $res; 
			
		}else return false;
	}
    	protected function moveLast(){if($this->rst)return $this->rst->moveLast();else return false;}
    	
	public function next(){if($this->rst)return $this->rst->next();else return false;}
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
    	
    	private function extract_array()
    	{
    		$res = [];
    		foreach ($this->fields() as $value)$res[$value] = $this->rst->col($value);
    		
    		var_dump($res);
    	}
    	/*
    	SQL Numerische Datentypen
    	BIT 	1 	0
TINYINT 	0 	255
SMALLINT 	-32,768 	32,767
INT 	-2,147,483,648 	2,147,483,647
BIGINT 	-9,223,372,036,854,775,808 	9,223,372,036,854,775,807
DECIMAL 	-10^38 + 1 	10^38 – 1
NUMERIC 	-10^38 + 1 	10^38 – 1
FLOAT 	-1.79E+308 	1.79E+308
REAL  	-3.40E+38 	3.40E+38
SQL Datum- und Zeit-Datentypen
Datentyp 	Beschreibung
DATE 	Speichert Datum im Format JJJJ-MM-TT
TIME 	Speichert Zeit im Format HH:MM:SS
DATETIME 	Speichert Datum- und Zeitinformationen im Format JJJJ-MM-TT HH:MM:SS
TIMESTAMP 	Speichert die Anzahl der Sekunden seit der Unix-Epoche (‚1970-01-01 00:00:00‘ UTC)
YEAR 	Speichert das Jahr im 2- oder 4-stelligen Format. Bereich 1901 bis 2155 im 4-stelligen Format. Bereich 70 bis 69, was 1970 bis 2069 entspricht.
SQL Zeichen- und String-Datentypen
Datentyp 	Beschreibung
CHAR 	Feste Länge mit einer maximalen Länge von 8.000 Zeichen
VARCHAR 	Variable Länge mit einer maximalen Länge von 8.000 Zeichen
VARCHAR(max) 	Variable Länge mit angegebener maximaler Zeichenanzahl, in MySQL nicht unterstützt
TEXT 	Variable Länge mit einer maximalen Größe von 2 GB Daten
SQL Unicode Zeichen- und String-Datentypen
Datentyp 	Beschreibung
NCHAR 	Feste Länge mit einer maximalen Länge von 4.000 Zeichen
NVARCHAR 	Variable Länge mit einer maximalen Länge von 4.000 Zeichen
NVARCHAR(max) 	Variable Länge mit angegebener maximaler Zeichenanzahl
NTEXT 	Variable Länge mit einer maximalen Größe von 1 GB Daten
SQL Binäre Datentypen
Datentyp 	Beschreibung
BINARY 	Feste Länge mit einer maximalen Länge von 8.000 Bytes
VARBINARY 	Variable Länge mit einer maximalen Länge von 8.000 Bytes
VARBINARY(max) 	Variable Länge mit angegebener maximaler Byteanzahl
IMAGE 	Variable Länge mit einer maximalen Größe von 2 GB binären Daten
SQL Verschiedene Datentypen
Datentyp 	Beschreibung
CLOB 	Charakter-Großobjekte, die bis zu 2 GB speichern können
BLOB 	Für große binäre Objekte
XML 	Zum Speichern von XML-Daten
JSON 	Zum Speichern von JSON-Daten
*/
}
?>
