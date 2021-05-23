<?PHP

/**
*ContentGenerator
*
* replaces values
*
* @-------------------------------------------
* @title:Selector
* @autor:Stefan Wegerhoff
* @description: it is a converter and replaces values, which send between an emitter and a listener
*/
require_once("plugin_interface.php");

class Selector extends plugin 
{

	//reihe
	var $param = array();
	var $bool = false;
	var $res = array(); 	
var $list = array();
var $content = array();
var $content;
var $key;

var $obj = null;
var $tag;
var $test = false;
//http://www.sight-board.de/_editor/dataProvider/data.php?external=34
	
	
	function set($type, $value)
	{
		parent::set($type, $value);
		//echo $type . ' ' . $value;

		if($type == "TEST")
		{
			
			$this->test = true;		
					
		}
		
		/**
		*@parameter: LIST = gets an object to receive data
		*/
		if($type == "LIST")
		{
			
			if(is_object($value))
			{
				$this->obj = &$value;
				
			}
		}
		/**
		*@parameter: ITER = gives out a object to LIST-parameter
		*/
		if($type == "ITER"){$this->param_out($this);$this->get_require_fields();}
		
		/**
		*@parameter: COL = gives out data to an field
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
				$this->obj->set('COL',$value);
			
				$mykey=$this->obj->out();
				
				$tmp = $this->res[$value][$mykey];
				
			}
			//echo '<br>' . $tmp . ' : ' . $value;
			$this->param_out($tmp);
		}

		/**
		*@---------sequence---------
		*@parameter: TAG_IN = opens a definition for a column, which is available about the value name.
		*/
		if($type == "TAG_IN")
		{
			
			$this->cur = $value;
			$this->order[count($this->order)] = $value;
			
		}
		
		/**
		*
		*@parameter: CONTENT = needs first the content, which describes columnname in connected object
		*/
		if($type == "CONTENT")
		{
			
			$this->content = $value;
			
			
		}
		
		/**
		*
		*@parameter: KEY = needs as second entry in sequence. Value send from connected object will be replaced. KEY is the original value and VALUE the surrogate
		*/
		if($type == "KEY")
		{
			
			$this->key = $value;
			
			
		}

		/**
		*
		*@parameter: VALUE = needs as third entry in sequence. VALUE is the surrogate, for the KEY value
		*/
		if($type == "VALUE")
		{
			
			$this->res[$this->content][$this->key]=$value;
			
			
		}

		/**
		*
		*@parameter: TAG_OUT = close the current columndefinition. Needs no value.
		*/
		if($type == "TAG_OUT")
		{
			
			$this->cur = '';
			$this->content = '';
			$this->key = '';
		}
		
		/**
		*
		*@parameter: MANY = requests the many of rows
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
		
		

		
		
	}



	
	
	function decription(){return "no description avaiable!";}
}
?>
