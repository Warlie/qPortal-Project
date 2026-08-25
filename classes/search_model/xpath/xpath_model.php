<?PHP

/**
*	search model for XPath
*
*	Noch nicht gebaut. Der Platz ist absichtlich frei: die Zerlegung eines Pfades in
*	Schritte gehoert in den Automaten (classes/finite_state_machine/ mit
*	classes/fs_parser/qp_workflow.php als durchgearbeitetem Beispiel), nicht in eine
*	handgeschriebene Zerlegung an dieser Stelle. Vorlage fuer die Grammatik ist
*	anttree/funct_parser_lib.js — dort steht dieselbe Automatenbauart mit einer
*	deklarativen SPARQL-Grammatik darauf.
*
*	Ausgewertet wird ein Schritt dann ueber Internal_Searching_Model::requestArray(),
*	mit der Ergebnismenge des vorigen Schritts als Suchraum.
*/

class XPath implements Searching_Model
{
	private $data_model;

	public function __construct(&$data_model)
	{
		$this->data_model = &$data_model;
	}

	public static function model_name(): string
	{
		return 'xpath';
	}

	public static function model_description(): string
	{
		return 'Pfadausdruecke ueber den Automaten. Noch nicht gebaut.';
	}

	public function query(string $statement): array
	{
		/* Bisher gab dieses query() null zurueck, obwohl es array deklariert — unter
		*  PHP 8 ein TypeError an der Aufrufstelle statt einer Aussage hier. */
		throw new Exception('XPath: dieses Modell ist noch nicht gebaut. Ein einzelner '
		                  . 'Schritt laeuft heute ueber das Modell "internal".');
	}
}
?>
