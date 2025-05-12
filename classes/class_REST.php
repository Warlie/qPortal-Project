<?php

class REST_Connection {

public const GET = 'GET';
public const POST = 'POST';
public const PUT = 'PUT';
public const HEAD = 'HEAD';
public const DELETE = 'DELETE';
public const PATCH = 'PATCH';
public const OPTIONS = 'OPTIONS';
public const CONNECT = 'CONNECT';
public const TRACE = 'TRACE';
	
    private $baseUrl = '';
    private $lastUrl = ''; // Die zuletzt tatsächlich aufgerufene URL
    private $lastMethod = 'GET';
    private $requestHeaders = []; // Zu sendende Header (Key-Value oder String-Array)
    private $requestParameters = []; // Zu sendende Parameter (GET/POST)
    private $requestBody = null; // Zu sendender Body

    // --- NEU: Eigenschaften zum Speichern der Antwort ---
    private $rawResponseBody = ''; // Roher Body der Antwort
    private $responseHeaders = []; // Empfangene Header (Key => Value/Array)
    private $responseInfo = []; // Ergebnisse von curl_getinfo()

    // Konstruktor (Beispielhaft angepasst)
    public function __construct(string $baseUrl = '') {
        $this->baseUrl = $baseUrl;
        // Ggf. Standard-Header hier setzen?
        // $this->setHeader('User-Agent', 'MyRestClient/1.0');
    }

    // Methoden zum Setzen von Request-Parametern (Beispiele)
    public function setMethod(string $method): self {
        $this->lastMethod = strtoupper($method);
        return $this;
    }

    // Methode zum Setzen von Headern (Beispiel - Akzeptiert Key/Value ODER ganze Zeile)
    public function setHeader(string $headerOrKey, ?string $value = null): self {
        if ($value === null && strpos($headerOrKey, ':') !== false) {
            // Ganze Zeile wurde übergeben (z.B. "Accept: application/json")
            // Speichere sie direkt für CURLOPT_HTTPHEADER
             $this->requestHeaders[] = $headerOrKey;
        } else if ($value !== null) {
            // Key-Value wurde übergeben
            // Format für CURLOPT_HTTPHEADER: "Key: Value"
             $this->requestHeaders[] = $headerOrKey . ': ' . $value;
        }
        // Deduplizieren wäre hier sinnvoll, wenn Key-Value genutzt wird
        // $normalizedKey = ucwords(strtolower($headerOrKey), '-');
        // $this->requestHeaders[$normalizedKey] = $headerOrKey . ': ' . $value;
        // Und dann am Ende $this->requestHeaders = array_values($this->requestHeaders);
        return $this;
    }
    
    public function setHeaderArray(array $header): self {
       	if(is_array($header)) {
        		foreach ($header as $key => $headerZeile) {
        			if(is_numeric($key))
        				$this->setHeader($headerZeile);
        			else
        				$this->setHeader($key, $headerZeile);
        		}
        }
    	
    	 return $this;
    }

    /**
     * Sets all request parameters at once from an array, overwriting any existing ones.
     *
     * @param array $params Associative array of parameters (key => value).
     * @return self To allow method chaining.
     */
    public function setParameters(array $params): self {
        $this->requestParameters = $params; // Überschreibt das gesamte Array
        return $this;
    }

    /**
     * Sets or updates a single request parameter.
     * Useful for adding or overriding parameters individually.
     *
     * @param string $key The name of the parameter.
     * @param mixed $value The value of the parameter.
     * @return self To allow method chaining.
     */
    public function setParameter(string $key, $value): self {
        $this->requestParameters[$key] = $value; // Fügt hinzu oder überschreibt den Schlüssel
        return $this;
    }

    public function setBody($body): self {
        $this->requestBody = $body;
        return $this;
    }

    /**
     * Führt den eigentlichen cURL Request aus.
     * (Kann auch deine bestehende request() Methode sein, die erweitert wird)
     *
     * @param string|null $url Optionale URL, überschreibt BaseUrl/letzte URL
     * @return self Chaining ermöglichen
     */
    public function request(?string $url = null): self {
    $targetUrl = '';

    if ($url === null || $url === '') {
        // Kein spezifischer URL übergeben, BaseUrl verwenden
        $targetUrl = $this->baseUrl;
    } elseif (preg_match('~^https?://~i', $url)) {
        // $url ist bereits eine absolute URL, direkt verwenden
        $targetUrl = $url;
    } else {
        // $url ist ein relativer Pfad, kombiniere mit BaseUrl
        // Stelle sicher, dass genau ein Slash dazwischen ist
        $targetUrl = rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
    }

    // Prüfen, ob am Ende eine gültige URL herauskam
    if (empty($targetUrl) || !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
         // Fehler: Keine gültige URL bestimmbar.
         // Hier könntest du eine Exception werfen oder einen Fehlerstatus setzen.
         // Fürs Debugging:
         error_log("Konnte keine gültige URL bestimmen. Base: '{$this->baseUrl}', UrlParam: '{$url}' Ergab: '{$targetUrl}'");
         // Setze einen Fehlerstatus oder wirf eine Exception
         // $this->setError("Invalid URL determined: {$targetUrl}"); // Beispiel
         return $this; // Frühzeitiger Ausstieg oder Exception
    }


    $this->lastUrl = $targetUrl; // Die letztendlich verwendete URL speichern

        // --- Reset für Response-Daten ---
        $this->rawResponseBody = '';
        $this->responseHeaders = []; // Wichtig: für jeden Request leeren
        $this->responseInfo = [];

        $ch = curl_init();

        // URL und Methode zusammenbauen (Parameter anhängen bei GET etc.)
        $processedUrl = $this->buildUrlWithParams();
        curl_setopt($ch, CURLOPT_URL, $processedUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->lastMethod); // Methode setzen
/*
        // Request Body setzen (falls vorhanden und Methode es erlaubt)
        if (!in_array($this->lastMethod, ['GET', 'HEAD', 'DELETE']) && $this->requestBody !== null) {
            // Ggf. Content-Type Header automatisch setzen, wenn Body JSON ist etc.?
            // if (is_array($this->requestBody) || is_object($this->requestBody)) { ... json_encode ... }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
        }
*/
        // --- MODIFICATION START ---

// Daten für den Request Body bestimmen (für POST/PUT/PATCH etc.)
$postDataString = null; // Initialisiere Variable für den Body-String

if (!in_array($this->lastMethod, ['GET', 'HEAD', 'DELETE'])) { // Prüfe auf Methoden, die einen Body haben können
    if ($this->requestBody !== null) {
        // 1. Expliziter Body wurde via setBody() gesetzt -> Vorrang geben
        //    Annahme: Der Body ist bereits im korrekten Format (z.B. JSON-String)
        if (is_string($this->requestBody)) {
             $postDataString = $this->requestBody;
        } else {
             // Körper ist kein String - Fehler loggen oder versuchen zu kodieren?
             error_log("REST_Connection Warning: Request body set via setBody() was not a string. Sending empty body.");
             $postDataString = ''; // Oder Exception werfen?
        }
        // WICHTIG: Der Benutzer MUSS den korrekten Content-Type Header (z.B. application/json)
        // für diesen expliziten Body selbst via setHeader() gesetzt haben!

    } elseif (!empty($this->requestParameters)) {
        // 2. Kein expliziter Body, aber Parameter wurden via setParameter(s) gesetzt
        //    -> Kodiere diese Parameter als JSON für den Body
        $postDataString = json_encode($this->requestParameters);
        // Optional: Prüfen ob json_encode erfolgreich war (sollte bei Arrays klappen)
        if ($postDataString === false) {
             error_log("REST_Connection Error: Failed to json_encode request parameters. Sending empty body. Error: " . json_last_error_msg());
             $postDataString = ''; // Oder Exception
        }

        // WICHTIG: Der Benutzer MUSS den Content-Type: application/json Header
        // selbst via setHeader() gesetzt haben, damit der Server dies korrekt interpretiert!
        // (Diese Klasse setzt ihn NICHT automatisch, um Flexibilität zu wahren)
    }
    // Optional: Wenn weder Body noch Parameter gesetzt sind, ist $postDataString weiterhin null (kein Body).
}

// Setze den Request Body in cURL, wenn Daten vorhanden sind
if ($postDataString !== null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataString);
}

// --- MODIFICATION END ---
/*
// --- VALIDATION START ---
// 1. JSON allgemein parsen
$payload = json_decode($postDataString, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new \RuntimeException('Ungültiges JSON im Request: ' . json_last_error_msg());
}

// 2. Nachrichten-Content validieren
foreach ($payload['messages'] as $i => $msg) {
    if (!isset($msg['content']) || !is_string($msg['content']) && !is_array($msg['content'])) {
        throw new \RuntimeException("Ungültiger Nachrichtentyp für messages[$i].content: " .
            gettype($msg['content']));
    }
}
// --- VALIDATION END ---
*/
// ... (Rest der request Methode: RETURNTRANSFER, HEADERFUNCTION, exec, getinfo, close) ...
        
        // Zu sendende Header setzen
        if (!empty($this->requestHeaders)) {
            // Stelle sicher, dass es ein Array von Strings ist
            $headerArray = [];
            foreach ($this->requestHeaders as $header) {
                if (is_string($header)) { // Nur Strings verwenden
                   $headerArray[] = $header;
                }
            }
             curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        }

        // --- Wichtig für Antwort-Verarbeitung ---
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Antwort als String zurückgeben
        curl_setopt($ch, CURLOPT_FAILONERROR, false); // Auch bei HTTP-Fehlercodes (4xx, 5xx) den Body holen

        // --- NEU: Response Header verarbeiten ---
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $headerLine) {
            $len = strlen($headerLine);
            $headerLine = trim($headerLine);

            if ($headerLine === '') {
                // Leere Zeilen ignorieren (Trenner zwischen Header/Body)
                return $len;
            }

            // Statuszeile separat speichern? (Optional)
            if (strpos(strtolower($headerLine), 'http/') === 0) {
                $this->responseHeaders['status_line'] = $headerLine;
            } else {
                // Reguläre Headerzeile (Name: Wert)
                $parts = explode(':', $headerLine, 2);
                if (count($parts) === 2) {
                    $headerName = trim($parts[0]);
                    $headerValue = trim($parts[1]);
                    // Schlüssel normalisieren (Title-Case) für konsistenten Zugriff
                    $normalizedName = ucwords(strtolower($headerName), '-');

                    // Header, die mehrmals vorkommen können (z.B. Set-Cookie), als Array speichern
                    if (isset($this->responseHeaders[$normalizedName])) {
                        if (!is_array($this->responseHeaders[$normalizedName])) {
                            // Ersten Wert in Array umwandeln
                            $this->responseHeaders[$normalizedName] = [$this->responseHeaders[$normalizedName]];
                        }
                        $this->responseHeaders[$normalizedName][] = $headerValue;
                    } else {
                        // Ersten Header als String speichern
                        $this->responseHeaders[$normalizedName] = $headerValue;
                    }
                } else {
                     // Optional: Fehlformatierte Header behandeln
                     // $this->responseHeaders['malformed'][] = $headerLine;
                }
            }
            return $len; // Wichtig: Anzahl verarbeiteter Bytes zurückgeben
        });

        // Request ausführen
        $this->rawResponseBody = curl_exec($ch);

        // Informationen sammeln (Status Code etc.)
        $this->responseInfo = curl_getinfo($ch);

// In der request() Methode, NACH $this->rawResponseBody = curl_exec($ch);

if (curl_errno($ch)) {
    $curlErrorMessage = curl_error($ch);
    $curlErrorCode = curl_errno($ch);
    // Fehler loggen oder anderweitig behandeln
    error_log("cURL Error ({$curlErrorCode}): {$curlErrorMessage} for URL: {$this->lastUrl}");
    // Optional: Eine Exception werfen oder einen Fehlerstatus setzen
    // throw new \RuntimeException("cURL Error ({$curlErrorCode}): {$curlErrorMessage}");

    // Wichtig: In diesem Fall sind $this->responseInfo etc. nicht zuverlässig.
    $this->responseInfo['http_code'] = 0; // Setze explizit 0 bei cURL-Fehler
} else {
    // Nur wenn kein cURL-Fehler auftrat, die Infos holen
    $this->responseInfo = curl_getinfo($ch);
}

        curl_close($ch);

        return $this; // Erlaubt Chaining: $client->request()->getStatusCode();
    }

    /**
     * Baut die URL mit GET-Parametern zusammen (vereinfacht).
     */
    private function buildUrlWithParams(): string {
         $url = $this->lastUrl;
         if (in_array($this->lastMethod, ['GET', 'HEAD', 'DELETE']) && !empty($this->requestParameters)) {
             $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($this->requestParameters);
         }
         return $url;
    }

    // --- NEU: Methoden zum Abrufen der Antwort ---

    /**
     * Gibt den rohen, unverarbeiteten Body der letzten Antwort zurück.
     *
     * @return string Der rohe Response Body.
     */
    public function getRaw(): string {
        // Prüfen, ob rawResponseBody überhaupt gesetzt ist (nach Fehler etc.)
        return $this->rawResponseBody ?? '';
    }

    /**
     * Gibt alle empfangenen Response Header als assoziatives Array zurück.
     * Schlüssel sind Header-Namen (Title-Case), Werte sind Strings oder Arrays von Strings.
     * Enthält ggf. auch 'status_line'.
     *
     * @return array Die Response Header.
     */
    public function getAllResponseHeaders(): array {
        return $this->responseHeaders;
    }

    /**
     * Gibt den Wert eines spezifischen Response Headers zurück.
     * Header-Namen sind case-insensitive. Gibt bei multiplen Headern den ersten zurück.
     *
     * @param string $headerName Name des Headers (z.B. 'Content-Type').
     * @return string|null Der Wert des Headers oder null, wenn nicht gefunden.
     */
    public function getResponseHeader(string $headerName): ?string {
        $normalizedName = ucwords(strtolower($headerName), '-'); // Suche nach Title-Case
        $value = $this->responseHeaders[$normalizedName] ?? null;

        if (is_array($value)) {
            return $value[0] ?? null; // Gib den ersten Wert zurück, wenn es ein Array ist
        }
        return $value; // Gib den String zurück oder null
    }

    /**
     * Gibt den HTTP-Statuscode der letzten Antwort zurück.
     *
     * @return int Der HTTP Status Code (z.B. 200, 404) oder 0 bei Fehlern.
     */
    public function getStatusCode(): int {
        return $this->responseInfo['http_code'] ?? 0;
    }

     /**
     * Gibt den Content-Type der letzten Antwort zurück.
     * Extrahiert aus curl_getinfo für Zuverlässigkeit.
     *
     * @return string|null Der Content-Type Header oder null.
     */
    public function getContentType(): ?string {
         // CURLINFO_CONTENT_TYPE ist oft zuverlässiger als manuelles Parsen
         return $this->responseInfo['content_type'] ?? null;
         // Alternative: return $this->getResponseHeader('Content-Type');
    }


    /**
     * Versucht, den rohen Response Body basierend auf dem Content-Type Header
     * der Antwort zu parsen und als Objekt/Array zurückzugeben.
     *
     * @param bool $assoc Wenn true und Content-Type JSON ist, wird ein assoziatives Array zurückgegeben.
     * @return mixed Das geparste Ergebnis (Objekt, Array) oder der rohe Body bei unbekanntem Typ oder Fehler.
     */
    public function getResult(bool $assoc = false) {
        $contentType = $this->getContentType(); // Hole Content-Type via curl_getinfo
        $body = $this->getRaw();

        if ($contentType && $body !== '') {
            // Extrahiere den reinen Typ (ohne "; charset=...")
            $contentTypeBase = strtolower(trim(explode(';', $contentType)[0]));

            switch ($contentTypeBase) {
                case 'application/json':
                    $decoded = json_decode($body, $assoc);
                    // Prüfe auf JSON-Fehler
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    } else {
                        // Optional: Fehler loggen oder signalisieren
                        // error_log("JSON Decode Error: " . json_last_error_msg());
                        return $body; // Fallback auf rohen Body bei Fehler? Oder null?
                    }
                    break; // Eigentlich unnötig nach return

                case 'application/xml':
                case 'text/xml':
                    // XML parsen (Fehlerbehandlung ist wichtig!)
                    libxml_use_internal_errors(true);
                    $xml = simplexml_load_string($body);
                    if ($xml !== false) {
                        libxml_clear_errors();
                        libxml_use_internal_errors(false); // Globalen Zustand wiederherstellen
                        return $xml; // Gibt SimpleXMLElement zurück
                    } else {
                        // Optional: XML-Fehler loggen
                        // $errors = libxml_get_errors(); libxml_clear_errors();
                        libxml_use_internal_errors(false);
                        return $body; // Fallback
                    }
                    break; // Unnötig

                // case 'text/plain':
                // case 'text/html':
                //    return $body; // Einfach als String zurückgeben
                //    break;

                // Füge weitere Typen hinzu, falls nötig (z.B. CSV parsen)
            }
        }

        // Fallback, wenn kein Content-Type bekannt oder Body leer oder Parsing fehlschlug/nicht implementiert
        return $body;
    }
}

?>