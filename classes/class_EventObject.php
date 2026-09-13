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

	/* Die benannten Argumente eines Laufs — der Rahmen, in dem eine Befehlskette
	*  Werte UNTER NAMEN sammelt.
	*
	*  ⚠ Warum es mycontext nicht tut: dort steht genau EIN Wert, und jeder Schritt
	*  ueberschreibt den vorigen. Das reicht, um ein Datum weiterzugeben, aber nicht,
	*  um mehrere zu sammeln. STW (2026-09-13): "Ich kann aber aktuell immer nur ein
	*  Datum sinnvoll transportieren. Klar gehen mehrere, aber wenn ich Daten fuer
	*  Argumente sammel, dann muessen diese strukturiert (zugewiesen mit Argumentnamen)
	*  uebergeben werden. Meine Befehlsketten werden ansonsten eher kurz sein."
	*
	*  Geschrieben wird ausschliesslich ueber __argument (behavior/std.php). Wer den
	*  Rahmen nicht benutzt, merkt nichts von ihm — er ist leer und wird von nichts
	*  Bestehendem gelesen.
	*
	*  ⚠ OFFEN: die LEBENSDAUER. Heute lebt der Rahmen so lange wie das Ereignis. Ob er
	*  geklammert gehoert wie clearance (push/pop je Kette), entscheidet sich daran, ob
	*  verschachtelte Ketten sich gegenseitig ueberschreiben duerfen. Siehe den Kopf von
	*  __argument. */
	var $myarguments = array();

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
	
	/** Ein Argument unter seinem Namen ablegen. Ein gleicher Name ueberschreibt. */
	function set_argument($name, $value)
	{
		$this->myarguments[(string) $name] = $value;
	}

	/** Alle benannten Argumente. Leer, solange niemand __argument benutzt hat. */
	function &get_arguments()
	{
		return $this->myarguments;
	}

	/** Den ganzen Rahmen setzen - zum Leeren (reset, flush). */
	function set_arguments(&$arguments)
	{
		$this->myarguments = $arguments;
	}

	/** Ein einzelnes, oder der Vorgabewert. */
	function get_argument($name, $default = null)
	{
		return $this->myarguments[(string) $name] ?? $default;
	}

	function set_locked($bool){ $this->mylocked = $bool;}
	function get_locked(){return $this->mylocked;}
}
?>
