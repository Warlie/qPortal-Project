<?php

namespace Finite\Elements;

/**
*	Mealy-Automat — zeichengetrieben, mit Ausgabe an der Kante.
*
*	Portierung von de.auster_gmbh.library.language.mealy_automat aus
*	anttree/funct_parser_lib.js. Die Vorlage ist unveraendert uebernommen; was
*	hinzugekommen ist, steht unten unter "Zutaten".
*
*	Er ist der zweite Automat neben dem Acceptor und beantwortet eine andere Frage:
*
*	  Acceptor   regex je Uebergang, baut ueber den Transducer einen KNOTENBAUM.
*	  Mealy      ein ZEICHEN je Schritt, sammelt in eine FLACHE TABELLE.
*
*	Fuer eine Abfragesprache ist die flache Tabelle die richtige Form: eine SPARQL-
*	Anfrage ist eine Liste von Zeilen (Prefix, Spalte, Tripel), kein Baum. Und sie
*	traegt sich bis C — der Automat kennt nur Arrays und Zeichen, keine Objektgraphen.
*
*	== Die drei Angaben ==
*
*	  setNode/setNodes($name,…)        Zustaende. Der zuerst genannte ist der Start.
*	  setEdge($von,$nach,$zeichen,$cmd) Uebergang. $zeichen ist ein Zeichen, eine
*	                                    ZEICHENFOLGE ("SELECT") oder ein Array davon.
*	  setStringNode($node,$feld)        "was in diesem Zustand keine Kante findet,
*	                                    gehoert in Feld $feld" — so werden Namen und
*	                                    URIs gelesen, ohne sie zu buchstabieren.
*
*	== Die Ausgabe ($cmd) ==
*
*	Ein Feldname ("uri", "subject") haengt das gelesene Stueck an die aktuelle Zeile.
*	Ein Befehl mit Klammern steuert die Tabelle:
*
*	  next()        Zeile abschliessen, naechste beginnen
*	  deeper()      eine Ebene tiefer (#deep steigt) — "{"
*	  shallow()     eine Ebene hoeher (#deep faellt) — "}"
*	  section(x)    ab hier gehoeren die Zeilen zu Abschnitt x (#section)
*
*	Jede Zeile traegt darum zwei Zusatzfelder: #section (wo sie steht) und #deep (wie
*	tief). Damit ist die Klammerung erhalten, ohne dass ein Baum gebaut wurde.
*
*	== Wie gelesen wird ==
*
*	Die Zeichenfolgen aller Kanten liegen in einem Auswahlbaum (selectiontree), Zeichen
*	fuer Zeichen, mit '§' vor jedem Zeichen — damit die Zeichenschluessel nicht mit den
*	Zustandsnummern kollidieren, die im selben Array liegen. Der Automat laeuft an jeder
*	Stelle so weit hinein, wie der Baum traegt (laengste Uebereinstimmung: "SELECT" wird
*	als eines gelesen, nicht als S-E-L-E-C-T), und fragt dann, ob der aktuelle Zustand
*	dort eine Kante hat.
*
*	== Zutaten gegenueber der Vorlage ==
*
*	1. checkString() setzt den Laufzustand zurueck (Tabelle, Zeile, Tiefe, Abschnitt).
*	   In der Vorlage lief jede Anfrage in ein frisches Objekt; hier darf ein Parser
*	   stehenbleiben und mehrfach gefragt werden.
*	2. Am Ende von checkString() wird eine angefangene Zeile nachgetragen. Bei gut
*	   geformter Eingabe ist das wirkungslos (das schliessende "}" hat schon
*	   abgeschlossen); bei einer Eingabe ohne Schluss geht die letzte Zeile nicht mehr
*	   still verloren.
*	3. setEdge/setStringNode sagen es, wenn ein Zustand nicht angemeldet ist. In der
*	   Vorlage wurde daraus stumm eine Kante ins Nichts.
*	4. showStructure/showAll schreiben nicht, sie geben zurueck (structure/debug_rows).
*	   Ausgabe im Produktionspfad zerstoert JSON_RESPONSE.
*
*	@see anttree/funct_parser_lib.js
*/
class Mealy_Automat
{
	/** Zustandsname => Nummer. */
	private array $ref = array();

	/** Nummer => Zustandsname. */
	private array $reverse = array();

	/** Nur zur Anschauung: alle Kanten, wie sie angemeldet wurden. */
	private array $edge_table = array();

	/** Zeichenbaum: '§z' => Teilbaum, darin Zustandsnummer => array(Ziel, cmd). */
	private array $selectiontree = array();

	/** Zustandsnummer => Feldname, in das gesammelt wird, was keine Kante findet. */
	private array $collectable = array();

	/** Die Feldnamen, die es wirklich gibt — alles andere ist ein Befehl. */
	private array $blueprint_line = array();

	private int $unique = 0;

	/* ------------------------------------------------------------- Laufzustand */

	private array $current_line = array();
	private array $fulltable    = array();
	private array $debug_table  = array();
	private bool  $next_available = false;
	private int   $deep    = 0;
	private string $section = 'none';


	// -------------------------------------------------------------------------
	// Anmelden
	// -------------------------------------------------------------------------

	public function setNodes(string ...$names): void
	{
		foreach($names as $name)
			$this->setNode($name);
	}

	public function setNode(string $name): void
	{
		if(isset($this->ref[$name]))
			throw new \Exception('Mealy_Automat: Zustand "' . $name . '" steht schon im Stapel.');

		$this->ref[$name] = $this->unique;
		$this->reverse[$this->unique++] = $name;
	}

	/**
	*	@param	string		$ante	Zustand, aus dem die Kante fuehrt
	*	@param	string		$succ	Zustand, in den sie fuehrt
	*	@param	string|array	$valid	Zeichen/Zeichenfolge oder eine Liste davon
	*	@param	string		$cmd	Feldname oder Befehl, siehe Kopf
	*/
	public function setEdge(string $ante, string $succ, $valid, string $cmd = ''): void
	{
		$this->must_know($ante);
		$this->must_know($succ);

		$arg = is_array($valid) ? $valid : array($valid);

		foreach($arg as $sign)
		{
			$this->edge_table[] = array('from' => $ante, 'to' => $succ,
			                            'sign' => '"' . $sign . '"', 'cmd' => $cmd);

			$res = &$this->createTreeSearch((string) $sign);
			$res[$this->ref[$ante]] = array($this->ref[$succ], $cmd);
			unset($res);

			$this->note_field($cmd);
		}
	}

	/**
	*	"Was in diesem Zustand keine Kante findet, gehoert in Feld $cmd."
	*/
	public function setStringNode(string $node, string $cmd): void
	{
		$this->must_know($node);

		$this->collectable[$this->ref[$node]] = $cmd;
		$this->note_field($cmd);
	}

	private function must_know(string $name): void
	{
		if(!isset($this->ref[$name]))
			throw new \Exception('Mealy_Automat: Zustand "' . $name . '" ist nicht angemeldet '
			                   . '(setNode/setNodes vor setEdge).');
	}

	/** Ein cmd ohne Klammern ist ein Feld der Zeile, mit Klammern ein Befehl. */
	private function note_field(string $cmd): void
	{
		if($cmd !== '' && !str_ends_with($cmd, ')'))
			$this->blueprint_line[$cmd] = true;
	}

	/** Legt den Pfad einer Zeichenfolge im Auswahlbaum an und gibt sein Ende zurueck. */
	private function &createTreeSearch(string $val)
	{
		$tmp = &$this->selectiontree;
		$len = strlen($val);

		for($i = 0; $i < $len; $i++)
		{
			$key = '§' . $val[$i];

			if(!isset($tmp[$key]))
				$tmp[$key] = array();

			$tmp = &$tmp[$key];
		}

		return $tmp;
	}


	// -------------------------------------------------------------------------
	// Lesen
	// -------------------------------------------------------------------------

	/**
	*	Laesst den Automaten ueber die Zeichenkette laufen. Danach steht das Ergebnis
	*	in getResult(), der Weg dorthin in debug_rows().
	*
	*	@throws	\Exception	wenn ein Zustand fuer das naechste Zeichen keine Kante hat
	*/
	public function checkString(string $str): void
	{
		$this->fulltable      = array();
		$this->debug_table    = array();
		$this->current_line   = array();
		$this->next_available = false;
		$this->section        = 'none';
		$this->deep           = 0;

		$process     = '';
		$context     = '';
		$context_add = '';
		$check_str   = '';
		$state       = 0;
		$compare     = 0;
		$tmp         = &$this->selectiontree;
		$len         = strlen($str);
		$pos         = 0;

		for($pos = 0; $pos < $len; $pos++)
		{
			/* So weit in den Zeichenbaum hinein, wie er traegt — laengste Folge gewinnt. */
			while(true)
			{
				$ch = $this->charAt($str, $pos + $compare);

				if($ch !== '' && isset($tmp['§' . $ch]))
				{
					$tmp        = &$tmp['§' . $ch];
					$check_str .= '.§' . $ch;
				}
				else
				{
					$compare      = max(0, $compare - 1);
					$context_add .= '[' . $pos . '+' . $compare . ' {' . $check_str;
					break;
				}

				$compare++;
			}

			$check_str = '';

			if(isset($this->collectable[$state]))
			{
				$process = 'collectable';

				/* Kein Uebergang von hier: das Zeichen gehoert in das Feld. */
				if(!isset($tmp[$state]))
				{
					$tmp      = &$this->selectiontree;
					$context .= $this->charAt($str, $pos);

					$this->saveSet($this->charAt($str, $pos), $this->collectable[$state]);

					$compare      = 0;
					$context_add .= '(' . $this->charAt($str, $pos + 1) . ")]\n";
					continue;
				}

				$this->saveSet('', $tmp[$state][1]);
				$context_add .= '.[' . $tmp[$state][0] . ', ' . $tmp[$state][1] . "]]\n";
			}
			else if(isset($tmp[$state]))
			{
				$context_add .= '.[' . $tmp[$state][0] . ', ' . $tmp[$state][1] . ']';
				$process      = ($tmp[$state][1] !== '')
				                ? 'has_process:' . $tmp[$state][1]
				                : 'common_path';

				$this->saveSet(substr($str, $pos, $compare + 1), $tmp[$state][1]);
			}

			if(!isset($tmp[$state]))
				throw new \Exception('Mealy_Automat: Text nicht angenommen. Zustand "'
				                   . ($this->reverse[$state] ?? $state) . '" hat keine Kante fuer "'
				                   . $this->charAt($str, $pos + $compare) . '" an Stelle '
				                   . ($pos + $compare) . ':' . "\n"
				                   . substr($str, 0, $pos + $compare) . ' >'
				                   . $this->charAt($str, $pos + $compare) . '< '
				                   . substr($str, $pos + $compare + 1));

			$this->debug_table[] = array(
				'state'    => $this->reverse[$state] ?? $state,
				'next'     => $this->reverse[$tmp[$state][0]] ?? $tmp[$state][0],
				'position' => "'" . substr($str, $pos, $compare + 1) . "'(" . $pos . ', ' . $compare . ')',
				'process'  => $process,
				'context'  => $context,
				'breaks'   => $context_add);

			$state = $tmp[$state][0];

			$tmp         = &$this->selectiontree;
			$pos         = $pos + $compare;
			$compare     = 0;
			$process     = '';
			$context     = '';
			$context_add = '';
		}

		/* Zutat: eine angefangene Zeile geht nicht verloren, wenn der Schluss fehlt. */
		if($this->next_available)
		{
			$this->current_line['#section'] = $this->section;
			$this->current_line['#deep']    = $this->deep;
			$this->fulltable[]              = $this->current_line;
			$this->current_line             = array();
			$this->next_available           = false;
		}

		$this->debug_table[] = array(
			'state' => $this->reverse[$state] ?? $state, 'next' => $this->reverse[$state] ?? $state,
			'position' => '(' . $pos . ')', 'process' => 'Finalized', 'context' => '', 'breaks' => '');
	}

	/** charAt: hinter dem Ende ein leerer String, keine Warnung — wie in der Vorlage. */
	private function charAt(string $str, int $pos): string
	{
		return ($pos >= 0 && $pos < strlen($str)) ? $str[$pos] : '';
	}

	/**
	*	Die Ausgabe der Kante: entweder in ein Feld sammeln oder die Tabelle steuern.
	*/
	private function saveSet(string $string, string $cmd): bool
	{
		if($cmd === '')
			return true;

		if(str_ends_with($cmd, ')'))
		{
			$open    = strpos($cmd, '(');
			$command = substr($cmd, 0, $open);
			$arg     = substr($cmd, $open + 1, strpos($cmd, ')') - $open - 1);

			if($command === 'next' && $this->next_available)
			{
				$this->next_available           = false;
				$this->current_line['#section'] = $this->section;
				$this->current_line['#deep']    = $this->deep;
				$this->fulltable[]              = $this->current_line;
				$this->current_line             = array();
			}

			if($command === 'deeper')
			{
				$this->current_line['#section'] = $this->section;
				$this->current_line['#deep']    = ++$this->deep;

				if($this->next_available)
				{
					$this->fulltable[]    = $this->current_line;
					$this->current_line   = array();
					$this->next_available = false;
				}
			}

			if($command === 'shallow')
			{
				$this->current_line['#section'] = $this->section;
				$this->current_line['#deep']    = $this->deep--;

				if($this->next_available)
				{
					$this->fulltable[]    = $this->current_line;
					$this->current_line   = array();
					$this->next_available = false;
				}
			}

			if($command === 'section')
				$this->section = $arg;

			return true;
		}

		if(isset($this->blueprint_line[$cmd]))
		{
			$this->next_available = true;

			if(!isset($this->current_line[$cmd]))
				$this->current_line[$cmd] = '';

			$this->current_line[$cmd] .= $string;
		}

		return true;
	}


	// -------------------------------------------------------------------------
	// Ergebnis und Anschauung
	// -------------------------------------------------------------------------

	/** Die gelesene Tabelle: je Zeile die Felder plus #section und #deep. */
	public function getResult(): array
	{
		return $this->fulltable;
	}

	/** Zustaende und Kanten, wie sie angemeldet wurden — statt console.table. */
	public function structure(): array
	{
		$nodes = array();

		foreach($this->ref as $name => $idx)
			$nodes[$name] = $this->collectable[$idx] ?? '';

		return array('nodes' => $nodes, 'edges' => $this->edge_table);
	}

	/** Der Weg durch die Eingabe, Schritt fuer Schritt — statt showAll. */
	public function debug_rows(): array
	{
		return $this->debug_table;
	}
}
