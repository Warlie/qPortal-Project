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
private $documents = '';
private $semantic_uri = array();

	function Report(/* System.Content */ &$back, /* System.CurRef */ &$treepos)
	{
		
		$this->back= &$back->getXMLObj();
		
		$this->treepos = &$value;
		
		$this->content = &$back;
		//$this->id = $value; , &$id
		
	}
	
			
	/**
	*@function: MOVEFIRST = goes to first record
	*/
		
	public function generatetbl()
	{
		echo "hallo";
	}
	

		
	public function __toString(){return 'javascript_generator';}	
}

?>
