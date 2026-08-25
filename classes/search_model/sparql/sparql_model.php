<?PHP

/**
*	search model for SPARQL
*
*	Das Modell kennt mehrere Quellen nebeneinander, nicht eine aktive. Jede traegt ihren
*	eigenen Zweig in der Konfiguration ([search] sparql.<quelle>.*), und darin sagt
*	"source", welcher Geltungsbereich innerhalb dieser Quelle gemeint ist:
*
*	  sparql.intern.source   welcher Baum dieser Instanz durchsucht wird
*	  sparql.fuseki.source   welcher Datensatz bzw. benannte Graph auf dem Server
*
*	Damit ist derselbe Ausdruck gegen verschiedene Quellen laufbar, ohne dass er sich
*	aendert — was gewechselt wird, ist die Quelle, nicht die Frage. Welche Quelle ohne
*	Angabe gilt, steht in sparql.use; ein Aufrufer waehlt mit use_source().
*
*	Gebaut ist bisher die Quelle "fuseki": REST an den Endpunkt, die Antwort kommt als
*	SPARQL-Results-JSON und wird ueber SPARQL_handle in einen eigenen Baum geladen;
*	zurueck gehen dessen Knoten. Das Ergebnis einer Fremdabfrage ist damit ein
*	qPortal-Baum wie jeder andere.
*
*	"intern" ist benannt und noch nicht gebaut. Die Tripelauswertung dafuer steht in
*	anttree/funct_parser_lib.js (SPARQLObject.execute mit estimateCost) als
*	durchgearbeitetes Beispiel, der Parser in xml_multitree_SPARQL.php.
*/

class SPARQL_Model implements Searching_Model
{
	/** Quellen, die dieses Modell bedienen kann. */
	const SOURCES = array('intern', 'fuseki');

	private $data_model;
	private array $config = array();

	/** Ausdruecklich gewaehlte Quelle; leer heisst: die aus sparql.use. */
	private string $chosen = '';

	/** Baum, in den die Antwort geladen wurde. Gehalten, damit die Knoten leben. */
	private $result_tree = null;

	public function __construct(&$data_model)
	{
		$this->data_model = &$data_model;
	}

	public static function model_name(): string
	{
		return 'sparql';
	}

	public static function model_description(): string
	{
		return 'SPARQL gegen eine konfigurierte Quelle. Moeglich sind "fuseki" (ein '
		     . 'entfernter Endpunkt) und "intern" (die geladenen Dokumente, noch nicht '
		     . 'gebaut); der Geltungsbereich steht je Quelle in [search] '
		     . 'sparql.<quelle>.source.';
	}

	public function set_model_config(array $config)
	{
		$this->config = $config;
	}

	/**
	*	Waehlt die Quelle fuer die naechsten Aufrufe. Ohne Argument zurueck zur Vorgabe.
	*
	*	@throws	Exception	wenn es die Quelle nicht gibt
	*/
	public function use_source(string $name = '')
	{
		if(($name !== '') && !in_array($name, self::SOURCES, true))
			throw new Exception('SPARQL_Model: unbekannte Quelle "' . $name . '". Moeglich '
			                  . 'sind ' . implode(', ', self::SOURCES) . '.');

		$this->chosen = $name;

		return $this;
	}

	/**
	*	Die Quelle, gegen die gerade gefragt wird — ausdrueckliche Wahl vor sparql.use.
	*/
	public function source(): string
	{
		if($this->chosen !== '')
			return $this->chosen;

		return trim((string)($this->config['use'] ?? ''));
	}

	/**
	*	Der Geltungsbereich innerhalb der Quelle: bei fuseki der Datensatz bzw. Graph,
	*	bei intern der Baum. Leer heisst, dass die Quelle selbst entscheidet.
	*/
	public function scope(string $source = ''): string
	{
		$source = ($source !== '') ? $source : $this->source();

		return trim((string)($this->config[$source]['source'] ?? ''));
	}

	/**
	*	Welche Quellen konfiguriert sind — fuer eine Selbstauskunft, ohne dass etwas
	*	abgeschickt wird.
	*
	*	@return	array	Quellenname => Geltungsbereich
	*/
	public function configured_sources(): array
	{
		$res = array();

		foreach(self::SOURCES as $name)
			if(isset($this->config[$name]))
				$res[$name] = $this->scope($name);

		return $res;
	}

	/**
	*	@param	string	$statement	SPARQL-Ausdruck
	*	@return	array			Knoten des Ergebnisbaums
	*/
	public function query(string $statement): array
	{
		$statement = trim($statement);

		if($statement === '')
			return array();

		switch($this->source())
		{
			case 'fuseki' :
				return $this->query_fuseki($statement);

			case 'intern' :
				throw new Exception('SPARQL_Model: die Quelle "intern" ist noch nicht '
				                  . 'gebaut. Der Parser liegt in xml_multitree_SPARQL.php, '
				                  . 'die Tripelauswertung als Beispiel in '
				                  . 'anttree/funct_parser_lib.js.');

			case '' :
				throw new Exception('SPARQL_Model: keine Quelle gewaehlt. In config.ini '
				                  . 'unter [search] sparql.use eine der Quellen '
				                  . implode(', ', self::SOURCES) . ' eintragen, oder '
				                  . 'use_source() aufrufen.');

			default :
				throw new Exception('SPARQL_Model: unbekannte Quelle "' . $this->source()
				                  . '". Moeglich sind ' . implode(', ', self::SOURCES) . '.');
		}
	}

	/**
	*	Schickt den Ausdruck an den konfigurierten Endpunkt und laedt die Antwort als
	*	Baum. Ohne Endpunkt wird nichts abgeschickt, sondern gesagt, was fehlt.
	*/
	private function query_fuseki(string $statement): array
	{
		$conf     = $this->config['fuseki'] ?? array();
		$endpoint = trim((string)($conf['endpoint'] ?? ''));

		if($endpoint === '')
			throw new Exception('SPARQL_Model: [search] sparql.fuseki.endpoint ist leer. '
			                  . 'Ohne Endpunkt wird nichts abgeschickt.');

		if(!class_exists('REST_Connection'))
			throw new Exception('SPARQL_Model: classes/class_REST.php ist nicht geladen.');

		$body  = array('query' => $statement);
		$scope = $this->scope('fuseki');

		/* Der Geltungsbereich geht als default-graph-uri mit — so sieht das
		*  SPARQL-Protokoll die Einschraenkung auf einen Graphen vor, und der Ausdruck
		*  selbst bleibt unangetastet. */
		if($scope !== '')
			$body['default-graph-uri'] = $scope;

		$rest = new REST_Connection($endpoint);
		$rest->setMethod('POST')
		     ->setHeader('Accept', 'application/sparql-results+json')
		     ->setHeader('Content-Type', 'application/x-www-form-urlencoded')
		     ->setVerifySsl((bool)($conf['verify_ssl'] ?? true))
		     ->setBody($body);

		$user = (string)($conf['user'] ?? '');

		if($user !== '')
			$rest->setBasicAuth($user, (string)($conf['password'] ?? ''));

		$rest->request();

		if($rest->getStatusCode() >= 400)
			throw new Exception('SPARQL_Model: der Endpunkt antwortete mit HTTP '
			                  . $rest->getStatusCode() . '.');

		return $this->answer_to_nodes($rest->getRaw());
	}

	/**
	*	Laedt eine SPARQL-Results-Antwort ueber SPARQL_handle in einen eigenen Baum und
	*	gibt dessen Knoten zurueck. Oeffentlich, damit sich der Weg mit einer
	*	gespeicherten Antwort pruefen laesst, ohne etwas abzuschicken.
	*
	*	@param	string	$answer	SPARQL-Results-JSON
	*	@return	array		Knoten des Ergebnisbaums
	*/
	public function answer_to_nodes(string $answer): array
	{
		if(trim($answer) === '')
			return array();

		if(!class_exists('xml_ns'))
			throw new Exception('SPARQL_Model: die ns-Schicht ist nicht geladen.');

		$this->result_tree = new xml_ns();
		$this->result_tree->load_Stream($answer, 0, 'SPARQL');

		return $this->result_tree->collect_nodes();
	}

	/**
	*	Der Baum der letzten Antwort — wer mehr als die Knotenliste braucht (Stempel,
	*	Nachbarschaft, eine zweite Suche darin), bekommt ihn hier.
	*/
	public function &result_tree()
	{
		return $this->result_tree;
	}
}
?>
