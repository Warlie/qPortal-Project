<?PHP

/**
*
*
* convert csv notation to table
* @-------------------------------------------
* @title:CSV_Converter
* @autor:Stefan Wegerhoff
* @description: Databaseobject, needs only a columndefinition to receive data from other object
*
*/
require_once("plugin_interface.php");

class CSV_Converter extends plugin 
{
private $tag_name = false;
private $allocation = array();
private $std_default = array();
private $heads = array();
private $body = array();
private $count = 0;
private $cur = 0;

var $rst = null;
var $into = array();
var $table = array();
//var $obj = null;
var $back =  null;
var $content = null;

var $param = array();
var $images = array();
var $tag;

	function CSV_Converter(/* System.Parser */ &$back, /* System.Content */ &$content)
	{
		
		
		$this->back= &$back;
		$this->content = &$content;
	
		
	}
	
	public function col($columnname)
	{
	if($this->rst)
	{	
		//echo "body[ $columnname ][ " . $this->cur . " ]=" .$this->body[$columnname][$this->cur] . "\n";
	  return $this->body[$columnname][$this->cur];
	}
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	/**
	*@function: col_name = creates a table, fetched from column in rst
	*@param : name : name of col to fetch
	*/
	public function col_name($name)
	{

		$list_of_csv = array();
		if($this->rst)
		{
			$this->rst->moveFirst();
			do {
			$list_of_csv[] = $this->rst->col($name);
			} while ($this->rst->next());
			//var_dump($list_of_csv);
				
			$allRows = explode("\\r\\n",$list_of_csv[0]); //Delimiter falsch
				
			$delimiter = $this->parse_Head($allRows[0]);

			for($i = 1;$i < count($allRows);$i++)
			{
					
				$this->parse_body($allRows[$i],$delimiter,$i);

			}

			$this->count = count($allRows) - 1;
		}
	}

	private function parse_Head($line)
	{
		$delimiter = '';
		if(!(false === strpos($line, ',')))$delimiter = ',';
		if(!(false === strpos($line, ';')))$delimiter = ';';
		if(!(false === strpos($line, '|')))$delimiter = '|';
		if(!(false === strpos($line, '	')))$delimiter = '	';
		//echo $delimiter;
		$this->heads = explode($delimiter,$line);

		for($i = 0; count($this->heads) > $i ; $i++)
		{
			$this->heads[$i] = trim($this->heads[$i]);
			$this->body[$this->heads[$i]] = array();
		}

		return $delimiter;
	}

	private function parse_body($line,$delimiter,$i)
	{
				if(trim($line)<>'')
				{
				
				$cur_row = explode($delimiter,$line);
				
							for($j = 0;$j<count($cur_row);$j++)
							{

								$this->body[$this->heads[$j]][$i - 1] =  $cur_row[$j] ;


							}
							

				}


	}
	

	public function r_default($value)
	{
	  if(!($this->tag_name === false))
	  $this->std_default[$this->tag_name] = $value;
	
	}
	
	
	function getAdditiveSource(){;}
	protected function moveFirst(){if($this->rst){$this->cur = 0; return true;}else return false;}
    	protected function moveLast(){if($this->rst){$this->cur = $this->count - 1;return true;}else return false;}
    	
	public function next()
	{
		if($this->rst)
			if($this->cur + 1 < $this->count)
			{		
			
				$this->cur++;
				return true;
			}else return false;
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
    	


}
?>
