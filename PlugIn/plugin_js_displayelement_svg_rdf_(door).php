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
private $full_uri = array();

	function JSDisplay(/* System.Parser */ &$back, /* System.CurRef */ &$treepos)
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
  	this.dataref = control;
  	this.myNodeid = '" . $this->nodeId . "';
  	this.mySVGObj = null;
  	this.dataref.addListener(this);
  	
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
  	
  	
 		if(type == 'init')
  		{
			this.mySVGObj = document.getElementById(this.myNodeid);
						
			var visEl = new visualElement();
			//wall
			var svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,200);
			svg.setWayPoint(0,0);
			svg.setWayPoint(600,0);
			svg.setWayPoint(600,500);
			

			
			svg.setWayPoint(0,500);
			svg.setWayPoint(0,0);
			
			svg.setStyle('fill:#bbbbbb;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);

//wall deep
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,200);
			svg.setWayPoint(0,0);
			svg.setWayPoint(600,0);
			svg.setWayPoint(600,500);
			
			svg.setWayPoint(625,475);
			svg.setWayPoint(625,-25);
			svg.setWayPoint(25,-25);	
			
			svg.setWayPoint(0,0);
			
			svg.setStyle('fill:#999999;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//door1
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			
			svg.setWayPoint(425,500);
			svg.setWayPoint(425,100);
			svg.setWayPoint(175,100);
			svg.setWayPoint(175,500);
			svg.setWayPoint(425,500);
			
			

			
			svg.setStyle('fill:#eeeeee;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//shadow1
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			svg.setWayPoint(425,500);
			svg.setWayPoint(425,100);
			svg.setWayPoint(430,95);
			svg.setWayPoint(430,495);
			svg.setWayPoint(425,500);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//shadow2			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			svg.setWayPoint(175,100);
			svg.setWayPoint(425,100);
			svg.setWayPoint(430,95);
			svg.setWayPoint(180,95);
			svg.setWayPoint(175,100);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//hinge			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(395,225);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			svg.setWayPoint(440,95);
			svg.setWayPoint(440,145);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(395,225);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			
			svg.setWayPoint(440,95);
			svg.setWayPoint(435,95);
			
			svg.setWayPoint(430,100);
			svg.setWayPoint(430,150);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#888888;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//end hinge			
//hinge			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(395,535);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			svg.setWayPoint(440,95);
			svg.setWayPoint(440,145);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(395,535);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			
			svg.setWayPoint(440,95);
			svg.setWayPoint(435,95);
			
			svg.setWayPoint(430,100);
			svg.setWayPoint(430,150);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#888888;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//end hinge	

			visEl.init();
			
			
			/*
			if(message == 139 || message == 145 || message == 178 || message == 184 )
			{
			var visEl = new visualElement();
			//wall
			var svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,200);
			svg.setWayPoint(0,0);
			svg.setWayPoint(600,0);
			svg.setWayPoint(600,500);
			

			
			svg.setWayPoint(0,500);
			svg.setWayPoint(0,0);
			
			svg.setStyle('fill:#bbbbbb;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);

//wall deep
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,200);
			svg.setWayPoint(0,0);
			svg.setWayPoint(600,0);
			svg.setWayPoint(600,500);
			
			svg.setWayPoint(625,475);
			svg.setWayPoint(625,-25);
			svg.setWayPoint(25,-25);	
			
			svg.setWayPoint(0,0);
			
			svg.setStyle('fill:#999999;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			
			//hinge			

			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(40,225);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			
			svg.setWayPoint(440,95);
			svg.setWayPoint(435,95);
			
			svg.setWayPoint(430,100);
			svg.setWayPoint(430,150);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#888888;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//end hinge			
//hinge			

			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(40,535);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			
			svg.setWayPoint(440,95);
			svg.setWayPoint(435,95);
			
			svg.setWayPoint(430,100);
			svg.setWayPoint(430,150);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#888888;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//end hinge	

//door1
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(300,205);

			
			svg.setWayPoint(405,500);
			svg.setWayPoint(405,100);
			svg.setWayPoint(175,100);
			svg.setWayPoint(175,500);
			svg.setWayPoint(405,500);
			
			

			
			svg.setStyle('fill:#eeeeee;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//shadow1
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(300,205);

			svg.setWayPoint(405,500);
			svg.setWayPoint(405,100);
			svg.setWayPoint(410,95);
			svg.setWayPoint(410,495);
			svg.setWayPoint(405,500);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//shadow2			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(300,205);

			svg.setWayPoint(175,100);
			svg.setWayPoint(405,100);
			svg.setWayPoint(410,95);
			svg.setWayPoint(180,95);
			svg.setWayPoint(175,100);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);


//door1
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(535,205);

			
			svg.setWayPoint(405,500);
			svg.setWayPoint(405,100);
			svg.setWayPoint(175,100);
			svg.setWayPoint(175,500);
			svg.setWayPoint(405,500);
			
			

			
			svg.setStyle('fill:#eeeeee;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//shadow1
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(535,205);

			svg.setWayPoint(405,500);
			svg.setWayPoint(405,100);
			svg.setWayPoint(410,95);
			svg.setWayPoint(410,495);
			svg.setWayPoint(405,500);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//shadow2			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(535,205);

			svg.setWayPoint(175,100);
			svg.setWayPoint(405,100);
			svg.setWayPoint(410,95);
			svg.setWayPoint(180,95);
			svg.setWayPoint(175,100);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//hinge			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(510,225);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			svg.setWayPoint(440,95);
			svg.setWayPoint(440,145);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(510,225);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			
			svg.setWayPoint(440,95);
			svg.setWayPoint(435,95);
			
			svg.setWayPoint(430,100);
			svg.setWayPoint(430,150);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#888888;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//end hinge			
//hinge			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(510,535);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			svg.setWayPoint(440,95);
			svg.setWayPoint(440,145);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(510,535);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			
			svg.setWayPoint(440,95);
			svg.setWayPoint(435,95);
			
			svg.setWayPoint(430,100);
			svg.setWayPoint(430,150);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#888888;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
//end hinge	

			visEl.init();
			} */
			
		}
  	
  		if(type == 'init')
  		{
  			if(this.myNodeid == '')
  			this.mySVGObj = message.parentNode;
  			else
  			this.mySVGObj = document.getElementById(this.myNodeid);
  			//alert(this.mySVGObj.getAttribute('id'));
  			/*
  			var shape = document.createElementNS('http://www.w3.org/2000/svg', 'path');

  				shape.setAttributeNS(null, 'id', 'control');
  				shape.setAttributeNS(null, 'd', 'M 44.326171,52.954434 L 194.31539,52.463303 L 211.72122,66.560352 L 211.62628,166.80131 L 44.326171,166.80131 L 44.326171,52.954434 z');
  				shape.setAttributeNS(null, 'style', 'fill:#0000ff;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');

  				this.mySVGObj.appendChild(shape);
				*/
			
			var visEl = new visualElement();
/*			
			var svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(45,40);
			svg.setWayPoint(0,0);
			svg.setWayPoint(300,0);
			svg.setWayPoint(320,20);
			svg.setWayPoint(320,200);
			svg.setWayPoint(0,300);
			svg.setWayPoint(0,0);
			svg.setStyle('fill:#005fff;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control');
			
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(50,90);
			svg.setWayPoint(0,0);
			svg.setWayPoint(300,0);
			svg.setWayPoint(300,50);
			svg.setWayPoint(0,50);
			svg.setWayPoint(0,0);
			
			svg.setStyle('fill:#3300ff;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGText'](this.mySVGObj);
			svg.transit(50,90);
			svg.setWayPoint(0,30);

			svg.setText('booh');
			svg.setStyle('font-size:30;font-family:Comic Sans MS, Arial; font-weight:bold;font-style:oblique;stroke:black;stroke-width:1;fill:none');
			svg.setID('controltext');
			visEl.add(svg);

			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
svg.transit(50,70);
			svg.setWayPoint(198.29762 + 90 ,65.860403 - 70);
			svg.setWayPoint(190.58577 + 90 ,73.734851 - 70);
			svg.setWayPoint(198.29762 + 90 ,82.646873 - 70); 
			svg.setWayPoint(198.29762 + 90 ,78.577423 - 70); 
			svg.setWayPoint(201.55508 + 90 ,78.577423 - 70); 
			svg.setWayPoint(201.55508 + 90 ,82.646873 - 70); 
			svg.setWayPoint(209.06784 + 90 ,74.127283 - 70); 
			svg.setWayPoint(201.55508 + 90 ,65.860403 - 70); 
			svg.setWayPoint(201.55508 + 90 ,69.929853 - 70); 
			svg.setWayPoint(198.29762 + 90 ,69.929853 - 70); 
			svg.setWayPoint(198.29762 + 90 ,65.860403 - 70);
			
			

			
			svg.setStyle('fill:none;fill-rule:evenodd;stroke:#ffffff;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			


			visEl.init();

*/
			visEl = new visualElement();

			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,200);
			svg.setWayPoint(0,0);
			svg.setWayPoint(600,0);
			svg.setWayPoint(600,500);
			

			
			svg.setWayPoint(0,500);
			svg.setWayPoint(0,0);
			
			svg.setStyle('fill:#bbbbbb;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);

			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,200);
			svg.setWayPoint(0,0);
			svg.setWayPoint(600,0);
			svg.setWayPoint(600,500);
			
			svg.setWayPoint(625,475);
			svg.setWayPoint(625,-25);
			svg.setWayPoint(25,-25);	
			
			svg.setWayPoint(0,0);
			
			svg.setStyle('fill:#999999;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);

			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			
			svg.setWayPoint(425,500);
			svg.setWayPoint(425,100);
			svg.setWayPoint(175,100);
			svg.setWayPoint(175,500);
			svg.setWayPoint(425,500);
			
			

			
			svg.setStyle('fill:#eeeeee;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);

			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			svg.setWayPoint(425,500);
			svg.setWayPoint(425,100);
			svg.setWayPoint(430,95);
			svg.setWayPoint(430,495);
			svg.setWayPoint(425,500);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			svg.setWayPoint(175,100);
			svg.setWayPoint(425,100);
			svg.setWayPoint(430,95);
			svg.setWayPoint(180,95);
			svg.setWayPoint(175,100);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			svg.setWayPoint(440,95);
			svg.setWayPoint(440,145);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			svg.setWayPoint(435,150);
			svg.setWayPoint(435,100);
			
			svg.setWayPoint(440,95);
			svg.setWayPoint(435,95);
			
			svg.setWayPoint(430,100);
			svg.setWayPoint(430,150);
			svg.setWayPoint(435,150);
			
			svg.setStyle('fill:#888888;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			svg.setWayPoint(430,250);
			svg.setWayPoint(430,200);
			svg.setWayPoint(435,195);
			svg.setWayPoint(435,245);
			svg.setWayPoint(430,250);
			
			svg.setStyle('fill:#333333;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);
			
			svg = new clazzSVG['SVGPath'](this.mySVGObj);
			
			svg.transit(400,205);

			svg.setWayPoint(430,250);
			svg.setWayPoint(430,200);
			
			svg.setWayPoint(435,195);
			svg.setWayPoint(430,195);
			
			svg.setWayPoint(425,200);
			svg.setWayPoint(425,250);
			svg.setWayPoint(430,250);
			
			svg.setStyle('fill:#888888;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1');
			svg.setID('control1');
			visEl.add(svg);

			//visEl.init();
			

  			
  		}
  		
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
