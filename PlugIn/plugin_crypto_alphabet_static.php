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

//modul 73

class Alphabet extends plugin 
{
private $list1 = null;
private $list2 = array();
private $numbers = array('0','1','2','3','4','5','6','7','8','9');
private $forward = true;
private $tag_name = false;

var $rst = null;
var $into = array();
//var $obj = null;
var $back =  null;
var $content = null;


var $param = array();
var $images = array();
var $tag;



	function Alphabet(/* System.Parser */ &$back, /* System.Content */ &$content)
	{
		
		$letters_U = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		$letters_l = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'); 
		$sign = array(' ','?','!',',',';','.','-','_','(',')','\n');
		$this->list1 = array_merge($sign,$letters_U, $letters_l);
		
		for($i = 10;count($this->list1) + 10 > $i; $i++)
		{
			$this->list2[$i] = $i . "&";
		}



		
	}
	
	public function setForward(){$this->forward = true;}
	public function setBackward(){$this->forward = false;}

	public function col($col_name)
	{
	if($this->rst)
	{ 
	  if($this->tag_name == $col_name)
	  {
		$value = $this->rst->col($col_name);



	  	
	  	if($this->forward)
		{
		$value = str_replace('0','0&',$value);
		$value = str_replace('1','1&',$value);
		$value = str_replace('2','2&',$value);
		$value = str_replace('3','3&',$value);
		$value = str_replace('4','4&',$value);
		$value = str_replace('5','5&',$value);
		$value = str_replace('6','6&',$value);
		$value = str_replace('7','7&',$value);
		$value = str_replace('8','8&',$value);
		$value = str_replace('9','9&',$value);

	  	  return str_replace($this->list1,$this->list2,$value);
		}
	  	else
		{
		$value = str_replace($this->list2,$this->list1,$value);
		$value = str_replace('0&','0',$value);
		$value = str_replace('1&','1',$value);
		$value = str_replace('2&','2',$value);
		$value = str_replace('3&','3',$value);
		$value = str_replace('4&','4',$value);
		$value = str_replace('5&','5',$value);
		$value = str_replace('6&','6',$value);
		$value = str_replace('7&','7',$value);
		$value = str_replace('8&','8',$value);
		$value = str_replace('9&','9',$value);
	  	  return $value;
		}
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
