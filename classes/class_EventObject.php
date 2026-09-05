<?PHP
class EventObject
{
	var $myrequest;
	var $myrequester;
	var $mycontext;
	var $mynode;
	/* Der Fragende — wem die Antwort gehoert.
	*
	*  NICHT dasselbe wie myrequester: der ist ueber den ganzen Startlauf der
	*  ContentGenerator (er baut das Event mit sich selbst, class_Contentgenerator.php:534),
	*  und NICHT dasselbe wie mycontext: der wandert beim Abstieg mit
	*  (tree_tree.php:212 set_context). Der Owner ist die Stelle, die eine Antwort
	*  erwartet — ein Knoten im Baum oder ein Aufrufer von aussen.
	*
	*  Er ist neu und wird von nichts Bestehendem gelesen; wer ihn nicht setzt,
	*  merkt nichts. Zugestellt wird ueber __to_owner (behavior/std.php). */
	var $myowner;
	var $mylocked = false;
	function __construct($request,&$requester,&$context)
	{
		$this->myrequest = $request;
		$this->myrequester = &$requester;
		$this->mycontext = &$context;
		$this->myowner = &$requester;
		
	}
	function get_request()
	{
		return $this->myrequest;
	}
	
	function &get_requester()
	{
		return $this->myrequester;
	}
	
	function &get_node()
	{
		return $this->mynode;
	}
	
	function &get_context()
	{
		return $this->mycontext;
	}
	
	
	function set_request($request)
	{
		$this->myrequest = $request;
	}
	
	function set_requester(&$requester)
	{
		$this->myrequester = &$requester;
	}
	
	function set_context(&$context)
	{
		$this->mycontext = &$context;
	}
	
	function &get_owner()
	{
		return $this->myowner;
	}
	
	function set_owner(&$owner)
	{
		$this->myowner = &$owner;
	}
	
	function set_node(&$node)
	{
		if(is_object($this->mynode))unset($this->mynode);
		$this->mynode = &$node;
	}
	
	function set_locked($bool){ $this->mylocked = $bool;}
	function get_locked(){return $this->mylocked;}
}
?>
