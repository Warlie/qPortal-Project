<?php

/**  Produces the entry list that Obj_Class_Collection consumes, derived from the
*    syntax tree instead of from substring matching.
*
*    Every entry keeps the shape File_Scan delivers - ['tag','pos','file'] - and adds a
*    'meta' array holding what a substring cannot carry: visibility, static/abstract/final,
*    parameter types, default values, implemented interfaces, and the kind of declaration.
*    Consumers that do not know 'meta' keep working on the tag string alone.
*
*    scan() throws PhpParser\Error for a source PHP itself would reject; the caller decides.
*/

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\Modifiers;

class PHP_Ast_Scan
{
	/* Beschreibungs-Namensraeume. Als Konstante gefuehrt, damit ein spaeterer
	*  Domainwechsel eine Zeile kostet und nicht jede Fundstelle.
	*/
	const NS_DESC = 'http://www.trscript.de/2026/pedl-desc';
	/* DCMI Metadata Terms, nicht das unqualifizierte Set von elements/1.1.
	*  Der Unterschied ist die Verfeinerung: elements/1.1 kennt nur ein unscharfes
	*  "date", terms trennt created von modified; statt eines pauschalen "relation"
	*  gibt es isPartOf, references, replaces. Wer eine Beschreibungsschicht baut,
	*  will diese Unterschiede - STW: "Dann nehmen wir das Feine."
	*  Beide Saetze NICHT mischen: dcterms:title und dc:title sind fuer den Baum
	*  zwei verschiedene Praedikate.
	*  ⚠ Nicht angefasst: SVG_Overview_handle schreibt weiter elements/1.1. Das ist
	*    der Inkscape/CreativeCommons-Metadatenblock im SVG (cc:Work, dcmitype), dort
	*    ist elements/1.1 die Konvention - anderer Kontext, andere Regel.
	*/
	const NS_DCTERMS = 'http://purl.org/dc/terms/';

	/* Was ein @schluessel: im Quelltext im Baum wird.
	*  Dublin Core, wo Dublin Core es meint - Aussagen ueber ein Dokument. Alles
	*  Uebrige sind Aussagen ueber Code, dafuer hat Dublin Core kein Vokabular; das
	*  traegt desc:.
	*  Die Schreibvarianten stehen hier, damit im Quelltext nichts korrigiert werden muss.
	*/
	/* Kopfnotation in /*@ ... @* / (STW 2026-09-16): eine Zeile "name::" beginnt einen
	*  Abschnitt, der Text darunter gehoert zu desc:name. Gewaehlt nach Messung ueber
	*  15.913 Kommentarzeilen: "wort::" am Zeilenanfang kommt NIE vor ("::" steht 232 mal,
	*  immer mitten in plugin::col()), eine Zeile "<wort>" dagegen schon 4 mal - qPortal-
	*  Prosa zitiert XML, ein <final> allein auf einer Zeile waere still zum Kopf geworden.
	*
	*  Frei sind die Namen; diese hier sind bekannt und werden nicht als unbekannt gemeldet.
	*  delivers/columns/effect stehen bewusst NICHT in DESC_KEYS: dort wuerde @delivers: zur
	*  flachen Textform, und die Ausgabebeschreibung soll ein Verweis sein koennen. */
	const HEAD_KNOWN    = ['delivers', 'columns', 'effect', 'text'];
	/* Nur Praefixe, deren Namensraum der PEDL-Kopf erklaert - ein fremdes waere ein Tag
	*  ohne Namensraum. */
	const HEAD_PREFIXES = ['desc', 'dcterms'];
	/* Angaben zu einer Aussage in der Klammer: "tricky(lang=de)::". Links der Schluessel
	*  in der Notation, rechts das Attribut am erzeugten Element. Ein allgemeiner Platz -
	*  vorgesehen sind noch die Richtung einer Verfeinerung und group/one/all (STW 06-15).
	*  xml:lang steht so am Eigenschaftselement wie im Bestand (<rdfs:label xml:lang="de">,
	*  197 mal) und landet beim Parsen unter http://www.w3.org/XML/1998/namespace#lang. */
	const HEAD_ATTRIBS  = ['lang' => 'xml:lang'];

	const DESC_KEYS = [
		'title'       => 'dcterms:title',
		'description' => 'dcterms:description',
		'creator'     => 'dcterms:creator',   // der Dublin-Core-Name selbst - fehlte, creator:: wurde desc:creator
		'autor'       => 'dcterms:creator',
		'author'      => 'dcterms:creator',

		/* Die Verfeinerungen, die den Wechsel ueberhaupt lohnen. */
		'created'     => 'dcterms:created',
		'modified'    => 'dcterms:modified',
		'date'        => 'dcterms:date',       // der unscharfe Fall, wenn keiner passt
		'license'     => 'dcterms:license',
		'rights'      => 'dcterms:rights',
		'subject'     => 'dcterms:subject',
		'publisher'   => 'dcterms:publisher',
		'contributor' => 'dcterms:contributor',
		'language'    => 'dcterms:language',
		'identifier'  => 'dcterms:identifier',
		'ispartof'    => 'dcterms:isPartOf',
		'partof'      => 'dcterms:isPartOf',
		'references'  => 'dcterms:references',
		'replaces'    => 'dcterms:replaces',

		'function'    => 'desc:function',
		'func'        => 'desc:function',
		'func_tion'   => 'desc:function',
		'parameter'   => 'desc:parameter',
		'param'       => 'desc:parameter',
		'tricky'      => 'desc:tricky',
		'throws'      => 'desc:throws',
	];

	/**
	*	@param $source : php source text
	*	@param $file   : path recorded in every entry
	*	@return array of ['tag','pos','file','meta']
	*/
	public static function scan(string $source, string $file, bool $follow_includes = true) : array
	{
		$seen = [];
		return self::scan_source($source, $file, $follow_includes, $seen);
	}

	/* One form for every path that reaches the .pedl, so a generated file does not bake in
	*  the machine it was generated on. Paths outside the project stay as they are.
	*/
	public static function project_relative(string $path) : string
	{
		$root = dirname(__DIR__, 2) . '/';

		if(str_starts_with($path, $root))return substr($path, strlen($root));

		if(($real = realpath($path)) && str_starts_with($real, $root))
			return substr($real, strlen($root));

		return $path;
	}

	private static function scan_source(string $source, string $file, bool $follow, array &$seen) : array
	{
		if($real = realpath($file))$seen[$real] = true;

		$ast = (new ParserFactory())->createForNewestSupportedVersion()->parse($source);

		if(is_null($ast))return [];

		$visitor = new PHP_Ast_Scan_Visitor(self::project_relative($file));

		/* Der Dateikopf - @title, @autor, @description - steht in vielen Plugins vor dem
		*  ersten require_once. Der Parser haengt ihn dann an die Einbindung, nicht an die
		*  Klasse. Also alles vor der ersten Klasse einsammeln und ihr zuschlagen.
		*/
		$head = [];
		foreach($ast as $stmt)
		{
			if($stmt instanceof Node\Stmt\ClassLike)break;
			$head = array_merge($head, $visitor->desc_entries($stmt));
		}
		$visitor->set_head_desc($head);

		$traverser = new NodeTraverser();
		$traverser->addVisitor($visitor);
		$traverser->traverse($ast);

		$entries = $visitor->result();

		if(!$follow)return $entries;

		/* Included files are walked as well, as the old cross_seek did. Their classes are
		*  dropped later by the void list, but building them is what fills
		*  Obj_Class::$my_list_of_resources - and that map is where the rdfs:seeAlso to the
		*  parent's .pedl comes from. Without it the inheritance merge loses the parent.
		*/
		foreach($visitor->includes() as $include)
		{
			$path = self::resolve_include($include, $file);

			if(is_null($path) || !is_file($path))continue;
			if(($real = realpath($path)) && isset($seen[$real]))continue;

			try
			{
				$entries = array_merge($entries,
					self::scan_source(file_get_contents($path), $path, true, $seen));
			}
			catch (\PhpParser\Error $e)
			{
				//an unparsable include only costs the seeAlso hint, it must not stop the file at hand
				continue;
			}
		}

		return $entries;
	}

	/* Mirrors the old cross_seek path handling: a bare filename is taken relative to the
	*  including file, anything holding a slash is used as given.
	*/
	private static function resolve_include(string $target, string $from) : ?string
	{
		if('' === trim($target))return null;

		if(false === strrpos($target, '/'))
		{
			$dir = dirname($from);
			return ('.' === $dir ? '' : $dir . '/') . $target;
		}

		return $target;
	}
}

class PHP_Ast_Scan_Visitor extends NodeVisitorAbstract
{
	private $file;
	private $entries = [];
	private $includes = [];
	private $printer;

	public function __construct(string $file)
	{
		$this->file = $file;
		$this->printer = new PrettyPrinter\Standard();
	}

	public function result() : array {return $this->entries;}

	/* literal include/require targets found in this file */
	public function includes() : array {return $this->includes;}

	/* Beschreibungen aus dem Dateikopf; gehen an die erste Klasse der Datei */
	private $head_desc = [];
	private $head_used = false;
	public function set_head_desc(array $entries) : void {$this->head_desc = $entries;}

	/* File_Scan counts lines from zero, the parser counts from one */
	private function add(string $tag, int $startLine, array $meta) : void
	{
		$this->entries[] = ['tag' => $tag, 'pos' => $startLine - 1, 'file' => $this->file, 'meta' => $meta];
	}

	public function enterNode(Node $node)
	{
		/* only literal targets can be followed; a path built at runtime was out of reach
		*  for the old scanner too
		*/
		if($node instanceof Node\Expr\Include_ && $node->expr instanceof Node\Scalar\String_)
			$this->includes[] = $node->expr->value;

		if($node instanceof Node\Stmt\Class_)     return $this->take_classlike($node, 'class');
		if($node instanceof Node\Stmt\Interface_) return $this->take_classlike($node, 'interface');
		if($node instanceof Node\Stmt\Trait_)     return $this->take_classlike($node, 'trait');

		return null;
	}

	private function take_classlike(Node\Stmt\ClassLike $node, string $kind)
	{
		//anonymous classes carry no name and were never picked up before either
		if(is_null($node->name))return null;

		$name = $node->name->toString();

		$meta = ['kind' => $kind, 'name' => $name, 'extends' => '', 'implements' => [],
			'abstract' => false, 'final' => false, 'desc' => $this->desc_entries($node)];

		//der Dateikopf gehoert der ersten Klasse, nicht jeder
		if(!$this->head_used)
		{
			$meta['desc'] = array_merge($this->head_desc, $meta['desc']);
			$this->head_used = true;
		}

		if($node instanceof Node\Stmt\Class_)
		{
			$meta['extends']    = $node->extends ? $node->extends->toString() : '';
			$meta['implements'] = array_map(fn($i) => $i->toString(), $node->implements);
			$meta['abstract']   = $node->isAbstract();
			$meta['final']      = $node->isFinal();
		}
		elseif($node instanceof Node\Stmt\Interface_)
		{
			//an interface extends a list, not a single parent; the first one keeps the old edge
			$parents = array_map(fn($i) => $i->toString(), $node->extends);
			$meta['extends']    = $parents[0] ?? '';
			$meta['implements'] = array_slice($parents, 1);
		}

		/* The tag keeps the shape a substring scanner would have produced, so the fallback
		*  dispatch in Obj_Class_Collection still recognises it.
		*/
		$tag = 'class ' . $name . ('' === $meta['extends'] ? '' : ' extends ' . $meta['extends']);
		$this->add($tag, $node->getStartLine(), $meta);

		/* Members are emitted here rather than in their own branch, so they can never
		*  appear before the class they belong to.
		*/
		foreach($node->stmts as $stmt)
		{
			if($stmt instanceof Node\Stmt\ClassMethod)
				$this->add($this->method_tag($stmt), $stmt->getStartLine(), $this->method_meta($stmt, $name));

			if($stmt instanceof Node\Stmt\Property)
				foreach($stmt->props as $prop)
					$this->add('property ' . $prop->name->toString(), $stmt->getStartLine(),
						$this->property_meta($stmt, $prop));

			if($stmt instanceof Node\Stmt\ClassConst)
				foreach($stmt->consts as $const)
					$this->add('constant ' . $const->name->toString(), $stmt->getStartLine(),
						$this->constant_meta($stmt, $const));
		}

		return null;
	}

	private function visibility(int $flags) : string
	{
		if($flags & Modifiers::PRIVATE)  return 'private';
		if($flags & Modifiers::PROTECTED)return 'protected';
		if($flags & Modifiers::PUBLIC)   return 'public';

		//no keyword at all - php treats it as public, but the source did not say so
		return 'implicit';
	}

	private function method_meta(Node\Stmt\ClassMethod $method, string $class) : array
	{
		$name = $method->name->toString();

		$params = [];
		foreach($method->params as $param)$params[] = $this->param_meta($param);

		//was einen Parameter beim Namen nennt, gehoert an den Parameter
		$desc = $this->route_param_desc($this->desc_entries($method), $params);

		return [
			'kind'       => 'method',
			'name'       => $name,
			/* Bewusst schreibungsempfindlich, wie die alte Regel: eine Klasse Filter darf
			*  eine gewoehnliche Methode filter() haben. Nur der gleich geschriebene Name
			*  ist der PHP-4-Konstruktor - sonst bekaeme die Klasse zwei Konstruktoren.
			*/
			'constructor'=> ($name === $class || '__construct' === $name),
			'visibility' => $this->visibility($method->flags),
			'static'     => $method->isStatic(),
			'abstract'   => $method->isAbstract(),
			'final'      => $method->isFinal(),
			'byRef'      => $method->byRef,
			'returnType' => $this->type_text($method->returnType),
			'params'     => $params,
			'desc'       => $desc,
		];
	}

	/** Haengt Beschreibungen an den Parameter, den sie beim Namen nennen.
	*
	*   "@parameter: tag_name = name for column" nennt sein Ziel selbst, vor dem "=".
	*   Trifft der Name einen Parameter der Methode, wandert der Eintrag dorthin und
	*   verliert den Namensvorspann - der steht ja jetzt am umgebenden Knoten.
	*
	*   Grossgeschriebene Namen wie COL oder DOCTYPE meinen keinen PHP-Parameter,
	*   sondern die Aufrufflaeche des Plugins. Sie treffen keinen Parameter und
	*   bleiben deshalb an der Methode - genau richtig, dort beschreiben sie das
	*   Werkzeug und nicht sein Argument.
	*
	*   @param $params wird veraendert
	*   @return die Eintraege, die an der Methode bleiben
	*/
	private function route_param_desc(array $desc, array &$params) : array
	{
		$index = [];
		foreach($params as $pos => $one)$index[$one['name']] = $pos;

		$rest = [];

		foreach($desc as $entry)
		{
			if('desc:parameter' !== $entry['tag']
				|| !isset($entry['text'])
				|| !preg_match('#^([A-Za-z_][A-Za-z_0-9]*)\s*=\s*(.*)$#s', $entry['text'], $hit)
				|| !isset($index[$hit[1]]))
			{
				$rest[] = $entry;
				continue;
			}

			$pos  = $index[$hit[1]];
			$text = trim($hit[2]);

			/* "[optional]" am Ende ist eine Angabe, keine Beschreibung. Bei Parametern
			*  ohne Vorgabewert ist es die einzige Quelle dafuer, also wird es zur
			*  Eigenschaft statt im Fliesstext zu bleiben.
			*/
			if(preg_match('#^(.*?)\s*\[optional\]\s*$#is', $text, $opt))
			{
				$text = trim($opt[1]);
				$params[$pos]['optional'] = true;
			}

			$params[$pos]['desc'][] = isset($entry['attrib'])
				? ['tag' => 'desc:text', 'text' => $text, 'attrib' => $entry['attrib']]
				: ['tag' => 'desc:text', 'text' => $text];
		}

		return $rest;
	}

	private function param_meta(Node\Param $param) : array
	{
		$refersTo = '';
		foreach($param->getComments() as $comment)
		{
			$body = trim($comment->getText());

			//the house notation for a preset: /* System.Parser */ before the parameter
			if(str_starts_with($body, '/*') && !str_starts_with($body, '/**'))
				$refersTo = trim(trim(substr($body, 2, -2)));
		}

		$name = ($param->var instanceof Node\Expr\Variable && is_string($param->var->name))
			? $param->var->name : 'param';

		return [
			'name'     => $name,
			'type'     => $this->type_text($param->type),
			'nullable' => ($param->type instanceof Node\NullableType),
			'byRef'    => $param->byRef,
			'variadic' => $param->variadic,
			'default'  => is_null($param->default) ? null : $this->printer->prettyPrintExpr($param->default),
			'refersTo' => $refersTo,

			//werden von route_param_desc gefuellt, wenn eine Beschreibung diesen Namen nennt
			'desc'     => [],
			'optional' => false,
		];
	}

	private function property_meta(Node\Stmt\Property $prop, Node\Stmt\PropertyProperty $one) : array
	{
		return [
			'kind'       => 'property',
			'name'       => $one->name->toString(),
			'visibility' => $this->visibility($prop->flags),
			'static'     => $prop->isStatic(),
			'readonly'   => $prop->isReadonly(),
			'type'       => $this->type_text($prop->type),
			'default'    => is_null($one->default) ? null : $this->printer->prettyPrintExpr($one->default),
		];
	}

	private function constant_meta(Node\Stmt\ClassConst $const, Node\Const_ $one) : array
	{
		return [
			'kind'       => 'constant',
			'name'       => $one->name->toString(),
			'visibility' => $this->visibility($const->flags),
			'value'      => $this->printer->prettyPrintExpr($one->value),
		];
	}

	/** Beschreibungen, die an einem Knoten haengen.
	*
	*   Zwei Formen, beide aus den Kommentaren, die der Parser dem Knoten zuordnet:
	*
	*   1. /*@ ... @* / — mehrzeiliger Text. Der Abschluss mit @ ist Pflicht,
	*      damit ein blosses /*@ mit gewoehnlichem Ende nicht mitgelesen wird. Darin
	*      gliedern Koepfe "name::" den Text, siehe block_entries().
	*   2. @schluessel: wert — die Form, die im Bestand schon rund 500 mal steht.
	*      Nur AUSSERHALB der Bloecke.
	*
	*   @return Liste von ['tag' => 'dcterms:title', 'text' => '...'] oder, fuer einen
	*           Verweis, ['tag' => 'desc:delivers', 'resource' => '#plugin']
	*/
	public function desc_entries(Node $node) : array
	{
		$res = [];

		foreach($node->getComments() as $comment)
		{
			$raw = $comment->getText();

			//Form 1: Block, Abschluss mit @ verpflichtend, darin Koepfe
			if(preg_match_all('#/\*@(.*?)@\*/#s', $raw, $blocks))
				foreach($blocks[1] as $block)
					$res = array_merge($res, $this->block_entries($this->clean_block($block)));

			/* Form 2 nur ausserhalb der Bloecke. Innen stand eine @schluessel:-Zeile sonst
			*  doppelt - als Aussage UND im Fliesstext des Blocks. Innen versteht
			*  block_entries() sie ohnehin als Kopf. Gemessen 2026-09-16: keine solche Zeile
			*  im Bestand, der Schnitt aendert nichts an vorhandenen Beschreibungen. */
			$raw = preg_replace('#/\*@.*?@\*/#s', '', $raw);

			//Form 2: zeilenweise Schluessel
			foreach(explode("\n", $raw) as $line)
			{
				//fuehrende Kommentarzeichen weg, dann @schluessel: rest
				$line = preg_replace('#^\s*(/\*+|\*+/?|//)\s*#', '', $line);

				if(!preg_match('#^@([a-zA-Z_]+)\s*:\s*(.*)$#', trim($line), $hit))continue;

				$key = strtolower($hit[1]);

				if(!isset(PHP_Ast_Scan::DESC_KEYS[$key]))continue;

				if('' !== ($text = trim($hit[2])))
					$res[] = ['tag' => PHP_Ast_Scan::DESC_KEYS[$key], 'text' => $text];
			}
		}

		return $res;
	}

	/* Zerlegt einen gesaeuberten Block an seinen Koepfen.
	*
	*  - Text vor dem ersten Kopf -> desc:text, wie bisher der ganze Block. Ein Block ohne
	*    Kopf ergibt also genau das, was er vorher ergab.
	*  - "name::" allein -> die folgenden Zeilen bis zum naechsten Kopf sind der Text.
	*  - "name:: wert" -> einzeilige Aussage. Hat der Wert die Form eines Verweises
	*    (#name oder <uri>, dieselben Formen, die der SPARQL-Parser als Begriff nimmt),
	*    wird er rdf:resource statt Text - "delivers:: #plugin" ist ein Verweis, keine
	*    Zeichenkette. Folgt einem Verweis noch Text, wird der ein eigenes desc:text.
	*  - "@schluessel: wert" gilt innen als Kopf, aber nur fuer bekannte Schluessel - wie
	*    Form 2 draussen. Unbekannt bleibt die Zeile Fliesstext.
	*/
	private function block_entries(string $text) : array
	{
		$res    = [];
		$tag    = null;
		$wert   = '';
		$attr   = [];
		$zeilen = [];

		/* Ein Eintrag bekommt 'attrib' nur, wenn es etwas zu sagen gibt - ohne Klammer
		*  sieht er aus wie vorher. */
		$mit = fn(array $eintrag, array $a) => $a ? $eintrag + ['attrib' => $a] : $eintrag;

		$abschluss = function() use (&$res, &$tag, &$wert, &$attr, &$zeilen, $mit)
		{
			$koerper = $this->dedent($zeilen);

			if(is_null($tag))
			{
				if('' !== $koerper)$res[] = ['tag' => 'desc:text', 'text' => $koerper];
				return;
			}

			if('' !== $wert && preg_match('#^(?:(\#[A-Za-z_][A-Za-z0-9_.\-]*)|<([^<>\s]+)>)$#', $wert, $v))
			{
				/* Ein Verweis hat in RDF keine Sprache - xml:lang gilt dann nur fuer den
				*  Text, der ihm folgt. */
				$am_verweis = $attr;
				if(isset($am_verweis['xml:lang']))
				{
					if('' === $koerper)
						self::melden('Kopf "' . $tag . '(lang=' . $am_verweis['xml:lang']
							. ')::" an einem Verweis - eine Ressource hat keine Sprache, weggelassen');
					unset($am_verweis['xml:lang']);
				}

				$res[] = $mit(['tag' => $tag, 'resource' => ('' !== $v[1]) ? $v[1] : $v[2]], $am_verweis);
				if('' !== $koerper)
					$res[] = $mit(['tag' => 'desc:text', 'text' => $koerper],
						isset($attr['xml:lang']) ? ['xml:lang' => $attr['xml:lang']] : []);
				return;
			}

			$inhalt = trim($wert . (('' !== $wert && '' !== $koerper) ? "\n" : '') . $koerper);

			if('' === $inhalt)
			{
				self::melden('Kopf "' . $tag . '::" ohne Inhalt - uebersprungen');
				return;
			}

			$res[] = $mit(['tag' => $tag, 'text' => $inhalt], $attr);
		};

		foreach(explode("\n", $text) as $zeile)
		{
			if(is_null($kopf = $this->block_head($zeile)))
			{
				$zeilen[] = $zeile;
				continue;
			}

			$abschluss();
			[$tag, $wert, $attr] = $kopf;
			$zeilen = [];
		}

		$abschluss();

		return $res;
	}

	/* [tag, wert, attribute] fuer eine Kopfzeile, sonst null. */
	private function block_head(string $zeile) : ?array
	{
		$t = trim($zeile);

		if(preg_match('#^([A-Za-z_][A-Za-z0-9_]*(?::[A-Za-z_][A-Za-z0-9_]*)?)(?:\(([^()]*)\))?::(?:\s+(.*))?$#', $t, $m))
		{
			$name = $m[1];
			$attr = $this->head_attribs($name, $m[2] ?? '');
			$m[2] = $m[3] ?? '';

			if(false !== ($p = strpos($name, ':')))
			{
				if(!in_array(strtolower(substr($name, 0, $p)), PHP_Ast_Scan::HEAD_PREFIXES, true))
				{
					self::melden('Kopf "' . $name . '::" mit unbekanntem Praefix - bleibt Fliesstext');
					return null;
				}
				return [$name, trim($m[2] ?? ''), $attr];
			}

			$key = strtolower($name);

			if(isset(PHP_Ast_Scan::DESC_KEYS[$key]))
				return [PHP_Ast_Scan::DESC_KEYS[$key], trim($m[2] ?? ''), $attr];

			if(!in_array($key, PHP_Ast_Scan::HEAD_KNOWN, true))
				self::melden('Kopf "' . $name . '::" ist kein bekannter Name - wird desc:' . $key
					. ' (Tippfehler?)');

			return ['desc:' . $key, trim($m[2] ?? ''), $attr];
		}

		if(preg_match('#^@([A-Za-z_]+)\s*:\s*(.*)$#', $t, $m) && isset(PHP_Ast_Scan::DESC_KEYS[strtolower($m[1])]))
			return [PHP_Ast_Scan::DESC_KEYS[strtolower($m[1])], trim($m[2]), []];

		return null;
	}

	/* Liest "lang=de, schluessel=wert" aus der Klammer eines Kopfes. Nur Schluessel aus
	*  HEAD_ATTRIBS werden Attribute; alles andere wird gemeldet und weggelassen - nicht
	*  still uebernommen. */
	private function head_attribs(string $kopf, string $klammer) : array
	{
		$res = [];
		if('' === trim($klammer))return $res;

		foreach(explode(',', $klammer) as $paar)
		{
			if('' === trim($paar))continue;

			if(!preg_match('#^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S*))\s*$#', $paar, $p))
			{
				self::melden('Kopf "' . $kopf . '(...)": "' . trim($paar) . '" ist kein schluessel=wert - weggelassen');
				continue;
			}

			$schluessel = strtolower($p[1]);
			$wert       = ($p[2] ?? '') . ($p[3] ?? '') . ($p[4] ?? '');

			if(!isset(PHP_Ast_Scan::HEAD_ATTRIBS[$schluessel]))
			{
				self::melden('Kopf "' . $kopf . '(...)": Angabe "' . $schluessel . '" ist unbekannt - weggelassen');
				continue;
			}

			/* Sprachkennung in der Form von BCP 47: de, en, de-DE. */
			if('lang' === $schluessel && !preg_match('#^[A-Za-z]{2,8}(-[A-Za-z0-9]{1,8})*$#', $wert))
			{
				self::melden('Kopf "' . $kopf . '(lang=' . $wert . ')": keine Sprachkennung - weggelassen');
				continue;
			}

			$res[PHP_Ast_Scan::HEAD_ATTRIBS[$schluessel]] = $wert;
		}

		return $res;
	}

	/* Zieht den gemeinsamen Vorspann ab und schneidet leere Randzeilen weg - der Text
	*  unter einem Kopf ist ueblicherweise eingerueckt, die Einrueckung ist keine Aussage. */
	private function dedent(array $zeilen) : string
	{
		while(count($zeilen) && '' === trim($zeilen[0]))   array_shift($zeilen);
		while(count($zeilen) && '' === trim(end($zeilen))) array_pop($zeilen);

		$breite = null;
		foreach($zeilen as $z)
		{
			if('' === trim($z))continue;
			$w = strlen($z) - strlen(ltrim($z));
			$breite = is_null($breite) ? $w : min($breite, $w);
		}

		if($breite)
			foreach($zeilen as $k => $z)
				$zeilen[$k] = substr($z, $breite);

		return implode("\n", $zeilen);
	}

	/* Einmal je Meldung und Lauf. Unbekannte Namen sind erlaubt (PEDL ist frei in seinen
	*  Beschreibungen) - still sollen sie aber nicht sein, sonst wird aus fucntion:: ein
	*  ordentliches desc:fucntion, und niemand merkt es. */
	private static function melden(string $text) : void
	{
		static $gemeldet = [];
		if(isset($gemeldet[$text]))return;
		$gemeldet[$text] = true;

		global $logger_class;
		if(is_object($logger_class))
			$logger_class->setAssert('PHP_Ast_Scan: ' . $text . ' (classes/handles/PHP_ast_scan.php)', 5);
	}

	/* Nimmt die Kommentar-Randzeichen weg und ruecke den Block aus, ohne die
	*  Zeilenumbrueche zu verlieren - die sind der Grund, warum das ein Kindknoten wird.
	*/
	private function clean_block(string $block) : string
	{
		$lines = [];

		foreach(explode("\n", $block) as $line)
			$lines[] = rtrim(preg_replace('#^\s*\*+\s?#', '', $line));

		while(count($lines) && '' === trim($lines[0]))          array_shift($lines);
		while(count($lines) && '' === trim(end($lines)))        array_pop($lines);

		/* Die Einrueckung des Blocks im Quelltext gehoert nicht zur Beschreibung.
		*  Abgezogen wird nur der gemeinsame Vorspann, damit Struktur innerhalb des
		*  Textes - Aufzaehlungen, eingerueckte Beispiele - erhalten bleibt.
		*/
		$indent = null;
		foreach($lines as $line)
		{
			if('' === trim($line))continue;
			$width = strlen($line) - strlen(ltrim($line));
			$indent = is_null($indent) ? $width : min($indent, $width);
		}

		if($indent)
			foreach($lines as $key => $line)
				$lines[$key] = substr($line, $indent);

		return implode("\n", $lines);
	}

	/* union, intersection and nullable types are rendered the way they are written */
	private function type_text($type) : string
	{
		if(is_null($type))return '';
		if($type instanceof Node\NullableType)return '?' . $this->type_text($type->type);
		if($type instanceof Node\UnionType)
			return implode('|', array_map(fn($t) => $this->type_text($t), $type->types));
		if($type instanceof Node\IntersectionType)
			return implode('&', array_map(fn($t) => $this->type_text($t), $type->types));

		return (string)$type;
	}

	private function method_tag(Node\Stmt\ClassMethod $method) : string
	{
		$params = [];

		foreach($method->params as $param)
		{
			$one = '';
			foreach($param->getComments() as $comment)
			{
				$body = trim($comment->getText());
				if(str_starts_with($body, '/*') && !str_starts_with($body, '/**'))$one .= $body . ' ';
			}
			$one .= ($param->byRef ? '&' : '') . '$';
			$one .= ($param->var instanceof Node\Expr\Variable && is_string($param->var->name))
				? $param->var->name : 'param';

			$params[] = $one;
		}

		return 'function ' . ($method->byRef ? '&' : '') . $method->name->toString()
			. '(' . implode(', ', $params) . ')';
	}
}
