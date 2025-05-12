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

class Common_Cipher_Block extends plugin 
{
private $tag_name = false;

private $replace = array();
private $permut = array();
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

	function Common_Cipher_Block(){}
	
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

	if($this->mode == 1)for($i = $start; $stop > $i;$i++)$arr[$i] = ($arr[$i] + intval($this->c)) % $this->modul ;  

//var_dump($this->permut);
		$tmp = 0;
		$tmp2 = 0;
		for($i = 0;count($this->permut) > $i; $i++)
		{
			$tmp = $arr[$start + $this->permut[$i][ count($this->permut[$i]) - 1 ]];
			
//			echo "speichere $tmp (" . ($start + $this->permut[$i][ count($this->permut[$i]) - 1 ]) . ") \n";
			for($j = 0; count($this->permut[$i]) > $j;$j++)
			{


				$tmp2 = $arr[$start + $this->permut[$i][$j] ];
//echo "speichere $tmp2 (" . ($start + $this->permut[$i][$j]) . ") \n";
				$arr[$start + $this->permut[$i][$j] ] = $tmp;
//echo "schreibe $tmp an die gleiche Stelle \n ";
				$tmp = $tmp2;
			}


		}	

		for($j = 0; count($this->substitution) > $j;$j++)
		{
			//echo count($this->substitution);
				$arr[$start + $j ] = ($arr[$start + $j ] + intval($this->substitution[$j])) % $this->modul;

		}
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

	public function setPermutation($permut)
	{
		
		$all_strings = explode(';',trim($permut));
		for($i = 0;count($all_strings) > $i;$i++)$this->permut[$i] = explode(',',$all_strings[$i]);
		
		
	}

	public function setSubstitution($subst)
	{

		if(strlen($subst) > 0)
		{		
		$tmp = explode(',',trim($subst));

		if(($this->block_len > count($tmp)) || (count($tmp) > 0) )
		$this->substitution = $tmp;
		}
		
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
