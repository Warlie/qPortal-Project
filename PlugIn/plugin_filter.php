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
require_once("plugin_interface_multisource.php");

class Filter extends plugin_multisource
{
private $run_count = 0; 
//private $columns = array();
private $tag_name = false;
private $allocation = array();
private $std_default = array();
private $std_null = array();
private $first_use = true;
private $last_use = false;
//var $rst = null;
var $into = array();
//var $obj = null;
var $back =  null;
var $content = null;
var $computeNull = false;
private $rule = array();
private $protected_words = array(
	'not' => 2,
	'to' => 3 , 
	'is' => 5 , 
	'=' => 7 , 
	'>='  => 11 , 
	'<=' => 13  , 
	'>' => 17 , 
	'<' => 19 , 
	'or' => 23 ,
	'xor' => 29 , 
	'and' => 31,
	'set' => 37,
	'find' => 41); //2,     3,     5,     7,    11,    13,    17,    19,    23,    29,    31,    37,    41,    43
private $id_counter = 0;
private $registry = array();



private $testme = array();
var $param = array();
var $images = array();
var $tag;

	function __construct(/* System.Parser */ &$back, /* System.Content */ &$content)
	{
		
		
		$this->back= &$back;
		$this->content = &$content;
		
	}
	
	public function col($columnname)
	{

	if($this->rst)
	{
		//echo "(probe:"  . $columnname . " :"  . $this->rst->col($columnname) . ")";
		if($this->first_use)
			{
				
				if(!$this->check())$this->next();
				$this->first_use = false;
			}

				
		
		//echo $columnname . " :"  . $this->rst->col($columnname) . ", ";
	  return $this->rst->col($columnname);
	}
	  throw new ObjectBlockException('Recordset is missing');
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	
	public function filter($columns, $mode)
	{

		$this->columns = explode ( ',' , $columns);
		
	
	}

/*
regeln
not is null
not is ''

*/
	public function nameColumns($columns, $names, $delimiter)
	{
		
		$keys = explode ( $delimiter , $names);
		$values = explode ( $delimiter , $columns);
		for($i = 0;$i < min( count( $keys ),count( $values )) ; $i++ )
		{
			$this->columns[$keys[$i]] = $values[$i];
		}

	}

	public function rule($rule, $concat = 'AND')
	{
		if(is_null($concat))$concat = 'AND';
		// (feld1 = 'bla') or (feld2 = 'blub')
		$statement = $this->collect($rule,0);
		//echo "-----------\n";
		//var_dump($statement);
		//echo "my\n";
		//var_dump($this->testme);
		//var_dump($this->registry);
		//$tmp = 
		//var_dump($tmp);
		$this->rule[] = array($concat, explode ( ' ' , $rule));
		
		//var_dump($this->rule);
		//$tmp = array('not')
		//$rule
	}
	
	/**
	* 
	*/
	private function &collect($stmnt, $deep)
		{
			$res;// = array();
		$first = strpos($stmnt, '(');
		$last = strrpos($stmnt, ')');
		if($first === false xor $last == false) throw new ObjectBlockException('Bad statement:' . $stmnt);
		if($first === false)
		{
			$first = $last = strlen($stmnt) + 1;
			
			
			//echo " $stmnt $deep \n"; 
			//if(!isset($this->testme[$deep]))$this->testme[$deep] =  array();
			$tmp = $this->build_com($stmnt);
			$this->testme[$deep] = $tmp;
			$res = $tmp;
			

		}
		else
		{
			$res = array();
			$last = strpos($stmnt, ')', $first);
			
			if($first > 0)
			{
				$tmp2 = $this->build_com(substr($stmnt, 0, $first));
				array_unshift($res, $tmp2);
				$this->testme[$deep][] = $tmp2; //substr($stmnt, 0, $first);
			}
				
			array_push($res, $this->collect(substr($stmnt, $first + 1, $last - $first - 1),$deep + 1));
			
			$tmp = &$this->collect(substr($stmnt, $last + 1),$deep);
			
				
			for($i = 0;$i < count($tmp);$i++)
				{
					array_push($res, $tmp[$i]); 
				}
			//	, 
			//	substr($stmnt, $last + 1)  
			

		}
			/*
			for($i = 0;$i < 3;$i++)
				{
					if(strlen($arr[$i]) != 0)$res[$i] = &$this->collect($arr[$i], ( $i ==1 ? $deep + 1:$deep ));
				}
*/
			//echo "on level $deep \n";
			
			
		return $res;
	}
	
	private function &build_com($com)
	{
		$com_id = 1; 
		$arg = array();
		$res = '';
		
		$rule = explode ( ' ' , $com);
		//var_dump($this->protected_words);
		foreach($rule as $value)
			 if(!is_null($tmp = $this->protected_words[ $value]) ){$com_id = $com_id * max(intval($tmp),1) ;}
		 	else
		 	{

		 		$this->registry[$this->id_counter] = $value;
		 		$arg[] = $this->id_counter++;
		 	}
		 	
		//var_dump($com_id, $arg);
		return array('com' => $com_id, 'arg' => $arg, 'res' => $res);
	}
	
	private function check()
	{
		if($this->rst)
		{

		$bool = false;
			// processes rule by rule
			for($i = 0;$i < count($this->rule);$i++)
			{
				$arg = array();
				$command = '=';
				$neg = false;
				$and = true;

				
				//var_dump($this->rule);
				
				//printf("\nzykel\n");
				foreach($this->rule[$i][1] as $value)
				{
/*
printf("\n rule", $i);
echo $i . "\n"; 
	print_r($this->rule[$i]);
printf("value\n");
	print_r($value);
	*/
					$and = ('AND' == trim(strtoupper($this->rule[$i][0])) ); // check for AND
					
					if($value == 'is' || $value == '=') continue;
					if($value == 'not' )
						{
							$neg = true;
							continue;
						}
					if($value == 'empty' )
						{
							$arg[] = '';
							continue;
						}

					if(!is_null($tmp = $this->columns[$value]))
					{
						//if($this->rst->col($tmp))return false;
						$arg[] = $this->rst->col($tmp);
					}
					else
						$arg[] = trim($value, "'");
						

				}
				//echo "var_dump\n";
				//var_dump("arg",$arg, "command", $command, "neg", $neg);

				if($command == '=')
				{
					//echo "\n $i:" . ($neg? "not '":" '") . $arg[0] . "' = '" . $arg[1] . "' ";
					if($arg[0] == $arg[1] xor  !$neg )
					{
						/*
						if($and)
						echo " (false and ";
					else
						echo " (false or ";
						
						if($bool)
						echo " true )";
					else
						echo " false)";
						*/
						
						if($and)
						{
							//echo " =>false \n";
							//$bool = false;
							return false;
							
						}
						elseif(!$and && (!$bool && ($i !=0)))
						{
							//echo " =>false \n";
							//$bool = false;
							return false;

						}
						
					}
					else
					{ /*
												if($and)
						echo " (true and ";
					else
						echo " (true or ";
						
						if($bool)
						echo " true ) ";
					else
						echo " false)  ";
						echo " \n set true\n"; */
						//echo " =>true \n";
						$bool = true; 
						
						
					}
				}	
					
				unset($arg);
			}

			//if($bool)echo " =>true \n"; else echo " =>false \n";
			return $bool;
		}
	}
	
	function getAdditiveSource(){;}
	protected function moveFirst()
	{
		if($this->rst)
		{
			//echo "WuhuW\n";
			$tmp = $this->rst->moveFirst(); 
			if(!$this->check())
				$tmp = $this->next();
			//if($tmp)echo " next=true  ";
			//else echo " next=false  ";
//echo "ende moveFirst\n";
			return $tmp;
		}
		else 
		return false;
	}
	
    	protected function moveLast(){if($this->rst)return $this->rst->moveLast();else return false;}
    	
	public function next()
	{
$this->run_count++;
		//if($this->run_count++ > 0)return false;
		if($this->rst)
			{
				
				//echo "next\n";
			$tmp = $this->rst->next();

			/*
				if(!$this->last_use && !$tmp)
					{
						$this->last_use = true;
						$tmp = true;
					} */
			
			while(!$this->check() && $tmp)
			{
				//if($tmp)echo "avoid (next=true)  \n";
				$tmp = $this->rst->next();
			}/*
if($tmp)echo "avoid (next=true)  \n";
else
echo "avoid (next=false)  \n";
			echo "ende\n"; */
			//echo "(" . $this->run_count . ")\n";
			return $tmp;
			}
		else return false;}
		
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
