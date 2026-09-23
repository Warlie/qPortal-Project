<?PHP

/**
*
*
* @-------------------------------------------
* @title:Mail
* @autor:Stefan Wegerhoff
* @description: verschickt eine Formularanfrage als Textmail ueber den Mailserver des
*               Webspace (PHP mail()). Kein Zugang, kein Passwort - der SPF-Eintrag der
*               Domain erlaubt den Hoster bereits.
*
*	Aufruf aus einem Dokument (Reihenfolge: erst die Felder, dann send):
*
*	  <object id="post" name="Mail" src="PlugIn/plugin_mail.php">
*	    <remote name="Mail.to">nsl@dws-sicherheit.de</remote>
*	    <remote name="Mail.from">noreply@dws-sicherheit.de</remote>
*	    <remote name="Mail.subject">Anfrage ueber die Website</remote>
*	    <remote name="Mail.reply_to"> … Request.out … </remote>
*	    <remote name="Mail.field.name">Unternehmen</remote>
*	    <remote name="Mail.field.value"> … Request.out … </remote>
*	    <remote name="Mail.field" />
*	    <remote name="Mail.trap.value"> … Request.out (Honigtopf) … </remote>
*	    <remote name="Mail.trap" />
*	    <remote name="Mail.send" />
*	  </object>
*
*	Gelesen wird wie eine Ergebnismenge, damit das Dokument verzweigen kann:
*	  col('status')   'ok' | 'fehler' | 'spam'
*	  col('meldung')  ein Satz fuer den Besucher (nie eine technische Meldung)
*	out() gibt dasselbe wie col('status').
*
*	⚠ KEIN echo. Alles, was hier schiefgeht, geht in $logger_class->setAssert() -
*	  eine Ausgabe mitten in der Antwort zerstoert jede Serialisierung.
*
*	⚠ KOPFZEILEN-EINSCHLEUSUNG: jeder Wert, der in einen Header geht (from, reply_to,
*	  subject), wird an \r und \n abgeschnitten. Wer das weglaesst, laesst einen
*	  Besucher beliebige Empfaenger in die Mail schreiben.
*/
require_once("plugin_interface.php");

class Mail extends plugin
{
	var $tag;

	private $to        = '';
	private $from      = '';
	private $reply_to  = '';
	private $subject   = 'Anfrage ueber die Website';
	private $felder    = array();      /* Beschriftung => Wert, in Reihenfolge */
	private $ist_spam  = false;
	private $status    = '';           /* '' = noch nicht verschickt */
	private $meldung   = '';

	function __construct(){}

	/* ---------------------------------------------------------------- setzen */

	public function to($value)       { $this->to       = $this->kopfzeile($value); return true; }
	public function from($value)     { $this->from     = $this->kopfzeile($value); return true; }
	public function reply_to($value) { $this->reply_to = $this->kopfzeile($value); return true; }
	public function subject($value)  { $this->subject  = $this->kopfzeile($value); return true; }

	/**
	*	Ein Feld der Anfrage. name ist die Beschriftung in der Mail, value der Wert
	*	aus dem Formular. Ein leeres Feld wird uebersprungen - eine leere Zeile in
	*	der Mail sagt nichts.
	*/
	public function field($name, $value)
	{
		$name  = trim((string) $name);
		$value = trim((string) $value);

		if($name === '' || $value === '')
			return false;

		$this->felder[$name] = $value;
		return true;
	}

	/**
	*	Honigtopf: ein Feld, das im Formular versteckt ist und deshalb leer bleibt.
	*	Steht etwas darin, hat es ein Automat ausgefuellt. Die Anfrage wird dann
	*	NICHT verschickt, dem Besucher aber auch nicht widersprochen - wer den
	*	Automaten schickt, soll nicht lernen, woran er gescheitert ist.
	*/
	public function trap($value)
	{
		if(trim((string) $value) !== '')
			$this->ist_spam = true;

		return true;
	}

	/* -------------------------------------------------------------- schicken */

	public function send()
	{
		global $logger_class;

		if($this->ist_spam)
		{
			$this->melde('spam', 'Vielen Dank, Ihre Anfrage ist eingegangen.');
			$this->notiere('Mail: Honigtopf gefuellt, nicht verschickt', 5);
			return true;
		}

		if(count($this->felder) == 0)
		{
			$this->melde('fehler', 'Bitte fuellen Sie das Formular aus.');
			return false;
		}

		if(!filter_var($this->to, FILTER_VALIDATE_EMAIL))
		{
			$this->melde('fehler', 'Die Anfrage konnte nicht zugestellt werden. Bitte rufen Sie uns an.');
			$this->notiere('Mail: kein gueltiger Empfaenger ("' . $this->to . '")', 0);
			return false;
		}

		if(!filter_var($this->from, FILTER_VALIDATE_EMAIL))
		{
			$this->melde('fehler', 'Die Anfrage konnte nicht zugestellt werden. Bitte rufen Sie uns an.');
			$this->notiere('Mail: kein gueltiger Absender ("' . $this->from . '")', 0);
			return false;
		}

		$header   = array();
		$header[] = 'From: ' . $this->from;
		$header[] = 'Content-Type: text/plain; charset=UTF-8';
		$header[] = 'Content-Transfer-Encoding: 8bit';
		$header[] = 'X-Mailer: qPortal';

		/* Antwort-an nur, wenn der Besucher eine brauchbare Adresse hinterlassen hat -
		*  sonst antwortet der Bearbeiter versehentlich ins Leere. */
		if(filter_var($this->reply_to, FILTER_VALIDATE_EMAIL))
			$header[] = 'Reply-To: ' . $this->reply_to;

		$gesendet = mail($this->to,
		                 $this->betreff_kodiert(),
		                 $this->rumpf(),
		                 implode("\r\n", $header));

		if($gesendet)
		{
			$this->melde('ok', 'Vielen Dank, Ihre Anfrage ist eingegangen. Wir melden uns bei Ihnen.');
			$this->notiere('Mail: Anfrage an ' . $this->to . ' uebergeben', 5);
			return true;
		}

		$this->melde('fehler', 'Die Anfrage konnte nicht zugestellt werden. Bitte rufen Sie uns an: 02191 6080010.');
		$this->notiere('Mail: mail() hat abgelehnt (Empfaenger ' . $this->to . ')', 0);
		return false;
	}

	/* ----------------------------------------------------------------- lesen */

	public function moveFirst() { return $this->status !== ''; }
	public function next()      { return false; }

	public function col($columnName)
	{
		if($columnName == 'status')  return $this->status;
		if($columnName == 'meldung') return $this->meldung;

		return false;
	}

	public function fields()
	{
		return array('status', 'meldung');
	}

	public function &out()
	{
		$this->out = $this->status;
		return $this->out;
	}

	public function check_type($type)
	{
		return parent::check_type($type);
	}

	public function decription(){ return "verschickt eine Formularanfrage als Textmail"; }

	/* ------------------------------------------------------------- innendrin */

	/**
	*	Ein Wert, der in eine Kopfzeile geht, endet am ersten Zeilenumbruch.
	*	Alles dahinter waere eine eigene Kopfzeile - genau so schleust man
	*	fremde Empfaenger ein.
	*/
	private function kopfzeile($value)
	{
		$value = (string) $value;
		$schnitt = strcspn($value, "\r\n");

		return trim(substr($value, 0, $schnitt));
	}

	/**
	*	Ein Betreff mit Umlauten gehoert kodiert, sonst zeigt ihn mancher
	*	Klient als Buchstabensalat.
	*/
	private function betreff_kodiert()
	{
		if(preg_match('/[\x80-\xFF]/', $this->subject))
			return '=?UTF-8?B?' . base64_encode($this->subject) . '?=';

		return $this->subject;
	}

	/**
	*	Der Rumpf: je Feld eine Beschriftung und darunter der Wert. Mehrzeilige
	*	Werte bleiben mehrzeilig, ein Absatz trennt die Felder.
	*/
	private function rumpf()
	{
		$zeilen = array('Anfrage ueber die Website', str_repeat('-', 40), '');

		foreach($this->felder as $name => $wert)
		{
			$zeilen[] = $name . ':';
			$zeilen[] = $wert;
			$zeilen[] = '';
		}

		$zeilen[] = str_repeat('-', 40);
		$zeilen[] = 'Eingegangen am ' . date('d.m.Y') . ' um ' . date('H:i') . ' Uhr.';

		return implode("\r\n", $zeilen);
	}

	private function melde($status, $meldung)
	{
		$this->status  = $status;
		$this->meldung = $meldung;
	}

	private function notiere($text, $stufe)
	{
		global $logger_class;

		if(is_object($logger_class))
			$logger_class->setAssert($text, $stufe);
	}
}
?>
