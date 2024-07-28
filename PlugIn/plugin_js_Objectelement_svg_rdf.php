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

class JSObject extends plugin_js
{
private $test = 0;
private $rst;
private $uri;
private $template;
private $tag_name;
private $toolbar = 'toolbar';
private $display = 'object';
private $nodeId = '';
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
	
	/**
	*
	*@-------------------------------------------
	*/
	//parameterausgabe
	public function getAdditiveSource(){ return 'script/JSObjectLib.js';}
	
	public function set_toolbar_id( $tag_id )
	{
		
		$this->toolbar = $tag_id;
		
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
//-------------------Object Element-----------------------------------
		
  $namespace.clazz = $funct(semweb,control)
  {
  
  this.toString = $funct(){return 'Display Element';}
    	this.semref = semweb;
  	this.controlref = control;
  	this.dataref = control;
  	this.myNodeid = '" . $this->nodeId . "'; 
  	this.myToolBar = '" . $this->toolbar . "';
  	this.mySVGObj = null;
  	this.controlref.addListener(this);
   	this.container = new Array();
  	var intervalElement = new Array();
	
	/* contains an object, which is the actual one */
	var currentNode = undefined;
	var currentid = new Array();
	currentid[0] = 0;
	currentid[1] = 0;
	
	var funcArray = new Array();
	var functpoint = 0;

	this.Web = new de.auster_gmbh.objectelement_toolbox.Web(this);
	this.Info = new de.auster_gmbh.objectelement_toolbox.Info(this);
	
	
	this.initrotateMenue = $funct(graphicElement)
	{
		if(intervalElement['graphic'] == undefined)
		{
			intervalElement['graphic'] = this.container[1]['NodeWeb'].findID(  'workbench'  ).getListOfElements();
			intervalElement['cur_x/n'] = 0;
			intervalElement['prozess'] = 'rotate';
			intervalElement['steps'] = 180;
			intervalElement['main'] = null;
			intervalElement['x_line'] = 0;
			intervalElement['x_pos'] = 0;
			intervalElement['y_pos'] = 0;
			intervalElement['expand'] = 0;
			intervalElement['name'] = '';
			
			
		}
		
	}
	
	this.rotateMenue = $funct( pos )
	{
			intervalElement['name'] = pos;
			
			var div = pos.indexOf('_');
			var div2 = pos.indexOf('_', div + 1);

			var breaker = pos.substr(div + 1, div2 - div - 1);
			var middle = breaker.indexOf('/');
			var counter = parseFloat(breaker.substr(0, middle ));
			var caller = parseFloat( breaker.substr(middle + 1, div2 - middle - 1 ));
			//alert(caller);
			//alert( breaker +  ' ' + intervalElement['cur_x/n'] + ' ' +  ( caller - counter)  );
			intervalElement['cur_x/n'] =  Math.abs( intervalElement['cur_x/n'] - ( caller - counter)  );
			
			//Math.abs( intervalElement['cur_x/n'] - (( caller - counter) % caller) )
			
			
			// (counter + intervalElement['cur_x/n'] + counter ) % caller;
			
			//var num = parseInt(message.id.substr(7, div - 7);
			//alert(intervalElement['cur_x/n']);
			
		//alert(pos);
			//intervalElement['graphic']		

			intervalElement['curstep'] = 0;
			
			intervalElement['stopstep'] =  Math.round(  (intervalElement['cur_x/n']  / caller) * (intervalElement['steps'] ));	
			
			intervalElement['interval'] = window.setInterval('$namespace.intervalevent()', 20);

			
		


	}
	this.intervalevent = $funct()
	{
	
	
	if(intervalElement['x_pos'] != 0)
	{
	
		window.clearInterval(intervalElement['interval']);
		intervalElement['stopstep'] = 40;
		intervalElement['curstep'] = 40;
		intervalElement['interval'] = window.setInterval('$namespace.intervalsubsidence()', 20);
		return false;
		
	
	}
	


		
			var elements = null;
			var mtrans = null;
			var mynull_x = 0;
			var mynull_y = 0;
			for (testme = 0 ; intervalElement['graphic'].length > testme; testme++)
			{
				
				if(intervalElement['graphic'][testme].getID() == 'ObjectsOfCircle')
					{
						
					
		
						elements = intervalElement['graphic'][testme].getListOfElements();
						mtrans = elements[1];
						
						if(intervalElement['main'] == null && mtrans.getID() == intervalElement['name'])
						{
							intervalElement['main'] = elements;
						}
						
						if(mynull_x == 0)
						{
						 mynull_x = mtrans.getWayPoint(0,0);
						 mynull_y = mtrans.getWayPoint(0,1);
						}
						
						elements[1].maintransit((-1 * (Math.cos((Math.PI * 2 * (((mtrans.startcounter) / mtrans.caller) + (intervalElement['curstep'] /intervalElement['steps']))))) * 170) + 170 -  (mtrans.getWayPoint(0,0) - mynull_x) , (Math.sin((Math.PI * 2 *  (((mtrans.startcounter) / mtrans.caller) + (intervalElement['curstep'] /intervalElement['steps'])))) * 170)  -  (mtrans.getWayPoint(0,1) - mynull_y)   )  ;
						
					elements[0].alterWayPoint(1,0,(-1 * (Math.cos((Math.PI * 2 * (((mtrans.startcounter) / mtrans.caller) + (intervalElement['curstep'] /intervalElement['steps']))))) * 170) + 170 +  mynull_x );
					elements[0].alterWayPoint(1,1,(Math.sin((Math.PI * 2 *  (((mtrans.startcounter) / mtrans.caller) + (intervalElement['curstep'] /intervalElement['steps'])))) * 170) +  mynull_y   );
					
					
					//elements[0].alterWayPoint(1,0,0);
					
					elements[1].alter();
					
//- elements[1].getWayPoint(0,0)
		

					}
			}
	
	
	
	if( intervalElement['curstep']++ >= (intervalElement['stopstep'] % intervalElement['steps']))
	{
		
		window.clearInterval(intervalElement['interval']);
		
		if(intervalElement['main'] != null)
		{
		
		intervalElement['curstep'] = 0;
			
		intervalElement['stopstep'] =  40;	
		
		intervalElement['interval'] = window.setInterval('$namespace.intervalexpand()', 20);
		
		
		}
		

	}
	
	intervalElement['curstep'] = intervalElement['curstep'] % intervalElement['steps'];
	

	
	}
	
	//---------------------------------------------------------------------------------
	this.intervalexpand = $funct()
	{
	
	if(intervalElement['x_pos'] == 0)
	{
	var elem = intervalElement['main'][1].getmaintransit();
	
	intervalElement['x_pos'] = elem[0];
	intervalElement['y_pos'] = elem[1];
	
	intervalElement['x_line'] = intervalElement['main'][0].getWayPoint(1,0);
	
	}
	
	
	
	intervalElement['main'][1].maintransit( 
		intervalElement['x_pos'] - ((window.innerWidth/5) * ( intervalElement['curstep'] / intervalElement['stopstep'] )) 
		,intervalElement['y_pos']);
	
	
	intervalElement['main'][0].alterWayPoint(1,0
		, intervalElement['x_line'] - ((window.innerWidth/5) * ( intervalElement['curstep'] / intervalElement['stopstep'] )) );
	
	intervalElement['main'][1].alter();
	

	
	if(intervalElement['curstep']++ >= intervalElement['stopstep'])
	{
	window.clearInterval(intervalElement['interval']);
	
	// oeffnet die Graphen  !!!!!!!!!!!!!!!aufgehoert!!!!!!!!!!!!!!!!!!
	//concatcontrol(this.mySVGObj,intervalElement['main'][1],)
	
	}
	
	
	
	}
	
	//---------------------------------------------------------------------------------
	this.intervalsubsidence = $funct()
	{
	
	
	
	
	intervalElement['main'][1].maintransit( 
		intervalElement['x_pos'] - ((window.innerWidth/5) * ( intervalElement['curstep'] / intervalElement['stopstep'] )) 
		,intervalElement['y_pos']);
	
	
	intervalElement['main'][0].alterWayPoint(1,0
		, intervalElement['x_line'] - ((window.innerWidth/5) * ( intervalElement['curstep'] / intervalElement['stopstep'] )) );
	
	intervalElement['main'][1].alter();
	
	
	
	if(intervalElement['curstep']-- == 0)
	{
	window.clearInterval(intervalElement['interval']);
	
	
	intervalElement['main'] = null;
	intervalElement['x_pos'] = 0;
	intervalElement['y_pos'] = 0;
	
	intervalElement['x_line'] = 0;
	
	this.rotateMenue(intervalElement['name']);
	}

	
	}

 /*-----------------------------------------------------
* createPrimaryPanel(  add , parentObj  ):
* -----------------------------------------------------
*/ 	
  	var createPrimaryPanel =  $funct( add , parentObj  )
 	{
 		
 		
 		var mycontainer = new de.auster_gmbh.graphicelement.visualElement();
		var mybag =  new de.auster_gmbh.graphicelement.visualBag();
		var mysvg = new de.auster_gmbh.graphicelement.svg.SVGPath(parentObj,$pfad);
			
		mysvg.transit(0,0);
		mysvg.setWayPoint(0,0);
		mysvg.setWayPoint(window.innerWidth,0);
		mysvg.setWayPoint(window.innerWidth,28);
		mysvg.setWayPoint(625,28);
		mysvg.setWayPoint(610,18);
		mysvg.setWayPoint(340,18);
		mysvg.setWayPoint(310,38);
		mysvg.setWayPoint(145,38);
		mysvg.setWayPoint(130,28);
		mysvg.setWayPoint(0,28);
		mysvg.setWayPoint(0,0);
		mysvg.setStyle('fill:#002e7a;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
		mysvg.setID('objectHeadUp');
			
		mycontainer.add(mysvg);
		
		mysvg = new de.auster_gmbh.graphicelement.svg.SVGText(parentObj,$pfad);
		mysvg.transit(window.innerWidth - 180,0);
		mysvg.setWayPoint(5,20);

		mysvg.setText('Objektkontrolle');
		mysvg.setStyle('font-size:16px;font-family:Bitstream Vera Sans, Arial; font-weight:normal;font-style:normal;stroke:#8794a6;stroke-width:1;fill:#8794a6');
		mysvg.setID('headline1');
		mycontainer.add(mysvg);
		

		mysvg = new de.auster_gmbh.graphicelement.svg.SVGPath(parentObj,$pfad);
			
		mysvg.transit(window.innerWidth - 300,10);
		//mysvg.setWayPoint(198.29762 + 90 ,65.860403 - 70);
		//mysvg.setWayPoint(190.58577 + 90 ,73.734851 - 70);
		//mysvg.setWayPoint(198.29762 + 90 ,82.646873 - 70); 
		mysvg.setWayPoint(188.29762 + 90 ,78.577423 - 70); 
		mysvg.setWayPoint(201.55508 + 90 ,78.577423 - 70); 
		mysvg.setWayPoint(201.55508 + 90 ,82.646873 - 70); 
		mysvg.setWayPoint(209.06784 + 90 ,74.127283 - 70); 
		mysvg.setWayPoint(201.55508 + 90 ,65.860403 - 70); 
		mysvg.setWayPoint(201.55508 + 90 ,69.929853 - 70); 
		mysvg.setWayPoint(188.29762 + 90 ,69.929853 - 70); 
		//mysvg.setWayPoint(208.29762 + 90 ,65.860403 - 70);
		
		mysvg.setStyle('fill:#002e7a;fill-rule:evenodd;stroke:#ffffff;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
		mysvg.setID('foldingbutton_object');
		mycontainer.add(mysvg);
		
		mysvg = new de.auster_gmbh.graphicelement.svg.SVGPath(parentObj,$pfad);
			
		mysvg.transit(-175,10);
		mysvg.setWayPoint(198.29762 + 90 ,65.860403 - 70);
		mysvg.setWayPoint(190.58577 + 90 ,73.734851 - 70);
		mysvg.setWayPoint(198.29762 + 90 ,82.646873 - 70); 
		mysvg.setWayPoint(198.29762 + 90 ,78.577423 - 70); 
		mysvg.setWayPoint(211.55508 + 90 ,78.577423 - 70); 
		mysvg.setWayPoint(211.55508 + 90 ,69.929853 - 70); 
		mysvg.setWayPoint(198.29762 + 90 ,69.929853 - 70); 
		mysvg.setWayPoint(198.29762 + 90 ,65.860403 - 70);
		
		mysvg.setStyle('fill:#002e7a;fill-rule:evenodd;stroke:#ffffff;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
		mysvg.setID('foldingbutton_object');
		mycontainer.add(mysvg);

		mysvg = new de.auster_gmbh.graphicelement.svg.SVGImage(parentObj,$pfad);
			
		mysvg.transit(140,6);
		mysvg.setWayPoint(5,2);
		mysvg.setDimension(26,23); 
		mysvg.setImage('img/objectaddsymbol.png');
			

		mysvg.setID('objectadd');
		mycontainer.add(mysvg);
			

		mysvg = new de.auster_gmbh.graphicelement.svg.SVGImage(parentObj,$pfad);
			
		mysvg.transit(190,6);
		mysvg.setWayPoint(5,2);
		mysvg.setDimension(55,25); 
		mysvg.setImage('img/objectnetsymbol.png');
			

		mysvg.setID('objectedit');
		mycontainer.add(mysvg);


		mysvg = new de.auster_gmbh.graphicelement.svg.SVGImage(parentObj,$pfad);
			
		mysvg.transit(270,6);
		mysvg.setWayPoint(5,2);
		mysvg.setDimension(28,27); 
		mysvg.setImage('img/objectinfosymbol.png');
			
//'objectadd'
//'objectedit'
//'objectinfo'
		mysvg.setID('objectinfo');
		mycontainer.add(mysvg);
			
		mybag.add(mycontainer);
			
		mybag.init();
		return mybag;
 		
 	}
 	
  	var createOverviewPanel =  de.auster_gmbh.objectelement_toolbox.tools.Datapanel; 
 	/*
 	* 
 	*/
 	var alterOverviewPanel =  $funct( parentObj , svgBag, semobj  )
 	{
 		var mycontainer = svgBag;
 		var myelement = null;	
		var mysvg = null;
		var labelvar = '';
		var commentvar = '';
		
	
		
		var max = semobj.childmany(1);
		
					
			//this.semref.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,true);
			//var headline = this.semref.curValue(1);
			//this.semref.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,false);
		
		for(aopiter = 0;aopiter < max; aopiter++ )
		{
		myelement = new de.auster_gmbh.graphicelement.visualElement();	
		
		semobj.childNode(1,aopiter);
		
		mysvg = new de.auster_gmbh.graphicelement.svg.SVGLine(parentObj,$pfad);
		
 		mysvg.transit(150,28);
		mysvg.setWayPoint(((window.innerWidth- 320)/2) + ((window.innerWidth)/5) ,(window.innerHeight -80)/2);
		mysvg.setWayPoint( (-1 * (Math.cos((Math.PI * 2 * aopiter) / max) * 170)) + ((window.innerWidth- 320)/2) + ((window.innerWidth)/5) , (Math.sin((Math.PI * 2  * aopiter) / max) * 170) + ((window.innerHeight -80)/2) );
		mysvg.setStyle('stroke:black;stroke-width:3;');
		mysvg.setID('lineto');
		mysvg.init();	
		myelement.add(mysvg);		
		
		
		mysvg = new de.auster_gmbh.graphicelement.svg.SVGCircle(parentObj,$pfad);
		
			if(!semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,true)
			)
			labelvar = '';
			else
			labelvar = semobj.curValue(1);
			
			semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,false);
			
			if(
			!semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#comment',0,true)
			)
			commentvar = '';
			else
			commentvar = semobj.curValue(1);
			
			semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#comment',0,false);
			
		mysvg.setLabel(labelvar,commentvar);
		mysvg.caller = max;
		mysvg.startcounter = aopiter;
		mysvg.curcounter = aopiter;
 		mysvg.transit(150,28);
		mysvg.setWayPoint( (-1 * (Math.cos((Math.PI * 2 * aopiter) / max) * 170)) + ((window.innerWidth- 320)/2) + ((window.innerWidth)/5) , (Math.sin((Math.PI * 2  * aopiter) / max) * 170) + ((window.innerHeight -80)/2) );
		mysvg.setradiant( 20 );
		mysvg.setStyle('opacity:1;fill:#055555;fill-opacity:1;fill-rule:nonzero');
		
		mysvg.setID('ObjectsOfCircle_' + aopiter + '/' + max + '_0');
		mysvg.init();	
		myelement.add(mysvg);

		myelement.setID( 'ObjectsOfCircle' );
		
		mycontainer.add(myelement);
		
		semobj.parentNode(1, 0);
		
		}
		
		
		
		myelement = new de.auster_gmbh.graphicelement.visualElement();	
		mysvg = new de.auster_gmbh.graphicelement.svg.SVGCircle(parentObj,$pfad);
		
			if(!semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,true)
			)
			labelvar = '';
			else
			labelvar = semobj.curValue(1);
			
			semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,false);
			
			if(
			!semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#comment',0,true)
			)
			commentvar = '';
			else
			commentvar = semobj.curValue(1);
			
			semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#comment',0,false);
		
		mysvg.setLabel(labelvar,commentvar);
 		mysvg.transit(150,28);
		mysvg.setWayPoint((window.innerWidth- 320)/2 + ((window.innerWidth)/5) ,(window.innerHeight -80)/2);
		mysvg.setradiant( 30 );
		mysvg.setStyle('opacity:1;fill:#c71712;fill-opacity:1;fill-rule:nonzero');
		mysvg.setID('center');
		mysvg.init();	
		myelement.add(mysvg);
		myelement.setID('center');
		//myelement.setID( 'container_of_a_model' );
		mycontainer.add(myelement);
		
		
		/*
		var myelement = new de.auster_gmbh.graphicelement.visualElement();	
		var mysvg = new de.auster_gmbh.graphicelement.svg.SVGPath(parentObj,$pfad);
			
		mysvg.transit(150,28);
		mysvg.setWayPoint(0,0);
		mysvg.setWayPoint(window.innerWidth - 300,0);
		mysvg.setWayPoint(window.innerWidth- 300 ,window.innerHeight -70);
		mysvg.setWayPoint(window.innerWidth- 320 ,window.innerHeight - 50);
		mysvg.setWayPoint(0,window.innerHeight - 50);
		mysvg.setWayPoint(0,0);
		mysvg.setStyle('fill:#;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
		mysvg.setID('objectHeadUp');
			
		myelement.add(mysvg);
		mysvg = new de.auster_gmbh.graphicelement.svg.SVGPath(parentObj,$pfad)
		mysvg.transit(150,28);
		mysvg.setWayPoint(20,0);
		mysvg.setWayPoint(window.innerWidth - 320,0);
		mysvg.setWayPoint(window.innerWidth- 320 ,window.innerHeight -80);
		mysvg.setWayPoint(window.innerWidth- 330 ,window.innerHeight - 70);
		mysvg.setWayPoint(40,window.innerHeight - 70);
		mysvg.setWayPoint(20,window.innerHeight - 90);
		mysvg.setWayPoint(20,0);
		mysvg.setStyle('fill:#ffffff;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
		mysvg.setID('objectHeadUp');
			
		myelement.add(mysvg);

		mycontainer.add(myelement);
			
		mycontainer.init();
		return mycontainer;
 		*/
 	}
 	
 	
 	/*
 	* 
 	*/
 	var concatcontrol =  $funct( parentObj , graphicObj , svgBag, semobj  )
 	{
 		var mycontainer = svgBag;
 		var myelement = null;	
		var mysvg = null;
		var labelvar = '';
		var commentvar = '';
		
	return true;
		
		var max = semobj.childmany(1);
		
					
			//this.semref.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,true);
			//var headline = this.semref.curValue(1);
			//this.semref.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,false);
		
		for(aopiter = 0;aopiter < max; aopiter++ )
		{
		myelement = new de.auster_gmbh.graphicelement.visualElement();	
		
		semobj.childNode(1,aopiter);
		
		mysvg = new de.auster_gmbh.graphicelement.svg.SVGLine(parentObj,$pfad);
		
 		mysvg.transit(150,28);
		mysvg.setWayPoint(((window.innerWidth- 320)/2) + ((window.innerWidth)/5) ,(window.innerHeight -80)/2);
		mysvg.setWayPoint( (-1 * (Math.cos((Math.PI * 2 * aopiter) / max) * 170)) + ((window.innerWidth- 320)/2) + ((window.innerWidth)/5) , (Math.sin((Math.PI * 2  * aopiter) / max) * 170) + ((window.innerHeight -80)/2) );
		mysvg.setStyle('stroke:black;stroke-width:3;');
		mysvg.setID('lineto');
		mysvg.init();	
		myelement.add(mysvg);		
		
		
		mysvg = new de.auster_gmbh.graphicelement.svg.SVGCircle(parentObj,$pfad);
		
			if(!semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,true)
			)
			labelvar = '';
			else
			labelvar = semobj.curValue(1);
			
			semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,false);
			
			if(
			!semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#comment',0,true)
			)
			commentvar = '';
			else
			commentvar = semobj.curValue(1);
			
			semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#comment',0,false);
			
		mysvg.setLabel(labelvar,commentvar);
		mysvg.caller = max;
		mysvg.startcounter = aopiter;
		mysvg.curcounter = aopiter;
 		mysvg.transit(150,28);
		mysvg.setWayPoint( (-1 * (Math.cos((Math.PI * 2 * aopiter) / max) * 170)) + ((window.innerWidth- 320)/2) + ((window.innerWidth)/5) , (Math.sin((Math.PI * 2  * aopiter) / max) * 170) + ((window.innerHeight -80)/2) );
		mysvg.setradiant( 20 );
		mysvg.setStyle('opacity:1;fill:#055555;fill-opacity:1;fill-rule:nonzero');
		
		mysvg.setID('ObjectsOfCircle_' + aopiter + '/' + max + '_0');
		mysvg.init();	
		myelement.add(mysvg);

		myelement.setID( 'ObjectsOfCircle' );
		
		mycontainer.add(myelement);
		
		semobj.parentNode(1, 0);
		
		}
		
		
		
		myelement = new de.auster_gmbh.graphicelement.visualElement();	
		mysvg = new de.auster_gmbh.graphicelement.svg.SVGCircle(parentObj,$pfad);
		
			if(!semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,true)
			)
			labelvar = '';
			else
			labelvar = semobj.curValue(1);
			
			semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#label',0,false);
			
			if(
			!semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#comment',0,true)
			)
			commentvar = '';
			else
			commentvar = semobj.curValue(1);
			
			semobj.moveinWeb(1,'http://www.w3.org/2000/01/rdf-schema#comment',0,false);
		
		mysvg.setLabel(labelvar,commentvar);
 		mysvg.transit(150,28);
		mysvg.setWayPoint((window.innerWidth- 320)/2 + ((window.innerWidth)/5) ,(window.innerHeight -80)/2);
		mysvg.setradiant( 30 );
		mysvg.setStyle('opacity:1;fill:#c71712;fill-opacity:1;fill-rule:nonzero');
		mysvg.setID('center');
		mysvg.init();	
		myelement.add(mysvg);
		myelement.setID('center');
		//myelement.setID( 'container_of_a_model' );
		mycontainer.add(myelement);
		
		

 	}
 	
 	//var textInsertElement = null;
 	//var textAreaElement = null;
 	
  	var createInsertPanel =  de.auster_gmbh.objectelement_toolbox.tools.Insertpanel;

//-----------------------------------------------------
  	

  	
  	var stdclazz = $funct(){};

  	
  	this.display = new Array();
  	
  	var treeid = -1; 
  	
  	this.setBasicElement = $funct(idOfElement)
  	{
  		
  	}
  	/*
  	this.setBasicElement = $funct(idOfElement)
  	{
  		
  	}
  	*/
  	this.getBasicElement = $funct()
  	{
  		
  	}
  	
  	/* onFocus */
  	this.onFocus = $funct()
  	{
  		if(!de.auster_gmbh.objectelement_toolbox.tools.bar_status(this.container))
  		de.auster_gmbh.objectelement_toolbox.tools.open_bar(this.container);
  	}
  	
  	 
  	/* onCollapse */
  	this.onCollapse = $funct()
  	{
  		if(de.auster_gmbh.objectelement_toolbox.tools.bar_status(this.container))
  		de.auster_gmbh.objectelement_toolbox.tools.open_bar(this.container);
  	}
  	
  	
  	/* setID */
  	this.setID = $funct(myid)
  	{
  	
  	
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
  		
  	}
  	
  	this.event = $funct( type , message )
  	{
	
	
	
		/*-----------------------------------------------------
		*	foldingbutton_event
		* -----------------------------------------------------
		*/
  	  	if(type == 'onClick_event' && message.id == 'foldingbutton_object')
  		{
  			
  			
  			de.auster_gmbh.objectelement_toolbox.tools.open_bar(this.container);

  		}
  		//
//'objectedit'
//'objectinfo'
//
		if(type == 'onClick_event' && message.id.substr(0, 15) == 'ObjectsOfCircle' )
		{
		
			var div = message.id.indexOf('_');
			var div2 = message.id.indexOf('_', div + 1);

			var breaker = message.id.substr(div + 1, div2 - div - 1);
			var middle = breaker.indexOf('/');
			var counter = parseFloat(breaker.substr(0, middle ));
			var caller = parseFloat( breaker.substr(middle + 1, div2 - middle - 1 ));
			
			//var num = parseInt(message.id.substr(7, div - 7);
			//alert(breaker);
			//alert(parseFloat(counter / caller));
			
			this.initrotateMenue( this.container[1]['NodeWeb'].findID(  'workbench'  ));
			
			this.rotateMenue( message.id );
			
			
			/*
			var listofelements = this.container[1]['NodeWeb'].findID(  'workbench'  ).getListOfElements();
			
			for (testme = 0 ; listofelements.length > testme; testme++)
			{
			
				alert(listofelements[testme].getID() );
			}
			*/
		}

		/*-----------------------------------------------------
		*	add_event
		* -----------------------------------------------------
		*/
   	  	if(type == 'onClick_event' && message.id == 'objectadd')
  		{
  			de.auster_gmbh.objectelement_toolbox.tools.open_NewNode(this.container);  			
  		}
  		
  		
		/*-----------------------------------------------------
		*	edit_event
		* -----------------------------------------------------
		*/
   	  	if(type == 'onClick_event' && message.id == 'objectedit')
  		{
  		
  		
  		  		//var eventobj = new de.auster_gmbh.library.tools.eventObject('',this,null);
			
				//this.controlref.fireEvent('[*/JSObject].onCollapse',eventobj);
  		
			de.auster_gmbh.objectelement_toolbox.tools.open_Node(
			this.container,
			'NodeWeb',
			de.auster_gmbh.objectelement_toolbox.tools." . $funct . "s.neutral,
  			this.semref,
			this.mySVGObj,
			$pfad
			);  
  			//de.auster_gmbh.objectelement_toolbox.tools.functs.neutral
  			
  		}


  		
  		
		/*-----------------------------------------------------
		*	add_info
		* -----------------------------------------------------
		*/

   	  	if(type == 'onClick_event' && message.id == 'objectinfo')
  		{
  		

  			this.semref.findID(0, currentid[0]);
  			
  			de.auster_gmbh.objectelement_toolbox.tools.open_Node(
  			this.container,
  			'NodeInfo',
  			de.auster_gmbh.objectelement_toolbox.tools." . $funct . "s.info,
  			this.semref,
			this.mySVGObj,
			$pfad);  

  			
  			
  			
  			
  		}
  	
  	
  		/*-----------------------------------------------------
		*	valid_entry_event
		* -----------------------------------------------------
		*/
   	  	if(type == 'onblur_event' && message.id == 'URI')
  		{
  		
  		var arr = de.auster_gmbh.semanticelement.semantic_web.showListofIDs(message.value);
  		if(arr.length > 0)
  		message.value = arr[0];
  		else
  		message.value = '';
  		}

  		
  	
  	  	if(type == 'onClick_event' && message.id == 'okField')
  		{
  			var curnode = this.semref.getRef1();
			var stamp = ''; 
			var doc = '';
			
			if(false === (stamp = this.semref.getTrace()) )return false;
			if(false === (doc = this.semref.getOntology()) )return false;
			
			var request = new de.auster_gmbh.library.tools.qPComObject();
			//request.setAttribute('http://www.w3.org/1999/02/22-rdf-syntax-ns', 'about', 'boho');
			cur_id = request.setNewNode(doc, stamp, this.container[1]['InsertNode'].uri.getText());

			if(!(this.container[1]['InsertNode'].label == 'label' || this.container[1]['InsertNode'].label == ''))
			{
				request.setAttribute('data', '0', this.container[1]['InsertNode'].label.getText());
				request.setNewNode(doc, stamp, 'http://www.w3.org/2000/01/rdf-schema#label' ,cur_id);	
				// http://www.w3.org/2000/01/rdf-schema
				//label		
			}
			

			
			de.auster_gmbh.library.access.execute_request(request);

  			//alert(this.container[1]['InsertNode'].uri.getText() + 'in zeile 1020 gefunden');
  			//alert(escape(textInsertElement.getText()));
  			//alert(this.semref.curNode(1));
  			/*
  			//this.semref.aboutObj(1,'http://www.auster-gmbh.de/09/09/lib#' + encodeURI(textInsertElement.getText()));
			//this.semref.inheritObj('http://www.auster-gmbh.de/09/09/lib#' + encodeURI(textInsertElement.getText()));
			//var semid = this.semref.getID();
			
			//this.semref.setGraphLiteral(2,'http://www.w3.org/2000/01/rdf-schema#label',textInsertElement.getText());
			
			//this.semref.setGraphLiteral(2,'http://www.w3.org/2000/01/rdf-schema#comment',textAreaElement.getText());
  			
  			
  			
  			//textInsertElement.alterText('');
  			//textAreaElement.alterText('');
  			
  			var args_array = new Array();
  			var it = 0;
 
  			
  			alterOverviewPanel( this.mySVGObj , this.container[1]['NodeWeb'].findID(  'workbench'  ) , this.semref);
  			
  		
  			if(this.container[1]['NodeWeb'].closed)
  			{  		
  			args_array[it] = new Array();
  			args_array[it][0] = this.container[1]['NodeWeb'];
  			args_array[it][1] = 0;
  			args_array[it][2] = 0;
  			args_array[it++][3] = 'my1';
  			this.container[1]['NodeWeb'].closed = false;
  			}
  			  		
  			if(!this.container[1]['InsertNode'].closed)
  			{
   			args_array[it] = new Array();
  			args_array[it][0] = this.container[1]['InsertNode'];
  			args_array[it][1] = 0;
  			args_array[it][2] = (-1 * 300);
  			args_array[it][3] = 'my2';
  			this.container[1]['InsertNode'].closed = true;
  			}
  				//
  				
  			if(args_array.length > 0) de.auster_gmbh.graphicelement.sequenceMoveToPoint( args_array );
  			//this.container[1]['NodeWeb'].closed = false;
  			*/
  		} 
  	
		if( type == 'selected_object' ) 
		{
			this.setBasicElement(message);
			
			var args_array = new Array();
  			var it = 0;
  			  			
  			
  			args_array[it] = new Array();
  			args_array[it][0] = this.container[1]['InsertNode'];
  			args_array[it][1] = 0;
  			args_array[it][2] = 0;
  			args_array[it++][3] = 'my1';
  			this.container[1]['InsertNode'].closed = false;

  			args_array[it] = new Array();
  			args_array[it][0] = this.container[0];
  			args_array[it][1] = 0;
  			args_array[it][2] = 0;
  			args_array[it++][3] = 'my1';
  			
  			
			this.container[0].closed = false;
			de.auster_gmbh.graphicelement.sequenceMoveToPoint( args_array );
  			
			
		}
  	
  		if(type == 'init')
  		{
   			
   			if(this.myNodeid == '')
  			this.mySVGObj = message.parentNode;
  			else
  			this.mySVGObj = document.getElementById(this.myNodeid);
  			
  			
  			if(this.myToolBar == '')
  			this.mySVGToolBar = message.parentNode;
  			else
  			this.mySVGToolBar = document.getElementById(this.myToolBar);
  			
  			
  			


			this.container[1] = new Array();
			this.container[1]['NodeWeb'] = createOverviewPanel(this.mySVGObj,'Overview',$pfad, '#002e7a');
			this.container[1]['NodeWeb'].transit(0,-1 * (window.innerHeight - 20));
			this.container[1]['NodeWeb'].closed = true;
			this.container[1]['NodeInfo'] = createOverviewPanel(this.mySVGObj,'Info',$pfad, '#00ee7a');
			this.container[1]['NodeInfo'].transit(0,-1 * (window.innerHeight - 20));
			this.container[1]['NodeInfo'].closed = true;
			this.container[1]['InsertNode'] = createInsertPanel(this.mySVGObj,$pfad);
			this.container[1]['InsertNode'].transit(0,-1 * 400);
			this.container[1]['InsertNode'].closed = true;
			
			this.container[0] = createPrimaryPanel(0 , this.mySVGToolBar );


			this.container[0].transit((-1 * (window.innerWidth)) + 120,0);
			this.container[0].closed = true;
			
	
  			
  		}
  		
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
  	
  	var fireEvent =  $funct( type , message )
  	{
  		this.dataref.fireEvent( type , message );
  	}
  	
  } 
  
  $namespace = new $namespace.clazz(de.auster_gmbh.semanticelement.semantic_web,$pfad);
  //$namespace($pfad);
  
  //$pfad.addListener($namespace);

  
		"; 
	
		return $res;
	}
	
	public function __toString(){return 'javascript_display_element';}	
}

?>
