<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class SVG_Overview_handle extends Interface_handle 
{

	
	private $idx_on_table = 0;
	private $dbltable = array();
	private $idxtable_nodePos = array();
	private $pos_request = array();
	private $pos_inherit = array();
	private $cur_id = 0;

	function parse_document(&$source)
	{
	
		$is_obj = ($source instanceof FileHandle);
		//$is_obj = is_subclass_of($source, 'FileHandle');
		
		$this->parser = xml_parser_create(); //'UTF-8'

                        xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, $this->attribute_values['XML_OPTION_CASE_FOLDING'] );
			xml_parser_set_option( $this->parser, XML_OPTION_TARGET_ENCODING, 'ISO-8859-1' );
                        xml_set_object($this->parser, $this->base_object);
			
			
                        xml_set_element_handler($this->parser, "tag_open", "tag_close");
                        xml_set_character_data_handler($this->parser, "cdata");
			
		
		
			

		
		if(!$is_obj)
			{

				$allRows = explode("\n",$source); 
				if(!xml_parse($this->parser, $source)){
                        
                                
					$lineNum = xml_get_current_line_number($this->parser);
					echo xml_error_string(xml_get_error_code($this->parser));
					echo $lineNum;
					echo ' in rowcontent:' . $allRows[$lineNum-1] . '<br>';
					return 2;


				}
				
			}
			else
			{
				if(!$source->toPos(0))echo 'Error on reseting pointer in "CSV_handle" in line 61!';
				$i = 1;
				
				while($source->eof())
				{
				
				if (!xml_parse($this->parser, $source->get_line())) {
                        
                                
					$lineNum = xml_get_current_line_number($this->parser);
					echo xml_error_string(xml_get_error_code($this->parser));
					echo $lineNum;
					echo ' in rowcontent:' . $allRows[$lineNum-1] . '<br>';
					return 2;


				}
				$i++;
				}
				$source->close_File();
			}
		
		
		xml_parser_free($this->parser); 
		unset($this->parser);
		return 0;
		
	}
	
	function save_back($format, $send_header = false)
	{

global $logger_class;		
		/**
		*
		*
		*/
		
		$printall = false;
		if( $this->attribute_values['OUTPUT'] == 'ALL')
		{
		$printall = true;

		}
      switch ($format)
      {
      case 'HTML': $arg = 'ISO-8859-5';
      break;
      case 'UTF-8': $arg = 'UTF-8';
      }

header("Content-type: application/xhtml+xml");

      $nl = chr(13) . chr(10);
      

      if($this->base_object->DOC[$this->base_object->idx] <> '')$res .= $this->base_object->DOC[$this->idx];


$dblx =  150.0;
$dbly =  150.0;

$head_res;
$head2_res;
$foot_res;

$this->idx_on_table = 0;
$this->dbltable[0][0] = 20.0;
$this->dbltable[0][1] = 170.0;

/**
*
*
*/

$head_res =  '<?xml version="1.0"?>
<!DOCTYPE svg PUBLIC
    "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN"
    "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd"[
<!ENTITY % SVG.prefixed "IGNORE" >
<!ENTITY % XHTML.prefixed "INCLUDE" >
<!ENTITY % XHTML.prefix "xhtml" >
<!ENTITY % MATHML.prefixed "INCLUDE" >
<!ENTITY % MATHML.prefix "math" >
<!ELEMENT dataAboutNode (#PCDATA)>
<!ATTLIST dataAboutNode
   id            ENTITY              #REQUIRED
   node            ENTITY              #REQUIRED
>


]>
<svg
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:cc="http://creativecommons.org/ns#"
   xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
   xmlns:svg="http://www.w3.org/2000/svg"
   xmlns="http://www.w3.org/2000/svg"
   xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
   xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
   xmlns:car="http:/www.auster-gmbh.de/carry/09/car"
   xmlns:xhtml="http://www.w3.org/1999/xhtml"
   width="1210mm"
   height="25297mm"
   id="svg2"
   sodipodi:version="0.32"
   inkscape:version="0.46"
   sodipodi:docname="hilfe.svg"
   inkscape:output_extension="org.inkscape.output.svg.inkscape">
   <defs>
          <linearGradient id = "linearGradient3167">
            <stop stop-color = "#00EE00" offset = "0%"/>
            <stop stop-color = "#0000EE" offset = "100%"/>
        </linearGradient>
        <linearGradient id = "linearGradient3168">
            <stop stop-color = "#EE00EE" offset = "0%"/>
            <stop stop-color = "#EE0000" offset = "100%"/>
        </linearGradient>
   </defs>
   <script language="JavaScript" type="text/javascript"><![CDATA[ 
 
   function helpobject()
   {}
   function show_element(svg_object)
   {
   	//alert(svg_object.attributes["car:key"].nodeValue);
   }
   
   var de;
   
   if(!de) de = {};
   else if (typeof de != "object")
   	throw new Error("de allready exists and os not an object");
   	
   if(!de.auster_gmbh) de.auster_gmbh = {};
      else if (typeof de.auster_gmbh != "object")
   	throw new Error("de.auster-gmbh allready exists and is not an object");
   
    if(!de.auster_gmbh.xmleditor) de.auster_gmbh.xmleditor = {};
      else if (typeof de.auster-gmbh.xmleditor != "object")
   	throw new Error("de.auster-gmbh.xmleditor allready exists and is not an object");
 
 de.auster_gmbh.xmleditor.Class = {
 	
 	controlElement: null,
 	
 	setControlElement: function(control)
 	{
 	this.controlElement = control;
 	this.controlElement.setAttributeNS(null, \'y\', 0.0);
  	this.controlElement.setAttributeNS(null, \'width\', 5.0);
 	
 	
 	},
 	
 	CSV: function(string)
 	{
		var mycommand =  string;
		
		
		
		var Line_Element = function()
		{
			var line_elements = new Array();
			this.addElement = function(column)
			{
			line_elements[line_elements.length] = column;
			}
		}
		
		var column = function( tagvalue)
		{
			this.value = tagvalue;
		}
		
		var list = function()
		{
			var list_elements = new Array();
			this.addElement = function(line)
			{
			list_elements[list_elements.length] = line;
			}
		
		
		}
		
		var tree = new list();
		var point = 0
		var line = new Array();
		var countup = 0;
		var lineObject = null;
		
		// finds all lines in cdata
		while( -1 < (point = mycommand.search( /\n/ )))
		{
			//abridge the mycommand and save the lines in an array
			line[countup++] = mycommand.slice(0,point);
			mycommand = mycommand.slice(point + 1);
		
		}
		
		line[countup] = mycommand.slice(point + 1);
		
		for(i = 0; i <= countup; i++)
		{
		
		lineObject = new Line_Element();
		
		while( -1 < (point = line[i].search( ";" )))
		{
		
		lineObject.addElement( new column(line[i].slice(0,point)));
		line[i] = line[i].slice(point + 1);
		
		}
		lineObject.addElement( new column(line[i].slice(point + 1)));
		tree.addElement(lineObject);
		}
		
		//alert(line);
		

		 	
 	},
 	
 	onDoEvent: function(obj,typ)
 	{
 	//alert(this.controlElement);
 	this.controlElement.setAttributeNS(null, \'y\', 0.0);
  	this.controlElement.setAttributeNS(null, \'width\', 300.0);
  	parent = this.controlElement.parentNode;
        var g = null;
        var text = null;
        g = document.createElementNS("http://www.w3.org/2000/svg", "g");
        parent.appendChild(g);
        
        //text = document.createElementNS("http://www.w3.org/2000/svg", "text");
        //var textNode = document.createTextNode("foobar");
        //text.setAttributeNS(null, "style", "font-size:40px;font-style:normal;font-weight:normal;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;font-family:Bitstream Vera Sans" );
        //text.setAttributeNS(null, "x", 80);
        //text.setAttributeNS(null, "y", 50);
	//var tar = text.target;
	//var subtext = document.createElementNS("http://www.w3.org/2000/svg", "tspan");
	//text.insertData(0, " Fragt der Barkeeper:");

        //parent.appendChild(text);

 	},
 	
 	menueSwitch: function()
 	{
 		if(this.controlElement.getAttributeNode("x").nodeValue < -1.0 )
 		{
 			this.controlElement.setAttributeNS(null, \'x\', 0.0);
 			document.getElementsByTagName("foreignObject")[0].setAttributeNS(null, \'x\',0.0);
 		}
 		else
 		{
 			this.controlElement.setAttributeNS(null, \'x\', -595.0);
 			document.getElementsByTagName("foreignObject")[0].setAttributeNS(null, \'x\',-595.0);
 		}
 	},
 	
 	drawLine: function(obj,vis,typ)
 	{
 	
 		
 	
 		if(document.getElementById(vis).style.visibility != "visible")
 		{
 		document.getElementById(vis).style.visibility = "visible";
 		 
 		this.controlElement.setAttributeNS(null, \'y\', window.pageYOffset);
 		this.controlElement.setAttributeNS(null, \'x\', -595.0);
		document.getElementsByTagName("foreignObject")[0].setAttributeNS(null, \'y\', window.pageYOffset);
		document.getElementsByTagName("foreignObject")[0].setAttributeNS(null, \'x\',-595.0);
  		this.controlElement.setAttributeNS(null, \'width\', 600.0);
 		var newLI = document.createElement("h1");
  		
  		var usedCircle = document.getElementById(obj);
  		
  		var csvElement = document.getElementsByTagName("dataAboutNode");
  		//var sammeln = "";
  		for(i=0;i<csvElement.length;i++)
  		{
  			//sammeln
  			if(csvElement[i].getAttributeNode("id").nodeValue == (obj + ":csv"))
  			{
  			csvElement = csvElement[i];
  			break;
  			}
  		}
  		
  		
  		var newLIText = document.createTextNode(usedCircle.getAttributeNode("name").nodeValue);
  		document.getElementById("textfield").appendChild(newLI);
  		newLI.appendChild(newLIText);
  		
  		this.CSV(csvElement.firstChild.nodeValue );

 		
 		}
 		else
 		{
 		var Knoten = null;
 		
 		//document.getElementsByTagName("ol")[0].firstChild;

 		//document.getElementById("textfield").removeChild(Knoten)
 		this.controlElement.setAttributeNS(null, \'y\', 0);
  		this.controlElement.setAttributeNS(null, \'width\', 5.0);
  		
 		document.getElementById(vis).style.visibility = "hidden";
 		}
 		
 		
;
 	}
 };
 
 de.auster_gmbh.xmleditor.service = de.auster_gmbh.xmleditor.Class;
 

 
 
   
   ]]></script>
  <defs
     id="defs4">
    <inkscape:perspective
       sodipodi:type="inkscape:persp3d"
       inkscape:vp_x="0 : 526.18109 : 1"
       inkscape:vp_y="0 : 1000 : 0"
       inkscape:vp_z="744.09448 : 526.18109 : 1"
       inkscape:persp3d-origin="372.04724 : 350.78739 : 1"
       id="perspective10" />
  </defs>
  <sodipodi:namedview
     id="base"
     pagecolor="#ffffff"
     bordercolor="#666666"
     borderopacity="1.0"
     inkscape:pageopacity="0.0"
     inkscape:pageshadow="2"
     inkscape:zoom="0.35"
     inkscape:cx="350"
     inkscape:cy="520"
     inkscape:document-units="px"
     inkscape:current-layer="layer1"
     showgrid="false"
     inkscape:window-width="640"
     inkscape:window-height="671"
     inkscape:window-x="5"
     inkscape:window-y="22" />
  <metadata
     id="metadata7">
    <rdf:RDF>
      <cc:Work
         rdf:about="">
        <dc:format>image/svg+xml</dc:format>
        <dc:type
           rdf:resource="http://purl.org/dc/dcmitype/StillImage" />
      </cc:Work>
';
$head2_res = '    </rdf:RDF>      
  </metadata>

  <circle cx ="20" cy ="170.0" r ="20" style="fill:#ffff00;" />
  ';

if($printall)
{
$start = 0;
$stop = $this->base_object->doc_many();
//echo $this->base_object->doc_many();
}
else
{
$start = $this->base_object->cur_idx();
$stop = $this->base_object->cur_idx() + 1;
}
//echo $this->base_object->doc_many();

for($ic = $start;($ic < $stop) ; $ic++)
{
$end = false;
	$this->base_object->change_idx($ic);

	                        $deep[$this->base_object->idx]=0;
                                $i = 30000;
                                $end = false;

                                $reset=true;

/* */
                              $this->base_object->set_first_node();

                              //fputs ($fp, "\n<ul>\n");

$modus = array();
$modus['mod'] = 0;
$modus['xhtml'] = false;
$modus['mathml'] = false;
$this->cur_id = 0;
 while(!$end){ /* start block 1 */
/* ---------------------------------------------------------------------------
/*                         Loop for the complet tree
/*
/* ---------------------------------------------------------------------------
*/ 
 
		
 //schaltet den pointer zurück, wenn ein neuer knoten betreten wird
 if($reset)$this->base_object->reset_pointer();

/* ---------------------------------------------------------------------------
*  looks the new elementtype up and saves an indicating number to "$new"
*  ...........................................................................
*  1: http://www.w3.org/1999/xhtml#*
*  2: http://www.w3.org/1998/Math/MathML#*
*  0: http://www.w3.org/1999/02/22-rdf-syntax-ns#*
*  0: http://www.w3.org/2000/01/rdf-schema#*
*  ---------------------------------------------------------------------------
*/
if( $this->base_object->show_xmlelement()->is_Node('http://www.w3.org/1999/xhtml#*')){ $new = 1;}
elseif( $this->base_object->show_xmlelement()->is_Node('http://www.w3.org/1998/Math/MathML#*')) $new = 2;
elseif( $this->base_object->show_xmlelement()->is_Node('http://www.w3.org/1999/02/22-rdf-syntax-ns#*')) $new = 0;
elseif( $this->base_object->show_xmlelement()->is_Node('http://www.w3.org/2000/01/rdf-schema#*')) $new = 0;
else $new = $modus['mod'];

$modus['cur'] = $new; 

/* ---------------------------------------------------------------------------
*  
*
*  ---------------------------------------------------------------------------
*/
  //testet, ob es weitere knoten gibt
  if($this->base_object->index_child()>0){ /* starts block 2 */

   	//schreibt die eingabe
   	if(-1 == $this->base_object->show_pointer()){ /* starts block 3 */

		$modus['nodetype'] = 1; 

		$this->concatProcessState1($res, $modus, $dblx, $dbly);

		$this->idx_on_table++;
		$this->dbltable[$this->idx_on_table][0] = $dblx;
		$this->dbltable[$this->idx_on_table][1] = ($dbly + 20.0);

    		$reset = true;
		$dblx += 250.0;

     		if(!$this->base_object->child_node(0)) 
      		{
			if(is_null($this->base_object->show_xmlelement()))$this->base_object->parent_node();
			$this->base_object->show_xmlelement()->giveOutOverview();
			throw new ErrorException('Consistence-Error in ' 
			. $this->base_object->cur_node() . ' on position-stamp ' 
			. $this->base_object->position_stamp() . ' current historypointer is on "'
			. $this->base_object->show_pointer() . '" and '
			. $this->base_object->index_child() .  ' child(s) ' . "\n", 0,1,'XML_handle.php',1);
      		}

                                                                       
	$deep[$this->base_object->idx]++;
	/* ends block 3 */ }
	elseif((($this->base_object->index_child()-1) > $this->base_object->show_pointer()) )
	{/* starts block 3 */
      
	$modus['nodetype'] = 2; 
 
	$this->concatProcessState2($res, $modus, $dblx, $dbly);
	$dbly += 50.0; 
	$reset = true;
	$check = $this->base_object->child_node($this->base_object->show_pointer() + 1);
	$deep[$this->base_object->idx]++;
                                                                                
       if(!$check)echo 'geht nicht weiter';
	/* ends block 3 */ }
	else{ /* starts block 3 */

	$modus['nodetype'] = 3; 

	$this->concatProcessState3($res, $modus, $dblx, $dbly);
	$dblx -= 250.0;
	$dbly += 250.0;
	$this->idx_on_table--;
	$end = !$this->base_object->parent_node();

	$deep[$this->base_object->idx]--;
	$reset = false;
        }
	/* ends block 2 */ 
	}else
	{

		if(-1 == $this->base_object->show_pointer())
		{
			$modus['nodetype'] = 4; 
			$this->concatProcessState4($res, $modus, $dblx, $dbly);        
			$this->idx_on_table++;
			$this->dbltable[$this->idx_on_table][0] = $dblx;
			$this->dbltable[$this->idx_on_table][1] = ($dbly + 20.0);
			$dblx += 250.0;
		}

		$dblx -= 250.0;
		$dbly += 250.0;
		$this->idx_on_table--;
		$end = !$this->base_object->parent_node();
		$deep[$this->base_object->idx]--;
		$reset = false;
	}

	$modus['mod'] = $new;
	$this->cur_id++;
}//END WHILE


                         
$res .= "\n";
$dbly -= 250.0;
$this->idx_on_table = 0;
}

$foot_res .=   "  <g id=\"relations\"> \n";

for($k = 0; $k < count($this->pos_request);$k++)
{
	$request = $this->pos_request[$k]['request'];
	$from_x = $this->pos_request[$k]['x'];
	$from_y = $this->pos_request[$k]['y'];
	$to_x = $this->idxtable_nodePos[$request]['x'];
	$to_y =  $this->idxtable_nodePos[$request]['y'];

	$logger_class->setAssert('          Draw REF-line:' . $request . ' x1:' . $from_x . ' y1:' . $from_y . ' x2:' . $to_x . ' y2:' . $to_y  ,10);

 	 $foot_res .=  '
 	 
  	     <line x1 ="' . $from_x . '" y1 ="' . $from_y . '" x2 ="' . $to_x . '" y2 ="' . $to_y . '" style="stroke:#00ff00;stroke-width:4px;visibility:hidden;" id="Relation:' . $k . '" />
  	     <circle cx ="' . $from_x . '" cy ="' . $from_y . '" r ="5" style="fill:#00EE00;" onclick="de.auster_gmbh.xmleditor.service.drawLine(\'' . $request . '\',\'Relation:' . $k . '\',\'Relation\');" />
  	     <circle cx ="' . $to_x . '" cy ="' . $to_y . '" r ="5" style="fill:#0000EE;" onclick="de.auster_gmbh.xmleditor.service.drawLine(\'' . $request . '\',\'Relation:' . $k . '\',\'Relation\');" />
  	 ';
}
$foot_res .=   "  </g>";


$foot_res .=   "  <g id=\"inherited\"> \n";

for($k = 0; $k < count($this->pos_inherit);$k++)
{
	$request = $this->pos_inherit[$k]['request'];
	$from_x = $this->pos_inherit[$k]['x'] + 20;
	$from_y = $this->pos_inherit[$k]['y'] + 20;
	$to_x = $this->idxtable_nodePos[$request]['x'] - 20;
	$to_y =  $this->idxtable_nodePos[$request]['y'] + 20;

	if($to_x) 	 
	$foot_res .=  '
 	 
 	     <!-- Vererbung -->
  	     <line x1 ="' . $from_x . '" y1 ="' . $from_y . '" x2 ="' . $to_x . '" y2 ="' . $to_y . '" style="stroke:#0000ff;stroke-width:3px;visibility:hidden;" id="Inherit:' . $k . '" />
  	     <circle cx ="' . $from_x . '" cy ="' . $from_y . '" r ="5" style="fill:#EE00EE;" onclick="de.auster_gmbh.xmleditor.service.drawLine(\'' . $request . '\',\'Inherit:' . $k . '\',\'inherit\');" />
  	     <circle cx ="' . $to_x . '" cy ="' . $to_y . '" r ="5" style="fill:#EE0000;" onclick="de.auster_gmbh.xmleditor.service.drawLine(\'' . $request . '\',\'Inherit:' . $k . '\',\'inherit\');" />
  	 ';
}
$foot_res .=   "  </g>";


$foot_res .=  '  <g id="menu">

	<!--<rect
       style="opacity:0.8;fill:#0000be;fill-opacity:1;fill-rule:evenodd;stroke:#000000;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1"
       id="rect2454"
       width="408.57144"
       height="445.71429"
       x="0.0"
       y="380.93362" onload=" de.auster_gmbh.xmleditor.service.setControlElement(this);" 
       onclick="de.auster_gmbh.xmleditor.service.menueSwitch();" /> -->
	<switch>
        <foreignObject x="100" y="200" width="600" height="440" onclick="de.auster_gmbh.xmleditor.service.menueSwitch();" >
            <body xmlns="http://www.w3.org/1999/xhtml" id="textfield" />
        </foreignObject>
    </switch>


  </g></svg>';



    

return $head_res . $head2_res . $res . $foot_res;
}


/**
*-----------------------------------------------------
* @funciton concatProcessState1
*-----------------------------------------------------
* has textnodes in node but visits the node first time 
*-----------------------------------------------------
*@param String res : contains the String for return
*@param Array modus : { 
*	'mod' => (0|1|2) "contains value of last circuit"} ,
*	'cur' => (0|1|2) "contains value of current circuit"} , 
*	'xhtml' => (false|[Integer] "contains status of the opened subtree in xhtml" , 
*	'mathml' => (false|[Integer] "contains status of the opened subtree in mathml" }
*@param Double dblx : X-Positon for Nodes
*@param Double dbly : Y-Positon for Nodes
*
*/
private function concatProcessState1(&$res, &$modus,&$dblx,&$dbly)
{
                $res .= $this->extractNODEPartSVG( $this->base_object , $dblx , $dbly , 
                 $this->extractATTRIBPartSVG($this->base_object , $dblx , $dbly) ,
                 $this->extractCDATAPartSVG($this->base_object , $dblx , $dbly),
                 $modus );
}

private function concatProcessState2(&$res, &$modus,&$dblx,&$dbly)
{

}

private function concatProcessState3(&$res, &$modus,&$dblx,&$dbly)
{

}

private function concatProcessState4(&$res, &$modus,&$dblx,&$dbly)
{
                $res .= $this->extractNODEPartSVG( $this->base_object , $dblx , $dbly , 
                 $this->extractATTRIBPartSVG($this->base_object , $dblx , $dbly) ,
                 $this->extractCDATAPartSVG($this->base_object , $dblx , $dbly),
                 $modus );
}



private function extractCDATAPartSVG( &$dom , $dblx , $dbly )
{
$res = '';

if( ($many = $dom->many_cur_data(false)) > 0) //$dom->many_cur_data
  {
  	
  	
  	  
  	for($i = 1 ;$i <= $many;$i++)
  	{
  	 
  	$data_text = trim($dom->show_cur_data($i - 1));
  	
  	if(strlen($data_text) > 20) $data_text = "long";
  		
  	 $res .=  '
  	 <!-- Baumstruktur -->
  	     <line x1 ="' . ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '" y1 ="' . ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '" x2 ="' . $dblx . '" y2 ="' . ($dbly + 20.0) . '" style="stroke:#330011;stroke-width:5px;" />
  	 ';
  	 
  	 
  	    $res .=  '    <circle cx ="' . 
  	    ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '" cy ="' . 
  	    ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '" r ="20" id="CDATA" style="fill:#00ff00;" onclick="de.auster_gmbh.xmleditor.service.onDoEvent(this,\'cdata\');" />';
  	    
  	    $res .=  '    <text
       xml:space="preserve"
       style="font-size:40px;font-style:normal;font-weight:normal;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;font-family:Bitstream Vera Sans"
        x="' . ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '"
         y="' . ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '"
       id="text2387"><tspan
         sodipodi:role="line"
         id="tspan2389"
         x="' . ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '"
         y="' . ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '"
         style="font-size:12px"><![CDATA[' . $data_text . ']]></tspan></text>'; //$dom->show_cur_data($i - 1)
  	 
  	}
  }
  
  return $res;
}
	
private function extractATTRIBPartSVG( &$dom , $dblx , $dbly )
{
$res = '';

//$pos = $dom->position_stamp();
//$this->idxtable_nodePos[$pos]['x'] = ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 50.0));
//$this->idxtable_nodePos[$pos]['y'] = ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 50.0));
                                                                 
  if( ($many = $dom->get_attribute_many()) > 0)
  {
 
 	$asso_array = $dom->show_ns_attrib();
 	$num_array = array();
 	$z = 1;
	foreach ($asso_array as $key => $value) 
	{
	
    	$num_array[$z]['key'] = $key;
    	$num_array[$z++]['value'] = $value;
    	
	}
 
 


 
  	for($i = 1 ;$i <= $many;$i++)
  	{
  	$dom->goto_Attribute($key);
  	 
  	 $pos = $dom->position_stamp();
		$this->idxtable_nodePos[$pos]['x'] = ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 50.0));
		$this->idxtable_nodePos[$pos]['y'] = ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 50.0));
//$dom->get_Class_stamp();

 		$my_array = $dom->get_ListenerList();

		for($j = 0;$j < count($my_array);$j++)
		{
			
			$increment = count($this->pos_request);
			$this->pos_request[$increment]['request'] = $my_array[$j];
			$this->pos_request[$increment]['x'] = ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 50.0));
			$this->pos_request[$increment]['y'] = ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 50.0));
		}

  	$dom->parent_node();
  	 $res .=  '
  	     <!-- Attribute linie -->
  	     <line x1 ="' . ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 50.0)) . '" y1 ="' . ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 50.0)) . '" x2 ="' . $dblx . '" y2 ="' . ($dbly + 20.0) . '" style="stroke:#330011;stroke-width:5px;" />
  	 ';
  	 
  	 
  	    $res .=  '    <circle cx ="' . 
  	    ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 50.0)) . '" cy ="' . 
  	    ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 50.0)) . '" r ="20" style="fill:#0000ff;" onclick="de.auster_gmbh.xmleditor.service.onDoEvent(this,\'attrib\');" />';
  	    
            $res .=  '    <text
       xml:space="preserve"
       style="font-size:40px;font-style:normal;font-weight:normal;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;font-family:Bitstream Vera Sans"
        x="' . ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 50.0)) . '"
         y="' . ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 50.0)) . '"
       id="text2387"><tspan
         sodipodi:role="line"
         id="tspan2389"
         x="' . ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 50.0)) . '"
         y="' . ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 50.0)) . '"
         style="font-size:5px">' . $num_array[$i]['key'] . '</tspan>
         <tspan sodipodi:role="line"
         id="tspan2390"
         x="' . ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 50.0)) . '"
         y="' . (($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 50.0)) + 10) . '"
         style="font-size:5px">' . '' . '</tspan></text>'; //$num_array[$i]['value']
  	    
  	}
  }

	return $res;
}
	
	
	
private function extractNODEPartSVG( &$dom , $dblx , $dbly, $attrib, $cdata, &$mode )
{

$pos = $dom->position_stamp();
$color = 'ff0000';
if(get_class($dom->show_xmlelement()) == 'PEDL_Object_Constructor')$color = 'aa22aa';
if(get_class($dom->show_xmlelement()) == 'PEDL_Object_Class')$color = '000000';
if(get_class($dom->show_xmlelement()) == 'PEDL_Object_Funktion')$color = 'ff2222';
if(get_class($dom->show_xmlelement()) == 'PEDL_Object_Parameter')$color = 'aaaa00';

$this->idxtable_nodePos[$pos]['x'] = $dblx;
$this->idxtable_nodePos[$pos]['y'] = $dbly;
/*
$modus['mod'] = 0;
$modus['xhtml'] = false;
$modus['mathml'] = false;

<switch>
    <foreignObject  x="300" y="300" width="800" height="800">
      <xhtml:p >hay</xhtml:p>
    </foreignObject>
 </switch>

*/

  	 $pos = $dom->position_stamp();
  	 	$this->idxtable_nodePos[$pos]['id'] = $stamp;
		$this->idxtable_nodePos[$pos]['x'] = $dblx;
		$this->idxtable_nodePos[$pos]['y'] = $dbly;

//---------------------------------------------------------

 		$my_array = $dom->get_ListenerList();

		for($j = 0;$j < count($my_array);$j++)
		{
		
			$increment = count($this->pos_request);
			$this->pos_request[$increment]['request'] = $my_array[$j];
			$this->pos_request[$increment]['x'] = $dblx;
			$this->pos_request[$increment]['y'] = $dbly;
		
		}
		
		
		if(!(false===($stamp = $dom->get_Classstamp())))
		{
		$increment = count($this->pos_inherit);
		$this->pos_inherit[$increment]['request'] = $stamp;
		$this->pos_inherit[$increment]['x'] = $dblx;
		$this->pos_inherit[$increment]['y'] = $dbly;
		}

//---------------------------------------------------------
$res = $cdata;
$res .= $attrib;

    $res .=  '    <line x1 ="' . $this->dbltable[ $this->idx_on_table][0] . '" y1 ="' . $this->dbltable[ $this->idx_on_table][1] . '" x2 ="' . $dblx . '" y2 ="' . ($dbly + 20.0) . '" style="stroke:#330011;stroke-width:2px;" />';
    

    
    $res .=  '    <circle cx ="' . $dblx . '" cy ="' . ($dbly + 20.0) . '" r ="20" style="fill:#' . $color . ';" onclick="de.auster_gmbh.xmleditor.service.onDoEvent(this,\'node\');" id="' . $dom->position_stamp() . '" name="' . get_class($dom->show_xmlelement()) . ':' . $dom->cur_node() . '" content="test" />';

    $res .=  '<dataAboutNode id="' . $dom->position_stamp() . ":csv\" >TypeofLink;Linkname\nTypeofLink;Linkname</dataAboutNode>";

    $res .=  '    <text
       xml:space="preserve"
       style="font-size:40px;font-style:normal;font-weight:normal;fill:#0000FF;fill-opacity:1;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;font-family:Bitstream Vera Sans"
       x="50.260422"
       y="69.99939"
       id="text2387"><tspan
         sodipodi:role="line"
         id="tspan2389"
         x="' . ($dblx + 10) . '"
         y="' . $dbly . '"
         style="font-size:12px">' .  $dom->cur_node() . '</tspan><tspan style="font-size:8px">(' . get_class($dom->show_xmlelement()) . ')</tspan></text>';
         
         return $res;
}
	
	
function send_header()
{/*
	                        if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ||
					stristr($_SERVER["HTTP_USER_AGENT"],"W3C_Validator")) {
					header("Content-type: application/xhtml+xml");
				} else {
					header("Content-type: text/html");
				}
*/	
}
		
}

?>
