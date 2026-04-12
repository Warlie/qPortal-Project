<?php

/**  TREE_subtree
*

*/

/*@ 
	<content name="response#response" ><subtree name="#human" id="myresponse" /></content>
	
*/

class TREE_subtree extends Interface_node
{
var $name = 'empty';
var $type = 'none';
var $namespace = 'none';
	
function __construct()
{

}

function &get_Instance()
{
return new TREE_subtree();
}


function &new_Instance()
{
                                
				$obj = $this->get_Instance();

				$obj->link_to_class = &$this;
				
				return $obj;
}

//primar call after finishing object, ther wont be an existing childnode
function event_initiated()
{
	$this->to_listener();

}

function complete()
	{
		parent::complete();
		

	}


	
function event_message_in($type,&$obj)
	{


	
				// ---------------------- Start with progress --------------------
		
		if($obj instanceof EventObject )
		{
		$template = $obj->get_requester()->get_out_template($other_template);
		//echo $obj->get_requester()->get_out_template();
		$obj->get_requester()->set_current_template($obj->get_requester()->get_out_template()) ;
		
			//changes maintemplate, to edit an non maintemplate
			if ($other_template = $this->get_attribute('id'))
			{
			
				if($template = $obj->get_requester()->get_template($other_template))
				{
							$obj->get_requester()->set_current_template($other_template) ;
				}	
				else
				{
				echo "Der Name $other_template konnte nicht bei den Templates gefunden werden";
				$template = $obj->get_requester()->get_out_template($other_template);
				}
			}
		
		
		
			
		$tag_name = $this->get_attribute('name');
		$tag_array = array();
		$void = false;
		
		for($i = 0 ; $i < $this->index_max();$i++)
		{
			$tmp = $this->getRefnext($i,true);
			if($tmp->full_URI() == 'http://www.trscript.de/tree#param')
			{
				$tmp->event_message_in('',$obj);
				
				$tag_array[$tmp->get_attribute('name')] = $tmp->getdata();
			}
		}

	$this->get_parser()->change_URI($template);
	
	if($transform = $this->get_attribute('transform_output'))
	{
		$proc = new XSLTProcessor();
    
		$xsl = new DOMDocument();
		if (!$xsl->load($transform)) {
			return false;
			}

			$xmlDoc = new DOMDocument();

			if (!@$xmlDoc->loadXML($this->get_parser()->save_Stream())) {
					return "Fehler: Ungültiges XML-Format.";
			}
								
			$proc->importStyleSheet($xsl);


    		$transformedText = $proc->transformToXML($xmlDoc);
    		//$obj->get_node()->set_bolcdata(true);
    		//echo $transformedText . " " . $this->get_parser()->save_Stream();
    		$obj->get_node()->setdata($transformedText);
    		return;
		//echo $transformedText;
	}
	
	
	$this->get_parser()->flash_result();
	if($this->get_parser()->seek_node($tag_name,$tag_array) )
	{
		//var_dump("addtree",$obj->get_node(), "for cloning", $this->get_parser()->show_xmlelement());
		$this->get_parser()->show_xmlelement()->cloning($obj->get_node());
		//echo "cloning done";

	}
	else
	{
		echo 'Tag "' . $tag_name . '" was not found in the ' . $template . " document<br>\n";
				$this->get_parser()->test_consistence();

	}

	}

	}
}

?>
