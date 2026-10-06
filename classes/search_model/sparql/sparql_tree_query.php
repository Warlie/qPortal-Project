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
*	  Subjekt     der Elementknoten
*	  rdf:type    EIN Schritt link_to_class (STW 09-15); ist der unbenannt, der Tag
*	  Praedikat   ein Attribut ODER ein Kindknoten (gestreiftes RDF/XML, PEDL)
*	  Objekt      Attributwert | rdf:resource | Kindknoten (PEDL-Form) | Text
*
*	== Das Register ist der Index (STW 2026-10-04) ==
*
*	Jeder Knoten entsteht als new_Instance() eines registrierten Namens - einer
*	Definition aus einem Vokabular oder, ohne Definition, eines freien Prototyps der
*	Fabrik - und traegt sich seit 099d677 bei ihm in link_to_instance ein. Element,
*	Praedikat, Attribut: alle. "Die Klasse ist kein Treffer, sondern ein Index."
*	Darum fragt ein Tripel zuerst das Register:
*
*	  ?s rdf:type K     die Instanzen von K           (gemessen: 31 RentalUnit)
*	  ?s p ?o           die Instanzen von p, Traeger = getRefprev()  (25 isTenantOf)
*
*	Bis dahin las die Abfrage ein Praedikat NUR als Attribut: die Beziehungen des
*	Immobilienexports (Kindknoten) gaben 0 Zeilen, der Kuehlschrank trug nur dank
*	"queryable". Der alte Weg ueber collect_nodes bleibt als Rueckfall, wo das
*	Register einen Namen nicht kennt oder keine lebende Instanz hat.
*
*	⚠ Eingetragen wird immer, ausgetragen nicht ueberall (removeNode ja,
*	removeRefnext allein nein): eine Instanz zaehlt nur, wenn ihr Baum geladen ist und
*	sie noch an ihrem Traeger steht. Nachgeprueft wird beim Lesen.
*
*	== Wie ausgewertet wird ==
*
*	Eine Loesung ist eine Belegung der Variablen. Angefangen wird mit einer leeren
*	Loesung; jedes Tripel nimmt die Menge der Loesungen entgegen und gibt eine neue
*	zurueck — es bindet (aus dem Index), verfeinert (am gebundenen Knoten) oder wirft
*	eine Loesung weg. Konjunktion ist vertauschbar, darum darf vorher umsortiert werden.
*
*	Die Reihenfolge (STW 2026-10-04): jeweils die KLEINSTE Menge zuerst. Nach jedem
*	Schritt wird neu gewaehlt. Ist das Subjekt eines Tripels schon gebunden, kostet es
*	einen Gang am Knoten - seine eigenen Kanten (Pfad ablaufen). Sonst kostet es die
*	Groesse seiner Sammlung aus dem Register, und die wird gegen die bisherigen
*	Bindungen geschnitten. Jede Sammlung entsteht einmal je Abfrage.
*
*	== Was heute getragen wird ==
*
*	  Variablen an Subjekt und Objekt · rdf:type · Attribute UND Kindknoten als
*	  Praedikat, mit Variable oder festem Wert im Objekt · mehrere Tripel als UND
*	  nicht: Variablen im PRAEDIKAT, OPTIONAL/FILTER, Literale mit Sprach- oder
*	  Typmarke
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

		$offen     = $query['where'];
		$loesungen = array(array());
		$this->sammlung = array();

		while(count($offen))
		{
			$t = $this->smallest($offen, $loesungen);
			$loesungen = $this->apply($t, $loesungen);

			/* Eine leere Menge bleibt leer — der Rest der Tripel kann sie nicht
			*  wieder fuellen. Frueher Abbruch. */
			if(empty($loesungen))
				break;
		}

		return $this->project($loesungen, $query['select']);
	}

	/** Die Sammlungen dieser Abfrage, je Tripel einmal gebaut (Schluessel: s|p|o). */
	private $sammlung = array();

	/**
	*	Das naechste Tripel: das mit der kleinsten Menge. Nimmt es aus $offen heraus.
	*
	*	Gebundenes Subjekt (alle Loesungen binden dieselben Variablen, die erste
	*	genuegt) = ein Gang am Knoten, Kosten 1. Sonst die Groesse der Sammlung. Bei
	*	Gleichstand die alte Regel: eine Sorte vor einem festen Wert vor dem Aufzaehlen.
	*/
	private function smallest(array &$offen, array $loesungen): array
	{
		$erste = $loesungen[0] ?? array();
		$best  = null;
		$wahl  = null;

		foreach($offen as $i => $t)
		{
			$kosten = (self::is_var($t['s']) && array_key_exists($t['s'], $erste))
			        ? 1
			        : count($this->collection($t)) + 1;

			$rang = array($kosten, $this->weight($t));

			if(is_null($best) || $rang < $best) { $best = $rang; $wahl = $i; }
		}

		$t = $offen[$wahl];
		unset($offen[$wahl]);

		return $t;
	}

	private function weight(array $t): int
	{
		if($t['p'] === self::RDF_TYPE && !self::is_var($t['o'])) return 0;  // eine Sorte
		if(!self::is_var($t['o']))                               return 1;  // ein fester Wert
		return 2;                                                           // zaehlt nur auf
	}

	/** Die Sammlung eines Tripels - einmal je Abfrage. */
	private function collection(array $t): array
	{
		$schluessel = $t['s'] . '|' . $t['p'] . '|' . $t['o'];

		if(!isset($this->sammlung[$schluessel]))
			$this->sammlung[$schluessel] = $this->from_index($t);

		return $this->sammlung[$schluessel];
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

					/* ⚠ Auch HIER kann die Objektvariable schon gebunden sein - dann prueft
					*  das Tripel sie, statt sie zu ueberschreiben. Der Fix vom 2026-09-20
					*  stand nur im Zweig "Subjekt noch offen". Seit smallest() die kleinste
					*  Menge zuerst nimmt, kommt dieser Zweig mit gebundenem Objekt vor:
					*      ?t isTenantOf ?u . ?u isUnitOf ?p . ?p propertyNo ?nr
					*  wertet propertyNo (23) und isTenantOf (25) zuerst aus - ohne gemeinsame
					*  Variable 575 Zwischenloesungen. isUnitOf kommt mit gebundenem ?u UND ?p,
					*  und das Ueberschreiben von ?p liess alle 575 stehen statt der 25
					*  (gemessen 2026-10-07 am Immobilienexport, Liegenschaft zu Mieter). */
					if(self::is_var($t['o']))
					{
						if(array_key_exists($t['o'], $l))
						{
							if(!$this->gleiche_bindung($l[$t['o']], $wert))
								continue;
						}
						else
							$erweitert[$t['o']] = $wert;
					}

					$neu[] = $erweitert;
				}

				continue;
			}

			/* Subjekt noch offen: die Sammlung des Tripels, geschnitten mit dem, was
			*  schon gebunden ist. */
			foreach($this->collection($t) as $paar)
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
			$typ = self::type_of($node);

			if(self::is_var($t['o']))            return array($typ);
			return $typ === $t['o'] ? array($typ) : array();
		}

		/* Die Kanten des Knotens selbst - Kindknoten dieses Praedikats (gestreiftes
		*  RDF/XML, PEDL). Danach das Attribut wie bisher. Dieselbe Aussage zaehlt einmal. */
		$werte = array();
		for($i = 0, $n = $node->index_max(); $i < $n; $i++)
		{
			$kind = $node->getRefnext($i);
			if(is_object($kind) && self::same_uri($kind->full_URI(), $t['p']))
				$werte[] = self::value_of($kind);
		}

		$attr = $node->get_ns_attribute($t['p']);
		if($attr !== false && !is_null($attr))
			$werte[] = $attr;

		if(count($werte))
		{
			$passend = array();
			$gesehen = array();
			foreach($werte as $w)
			{
				$k = self::key_of($w);
				if(isset($gesehen[$k])) continue;
				$gesehen[$k] = true;

				if(self::is_var($t['o']) || $this->gleiche_bindung($w, self::plain($t['o'])))
					$passend[] = $w;
			}
			return $passend;
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
		$aus_register = $this->from_register($t);

		if(count($aus_register))
			return $aus_register;

		return $this->from_tree_index($t);
	}

	/**
	*	Kandidaten aus dem REGISTER: die lebenden Instanzen des Namens.
	*
	*	rdf:type K  -> jede Instanz von K ist ein Subjekt
	*	p           -> jede Instanz von p ist eine Kante: Traeger = Subjekt, Wert = Objekt
	*
	*	Leer, wenn der Name nicht registriert ist oder keine lebende Instanz hat - dann
	*	faellt from_index auf den alten Weg zurueck.
	*/
	private function from_register(array $t): array
	{
		if($t['p'] === self::RDF_TYPE && self::is_var($t['o']))
			return array();   // from_tree_index sagt, warum das nicht getragen wird

		$name = ($t['p'] === self::RDF_TYPE) ? $t['o'] : $t['p'];
		$fest = self::is_var($t['o']) ? null : self::plain($t['o']);
		$res  = array();
		$gesehen = array();

		foreach($this->instances($name) as $inst)
		{
			if($t['p'] === self::RDF_TYPE)
			{
				$res[] = array('node' => $inst, 'value' => $t['o']);
				continue;
			}

			$traeger = $inst->getRefprev();
			$wert    = self::value_of($inst);

			if(!is_null($fest) && !$this->gleiche_bindung($wert, $fest))
				continue;

			$k = spl_object_id($traeger) . '|' . self::key_of($wert);
			if(isset($gesehen[$k])) continue;
			$gesehen[$k] = true;

			$res[] = array('node' => $traeger, 'value' => $wert);
		}

		return $res;
	}

	/** Die lebenden Instanzen eines registrierten Namens. */
	private function instances(string $uri): array
	{
		if(false === strpos($uri, '#'))
			return array();

		try
		{
			$eintrag = &$this->tree->get_Class_of_Namespace($uri);
		}
		catch(Throwable $e)
		{
			return array();
		}

		if(!is_object($eintrag))
			return array();

		$res = array();
		for($i = 0, $n = $eintrag->ManyInstance(); $i < $n; $i++)
		{
			$inst = $eintrag->linkToInstance($i);
			if($this->alive($inst))
				$res[] = $inst;
		}

		return $res;
	}

	/**
	*	Steht die Instanz noch? Ihr Baum ist geladen (ein entladener Slot hat mirror
	*	null), und der GANZE Weg bis zu seiner Wurzel steht: jeder Traeger fuehrt sein
	*	Kind noch - als Attribut oder in der Kindliste. Ein Repraesentant (rdf:about) hat
	*	keinen Traeger und zaehlt nicht.
	*
	*	⚠ Ein Schritt nach oben genuegt nicht: removeNode() traegt nur den entfernten
	*	Knoten aus, nicht seine Kinder. Die Kante eines geloeschten Subjekts steht weiter
	*	bei ihm - gemessen 2026-10-04: nach removeNode() auf dem Subjekt noch 2 liegtIn
	*	statt 1.
	*/
	private function alive($inst): bool
	{
		if(!is_object($inst)) return false;

		$idx  = $inst->get_idx();
		$root = $this->tree->mirror[$idx] ?? null;
		if(!is_object($root)) return false;

		/* ⚠ Ein Baum mit '@'-Namen ist ein SYSTEMBAUM (@registry_surface_system: der
		*  Bindungsblock des Kerns), kein Dokument. Der alte Index hatte ihn nicht, das
		*  Register kennt seine Knoten - gemessen 2026-10-04: zwei tree:tree mehr,
		*  tree_sparql_plugin rot. Ausgeschlossen wie in Turtle_handle. */
		$quelle = (string) ($this->tree->loaded_URI[$idx] ?? '');
		if($quelle !== '' && $quelle[0] === '@') return false;

		$k = $inst;
		for($stufe = 0; $stufe < 512; $stufe++)
		{
			if($k === $root) return true;

			$traeger = $k->getRefprev();
			if(!is_object($traeger)) return false;

			if($k->get_NodeType() == ATTRIBUTE)
			{
				if($traeger->get_ns_attribute_obj($k->full_URI()) !== $k) return false;
			}
			else
			{
				$gefunden = false;
				for($i = 0, $n = $traeger->index_max(); $i < $n; $i++)
					if($traeger->getRefnext($i) === $k) { $gefunden = true; break; }
				if(!$gefunden) return false;
			}

			$k = $traeger;
		}

		return false;
	}

	/**
	*	Der Objektwert einer Kante: ein Attribut traegt seinen Wert; ein Kindknoten
	*	rdf:resource, sonst sein erstes Kindelement (PEDL-Form: das Ding steht IN der
	*	Kante), sonst seinen Text.
	*/
	private static function value_of($inst)
	{
		if($inst->get_NodeType() == ATTRIBUTE)
			return $inst->getdata();

		$verweis = $inst->get_ns_attribute('http://www.w3.org/1999/02/22-rdf-syntax-ns#resource');
		if($verweis !== false && !is_null($verweis))
			return $verweis;

		for($i = 0, $n = $inst->index_max(); $i < $n; $i++)
		{
			$kind = $inst->getRefnext($i);
			if(is_object($kind)) return $kind;
		}

		$text = $inst->getdata();
		return is_scalar($text) ? (string) $text : $text;
	}

	/** Ein Vergleichsschluessel fuer einen Wert: Knoten nach Objekt, sonst der Text. */
	private static function key_of($wert): string
	{
		return is_object($wert) ? 'o' . spl_object_id($wert) : 's' . (string) $wert;
	}

	/** rdf:type: EIN Schritt link_to_class; ist der unbenannt ("none#…"), der Tag. */
	private static function type_of($node): string
	{
		$klasse = $node->linkToClass();
		if(is_object($klasse))
		{
			$typ = $klasse->full_URI();
			if(!str_starts_with($typ, 'none#') && !str_starts_with($typ, '#'))
				return $typ;
		}
		return $node->full_URI();
	}

	/** full_URI() haengt '#' auch an einen Namensraum auf '/' (dcterms). */
	private static function same_uri(string $a, string $b): bool
	{
		return $a === $b || str_replace('/#', '/', $a) === str_replace('/#', '/', $b);
	}

	/**
	*	Der alte Weg ueber collect_nodes - Rueckfall, wenn das Register nichts weiss.
	*/
	private function from_tree_index(array $t): array
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
