<?php

/**  YAML handle — parses/serializes the YAML subset used by RenJS configs
*    (block maps, block lists, scalars with type inference, comments, quotes).
*    NOT supported: anchors/aliases, multiline scalars (| >), flow collections ([] {}).
*
*    Steckt in anderen Handles: extends JSON_handle so the resulting tree is
*    indistinguishable from a JSON document (same datatype attributes) —
*    downstream pipelines (XMLDO injection etc.) work unchanged.
*    save_back delegates to the XML handle like JSON_handle does.
*/

class YAML_handle extends JSON_handle
{

	function parse_document($source)
	{

		$this->base_object->MIME[$this->base_object->idx]['name'] = 'yaml';
		$this->base_object->MIME[$this->base_object->idx]['version'] = '1.2';
		$this->base_object->MIME[$this->base_object->idx]['encoding'] = 'UTF8';

		$yaml_array = self::yamlToArray($source);

		$native_attribute = array();

		if(is_null($this->base_object->NAMESPACES[$this->base_object->idx]) == 0)
			$native_attribute[] = $native_attribute['xmlns'] = 'yaml';
		else
		foreach ($this->base_object->NAMESPACES[$this->base_object->idx] as $key => $value)
			if('@main' == $key)
				$native_attribute['xmlns'] = $value;
			else
				$native_attribute['xmlns:' . $key ] = $value;


		$this->base_object->tag_open($this, 'yaml', $native_attribute );
		$this->parse_body($yaml_array);
		$this->base_object->tag_close($this, 'yaml');

	}

	/* like JSON_handle::parse_body, plus boolean/null awareness */
	function parse_body($setOfArrays, $arrayKey = null)
	{

		$attributes = [];
		$key2 = null;

			foreach ($setOfArrays as $key => $value)
			{

				if(is_bool($value))
					$attributes['datatype'] = "boolean";
				elseif(is_int($value))
					$attributes['datatype'] = "integer";
				elseif(is_float($value))
					$attributes['datatype'] = "number";
				elseif(is_string($value))
					$attributes['datatype'] = "string";

				if(is_array($value))
					foreach ($value as $key2 => $value2)
					{
						if($key2 == '@attributes')
							$attributes = $value2;

						if(is_numeric($key2))
						{
							// this could be an element to create an array
							$this->parse_body($value, $key);
							break;
						}
					}

				if(is_numeric($key2))
					break;

				if($key == '@attributes')
					continue;

				elseif(is_array($value))
					{
						if(!is_null($arrayKey)) $attributes['datatype'] = 'list_item';
						$this->base_object->tag_open($this, (is_null($arrayKey)? $key: $arrayKey), $attributes );
						$this->parse_body($value);
						$this->base_object->tag_close($this, (is_null($arrayKey)? $key: $arrayKey));
						$attributes = [];

					}
				else
					{
						$this->base_object->tag_open($this, (is_null($arrayKey)? $key: $arrayKey), $attributes );
						$this->base_object->cdata($this, self::scalarToText($value));
						$this->base_object->tag_close($this, (is_null($arrayKey)? $key: $arrayKey));
						$attributes = [];
					}

			}

	}

	function save_back($format,$send_header = false)
	{
		/* Handle in Handle: XML serialisiert den Baum, danach typisiert zurueck nach PHP */
		$handle = &My_Handle_factory::handle_factory('XML');
		$handle->set_object($this->base_object);
		$handle->set_attribute('XML_OPTION_CASE_FOLDING',false);
		$handle->set_attribute('XML_OPTION_ESCAPE_DQUOTE',false);

		$xml_output = $handle->save_back("UTF-8");

		$filechange = preg_replace('/>\s+</', '><', $xml_output);
		$filetrim = trim( $filechange);
		$resultxml = simplexml_load_string($filetrim);
		if(false === $resultxml)
			throw new \RuntimeException("Ungueltiges XML im YAML_handle");

		$typed = xmlNodeToPhp($resultxml, true); /* true: key-Attribut wird Map-Schluessel */

		return self::arrayToYaml($typed);
	}

	function save_stream_back(&$stream, $format,$send_header = false)
	{
		return (false !== fwrite($stream, $this->save_back($format)));
	}

	function send_header()
	{
		header("Content-Type: text/yaml; charset=utf-8");
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
	}


	/* ------------------------------------------------------------------ */
	/* YAML subset parser — static, damit ohne Baumobjekt testbar          */
	/* ------------------------------------------------------------------ */

	public static function yamlToArray($text)
	{
		$lines = [];
		foreach(preg_split('/\r?\n/', $text) as $raw)
		{
			$line = self::stripComment($raw);
			if(trim($line) === '') continue;
			$indent = strlen($line) - strlen(ltrim($line, ' '));
			$lines[] = ['i' => $indent, 'c' => trim($line)];
		}
		if(!count($lines)) return [];
		$pos = 0;
		return self::parseBlock($lines, $pos);
	}

	private static function isListLine($c)
	{
		return $c === '-' || str_starts_with($c, '- ');
	}

	private static function parseBlock(array &$lines, int &$pos)
	{
		$indent = $lines[$pos]['i'];
		$isList = self::isListLine($lines[$pos]['c']);
		$result = [];

		while($pos < count($lines)
			&& $lines[$pos]['i'] == $indent
			&& $isList == self::isListLine($lines[$pos]['c']))
		{
			$c = $lines[$pos]['c'];

			if($isList)
			{
				$rest = trim(substr($c, 1));
				if($rest === '')
				{
					$pos++;
					$result[] = ($pos < count($lines) && $lines[$pos]['i'] > $indent)
						? self::parseBlock($lines, $pos) : null;
					continue;
				}

				[$k, $v] = self::splitKV($rest);
				if($k === null){ $result[] = self::parseScalar($v); $pos++; continue; }

				/* Listeneintrag ist eine Map: "- key: value" + Folgezeilen */
				$item = [];
				if($v === null)
				{
					$pos++;
					$item[$k] = ($pos < count($lines) && $lines[$pos]['i'] > $indent + 2)
						? self::parseBlock($lines, $pos) : [];
				}
				else { $item[$k] = self::parseScalar($v); $pos++; }

				if($pos < count($lines) && $lines[$pos]['i'] > $indent
					&& !self::isListLine($lines[$pos]['c']))
					$item = array_merge($item, self::parseBlock($lines, $pos));

				$result[] = $item;
			}
			else
			{
				[$k, $v] = self::splitKV($c);
				if($k === null){ $result[] = self::parseScalar($v); $pos++; continue; }

				if($v === null)
				{
					$pos++;
					$result[$k] = ($pos < count($lines) && $lines[$pos]['i'] > $indent)
						? self::parseBlock($lines, $pos) : [];
				}
				else { $result[$k] = self::parseScalar($v); $pos++; }
			}
		}
		return $result;
	}

	/* Kommentar nur ausserhalb von Quotes kappen ("#FFFFFF" bleibt heil) */
	private static function stripComment($line)
	{
		$in = false; $q = '';
		$len = strlen($line);
		for($i = 0; $i < $len; $i++)
		{
			$ch = $line[$i];
			if($in){ if($ch === $q) $in = false; continue; }
			if($ch === '"' || $ch === "'"){ $in = true; $q = $ch; continue; }
			if($ch === '#' && ($i === 0 || ctype_space($line[$i-1])))
				return rtrim(substr($line, 0, $i));
		}
		return rtrim($line);
	}

	/* erste ':'-Trennstelle ausserhalb von Quotes; [key, value|null] oder [null, scalar] */
	private static function splitKV($line)
	{
		$in = false; $q = '';
		$len = strlen($line);
		for($i = 0; $i < $len; $i++)
		{
			$ch = $line[$i];
			if($in){ if($ch === $q) $in = false; continue; }
			if($ch === '"' || $ch === "'"){ $in = true; $q = $ch; continue; }
			if($ch === ':')
			{
				if($i + 1 >= $len)
					return [self::unquoteKey(substr($line, 0, $i)), null];
				if($line[$i+1] === ' ')
				{
					$v = trim(substr($line, $i + 2));
					return [self::unquoteKey(substr($line, 0, $i)), ($v === '' ? null : $v)];
				}
			}
		}
		return [null, $line];
	}

	/* "'y': 458" — Keys koennen gequotet sein (y/n sind YAML-1.1-Booleans) */
	private static function unquoteKey($k)
	{
		$k = trim($k);
		if(strlen($k) > 1 && $k[0] === '"' && str_ends_with($k, '"'))
			return stripcslashes(substr($k, 1, -1));
		if(strlen($k) > 1 && $k[0] === "'" && str_ends_with($k, "'"))
			return str_replace("''", "'", substr($k, 1, -1));
		return $k;
	}

	private static function parseScalar($s)
	{
		$s = trim($s);
		if($s === '') return '';
		if(strlen($s) > 1 && $s[0] === '"' && str_ends_with($s, '"'))
			return stripcslashes(substr($s, 1, -1));
		if(strlen($s) > 1 && $s[0] === "'" && str_ends_with($s, "'"))
			return str_replace("''", "'", substr($s, 1, -1));
		$l = strtolower($s);
		if($l === 'true'  || $l === 'yes') return true;
		if($l === 'false' || $l === 'no')  return false;
		if($l === 'null'  || $s === '~')   return null;
		if(preg_match('/^-?\d+$/', $s))        return (int)$s;
		if(preg_match('/^-?\d*\.\d+$/', $s))   return (float)$s;
		return $s;
	}


	/* ------------------------------------------------------------------ */
	/* Emitter                                                             */
	/* ------------------------------------------------------------------ */

	public static function arrayToYaml($data, $indent = 0)
	{
		$pad = str_repeat('  ', $indent);
		$out = '';

		if(!is_array($data))
			return $pad . self::scalarToYaml($data) . "\n";

		if(array_is_list($data))
		{
			foreach($data as $v)
			{
				if(is_array($v) && count($v))
				{
					if(array_is_list($v))
						$out .= $pad . "-\n" . self::arrayToYaml($v, $indent + 1);
					else
					{
						/* Map-Listeneintrag: erster Key hinter den Strich */
						$inner = rtrim(self::arrayToYaml($v, $indent + 1));
						$innerLines = explode("\n", $inner);
						$innerLines[0] = $pad . '- ' . ltrim($innerLines[0]);
						$out .= implode("\n", $innerLines) . "\n";
					}
				}
				elseif(is_array($v))
					$out .= $pad . "- []\n";
				else
					$out .= $pad . '- ' . self::scalarToYaml($v) . "\n";
			}
		}
		else
		{
			foreach($data as $k => $v)
			{
				if(is_array($v) && count($v))
					$out .= $pad . self::keyToYaml($k) . ":\n" . self::arrayToYaml($v, $indent + 1);
				elseif(is_array($v))
					$out .= $pad . self::keyToYaml($k) . ": []\n";
				else
					$out .= $pad . self::keyToYaml($k) . ': ' . self::scalarToYaml($v) . "\n";
			}
		}
		return $out;
	}

	/* quotet Keys, die sonst als YAML-1.1-Boolean/Zahl gelesen wuerden ('y', 'n', ...) */
	private static function keyToYaml($k)
	{
		$k = (string)$k;
		$l = strtolower($k);
		if($k === '' || in_array($l, ['y','n','yes','no','true','false','on','off','null','~'])
			|| preg_match('/^-?\d+(\.\d+)?$/', $k)
			|| preg_match('/[:#"\'\[\]{},]/', $k)
			|| $k !== trim($k))
			return "'" . str_replace("'", "''", $k) . "'";
		return $k;
	}

	private static function scalarToText($v)
	{
		if(is_bool($v)) return $v ? 'true' : 'false';
		if(is_null($v)) return '';
		return (string)$v;
	}

	private static function scalarToYaml($v)
	{
		if(is_bool($v))  return $v ? 'true' : 'false';
		if(is_null($v))  return 'null';
		if(is_int($v) || is_float($v)) return (string)$v;

		$s = (string)$v;
		$l = strtolower($s);

		$needsQuotes = ($s === '')
			|| preg_match('/^[#&*?|>%@`!,\[\]{}"\']/', $s)
			|| str_starts_with($s, '- ')
			|| $s === '-'
			|| str_contains($s, ': ')
			|| str_ends_with($s, ':')
			|| str_contains($s, ' #')
			|| preg_match('/^-?\d+(\.\d+)?$/', $s)
			|| in_array($l, ['true','false','null','yes','no','~'])
			|| $s !== trim($s);

		if($needsQuotes)
			return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $s) . '"';
		return $s;
	}

}

?>
