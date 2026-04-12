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
	private $commandName = null;

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
            throw new Exception("No Namespace selected. Use _useNS() first.");
        }

		if (!isset($this->behaviors[$this->currentNSName][$localName])) {
            $this->behaviors[$this->currentNSName][$localName] = [];
        }
        $this->_useLN($localName);
        return $this;
	}

	public function _useLN($localName)
    {
        if (!array_key_exists($localName, $this->behaviors[$this->currentNSName])) {
            throw new Exception($localName . " is unknown");
        }
        $this->currentLocalName = $localName;
        return $this;
    }

    private function checkContext()
    {
        if ($this->currentNSName === null) {
            throw new Exception("No Namespace selected. Use _useNS() first.");
        }
        if ($this->currentLocalName === null) {
            throw new Exception("No LocalName selected. Use _useLN() first.");
        }
    }

    public function __set(string $name, $value)
    {
    	$this->checkContext();
    	if (array_key_exists($name, $this->behaviors[$this->currentNSName][$this->currentLocalName])) {
            throw new Exception($name . " is already in use");
        }
    	$this->commandName = $name;
        $this->behaviors[$this->currentNSName][$this->currentLocalName][$name] = ["command" => $value, "log" => false, "level" => 5];
    }

	public function __get(string $name)
	{
		$this->checkContext();
		return $this->behaviors[$this->currentNSName][$this->currentLocalName][$name];
	}

	public function __isset(string $name)
	{
		$this->checkContext();
		return array_key_exists($name, $this->behaviors[$this->currentNSName][$this->currentLocalName]);
	}

	// Call immediately after registering a command via __set.
	public function addLog(Closure | string $func, int $lvl = 5)
	{
		$this->behaviors[$this->currentNSName][$this->currentLocalName][$this->commandName]["log"] = $func;
		$this->behaviors[$this->currentNSName][$this->currentLocalName][$this->commandName]["level"] = $lvl;
	}
}
?>
