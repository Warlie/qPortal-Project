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
	*  DIE LEBENSDAUER ist die KETTE, nicht das Ereignis (STW, 2026-09-14: "Wenn die Kette
	*  fertig ist, beschaeftigt sich der Garbage Collector mit ihr. Es gibt nur einen
	*  Prozessstrang."). Zwei Stellen halten das:
	*
	*    next_in_chain()  traegt den Rahmen ueber die Befehle EINER Kette weiter — auch
	*                     ueber die, die fuer ihr Value ein neues Ereignis bauen.
	*    __call           klammert ihn (behavior/std.php): der Gerufene faengt mit den
	*                     Attributen des Aufrufs an, der Rufer bekommt seinen Stand
	*                     zurueck. Ein Aufruf ist ein Aufruf, kein Weiterreichen.
	*
	*  ⚠ OFFEN bleibt nur, WER IHN LIEST: heute niemand ausser action=apply. Siehe den
	*  Kopf von __argument, Punkt 3. */
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
	
	/** Das naechste Ereignis DERSELBEN Kette.
	*
	*  Ein Befehl, der seinem Value einen Wert mitgeben will, baut dafuer ein neues
	*  EventObject (so __position_stamp, __add_node, __get_attribute) statt set_context
	*  zu rufen - der Kontext ist eine Referenz, und ein set_context wuerde dem Aufrufer
	*  seinen ueberschreiben.
	*
	*  ⚠ Der Konstruktor setzt myowner auf den REQUESTER. Ueber den Intern-Weg ist das
	*  der ContentGenerator - und damit verlor eine Kette an genau dieser Stelle den
	*  Owner, den ihr jemand gegeben hatte. Gemessen an <access>, das ueber den Owner
	*  seinen Rueckgabewert bekommt: der Wert ging nach aussen statt an den Knoten.
	*
	*  Wer die Kette fortschreibt, nimmt Owner UND Argumentrahmen mit. Wer keinen Owner
	*  gesetzt hat, merkt nichts: dann ist er weiter der Requester.
	*
	*  ⚠ Der Rahmen wird KOPIERT, nicht geteilt - set_arguments weist zu, das & in seiner
	*  Signatur spart nur die Kopie beim Uebergeben. Der Folgeschritt sieht also alles,
	*  was bis hierher gesammelt wurde, und schreibt nicht in ein Ereignis zurueck, das
	*  der Rufer noch in der Hand haelt. Heute ist der Unterschied nicht zu beobachten:
	*  ausser apply liest den Rahmen niemand (siehe __argument, offener Punkt 3). Wenn
	*  das entschieden ist, ist DAS hier die Stelle.
	*/
	function next_in_chain(&$context)
	{
		$folge = new EventObject($this->myrequest, $this->myrequester, $context);

		$folge->set_owner($this->myowner);
		$folge->set_arguments($this->myarguments);

		return $folge;
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
