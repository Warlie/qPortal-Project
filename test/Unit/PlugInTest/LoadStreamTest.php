<?php



use PHPUnit\Framework\TestCase;
				require_once('PlugIn/plugin_LoadStream.php');

                      

class TestPlugin extends plugin 
{


	protected $rst;
	private $names = ['tbl_dls_Objekte.Strasse', 'tbl_dls_Objekte.Plz', 'tbl_dls_Objekte.Ort', 'tbl_dls_Objekte.GPSPositionX', 'tbl_dls_Objekte.GPSPositionY'];                

	private $input = [];	
	private $output = [];	
	
	private $max  = 1;
	private $pos = 0;
	private $groups = array();

	function __construct()
	{	
		$this->input[] = ['tbl_dls_Objekte.Strasse'=>'Robert-Stolz-Str. 18a', 'tbl_dls_Objekte.Plz'=>'42929', 'tbl_dls_Objekte.Ort'=>'Wermelskirchen', 'tbl_dls_Objekte.GPSPositionX'=>'Schrott', 'tbl_dls_Objekte.GPSPositionY'=>'Schrott']; 
		$this->input[] = ['tbl_dls_Objekte.Strasse'=>'Alte Kölner Str. 5 / Kölner Str. 71', 'tbl_dls_Objekte.Plz'=>'42897', 'tbl_dls_Objekte.Ort'=>'Remscheid', 'tbl_dls_Objekte.GPSPositionX'=>'Schrott', 'tbl_dls_Objekte.GPSPositionY'=>'Schrott']; 
		$this->input[] = ['tbl_dls_Objekte.Strasse'=>'Honsberger Str. 45', 'tbl_dls_Objekte.Plz'=>'42857', 'tbl_dls_Objekte.Ort'=>'Remscheid', 'tbl_dls_Objekte.GPSPositionX'=>'Schrott', 'tbl_dls_Objekte.GPSPositionY'=>'Schrott'];
		$this->input[] = ['tbl_dls_Objekte.Strasse'=>'', 'tbl_dls_Objekte.Plz'=>'42857', 'tbl_dls_Objekte.Ort'=>'Remscheid', 'tbl_dls_Objekte.GPSPositionX'=>'Schrott', 'tbl_dls_Objekte.GPSPositionY'=>'Schrott'];
		$this->input[] = ['tbl_dls_Objekte.Strasse'=>'', 'tbl_dls_Objekte.Plz'=>'', 'tbl_dls_Objekte.Ort'=>'', 'tbl_dls_Objekte.GPSPositionX'=>'Schrott', 'tbl_dls_Objekte.GPSPositionY'=>'Schrott'];
		$this->input[] = ['tbl_dls_Objekte.Strasse'=> null, 'tbl_dls_Objekte.Plz'=>null, 'tbl_dls_Objekte.Ort'=>null, 'tbl_dls_Objekte.GPSPositionX'=>'Schrott', 'tbl_dls_Objekte.GPSPositionY'=>'Schrott']; 
		$this->input[] = ['tbl_dls_Objekte.Strasse'=>'H. Bleser', 'tbl_dls_Objekte.Plz'=>'42349', 'tbl_dls_Objekte.Ort'=>'Wuppertal', 'tbl_dls_Objekte.GPSPositionX'=>'Schrott', 'tbl_dls_Objekte.GPSPositionY'=>'Schrott'];
	}
	
			
	/**
	*@function: MOVEFIRST = goes to first record
	*/
		
	public function moveFirst()
	{

		$this->pos = 0;
		return true;
	
	}

	
	/**
	*@function: MOVELAST = goes to last record
	*/
	public function moveLast()
	{
		$this->pos = count($this->input) ;
		return true;
	}
		
	public function next()	
	{
		if($this->pos + 1 < count($this->input)) 
			{
			$this->pos++;

			return true;
			}

		return false;
	}
	
		
	/**
	*
	*@-------------------------------------------
	*/
	//parameterausgabe
	public function getAdditiveSource(){}
	

	

	public function set_list(&$value)
	{
		if(is_object($value))
		{
			$this->rst = &$value;
		}
	}
	
	
	public function &iter()
	{return $this;}
	
	
 
public function col($columnName)
{
	if(array_key_exists($columnName, $this->input[$this->pos]))
		return $this->input[$this->pos][$columnName];
}






		public function test()
		{
			 $this->rst->moveFirst();
			
			do {
				$row = [];
				foreach ($this->names as $value)
				{
				//var_dump($value);
					$row[$value] = $this->rst->col($value);
				}
				$this->output[] = $row;
    		} while ($this->rst->next());
    		print_r($this->output);
    		/*
			foreach ($this->input as $input_element)
			{

			} 
			*/
		}
		
		public function fields()
		{
	$res = array();
	foreach ($_SESSION as $key => $value)
		{
			$res[] = $key;
		}
	return $res;


		}
}        
                                
final class LoadStreamTest extends TestCase
{
	

 

	
	
	

	
    public function testLoadStream() : void
{
	
	 // Test überspringen, falls eine bestimmte Bedingung erfüllt ist
    $this->markTestSkipped('Dieser Test testet die Kommunikation mit openMaps und wird übersprungen.');
	
	//$test_string = "*?__find_node(model=xpath_model,namespace='',query='wubb')=wup";

	//echo "Next test with:" . $value . "\n";
	
	$test = new TestPlugin();
	
	$stream = new LoadStream();
	
	$stream->set_list($test->iter());
	$test->set_list($stream->iter());
	
	$stream->configuration('{"injection":["tbl_dls_Objekte.Strasse", "tbl_dls_Objekte.Plz", "tbl_dls_Objekte.Ort"]
		,"projection":{"tbl_dls_Objekte.GPSPositionX": "lat", "tbl_dls_Objekte.GPSPositionY":	"lon"},
		"fail":{"tbl_dls_Objekte.GPSPositionX": "lat", "tbl_dls_Objekte.GPSPositionY":	"lon"}
		}');
	//$stream->setInjection(['tbl_dls_Objekte.Strasse', 'tbl_dls_Objekte.Plz', 'tbl_dls_Objekte.Ort']);
	
	//$stream->setProjection(['tbl_dls_Objekte.GPSPositionX'=>'lat', 'tbl_dls_Objekte.GPSPositionY'=>	'lon']);
	
	//$stream->load([]);
    			
	$test->test();
       
    $this->assertSame(1, 1);

    

    //$this->assertSame(18, $user->age);
    //$this->assertEmpty($user->favorite_movies);
}


    
}

?>