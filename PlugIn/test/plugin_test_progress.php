<?PHP

/**
*
*
* creates a menu
* @-------------------------------------------
* @title:DBO
* @autor:Stefan Wegerhoff
* @description: Databaseobject, needs only a columndefinition to receive data from other object
*
*/


class Test extends plugin 
{
//private $parser;
//private $treePosition;
//private $content;

	function __construct() //(/* System.Parser */ &$back, /* System.CurRef */ &$treepos, /* System.Content */ &$content)
	{
		//$this->parser= &$back;
		//$this->treePosition = &$value;
		//$this->content = &$content;
		
	}
	
	function test (){echo "test";}
	
	public function showMeMyPosition()
	{
		var_dump($this->treepos->full_URI());
	}

}
?>
