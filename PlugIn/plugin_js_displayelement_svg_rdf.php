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

class JSDisplay extends plugin_js
{
private $test = 0;
private $rst;
private $uri;
private $template;
private $tag_name;
private $nodeId = '';
private $nodeBarId = '';
private $full_uri = array();

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
  	var clazzSVG = new Array();

	//SVGPath
 	clazzSVG['SVGPath'] =  $funct(node)
 	{
 	var parentElement = node;
 	var style = '';
 	var wayPoints = new Array();
 	var transit = new Array();
 	var id = '';
 	transit[0] = 0;
 	transit[1] = 0;
 	
 	this.graphicElement = null;
 	
 	this.setWayPoint = $funct( xpoint , ypoint )
 	{
 		wayPoints[wayPoints.length] = new Array();
 		wayPoints[wayPoints.length - 1][0] = xpoint;
 		wayPoints[wayPoints.length - 1][1] = ypoint;
 		
 	}
 	
 	this.setStyle = $funct( objstyle )
 	{
 		style = objstyle;
 	}
 	
 	this.setID = $funct(myid)
 	{
 		id = myid;
 	}
 	
 	this.transit = $funct(xpoint, ypoint)
 	{
 		transit[0] = xpoint;
 		transit[1] = ypoint;
 	}
 	
 	this.init = $funct()
	{
	
		var d = 'M ';
		for(i = 0 ; wayPoints.length > i ; i++)
		
			if(i == 0)
			 d += (wayPoints[i][0] + transit[0]) + ',' + (wayPoints[i][1] + transit[1]) + ' ';
			else
			 d += 'L ' +(wayPoints[i][0] + transit[0]) + ',' + (wayPoints[i][1] + transit[1]) + ' ';
			 
		d += 'z';
		
		this.graphicElement = document.createElementNS('http://www.w3.org/2000/svg', 'path');
		
		this.graphicElement.setAttributeNS(null, 'id', id);
		this.graphicElement.setAttributeNS(null, 'd', d);
		this.graphicElement.setAttributeNS(null, 'style', style);
		
		parentElement.appendChild(this.graphicElement);
		
	}


 	 };
  	
	//SVGPath
 	clazzSVG['SVGText'] =  $funct(node)
 	{
 	var parentElement = node;
 	var style = '';
 	var wayPoints = new Array();
 	var transit = new Array();
 	var text = '';
 	var id = '';
 	transit[0] = 0;
 	transit[1] = 0;
 	
 	this.graphicElement = null;
 	
 	this.setText = $funct(mytext){text = mytext;}
 	
 	this.setWayPoint = $funct( xpoint , ypoint )
 	{
 		wayPoints[wayPoints.length] = new Array();
 		wayPoints[wayPoints.length - 1][0] = xpoint;
 		wayPoints[wayPoints.length - 1][1] = ypoint;
 		
 	}
 	
 	this.setStyle = $funct( objstyle )
 	{
 		style = objstyle;
 	}
 	
 	this.setID = $funct(myid)
 	{
 		id = myid;
 	}
 	
 	this.transit = $funct(xpoint, ypoint)
 	{
 		transit[0] = xpoint;
 		transit[1] = ypoint;
 	}
 	
 	this.init = $funct()
	{
	
		var x_elem = '';
		var y_elem = '';
		for(i = 0 ; wayPoints.length > i ; i++)
		{
			if(i == 0)
			{
			x_elem = (wayPoints[i][0] + transit[0]);
			y_elem = (wayPoints[i][1] + transit[1]);
			}
			else
			{
			x_elem = ' ' + (wayPoints[i][0] + transit[0]);
			y_elem = ' ' + (wayPoints[i][1] + transit[1]);
			}
		}
		

		
		this.graphicElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
		
		this.graphicElement.setAttributeNS(null, 'id', id);
		this.graphicElement.setAttributeNS(null, 'x', x_elem);
		this.graphicElement.setAttributeNS(null, 'y', y_elem);
		this.graphicElement.setAttributeNS(null, 'style', style);
		
		this.graphicElement.appendChild(document.createTextNode(text));
		
		parentElement.appendChild(this.graphicElement);
		
	}


 	 };
  	
  	
  	this.display = new Array();
  	
  	this.event = $funct( type , message )
  	{
  	
	if( !(message instanceof de.auster_gmbh.library.tools.eventObject) )
	{
 		if(type == 'init')
  		{
			this.mySVGObj = document.getElementById(this.myNodeid);
			this.mySVGBarObj = document.getElementById(this.myBarNodeid);
			de.auster_gmbh.library.commonrefs.STDNODE = this.mySVGBarObj;
			de.auster_gmbh.library.commonrefs.STDLISTENER = this.pfad;
			
			de.auster_gmbh.semanticelement.SOM.tagInDoc = this.mySVGObj;
			de.auster_gmbh.semanticelement.SOM.callRef = this.pfad;
			 	
			var viscont = new de.auster_gmbh.graphicelement.visualBag();
			var symbolBag = new de.auster_gmbh.graphicelement.visualBag();					
			var visEl = new de.auster_gmbh.graphicelement.visualElement();
			//wall
			var svg = new de.auster_gmbh.graphicelement.svg.SVGPath(this.mySVGBarObj,this.pfad);
			
			svg.transit((- window.innerWidth + 44),window.innerHeight - 120); //-156,350 window.innerHeight - 120
			
			svg.setWayPoint(0,0);
			svg.setWayPoint(window.innerWidth - 30,0);
			svg.setWayPoint(window.innerWidth,30);
			svg.setWayPoint(window.innerWidth,100);
			

			
			svg.setWayPoint(0,100);
			svg.setWayPoint(0,0);
			
			svg.setStyle('fill:#bac04f;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('ThemeOverview');
			visEl.add(svg);
			
			var svg = new de.auster_gmbh.graphicelement.svg.SVGPath(this.mySVGBarObj,this.pfad);
			
			svg.transit((- window.innerWidth + 44),window.innerHeight - 120); //-156,350 window.innerHeight - 120
			
			svg.setWayPoint(60,10);
			svg.setWayPoint(window.innerWidth - 60,10);
			svg.setWayPoint(window.innerWidth - 30,40);
			svg.setWayPoint(window.innerWidth - 30,90);
			
			svg.setWayPoint(20,90);
			
			svg.setWayPoint(20,60);
			
			
			svg.setStyle('fill:#ffffff;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('ThemeOverview');
			visEl.add(svg);

			svg = new de.auster_gmbh.graphicelement.svg.SVGPath(this.mySVGBarObj,this.pfad);
			
			//svg.transit(300,300);
			svg.transit(23, (window.innerHeight - 90)); // (window.innerHeight - 90)
			//svg.setWayPoint(8 ,0); //0
			//svg.setWayPoint(0 ,8); //8
			//svg.setWayPoint(8 ,17);  //17
			svg.setWayPoint(8 ,13);  //13
			svg.setWayPoint(11 ,13); //13
			svg.setWayPoint(11 ,17); //17
			svg.setWayPoint(19 ,9); //9
			svg.setWayPoint(11 ,0); //0
			svg.setWayPoint(11 ,4); //4
			svg.setWayPoint(8 ,4); //4
			//svg.setWayPoint(8 ,0);//0
		
			svg.setStyle('fill:#264e87;fill-rule:evenodd;stroke:#ffffff;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('foldingbutton_display');
			visEl.add(svg);
			
			svg = new de.auster_gmbh.graphicelement.svg.SVGPath(this.mySVGBarObj,this.pfad);
			
			//svg.transit(300,300);
			svg.transit((- window.innerWidth + 70), (window.innerHeight - 100)); // (window.innerHeight - 90)
			svg.setWayPoint(8 ,0); //0
			svg.setWayPoint(0 ,8); //8
			svg.setWayPoint(8 ,17);  //17
			svg.setWayPoint(8 ,13);  //13
			svg.setWayPoint(11 ,13); //13
			//svg.setWayPoint(11 ,17); //17
			//svg.setWayPoint(19 ,9); //9
			//svg.setWayPoint(11 ,0); //0
			svg.setWayPoint(11 ,4); //4
			svg.setWayPoint(8 ,4); //4
			svg.setWayPoint(8 ,0);//0
		
			svg.setStyle('fill:#264e87;fill-rule:evenodd;stroke:#ffffff;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('foldingbutton_display');
			visEl.add(svg);
			

			symbolBag.setID('symbols');
			viscont.add(visEl);
			viscont.add(symbolBag);
			
			viscont.init();
			viscont.transit(0,0);
			viscont.closed = true;
			viscont.top = (window.innerHeight - 120);
			viscont.left = (- window.innerWidth + 44);
			viscont.width = ( window.innerWidth - 44);
			viscont.hight = 100;
			
			
			
			this.display[0] = viscont;
			this.display[1] = symbolBag;
			
		}
		
		if(type == 'onClick_event' && message.id == 'foldingbutton_display')
  		{
  		
  			var listGE = new Array();
			listGE[0] = new Array();
			
  			if(this.display[0].closed)
  			{
  			
  			de.auster_gmbh.library.tools.displayElements.assembleThemeBar(
  			this.display[1],
			this.display[0].top,
			this.display[0].left,
			this.display[0].width,
			this.display[0].hight,
  			this.mySVGBarObj,
  			this.pfad);
  			 			
  			listGE[0][0] = this.display[0];
  			listGE[0][1] = this.display[0].width;
  			listGE[0][2] = 0;
  			
  			de.auster_gmbh.graphicelement.sequenceMoveToPoint(listGE);
  			//this.container[0].transit(0,0);
  			this.display[0].closed = false;
  			

  			
  			}
  			else
  			{
  			
  			
  			listGE[0][0] = this.display[0];
  			listGE[0][1] = 0;
  			listGE[0][2] = 0;
  			
  			de.auster_gmbh.graphicelement.sequenceMoveToPoint(listGE);
  			//this.container[0].transit(-270,0);
  			this.display[0].closed = true;
  		}
  		}
  		
		if(type == 'onClick_event' && message.id.substring(0, 6) == 'theme_')
  		{
  			var id_num = message.id.substring(6, message.id.lastIndexOf('_'));
			var command = message.id.substring( message.id.lastIndexOf('_') + 1);
   			var listGE = new Array();
   			var eventobj = null;
			listGE[0] = new Array();
			if(this.isActive != 0)
			{
				eventobj = new de.auster_gmbh.library.tools.eventObject('onDeactivate',message,this.isActive);
  				this.dataref.fireEvent('Controlcenter.sendToSemWeb',eventobj); //Controlcenter
  				this.isActive = id_num;
			}
			else
			{
				this.isActive = id_num;
			} 
  			
  			listGE[0][0] = this.display[0];
  			listGE[0][1] = 0;
  			listGE[0][2] = 0;
  			
  			de.auster_gmbh.graphicelement.sequenceMoveToPoint(listGE);
  			//this.container[0].transit(-270,0);
  			this.display[0].closed = true;
  			
  			eventobj = new de.auster_gmbh.library.tools.eventObject(command,message,id_num);
  			console.debug(command,message,id_num);
  			this.dataref.fireEvent('Controlcenter.sendToSemWeb',eventobj); //Controlcenter
   			eventobj = new de.auster_gmbh.library.tools.eventObject('',this,id_num);
  			this.dataref.fireEvent('[*/JSControl].setID',eventobj);
  		}

		//
		if(type == 'onClick_event' && message.id.substring(0, 5) == 'OBJID')
  		{
  		
  			//alert(message.id.substring(5));
  			var idObj = de.auster_gmbh.semanticelement.semantic_web.findIDobj(message.id.substring(5));
  			if(idObj)
  			{
  				if(idObj.onActivate)
  				{
  				 
  					idObj.onClick(type,message);
  				}
  			}
  		}
  		
  		/*
 		if(type == 'onClick_event' && message.id == 'foldingbutton_display')
  		{
  		
  			var listGE = new Array();
			listGE[0] = new Array();
			
  			if(this.display[0].closed)
  			{
  			 			
  			listGE[0][0] = this.display[0];
  			listGE[0][1] = this.display[0].width;
  			listGE[0][2] = 0;
  			
  			de.auster_gmbh.graphicelement.sequenceMoveToPoint(listGE);
  			//this.container[0].transit(0,0);
  			this.display[0].closed = false;
  			

  			
  			}
  			else
  			{
  			
  			
  			listGE[0][0] = this.display[0];
  			listGE[0][1] = 0;
  			listGE[0][2] = 0;
  			
  			de.auster_gmbh.graphicelement.sequenceMoveToPoint(listGE);
  			//this.container[0].transit(-270,0);
  			this.display[0].closed = true;
  		}
  		}
  		*/
  		
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
