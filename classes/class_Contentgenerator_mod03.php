<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
* (C) Stefan wegerhoff
*/
require("class_Contentgenerator_mod02.php");



class ContentGeneratorMod03 extends ContentGeneratorMod02
{
	
	function generate()
	{
		
		$this->heap['script'] = array();
	return parent::generate();
	}


	//erstellt functionen und variablen
	function check(&$cur,&$name,$add,$look){

	//stellt die liste zur�ck
        reset($add);
	//$add mu� ein zweidimensionaler assoziativer array sein
        while ($key = key($add[$look]) ) {
        
		//doppelte namen werden nicht ausgef�hrt
        if(is_null($name[$look][$key]))
                {
                $name[$look][$key]="!";
                $cur[$look] .= "\n" . $add[$look][$key] . "\n";

                }
                else{
                //echo "redundanter functionsname gefunden: $key " . $add[$look][$key] . "!<p>";
                }
        
		next($add[$look]);
        	}
	
	}
	
	function getoutput($set_header, $type = "",$special = "")
	{

		$this->XMLlist->change_URI($this->template);
		
		if(count($this->heap['script']) > 0)
		if($this->XMLlist->seek_node('head'))
		{		//'<script language="JavaScript" type="text/javascript">'
			
			
				$this->XMLlist->create_node($this->XMLlist->position_stamp());
				$this->XMLlist->set_node_name('script');
				$this->XMLlist->set_node_attrib('language','JavaScript');
				$this->XMLlist->set_node_attrib('type','text/javascript');
				$this->XMLlist->set_node_cdata('hallo',0);
				$this->XMLlist->curtag_cdata(true);
				
		}

		return parent::getoutput($set_header ,$type,$special);
	}
	
}
?>
