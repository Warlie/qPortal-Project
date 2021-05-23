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

class Date_time extends plugin 
{
private $tag_name = false;
private $datetime = array();
private $col_name = array();
private $rel = array();
var $rst = null;
var $into = array();
//var $obj = null;
var $back =  null;
var $content = null;

private $start_timestamp = 0;
private $interval_timestamp = 0;
private $default ="d" ;
private $root = null;
private $format = 'Y-m-d H:i:s';
private $myleafs = 0;
private $add_leafs = 0;

var $param = array();
var $images = array();
var $tag;

	function Date_time(/* System.Parser */ &$back, /* System.Content */ &$content)
	{
		
		
		$this->back= &$back;
		$this->content = &$content;
		$this->root = new Interval_tree(0, $this);
	
		
	}
	
	public function col($columnname)
	{

	if($this->rst)
	{
	  if(isset($this->rel[$columnname]))
	  {

		if($res = $this->root->get_relation($this->rst->col($this->rel[$columnname])))return $res;
		return $this->default;

	  }
	  /*if(isset($this->datetime[$columnname]))
	  {
	  	
		$day = $this->rst->col($this->datetime[$columnname]['day']);
		$month = $this->rst->col($this->datetime[$columnname]['month']);
		$year = $this->rst->col($this->datetime[$columnname]['year']);
		$hour = $this->rst->col($this->datetime[$columnname]['hour']);
		$minute = $this->rst->col($this->datetime[$columnname]['minute']);
		
		$res = "$year-$month-$day $hour:$minute:00";
		return $res;

		//$day = $this->rst->col($this->datetime[$columnname]['day']);
		
	  }
	*/
	  return $this->rst->col($columnname);
	}
	  return 'no dataset';
	}
	
	public function add_threshold_leafs($leafs)
	{ $this->add_leafs = $leafs; }

	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}

	public function set_datetime($datetime, $col_name)
	{
		$this->datetime[$col_name] =  new DateTime($datetime);


	}



	public function set_format( $format )
	{
		$this->format = $format;
	}

	/**
	* @function set_timedate_cycle
	* Defines a interval, which will be repeated 
	*/
	public function set_timedate_cycle($start, $stop, $default)
	{	
		$start_obj = new DateTime($start);
		$stop_obj = new DateTime($stop);
		$this->default = $default; 

		$this->start_timestamp = $start_obj->getTimestamp(); 
		$this->interval_timestamp = $stop_obj->getTimestamp() - $start_obj->getTimestamp();
		$this->root->set_shiftAndModul($this->start_timestamp, $this->interval_timestamp);
	}

	public function set_residue_class($start, $stop, $ident)
	{
		$this->myleafs++;
		$start_obj = new DateTime($start);
		$stop_obj = new DateTime($stop);
		

		//echo round(log(( 2 * $this->myleafs ) + $this->add_leafs));
		$this->root->set_Resude_Interval_Param($start_obj->getTimestamp(), $stop_obj->getTimestamp(), $ident,round(log(( 2 * $this->myleafs ) + $this->add_leafs)));
//		echo $this->root->tree() . "\n";
	}

	public function set_column_relation($first, $sec)
	{
		$this->rel[$sec] = $first;
	}
	
	function getAdditiveSource(){;}
	protected function moveFirst(){if($this->rst)return $this->rst->moveFirst(); else return false;}
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
    	
    	public function fields(){if($this->rst) return $this->rst->fields();else return array();}
    	
	public function entries(){return $this->myleafs;}

	public function test()
	{
		//echo "test:\n";
		echo $this->root->tree() . "\n";
	}

}

class Residue_Interval 
{
	private $ident;
	private $residue;
	private $interval;
	private $command = 0;
	
	function __construct($residue, $interval, $ident) 
	{
		$this->ident = $ident;
		$this->residue = $residue;
		$this->interval = $interval;
		//echo 	" " . $this->residue. ", " . $this->interval . " - ";
 
    	}

	public function greaterThan($test)
	{
		return $this->biggestRC() <  $test->residue();
	}
	
	public function biggestRC(){return $this->residue + $this->interval;}

	public function residue()
	{
		return $this->residue;
	}

	public function biggerthan(&$obj){return $this->residue() > $obj->biggestRC();}

	public function id(){return $this->ident;}

	public function get_id_to_RC($number)
	{

		/*echo " if(" . $this->residue .  " <=  $number && " . $this->biggestRC() . "  >= $number ) ";*/
		if( $this->residue <=  $number  && $this->biggestRC() >= $number) 
			return $this->ident;

		/*echo "gefunden " . $this->residue . " ($number) -" ;*/
		return false;

	}

	public function set_cmd($cmd){$this->command = $cmd;}
	public function get_cmd(){return $this->command;}

	public function tree()
	{
	$left = $this->residue;
	$right = $this->interval + $this->residue;	
	
	return "{[$left,$right], " . $this->ident . "}";
	}


}

//so, idee ich brauche einen gespeicherten wert in der Mitte

class Interval_tree 
{

	private $shift; 
	private $modul;
	private $right = null;
	private $left = null;
	private $line = -1;
	private $deep = -1;
	private $mainobj = null;
	private $lowerNode = array();
	private $command = 0;

	function __construct($deep, &$obj) 
	{
		$this->deep = $deep;
		$this->mainobj = &$obj;
    	}

	public function set_shiftAndModul($shift, $modul)
	{
		$this->shift = $shift;
		$this->modul = $modul;
	}
	
	public function get_relation($datetime)
	{
		 

		$td = new DateTime($datetime);
		$residue = $td->getTimestamp() - $this->shift;

		while($residue < 0)$residue += $this->modul; 

		$residue = $residue % $this->modul;

		$tmp = $this->get_id_to_RC($residue);
		//echo $tmp . "($residue) ";
		return $tmp;
	}

	public function set_Resude_Interval_Param($start, $stop, $ident, $max_deep)
	{

		$residue1 = $start - $this->shift;
		$residue2 = $stop - $this->shift;


		while($residue1 < 0)$residue1 += $this->modul; 
		while($residue2 < 0)$residue2 += $this->modul; 

		$residue1 = $residue1 % $this->modul;
		//$residue2 = $residue2 % $this->modul;

		if($residue1 > $residue2)echo $ident . " ist kein gueltiges Interval";
		
		$undef = new Residue_Interval($residue1, $residue2 - $residue1 - 1 , $ident);

		$this->nextBranch($undef, $max_deep);

	}

	public function &nextBranch(&$undef, $max_deep)
	{
//echo "max deep is " . $max_deep . "\n";
		 $leftright = 1;
		if($this->greaterThan($undef))$leftright = 0;
		
			// liefert ein Object zurueck, das ersetzt wurde und nun neu zugeordnet werden muss
			$fb = &$this->setBranch($undef, $leftright, $max_deep);

		if(!is_null($fb))
		{

//		echo "\nrightleft: $leftright cmd:" . $fb->get_cmd() . "! \n";

			if($fb->get_cmd() + 1) //cmd = 1
			{
				
				$this->line = $fb->biggestRC();
						
				return $this->setBranch($fb,$leftright ^ 1, $maxdeep);// noch mal ansehen
			}
			if($fb->get_cmd() - 1)echo "booh2";

		}
			//echo "links $fb \n";
		

/*
	//	echo "nextBranch " . $undef->residue() . " " . $undef->id() . " \n";
		if(!isset($this->right))
		{
	//echo "booho" . $undef->residue() . " ";
		//echo $undef->biggestRC();
			$this->line = $undef->biggestRC();
			$this->right = $undef;

		}
		else
		{
		if($this->greaterThan($undef))
		{
	//echo "nextBranch" . $this->line .  " < " . $undef->residue(). "  \n";

	echo " (<) deep:" . $this->deep . " und max_deep: $max_deep \n";
			if(!isset($this->left))
			$this->left = $undef;
			else
			{
			
				if (get_class($this->left) == 'Interval_tree'){ $this->left->nextBranch($undef, $max_deep);}
				if (get_class($this->left) == 'Residue_Interval')
					{

					
				
					$tmp = $this->left;
					unset($this->left);
					$this->left = new Interval_tree($this->deep + 1, $this->mainobj);
					$this->left->nextBranch($tmp, $max_deep);
					$this->left->nextBranch($undef, $max_deep);
					}
			
			 
			}

			
		}
		else
		{
	echo " (>) deep:" . $this->deep . " und max_deep: $max_deep \n";
		//echo "nextBranch > " . $undef->residue(). "  \n";
			if (get_class($this->right) == 'Interval_tree')$this->right->nextBranch($undef, $max_deep);
			if (get_class($this->right) == 'Residue_Interval')
				{
					$tmp = $this->right;
					unset($this->right);
					$this->right = new Interval_tree($this->deep + 1, $this->mainobj);
					$this->right->nextBranch($tmp, $max_deep);
					$this->right->nextBranch($undef, $max_deep);
				}
			
			 
		}
		}
		
*/



		
	}

	public function set_cmd($cmd){$this->command = $cmd;}
	public function get_cmd(){return $this->command;}


/**
* 
* 
*
*/
	private function &setBranch(&$interval, $posNode, $maxdeep)
	{



		if($this->deep > 20)return false;

			if(!isset($this->lowerNode[$posNode]))
			{
//				echo " und ende! " . $interval->residue() . " tiefe" . $this->deep . "-- ";
				$this->lowerNode[$posNode] = $interval;
				return null;
			}
			else
			{
				if (get_class($this->lowerNode[$posNode]) == 'Interval_tree')
				{
					$this->lowerNode[$posNode]->nextBranch($interval, $maxdeep);
					return null;
					
				}
				if (get_class($this->lowerNode[$posNode]) == 'Residue_Interval')
				{
						
					$tmp = $this->lowerNode[$posNode];
					unset($this->lowerNode[$posNode]);
					if($maxdeep < $this->deep )
					{
						$this->lowerNode[$posNode] = $interval;

						if($interval->biggerthan($tmp))
						{
//						echo " interval > obj";
						$tmp->set_cmd(1);
						
						}
						
						if($tmp->biggerthan($interval))
						{
//						echo " interval < obj";
						$tmp->set_cmd(-1);
						
						}
					return $tmp;
					}
					




					$this->lowerNode[$posNode] = new Interval_tree($this->deep + 1, $this->mainobj);
					
					//echo " ------------------" . $interval->residue() . "------------------- \n";
					$this->lowerNode[$posNode]->nextBranch($interval, $maxdeep);
					$this->lowerNode[$posNode]->nextBranch($tmp, $maxdeep);
					return null;
				}
			}
	}

	public function get_id_to_RC($number)
	{
		$tmp = 0;
//		echo "\n" . $number . " wird gesucht \n";
//		 echo " i am in(" . $this->line . "), \n";
		if( $this->line >=  $number ){
//echo " links \n" ;
		//for($i=0;$i<2;$i++)
			if(isset($this->lowerNode[0])){		
				 if($tmp = $this->lowerNode[0]->get_id_to_RC($number)){ return $tmp;}}
		}
		else
		{
//echo " rechts \n" ;
			if(isset($this->lowerNode[1])){ /* echo " links \n" ; */ if($tmp = $this->lowerNode[1]->get_id_to_RC($number)){  return $tmp; }}
		  
		}
//echo "\n und nichts gefunden! ";
		return false;

	}

	public function greaterThan(&$test)
	{
		
		if($this->line < 0)
		{
			$this->line = $test->biggestRC();
			return true;
		}
		return $this->line >  $test->residue();
	}

	public function tree()
	{
	$left = "-";
	if(isset($this->lowerNode[0]))$left = $this->lowerNode[0]->tree();
	$right = "-";	
	if(isset($this->lowerNode[1]))$right  = $this->lowerNode[1]->tree();

	return '<' . $this->line . "(" . $this->deep . ")<" . $this->mainobj->entries() . ">>($left,$right)";
	}

}
?>
