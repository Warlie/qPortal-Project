<?PHP

/**
*ContentGenerator
*
* Generates content by reading and writing trees
*
* @-------------------------------------------
* @title:XMLDO
* @autor:Stefan Wegerhoff
* @description: Treeobject, transforms trees to tables and back
* --------------------------------------------
* @function: MOVEFIRST = goes to first record
* @function: MOVELAST = goes to last record
*/

require_once("/PlugIn/plugin_interface_js.php");

class JSDisplay_Selectbox extends plugin_js
{
private $test = 0;
private $rst;
private $uri;
private $template;
private $tag_name;
private $nodeId = '';
private $nodeBarId = '';
private $full_uri = array();

	function JSDisplay_Selectbox(/* System.Parser */ &$back, /* System.CurRef */ &$treepos)
	{
		
		$this->back= &$back;
		$this->treepos = &$value;
		//$this->id = $value; , &$id
		
		
	}
	
			
	/**
	*@function: MOVEFIRST = goes to first record
	*/
		
	public function moveFirst()
	{$this->pos = 0;}
	
	/**
	*@function: MOVELAST = goes to last record
	*/
	public function moveLast()
	{$this->pos = count($this->table) - 1;}
	
	/**
	*@function: HAS_TAG = returns a boolean value refering to the seeking tag, descripted by xpath 
	* TODO muss auf xpath erweitert werden
	*/
	public function has_Tag()
	{
	
	if(is_null($this->template))
	{
		return 'false';
	}
	
			
	$tmpstamp = $this->generator()->XMLlist->position_stamp();
				
	$this->generator()->XMLlist->change_URI($this->template);
	$this->generator()->XMLlist->set_first_node();
					//$generator->XMLlist->cur_idx(). "id \n";
					
					//$generator->XMLlist->seek_node($this->tag[$this->order[$i]]['xpath']);
	$orginal = &$this->generator()->XMLlist->show_xmlelement();
	$clone = &$this->find($orginal,$value);
	if(is_null($clone->name) )
	{
		$mytemp = "false";
	}
	else
	{
		$mytemp = "true";
	}	
		
	$generator->XMLlist->go_to_stamp($tmpstamp);
	return $mytemp;
	}
	
	/**
	*
	*@-------------------------------------------
	*/
	//parameterausgabe
	public function getAdditiveSource(){ return 'script/JSDisplayLib.js';}
	
	/**
	*@parameter: LIST = gets an object to receive data
	*/
	public function set_list(&$value)
	{
			//echo 'booh';
	if(is_object($value))
	{
		$this->rst = &$value;
	}
	}
	
	/**
	*@function: ITER = gives out a object to LIST-parameter
	*/
	public function &iter()
		{
		//echo 'booh' . $this->test++ . '<br>';
		return $this;}


		public function col($columnName)
		{
		}

	public function set_qualified_URI( $uri )
	{
	
		$this->full_uri = explode('.',$uri); 
		
	}
	
	public function set_Node_ID( $id )
	{
	
		$this->nodeId = $id; 
		
	}
	
	public function set_Bar_Node_ID( $id_bar )
	{
	
		$this->nodeBarId = $id_bar; 
		
	}
	
	public function build_script($pfad)
	{
	
	$funct = 'funct' . 'ion';
	$clas = 'cla' . 'ss';
	
		$namespace = '';
	
	
	for($i = 0 ; $i < count($this->full_uri);$i++)
	{
		//-------------build namespaces to to test ---------
		

		if($i == 0)
		{
		$namespace .= $this->full_uri[0];
		
		$res = "\n" .
		" if(!$namespace) $namespace = {};" . "\n" .
   		" else if (typeof $namespace != 'object')" . "\n" .
   		" throw new Error('$namespace allready exists and os not an object'); \n";
		}
		else
		{
		
		$namespace .= '.' . $this->full_uri[$i];
		$res .= "\n" .
		" if(!$namespace) $namespace = {};" . "\n" .
   		" else if (typeof $namespace != 'object')" . "\n" .
   		" throw new Error('$namespace allready exists and os not an object'); \n";
		}
	

   		
   		
	}
	
		$res .= "
//-------------------Display Element-----------------------------------
		
  $namespace.clazz = $funct(control)
  {
  
  this.toString = $funct(){return 'Display Element';}
   	this.pfad = $pfad;
  	this.dataref = control;
  	this.myNodeid = '" . $this->nodeId . "';
   	this.myBarNodeid = '" . $this->nodeBarId . "';
  	this.mySVGObj = null;
  	this.mySVGBarObj = null;
  	this.isActive = 0;
  	this.dataref.addListener(this);
  	this.display = new Array();
  	
  	
  	//elementclazz to display entryelements
  	var visualElement = $funct()
  	{
  	this.bag = new Array();

  	this.add= $funct(obj)
 		{

 			
 				this.bag[this.bag.length] = obj;
 
 			
 		};
 	
 	this.transit = $funct( xpoint , ypoint )
 	{
 		for(i = 0 ; this.bag.length > i ; i++ )
 		{
 			this.bag.transit(xpoint, ypoint);
 		}
 	};
 	
 	
 	
 	this.init = $funct()
 	{
  		for(j = 0 ; this.bag.length > j ; j++ )
 		{	
 			this.bag[j].init();
 		}		
 	};
 	
  	};
  	
  	var stdclazz = $funct(){};

  	this.display = new Array();
  	
  	this.event = $funct( type , message )
  	{
  	
	if( !(message instanceof de.auster_gmbh.library.tools.eventObject) )
	{
 		if(type == 'init')
  		{

		}
		
		if(type == 'onClick_event' && message.id == 'foldingbutton_display')
  		{

  		}
  		
		if(type == 'onClick_event' && message.id.substring(0, 6) == 'theme_')
  		{
 
  		}

		//
		if(type == 'onClick_event' && message.id.substring(0, 5) == 'OBJID')
  		{

  		}
  		

  		
  	}
  	else
  	{
  	  	/*primary funct workbench */
  		var num = 0;
  		var arg = new Array();
  		arg[0] = message;
  		
  		if(-1 != (num = type.indexOf('.')))
  		{
  			var funct = type.substring(num + 1, type.length);
  			
  			if(this[funct] != undefined )
  				this[funct].apply(this,arg);
  		}	
  	}

  		
  	}
  	
  	/* functions of the class Controlelement */
  	
  	
  	/* onFocus */
  	this.onFocus = $funct()
  	{
  		if(!de.auster_gmbh.controllelement_toolbox.tools.bar_status(this.container))
  		de.auster_gmbh.controllelement_toolbox.tools.openControl(this.container);
  	}
  	
  	 
  	/* onCollapse */
  	this.onCollapse = $funct()
  	{
  		if(de.auster_gmbh.controllelement_toolbox.tools.bar_status(this.container))
  		de.auster_gmbh.controllelement_toolbox.tools.openControl(this.container);
  	}

  	/* setID */
  	this.setID = $funct(myid)
  	{
  	
  	/*
  		currentid[0] = myid.getContext();
  		//alert(myid.getContext());
  		//currentNode = this.semref.getRef1()
  		
  		if(!this.container[1]['NodeInfo'].closed)
  		{
  		
  		this.container[1]['NodeInfo'].deleteID('entry');
  		
  		var fake = new Array();
  		fake[0] = new Array();
  		fake[1] = 1;
  		fake[0][0] = this.container[1]['NodeInfo'];
  		de.auster_gmbh.objectelement_toolbox.tools.functions.info(this.container[1]['NodeInfo'],this.semref,this.mySVGObj,
			$pfad);
			
  		}
  	*/
  		
  	}
  	
  	var fireEvent =  $funct( type , message )
  	{
  		this.dataref.fireEvent( type , message );
  	}
  	
  } 
  
  $namespace = new $namespace.clazz($pfad);
  //$namespace($pfad);
  
  //$pfad.addListener($namespace);

  
		"; 
	
		return $res;
	}
	
	public function __toString(){return 'javascript_display_element';}	
}
//booh
?>
