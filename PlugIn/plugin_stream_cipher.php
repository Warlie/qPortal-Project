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

class Stream_Cipher extends plugin 
{
private $tag_name = false;

private $replace = array();
private $matrix = array();
private $substitution = array();
private $c = array();

protected $block_len = 7;
protected $modul = 73;
protected $mode = 0;


var $rst = null;
var $into = array();
//var $obj = null;
var $back =  null;
var $content = null;


var $param = array();
var $images = array();
var $tag;

	function Linear_Cipher_Block(){}
	
	public function col($col_name)
	{


	if($this->rst)
	{ 
	  if($this->tag_name == $col_name)
	  {

		//if(count($this->replace) < )

		$value = $this->rst->col($col_name);

		$arr = explode('&', $value);
		

		// komischer kommentar
		// while 1
//	_
//     / \
//     | |
//     | |
//    /   \
//    \ | /
//      
//    
//   (.) (.)
//    ) . (
//   (  v  )
//    \ | /


		$len = ceil((count($arr) - 1)/$this->block_len);

		$rest = (count($arr) - 1) % ($this->block_len);
		
		if($rest > 0)
			{
		$pos = count($arr) - 1;
		for($i = $rest;$this->block_len > $i;$i++)$arr[$pos++]='10';

//	echo count($arr) . " und $len rest $rest ";
			}

		for($j = 0;$j < $len;$j++)		 
			$this->cipher($arr, $j * $this->block_len, ($j + 1) *  $this->block_len);

	  	  return implode('&',$arr);
		
	  }
	
	  return $this->rst->col($col_name);
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

	protected function cipher(&$arr, $start, $stop)
	{
	$key = array();
	$res = array();

	


	if($this->mode == 1)for($i = $start; $stop > $i;$i++) $arr[$i] = ($arr[$i] + intval($this->c)) % $this->modul;

//-----------------------------

	for($i = 0; $i < count($this->c);$i++)$arr[$i] = ($this->arr[$i] 	
	
	for($i = (count($this->c) + 1); $i < count($arr);$i++)$key[$i] = 

//-----------------------------
	if($this->mode == 2)for($i = $start; $stop > $i;$i++)$arr[$i] = ($arr[$i] + $this->modul - intval($this->c)) % $this->modul ;

	if($this->mode <> 0)for($i = $start; $stop > $i;$i++)$this->c[$i] = $arr[$i];
	

	}

	protected function swap(&$arr, $first, $second)
	{
		$tmp = $arr[$second];
		$arr[$second] = $arr[$first];
		$arr[$first] = $tmp;
	}

	public function setModul($modul){$this->modul = $modul;}

	public function setCiffreStart($c)
	{
		
		$this->c = explode(';',trim($c));
		
		
	}

	public function getInverse()
	{
	
		$start = array( array(3, 4) , array(1 , 2) );
		$e = array();

		for($i = 0;$i < count($start);$i++)
		{
		$e[$i] = array(); 
		for($j = 0;$j < count($start[$j]);$j++)
		{
			$e[$i][$j] = 0;
			if($i == $j)$e[$i][$j] = 1;
		}
		}	

		

		//echo $this->inversecoeff($start[0][0], $start[1][0], 0);

		for($i = 0;$i < count($start);$i++)
		for($j = 0;$j < $i;$j++)
			{
				$booh  = $this->inversecoeff($start[$i][$i], $start[$i][$j], 0);
				$start[$i][$j] = $start[$i][$i], $start[$i][$j], 0);
			}


	//var_dump($start);
	
	}

	private function inversecoeff($ii, $ij, $to)
	{
		for($i = 0;$this->modul > $i; $i++)
		{

			
			if( (( $ii + ( $ij *  $i)) % $this->modul) == $to )return $i;  
		}
		return -1;
	}


	public function blocklength($len){$this->block_len = $len;}
	public function mode($mode,$direction)
	{
		if("ECB" == $mode)$this->mode = 0;
		if("CBC" == $mode && "ENCODE" == $direction)$this->mode = 1;
		if("CBC" == $mode && "DECODE" == $direction)$this->mode = 2;
	}

	public function iv($iv)
	{
	$tmp = explode(',',trim($iv));
	if(count($tmp) == $this->block_len)
	$this->c = $tmp; 
	}
	public function addFinalSign($string){}
	
	protected function getBlock($block, $length){}	
	public function col_name($name){$this->tag_name = $name;}

	function getAdditiveSource(){;}
	public function moveFirst(){if($this->rst)return $this->rst->moveFirst(); else return false;}
    	public function moveLast(){if($this->rst)return $this->rst->moveLast();else return false;}
    	
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
    	


}
?>
