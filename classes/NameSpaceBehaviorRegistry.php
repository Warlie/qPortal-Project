<?PHP
class NameSpaceBehaviorRegistry
{
	// TODO Das Konzept ist Müll. Definiere lieber Funktionen und Kompositionen. Dann weise diese den Tags zu. Dafür gibt es dann ein "Standard" und ein Tag spezifisches Ding
	private $behaviors = [
    '' => [
        '' => []
        ]
    ];
	private $currentLocalName = null;
	private $currentNSName = null;
	
	public function _useGeneral()
	{
		$this->currentNSName = "";
		$this->currentLocalName = '';
	}
	
	public function _addNS($ns)
	{
		if (!isset($this->behaviors[$ns])) {
            $this->behaviors[$ns] = [];
        }
        $this->_useNS($ns);
        return $this;
	}
	
	
	public function _useNS($ns)
    {
        if (!array_key_exists($ns, $this->behaviors)) {
            throw new Exception($ns . " is unknown");
        }
        $this->currentNSName = $ns;
        return $this;
    }
    
    public function _addLN($localName)
	{
		if ($this->currentNSName === null) {
            throw new Exception("No Namespace selected. Use useNS() first.");
        }
        
		if (!isset($this->behaviors[$this->currentNSName][$localName])) {
            $this->behaviors[$this->currentNSName][$localName] = [];
        }
        $this->_useLN($localName);
        return $this;
	}    
	
	public function _useLN($localName)
    {
        if (!array_key_exists($currentLocalName, $this->behaviors)) {
            throw new Exception($ns . " is unknown");
        }
        $this->currentLocalName = $localName;
        return $this;
    }
	
    private function check()
    {
            if ($this->currentNSName === null) {
            throw new Exception("No Namespace selected. Use _useNS() first.");
        }
        
        if ($this->currentLocalName === null) {
            throw new Exception("No Namespace selected. Use _useLN() first.");
        }

        if (array_key_exists($name, $this->behaviors[$this->currentNSName][$this->currentLocalName])) {
            throw new Exception($name . " is sill in use");
        }
    }
    
    public function __set(string $name, $value)
    {
    	$this->check();
        
        // Wir schreiben direkt in den Speicherplatz des Haupt-Arrays
        $this->behaviors[$this->currentNSName][$this->currentLocalName][$name] = $value;
    }
    
	 public function __get(string $name)
	 {
	 	 $this->check();
	 	 return $this->behaviors[$this->currentNSName][$this->currentLocalName][$name];
	 }
	 
	 public function __isset(string $name)
	 {
	 	 $this->check();
	 	 return array_key_exists($name, $this->behaviors[$this->currentNSName][$this->currentLocalName]);
	 }
}
?>