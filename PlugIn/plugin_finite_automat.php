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

class FiniteAutomat extends plugin 
{

private $alias;
private $filename; 
private $hash; 
private $output;

private $selectiontree = array();
private $ref = array();
private $unique_num = 0;
private $collectable = array();
private $next_available = false;
private $current_line = array();
private $fulltable = array();

var $rst = null;

//var $obj = null;



	function __construct()
	{
		
		
	}
	
	public function setNodes($names)
	{
		$arr = explode(",",$names);
		foreach($arr as $value)$this->setNode( $value);
	}
	
	public function setNode($name)
	{
		if(isset($ref[$name]))throw new Exception("already exists");
			$this->ref[$name] = $this->unique_num++;
	}
	
	public function setGraph($ante, $succ, $valid, $cmd)
	{
		
		//echo "($ante, $succ)" . $valid . "\n";
		if(is_array($valid))
			$arg = $valid;
		else
			$arg = array($valid);
		foreach($arg as $val)
		{
		 $res = &$this->createTreeSearch($val);
		 $res[$this->ref[$ante]] = array();
		 $res[$this->ref[$ante]][] = $this->ref[$succ];
		 $res[$this->ref[$ante]][] = $cmd;
		 if(substr($cmd, -1) !=')' && $cmd !='')
		 	 $this->current_line[$cmd] = '';
		} 
	}
	
	public function setStringGraph($node, $cmd)
	{
		
		$this->collectable[$this->ref[$node]] = $cmd;
		if(substr($cmd, -1) !=')')
		 	 $this->current_line[$cmd] = '';
		
	}
	
	private function &createTreeSearch($valid)
	{

		$arg = str_split($valid);
		
		$tmp = &$this->selectiontree;
		$swap;
		foreach($arg as $char)
		{
			//echo $char . '-';
			if(!isset($tmp[ '§' . $char ]))
				$tmp['§' . $char] = array();
				$swap = &$tmp['§' . $char];
				unset($tmp);
				$tmp = &$swap;
				unset($swap);
		}	
		
		return $tmp;
			
		
		
	}
	

	
	public function setColumns($columns){}
	
	public function checkString($str)
	{
		$str = str_replace(" ", "", $str);
		//echo $str . "\n";
		$arg = str_split($str);
		$state = 0;
		$compare = 0;
		$context_add = "";
		$collect1 = '';
		$tmpstr = '';
		$collect2 = '';
		//var_dump($this->selectiontree);
		$tmp = &$this->selectiontree;
		$swap;
		for($pos=0;$pos < count($arg);$pos++)
			{
				
				$tmpstr .= $arg[$pos];
				
				/* search in a char array for pattern */
				for($compare=0;true;$compare++)
				{
					//echo "" . $arg[$pos + $compare] . "";
				if(isset($tmp['§' . $arg[$pos + $compare]]))
				{
					$collect1 .= $arg[$pos + $compare];
					//echo $arg[$pos + $compare];
					$swap = &$tmp['§' . $arg[$pos + $compare]];
					unset($tmp);
					$tmp = &$swap;
					unset($swap);
				}
				else
				{
					
						//$collect2 = $arg[$pos + $compare];
					
					//mo and monthly 
					break;
				}
				}
				
				if(isset($this->collectable[$state]))
					{
						if(!isset($tmp[$state]))
							{
								$tmp = &$this->selectiontree;
								
								$collect2 .= $tmpstr;
								$tmpstr = '';
								$collect1 = '';
								//echo "\n'$collect1;$collect2'($compare)\n";
								continue;
							}
							else
							{
								$this->saveSet($collect2, $this->collectable[$state]);
								//echo "col2: $collect2 \n";
								$collect2 = '';
							}
							
					}
				
				//echo "($state-->";
				if(!isset($tmp[$state]))
				{
					$commend = 'text not accepted:';
					$commend .= array_search($state, $this->ref) . "($state) has no graph '$tmpstr$collect1' on position $pos : ..." ;
					throw new Exception($commend);
				}
				if(!$this->saveSet($collect1, $tmp[$state][1]))
				{
					$commend = 'Transducing not successful:';
					$commend .= array_search($state, $this->ref) . "($state) command '" . $tmp[$state][1] . " did not allow '$collect1'";
					throw new Exception($commend);
				}
				$hlp = $tmp[$state][1];
				$state = $tmp[$state][0];
				//echo "$state)['$collect1']{" . $hlp . "}\n";
				$tmpstr = '';
				$collect1 = '';
				$tmp = &$this->selectiontree;
				$pos = $pos + $compare - 1;
				$compare = 0;
				
			}
		
		
	}
	
	
	private function saveSet($string, $cmd)
	{
		if($cmd == '')return true;
		if($cmd == 'next()' && $this->next_available)
			{
				$this->next_available = false;
				$this->fulltable[] = $this->current_line;
				//echo "next()\n";
				return true;
			}

		if(isset($this->current_line[$cmd]))
			{
				if(trim($string) == '')return true;
				$this->next_available = true;
				$this->current_line[$cmd] = $string;
				return true;
			}
		return true;
		//echo "\nTo table: $string ($cmd)\n";
	}
	
	public function getResult(){return $this->fulltable;}
	
	public function showAll()
	{
		var_dump($this->fulltable);
		//var_dump($this->ref);
		//var_dump($this->selectiontree);
	}
	
	
	public function col($columnname)
	{

	if($this->rst)return $this->rst->col($columnname);
	
	
	  return 'no dataset';
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		return $this;}

	

	
	
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
    	
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}

}
?>
