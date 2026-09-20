<?PHP

/**
*	Wertet eine gelesene SPARQL-Struktur gegen einen qPortal-Baum aus.
*
*	Der Parser (SPARQL_Parser) macht aus dem Ausdruck Spalten und Tripel; hier werden
*	die Tripel zu Loesungen. Getrennt gehalten, weil das zwei verschiedene Fragen sind:
*	die Sprache ist ueberall dieselbe, der Baum ist diese Instanz.
*
*	Gefragt wird ueber ALLE geladenen Baeume (in_every_tree), nicht nur den, auf dem
*	der Parser steht: die Bedeutung ist instanzweit, eine Abfrage hat keinen Scope.
*	Welcher Baum einen Knoten traegt, ist Struktur - das geht eine Anwendung auf tree
*	an (__where_am_i), nicht die Abfrage.
*
*	== Was ein Tripel im Baum ist ==
*
*	Nichts wird umgewandelt — der geparste Baum IST die Tripelmenge:
*
*	  Subjekt     der Elementknoten (bezeichnet durch seinen Positionsstempel)
*	  rdf:type    sein full_URI()
*	  Praedikat   ein Attribut — die volle URI ist schon der Schluessel
*	  Objekt      der Attributwert
*
*	Das traegt nur, weil Attribute Knoten sind und attribute() die einzige Stelle ist,
*	an der sie erfasst werden (Interface_ns.php:633). Deshalb ist die Erfassung
*	lueckenlos, und deshalb darf hier ueber den Index gesucht statt gelaufen werden.
*
*	== Wie ausgewertet wird ==
*
*	Eine Loesung ist eine Belegung der Variablen. Angefangen wird mit einer leeren
*	Loesung; jedes Tripel nimmt die Menge der Loesungen entgegen und gibt eine neue
*	zurueck — es bindet (aus dem Index), verfeinert (am gebundenen Knoten) oder wirft
*	eine Loesung weg. Konjunktion ist vertauschbar, darum darf vorher umsortiert werden.
*
*	Die Reihenfolge (die halbe estimateCost der Vorlage): ein Tripel mit festem Objekt
*	schraenkt ein, eines mit Variable im Objekt zaehlt nur auf. Das Einschraenkende
*	zuerst — sonst wird erst die ganze Menge aufgezaehlt und danach weggeworfen.
*
*	== Was heute getragen wird ==
*
*	  Variablen an Subjekt und Objekt · rdf:type gegen den Knotentyp · Attribute als
*	  Praedikat, mit Variable oder festem Wert im Objekt · mehrere Tripel als UND
*	  nicht: Variablen im PRAEDIKAT, Pfade ueber Kindknoten, OPTIONAL/FILTER, Literale
*	  mit Sprach- oder Typmarke
*
*	@see classes/search_model/sparql/sparql_parser.php
*	@see anttree/funct_parser_lib.js  (SPARQLObject.execute — die Vorlage)
*/
class SPARQL_Tree_Query
{
	const RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

	private $tree;

	public function __construct(&$tree)
	{
		$this->tree = &$tree;
	}

	/**
	*	@param	array	$query	Struktur aus SPARQL_Parser::parse
	*	@return	array		Loesungen — je Loesung Variablenname => Wert
	*				(Knoten bleiben Knoten, Attributwerte sind Zeichenketten)
	*/
	public function run(array $query): array
	{
		/* Eine Praedikatvariable wuerde hier still nichts finden — collect_nodes sucht
		*  dann nach der URI "?p". Lieber sagen, dass es nicht getragen wird: ein leeres
		*  Ergebnis sieht aus wie eine Antwort. */
		foreach($query['where'] as $t)
			if(self::is_var($t['p']))
				throw new Exception('SPARQL_Tree_Query: eine Variable im PRAEDIKAT ('
				                  . $t['p'] . ') wird nicht getragen. Nenne das Praedikat als '
				                  . 'URI — die Aufzaehlung aller Praedikate eines Knotens gibt '
				                  . 'get_ns_attribute() ohne Argument.');

		$muster = $this->ordered($query['where']);
		$loesungen = array(array());

		foreach($muster as $t)
		{
			$loesungen = $this->apply($t, $loesungen);

			/* Eine leere Menge bleibt leer — der Rest der Tripel kann sie nicht
			*  wieder fuellen. Frueher Abbruch. */
			if(empty($loesungen))
				break;
		}

		return $this->project($loesungen, $query['select']);
	}

	/**
	*	Einschraenkende Tripel zuerst. Das ist die Haelfte von estimateCost, die ohne
	*	Zahlen auskommt: WAS gefragt wird, nicht WIE VIEL es sein wird.
	*/
	private function ordered(array $muster): array
	{
		usort($muster, function($a, $b)
		{
			return $this->weight($a) <=> $this->weight($b);
		});

		return $muster;
	}

	private function weight(array $t): int
	{
		if($t['p'] === self::RDF_TYPE && !self::is_var($t['o'])) return 0;  // eine Sorte
		if(!self::is_var($t['o']))                               return 1;  // ein fester Wert
		return 2;                                                           // zaehlt nur auf
	}

	/**
	*	Ein Tripel auf die Loesungsmenge anwenden.
	*/
	private function apply(array $t, array $loesungen): array
	{
		$neu = array();

		foreach($loesungen as $l)
		{
			$gebunden = self::is_var($t['s']) ? ($l[$t['s']] ?? null) : null;

			/* ⚠ Der Kante FOLGEN: eine Variable, die aus einer OBJEKT-Stelle kommt, haelt
			*  einen Attributwert - also eine Zeichenkette, keinen Knoten. Steht sie im
			*  naechsten Tripel als SUBJEKT, muss daraus erst der Knoten werden, der diese
			*  URI als Identitaet traegt. Dafuer gibt es identity_index: global ueber alle
			*  geladenen Baeume, geschluesselt ueber rdf:about (seit 2026-08-26, "Bedeutung
			*  ist global"), und node_by_identity() prueft beim Lesen nach.
			*
			*  Bis 2026-09-20 fehlte dieser Schritt. Die Bindung war dann kein Objekt, der
			*  Zweig "Subjekt noch offen" lief an und zaehlte die ganze Menge NEU auf - aus
			*  einer Verbindung wurde ein Kreuzprodukt. Gemessen am Kuehlschrank:
			*      ?p location <#fach1>                        2   richtig
			*      ?f inStorage <#fridge>                      4   richtig
			*      ?p location ?f . ?f inStorage <#fridge>    28   statt 7  (7 x 4)
			*
			*  ⚠ Loest die Zeichenkette auf keinen Knoten auf, ist die Loesung TOT. Sie darf
			*  nicht in die Aufzaehlung fallen: eine gebundene Variable ist gebunden, auch
			*  wenn nichts zu ihr passt. */
			if(!is_object($gebunden) && self::is_var($t['s']) && array_key_exists($t['s'], $l))
			{
				$wert = (string) $l[$t['s']];
				$knoten = ('' === $wert) ? null : $this->tree->node_by_identity($wert);

				if(!is_object($knoten))
					continue;

				$gebunden = $knoten;
			}

			/* Subjekt schon gebunden: der Knoten steht fest, das Tripel prueft nur. */
			if(is_object($gebunden))
			{
				foreach($this->check_bound($t, $gebunden) as $wert)
				{
					$erweitert = $l;

					if(self::is_var($t['o']))
						$erweitert[$t['o']] = $wert;

					$neu[] = $erweitert;
				}

				continue;
			}

			/* Subjekt noch offen: aus dem Index holen. */
			foreach($this->from_index($t) as $paar)
			{
				$erweitert = $l;

				if(self::is_var($t['s']))
					$erweitert[$t['s']] = $paar['node'];

				if(self::is_var($t['o']))
				{
					/* ⚠ Steht die Objektvariable SCHON in der Loesung, ist sie gebunden -
					*  dann prueft dieses Tripel sie, statt sie zu ueberschreiben. Genau das
					*  fehlte bis 2026-09-20: die alte Bindung wurde stillschweigend ersetzt,
					*  und aus der Verbindung wurde ein Kreuzprodukt.
					*
					*  ordered() stellt das Einschraenkende nach vorn; darum kommt der Fall
					*  "Variable im Objekt schon gebunden" haeufiger vor als der im Subjekt. */
					if(array_key_exists($t['o'], $l))
					{
						if(!$this->gleiche_bindung($l[$t['o']], $paar['value']))
							continue;
					}
					else
						$erweitert[$t['o']] = $paar['value'];
				}

				$neu[] = $erweitert;
			}
		}

		return $neu;
	}

	/**
	*	Sind zwei Bindungen dieselbe Sache?
	*
	*	Eine Bindung ist entweder ein KNOTEN (aus einer Subjektstelle) oder eine
	*	ZEICHENKETTE (aus einer Objektstelle, denn ein Attributwert ist Text). Beide
	*	koennen dasselbe meinen - und ob sie es tun, beantwortet identity_index:
	*	welcher Knoten traegt diese URI als rdf:about.
	*
	*	⚠ Loest die Zeichenkette auf keinen Knoten auf, sind sie NICHT gleich. Ein
	*	Verweis ins Leere ist keine Uebereinstimmung.
	*/
	private function gleiche_bindung($gebunden, $wert): bool
	{
		if(is_object($gebunden) && is_object($wert))
			return $gebunden === $wert;

		if(is_object($gebunden))
		{
			$knoten = $this->tree->node_by_identity((string) $wert);
			return is_object($knoten) && $knoten === $gebunden;
		}

		if(is_object($wert))
		{
			$knoten = $this->tree->node_by_identity((string) $gebunden);
			return is_object($knoten) && $knoten === $wert;
		}

		return (string) $gebunden === (string) $wert;
	}

	/**
	*	Das Tripel an einem feststehenden Knoten pruefen.
	*
	*	@return	array	die passenden Objektwerte (leer = das Tripel passt nicht)
	*/
	private function check_bound(array $t, $node): array
	{
		if($t['p'] === self::RDF_TYPE)
		{
			$typ = $node->full_URI();

			if(self::is_var($t['o']))            return array($typ);
			return $typ === $t['o'] ? array($typ) : array();
		}

		/* ⚠ get_ns_attribute will die VOLLE URI. Ein roher Name trifft nie, ohne
		*  Warnung — deshalb kommt hier die aufgeloeste URI des Parsers herein. */
		$wert = $node->get_ns_attribute($t['p']);

		/* ⚠ FEHLT ist false (Interface_ns.php:737), nicht null und nicht ''. Der
		*  Unterschied traegt: ein Attribut mit leerem Wert IST eine Aussage und muss
		*  binden, ein fehlendes ist keine und muss die Loesung wegwerfen. Wer hier
		*  nur auf '' prueft, laesst jeden Knoten durch, der das Praedikat gar nicht
		*  hat — die UND-Verknuepfung waere still wirkungslos. */
		if($wert === false || is_null($wert))    return array();
		if(self::is_var($t['o']))                return array($wert);

		return $wert === self::plain($t['o']) ? array($wert) : array();
	}

	/**
	*	Kandidaten aus dem Index holen, wenn das Subjekt noch offen ist.
	*
	*	@return	array	array('node' => Traeger, 'value' => Objektwert)
	*/
	private function from_index(array $t): array
	{
		$res = array();

		if($t['p'] === self::RDF_TYPE)
		{
			if(self::is_var($t['o']))
				throw new Exception('SPARQL_Tree_Query: "?s rdf:type ?typ" ohne weitere '
				                  . 'Einschraenkung waeren alle geladenen Baeume. Nenne die Sorte '
				                  . 'oder binde ?s vorher.');

			/* rdf:type ist EIN Schritt link_to_class, und der Tag ist die Aussage:
			*  full_URI() des Knotens ist die IRI seines Prototyps. Die ganze Kette
			*  (is_Node) ist nicht rdf:type - Prototyping legt Instanz-von und
			*  Unterklasse-von zusammen, RDF tut das nicht. */
			foreach($this->in_every_tree(fn() => $this->tree->collect_nodes($t['o'])) as $node)
				$res[] = array('node' => $node, 'value' => $t['o']);

			return $res;
		}

		/* Ueber die ATTRIBUTKNOTEN: sie stehen seit 23.08. selbst in der
		*  Lookup-Tabelle, der Weg zum Traeger ist getRefprev(). Damit kostet
		*  "wer hat dieses Praedikat" einen Tabellenzugriff statt eines Durchlaufs. */
		$attribute = $this->in_every_tree(
			fn() => $this->tree->collect_nodes($t['p'], null, null, null, -1, ATTRIBUTE));
		$fest      = self::is_var($t['o']) ? null : self::plain($t['o']);

		foreach($attribute as $attr)
		{
			$traeger = $attr->getRefprev();

			if(!is_object($traeger))
				continue;

			$wert = $attr->getdata();

			if(!is_null($fest) && $wert !== $fest)
				continue;

			$res[] = array('node' => $traeger, 'value' => $wert);
		}

		return $res;
	}

	/**
	*	Dieselbe baumlokale Suche in JEDEM geladenen Baum.
	*
	*	Struktur ist baumlokal, Bedeutung ist global: collect_nodes ist ein Werkzeug der
	*	Struktur (looking_index ist nach [$idx] geschluesselt), die Frage ist eine der
	*	Bedeutung. Darum steht die Baumschleife hier und nicht in collect_nodes. Der
	*	Parser steht danach wieder, wo er stand - auch wenn es wirft.
	*
	*	Ein entladener Baum liefert nichts: delete_index raeumt seine Tabellen
	*	(drop_index_of), und Slots werden nicht wiederverwendet.
	*
	*	@param	callable	$suche	die Suche im aktuellen Baum, gibt Knoten zurueck
	*	@return	array
	*/
	private function in_every_tree(callable $suche): array
	{
		$res     = array();
		$zurueck = $this->tree->cur_idx();

		try
		{
			for($i = 0; $i <= $this->tree->max_idx(); $i++)
			{
				$this->tree->change_idx($i);

				foreach($suche() as $knoten)
					$res[] = $knoten;
			}
		}
		finally
		{
			/* Ohne setNewTree steht cur_idx auf null - change_idx wuerfe darauf. */
			if(is_numeric($zurueck))
				$this->tree->change_idx($zurueck);
		}

		return $res;
	}

	/**
	*	Auf die gefragten Spalten zusammenstreichen. "*" nimmt alles, was gebunden ist.
	*/
	private function project(array $loesungen, array $select): array
	{
		if(in_array('*', $select, true) || empty($select))
			return $loesungen;

		$res = array();

		foreach($loesungen as $l)
		{
			$zeile = array();

			foreach($select as $spalte)
				$zeile[$spalte] = $l[$spalte] ?? null;

			$res[] = $zeile;
		}

		return $res;
	}

	private static function is_var(string $term): bool
	{
		return $term !== '' && $term[0] === '?';
	}

	/** Ein Literal ohne seine Anfuehrungszeichen. */
	private static function plain(string $term): string
	{
		if(strlen($term) > 1 && $term[0] === '"' && substr($term, -1) === '"')
			return substr($term, 1, -1);

		return $term;
	}
}

?>
