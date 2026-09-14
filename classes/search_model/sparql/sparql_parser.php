<?PHP

/**
*	SPARQL-Parser — der Ausdruck wird zur Tabelle, die Tabelle zur Struktur.
*
*	Portierung der Grammatik aus anttree/funct_parser_lib.js
*	(de.auster_gmbh.library.tools.SPARQLObject, dort die mealy-Anmeldungen und
*	getQueryStructure). Der Automat darunter ist \Finite\Elements\Mealy_Automat.
*
*	Zwei Schritte, sauber getrennt:
*
*	  parse()      Ausdruck  -> Struktur (base, prefixes, select, where)
*	  rows()       Ausdruck  -> die rohe Tabelle des Automaten, wie sie gelesen wurde
*
*	Der Parser wertet NICHT aus. Was mit den Tripeln geschieht — gegen einen Baum,
*	gegen ein Fuseki — entscheidet das Modell, nicht die Sprache. Der Parser ist
*	darum ohne Baum und ohne Verbindung brauchbar und einzeln pruefbar.
*
*	== Der Zustandsgraph (aus der Vorlage) ==
*
*	  start --BASE--> base --' '--> pre1 --'<'--> uri --'>'--> start        next()
*	  start --PREFIX-> prefix -> prename --':'--> pre1 --'<'--> uri --'>'-> start
*	  start --SELECT-> select -> col --' ',','--> col                       next()
*	                            col --WHERE--> where --'{'--> space_sub     deeper()
*	  space_sub -> sub --' '-> space_pre -> pre --' '-> space_obj -> obj -> space
*	  space --'.'--> space_sub                                              next()
*	  space --'}'--> space                                                  shallow()
*
*	Die Namen selbst buchstabiert der Graph nicht: sub/pre/obj/uri/prename/col sind
*	setStringNode — was dort keine Kante findet, faellt in das Feld. Nur das ERSTE
*	Zeichen eines Namens steht in der Kante (darum die Buchstabenliste), der Rest
*	sammelt sich von selbst.
*
*	== Was die Vorlage traegt und was nicht ==
*
*	  traegt:  BASE, PREFIX, SELECT mit mehreren Spalten (Leerraum oder Komma), WHERE
*	           mit mehreren Tripeln, Variablen (?x), abgekuerzte Namen (rdf:type),
*	           Zeichenketten als Objekt ("Wert")
*	  traegt nicht: volle URIs in spitzen Klammern INNERHALB von WHERE, verschachtelte
*	           Klammern, Semikolon und Komma als Tripel-Kurzform, FILTER/OPTIONAL/UNION,
*	           "a" als Kurzform fuer rdf:type, Sprach- und Typmarken am Literal,
*	           kleingeschriebene Schluesselwoerter (select statt SELECT)
*
*	Das ist bewusst so uebernommen: erst die Vorlage, dann der Ausbau. Was fehlt, faellt
*	beim Lesen mit einer Meldung auf, nicht still — sie nennt Zustand, Zeichen und Stelle.
*
*	#deep steht in jeder Zeile mit, ist heute aber immer 1: die Vorlage kennt nur die eine
*	Klammer um das Tripelmuster. Das Feld ist der Platz, an dem eine verschachtelte
*	Klammer spaeter ankommt, ohne dass sich die Form der Tabelle aendert.
*
*	== Eine Abweichung von der Vorlage ==
*
*	Wo die Vorlage nur das LEERZEICHEN kennt (nach BASE, nach PREFIX, nach WHERE, zwischen
*	Subjekt, Praedikat und Objekt), steht hier der volle Leerraum aus self::SPACE. Sonst
*	wird eine ueber mehrere Zeilen geschriebene Anfrage — die uebliche Form — schon am
*	Umbruch vor "{" abgewiesen. Die Vorlage kam damit durch, weil ihre Beispiele
*	einzeilig waren.
*
*	@see anttree/funct_parser_lib.js
*	@see classes/finite_state_machine/class_Mealy.php
*/
class SPARQL_Parser
{
	/** Leerraum, wie ihn die Vorlage aufzaehlt. */
	const SPACE = array("\n", "\r", "\t", "\f", ' ');

	/** Womit ein Name anfangen darf — nur das erste Zeichen, den Rest sammelt setStringNode. */
	const NAME_START = '?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';

	private $mealy;

	public function __construct()
	{
		if(!class_exists('\Finite\Elements\Mealy_Automat'))
			throw new Exception('SPARQL_Parser: classes/finite_state_machine/class_Mealy.php '
			                  . 'ist nicht geladen.');

		$this->mealy = new \Finite\Elements\Mealy_Automat();
		$this->build_grammar();
	}

	/**
	*	Die Grammatik, Kante fuer Kante wie in der Vorlage.
	*/
	private function build_grammar(): void
	{
		$m    = $this->mealy;
		$name = str_split(self::NAME_START);

		$m->setNodes('start', 'base', 'pre1', 'uri');
		$m->setNodes('prefix', 'pre2', 'prename');
		$m->setNodes('select', 'col', 'where', 'space', 'space_sub', 'space_pre',
		             'space_obj', 'return', 'sub', 'pre', 'obj',
		             'sub_txt', 'pre_txt', 'obj_txt');
		$m->setNodes('sub_uri', 'pre_uri', 'obj_uri');

		$m->setEdge('start', 'start', self::SPACE, '');

		/* BASE <uri> */
		$m->setEdge('start', 'base',   'BASE', 'section(base)');
		$m->setEdge('base',  'pre1',   self::SPACE, '');
		$m->setEdge('pre1',  'pre1',   self::SPACE, '');

		/* PREFIX name: <uri> */
		$m->setEdge('start',   'prefix',  'PREFIX', 'section(prefix)');
		$m->setEdge('prefix',  'prename', self::SPACE, '');
		$m->setEdge('prename', 'prename', self::SPACE, '');
		$m->setStringNode('prename', 'name');
		$m->setEdge('prename', 'pre1', ':', '');

		$m->setEdge('pre1', 'uri', '<', '');
		$m->setStringNode('uri', 'uri');
		$m->setEdge('uri', 'start', '>', 'next()');

		/* SELECT ?a ?b */
		$m->setEdge('start',  'select', 'SELECT', 'section(select)');
		$m->setEdge('select', 'col',    self::SPACE, '');
		$m->setStringNode('col', 'col');
		$m->setEdge('col', 'col', array_merge(self::SPACE, array(',')), 'next()');

		/* WHERE { s p o . s p o } */
		$m->setEdge('col',   'where',     'WHERE', 'section(where)');
		$m->setEdge('where', 'where',     self::SPACE, '');
		$m->setEdge('where', 'space_sub', '{', 'deeper()');

		$m->setEdge('space_sub', 'space_sub', self::SPACE, '');
		$m->setEdge('space_sub', 'sub',       $name, 'subject');
		$m->setEdge('space_sub', 'space',     '}', 'shallow()');
		$m->setStringNode('sub', 'subject');

		$m->setEdge('sub',       'space_pre', self::SPACE, '');
		$m->setEdge('space_pre', 'space_pre', self::SPACE, '');
		$m->setEdge('space_pre', 'pre',       $name, 'predicate');
		$m->setStringNode('pre', 'predicate');

		$m->setEdge('pre',       'space_obj', self::SPACE, '');
		$m->setEdge('space_obj', 'space_obj', self::SPACE, '');
		$m->setEdge('space_obj', 'obj',       $name, 'object');
		$m->setStringNode('obj', 'object');

		$m->setEdge('space', 'space', self::SPACE, '');

		/* Volle URI in spitzen Klammern, an jeder der drei Stellen. Sie geht in ein
		*  EIGENES Feld, wie das Literal in object_txt: was hier steht, ist schon
		*  aufgeloest und darf nicht noch einmal durch die Praefixtabelle. Sonst
		*  wuerde <mailto:x> als Praefix "mailto" gelesen. */
		$m->setEdge('space_sub', 'sub_uri', '<', '');
		$m->setStringNode('sub_uri', 'subject_uri');
		$m->setEdge('sub_uri', 'space_pre', '>', '');

		$m->setEdge('space_pre', 'pre_uri', '<', '');
		$m->setStringNode('pre_uri', 'predicate_uri');
		$m->setEdge('pre_uri', 'space_obj', '>', '');

		$m->setEdge('space_obj', 'obj_uri', '<', '');
		$m->setStringNode('obj_uri', 'object_uri');
		$m->setEdge('obj_uri', 'space', '>', '');

		/* Objekt als Zeichenkette */
		$m->setEdge('space_obj', 'obj_txt', '"', '');
		$m->setEdge('obj_txt',   'space',   '"', '');
		$m->setStringNode('obj_txt', 'object_txt');

		$m->setEdge('obj', 'space', self::SPACE, '');

		$m->setEdge('space', 'space_sub', '.', 'next()');
		$m->setEdge('space', 'space',     '}', 'shallow()');
	}

	/**
	*	Liest den Ausdruck und gibt die Struktur zurueck.
	*
	*	@param	string	$statement	SPARQL-Ausdruck
	*	@return	array	array('base' => string, 'prefixes' => array(name => uri),
	*			      'select' => array(spalten), 'where' => array(tripel))
	*			Ein Tripel ist array('s','p','o','deep'); Namen sind ueber base
	*			und prefixes zu vollen URIs aufgeloest, Literale stehen in
	*			Anfuehrungszeichen, Variablen behalten ihr "?".
	*	@throws	Exception	wenn der Ausdruck nicht angenommen wird
	*/
	public function parse(string $statement): array
	{
		return $this->structure_of($this->rows($statement));
	}

	/**
	*	Die rohe Tabelle, wie der Automat sie gelesen hat — je Zeile die Felder plus
	*	#section und #deep. Fuer die Diagnose: hier sieht man, WAS gelesen wurde,
	*	bevor irgendetwas aufgeloest ist.
	*/
	public function rows(string $statement): array
	{
		$this->mealy->checkString($statement);

		return $this->mealy->getResult();
	}

	/** Der Zustandsgraph, wie er angemeldet wurde. */
	public function structure(): array
	{
		return $this->mealy->structure();
	}

	/** Der Weg durch die letzte Eingabe, Schritt fuer Schritt. */
	public function debug_rows(): array
	{
		return $this->mealy->debug_rows();
	}

	/**
	*	Tabelle -> Struktur. Portierung von getQueryStructure.
	*/
	private function structure_of(array $rows): array
	{
		$result = array('base' => '', 'prefixes' => array(),
		                'select' => array(), 'where' => array());

		foreach($rows as $row)
		{
			switch($row['#section'] ?? '')
			{
				case 'base' :
					if(isset($row['uri']))
						$result['base'] = $row['uri'];
					break;

				case 'prefix' :
					if(isset($row['name'], $row['uri']))
						$result['prefixes'][$row['name']] = $row['uri'];
					break;

				case 'select' :
					if(isset($row['col']) && $row['col'] !== '')
						$result['select'][] = $row['col'];
					break;

				case 'where' :
					/* Eine Zeile ohne Subjekt ist keine Aussage, sondern die Spur einer
					*  Klammer (deeper/shallow schreiben #section und #deep voraus).
					*  Das Subjekt steht entweder als Name da oder als <volle URI>. */
					$subjekt = isset($row['subject_uri'])
					           ? '<' . $row['subject_uri'] . '>'
					           : ($row['subject'] ?? null);

					if(is_null($subjekt))
						break;

					$result['where'][] = array(
						's'    => $subjekt,
						'p'    => isset($row['predicate_uri'])
						          ? '<' . $row['predicate_uri'] . '>'
						          : ($row['predicate'] ?? ''),
						/* Reihenfolge: Literal, dann volle URI, dann Name. */
						'o'    => isset($row['object_txt'])
						          ? '"' . $row['object_txt'] . '"'
						          : (isset($row['object_uri'])
						             ? '<' . $row['object_uri'] . '>'
						             : ($row['object'] ?? '')),
						'deep' => $row['#deep'] ?? 0);
					break;
			}
		}

		foreach($result['where'] as &$t)
		{
			$t['s'] = self::resolve($t['s'], $result['base'], $result['prefixes']);
			$t['p'] = self::resolve($t['p'], $result['base'], $result['prefixes']);

			if($t['o'] === '' || $t['o'][0] !== '"')
				$t['o'] = self::resolve($t['o'], $result['base'], $result['prefixes']);
		}
		unset($t);

		return $result;
	}

	/**
	*	Abgekuerzter Name -> volle URI. Eine Variable bleibt eine Variable.
	*
	*	rdf:type mit bekanntem Praefix wird zusammengesetzt; ein Name ohne Doppelpunkt
	*	haengt an der BASE (mit '#', wie die Vorlage es tut — qPortal setzt seine
	*	vollen URIs ueber full_URI() genauso zusammen).
	*
	*	Drei Formen kommen fertig an und gehen unangetastet durch:
	*	  <voll>        in spitzen Klammern, vom Parser als eigenes Feld gelesen
	*	  schema://…    blank hingeschriebene volle URI
	*	  ?name         eine Variable
	*
	*	⚠ Ein UNBEKANNTES Praefix wirft. Bis 2026-09-14 blieb es unangetastet — die
	*	Abfrage lief dann gegen die Zeichenkette "tree:final", die kein Knoten je
	*	traegt, und gab still NULL Zeilen zurueck. Ein vergessenes PREFIX sah damit
	*	aus wie ein leeres Ergebnis. Falsch-negativ verliert still einen Treffer,
	*	darum ist das hier ein Fehler und keine Nachricht im Ergebnis.
	*/
	private static function resolve(string $term, string $base, array $prefixes): string
	{
		if($term === '' || $term[0] === '?')
			return $term;

		/* <volle URI> - schon aufgeloest, die Klammern fallen weg. */
		if($term[0] === '<' && substr($term, -1) === '>')
			return substr($term, 1, -1);

		$colon = strpos($term, ':');

		if($colon !== false)
		{
			$ns = substr($term, 0, $colon);

			if(isset($prefixes[$ns]))
				return $prefixes[$ns] . substr($term, $colon + 1);

			/* Eine blanke volle URI traegt ihr Schema vor dem Doppelpunkt. */
			if(false !== strpos($term, '://'))
				return $term;

			throw new Exception('SPARQL_Parser: unbekanntes Praefix "' . $ns . ':" in "'
			                  . $term . '". Bekannt: '
			                  . (count($prefixes) ? implode(', ', array_map(fn($n) => $n . ':',
			                                                array_keys($prefixes)))
			                                      : 'keines - es steht kein PREFIX im Ausdruck')
			                  . '. Eine volle URI geht auch in spitzen Klammern.');
		}
		else if($base !== '')
			return $base . '#' . $term;

		return $term;
	}
}

?>
