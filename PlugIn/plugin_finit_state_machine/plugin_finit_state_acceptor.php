<?PHP

/**
*
*
* creates a menu
* @-------------------------------------------
* @title:DBO
* @autor:Stefan Wegerhoff
* @description: Finite State Machine
*
*/
require_once("PlugIn/plugin_interface.php");

class Acceptor extends plugin 
{

private $alias;
private $filename; 
private $hash; 
private $output;

private $alphabet = null;
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

	/**
	*	@param json : string
	*   configuration via json
	*	{ Sigma : [ "a", "b", "c", "d" ],
	*		Gamma: ["1","2","3","4"],
	*		states : [ state1, state2 ],
	*		start : state1,
	*		stop : state2,
	*		delta : [{"state" = "state1", "sigma" = "a", "to" = "state2"}] // contains tupel of ( state x Sigma ) -> state
	*		omega : [{"state" = "state1", "sigma" = "a", "gamma" = "1"}]} // contains tuopel of ( state x Sigma ) -> Gamma
	*/
	public function setJsonDescription($json)
	{

		$decoded_json = json_decode($json);
		
		if (JSON_ERROR_NONE !== json_last_error()) {
        throw new RuntimeException('Unable to parse response body into JSON: ' . json_last_error());
        	}
		
		var_dump($decoded_json);
		if($decoded_json->Sigma)
			$this->alphabet = $decoded_json->Sigma;
		
		if($decoded_json->states)
			foreach($decoded_json->states as $state)
				$this->setNode(trim($state));
	/*		
		if($decoded_json->states)
			foreach($decoded_json->states as $state)
				$this->setNode(trim($state));
*/
		if($decoded_json->delta)
			foreach($decoded_json->delta as $delta)
			{
				if($delta->sigma == '')
					
				$this->setGraph($delta->state, $delta->to, $delta->sigma, '');
				//var_dump($delta);
			}
		
	}
	
	/**
	*	@param names : string of names, sperated by comma
	*   creates nodes, adressable by names,
	*	setNote is used to add every name.
	*/
	
	
	public function setNodes($names)
	{
		$arr = explode(",",$names);
		foreach($arr as $value)$this->setNode( trim($value));
	}
	
	/**
	*	@param name : a simple name
	*
	*	Set a single node with a name
	*	is set in a table
	*/
	
	
	public function setNode($name)
	{
		if(isset($ref[$name]))throw new Exception("already exists");
			$this->ref[$name] = $this->unique_num++;
	}
	
	/**
	*	@param ante : a name to draw a graph from
	*	@param succ : a name to dreo a graph to
	*	@param valid : list of elements for transition 
	*	@param cmd : command line for a structured output
	*	creates a graph between two nodes. It is organized as a table as well
	*/
	
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
	
	
	/**
	*	@param node : is a node's name
	*	@param cmd : command line for the node
	*
	*/
	public function setStringGraph($node, $cmd)
	{
		
		$this->collectable[$this->ref[$node]] = $cmd;
		if(substr($cmd, -1) !=')')
		 	 $this->current_line[$cmd] = '';
		
	}
	
	/**
	*
	*	@param valid : does someth9ing
	*/
	private function &createTreeSearch($valid)
	{

		$arg = str_split($valid); // splits string into an array
		
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
	
	/**
	*
	*	@param str : string for parsing
	*	applies the machine on a string
	*/
	
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
		$tmp = &$this->selectiontree;
		$swap;
		$check_str = "";
		$process = "";
		$context = "";
		$context_add = "";
		
		for($pos=0;$pos < count($arg);$pos++)
			{
				
				//$tmpstr .= $arg[$pos];
				
	
				
				/* search in a char array for pattern */
				while(true) //for($compare=0;true;$compare++)
				{
					$tmpstr .= $arg[$pos + $compare];
					
					//echo "" . $arg[$pos + $compare] . "";
				if(isset($tmp['§' . $arg[$pos + $compare]]))
				{

					
					$collect1 .= $arg[$pos + $compare];
					//echo $arg[$pos + $compare];
					$swap = &$tmp['§' . $arg[$pos + $compare]];
					unset($tmp);
					$tmp = &$swap;
					unset($swap);
					$check_str += '.§' + str.charAt(pos + compare);
				}
				else
				{
					
						$compare = max(0, $compare - 1);
						//$collect2 = $arg[$pos + $compare];
						$context_add += "[" + $pos + "+" + $compare + " {" + $check_str;
					//mo and monthly 
					
					//mo and monthly 
					break;
				}
				$compare++;
				}
				
				$check_str = "";
				
				if(isset($this->collectable[$state]))
					{
						$process = "collectable";
						if(!isset($tmp[$state]))
							{
								$tmp = &$this->selectiontree;
								
								$context += $arg[pos];
								$this->saveSet($arg[pos], $this->collectable[$state]);
								$compare = 0;
								$tmpstr = '';
								$collect1 = '';
								$context_add .= "(" . $arg[$pos + $compare + 1] . ")]\n";
								continue;
								/*
								$collect2 .= $tmpstr;
								$tmpstr = '';
								$collect1 = '';
								//echo "\n'$collect1;$collect2'($compare)\n";
								continue; */
							}
							else
							{
								$this->saveSet("", $tmp[$state][1]);
								$context_add .= ".[" . $tmp[state][0] . ", " . $tmp[state][1] . "]]\n";
								//echo "col2: $collect2 \n";
								$collect2 = '';
							}
							
					}
					else
						if($tmp[state])
						{
							$context_add .= ".[" . $tmp[$state][0] . ", " . $tmp[$state][1] . "]";
							if($tmp[$state][1])
								$process = "has_process:" + $tmp[$state][1];
							else
								$process = "common_path";
							$this->saveSet(substr($pos, $pos + $compare + 1), $tmp[$state][1]);
							
						}
				
				//echo "($state-->";
				if(!isset($tmp[$state]))
				{
					$commend = 'text not accepted:' . " " . "\n"; 
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
	
	
	/**
	*
	*	@param string : text to do something with
	*	@param cmd : command for process the string
	*/
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
	
	/**
	*	@return gives back an array as a result
	*/
	public function getResult(){return $this->fulltable;}
	
	/**
	*	gives out a dump of the result
	*/
	public function showAll()
	{
		var_dump($this->fulltable);
		var_dump($this->ref);
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
