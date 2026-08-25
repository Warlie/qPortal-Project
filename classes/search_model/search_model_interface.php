<?PHP

/**
*	Gemeinsame Flaeche der Suchmodelle.
*
*	Ein Modell bekommt beim Erzeugen das Datenmodell, in dem es sucht — heute die
*	ns-Schicht (xml_ns) mit ihrer Lookup-Tabelle, spaeter ebenso ein entfernter Baum
*	oder eine Tripelablage. Der Aufrufer sieht davon nichts: er reicht einen Ausdruck
*	hinein und bekommt eine Knotenmenge zurueck.
*
*	Die Modelle halten keinen Zustand am Baum. Wer je Schritt eine eigene Menge braucht
*	(ein Pfad zerfaellt in Schritte), bekommt sie; die gemeinsame Ergebnisliste von
*	seek_node bleibt davon unberuehrt.
*/
interface Searching_Model
{
	/**
	*	@param	mixed	$data_model	Datenmodell, in dem gesucht wird (i.d.R. xml_ns)
	*/
	public function __construct(&$data_model);

	/**
	*	Wertet einen Ausdruck aus.
	*
	*	@param	string	$statement	Ausdruck in der Notation des Modells
	*	@return	array			gefundene Knoten, leer wenn nichts passt
	*	@throws	Exception		wenn die Notation nicht getragen wird
	*/
	public function query(string $statement): array;

	/**
	*	Kurzer Name, unter dem das Modell in der Factory steht. Zugleich das, was in
	*	einer Selbstauskunft erscheint.
	*/
	public static function model_name(): string;

	/**
	*	Was dieses Modell versteht — eine Zeile, fuer die Selbstauskunft.
	*/
	public static function model_description(): string;
}

?>
