<?PHP


try {
    $reg = $content->getRegObj();
    
    $reg->_useGeneral();

    $reg->addNamespaceDescription(
		'Der allgemeine Namensraum. Seine Befehle sind die Primitives des Systems: sie'
		. ' werden vor jedem namensraumeigenen Verhalten geprueft und kommen deshalb auf'
		. ' jedem Knoten an, unabhaengig von dessen Typ. Bearbeiten des Baums, Navigieren'
		. ' ueber Positionsstempel, Selbstauskunft und Fehlersuche liegen hier.');

    $reg->addLocalNameDescription(
		'Leerer lokaler Name - kein Knotentyp, sondern die Auffangebene fuer alle Knoten.');


    $reg->__redirect_node= function($node, $obj, $event)
    {
    		$node->send_messages($event->get_Command(0,1),$obj) ;
			return true;
    };
    
$reg->addLog(function($node, $obj, $event){return "__redirect_node in " . $node->full_URI();}, 5);
    /*
    	if($com_elemnet->matchesCommand('__redirect_node'))
		{
		$logger_class->setAssert('  Redirect was send to "' . $this->full_URI() . '"(Interface_node:event_message_check)' ,5);
			//$com_elemnet = $this->parseCommand($type);
			//echo $com_elemnet->get_Command(0,1) . ' ' . get_Class($obj->get_Node()) . ' ' . get_Class($this) . ' <br>';

			if(is_null($com_elemnet->get_Command(0,1)))throw new Exception("Null detected: " . $com_elemnet->get_Insert());
			

			$this->send_messages($com_elemnet->get_Command(0,1),$obj) ;
			return true;
			
		}
    */
    $reg->addDescription(
		'Reicht das Ereignis unveraendert an das Value-Kommando weiter (send_messages).'
		. ' Keine Attribute - der Inhalt steckt in Value.');

    //__find_node : finds aspecific node and continues with next command
    $reg->__find_node= function($node, $obj, $event) 
    { 
    	$structur = $event->get_Result_Array();
    	
    	$json = json_decode($structur['Command']['Attribute']['json'], true);

    		$name = null;
			$attribute = null;
			$value = $structur['Command']['Value'];
			if(array_key_exists('attribute', $json))$attribute =  $json['attribute'];
			if(array_key_exists('name', $json))$name =  $json['name'];

			 $node->get_parser()->flash_result();
			// var_dump("name",$name, "attrib", $attribute);
			if($node->get_parser()->seek_node($name,$attribute) && (count($node->get_parser()->get_result())>0))
			{

			$obj->set_node($node);

		foreach( $node->get_parser()->get_result() as $value_obj){

			
			$value_obj->hold_messages($value,$obj) ;
		}

		 	$node->get_parser()->flash_result();

			}
			else
			throw new NotExistingBranchException( " $name does not exist");

			return true;
    };
    
    $reg->addLog(function($node, $obj, $event){return "__find_node in " . $node->full_URI();}, 5);

    $reg->addDescription(
		'Sucht Knoten nach Namens-URI und/oder Attributen und feuert das Value-Kommando auf'
		. ' jedem Treffer. Gesucht wird in dem Baum, in dem der Befehl steht.',
		[
			'json' => ['description' => 'Suchmuster als JSON-String:'
			                          . ' {"name":"<Knoten-URI>","attribute":{"<Attribut-URI>":"<Wert>"}}.'
			                          . ' Beide Felder sind einzeln optional, ohne Treffer wird'
			                          . ' NotExistingBranchException geworfen.',
			           'required'    => true]
		]);

    $reg->__add_node = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$json = json_decode($structur['Command']['Attribute']['json'], true);

			$name    = $json['name'];
			$attrib  = $json['attribute'] ?? [];
			$text    = $json['text'] ?? null;

			// Resolve namespace and local name
			$posinStr = strpos($name, '#');
			if (false === $posinStr)
			{
				$new_node = $node->get_parser()->get_Object_of_Namespace($obj->get_requester()->get_Main_NS() . '#' . $name);
				$new_node->name = $name;
				$prefix_full  = $obj->get_requester()->get_Main_NS();
				$postfix_full = $name;
			}
			else
			{
				$prefix_full  = substr($name, 0, $posinStr);
				$postfix_full = substr($name, $posinStr + 1);
				$new_node = $node->get_parser()->get_Object_of_Namespace($name);
				$prefix = $node->get_parser()->get_Prefix($prefix_full, $node->get_idx());
				$new_node->name = (!is_null($prefix) && strlen($prefix) > 0)
					? $prefix . ':' . $postfix_full
					: $postfix_full;
			}

			$new_node->type      = $postfix_full;
			$new_node->namespace = $prefix_full;
			$new_node->set_idx($node->get_idx());

			// Add attributes
			foreach ($attrib as $key => $value)
			{
				$ns_qname = strpos($key, '#');
				if (false === $ns_qname)
				{
					$attrib_obj = $node->get_parser()->get_Object_of_Namespace($obj->get_requester()->get_Main_NS() . '#' . $key);
					$attrib_obj->setdata($value, 0);
					$new_node->attribute($key, $attrib_obj);
				}
				else
				{
					$attrib_obj = $node->get_parser()->get_Object_of_Namespace($key);
					$att_prefix  = $node->get_parser()->get_Prefix(substr($key, 0, $ns_qname), $node->get_idx());
					$att_postfix = substr($key, $ns_qname + 1);
					$attrib_obj->namespace = substr($key, 0, $ns_qname);
					$attrib_obj->type      = $att_postfix;
					$attrib_obj->setdata($value, 0);
					$attrib_obj->name = (strlen($att_prefix) > 0)
						? $att_prefix . ':' . $att_postfix
						: $att_postfix;
					$new_node->attribute($attrib_obj->name, $attrib_obj);
				}
				unset($attrib_obj);
			}

			// Register and attach
			$node->get_parser()->set_new_index($new_node);
			$cur_element = $node;
			$cur_element->setRefnext($new_node);
			$new_node->setRefprev($cur_element);

			/* Der ContentGenerator wird sonst nur beim Parsen gesetzt
			*  (xml_multitree_ns.php). Ohne ihn findet der neue Knoten die
			*  Befehlsregistry nicht und kann keine Nachricht annehmen - er waere
			*  zwar im Baum, aber nicht ansprechbar. Deshalb vom Elternknoten erben. */
			$cg = $node->get_contentGen();
			if (!is_null($cg))
				$new_node->set_contentGen($cg);

			// Optional text content
			if (!is_null($text))
				$new_node->setdata($text, 0);

			/* Value feuert auf dem NEUEN Knoten, nicht auf dem Elternknoten. Ohne
			*  Value bleibt alles wie bisher; mit Value laesst sich der frisch
			*  angelegte Knoten im selben Schritt weiterbehandeln - sonst kommt man
			*  gar nicht an ihn heran, weil ein zweiter Aufruf den Baum neu laedt.
			*  Der Ereigniskontext traegt dabei seinen Positionsstempel, damit die
			*  Adresse nicht nachtraeglich geholt werden muss (relativer Modus, wie
			*  __position_stamp ihn ohne Attribut bildet). */
			$value = $structur['Command']['Value'];
			if (!empty($value))
			{
				$path  = '';
				$hash  = $new_node->position_hash_map($path);
				$stamp = sprintf('%04d', $hash) . '.' . $new_node->get_idx() . $path;

				$new_obj = new EventObject($obj->get_request(), $obj->get_requester(), $stamp);
				$new_node->hold_messages($value, $new_obj);
			}

			return true;
		};

	$reg->addLog(function($node, $obj, $event)
		{
			$json = json_decode($event->get_Result_Array()['Command']['Attribute']['json'], true);
			return '__add_node: ' . ($json['name'] ?? '?') . ' an ' . $node->full_URI();
		}, 5);

	$reg->addDescription(
		'Erzeugt einen neuen Knoten und haengt ihn als Kind an den aktuellen. Namensraum und'
		. ' Praefix werden aus dem Namen abgeleitet; ohne # gilt der Hauptnamensraum des Aufrufers.'
		. ' Value wird auf dem neuen Knoten gefeuert - so laesst er sich im selben Schritt'
		. ' anlegen und benutzen, etwa um seinen Positionsstempel zu holen.',
		[
			'json' => ['description' => '{"name":"<ns#local | local>",'
			                          . '"attribute":{"<name>":"<wert>"},"text":"<optional>"}',
			           'required'    => true]
		]);

    // adds get_node to
    $reg->__add_in_object= function($node, $obj, $event)
		{
			if(is_Object($myNode = &$obj->get_node()))
			{
				$myNode->addToOutListener($node);
			}
			
			
			return true;
			
		};

    $reg->addLog(function($node, $obj, $event){return "__add_in_object:" . $node->full_URI() . " added into " . $obj->get_node()->full_URI();}, 5);

	$reg->addDescription(
		'Haengt den aktuellen Knoten in die Zuhoererliste des Ereignisknotens (addToOutListener).'
		. ' Damit verdrahtet das object-Element seine remote-Knoten. Keine Attribute.');


    $reg->__give_log= function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();

			/* Wer ein Log will, bekommt eins - auch wenn __give_log nicht vorn stand;
			*  dann fehlen nur die Zeilen davor (index.php schaut nur vorn nach). */
			Logger::$collect = true;

			/* Mit level: ein eigener Zuhoerer fuer die Dauer des Value, unabhaengig vom
			*  globalen Level, gekappt auf [log] listen_max. Ausgegeben wird dann SEIN
			*  Array statt des globalen Logs. */
			$level = $structur['Command']['Attribute']['level'] ?? null;
			$eigen = !is_null($level) && '' !== trim((string) $level);
			if($eigen)
			{
				Logger::register('__give_log', $level);
				Logger::$giveLogName = '__give_log';
			}

			$node->get_contentGen()->switchOutput();

			$value = $structur['Command']['Value'];

			$node->hold_messages($value,$obj) ;

			if($eigen)
				Logger::silence('__give_log');

			return true;

		};

	$reg->addDescription(
		'Schaltet die Ausgabe auf das Log um und feuert dann Value. Die Antwort des Aufrufs ist'
		. ' danach der Loginhalt (aus dem Speicher) statt des Ausgabedokuments - so werden die'
		. ' Spuren der inneren Kommandos sichtbar. Vorn im Rumpf gestellt, erfasst es auch die'
		. ' Zeilen vor den Befehlen; sonst nur, was nach ihm kommt.',
		[
			'level' => ['description' => 'Eigenes Level fuer die Dauer des Value, unabhaengig vom'
			                           . ' globalen. Ausgegeben wird dann nur, was dabei entsteht.'
			                           . ' Gekappt auf [log] listen_max.',
			            'required'    => false]
		]);

    /* __to_owner : die Antwort geht an den Fragenden.
    *
    *  Der Befehl kennt den Fragenden NICHT. Er reicht den Wert an den Owner und
    *  faellt damit auf eine einzige Handlung zusammen — moeglich, seit der
    *  ContentGenerator selbst ein setdata() hat (2026-09-05, STW) und einen
    *  Aufrufer von aussen genauso bedient wie ein Knoten den naechsten.
    *
    *  Wie die Antwort dann AUSSIEHT, entscheidet der Empfaenger, nicht dieser
    *  Befehl: ein Knoten legt sie in seinen Textbereich, der ContentGenerator
    *  sammelt sie und gibt sie aus. Ein Kommando, das MIME-Typen kennt, waere
    *  an der falschen Stelle klug.
    */
    $reg->__to_owner = function($node, $obj, $event)
		{
			global $logger_class;

			$structur = $event->get_Result_Array();

			/* Erst erwerben: haengt ein Value dran, wird es gefeuert. */
			if(isset($structur['Command']['Value']))
				$node->hold_messages($structur['Command']['Value'], $obj);

			/* Der Wert ist der Knoten, auf dem der Befehl STEHT — dieselbe Quelle,
			*  aus der __get_data liest.
			*  ⚠ Nicht $obj->get_node(): dort steht zwar die Ablage von <result>
			*  (tree_result.php), aber eben auch, was ein vorheriger Befehl liegen
			*  gelassen hat. Gemessen: nach __find_node auf einen final-Knoten meldete
			*  get_node() weiterhin den indextree. */
			$eigner = $obj->get_owner();

			if(!is_object($eigner) || !method_exists($eigner, 'setdata'))
			{
				$logger_class->setAssert('__to_owner: der Owner kann nichts aufnehmen ('
					. (is_object($eigner) ? get_class($eigner) : gettype($eigner))
					. ') (behavior/std.php:__to_owner)', 0);

				return false;
			}

			/* Der Wert: liegt im Ereignis ein gewoehnliches Datum (Zeichenkette, Zahl,
			*  Array), dann DAS. Das Ereignis ist der Zwischenspeicher zwischen zwei
			*  Befehlen (STW) - __set_data liest daraus, __get_attribute mit Value und
			*  __where_am_i legen hinein. Sonst wie bisher der Knoten, auf dem der Befehl
			*  steht. Ein OBJEKT im Kontext zaehlt nicht: im Baumlauf setzt tree_tree dort
			*  den tree-Knoten ab (set_context), und der ist keine Antwort. Oben im
			*  Intern-Aufruf ist der Kontext null. */
			$kontext = $obj->get_context();
			$wert    = (!is_null($kontext) && !is_object($kontext)) ? $kontext : $node;

			$eigner->setdata($wert, 0, false, false);

			$logger_class->setAssert('__to_owner: Wert an "'
				. (method_exists($eigner, 'full_URI') ? $eigner->full_URI() : get_class($eigner))
				. '" uebergeben (behavior/std.php:__to_owner)', 5);

			return true;
		};

	$reg->addDescription(
		'Reicht ein erworbenes Ergebnis an den Fragenden weiter. Haengt ein Value daran, wird es'
		. ' zuerst gefeuert. Der Wert ist, was im Ereignis liegt, wenn es ein gewoehnliches Datum ist (so legen es __get_attribute und __where_am_i ab), sonst der Knoten, auf dem der Befehl steht; er geht an den'
		. ' Owner des Ereignisses - im Baum ein Knoten, von aussen der ContentGenerator, in'
		. ' beiden Faellen derselbe Aufruf. Wie die Antwort aussieht, entscheidet der Empfaenger.'
		. ' Keine Attribute.');

    /* __info_ns : welche Namensraeume kennt die Befehlsregistry.
    *  Ein Skript laeuft nur auf start - __info_ns und __info sind Aspekte daneben
    *  und antworten selbst, statt ein Dokument zu rendern.
    */
    $reg->__info_ns = function($node, $obj, $event)
		{
			$reg_obj = $node->get_contentGen()->getRegObj();

			$node->get_contentGen()->setResponse(json_encode(
				["namespaces" => $reg_obj->listNamespaces()],
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			return true;
		};

	$reg->addLog(function($node, $obj, $event){return "__info_ns in " . $node->full_URI();}, 5);

	$reg->addDescription(
		'Listet die Namensraeume der Befehlsregistry mit ihren lokalen Namen und der Anzahl'
		. ' der dort registrierten Befehle. Einstieg in die Selbstauskunft: erst __info_ns,'
		. ' dann __info je Namensraum.');

    /* __info : die Befehle eines Namensraums mit ihrer Beschreibung */
    $reg->__info = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			/* Attribute stehen im Bestand an zwei Stellen - in Command (so parst der
			*  Automat) und daneben (so schickt es der Dispatcher an start). Beide lesen,
			*  Command gewinnt. */
			$attrib = ($structur['Command']['Attribute'] ?? []) + ($structur['Attribute'] ?? []);

			$ns = $attrib['ns'] ?? '';
			$ln = $attrib['ln'] ?? null;

			$reg_obj = $node->get_contentGen()->getRegObj();

			try {
				$commands = $reg_obj->describeNamespace($ns, $ln);
			}
			catch (RegistryNotFoundException $e) {
				if(!headers_sent()) http_response_code(400);
				$node->get_contentGen()->setResponse(json_encode(
					["error" => $e->getMessage(), "namespace" => $ns, "localName" => $ln],
					JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				return true;
			}

			$node->get_contentGen()->setResponse(json_encode(
				["namespace" => $ns, "localName" => $ln, "commands" => $commands],
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

			return true;
		};

	$reg->addLog(function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$attrib = ($structur['Command']['Attribute'] ?? []) + ($structur['Attribute'] ?? []);
			return "__info for namespace '" . ($attrib['ns'] ?? '') . "'";
		}, 5);

	$reg->addDescription(
		'Liefert die Befehle eines Namensraums mit Beschreibung und Parametern.'
		. ' Befehle ohne hinterlegte Beschreibung erscheinen mit description=null -'
		. ' eine Luecke soll sichtbar sein.',
		[
			'ns' => ['description' => 'Namensraum, leerer String = die allgemeinen Primitives.'
			                        . ' Namen liefert __info_ns.',
			         'required'    => false],
			'ln' => ['description' => 'Lokaler Name innerhalb des Namensraums. Weglassen = alle.',
			         'required'    => false]
		]);

    $reg->__save_back = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$attrib   = $structur['Command']['Attribute'];
			$format   = $attrib['format'] ?? '';
			$file     = $attrib['file']   ?? false;
			$node->get_parser()->save_file($format, false, $file ?: false);
			return true;
		};
	$reg->addLog(fn($node, $obj, $event) => '__save_back: ' . $node->get_parser()->loaded_URI[$node->get_parser()->idx], 3);
	/* Schreiben ist die magische Stufe: ab da raeumt man sich Stufen selbst weg.
	*  STW: "muss einfach eine 10 sein und beliebig schreiben koennen." */
	$reg->addSecurity(10);

	$reg->addDescription(
		'Schreibt das Dokument des aktuellen Parsers zurueck auf die Platte. Schreibender Befehl.',
		[
			'format' => ['description' => 'Zielformat; leer = das beim Laden erkannte.',
			             'required'    => false],
			'file'   => ['description' => 'Zielpfad; weglassen = der urspruenglich geladene Pfad.',
			             'required'    => false]
		]);

    $reg->__set_data = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$value = $structur['Value'];
			
			$text = $obj->get_context();
			$position = $event->get_Command(0,1);

			if(is_array($value))
			{
				$text = $value['Text'];
				$position = $value['Position'];

			}
				
			$node->setdata($text, $position, alter_sensity: false);
			return true;
		};

	$reg->addLog(function($node, $obj, $event){return "__set_data:" . $obj->get_context(). " was add to " . $node->full_URI();}, 5);

	$reg->addDescription(
		'Setzt den Datenteil des Knotens auf den Ereigniskontext. Alternativ traegt Value das'
		. ' Paar {"Text":"...","Position":0} und bestimmt Wert und Stelle selbst. Schreibender'
		. ' Befehl, keine Attribute. Achtung: dieser Befehl liest Value NEBEN Command, nicht darin.');

		
    $reg->__insert_data = function($node, $obj, $event)
		{
			$node->setdata($obj->get_context(), $event->get_Command(0,1), true, false);
			return true;
		};

	$reg->addDescription(
		'Haengt den Ereigniskontext an den Datenteil des Knotens an, statt ihn zu ersetzen.'
		. ' Die Stelle steht in Value. Schreibender Befehl, keine Attribute.');

    $reg->__get_data = function($node, $obj, $event)
		{
			global $logger_class;
			if(!is_null($tmp = &$node->getdata($event->get_Command(0,1))))
			{
				$logger_class->setAssert($obj->get_requester()->full_URI() . " gets $tmp to its datapart " ,5);
				if(is_Object($tmp))
				{
					$obj->get_requester()->setdata($tmp, 0, false, false);
				}
				elseif(strlen($tmp) > 0)
				{
					$booh = $tmp;
					$obj->get_requester()->setdata($booh, 0, false, false);
				}
			}
			$logger_class->setAssert('__get_data of requester "' . $obj->get_requester()->full_URI() . '" was send to "' . $node->full_URI() . '" context is "' . $tmp . '"(std.php:__get_data)' ,5);
			return true;
		};

	$reg->addDescription(
		'Kopiert den Datenteil dieses Knotens in den Datenteil des aufrufenden Knotens.'
		. ' Die Stelle steht in Value. Das ist der Rueckgabeweg zwischen zwei Knoten.'
		. ' Keine Attribute.');

    $reg->__set_namespace = function($node, $obj, $event)
		{
			if(!is_null($tmp = &$node->getdata($event->get_Command(0,1))))
			{
				$node->namespace = $tmp;
			}
			return true;
		};

	$reg->addDescription(
		'Setzt den Namensraum des Knotens auf die Zeichenkette, die in seinem eigenen Datenteil'
		. ' steht. Die Stelle steht in Value. Schreibender Befehl, keine Attribute.');

    $reg->__position_stamp = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$mode     = $structur['Attribute']['mode'] ?? 'relative';
			$parser   = $node->get_parser();
			$path     = '';
			$hash     = $node->position_hash_map($path);

			switch ($mode)
			{
				case 'internal':
					$node_idx = $node->get_idx();
					if ($node_idx == $parser->idx)           $idx_part = 'me';
					elseif ($node_idx == $parser->idx - 1)   $idx_part = 'prev';
					else                                      $idx_part = $node_idx;
					break;
				case 'absolute':
					$idx_part = '[' . $parser->loaded_URI[$node->get_idx()] . ']';
					break;
				case 'external':
					$filepath  = $parser->loaded_URI[$node->get_idx()];
					$iv        = random_bytes(openssl_cipher_iv_length(SECURITY_CIPHER));
					$encrypted = openssl_encrypt($filepath, SECURITY_CIPHER, hex2bin(SECURITY_STAMP_KEY), OPENSSL_RAW_DATA, $iv);
					$idx_part  = '[' . base64_encode($iv . $encrypted) . ']';
					break;
				default: // relative
					$idx_part = $node->get_idx();
					break;
			}

			$stamp = sprintf('%04d', $hash) . '.' . $idx_part . $path;
			$value = $structur['Command']['Value'];
			if (!empty($value))
			{
				$new_obj = new EventObject($obj->get_request(), $obj->get_requester(), $stamp);
				$node->hold_messages($value, $new_obj);
			}
			return true;
		};

    $reg->addLog(fn($node, $obj, $event) => '__position_stamp: ' . (function() use ($node, $event) {
		$mode   = $event->get_Result_Array()['Attribute']['mode'] ?? 'relative';
		$parser = $node->get_parser();
		$p = ''; $h = $node->position_hash_map($p);
		$idx_part = match($mode) {
			'internal' => ($node->get_idx() == $parser->idx ? 'me' : ($node->get_idx() == $parser->idx - 1 ? 'prev' : $node->get_idx())),
			'absolute' => '[' . $parser->loaded_URI[$node->get_idx()] . ']',
			default    => $node->get_idx(),
		};
		return sprintf('%04d', $h) . '.' . $idx_part . $p;
	})(), 5);

	$reg->addDescription(
		'Berechnet den Positionsstempel des Knotens und gibt ihn als Kontext an das'
		. ' Value-Kommando weiter. Der Stempel ist heute prozesslokal.',
		[
			'mode' => ['description' => 'relative (Vorgabe) | absolute | internal | external.'
			                          . ' external verschluesselt den Dateipfad mit stamp_key aus'
			                          . ' der Config. ACHTUNG: dieser Befehl liest das Attribut'
			                          . ' NEBEN Command, nicht darin.',
			           'required'    => false]
		]);

    $reg->__go_to_stamp = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$stamp    = $structur['Attribute']['stamp'] ?? $obj->get_context();
			if (!$node->get_parser()->go_to_stamp($stamp))
				throw new Exception('__go_to_stamp: could not resolve "' . $stamp . '"');
			$target = &$node->get_parser()->show_xmlelement();
			$obj->set_node($target);
			$value = $structur['Command']['Value'];
			if (!empty($value))
				$target->hold_messages($value, $obj);
			return true;
		};

    $reg->addLog(fn($node, $obj, $event) => '__go_to_stamp: ' . ($event->get_Result_Array()['Command']['Attribute']['stamp'] ?? $obj->get_context()), 5);

	$reg->addDescription(
		'Setzt den Parser auf den Knoten, den der Positionsstempel bezeichnet, und feuert dort'
		. ' Value. Schlaegt die Aufloesung fehl, wird eine Exception geworfen.',
		[
			'stamp' => ['description' => 'Positionsstempel aus __position_stamp; fehlt er, wird der'
			                           . ' Ereigniskontext genommen. ACHTUNG: NEBEN Command'
			                           . ' gelesen, nicht darin.',
			            'required'    => false]
		]);

    $reg->__set_attribute = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$json = json_decode($structur['Command']['Attribute']['json'], true);
			$name = $json['name'];
			$value = $json['value'];
			if (strpos($name, '#') === false)
				$name = $obj->get_requester()->get_Main_NS() . '#' . $name;
			$node->set_ns_attribute($name, $value);
			return true;
		};

	$reg->addDescription(
		'Setzt ein benanntes Attribut am Knoten. Schreibender Befehl.',
		[
			'json' => ['description' => '{"name":"<ns#local | local>","value":"<wert>"} -'
			                          . ' ohne # gilt der Hauptnamensraum des Aufrufers.',
			           'required'    => true]
		]);

    $reg->__remove_attribute = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$json = json_decode($structur['Command']['Attribute']['json'], true);
			$node->remove_attribute($json['name']);
			return true;
		};

	$reg->addDescription(
		'Entfernt ein benanntes Attribut vom Knoten. Schreibender Befehl.',
		[
			'json' => ['description' => '{"name":"<name>"}', 'required' => true]
		]);

    $reg->__get_attribute = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$json = json_decode($structur['Command']['Attribute']['json'] ?? '', true);
			$name = $json['name'] ?? '';

			/* Der Aufrufer ist ueber den Intern-Kanal kein Knoten, sondern der
			*  ContentGenerator - dort gibt es weder get_Main_NS noch setdata.
			*  Deshalb beide Requester-Wege pruefen, statt sie vorauszusetzen. */
			$requester = $obj->get_requester();
			$is_node   = $requester instanceof Interface_node;

			/* dieselbe Ergaenzung wie in __set_attribute - sonst liest man nicht,
			*  was man geschrieben hat. Ohne aufrufenden Knoten gibt es keinen
			*  Hauptnamensraum; dann gilt der Name so, wie er geschickt wurde. */
			if ($name !== '' && $is_node && strpos($name, '#') === false)
				$name = $requester->get_Main_NS() . '#' . $name;

			/* get_ns_attribute() ohne Argument liefert alle Attribute als Array und
			*  loest Knotenobjekte schon auf. Der Weg ueber das Array statt ueber den
			*  Einzelzugriff, weil dieser auf einen fehlenden Namen zugreift, bevor er
			*  ihn prueft - ein fehlendes Attribut soll aber keine Warnung sein. */
			$all = $node->get_ns_attribute();
			if (!is_array($all)) $all = [];

			if ($name === '')
				$res = json_encode($all, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			else
				$res = array_key_exists($name, $all) ? (string)$all[$name] : '';

			/* Zwei Rueckgabewege, wie bei den Geschwistern: mit Value traegt der
			*  Ereigniskontext den Wert weiter (so macht es __position_stamp), ohne
			*  Value landet er im Datenteil des Aufrufers (so macht es __get_data). */
			$value = $structur['Command']['Value'];
			if (!empty($value))
			{
				$new_obj = new EventObject($obj->get_request(), $requester, $res);
				$node->hold_messages($value, $new_obj);
			}
			elseif ($is_node)
			{
				$booh = $res;
				$requester->setdata($booh, 0, false, false);
			}
			else
			{
				/* Kein Knoten, der den Wert aufnehmen koennte - ueber den Intern-Kanal
				*  ist Value der einzige Rueckgabeweg. Sichtbar statt still fehlschlagen. */
				global $logger_class;
				$logger_class->setAssert('__get_attribute: kein aufrufender Knoten - '
					. 'Wert nur ueber Value abholbar, gelesen wurde "' . $res . '"', 0);
			}
			return true;
		};

    $reg->addLog(function($node, $obj, $event)
		{
			$json = json_decode($event->get_Result_Array()['Command']['Attribute']['json'] ?? '', true);
			$all  = $node->get_ns_attribute();

			return '__get_attribute in ' . $node->full_URI()
				. ' name=' . ($json['name'] ?? '*')
				. ' vorhanden=' . (is_array($all)
					? json_encode($all, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
					: 'keine');
		}, 5);

	$reg->addDescription(
		'Liest ein benanntes Attribut des Knotens. Ohne json kommen alle Attribute des'
		. ' Knotens als JSON-Objekt zurueck - der Weg, einen unbekannten Knoten zu'
		. ' befragen, statt sein Aussehen zu raten. Mit Value bekommt das Value-Kommando'
		. ' den Wert als Ereigniskontext, ohne Value landet er im Datenteil des Aufrufers.'
		. ' Ein fehlendes Attribut liefert den leeren String; ob es fehlt oder leer ist,'
		. ' zeigt der Aufruf ohne json. Lesender Befehl.',
		[
			'json' => ['description' => '{"name":"<ns#local | local>"} - ohne # gilt der'
			                          . ' Hauptnamensraum des Aufrufers. Weglassen = alle'
			                          . ' Attribute des Knotens.',
			           'required'    => false]
		]);

    $reg->__remove_node = function($node, $obj, $event)
		{
			return $node->removeNode();
		};

	$reg->addDescription(
		'Loest den Knoten aus dem Baum und entfernt ihn. Wirkt auf den Knoten, auf dem der Befehl'
		. ' ankommt - deshalb gehoert davor ein __find_node, das genau einen Treffer liefert.'
		. ' Schreibender Befehl, keine Attribute.');

    $reg->__look_around = function($node, $obj, $event)
		{
			$value = $event->get_Result_Array()['Command']['Value'];
			if (!empty($value))
				$node->hold_messages($value, $obj);

			return true;
		};

    $reg->addLog(function($node, $obj, $event)
		{
			$children = [];
			for ($i = 0; $i < $node->index_max(); $i++)
				$children[] = $node->getRefnext($i)->full_URI();

			$prev = $node->getRefprev();
			$survey = "look_around:" 
				. 'act:' . $node->full_URI()
				. ';next:' . implode(',', $children)
				. ';prev:' . ($prev ? $prev->full_URI() : '');


			return $survey;
		}, 5);

	$reg->addDescription(
		'Feuert Value, falls vorhanden, und schreibt danach URI, Kinder und Elternknoten ins Log.'
		. ' Werkzeug zur Fehlersuche; die Ausgabe wird nur zusammen mit __give_log sichtbar.'
		. ' Keine Attribute.');

	/* __echo - den Baum durchlaufen und die Unterbaeume LADEN, nicht starten (STW,
	*  2026-09-10). Zweck: was unter einem Dokument haengt, liegt danach als Baum im
	*  Parser und laesst sich abfragen, ohne dass ein Prozess gelaufen ist.
	*
	*  - Der Lauf geht als Befehl ueber die Struktur (getRefnext()), nicht ueber die
	*    Zuhoererlisten: tree-Knoten haengen per event_initiated flach am indextree.
	*    Jeder Knoten bekommt __echo selbst - die Knoten sind die aktiven Objekte.
	*  - Nach unten immer OHNE Value. Der Eingangsknoten fuehrt Value am Ende genau
	*    einmal aus; sonst liefe es 0- oder n-mal (n = alle Unterknoten).
	*  - depth zaehlt nur an einem src herunter. Ohne Angabe gilt 1: die erste
	*    src-Ebene wird geladen, dort steht es auf 0. Bei 0 wird durchlaufen, aber
	*    nichts geladen. Die Tiefe ist zugleich die Grenze gegen Kreise ueber src
	*    hinweg - ein Baum hat keine, zwei Dokumente, die sich gegenseitig nennen, schon.
	*  - Doppelt geladen wird nicht: xml::load() gibt einen schon geladenen Baum zurueck.
	*  - Nur tree#src und nur Dateien. Eine Adresse wuerde eine fremde Instanz abrufen -
	*    das tut __echo nicht nebenbei, es sagt es im Log.
	*  - Wo mayEnter nein sagt, endet der Lauf: kein Laden, kein Abstieg. */
	$reg->__echo = function($node, $obj, $event)
		{
			global $logger_class;

			$structur = $event->get_Result_Array();
			$attr     = $structur['Command']['Attribute'] ?? [];
			$depth    = (is_array($attr) && isset($attr['depth']) && '' !== trim((string) $attr['depth']))
			          ? max(0, intval($attr['depth']))
			          : 1;
			$value    = $structur['Command']['Value'] ?? null;

			$weiter = fn(int $d) => ['Identifire' => '*',
			                         'Command'    => ['Name' => '__echo', 'Attribute' => ['depth' => $d]]];

			$ist_tree = $node->full_URI() === 'http://www.trscript.de/tree#tree';
			$name     = $ist_tree ? $node->get_ns_attribute('http://www.trscript.de/tree#name') : '';
			$cg       = $node->get_contentGen();

			if($ist_tree && is_object($cg) && !$cg->mayEnter($node))
			{
				$logger_class->setAssert('__echo: tree "' . $name . '": kein Zutritt, nicht geladen, kein Abstieg', 5);
			}
			else
			{
				foreach(($node->getRefnext() ?? []) as $kind)
					$kind->hold_messages($weiter($depth), $obj);

				$src = $ist_tree ? $node->get_ns_attribute('http://www.trscript.de/tree#src') : false;

				if(false !== $src && '' !== trim((string) $src))
				{
					$pfad = resolve_path($src);

					if(!is_file($pfad) && preg_match('#^https?://#i', $pfad))
						$logger_class->setAssert('__echo: tree "' . $name . '": Adresse, nicht geladen (' . $pfad . ')', 5);
					elseif(!is_file($pfad))
						$logger_class->setAssert('__echo: tree "' . $name . '": src nicht gefunden (' . $pfad . ')', 5);
					elseif($depth < 1)
						$logger_class->setAssert('__echo: tree "' . $name . '": Tiefe erschoepft, nicht geladen (' . $pfad . ')', 5);
					else
					{
						/* load() setzt den Parser auf den neuen Baum - danach muss er
						*  zurueck, sonst arbeitet der Rest des Aufrufs im falschen Baum.
						*  War der Baum schon geladen, steht sein Zeiger womoeglich
						*  mitten drin; der wird ebenfalls zurueckgestellt. */
						$parser  = $node->get_parser();
						$zurueck = $parser->cur_idx();

						try
						{
							$idx   = $parser->load($pfad, 0);
							$vorher = &$parser->show_xmlelement();
							$parser->set_first_node();
							$wurzel = $parser->show_xmlelement();

							$logger_class->setAssert('__echo: tree "' . $name . '": ' . $pfad
								. ' geladen (Baum ' . $idx . ', Tiefe ' . $depth . ' -> ' . ($depth - 1) . ')', 5);

							if(is_object($wurzel))
								$wurzel->hold_messages($weiter($depth - 1), $obj);

							$parser->change_idx($idx);
							if(is_object($vorher)) $parser->set_xmlelement($vorher);
						}
						finally
						{
							$parser->change_idx($zurueck);
						}
					}
				}
			}

			if(!empty($value))
				$node->hold_messages($value, $obj);

			return true;
		};

	$reg->addLog(function($node, $obj, $event)
		{
			$a = $event->get_Result_Array()['Command']['Attribute'] ?? [];
			return '__echo auf ' . $node->full_URI() . ' (depth ' . var_export($a['depth'] ?? null, true) . ')';
		}, 6);

	$reg->addDescription(
		'Durchlaeuft den Baum ab dem Knoten, auf dem der Befehl steht, und LAEDT dabei die'
		. ' Dokumente, die tree-Knoten per src nennen - ohne sie zu starten. Danach liegen sie als'
		. ' Baeume im Parser und lassen sich abfragen. Nach unten laeuft der Befehl ohne Value;'
		. ' Value wird am Ende genau einmal auf dem Eingangsknoten gefeuert. Nur Dateien, keine'
		. ' Adressen; wo der Zutritt fehlt, endet der Lauf. Ein schon geladenes Dokument wird'
		. ' nicht neu geladen.',
		[
			'depth' => ['description' => 'Wie viele src-Ebenen geladen werden. Zaehlt nur an einem'
			                           . ' src herunter. Leer = 1, 0 = durchlaufen ohne zu laden.'
			                           . ' Zugleich die Grenze gegen Kreise zwischen Dokumenten.',
			            'required'    => false]
		]);

	/* __where_am_i - das Tuerschild des Knotens, auf dem der Befehl steht, als Array
	*  (STW, 2026-09-10). Parameter scope (local/tree/global) und show (Begriffsliste).
	*
	*  - Gefragt wird ueber den NAMEN, per SPARQL: ein Weg, derselbe, den spaeter jede
	*    Abfrage nimmt. Je Schildbegriff eine kleine Abfrage - die Maschine kennt weder
	*    OPTIONAL noch eine Variable im Praedikat.
	*  - Die Begriffe: tree:value, die Zielbegriffe aus PHP_Ast_Scan::DESC_KEYS (dieselben
	*    Worte wie an einer PHP-Methode) und die drei, die der Kuehlschrank dazu gepraegt
	*    hat (delivers, columns, effect) - die Ausgaben stehen nicht in DESC_KEYS.
	*  - ?s wird mit abgefragt, und nur die Zeilen mit GENAU diesem Knoten zaehlen: ein
	*    blanker Name ist ein Pfadsegment, er darf anderswo im Dokument wieder stehen.
	*  - Gefragt wird im Baum des Knotens, nicht im aktuellen (nach einem __echo liegen
	*    mehrere Baeume im Parser); danach steht der Parser wieder, wo er war.
	*  - Ergebnis ins EREIGNIS (set_context) - der Zwischenspeicher. Mit Value geht es
	*    auf demselben Ereignis weiter, __to_owner gibt es dann nach aussen. Ohne Value
	*    liegt es dort fuer den naechsten Befehl einer Liste.
	*  ⚠ dcterms heisst als volle URI .../terms/#title (full_URI haengt # an), darum der
	*    Praefix mit #. */
	$reg->__where_am_i = function($node, $obj, $event)
		{
			$T        = 'http://www.trscript.de/tree#';
			$structur = $event->get_Result_Array();
			$value    = $structur['Command']['Value'] ?? null;
			$attr     = $structur['Command']['Attribute'] ?? [];
			if(!is_array($attr)) $attr = [];

			$scope = trim((string) ($attr['scope'] ?? ''));
			if($scope === '') $scope = 'local';
			$show  = trim((string) ($attr['show'] ?? ''));

			$parser = $node->get_parser();
			$cg     = $node->get_contentGen();
			$notes  = [];

			if(!class_exists('PHP_Ast_Scan'))
				require_once(__DIR__ . '/../classes/handles/PHP_ast_scan.php');

			/* Die Begriffe. show nimmt Kurznamen (die Schluessel aus DESC_KEYS, dazu value,
			*  delivers, columns, effect) oder die Praefixform. Der Name wird in die Abfrage
			*  eingesetzt - darum nur, was dem Muster praefix:name folgt. */
			$kurz = PHP_Ast_Scan::DESC_KEYS + ['value'    => 'tree:value',
			                                   'delivers' => 'desc:delivers',
			                                   'columns'  => 'desc:columns',
			                                   'effect'   => 'desc:effect'];
			$begriffe = [];

			if($show === '')
				$begriffe = array_values(array_unique(array_merge(['tree:value'],
					array_values(PHP_Ast_Scan::DESC_KEYS), ['desc:delivers', 'desc:columns', 'desc:effect'])));
			else
				foreach(array_map('trim', explode(',', $show)) as $w)
				{
					if($w === '' || $w === 'uri' || $w === 'name') continue;
					if(isset($kurz[strtolower($w)]))                                         $b = $kurz[strtolower($w)];
					elseif(preg_match('/^(tree|desc|dcterms):[A-Za-z_][A-Za-z0-9_]*$/', $w)) $b = $w;
					else { $notes[] = 'unbekannt in show: ' . $w; continue; }
					if(!in_array($b, $begriffe, true)) $begriffe[] = $b;
				}

			/* Schilder tragen nur tree und final (STW). Was sonst im Baum steht, ist die
			*  Umsetzung des Prozesses und geht den Besucher nichts an - dafuer gibt es einen
			*  anderen Befehl, der eine hoehere Stufe tragen kann. Und was mayEnter
			*  verweigert, taucht auch in keiner Liste auf. */
			$sorten = [$T . 'final' => 'tree:final', $T . 'tree' => 'tree:tree'];
			$darf   = fn($n) => !is_object($cg) || $cg->mayEnter($n);
			$baeume = [];

			if(!is_object($parser))
				$notes[] = 'der Knoten hat keinen Parser (zur Laufzeit angelegt?)';
			elseif($scope === 'local')
			{
				if(!isset($sorten[$node->full_URI()]))
					$notes[] = 'kein tree- oder final-Knoten - Schilder tragen nur diese; der Rest laeuft ueber einen anderen Befehl';
				elseif(!$darf($node))
					$notes[] = 'kein Zutritt';
				else
					$baeume = [$node->get_idx()];
			}
			elseif($scope === 'tree')
				$baeume = [$node->get_idx()];
			elseif($scope === 'global')
				$baeume = range(0, $parser->max_idx());
			else
				$notes[] = 'unbekannter scope: ' . $scope . ' (local, tree, global)';

			$treffer = [];

			if($baeume)
			{
				$praefix = "PREFIX tree: <http://www.trscript.de/tree#>\n"
				         . "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n"
				         . "PREFIX desc: <" . PHP_Ast_Scan::NS_DESC . "#>\n"
				         . "PREFIX dcterms: <" . PHP_Ast_Scan::NS_DCTERMS . "#>\n";
				$zurueck = $parser->cur_idx();

				try
				{
					$m = $parser->seek_by_model('sparql');
					if($m->source() === '')
						$m->use_source('qportal');

					foreach($baeume as $i)
					{
						/* Gefragt wird im jeweiligen Baum - nach einem __echo liegen mehrere
						*  im Parser; danach steht er wieder, wo er war. */
						$parser->change_idx($i);

						foreach($sorten as $sorte)
						{
							/* Erst die Knoten, dann die Begriffe - sonst fiele einer ohne
							*  jeden Schildbegriff still heraus. */
							$m->query($praefix . 'SELECT ?s WHERE { ?s rdf:type ' . $sorte . ' }');
							foreach($m->solutions() as $z)
							{
								$k = $z['?s'] ?? null;
								if(!is_object($k) || ($scope === 'local' && $k !== $node) || !$darf($k))
									continue;

								if(!isset($treffer[spl_object_id($k)]))
								{
									$n = $k->get_ns_attribute($T . 'name');
									$eintrag = ['uri' => $k->full_URI(), 'name' => ($n === false ? null : $n)];
									if($scope === 'global') $eintrag['tree']  = $parser->indexToUri($i);
									if($scope !== 'local')  $eintrag['stamp'] = $k->position_stamp();
									$treffer[spl_object_id($k)] = ['i' => $i, 'e' => $eintrag];
								}
							}

							foreach($begriffe as $b)
							{
								$m->query($praefix . 'SELECT ?s ?wert WHERE { ?s rdf:type ' . $sorte . ' . ?s ' . $b . ' ?wert }');
								foreach($m->solutions() as $z)
									if(is_object($z['?s'] ?? null) && isset($treffer[spl_object_id($z['?s'])]))
										$treffer[spl_object_id($z['?s'])]['e'][$b] = $z['?wert'];
							}
						}
					}
				}
				catch(Throwable $ex)
				{
					$notes[] = 'Fehler: ' . $ex->getMessage();
				}
				finally
				{
					$parser->change_idx($zurueck);
				}
			}

			/* Dokumentreihenfolge: nach Baum, dann nach Stempel */
			usort($treffer, fn($x, $y) => ($x['i'] <=> $y['i']) ?: strnatcmp($x['e']['stamp'] ?? '', $y['e']['stamp'] ?? ''));
			$hits = array_map(fn($t) => $t['e'], $treffer);

			if($scope === 'local')
			{
				$n = $node->get_ns_attribute($T . 'name');
				$ergebnis = $hits[0] ?? ['uri' => $node->full_URI(), 'name' => ($n === false ? null : $n)];
			}
			elseif($scope === 'tree' && is_object($parser))
				$ergebnis = ['scope' => 'tree', 'tree' => $parser->indexToUri($node->get_idx()), 'hits' => $hits];
			else
				$ergebnis = ['scope' => $scope, 'hits' => $hits];

			if($notes)
				$ergebnis['note'] = implode('; ', $notes);

			$obj->set_context($ergebnis);

			if(!empty($value))
				$node->hold_messages($value, $obj);

			return true;
		};

	$reg->addLog(function($node, $obj, $event){
			$a = $event->get_Result_Array()['Command']['Attribute'] ?? [];
			return '__where_am_i auf ' . $node->full_URI() . ' (scope ' . var_export($a['scope'] ?? 'local', true) . ')';
		}, 5);

	$reg->addDescription(
		'Tuerschilder als Array. Ein Schild haben nur tree- und final-Knoten: uri, name und was'
		. ' es sagt - tree:value, dcterms:*, desc:function, desc:parameter (Eingaben),'
		. ' desc:delivers und desc:columns (Ausgaben), desc:effect, desc:tricky. Gefragt wird per'
		. ' SPARQL; was mayEnter verweigert, erscheint nicht. Das Ergebnis liegt danach im'
		. ' Ereignis; mit Value (etwa __to_owner) geht es weiter.',
		[
			'scope' => ['description' => 'local (Vorgabe): das Schild DIESES Knotens. tree: die'
			                           . ' Schilder aller tree/final im Dokument des Knotens, als'
			                           . ' {scope, tree, hits:[...]}. global: dasselbe ueber alle'
			                           . ' geladenen Baeume, je Treffer mit Baum und Stempel.',
			            'required'    => false],
			'show'  => ['description' => 'Kommagetrennte Liste der Begriffe: Kurznamen (function,'
			                           . ' parameter, delivers, columns, effect, value, title, ...)'
			                           . ' oder Praefixform (desc:effect). Leer = alle. uri und name'
			                           . ' stehen immer drin; Unbekanntes landet in note.',
			            'required'    => false]
		]);

	/* __call - den Knoten, auf dem der Befehl steht, ausfuehren wie ein <sub> und seine
	*  <result>-Rueckgabe einsammeln (STW, 2026-09-11). Derselbe Aufbau wie tree_sub.php;
	*  die Argumente (param) werden nachgeruestet.
	*
	*  - Zutritt ueber mayEnter, wie ueberall.
	*  - Ein EIGENER Scope mit eindeutigem Namen. tree_sub nimmt den src als Namen, und
	*    leaveScope loescht den Eintrag nicht - derselbe src zweimal in einem Request
	*    wuerde dort "existiert bereits" werfen.
	*  - Mit src: das Dokument laden, first und final starten (wie tree_sub). Ohne src:
	*    die eigenen Kinder starten, ausser template und tree. Adressen ruft __call nicht.
	*  - Die Rueckgabe: TREE_result legt eine frische INSTANZ in den Scope, deren
	*    Datenteil leer ist - der Wert steht am urspruenglichen Knoten (link_to_class).
	*    getdata() greift darauf nicht zurueck; darum hier ausdruecklich.
	*  - Ergebnis {uri, name, results:[...]} ins Ereignis, dann Value auf demselben
	*    Ereignis (etwa __to_owner). Danach stehen Parser und Scope-Stapel wieder, wo sie
	*    waren. */
	$reg->__call = function($node, $obj, $event)
		{
			$T        = 'http://www.trscript.de/tree#';
			$structur = $event->get_Result_Array();
			$value    = $structur['Command']['Value'] ?? null;
			$cg       = $node->get_contentGen();
			$parser   = $node->get_parser();

			$n       = $node->get_ns_attribute($T . 'name');
			$antwort = ['uri' => $node->full_URI(), 'name' => ($n === false ? null : $n), 'results' => []];

			/* Was ein Ergebnis traegt: Kinder der Instanz, sonst der Datenteil - der
			*  eigene oder der des urspruenglichen Knotens. Ein Objekt wird benannt,
			*  nicht ausgegeben (dasselbe wie answer_shape im ContentGenerator). */
			$wert = function($d)
			{
				return is_object($d) ? ['class' => get_class($d), 'serialised' => false] : $d;
			};
			$auszug = function($r) use ($wert)
			{
				if(!($r instanceof Interface_node))
					return $wert($r);

				$kinder = $r->getRefnext() ?? [];
				if($kinder)
					return array_map(fn($k) => $wert($k->getdata()), $kinder);

				$d = $r->getdata();
				if(($d === '' || is_null($d)) && is_object($r->link_to_class))
					$d = $r->link_to_class->getdata();

				return $wert($d);
			};

			if(!is_object($cg) || !is_object($parser))
				$antwort['note'] = 'kein ContentGenerator oder Parser am Knoten';
			elseif(!$cg->mayEnter($node))
				$antwort['note'] = 'kein Zutritt';
			else
			{
				$cg->createScope();
				$zurueck = $parser->cur_idx();

				try
				{
					$src = $node->get_ns_attribute($T . 'src');

					if(false !== $src && '' !== trim((string) $src))
					{
						$pfad = resolve_path($src);

						if(!is_file($pfad))
							$antwort['note'] = 'src ist keine Datei, Adressen ruft __call nicht: ' . $pfad;
						else
						{
							$parser->load($pfad, 0);

							$parser->flash_result();
							$parser->seek_node($T . 'first');
							$liste = $parser->get_result();
							$first = array_pop($liste);
							$parser->flash_result();
							$parser->seek_node($T . 'final');
							$liste = $parser->get_result();
							$final = array_pop($liste);
							$parser->flash_result();

							$obj->set_node($node);
							if($first) $first->hold_messages('', $obj);
							if($final) $final->hold_messages('', $obj);
							$parser->flash_result();
						}
					}
					else
					{
						/* Auch ohne src laeuft ZUERST das first DIESES Dokuments (STW): was ein
						*  Prozess voraussetzt - angelegte Tabellen zum Beispiel - steht dort, und
						*  der Zweig mit src fuehrt es ohnehin schon aus. Ohne diese Zeilen haette
						*  __call auf einen Knoten im eigenen Dokument als einziger Weg ins
						*  Dokument gefuehrt, der die Voraussetzungen ueberspringt.
						*
						*  ⚠ Ueber den indextree, nicht direkt auf den Knoten. first meldet sich
						*  nach seinem ersten start von der Kante ab (TREE_first), und das wirkt nur
						*  fuer den, der ueber die Kante kommt. Ein direktes hold_messages auf dem
						*  Knoten waere die zweite Tuer und liesse first bei jedem __call erneut
						*  laufen - gemessen, bevor das hier so stand. Es gibt genau eine Tuer, und
						*  es ist dieselbe, die behavior/tree.php benutzt. */
						$parser->flash_result();
						$parser->seek_node($T . 'indextree');
						$liste  = $parser->get_result();
						$wurzel = array_pop($liste);
						$parser->flash_result();

						/* once vor first — dieselbe Reihenfolge wie in behavior/tree.php. Wer ueber
						*  __call in ein Dokument geht, bekommt dieselben Voraussetzungen wie ueber
						*  start; sonst waere __call der Weg, der die Einrichtung ueberspringt. */
						if($wurzel)
							foreach([$T . 'once', $T . 'first'] as $bereich)
								$wurzel->send_messages(
									['Identifire' => $bereich, 'Command' => ['Name' => 'start'], 'Attribute' => []],
									$obj);

						foreach(($node->getRefnext() ?? []) as $kind)
							if(!in_array($kind->full_URI(), [$T . 'template', $T . 'tree'], true))
								$kind->hold_messages('', $obj);
					}

					foreach(($cg->getResult() ?? []) as $r)
						$antwort['results'][] = $auszug($r);
				}
				catch(Throwable $e)
				{
					$antwort['note'] = 'Fehler: ' . $e->getMessage();
				}
				finally
				{
					$cg->leaveScope();
					$parser->change_idx($zurueck);
				}
			}

			$obj->set_context($antwort);

			if(!empty($value))
				$node->hold_messages($value, $obj);

			return true;
		};

	$reg->addLog(function($node, $obj, $event){return '__call auf ' . $node->full_URI();}, 5);

	$reg->addDescription(
		'Fuehrt den Knoten, auf dem der Befehl steht, aus wie ein <sub> und sammelt seine'
		. ' <result>-Rueckgabe ein: mit src das Dokument (first, final), sonst die eigenen'
		. ' Kinder ausser template und tree. Ergebnis {uri, name, results:[...]} im Ereignis;'
		. ' mit Value (etwa __to_owner) geht es weiter. Argumente folgen. Keine Attribute.');

	/* __query - EINE Abfrage fuer alle Suchmodelle (STW, 2026-09-11 geplant, 09-13 gebaut).
	*
	*     {"Identifire":"*","Command":{"Name":"__query","Attribute":{
	*        "model":"sparql","statement":"SELECT ?s WHERE { ?s ?p ?o }"},
	*      "Value":{"Identifire":"*","Command":{"Name":"__to_owner"}}}}
	*
	* Der Automat ist der Standard (STW): eine Abfrage ist ein STRING, den ein Modell
	* liest. Mehr nimmt __query nicht entgegen.
	*
	* ⚠ KEIN scope, anders als bei __where_am_i. Dort hat es einen Sinn, weil tree und
	* final je Dokument existieren und die Tuerschilder an ihnen haengen. Hier nicht:
	* OWL ist in dieser Instanz IMMER instanzweit (STW). Die Daten liegen nicht in
	* Baeumen, sie liegen hinter der Gegenstelle; der Graph IST die Instanz. Ein Beispiel
	* steht im Bestand: template/ontologies/real_estate_data.xml reicht ein einziges
	* RstTurtle-Objekt durch sieben <sub> und setzt daraus einen Graphen ueber alles
	* zusammen - da gaebe es nichts zu begrenzen.
	*
	* ⚠ Die Modelle sind NICHT gleichwertig, und das Interface sagt das nicht:
	* Searching_Model verlangt nur query(). solutions(), use_source() und profile()
	* stehen allein am sparql_model. Darum wird gefragt (method_exists), statt es
	* vorauszusetzen - ein Interface, das zwei Modelle nur pro forma erfuellen, waere
	* unehrlicher als diese Frage.
	*
	*     sparql     laeuft. Ohne gewaehlte Quelle wird qportal genommen (sparql.use ist
	*                leer), dann query() und solutions() fuer die Zeilen.
	*     internal   96 Zeilen ohne einen einzigen Aufrufer, und query() will dort eine
	*                volle URI statt einer Abfrage - ein Nachschlageschluessel, keine
	*                Sprache. Laeuft hier durch, liefert aber keine Zeilen.
	*     xpath      wirft "noch nicht gebaut". Gefangen und als note zurueck.
	*                ⚠ Wenn es kommt, braucht es ZUSATZINFO: XPath ist dokumentspezifisch
	*                (STW), also muss der Knoten mit, auf dem der Befehl steht. Dafuer ist
	*                der Platz unten schon markiert.
	*
	* ⚠ Der Ausdruck kommt VOLLSTAENDIG von aussen und geht bei type=fuseki an eine fremde
	* Gegenstelle - anders als bei __where_am_i, das seine Abfragen aus einer festen Form
	* mit einer Begriffsliste selbst baut. Das ist der Grund fuer die Stufe 6. */
	$reg->__query = function($node, $obj, $event)
		{
			global $logger_class;

			$structur = $event->get_Result_Array();
			$attr     = $structur['Command']['Attribute'] ?? [];
			$value    = $structur['Command']['Value']     ?? null;

			$model     = trim((string) ($attr['model']     ?? ''));
			$statement = (string)       ($attr['statement'] ?? '');

			if($model === '') $model = 'sparql';

			$antwort = ['model' => $model, 'rows' => []];
			$parser  = $node->get_parser();

			if('' === trim($statement))
				$antwort['note'] = 'kein statement - ohne Ausdruck gibt es nichts zu fragen';
			elseif(!is_object($parser))
				$antwort['note'] = 'kein Parser am Knoten';
			else
			{
				try
				{
					$m = $parser->seek_by_model($model);

					if(is_null($m))
						$antwort['note'] = 'kein Suchmodell "' . $model . '"';
					else
					{
						/* Die Vorbereitung ist je Modell verschieden - use_source gibt es nur
						*  am sparql_model, ein pauschaler Aufruf waere ein Fatal. */
						if(method_exists($m, 'source') && method_exists($m, 'use_source')
						   && $m->source() === '')
							$m->use_source('qportal');

						/* ⚠ HIER kommt die Zusatzinfo hin, sobald xpath gebaut ist: XPath ist
						*  dokumentspezifisch und braucht den Knoten, auf dem __query steht.
						*  Wieder ueber method_exists, damit sparql davon nichts merkt. */
						if(method_exists($m, 'set_context'))
							$m->set_context($node);

						$treffer = $m->query($statement);

						/* Zeilen kann nur, wer solutions() hat. Sonst ist die Rueckgabe von
						*  query() alles, was es gibt. */
						$roh = method_exists($m, 'solutions') ? $m->solutions() : $treffer;

						$antwort['rows'] = array_map(function($zeile) use ($parser)
							{
								if(!is_array($zeile)) $zeile = ['wert' => $zeile];

								foreach($zeile as $spalte => $wert)
								{
									/* answer_shape serialisiert keinen Knoten - Knoten werden
									*  darum zu dem, was von aussen tragfaehig ist. */
									if($wert instanceof Interface_node)
										$zeile[$spalte] = ['uri'   => $wert->full_URI(),
										                   'name'  => $wert->get_ns_attribute('http://www.trscript.de/tree#name'),
										                   'stamp' => $wert->position_stamp()];
									elseif(is_object($wert))
										$zeile[$spalte] = (string) $wert;
								}

								return $zeile;
							}, is_array($roh) ? $roh : []);

						if(method_exists($m, 'solutions') === false)
							$antwort['note'] = 'das Modell "' . $model . '" kennt keine Zeilen'
							                 . ' - zurueck kommt, was query() gibt';
					}
				}
				catch(Throwable $e)
				{
					/* xpath wirft hier hinein, und ein kaputter Ausdruck ebenso. Innen die
					*  Meldung, aussen eine note - dieselbe Trennung wie ueberall. */
					$antwort['note'] = 'Fehler: ' . $e->getMessage();

					if($logger_class)
						$logger_class->setAssert('__query (' . $model . '): ' . $e->getMessage(), 0);
				}
			}

			$obj->set_context($antwort);

			if(!empty($value))
				$node->hold_messages($value, $obj);

			return true;
		};

	$reg->addLog(function($node, $obj, $event)
		{
			$a = $event->get_Result_Array()['Command']['Attribute'] ?? [];
			return '__query (' . ($a['model'] ?? 'sparql') . '): '
			     . substr((string) ($a['statement'] ?? ''), 0, 120);
		}, 5);

	$reg->addSecurity(6);

	$reg->addDescription(
		'Eine Abfrage fuer alle Suchmodelle. Attribute: model (Vorgabe sparql, sonst'
		. ' internal oder xpath) und statement - ein String, den das Modell liest. Ergebnis'
		. ' {model, rows:[...]} im Ereignis; mit Value (etwa __to_owner) geht es weiter.'
		. ' Knoten in den Zeilen erscheinen als uri/name/stamp. KEIN scope: OWL ist hier'
		. ' instanzweit, es gibt nichts zu begrenzen. Stufe 6, weil der Ausdruck'
		. ' vollstaendig von aussen kommt und bei einer fremden Gegenstelle landen kann.'
		. ' xpath ist noch nicht gebaut und meldet das als note.');

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

?>