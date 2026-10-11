<?PHP

/**
* @-------------------------------------------
* @title:QPDateTime
* @autor:Stefan Wegerhoff
* @description: Dekorator ueber einer Ergebnismenge: leitet aus einer Datumsspalte weitere
*               Spalten ab (Format, modify), alle anderen Spalten reicht er durch.
*
* Diese Fassung ist die unter [short] registrierte (plugin[QPDateTime]). Die Dokumente laden bis
* heute alle per src="PlugIn/plugin_datetime.php" die AELTERE Fassung derselben Klasse - beide in
* einem Request gaebe "Cannot redeclare class". Ueberarbeitet 2026-10-11. Behoben:
*  - fields() las $this->rst[0] - eine Ergebnismenge ist ein Objekt, kein Array (Absturz
*    "Cannot use object of type ... as array", gemessen)
*  - ohne modify stand in col() ein Zugriff auf einen fehlenden Schluessel (Warnung je Zeile)
*  - setsource fehlte -> der Vergleich mit '' griff nie (Vorgabe war null)
*  - toDateTime: ein misslungenes Datum warf ErrorException mit einem ARRAY als Meldung, und ein
*    ungueltiges (Monat 13) rechnete PHP still um - jetzt ein Fehler mit lesbarer Meldung
*  - ein LEERER Quellwert wurde zu "jetzt" (new DateTime('')); jetzt bleibt er leer
*/
require_once(__DIR__ . '/../plugin_interface.php');

class QPDateTime extends plugin
{

private $rel = array();
private $mod = array();
private $source = '';

	function __construct(){}

	/**
	* Setzt ein Datum aus Einzelteilen zusammen; fehlende (nicht numerische) Teile kommen von jetzt.
	* Ein zweistelliges Jahr wird mit "2010" aufgefuellt: 24 -> 2024.
	*/
	public function toDateTime($format, $year, $month, $day, $hour, $minute, $second)
	{
		$arg1 = '';
		$arg2 = '';
		if(is_numeric($year) )
		{
			$arg1 .= 'Y';
			$arg2 .= str_pad($year, 4, "2010", STR_PAD_LEFT);
		}
		if(is_numeric($month) )
		{
			$arg1 .= '-m';
			$arg2 .= '-' . str_pad($month, 2, "0", STR_PAD_LEFT);
		}
		if(is_numeric($day) )
		{
			$arg1 .= '-d';
			$arg2 .= '-' . str_pad($day, 2, "0", STR_PAD_LEFT);
		}
		if(is_numeric($hour) )
		{
			$arg1 .= ' H';
			$arg2 .= ' ' . str_pad($hour, 2, "0", STR_PAD_LEFT);
		}
		if(is_numeric($minute) )
		{
			$arg1 .= ':i';
			$arg2 .= ':' . str_pad($minute, 2, "0", STR_PAD_LEFT);
		}
		if(is_numeric($second) )
		{
			$arg1 .= ':s';
			$arg2 .= ':' . str_pad($second, 2, "0", STR_PAD_LEFT);
		}

		// Ungenanntes kommt von JETZT (so legt es plugin_datetimeTest fest: ohne Teile = aktuelle Zeit)
		$date = DateTimeImmutable::createFromFormat(trim($arg1), trim($arg2));
		// ohne Jahr prueft PHP Monat und Tag gar nicht ("-13-40" wird still zum 9. Februar) - also selbst
		$bad = (is_numeric($month) && (intval($month) < 1 || intval($month) > 12))
		    || (is_numeric($day) && !checkdate(is_numeric($month) ? intval($month) : 1, intval($day),
		                                       is_numeric($year) ? intval(str_pad($year, 4, "2010", STR_PAD_LEFT)) : intval(date("Y"))));
		// streng: auch eine WARNUNG ist ein Fehler - sonst rechnet PHP Monat 13 still in den Januar um
		$err = DateTimeImmutable::getLastErrors();
		if($bad || !$date || ($err && ($err['warning_count'] > 0 || $err['error_count'] > 0)))
		{
			$text = $err ? implode('; ', array_merge($err['errors'], $err['warnings'])) : '';
			throw new ErrorException('QPDateTime.toDateTime: "' . trim($arg2) . '" passt nicht zu "' . trim($arg1) . '" ' . $text);
		}
		return $date->format($format);
	}

	public function now($format)
	{
		$date = new DateTime('NOW');
		return $date->format($format);
	}

	/**
	* Eine abgeleitete Spalte (setcolumn) aus der Quelle; jede andere Spalte unveraendert.
	* Ist die Quelle kommagetrennt, sind es die Teile (Jahr, Monat, Tag, Stunde, Minute, Sekunde).
	*/
	public function col($columnname)
	{
		if(!$this->rst) return 'no dataset';

		if(!isset($this->rel[$columnname]))
			return $this->rst->col($columnname);

		if($this->source === '' || $this->source === null)
			throw new RuntimeException("QPDateTime: Source is missing");

		if(false === strpos($this->source, ','))
		{
			$raw = $this->rst->col($this->source);
			// leer ist leer - nicht "jetzt" (new DateTime('') waere der heutige Tag)
			if($raw === null || $raw === '' || $raw === false) return '';
			$date = new DateTime($raw);
			if(isset($this->mod[$columnname])) $date->modify($this->mod[$columnname]);
			return $date->format($this->rel[$columnname]);
		}

		$arr = explode(',', $this->source);
		$part = function($i) use ($arr) { return isset($arr[$i]) ? $this->rst->col($arr[$i]) : ''; };
		return $this->toDateTime($this->rel[$columnname],
			$part(0), $part(1), $part(2), $part(3), $part(4), $part(5));
	}

	/**
	*@parameter: ITER = gives out a object to LIST-parameter
	*/
	public function &iter(){ return $this; }

	public function setsource($column){ $this->source = $column; }
	public function setcolumn($column, $format, $modify = null){ $this->rel[$column] = $format; if($modify)$this->mod[$column] = $modify; }

	function getAdditiveSource(){;}
	public function moveFirst(){if($this->rst)return $this->rst->moveFirst(); else return false;}
	public function moveLast(){if($this->rst)return $this->rst->moveLast();else return false;}
	public function next(){if($this->rst)return $this->rst->next();else return false;}

	public function set_list(&$value)
	{
		if(is_object($value))
			$this->rst = &$value;
		else
			return 'no element received';
	}

	/** die Spalten der Quelle und die abgeleiteten */
	public function fields()
	{
		if(!$this->rst) return array();
		$base = method_exists($this->rst, 'fields') ? $this->rst->fields() : array();
		return array_values(array_unique(array_merge(is_array($base) ? $base : array(), array_keys($this->rel))));
	}

}
