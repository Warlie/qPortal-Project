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

require_once("plugin_interface.php");

class JSEngine extends plugin 
{
private $test = 0;
private $rst = array();
private $uri;
private $template;
private $tag_name;
private $full_uri = array();
private $graphic_uri = array();
private $content;
private $node_container_id;
private $main_tag_id = '';
private $documents = array();
private $semantic_uri = array();
private $type_script = 'text/ecmascript'; //text/javascript //text/ecmascript
private $script_link = 'xlink:href';

	function __construct(/* System.Content */ &$back, /* System.CurRef */ &$treepos)
	{
		
		$this->back= &$back->getXMLObj();
		
		$this->treepos = &$value;
		
		$this->content = &$back;
		//$this->id = $value; , &$id
		
	}
	
			
	/**
	*@func: MOVEFIRST = goes to first record
	*/
		
	public function moveFirst()
	{$this->pos = 0;}
	
	/**
	*@func: MOVELAST = goes to last record
	*/
	public function moveLast()
	{$this->pos = count($this->table) - 1;}
	
	/**
	*@func: HAS_TAG = returns a boolean value refering to the seeking tag, descripted by xpath 
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
		if($value instanceof plugin_js )
		$this->rst[count($this->rst)] = &$value;
		else
		$logger_class->setAssert('Error: Object of type"' . get_Clazz($value) . '" dont has correct parent cla' . 'ss "plugin_js"(plugin_js_generator_svg_rdf.php,96)',1);
		
	}
	}
	

	/**
	*
	*@-------------------------------------------
	*/
	//parameterausgabe
	public function getAdditiveSource(){}
	
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
		
	public function set_Node_ID( $id )
	{
		$this->node_container_id = $id;
	}
	
	public function set_Script_Type( $type )
	{
		$this->type_script = $type;
	}
	
	public function set_Script_link( $type )
	{
		$this->script_link = $type;
	}
	
	public function set_loading_tag($uri,$tag_name, $tag_attib, $tag_attib_value)
	{
		$this->uri = $uri;
		
		$this->tag_name = $tag_name;
		$this->tag_id[$tag_attib] = $tag_attib_value;
		
		//echo $name;
	}
	
	public function main_Graphic_Element_ID($id)
	{
		$this->main_tag_id = $id;
	}

	public function set_qualified_URI( $uri )
	{
	
		$this->full_uri = explode('.',$uri); 
	}
	
	public function set_graphical_URI( $uri )
	{
	
		$this->graphic_uri = explode('.',$uri); 
	}
	
	public function set_document_ids( $namelist )
	{
	
		$this->documents = explode(';', $namelist );
		//$this->graphic_uri = explode('.',$uri); 
	}
	
	public function set_URI( $urilist )
	{
		$this->semantic_uri = explode(';', $urilist );
		//$this->graphic_uri = explode('.',$uri); 
	}

	private function javascript_body()
	{
	
	if(count($this->full_uri) < 1)return " //namespace is not available for this javascriptmodul!";
	
	$funct = 'funct' . 'ion';
	$clas = 'cla' . 'ss';

	//echo $this->back->cur_node() . '-';

	//
	
	
	$namespace = '';
	
	
	for($i = 0 ; $i < count($this->graphic_uri);$i++)
	{
		//-------------build namespaces to to test ---------
		

		if($i == 0)
		{
		$namespace .= $this->graphic_uri[0];
		
		$res = ' var ' . $this->graphic_uri[0] . ";\n" .
		" if(!$namespace) $namespace = {};" . "\n" .
   		" else if (typeof $namespace != 'object')" . "\n" .
   		" throw new Error('$namespace allready exists and os not an object'); \n";
		}
		else
		{
		
		$namespace .= '.' . $this->graphic_uri[$i];
		$res .= "\n" .
		" if(!$namespace) $namespace = {};" . "\n" .
   		" else if (typeof $namespace != 'object')" . "\n" .
   		" throw new Error('$namespace allready exists and os not an object'); \n";
		}
	

   		
   		
	}
	

	
	
	$namespace = '';
	
	for($i = 0 ; $i < count($this->full_uri);$i++)
	{
		//-------------build namespaces to to test ---------
		

		if($i == 0)
		{
		$namespace .= $this->full_uri[0];
		
		$res .= " if(!$namespace) $namespace = {};" . "\n" .
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
	
	
	$script_add = '';
	for($i = 0 ; count($this->rst) > $i ; $i++)
	{
		
		$script_add .= $this->rst[$i]->build_script($namespace);
	}

	
	$this->back->set_node_attrib('onload',"$namespace.onDoLoad(this);");
	
	
	
	$message = $this->back->cur_node();
	
	$docList = '';
	
	for($i = 0; count($this->documents) > $i ; $i++)
	{
	
	$docList .= 'this.workstack[' . $i . '] = \'' . $this->documents[$i] . "';\n";
		
	}
	

	$URIList = '';
	
	for($i = 0; count($this->semantic_uri) > $i ; $i++)
	{
	
	$URIList .= 'this.listURLs[' . $i . '] = \'' . $this->semantic_uri[$i] . "';\n 	";
		
	}
	
	return "
   $res	




 $namespace.clazz = $funct JSEngine (){
 	
 	this.controlElement =  null;  /*visual element*/
 	this.mainGraphic;
 	this.NodeId = '" . $this->node_container_id . "';
 	this.main_tag_id = '" . $this->main_tag_id . "';
 	this.curheight ;
 	this.listURLs = new Array(); /*list of urls to load on start*/
 	$URIList
 	
 	
 	this.toString = function(){return 'JSEngine';}
 	
 	this.setControlElement = $funct(control) /*func-tion to initial element in svg-tree*/
 	{
 	this.controlElement = control;
 	this.controlElement.setAttributeNS(null, 'y', 0.0);
  	this.controlElement.setAttributeNS(null, 'width', 5.0);
 	
 	
 	}
 	

 	
 	this.onDoLoad = $funct() /*start function */
 	{
 	var obj = document.getElementById(this.main_tag_id);
 	
 	this.semantic_web(); /* creates basic semantic structur */
 	

 	
 	var myclazz = $funct(){};
 	var ajax_clazz = $funct AjaxObject(){};
 	ajax_clazz.prototype = this.ajaxConnection;
 	//ajax_clazz.prototype.constructor = function(){};
 	de.auster_gmbh.library.access.ajaxobj = new ajax_clazz();
 	de.auster_gmbh.library.access.ajaxobj.getObserver(this);
 	
 	de.auster_gmbh.library.access.ajaxobj.setMode('ONTOLOGY_STRUCTUR');
 	de.auster_gmbh.library.access.ajaxobj.load();


	/* add the Controlcenter to the semantic web */ 
	var coce = de.auster_gmbh.semanticelement.semantic_web.setGraphLiteralObj(
	de.auster_gmbh.semanticelement.semantic_web.setGraphLiteralObj(
  	de.auster_gmbh.semanticelement.semantic_web.addObjectToAccessable(
  		'http://www.auster-gmbh.de/2010/08/anttree-lib#Controlcenter'
  		,new de.auster_gmbh.Controlcenter(de.auster_gmbh.semanticelement.semantic_web,this)
  		,de.auster_gmbh.semanticelement.const.GENERIC_SEMANTIC
  		,'http://www.auster-gmbh.de/2006/05/pedl-lib#Class'
  		,'http://www.auster-gmbh.de/2010/08/anttree-lib')
  	,'http://www.w3.org/2000/01/rdf-schema#label'
  	,'ate:Controlcenter')
  	,'http://www.w3.org/2000/01/rdf-schema#comment'
  	,'offers an access to the visuall system');
  	
  	de.auster_gmbh.semanticelement.semantic_web.setManuallyGraph(
  	coce
  	, de.auster_gmbh.semanticelement.semantic_web.getObjecttoClazz('http://www.w3.org/2000/01/rdf-schema#subClassOf')
  	, de.auster_gmbh.semanticelement.semantic_web.getObjByRepresentationObj('http://www.auster-gmbh.de/2006/05/pedl-lib#Class')
  	);
  	
  	
  	de.auster_gmbh.semanticelement.semantic_web.setManuallyGraph(
  	coce
  	, de.auster_gmbh.semanticelement.semantic_web.getObjecttoClazz('http://www.w3.org/2000/01/rdf-schema#subClassOf')
  	, de.auster_gmbh.semanticelement.semantic_web.getObjByRepresentationObj('http://www.auster-gmbh.de/2006/05/pedl-lib#Class')
  	);
  	
  	
  	de.auster_gmbh.library.pedl.createfunction(coce, 
  	'http://www.auster-gmbh.de/2010/08/anttree-lib#Controlcenter.buildUp',
  	new Array('http://www.auster-gmbh.de/2010/08/anttree-lib#Controlcenter.buildUp.arg'),
  	'ate:Controlcenter.buildUp',
  	new Array('ate:Controlcenter.buildUp.arg'),
  	'function to create a new document');
  	//de.auster_gmbh.semanticelement.semantic_web.addBag(coce, add);
 	
 	
 	this.mainGraphic = obj;
 	this.curheight = window.innerHeight;
 	
 	de.auster_gmbh.library.controlelements.controllvis = obj;
 	
 	de.auster_gmbh.library.controlelements.controlbar(obj,window.innerWidth,this.curheight);
	

 	
 	this.fireEvent('init',obj);
 	
 	};
 	
 	this.VALUE = 0;
 	
 	this.GRAPH = 1;
 	
 	this.workstack = new Array();
	$docList
 	
 	this.onDoEvent = $funct(obj,typ)
 	{
 	//alert(this.controlElement);
 	this.controlElement.setAttributeNS(null, 'y', 0.0);

  	this.parent = this.controlElement.parentNode;
        var g = null;
        var text = null;
        g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        parent.appendChild(g);
        
        text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        var textNode = document.createTextNode('foobar');

	//text.insertData(0, ' Fragt der Barkeeper:');


 	}
 	
 	this.listeners = new Array();
 	

 	

 	this.addListener = $funct(addObj)
 	{

	
	this.listeners[this.listeners.length] = addObj; 
 	

 		
 	}
 	/*makes a list of commands*/
 	var divides = $funct(mytype)
 	{	var pos = 0;
 		var num = 0;
 		var mytypestr = mytype.toString();
 		num = mytype.indexOf('&');
 		var commandArray = new Array();
 		while(-1 != (num = mytypestr.indexOf('&')))
 		{
 			commandArray[pos++] = mytypestr.substring(0,num);
 			mytypestr = mytypestr.substring(num + 1,mytypestr.length);
 		}	
 		commandArray[pos++] = mytypestr.substring(0,mytypestr.length);
 		
 	 return commandArray;
 	}
 	
 	/*funct to check access */
 	var has_Sharing = $funct(mytype,obj)
 	{
 	
 		if('init' == mytype)return true;
 		var num = 0;
 		var mytypestr = mytype.toString();
 		var commandArray = new Array();
 		var allow;
 		var prohib;
 		var listofAllowed = new Array();
 		var listofProhibited = new Array();
 		//alert(mytype.indexOf('&'));
 		var pos = 0;
 		var pos2 = 0;
 		var res = new Array();


			
 			if(-1 != (num = mytypestr.indexOf('.')))
 			{
 				mytypestr = mytypestr.substring(0,num);
 				 
 			}
 			
 			if(num == -1)return true;
 			
 			if(-1 != (num = mytypestr.indexOf(']')))
 			{
 				mytypestr = mytypestr.substring(mytypestr.indexOf('[') + 1,num);
 			}
 			
 			if(-1 != (num = mytypestr.indexOf('/')))
 			{
 				
 				allow = mytypestr.substring(0,num);
 				prohib = mytypestr.substring(num + 1,mytypestr.length);
 			}
 			else
 			{
 				allow = mytypestr.toString();
 				prohib = '';
 			}
 			 
 			 while(-1 != (num = allow.indexOf(',')))
 			 {
 			 listofAllowed[pos2++] = allow.substring(0,num - 1);
 			 allow = allow.substring(num + 1,allow.length);
 			 }
 			 listofAllowed[pos2] = allow;
 			 
 			 pos2 = 0;
 			 while(-1 != (num = prohib.indexOf(',')))
 			 {
 			 listofProhibited[pos2++] = prohib.substring(0,num - 1);
 			 prohib = prohib.substring(num + 1,prohib.length);
 			 }
 			 listofProhibited[pos2] = prohib;
 			 
 			 
 			 for(pos2 = 0;pos2 < listofProhibited.length;pos2++)
 			 {
 			 	if(listofProhibited[pos2] == obj.toString())return false;
 			 }
 			 
 			 for(pos2 = 0;pos2 <  listofAllowed.length;pos2++)
 			 {
 			 	if( listofAllowed[pos2] == obj.toString() || listofAllowed[pos2] == '*')return true;
 			 }
 			
 		return false;
 	};
 	
 	this.fireEvent = $funct(type, message)
 	{
 		
 	 var commands = divides(type);
 	 var i;
 	 
 	 for(i = 0;commands.length > i ;i++)
 	 {
 		
 		for( aaaji = 0 ; this.listeners.length > aaaji; aaaji++)
 		{
 		
 			if(has_Sharing(commands[i],this.listeners[aaaji]))
 			{
 			this.listeners[aaaji].event( commands[i] , message );
 			}
 		}
 	 }
 	}

 	//ajax connection
 	this.ajaxConnection = de.auster_gmbh.library.access.ajaxConnection;
 	this.ajaxConnection.workstack = this.workstack;
 	
 	var size = this.ajaxConnection.workstack.length;
 	
 	for(var i = 0 ; this.listURLs.length > i; i++ )
 	 this.ajaxConnection.workstack[size++]=this.listURLs[i];
 	
 	this.onclick_event = $funct(eventObject)
 	{
 		
 	 	for( aaaji = 0 ; this.listeners.length > aaaji; aaaji++)
 		{
 			
 			this.listeners[aaaji].event( 'onClick_event' , eventObject );
 		}
 	
 	
 	}
 	
 	this.ondbclick_event = $funct(eventObject)
 	{

 	 	for( aaaji = 0 ; this.listeners.length > aaaji; aaaji++)
 		{
 		
 			this.listeners[aaaji].event( 'ondbClick_event' , eventObject );

 			
 		}

 	
 	}
 	
 	this.onmouseover_event = $funct(eventObject)
 	{
 		
 	 	for( aaaji = 0 ; this.listeners.length > aaaji; aaaji++)
 		{
 		
 			this.listeners[aaaji].event( 'onmouseover_event' , eventObject );
 		}
 	
 	
 	}
 	
 	this.onmouseout_event = $funct(eventObject)
 	{
 		
 	 	for( aaaji = 0 ; this.listeners.length > aaaji; aaaji++)
 		{
 		
 			this.listeners[aaaji].event( 'onmouseout_event' , eventObject );
 		}
 	
 	
 	}
 	
 	this.onkeyup_event = $funct(eventObject)
 	{
 		
 	 	for( aaaji = 0 ; this.listeners.length > aaaji; aaaji++)
 		{
 		
 			this.listeners[aaaji].event( 'onkeyup_event' , eventObject );
 		}
 	
 	
 	}
 	
 	this.onblur_event = $funct(eventObject)
 	{
 		
 	 	for( aaaji = 0 ; this.listeners.length > aaaji; aaaji++)
 		{
 		
 			this.listeners[aaaji].event( 'onblur_event' , eventObject );
 		}
 	
 	
 	}
 	
 	this.event = $funct(type,message)
 	{
 		this.fireEvent(type,message);
 	}
 	
 	this.semantic_web = de.auster_gmbh.library.access.semantic_web;
 	
 };
 
 $namespace = new $namespace.clazz();

 $script_add

/*
de.auster_gmbh.semanticelement['http://www.w3.org/2000/svg#image'].prototype = new 
de.auster_gmbh.graphicelement.svg.SVGImage(document.getElementById(de.auster_gmbh.accesselement.NodeId), de.auster_gmbh.displayelement.pfad);
*/
//document.getElementsByName('svg')[0].onload = de.auster_gmbh.accesselement.onDoLoad();
	";}
	

	public function build_script()
	{
	

	
		$pos_stamp = $this->back->position_stamp();
		$this->back->change_URI($this->uri);
		//$save_old_Node = $this->back->show_xmlelement();
		//$new_pos = $this->back->posOfPrev();
		//$this->back->parent_node();
		//$this->back->create_node($this->back->position_stamp(),$new_pos);
		//$this->back->set_node_name("script");
		
		//echo $this->back->position_stamp() . ' ';
		//echo $this->back->cur_node();
		//echo $this->back->posOfPrev();
		
		//$this->back->child_node($save_old_Node->posInPrev());
		
		//echo $this->back->cur_node();
		
		$posintree = $this->back->posOfPrev();
		$this->back->parent_node();
		
/*		
		  <script type="text/ecmascript" xlink:href="script/funct_lib.js" />
  <script type="text/ecmascript" xlink:href="script/graphic_elements.js" />
  <script type="text/ecmascript" xlink:href="script/SPARQL.js" />
  <script type="text/ecmascript" xlink:href="script/semantic_elements.js" />
  <script type="text/ecmascript" xlink:href="script/XML-Schema.js" />
  <script type="text/ecmascript" xlink:href="script/rdf.js" />
  <script type="text/ecmascript" xlink:href="script/owl.js" />
  <script type="text/ecmascript" xlink:href="script/xlink.js" />
  <script type="text/ecmascript" xlink:href="script/pedl.js" />
  <script type="text/ecmascript" xlink:href="script/OWL-Service.js" />
  <script type="text/ecmascript" xlink:href="script/SVG.js" />
  <script type="text/ecmascript" xlink:href="script/anttree.js" />
  <script type="text/ecmascript" xlink:href="script/overview.js" />
  <script type="text/ecmascript" xlink:href="script/modifications.js" />
  */
 		for($i = 0 ; count($this->rst) > $i ; $i++)
		{
		
		$add_url = $this->rst[$i]->getAdditiveSource();
		if($add_url)
		{
		
			
			$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => $add_url), $posintree );
			$this->back->parent_node();
		}
		
		}
  
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/modifications.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/overview.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/anttree.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/SVG.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/mathml.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/xhtml.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/OWL-Service.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/pedl.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/xlink.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/owl.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/suface_lib.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/rdf.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/XML-Schema.js'), $posintree );		
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/semantic_elements.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/SPARQL.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/graphic_elements.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/jsControlStructur.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/funct_parser_lib.js'), $posintree );
$this->back->parent_node();
$this->back->create_Ns_Node('script', null, array('type' => $this->type_script, $this->script_link => 'script/funct_lib.js'), $posintree );

			



		$attrib['http://www.w3.org/2000/svg#id'] = $this->tag_id;
		$this->back->seek_node($this->tag_name,$this->tag_id);

		
		$res = $this->javascript_body();
		$this->back->go_to_stamp($pos_stamp);
		return $res;
	}
		
	public function __toString(){return 'javascript_generator';}	
}

?>
