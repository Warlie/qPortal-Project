<?PHP

/**
*ContentGenerator
*
* Generates content by reading XML and DB-entries
*
*
*/
require_once("plugin_interface.php");

class Tree_Build extends plugin 
{
var $reset_name = "";
var $producer = '';
var $xpath = '';
var $toUse = '';
var $doctype = 'xml';
var $scanner;
var $result_table = array();
var $pos_res = 0;
var $filehandle;
var $save_file;
var $handle;


var $alias = array('tag'=>'tag','pos'=>'pos','file'=>'file','number'=>'number');


	function __construct(/* System.Parser */ &$back, /* System.CurRef */ &$treepos, /* System.Content */ &$content)
	{
		$this->back= &$back;
		$this->treepos = &$value;
		$this->content = &$content;
		$name = 'http://www.trscript.de/tree#template';
		$array = array();
		$pos = $this->back->position_stamp();
		
			if(!$this->back->change_URI('template/finance/details_upload.xml')) echo "not found";
		$pos2 = $this->back->position_stamp();
			echo $pos . " " .  $this->back->position_stamp();
			$this->back->flash_result();
		if($this->back->seek_node($name))
		{
			//$this->get_parser()->show_xmlelement()->event_message_in($type,$obj);
			//http://www.trscript.de/tree#add case_folding="0" doctype="XML" id="view" >template/correspondence/xml_all_uebersicht.xml</add>
			/*
			$attrib = [ 
				'http://www.trscript.de/tree:case_folding'=>'0',
				'http://www.trscript.de/tree:doctype'=>'CSV',
				'http://www.trscript.de/tree:id'=>'test',
				];*/
			//$this->back->create_Ns_Node('http://www.trscript.de/tree#add', $this->back->position_stamp(), $attrib);
			
		
		//$xml->set_node_name("a");
		//$xml->set_node_attrib("style","color:white;text-decoration:none;");
		//$xml->set_node_attrib("href","index.php?i=" . $name );
		
		//$res = &$this->back->get_result();
	//echo "-" . $this->back->position_stamp() . "-\n";
		//echo $res[0]->name . " lala";
		}
		//else
		$this->back->show_index();
		$this->back->go_to_stamp($pos2);
		$this->back->go_to_stamp($pos);
		


	}
	
	public function setAdd($name, $path)
	{

		
		
		
		
	}
	
	public function create_tree()
	{
			if(is_Null($this->get_attribute('doctype')))
		$doc_type = 'XML';
		else
		$doc_type = $this->get_attribute('doctype');
										
										
										
					
		if($this->get_attribute('case_folding')=="0")
		{
		$preload = $this->get_attribute('doctype_out');
											
											//echo 'no-casefold';
											
		$this->get_parser()->load($this->getdata(),0,$doc_type);
		if($this->get_parser()->error_num() <> 0)
		echo $this->get_parser()->error_num() . ':' . error_desc();									
										
											
			if($preload)
			{
													
				$this->get_parser()->TYPE[$this->get_parser()->idx] = $preload;
											
			}
											
		}
		else
		{
			$this->get_parser()->load($this->getdata(),1,$doc_type);
		}
		$this->get_parser()->set_first_node();
		
		
										
			$uri = $this->getRefprev()->full_URI();
			if($uri == 'http://www.trscript.de/tree#template' )
			{
				
				$obj->get_requester()->set_template($this->get_attribute('id'),$this->getdata());
				
				
	
			}
					
	}

	public function many()
	{
			if($this->scanner)
			return count($this->result_table);
	}
	
	//$pos_res
	public function next()
	{
	return false;
	
	}
	public function moveFirst(){return (count($this->result_table) > 0);}
    	public function moveLast(){}
    	public function col($columnname)
    	{
    	if($this->alias[$columnname] == 'number')return $this->pos_res;
    	return $this->result_table[$this->pos_res][$this->alias[$columnname]];
    	}
    	public function iter(){return $this;}
    	public function set_list(&$value){}
    	public function fields(){return array_keys($this->alias);}
    	public function getAdditiveSource(){}
}
?>
