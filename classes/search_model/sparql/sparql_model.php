<?PHP

/**
*	search model for SPARQL
*
*	SPARQL ist eine SPRACHE, keine Gegenstelle. Das Modell waehlt darum kein "wohin"
*	mehr aus einer eigenen Liste, sondern nennt ein VERBINDUNGSPROFIL ([connection],
*	siehe ConnectionProfile) — und das Profil sagt, wer die Sprache spricht:
*
*	  type      "fuseki"  ein SPARQL-Endpunkt ueber REST
*	            "qportal" eine qPortal-Instanz
*	  address   leer = diese Instanz bzw. localhost, sonst die Gegenstelle
*	  source    der Behaelter darin (bei fuseki der benannte Graph, bei qportal der Baum)
*
*	Damit faellt die alte Trennung "intern gegen fuseki" weg: sie war keine Trennung
*	der Sprache, sondern eine der ADRESSE. Ein Fuseki darf lokal laufen, eine zweite
*	qPortal-Instanz darf entfernt stehen; beides sind Profile, nicht Sonderfaelle.
*
*	Derselbe Ausdruck laeuft gegen jedes Profil, ohne sich zu aendern — gewechselt wird
*	die Gegenstelle, nicht die Frage. Welches Profil ohne Angabe gilt, steht in
*	[search] sparql.use; ein Aufrufer waehlt je Aufruf mit use_source().
*
*	Gebaut ist der Typ "fuseki": REST an die Adresse, die Antwort kommt als
*	SPARQL-Results-JSON und wird ueber SPARQL_handle in einen eigenen Baum geladen;
*	zurueck gehen dessen Knoten. Das Ergebnis einer Fremdabfrage ist damit ein
*	qPortal-Baum wie jeder andere.
*
*	Der Typ "qportal" ist halb gebaut. Der AUSDRUCK wird gelesen — SPARQL_Parser
*	(classes/search_model/sparql/sparql_parser.php) macht aus ihm die Struktur aus
*	Spalten und Tripeln, ohne Verbindung und ohne Baum; parse() gibt sie heraus, und
*	query() liest jeden Ausdruck zuerst, damit eine kaputte Anfrage schon hier auffaellt.
*	Was fehlt, ist die AUSWERTUNG: was ein Tripel im Baum bedeutet. Die Vorlage dafuer
*	steht in anttree/funct_parser_lib.js (SPARQLObject.execute mit estimateCost), laeuft
*	dort aber gegen anttrees eigene semantic_web-Ablage und nicht gegen einen qPortal-
*	Baum — das ist eine Entscheidung, keine Portierung.
*/

class SPARQL_Model implements Searching_Model
{
	/** Plattformen, mit denen dieses Modell reden kann. Nicht die Profilnamen. */
	const TYPES = array('qportal', 'fuseki');

	private $data_model;
	private array $config = array();

	/** Ausdruecklich gewaehltes Profil; leer heisst: das aus sparql.use. */
	private string $chosen = '';

	/** Baum, in den die Antwort geladen wurde. Gehalten, damit die Knoten leben. */
	private $result_tree = null;

	/** Der Leser der Sprache. Kommt beim ersten Bedarf, haelt keinen Zustand ueber den Lauf. */
	private ?SPARQL_Parser $parser = null;

	/** Loesungen der letzten Abfrage gegen einen Baum: je Zeile Variable => Wert. */
	private array $solutions = array();

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
		return 'SPARQL gegen eine benannte Gegenstelle. Welche das ist, steht als '
		     . 'Verbindungsprofil in [connection]; die Plattformen sind "fuseki" (ein '
		     . 'SPARQL-Endpunkt, gebaut) und "qportal" (eine qPortal-Instanz, noch '
		     . 'nicht gebaut). Ob lokal oder entfernt, sagt die Adresse des Profils.';
	}

	public function set_model_config(array $config)
	{
		$this->config = $config;
	}

	/**
	*	Waehlt das Verbindungsprofil fuer die naechsten Aufrufe. Ohne Argument zurueck
	*	zur Vorgabe aus sparql.use.
	*
	*	@throws	Exception	wenn es das Profil nicht gibt — ein Tippfehler soll hier
	*				auffallen und nicht erst beim Verbindungsaufbau
	*/
	public function use_source(string $name = '')
	{
		if($name !== '')
			$this->profile_of($name);   /* wirft, wenn es ihn nicht gibt */

		$this->chosen = $name;

		return $this;
	}

	/**
	*	Der Name des Profils, gegen das gerade gefragt wird — ausdrueckliche Wahl vor
	*	sparql.use.
	*/
	public function source(): string
	{
		if($this->chosen !== '')
			return $this->chosen;

		return trim((string)($this->config['use'] ?? ''));
	}

	/**
	*	Das Profil selbst.
	*
	*	@throws	Exception	wenn keines gewaehlt ist
	*/
	public function profile(): ConnectionProfile
	{
		$name = $this->source();

		if($name === '')
			throw new Exception('SPARQL_Model: keine Gegenstelle gewaehlt. In config.ini '
			                  . 'unter [search] sparql.use den Namen eines Profils aus '
			                  . '[connection] eintragen, oder use_source() aufrufen.');

		return $this->profile_of($name);
	}

	/**
	*	Loest einen Profilnamen auf. Zuerst in [connection]; findet sich dort nichts,
	*	gilt ein gleichnamiger Zweig sparql.<name>.* als Profil — der Rueckfallweg fuer
	*	Konfigurationen aus der Zeit vor [connection], in denen die Adresse noch
	*	"endpoint" hiess.
	*
	*	@throws	Exception	wenn der Name nirgends steht
	*/
	private function profile_of(string $name): ConnectionProfile
	{
		$fields = array();

		/* Unterlage: der eigene Zweig sparql.<name>.* aus der Zeit vor [connection].
		*  Dort hiess die Adresse "endpoint", und der Typ stand im Namen. */
		if(isset($this->config[$name]) && is_array($this->config[$name]))
		{
			$fields = $this->config[$name];

			if(!isset($fields['address']) && isset($fields['endpoint']))
				$fields['address'] = $fields['endpoint'];

			if(!isset($fields['type']))
				$fields['type'] = ($name === 'intern') ? 'qportal' : $name;
		}

		/* [connection] liegt darueber — wie config.ini ueber default.ini. Ein LEERES
		*  Feld ueberschreibt dabei nicht: es ist keine Aussage, sondern eine offene
		*  Stelle, sonst loeschte das mitgelieferte Musterprofil eine gesetzte Adresse. */
		if(class_exists('ConnectionProfile') && ConnectionProfile::exists($name))
			foreach(ConnectionProfile::get($name)->fields() as $key => $value)
				if(($value !== '') || !isset($fields[$key]))
					$fields[$key] = $value;

		if(count($fields) === 0)
			throw new Exception('SPARQL_Model: kein Verbindungsprofil "' . $name . '". '
			                  . 'Profile stehen in [connection]; bekannt sind '
			                  . (class_exists('ConnectionProfile')
			                      && count(ConnectionProfile::names())
			                        ? implode(', ', ConnectionProfile::names())
			                        : '(keine)') . '.');

		return ConnectionProfile::from_array($name, $fields);
	}

	/**
	*	Der Geltungsbereich innerhalb der Gegenstelle: bei fuseki der benannte Graph,
	*	bei qportal der Baum. Leer heisst, dass die Gegenstelle selbst entscheidet.
	*/
	public function scope(string $name = ''): string
	{
		return ($name !== '')
			? $this->profile_of($name)->source()
			: $this->profile()->source();
	}

	/**
	*	Welche Gegenstellen dieses Modell bedienen kann — fuer eine Selbstauskunft,
	*	ohne dass etwas abgeschickt wird. Zugangsdaten sind nicht dabei.
	*
	*	@return	array	Profilname => array(type, address, source)
	*/
	public function configured_sources(): array
	{
		if(!class_exists('ConnectionProfile'))
			return array();

		$res = array();

		foreach(ConnectionProfile::describe() as $name => $fields)
			if(in_array($fields['type'], self::TYPES, true))
				$res[$name] = $fields;

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

		$profile = $this->profile();

		switch($profile->type())
		{
			case 'fuseki' :
				return $this->query_fuseki($profile, $statement);

			case 'qportal' :
				/* Gelesen wird der Ausdruck zuerst — so faellt eine kaputte Anfrage mit der
				*  Meldung des Parsers auf (Zustand, Zeichen, Stelle) und nicht mit einer
				*  pauschalen Auskunft. */
				$query = $this->parse($statement);

				if(!$profile->is_local())
					throw new Exception('SPARQL_Model: der Ausdruck ist gelesen ('
					                  . count($query['select']) . ' Spalten, '
					                  . count($query['where']) . ' Tripel), aber der Weg zur '
					                  . 'entfernten Instanz ' . $profile->address()
					                  . ' fehlt noch.');

				return $this->query_tree($query);

			case '' :
				throw new Exception('SPARQL_Model: das Profil "' . $profile->name()
				                  . '" nennt keinen type. Moeglich sind '
				                  . implode(', ', self::TYPES) . '.');

			default :
				throw new Exception('SPARQL_Model: mit dem Typ "' . $profile->type()
				                  . '" (Profil "' . $profile->name() . '") kann dieses '
				                  . 'Modell nicht reden. Moeglich sind '
				                  . implode(', ', self::TYPES) . '.');
		}
	}

	/**
	*	Schickt den Ausdruck an die Adresse des Profils und laedt die Antwort als Baum.
	*	Ohne Adresse wird nichts abgeschickt, sondern gesagt, was fehlt.
	*/
	private function query_fuseki(ConnectionProfile $profile, string $statement): array
	{
		$endpoint = $profile->address();

		if($endpoint === '')
			throw new Exception('SPARQL_Model: das Profil "' . $profile->name() . '" hat '
			                  . 'keine address. Ein Fuseki hat immer eine, auch wenn er '
			                  . 'lokal laeuft — der Datensatz steht darin '
			                  . '(http://host:3030/<datensatz>/query). Ohne Adresse wird '
			                  . 'nichts abgeschickt.');

		if(!class_exists('REST_Connection'))
			throw new Exception('SPARQL_Model: classes/class_REST.php ist nicht geladen.');

		$body  = array('query' => $statement);
		$scope = $profile->source();

		/* Der Geltungsbereich geht als default-graph-uri mit — so sieht das
		*  SPARQL-Protokoll die Einschraenkung auf einen Graphen vor, und der Ausdruck
		*  selbst bleibt unangetastet. */
		if($scope !== '')
			$body['default-graph-uri'] = $scope;

		$rest = new REST_Connection($endpoint);
		$rest->setMethod('POST')
		     ->setHeader('Accept', 'application/sparql-results+json')
		     ->setHeader('Content-Type', 'application/x-www-form-urlencoded')
		     ->setVerifySsl($profile->verify_ssl())
		     ->setBody($body);

		$user = $profile->user();

		if($user !== '')
			$rest->setBasicAuth($user, $profile->password());

		$rest->request();

		if($rest->getStatusCode() >= 400)
			throw new Exception('SPARQL_Model: ' . $profile->name() . ' antwortete mit HTTP '
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
	*	Liest einen Ausdruck, ohne ihn abzuschicken: welche Spalten gefragt sind und
	*	welche Tripel gesucht werden, mit aufgeloesten URIs.
	*
	*	Das ist die Sprache, nicht die Gegenstelle — darum braucht es dafuer weder Profil
	*	noch Verbindung noch Baum, und darum laesst es sich einzeln pruefen.
	*
	*	@param	string	$statement	SPARQL-Ausdruck
	*	@return	array			base, prefixes, select, where — siehe SPARQL_Parser::parse
	*	@throws	Exception		wenn der Ausdruck nicht angenommen wird
	*/
	public function parse(string $statement): array
	{
		if($this->parser === null)
			$this->parser = new SPARQL_Parser();

		return $this->parser->parse($statement);
	}

	/**
	*	Wertet die gelesene Struktur gegen den eigenen Baum aus.
	*
	*	Zurueck gehen die KNOTEN — das ist, was die Schnittstelle zusagt und was ein
	*	Aufrufer im Baum weiterverwenden kann. Die volle Belegung (auch die Attributwerte,
	*	die keine Knoten sind) steht danach in solutions().
	*
	*	@param	array	$query	Struktur aus SPARQL_Parser::parse
	*	@return	array		die Knoten der ersten Spalte, die Knoten bindet
	*/
	private function query_tree(array $query): array
	{
		if(!class_exists('SPARQL_Tree_Query'))
			throw new Exception('SPARQL_Model: sparql_tree_query.php ist nicht geladen.');

		if(!is_object($this->data_model))
			throw new Exception('SPARQL_Model: dieses Modell haelt keinen Baum. Ueber '
			                  . 'xml_ns::seek_by_model() bekommt es einen.');

		$engine = new SPARQL_Tree_Query($this->data_model);
		$this->solutions = $engine->run($query);

		$res = array();

		foreach($this->solutions as $zeile)
			foreach($zeile as $wert)
				if(is_object($wert)){ $res[] = $wert; break; }

		return $res;
	}

	/**
	*	Die Belegungen der letzten Abfrage gegen einen Baum — je Zeile Variable => Wert.
	*	Knoten bleiben Knoten, Attributwerte sind Zeichenketten.
	*/
	public function solutions(): array
	{
		return $this->solutions;
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
