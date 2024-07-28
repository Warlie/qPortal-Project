<?PHP

/**
*ContentGenerator
*
*
* @-------------------------------------------
* @title:GK3
* @autor:Stefan Wegerhoff
* @description: it is a converter and calculates Gauss Krueger coordinates to an address, has to set in line to an emitter and a listener
*
*/
require_once("plugin_interface.php");

class GK3 extends plugin 
{

	//reihe
	var $param = array();
	var $bool = false;
	var $res = array(); 	
var $reqire = array();
var $content = array();
var $obj = null;
var $tag;
var $test = false;

	function GK3(){}
	
	function set($type, $value)
	{
		parent::set($type, $value);
		//echo $type . ' ' . $value;

		if($type == "TEST")
		{
			
			$this->test = true;		
					
		}
		
		/**
		*@parameter: LIST = gets the incomming object
		*/

		if($type == "LIST")
		{
			
			if(is_object($value))
			{
				$this->obj = &$value;
				
			}
		}
		/**
		*@parameter: ITER = sets the outcomming object
		*/
		if($type == "ITER"){$this->param_out($this);$this->get_require_fields();}
		/**
		*@parameter: COL = gives out data for the columnname
		*/
		if($type == "COL")
		{
			
			if(!isset($this->res[$value]))
			{
				$this->obj->set('COL',$value);
			
				$tmp=$this->obj->out();
				
			}
			else
			{
				
				$tmp = $this->res[$value];
				
			}
			//echo '<br>' . $tmp . ' : ' . $value;
			$this->param_out($tmp);
		}

		
/*
		if($type == "TAG_IN")
		{
			
			$this->cur = $value;
			$this->order[count($this->order)] = $value;
			
		}

		if($type == "TAG_OUT")
		{
			
			$this->cur = '';
			
			
		}
		*/
		/**
		*@parameter: REQUIRE = column to use for the request on maps.google.com. You should use Street, number, zip, town and contry to identify the position you want.
		*/
		if($type == "REQUIRE")
		{
			
			$this->reqire[count($this->reqire)] = $value;
			
			
		}
		/**
		*@parameter: CONTENT = fist contentvalue descriptes x gk3 coordinate, second y gk3 and third is normaly 0 for z (in maps.google.com not available)
		*@-------------------------------------------
		*/
		if($type == "CONTENT")
		{
			
			$this->content[count($this->content)] = $value;
			
			
		}
		/*
		if($type == "VALUE")
		{
			
			$this->tag[$this->cur]['value']=$value;
			
			
		}
		*/
		if($type == "MANY")
		{
			

				$this->obj->set('MANY',null);
			
				
				$this->param_out($this->obj->out());
		}
		
		if($type == "RUN")
		{
			
			
		}
	}
	
	function check_type($type)
	{
	if($type == "SQL")return true;
	if($type == "XMLTEMPLATE")return true;
	if($type == "COL")return true;
	//if($type == "")return true;
	return parent::check_type($type);
	}

	function next(){$return = $this->obj->next();if($return)$this->get_require_fields();return $return;}

	function get_require_fields()
	{
		
		for($i = 0;$i < count($this->reqire);$i++)
		{
			
			$this->obj->set('COL',$this->reqire[$i]);
			$this->param[$i] = $this->obj->out();		
			//echo $this->reqire[$i] . ' : ' . $this->param[$i];
		}
		for($i = 0;$i < count($this->content);$i++)
		{
			$this->obj->set('COL',$this->content[$i]);
			$vari = $this->obj->out();		
			
			$bool = ((is_null($vari) || $vari == '') && !$bool);
			if($bool)
				{
			//echo '<br>drin ' . implode(' ', $this->param) . '<br>';		
					$res = $this->get_GK3($this->param);
					
						for($i = 0;$i < count($this->content);$i++)
							{
								//echo $res[$i]; 
								$this->res[$this->content[$i]] = $res[$i];
							}
					
					break;
				}
		}
		

	}
	
	function get_GK3($param)
	{
		if(!is_array($param))return array();

		if($this->test)return array(1.0,1.0,0);
		
		$position =implode(' + ',$param);
		$obj = new xml();
		
		$timeout = 0;
		do{
		//@warning please use your own google.maps account in line 194
		//echo 'hier' . $position . '<br>';	
		$geo = $this->load_URL(
		'http://maps.google.com/maps/geo?q=' . urlencode($position) .'&output=xml&key=ABQIAAAAMlJ51SK8m8ZFVYfbj-qHEBSARgMYODk2SMXmtBlJM4iIluVMXxTTioREse3KHVZkmpVa-hjjiy9SKQ'
		);
		
		$geo = str_replace(
                        array('Ü','Ä','Ö','ü','ä','ö','ß'),
                        array('Ue','Ae','Oe','ue','ae','oe','ss'),
			$geo);
		
		
		if($timeout++ > 100)return array();
		}while($geo == ''||is_null($geo));
		
		
		
		
		$obj->load_Stream($geo);
		
		
		$obj->set_first_node();
		$obj->seek_node('CODE');
		if($obj->show_cur_data() <> 200)return array();
		//$obj->child_node(0);     
		//$obj->child_node(2);
		//$obj->child_node(2);
		//$obj->child_node(0);
		$obj->seek_node('COORDINATES');
		//echo "<br>" . $obj->cur_node();
		if($obj->cur_node() <> 'COORDINATES')return array();
		
		//echo "<br>" . $obj->show_cur_data();
		$res = explode(',',$obj->show_cur_data());

		
		
$brDezimal = floatval($res[0]);
$laDezimal = floatval($res[1]);


		
$a = 6377397.155;
$e = 0.003342773154;
$sy = 3;
$rho = 180 / pi();
$e2 = 0.0067192188;
$c = 6398786.849; 

//Berechnung:
 
$bf = $brDezimal / $rho;  

$g = 111120.61962 * $brDezimal - 15988.63853 * sin(2*$bf) + 16.72995 * sin(4*$bf) - 0.02178 *
sin(6*$bf) + 0.00003 * sin(8*$bf);  

$co = cos($bf);  

$g2 = $e2 * ($co * $co);  

$g1 = $c / sqrt(1+$g2);  

$t = sin($bf) / cos($bf);

$dl = $laDezimal - $sy * 3;  

$fa = $co * $dl / $rho;  

$y = $g + $fa * $fa * $t * $g1 / 2 + $fa * $fa * $fa * $fa * $t * $g1 * (5 - $t * $t + 9 * $g2) /
24;  

$rm = $fa * $g1 + $fa * $fa * $fa * $g1 * (1 - $t * $t + $g2) / 6 + $fa * $fa * $fa * $fa * $fa * $g1 * (5 - 18 * $t * $t * $t * $t * $t * $t) / 120;  

$x = $rm + $sy * 1000000 + 500000; 
		
		//echo $test;
		return array($x,$y,0);
	}
	function load_URL($URL){

        $fs = fopen($URL,'r');

        while(!feof($fs)) 
		{
                $content .= fread($fs,4096);
        	}
	fclose($fs);

	return $content;

	}
	
	function decription(){return "no description avaiable!";}
}
?>
