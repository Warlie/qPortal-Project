<?php

/**  Aufstellung der functionen des XML objektes
*   
*/

class SVG_Overview_handle extends Interface_handle 
{

	
	function parse_document($source)
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
	
	function save_back($format)
	{
		
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

	

      $nl = chr(13) . chr(10);
      

      if($this->base_object->DOC[$this->base_object->idx] <> '')$res .= $this->base_object->DOC[$this->idx];


$dblx =  150.0;
$dbly =  150.0;

$dbltable = array();
$idx_on_table = 0;
$dbltable[0][0] = 20.0;
$dbltable[0][1] = 170.0;

/**
*
*
*/

$res =  '<?xml version="1.0"?>
<!DOCTYPE svg PUBLIC
    "-//W3C//DTD XHTML 1.1 plus MathML 2.0 plus SVG 1.1//EN"
    "http://www.w3.org/2002/04/xhtml-math-svg/xhtml-math-svg.dtd"[
<!ENTITY % SVG.prefixed "IGNORE" >
<!ENTITY % XHTML.prefixed "INCLUDE" >
<!ENTITY % XHTML.prefix "xhtml" >
<!ENTITY % MATHML.prefixed "INCLUDE" >
<!ENTITY % MATHML.prefix "math" >
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
   height="15297mm"
   id="svg2"
   sodipodi:version="0.32"
   inkscape:version="0.46"
   sodipodi:docname="hilfe.svg"
   inkscape:output_extension="org.inkscape.output.svg.inkscape">
   <script language="JavaScript" type="text/javascript"> 
 
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
 	
 	onDoEvent: function(obj,typ)
 	{
 	//alert(this.controlElement);
 	this.controlElement.setAttributeNS(null, \'y\', 0.0);
  	this.controlElement.setAttributeNS(null, \'width\', 100.0);
  	parent = this.controlElement.parentNode;
        var g = null;
        var text = null;
        g = document.createElementNS("http://www.w3.org/2000/svg", "g");
        parent.appendChild(g);
        
        text = document.createElementNS("http://www.w3.org/2000/svg", "text");
        var textNode = document.createTextNode("foobar");
        //text.setAttributeNS(null, "style", "font-size:40px;font-style:normal;font-weight:normal;fill:#000000;fill-opacity:1;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;font-family:Bitstream Vera Sans" );
        //text.setAttributeNS(null, "x", 80);
        //text.setAttributeNS(null, "y", 50);
	//var tar = text.target;
	//var subtext = document.createElementNS("http://www.w3.org/2000/svg", "tspan");
	//text.insertData(0, " Fragt der Barkeeper:");

        //parent.appendChild(text);

 	}
 };
 
 de.auster_gmbh.xmleditor.service = de.auster_gmbh.xmleditor.Class;
 

 
 
   
   </script>
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
    </rdf:RDF>
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

 while(!$end){
/* ---------------------------------------------------------------------------
/*                         Loop for the complet tree
/*
/* ---------------------------------------------------------------------------
*/ 
 
 //schaltet den pointer zurück, wenn ein neuer knoten betreten wird
 if($reset)$this->base_object->reset_pointer();

/* ---------------------------------------------------------------------------
*  
*
*  ---------------------------------------------------------------------------
*/
if( $this->base_object->show_xmlelement()->is_Node('http://www.w3.org/1999/xhtml#*'))
{ $new = 1;
}
elseif( $this->base_object->show_xmlelement()->is_Node('http://www.w3.org/1998/Math/MathML#*')) $new = 2;
elseif( $this->base_object->show_xmlelement()->is_Node('http://www.w3.org/1999/02/22-rdf-syntax-ns#*')) $new = 0;
elseif( $this->base_object->show_xmlelement()->is_Node('http://www.w3.org/2000/01/rdf-schema#*')) $new = 0;
else
$new = $modus['mod'];

$modus['cur'] = $new; 

/* ---------------------------------------------------------------------------
*  
*
*  ---------------------------------------------------------------------------
*/

//echo $new . ' - ' . $modus['mod'] . '<br>';
  //testet, ob es weitere knoten gibt
  if($this->base_object->index_child()>0){

   //schreibt die eingabe
   if(-1 == $this->base_object->show_pointer()){
//echo $this->base_object->cur_node() . "\n";
$modus['nodetype'] = 1; 
//echo " rein1 ";
/* ---------------------------------------------------------------------------
*  
*
*  ---------------------------------------------------------------------------
*/


/*







                         //every element of svg will start and end with a g-Tag
                          if($new == 0) $res .=  '
                          <!-- intag 1 -->
                          <g>';
                          
                          if($new <> $modus['mod'])
				{
				
				
				//echo 'booh';
				if($new == 1){ $res .=  '
 <switch>
  <foreignObject  x="300" y="300" width="800" height="800">
<!-- start foreignObject -->
';
//echo 'start';
}
				
				}
  

  
    //$res .= $this->extractCDATAPart($this->base_object , $dblx , $dbly);
  
  

    //$res .= $this->extractATTRIBPart($this->base_object , $dblx , $dbly);


//. $this->base_object->all_attrib_axo($format)



//$dbltable[0][0] = 20.0;
//$dbltable[0][1] = 170.0;

         
         
         
                 $res .= $this->extractNODEPart( $this->base_object , $dblx , $dbly, $dbltable , $idx_on_table , 
                 $this->extractATTRIBPart($this->base_object , $dblx , $dbly) ,
                 $this->extractCDATAPart($this->base_object , $dblx , $dbly),
                 $modus );
        
                 if($new == 0) $res .=  '
                 </g>
                 <!-- end of g in out1 -->';
                 
                          if($new <> $modus['mod'])
				{

				//echo 'booh';
				if($new <> 1){ $res .=  '
  </foreignObject>
 </switch>
<!--  end foreignObject -->';
//echo 'end1';
}
				
				}
        
        
//    $res .=  $this->base_object->setcdata_tag($this->base_object->convert_to_XML($this->base_object->show_cur_data(0),$format),$this->base_object->show_curtag_cdata());


*/


    $idx_on_table++;
$dbltable[$idx_on_table][0] = $dblx;
$dbltable[$idx_on_table][1] = ($dbly + 20.0);

    $reset = true;
$dblx += 250.0;
//$dbly += 50.0;
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
      }elseif((($this->base_object->index_child()-1) > $this->base_object->show_pointer()) ){
      //echo " rein2 ";
      $modus['nodetype'] = 2; 
      
      /*
                 $res .= $this->extractNODEPart( $this->base_object , $dblx , $dbly, $dbltable , $idx_on_table , 
                 $this->extractATTRIBPart($this->base_object , $dblx , $dbly) ,
                 $this->extractCDATAPart($this->base_object , $dblx , $dbly),
                 $modus );
      */
                          if($new <> $modus['mod'])
				{

				//echo 'booh';
				if($new <> 1){ $res .=  '
  </foreignObject>
 </switch>
<!--  end foreignObject -->';
//echo 'end2';
}
 
 //    </foreignObject>
// </switch>
//
				
				}

                       $dbly += 50.0;                                                
       //$res .=  $this->base_object->setcdata_tag($this->base_object->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1,$format) ,$format),$this->base_object->show_curtag_cdata()); 
       $reset = true;
       $check = $this->base_object->child_node($this->base_object->show_pointer() + 1);
       $deep[$this->base_object->idx]++;
                                                                                
       if(!$check){
        echo 'geht nicht weiter';
                          

                                                                                }

       }else{
//echo " rein3 ";
$modus['nodetype'] = 3; 


                $res .= $this->extractNODEPart( $this->base_object , $dblx , $dbly, $dbltable , $idx_on_table , 
                 $this->extractATTRIBPart($this->base_object , $dblx , $dbly) ,
                 $this->extractCDATAPart($this->base_object , $dblx , $dbly),
                 $modus );
                 
                 //if($new == 0) $res .=  '</g>
                 //<!-- end of g in out2 -->';
                 
                          if($new <> $modus['mod'])
				{
				
				//echo 'booh';
				if($new <> 1){ $res .=  '
		</foreignObject>
  </switch>
<!--  end foreignObject -->';
//echo 'end3';
}
				
				}

       // $res .=  $this->base_object->setcdata_tag($this->base_object->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$format),$this->base_object->show_curtag_cdata());

        //$res .=  '</' .  $this->base_object->cur_node() . '>';

 

        $dblx -= 250.0;
	$dbly += 250.0;
	$idx_on_table--;
        $end = !$this->base_object->parent_node();

                                                                        $deep[$this->base_object->idx]--;
                                                                        $reset = false;
                                                                        }
                                        }else{

                                                                        if(-1 == $this->base_object->show_pointer()){
                                                                        //echo " rein4 ";
                                                                        $modus['nodetype'] = 4; 
                          $float_control = ( '' <>( $this->base_object->show_cur_data($this->base_object->show_pointer()+1)) );                                               
                          //every element of svg will start and end with a g-Tag
                          if($new == 0) $res .=  '<!-- intag 1 -->
                          <g>
                          ';
                          
                          if($new <> $modus['mod'])
				{

				
				if($new == 1)
				{ $res .=  '
 <switch>
  <foreignObject  x="300" y="300" width="800" height="800">
<!-- start foreignObject -->
';
//echo 'start';
}

				//<switch>
//    <foreignObject  x="300" y="300" width="800" height="800">
				


				}



  
            
 
                 $res .= $this->extractNODEPart( $this->base_object , $dblx , $dbly, $dbltable , $idx_on_table , 
                 $this->extractATTRIBPart($this->base_object , $dblx , $dbly) ,
                 $this->extractCDATAPart($this->base_object , $dblx , $dbly),
                 $modus );
                 
                 if($new == 0) $res .=  '
                 </g>
                 <!-- end of g in out2 -->';
                 
                          if($new <> $modus['mod'])
				{
				
				//echo 'booh';
				if($new <> 1){ $res .=  '
			</foreignObject>
  </switch>
<!--  end foreignObject -->';
 //echo 'end';
 }
 //    </foreignObject>
// </switch>
//
				
				}

                 
                 
                 
        $idx_on_table++;
	$dbltable[$idx_on_table][0] = $dblx;
	$dbltable[$idx_on_table][1] = ($dbly + 20.0);
                 
                                                                        $dblx += 250.0;
           //$res .=  '<' .  $this->base_object->cur_node() . $this->base_object->all_attrib_axo($format) ;
                                                                       }
                                                                                if( '' <>( $this->base_object->show_cur_data($this->base_object->show_pointer()+1)) )
                                                                                {
                                                                               // $res .=  '>' . $this->base_object->setcdata_tag($this->base_object->convert_to_XML($this->base_object->show_cur_data($this->base_object->show_pointer()+1) ,$format),$this->base_object->show_curtag_cdata());
                                                                                //$res .=  '</' .  $this->base_object->cur_node() . ' >';   // str_repeat (" ", 2*$deep[$this->idx])
                                                                                $dblx -= 250.0;
                                                                                $dbly += 250.0;
                                                                                $idx_on_table--;
                                                                                }
                                                                                else
                                                                                {
                                                                                $dblx -= 250.0;
                                                                                $dbly += 250.0;
                                                                                    $idx_on_table--;
                                                                                //$res .=  '/>';
                                                                                }
                                                                        $end = !$this->base_object->parent_node();
                                                                        $deep[$this->base_object->idx]--;
                                                                        $reset = false;
                                                                        //echo 'hallo';

                                        }
$modus['mod'] = $new;
                                }
                                
                                $res .= "\n";
 $dbly -= 250.0;
 $idx_on_table = 0;
}
 $res .=  '  <g id="menu">

	<rect
       style="opacity:0.47297297;fill:#0000be;fill-opacity:1;fill-rule:evenodd;stroke:#000000;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1"
       id="rect2454"
       width="408.57144"
       height="445.71429"
       x="0.0"
       y="380.93362" onload=" de.auster_gmbh.xmleditor.service.setControlElement(this);" />

  </g></svg>';



				return $res;
		
	}
	
private function extractCDATAPart( &$dom , $dblx , $dbly )
{
$res = '';

if( ($many = $dom->many_cur_data()) > 0)
  {
 
  	for($i = 1 ;$i <= $many;$i++)
  	{
  	 
  	 $res .=  '
  	     <line x1 ="' . ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '" y1 ="' . ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '" x2 ="' . $dblx . '" y2 ="' . ($dbly + 20.0) . '" style="stroke:#330011;stroke-width:5px;" />
  	 ';
  	 
  	 
  	    $res .=  '    <circle cx ="' . 
  	    ($dblx + (sin(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '" cy ="' . 
  	    ($dbly + 20 + (cos(( (2 * $i)/ $many ) * M_PI)  * 100.0)) . '" r ="20" style="fill:#00ff00;" onclick="de.auster_gmbh.xmleditor.service.onDoEvent(this,\'cdata\');" />';
  	    
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
         style="font-size:12px"><![CDATA[' . $dom->show_cur_data($i - 1) . ']]></tspan></text>';
  	 
  	}
  }
  
  return $res;
}
	
private function extractATTRIBPart( &$dom , $dblx , $dbly )
{
$res = '';

                                                                 
  if( ($many = $dom->get_attribute_many()) > 0)
  {
 
 	$asso_array = $dom->show_cur_attrib();
 	$num_array = array();
 	$z = 1;
	foreach ($asso_array as $key => $value) 
	{
    	$num_array[$z]['key'] = $key;
    	$num_array[$z++]['value'] = $value;
	}
 
 
  	for($i = 1 ;$i <= $many;$i++)
  	{
  	 
  	 $res .=  '
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
         style="font-size:5px">' . $num_array[$i]['value'] . '</tspan></text>';
  	    
  	}
  }

	return $res;
}
	
	
	
private function extractNODEPart( &$dom , $dblx , $dbly, $dbltable , $idx_on_table, $attrib, $cdata, &$mode )
{
if($mode['nodetype'] == 2 && $mode['cur'] == 0)return '';
if($mode['nodetype'] == 3 && $mode['cur'] == 0)return '';

if( $dom->show_xmlelement()->is_Node('http://www.w3.org/1999/xhtml#*'))
{ $new = 1;
}
elseif( $dom->show_xmlelement()->is_Node('http://www.w3.org/1998/Math/MathML#*')) $new = 2;
elseif( $dom->show_xmlelement()->is_Node('http://www.w3.org/1999/02/22-rdf-syntax-ns#*')) $new = 0;
elseif( $dom->show_xmlelement()->is_Node('http://www.w3.org/2000/01/rdf-schema#*')) $new = 0;
else
$new = $mode;

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

$res = $cdata;
$res .= $attrib;

    $res .=  '    <line x1 ="' . $dbltable[ $idx_on_table][0] . '" y1 ="' . $dbltable[ $idx_on_table][1] . '" x2 ="' . $dblx . '" y2 ="' . ($dbly + 20.0) . '" style="stroke:#330011;stroke-width:1px;" />';
    

    
    $res .=  '    <circle cx ="' . $dblx . '" cy ="' . ($dbly + 20.0) . '" r ="20" style="fill:#ff0000;" onclick="de.auster_gmbh.xmleditor.service.onDoEvent(this,\'node\');" />';

    $res .=  '    <text
       xml:space="preserve"
       style="font-size:40px;font-style:normal;font-weight:normal;fill:#0000FF;fill-opacity:1;stroke:none;stroke-width:1px;stroke-linecap:butt;stroke-linejoin:miter;stroke-opacity:1;font-family:Bitstream Vera Sans"
       x="50.260422"
       y="69.99939"
       id="text2387"><tspan
         sodipodi:role="line"
         id="tspan2389"
         x="' . $dblx . '"
         y="' . $dbly . '"
         style="font-size:12px">' .  $dom->cur_node() . '</tspan></text>';
         
         return $res;
}
	
	
function send_header()
{
	                        if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ||
					stristr($_SERVER["HTTP_USER_AGENT"],"W3C_Validator")) {
					header("Content-type: application/xhtml+xml");
				} else {
					header("Content-type: text/html");
				}
}
		
}

?>
