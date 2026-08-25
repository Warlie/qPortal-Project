<?PHP

/**
*	search model for SPARQL
*
*	Die Quelle steht in der Konfiguration ([search] sparql.source), nicht im Ausdruck:
*	derselbe Ausdruck kann gegen einen entfernten Endpunkt oder gegen die geladenen
*	Dokumente dieser Instanz laufen.
*
*	  "fuseki"    - REST an einen SPARQL-Endpunkt. Die Antwort kommt als
*	                SPARQL-Results-JSON und wird ueber SPARQL_handle in einen eigenen
*	                Baum geladen; zurueck gehen dessen Knoten. Das Ergebnis einer
*	                Fremdabfrage ist damit ein qPortal-Baum wie jeder andere und nicht
*	                eine Sonderform, die der Aufrufer kennen muesste.
*	  "documents" - Auswertung ueber die geladenen Dokumente. Noch nicht gebaut; die
*	                Tripelauswertung dafuer steht in anttree/funct_parser_lib.js
*	                (SPARQLObject.execute mit estimateCost) als durchgearbeitetes
*	                Beispiel, der Parser in xml_multitree_SPARQL.php.
*/

class SPARQL_Model implements Searching_Model
{
	private $data_model;
	private array $config = array();

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
		return 'SPARQL gegen eine konfigurierte Quelle: einen entfernten Endpunkt '
		     . '(fuseki) oder die geladenen Dokumente (documents, noch nicht gebaut). '
		     . 'Siehe [search] sparql.source.';
	}

	public function set_model_config(array $config)
	{
		$this->config = $config;
	}

	/**
	*	Welche Quelle dieses Modell befragt — auch fuer eine Selbstauskunft brauchbar,
	*	ohne dass etwas abgeschickt wird.
	*/
	public function source(): string
	{
		return trim((string)($this->config['source'] ?? ''));
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

			case 'documents' :
				throw new Exception('SPARQL_Model: die Quelle "documents" ist noch nicht '
				                  . 'gebaut. Der Parser liegt in xml_multitree_SPARQL.php, '
				                  . 'die Tripelauswertung als Beispiel in '
				                  . 'anttree/funct_parser_lib.js.');

			case '' :
				throw new Exception('SPARQL_Model: keine Quelle konfiguriert. In config.ini '
				                  . 'unter [search] sparql.source auf "fuseki" oder '
				                  . '"documents" setzen.');

			default :
				throw new Exception('SPARQL_Model: unbekannte Quelle "' . $this->source()
				                  . '". Moeglich sind "fuseki" und "documents".');
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

		$rest = new REST_Connection($endpoint);
		$rest->setMethod('POST')
		     ->setHeader('Accept', 'application/sparql-results+json')
		     ->setHeader('Content-Type', 'application/x-www-form-urlencoded')
		     ->setVerifySsl((bool)($conf['verify_ssl'] ?? true))
		     ->setBody(array('query' => $statement));

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
