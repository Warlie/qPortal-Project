<?PHP

/**
*	Verbindungsprofil — eine Gegenstelle unter einem Namen.
*
*	Ein Profil beantwortet immer dieselben vier Fragen, gleich ob dahinter ein
*	SPARQL-Endpunkt, eine qPortal-Instanz oder eine Datenbank steht:
*
*	  type     Wie wird geredet? Die Plattform ("qportal", "fuseki", "mysql").
*	  address  Mit wem? Leer heisst: diese Instanz bzw. localhost.
*	  source   Worin? Der benannte Behaelter auf der Gegenseite.
*	  user / password / verify_ssl   Darf ich?
*
*	Die Trennung, an der es vorher fehlte: SPARQL ist eine SPRACHE, keine Gegenstelle.
*	qPortal und Fuseki sind beide Plattformen, die sie sprechen — der Unterschied
*	zwischen ihnen ist der "type", der Unterschied zwischen lokal und entfernt ist die
*	"address". Ein Endpunkt darf lokal sein; "intern" war deshalb keine eigene Quelle,
*	sondern ein qportal-Profil ohne Adresse.
*
*	Die "source" ist die Ebene, die jede Plattform hat und die vorher mit der Adresse
*	verschmolzen war:
*
*	  mysql   : Server (address) -> Datenbank (source)  -> Tabelle
*	  fuseki  : Server (address) -> Datensatz (source)  -> benannter Graph
*	  qportal : Instanz (address)-> Baum (source)       -> Teilbaum
*
*	Das Vorbild ist class_database::db_profiles()/change_profile(): eine benannte
*	Sammlung und ein Umschalter je Aufruf. Hier ohne Bindung an eine Plattform.
*
*	@see	config/default.ini	Abschnitt [connection]
*/
class ConnectionProfile
{
	/** Profilname => Feldarray. Von index.php aus [connection] gesetzt. */
	private static array $collection = array();

	private string $name;
	private array  $fields;

	private function __construct(string $name, array $fields)
	{
		$this->name   = $name;
		$this->fields = $fields;
	}

	/**
	*	Nimmt den Abschnitt [connection] entgegen. Einmal beim Hochfahren.
	*/
	public static function set_collection(array $collection)
	{
		self::$collection = $collection;
	}

	/**
	*	Ein Profil aus einem Feldarray, ohne dass es in der Sammlung stehen muss —
	*	damit ein Verbraucher seinen eigenen Zweig anbieten kann (Rueckfallweg) und
	*	damit sich ein Profil im Test von Hand stellen laesst.
	*/
	public static function from_array(string $name, array $fields): self
	{
		return new self($name, $fields);
	}

	/**
	*	Das benannte Profil.
	*
	*	@throws	Exception	wenn es den Namen nicht gibt — ein Tippfehler soll hier
	*				auffallen und nicht erst beim Verbindungsaufbau
	*/
	public static function get(string $name): self
	{
		if(!isset(self::$collection[$name]) || !is_array(self::$collection[$name]))
			throw new Exception('ConnectionProfile: kein Profil "' . $name . '". '
			                  . 'Bekannt sind ' . (count(self::names())
			                      ? implode(', ', self::names())
			                      : '(keine — [connection] ist leer)') . '.');

		return new self($name, self::$collection[$name]);
	}

	public static function exists(string $name): bool
	{
		return isset(self::$collection[$name]) && is_array(self::$collection[$name]);
	}

	/** @return string[] */
	public static function names(): array
	{
		$res = array();

		foreach(self::$collection as $name => $fields)
			if(is_array($fields))
				$res[] = (string)$name;

		return $res;
	}

	/**
	*	Selbstauskunft ueber die Gegenstellen — ohne Zugangsdaten, damit sie sich
	*	gefahrlos ausgeben laesst.
	*
	*	@return	array	Profilname => array(type, address, source)
	*/
	public static function describe(): array
	{
		$res = array();

		foreach(self::names() as $name)
		{
			$p = self::get($name);

			$res[$name] = array('type'    => $p->type(),
			                    'address' => $p->address(),
			                    'source'  => $p->source());
		}

		return $res;
	}

	public function name(): string
	{
		return $this->name;
	}

	/** Die Plattform. Leer bleibt leer — geraten wird nicht. */
	public function type(): string
	{
		return trim((string)($this->fields['type'] ?? ''));
	}

	/** Leer heisst: diese Instanz bzw. localhost. */
	public function address(): string
	{
		return trim((string)($this->fields['address'] ?? ''));
	}

	/** Der benannte Behaelter auf der Gegenseite. Leer laesst die Gegenstelle waehlen. */
	public function source(): string
	{
		return trim((string)($this->fields['source'] ?? ''));
	}

	public function user(): string
	{
		return (string)($this->fields['user'] ?? '');
	}

	public function password(): string
	{
		return (string)($this->fields['password'] ?? '');
	}

	public function verify_ssl(): bool
	{
		return (bool)($this->fields['verify_ssl'] ?? true);
	}

	/**
	*	Ohne Adresse ist die Gegenstelle diese Instanz selbst. Das ist der Unterschied,
	*	der frueher "intern" gegen "fuseki" hiess — und er haengt an der Adresse, nicht
	*	am Typ: eine zweite qPortal-Instanz nebenan traegt denselben Typ mit Adresse.
	*/
	public function is_local(): bool
	{
		return $this->address() === '';
	}

	/**
	*	Alle Felder, wie sie in der Konfiguration stehen — damit ein Verbraucher ein
	*	Profil ueber ein eigenes legen kann, statt es zu verdecken.
	*/
	public function fields(): array
	{
		return $this->fields;
	}

	/** Ein einzelnes Feld, fuer Plattformen mit eigenen Zusatzangaben. */
	public function field(string $key, $default = '')
	{
		return $this->fields[$key] ?? $default;
	}
}

?>
