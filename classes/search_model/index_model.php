<?PHP

require_once(__DIR__ . '/search_model_interface.php');

/* Ein Modell ist eine Datei in einem eigenen Unterverzeichnis. Der Glob nimmt STWs
*  File_Scan-Absicht auf: ein neues Modell soll nichts anderes noetig machen als sich
*  selbst. Geladen wird hier, registriert in init_models() ueber model_name(). */
foreach(glob(__DIR__ . '/*/*.php') as $model_file)
	require_once($model_file);

/**
*	Fassade der Suchmodelle.
*
*	Ein Modell wird nicht selbst erzeugt, sondern hier erfragt — mit dem Datenmodell,
*	in dem gesucht werden soll. Der Aufrufer im Baum ist xml_ns::seek_by_model(), das
*	sich selbst als Datenmodell durchreicht: der Baum kommt je Anfrage, nicht aus einem
*	statischen Verweis. Damit darf ein Sucher im Sucher stehen.
*/
class SearchingModelObject
{

	var $nativ;
	var $node = array();
	var $attrib;

	/** Modellname => Klassenname. Wird beim ersten Zugriff gefuellt. */
	public static array $models = [];

	/** Bleibt fuer den Bestandsaufruf in index.php stehen; die Modelle brauchen ihn nicht. */
	public static $treeRef;

	/**
	*	Traegt die Modelle ein. Die Klassen nennen ihren Namen selbst
	*	(Searching_Model::model_name), damit ein neues Modell nur eine Datei ist und
	*	keine Aenderung hier.
	*
	*	Der frueher hier stehende File_Scan-Durchlauf ueber classes/search_model/ hat
	*	die Verzeichnisliste per print_r in die Ausgabe geschrieben und nichts
	*	registriert. Die Selbstentdeckung ist geblieben, die Ausgabe nicht.
	*/
	public static function init_models()
	{
		if(count(self::$models) > 0)
			return self::$models;

		foreach(get_declared_classes() as $class)
		{
			if(!in_array('Searching_Model', class_implements($class) ?: array(), true))
				continue;

			self::$models[$class::model_name()] = $class;
		}

		return self::$models;
	}

	/**
	*	Nennt alle Modelle mit ihrer Beschreibung — die Selbstauskunft der Suchschicht.
	*
	*	@return	array	Modellname => Beschreibung
	*/
	public static function describe_models(): array
	{
		$res = array();

		foreach(self::init_models() as $name => $class)
			$res[$name] = $class::model_description();

		return $res;
	}

	/**
	*	@param	string	$model_name	Name des Modells, siehe describe_models()
	*	@param	mixed	$data_model	Datenmodell, in dem gesucht wird (i.d.R. xml_ns)
	*	@return	Searching_Model|null
	*/
	public static function &model_factory($model_name, &$data_model = null)
	{
		$none = null;

		self::init_models();

		if(!isset(self::$models[$model_name]))
			return $none;

		$class = self::$models[$model_name];
		$model = new $class($data_model);

		return $model;
	}
}

?>
