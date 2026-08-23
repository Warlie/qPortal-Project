<?PHP
class RegistryNotFoundException extends \RuntimeException {}

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

	/* Erklaerungen der Ebenen ueber dem Befehl. Bewusst flach und neben
	*  $behaviors, damit dessen Struktur [ns][ln][command] unberuehrt bleibt.
	*/
	private $nsDescription = [];   // [ns]      => Text
	private $lnDescription = [];   // [ns][ln]  => Text

	public function _useGeneral()
	{
		$this->currentNSName = "";
		$this->currentLocalName = '';
		return $this;
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
            throw new RegistryNotFoundException($ns . " is unknown");
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
            throw new RegistryNotFoundException($localName . " is unknown");
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

	public function getKey(): string
	{
		return implode('.', [$this->currentNSName, $this->currentLocalName, $this->commandName]);
	}

	// Call immediately after registering a command via __set.
	public function addLog(Closure | string $func, int $lvl = 5)
	{
		$this->behaviors[$this->currentNSName][$this->currentLocalName][$this->commandName]["log"] = $func;
		$this->behaviors[$this->currentNSName][$this->currentLocalName][$this->commandName]["level"] = $lvl;
	}

	/* Beschreibung des zuletzt registrierten Befehls. Wie addLog direkt nach der
	*  Registrierung aufrufen - sie haengt am selben commandName.
	*  $attribute: je Parametername ["description" => string, "required" => bool].
	*  Die Parameter eines Befehls sind seine Attribute, deshalb der Name.
	*/
	public function addDescription(string $description, array $attribute = [])
	{
		$this->behaviors[$this->currentNSName][$this->currentLocalName][$this->commandName]["info"] =
			["description" => $description, "attribute" => $attribute];
	}

	/* Erklaerung des Namensraums - wofuer seine Befehle da sind. Nach _addNS/_useNS
	*  aufrufen. Getrennt von addLocalNameDescription, weil _useNS den lokalen Namen
	*  stehen laesst: ein gemeinsamer Setter haenge sonst am Rest des Vorgaengers.
	*/
	public function addNamespaceDescription(string $description)
	{
		if ($this->currentNSName === null)
			throw new Exception("No Namespace selected. Use _useNS() first.");

		$this->nsDescription[$this->currentNSName] = $description;
	}

	/* Erklaerung des lokalen Namens - auf welcher Art Knoten seine Befehle ankommen.
	*  Nach _addLN/_useLN aufrufen.
	*/
	public function addLocalNameDescription(string $description)
	{
		$this->checkContext();
		$this->lnDescription[$this->currentNSName][$this->currentLocalName] = $description;
	}

	/* Lesende Seite: die Registry gibt ueber sich selbst Auskunft.
	*  Liefert je Namensraum die lokalen Namen mit der Anzahl ihrer Befehle.
	*  Fehlende Erklaerungen erscheinen als null - eine Luecke soll sichtbar sein.
	*/
	public function listNamespaces(): array
	{
		$out = [];
		foreach ($this->behaviors as $ns => $localNames)
		{
			$entry = [
				"namespace"   => $ns,
				"description" => $this->nsDescription[$ns] ?? null,
				"localName"   => []
			];
			foreach ($localNames as $ln => $commands)
				$entry["localName"][] = [
					"name"        => $ln,
					"description" => $this->lnDescription[$ns][$ln] ?? null,
					"commands"    => count($commands)
				];
			$out[] = $entry;
		}
		return $out;
	}

	/* Alle Befehle eines Namensraums mit ihrer Beschreibung.
	*  $localName weggelassen = alle lokalen Namen des Namensraums.
	*  Befehle ohne addDescription erscheinen mit description=null - eine fehlende
	*  Beschreibung soll sichtbar sein, nicht verschwiegen.
	*/
	public function describeNamespace(string $ns, ?string $localName = null): array
	{
		if (!array_key_exists($ns, $this->behaviors))
			throw new RegistryNotFoundException($ns . " is unknown");

		$out = [];
		foreach ($this->behaviors[$ns] as $ln => $commands)
		{
			if (!is_null($localName) && $ln !== $localName) continue;

			foreach ($commands as $name => $slot)
				$out[] = [
					"name"        => $name,
					"namespace"   => $ns,
					"localName"   => $ln,
					"description" => $slot["info"]["description"] ?? null,
					"attribute"   => $slot["info"]["attribute"]   ?? [],
					"logged"      => false !== $slot["log"],
					"level"       => $slot["level"]
				];
		}

		if (!is_null($localName) && empty($out) && !array_key_exists($localName, $this->behaviors[$ns]))
			throw new RegistryNotFoundException($localName . " is unknown");

		return $out;
	}
}
?>
