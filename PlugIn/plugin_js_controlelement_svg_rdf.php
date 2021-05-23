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

require_once("plugin_interface_js.php");

class JSControl extends plugin_js
{
private $test = 0;
private $rst;
private $uri;
private $template;
private $tag_name;
private $nodeId = '';
private $full_uri = array();
private $start_id = '';
private $seeGraphs = false;

	function __construct(/* System.Parser */ &$back, /* System.CurRef */ &$treepos)
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
	public function getAdditiveSource(){ return 'script/JSElementLib.js';}
	
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
	
	public function fields(){if($this->rst) return $this->rst->fields();else return array();}
	
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
	
	public function set_on_graphs()
	{
	
		$this->seeGraphs = true; 
		
	}
	
	public function set_Node_ID( $id )
	{
	
		$this->nodeId = $id; 
		
	}
	

	
	public function set_sw_ns( $id )
	{
	
		$this->start_id = $id; 
		
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
//-------------------Control Element-----------------------------------
		
  $namespace.clazz = $funct(semweb,control)
  {
  	this.pfad = $pfad;
 	this.toString = $funct(){return 'Control Element';}
  	this.semref = semweb;
  	this.controlref = control;
  	this.myNodeid = '" . $this->nodeId . "';
  	this.mySVGObj = null;
  	this.controlref.addListener(this);
  	this.container = new Array();
  	this.graphsOn = " . boolString( $this->seeGraphs ) . ";
  	

/*-----------------------------------------------------Kabarett
* createPrimaryPanel( arrayOfFields , headline , parentObj  ):
* -----------------------------------------------------
*/ 	
  	var createPrimaryPanel =  de.auster_gmbh.library.controlelements.createPrimaryPanel;
 
//-----------------------------------------------------
/*-----------------------------------------------------
* createNPanel(pointerNum.id) : 
* -----------------------------------------------------
*/ 	
  	var createNPanel = de.auster_gmbh.library.controlelements.createNPanel;
//-----------------------------------------------------

/*-----------------------------------------------------
* createNGraphPanel(pointerNum.id) : 
* -----------------------------------------------------
*/ 	
  	var createNGraphPanel = de.auster_gmbh.library.controlelements.create_graph_field;
//-----------------------------------------------------

  	
  	var stdclazz = $funct(){};
  	var clazzSVG = new Array();

  	this.display = new Array();
  	this.root_ns = '" . $this->start_id . "';
  	
  	
  	
  	
  	
  	this.event = $funct( type , message )
  	{

	
	if( !(message instanceof de.auster_gmbh.library.tools.eventObject) )
	{

  		if(type == 'onClick_event' && message.id == 'foldingbutton')
  		{

  			de.auster_gmbh.controllelement_toolbox.tools.openControl(this.container);
  		}
  		
   		if(type == 'onClick_event' && message.id != 'foldingbutton' && message.id != 'control' )
  		{
  		
  			
  		
  			if(this.container[0].closed)return true;
  			//closes field
  			if(message.id.substr(0, 6) == 'fincre')
  			{
  			
  			  	var div = message.id.lastIndexOf('_');
  				var num = parseInt(message.id.substr(7, div - 7));
  				
  				this.semref.findID(1,num);
  				
   			for(var iter = 1;this.container.length > iter;iter++)
  			{
  				this.container[iter].transit(-1300,0);
  			}
  			
  			this.container[0].transit(-270,0);
  			//this.container[0].transit(-270,0);
  			
  			this.container[0].closed = true;
  					
			this.controlref.fireEvent('selected_object',message.id.substr(7, message.id.lastIndexOf('_') - 7));
			
			
			var eventobj = new de.auster_gmbh.library.tools.eventObject('',this,num);
  			this.controlref.fireEvent('[*/JSControl].setID',eventobj);
  			}
  			if(message.id.substr(0, 6) == 'posnum' || message.id.substr(0, 6) == 'hasbag' || message.id.substr(0, 6) == 'shogra' || message.id.substr(0, 6) == 'shoall')
  			{
  			
  			var type;
  			
  			switch (message.id.substr(0, 6)) 
  			{
    				case 'posnum': type = 0;
                   	break;
    				case 'hasbag': type = 1;
                   	break;
                   	    	case 'shogra': type = 2;
                   	break;
                   	        case 'shoall': type = 3;
                   	        alert('jo');
                   	break;
			}
  			
  			/* gets data for panelcreation */
  				var div = message.id.lastIndexOf('_');
  				var num = parseInt(message.id.substr(7, div - 7));
  				var n = parseInt(message.id.substr(div + 1,message.id.length - (div + 1)));
  				
  				
  				this.semref.findID(1,num);
  				
  				
  			de.auster_gmbh.library.controlelements.buildNPanel( this.semref,this.mySVGObj,this.pfad , this , type , n ,this.graphsOn);
  			
  				/* -------------------------------------------------------------------------------------------
			var nodemany = this.semref.childmany(1);
			var help = new Array();
			
			
			
			this.semref.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,true);
			var headline = this.semref.curValue(1);
			this.semref.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,false);
			
			//alert(this.semref.curNode(1));
			var myid = 0;
			var many = 0;
			for(it = 0;it < nodemany; it++)
			{
				this.semref.childNode(1,it);
				
				help[it] = new Array();	
				
				
				
				myid = this.semref.getID(1);
				many = this.semref.childmany(1);
				
				if(this.semref.manyBagEntry(1) == 0)
				{
					help[it][3] = '';
				}
				else
				{
					help[it][3] = 'hasbag_' + myid + '_' + (n + 1) ;
				}
				
				if(this.semref.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#isDefinedBy',0,true))
				{
				
					if(many == 0)
					{
					help[it][1] = 'fincre_' + myid + '_' + (n + 1) ;
					help[it][2] = '';
					}
					else
					{
					help[it][1] = 'create_' + myid + '_' + (n + 1) ;
					help[it][2] = 'posnum_' + myid + '_' + (n + 1);
					}
					
				}
				else
				{
					if(many == 0)
					{
					help[it][1] = 'finpos_' + myid + '_' + (n + 1) ;
					help[it][2] = '';
					}
					else
					{
					help[it][1] = 'posnum_' + myid + '_' + (n + 1) ;
					help[it][2] = 'posnum_' + myid + '_' + (n + 1);
					}
				}
				
					if( this.graphsOn )  
					help[it][4] = 'shogra_' + myid + '_' + (n + 1) ;
					else
					help[it][4] = '';
				
				this.semref.findID(1,myid);

				
				this.semref.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,true);
				help[it][0] = this.semref.curValue(1);
				this.semref.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,false);
 				
				this.semref.parentNode(1,0);

				 
			}
  				
					if(this.container.length > n)
  					for(iter2 = (this.container.length - 1) ; iter2 >= n ;iter2--)
  					{
  						
  						this.container[iter2].remove();
  						this.container.pop();
  					}
  					
 
  				
  				this.container[n] = createNPanel (n, help , headline , this.mySVGObj , this.pfad);
  				------------------------------------------------------------------------------- */
  			var eventobj = new de.auster_gmbh.library.tools.eventObject('',this,num);
  			this.controlref.fireEvent('[*/JSControl].setID',eventobj);
  			}

  			
  			/**
  			*
  			*
  			*/
  			
  			if(message.id.substr(0, 6) == 'finpos')
  			{
  				
  			var div = message.id.lastIndexOf('_');
  			var num = parseInt(message.id.substr(7, div - 7));
  			var n = parseInt(message.id.substr(div + 1,message.id.length - (div + 1)));
  			var value;
  			
  				
  				this.semref.findID(1,num);
  			
  			var list = this.semref.refIndex();
  				
			var nodemany = list.length;
			var help = new Array();
			
			var eventobj = new de.auster_gmbh.library.tools.eventObject('',this,num);
			
				this.controlref.fireEvent('[*/JSControl].setID&[*/JSControl].onFocus',eventobj);
  				
  			
  			
  			
  			}
  			

  			
  		}
  
  		if(type == 'init')
  		{
  		
  		
  		/*
  		* get Parentnode for creating Panel
  		*/
  			if(this.myNodeid == '')
  			this.mySVGObj = message.parentNode;
  			else
  			this.mySVGObj = document.getElementById(this.myNodeid);
 
  			

			
			/*
  			* find startindex in semweb and
  			* saves its many of childs
  			* and a variable to save all childnodes 
  			*/
			
			if(this.root_ns != '')this.semref.findIDX(1,this.root_ns,0);
			//this.semref.childNode(1,0);
			//alert(this.semref.getID(1));

			
  		
		this.container[0] = de.auster_gmbh.library.controlelements.buildPrimaryPanel(this.semref,this.mySVGObj,this.pfad, this.graphsOn);
		
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
  	
  	var fireEvent =  $funct( type , message )
  	{
  		this.controlref.fireEvent( type , message );
  	}
  	
  }
  
  $namespace = new $namespace.clazz(de.auster_gmbh.semanticelement.semantic_web,$pfad);
   
  //$namespace($pfad);
  //$pfad.addListener($namespace);
  
		"; 
	
		return $res;
	}
	
	public function __toString(){return 'javascript_control_element';}	
}

?>
