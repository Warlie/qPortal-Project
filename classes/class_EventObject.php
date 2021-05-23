<?PHP
class EventObject
{
	var $myrequest;
	var $myrequester;
	var $mycontext;
	var $mynode;
	var $mylocked = false;
	function __construct($request,&$requester,&$context)
	{
		$this->myrequest = $request;
		$this->myrequester = &$requester;
		$this->mycontext = &$context;
		
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
	
	function set_node(&$node)
	{
		if(is_object($this->mynode))unset($this->mynode);
		$this->mynode = &$node;
	}
	
	function set_locked($bool){ $this->mylocked = $bool;}
	function get_locked(){return $this->mylocked;}
}
?>
