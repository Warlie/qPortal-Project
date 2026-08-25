<?PHP

/**
*	search model for internal
*
*	Der Weg ueber die Lookup-Tabelle des Baums (xml_ns::collect_nodes). Er kann Namen
*	und — seit dem Suchraum — auch Struktur, aber keine Pfade: ein Ausdruck ist genau
*	ein Schritt. Das ist der Nullpunkt, gegen den sich jedes weitere Modell messen laesst.
*
*	Zusammengesetzte Ausdruecke gehoeren nicht hierher. Sie kommen ueber den Automaten
*	(classes/finite_state_machine/), der einen Ausdruck in Schritte zerlegt; jeder Schritt
*	ist dann wieder ein Aufruf hier, mit der Ergebnismenge des vorigen als Suchraum.
*/

class Internal_Searching_Model implements Searching_Model
{
	private $data_model;

	public function __construct(&$data_model)
	{
		$this->data_model = &$data_model;
	}

	public static function model_name(): string
	{
		return 'internal';
	}

	public static function model_description(): string
	{
		return 'Ein Schritt ueber die Lookup-Tabelle des Baums: eine volle URI, '
		     . 'wahlweise eingeschraenkt auf einen Suchraum.';
	}

	/**
	*	@param	string	$statement	volle URI eines Knotentyps (namespace#name)
	*	@return	array			gefundene Knoten
	*/
	public function query(string $statement): array
	{
		$statement = trim($statement);

		if($statement === '')
			return array();

		/* Praedikate und Pfade bleiben ausdruecklich draussen. Eine hier eingebaute
		*  Zerlegung waere eine dritte Verdrahtung neben dem Automaten, der genau
		*  dafuer da ist — und sie muesste spaeter wieder weg. */
		if(false === ($hash = strpos($statement, '#')))
			throw new Exception('Internal_Searching_Model: "' . $statement . '" ist keine '
			                  . 'volle URI. Die Lookup-Tabelle ist ueber namespace#name '
			                  . 'geschluesselt.');

		/* Ein Schritttrenner steht hinter dem '#' — der Namensraum davor bringt seine
		*  eigenen Schraegstriche mit. */
		$local = substr($statement, $hash + 1);

		if((false !== strpos($local, '[')) || (false !== strpos($local, '/')))
			throw new Exception('Internal_Searching_Model: "' . $statement . '" ist ein '
			                  . 'zusammengesetzter Ausdruck. Dieses Modell kennt genau '
			                  . 'einen Schritt; Zerlegung gehoert in den Automaten.');

		return $this->requestArray('', $statement, null, null);
	}

	/**
	*	Die strukturierte Form — kein Ausdruck, sondern seine Bestandteile. Das ist die
	*	Gestalt, in der der Automat einen Schritt weiterreicht.
	*
	*	@param	string	$namespace	Namensraum ohne '#', leer wenn $tag schon voll ist
	*	@param	string	$tag		lokaler Name oder volle URI
	*	@param	array	$attributes	Attributname => geforderter Wert, UND-verknuepft
	*	@param	array	$values		Datenforderung (vom Bestand noch nicht ausgewertet)
	*	@param	mixed	$scope		Knoten oder Knotenmenge als Suchraum
	*	@param	int	$depth		-1 descendant-or-self, 1 child, 0 self
	*	@return	array			gefundene Knoten
	*/
	public function requestArray($namespace, $tag, $attributes, $values,
	                             $scope = null, int $depth = -1): array
	{
		if(!is_object($this->data_model) || !method_exists($this->data_model, 'collect_nodes'))
			throw new Exception('Internal_Searching_Model: das Datenmodell kennt kein '
			                  . 'collect_nodes() — erwartet wird die ns-Schicht.');

		$uri = (is_string($namespace) && ($namespace !== ''))
		     ? rtrim($namespace, '#') . '#' . $tag
		     : $tag;

		return $this->data_model->collect_nodes($uri,
		                                        is_array($attributes) ? $attributes : null,
		                                        is_array($values)     ? $values     : null,
		                                        $scope,
		                                        $depth);
	}
}
?>
