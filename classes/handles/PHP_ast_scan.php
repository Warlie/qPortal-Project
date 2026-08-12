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
			'abstract' => false, 'final' => false];

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

		return [
			'kind'       => 'method',
			'name'       => $name,
			'constructor'=> (0 === strcasecmp($name, $class) || '__construct' === $name),
			'visibility' => $this->visibility($method->flags),
			'static'     => $method->isStatic(),
			'abstract'   => $method->isAbstract(),
			'final'      => $method->isFinal(),
			'byRef'      => $method->byRef,
			'returnType' => $this->type_text($method->returnType),
			'params'     => $params,
		];
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
