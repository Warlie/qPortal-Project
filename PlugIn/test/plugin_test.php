<?php
require_once("PlugIn/plugin_interface.php");

class Test extends plugin
{
    /** @var array<string,int> Start‑Zeitpunkte in Nanosekunden */
    private array $timers = [];

    /** @var array<string,int> Gemessene Dauern in Nanosekunden */
    private array $results = [];
    
    //protected $rst = [];

    public function __construct()
    {
        // ggf. parent::__construct(...);
    }

    /**
     * Startet einen Timer unter dem gegebenen Label.
     */
    public function startTimer($label): void // string
    {
        $this->timers[$label] = hrtime(true);
    }

    /**
     * Stoppt den Timer für das Label und speichert die Dauer.
     */
    public function stopTimer($label): void //string 
    {
        if (!isset($this->timers[$label])) {
            throw new \RuntimeException("Timer ‚$label‘ wurde nicht gestartet.");
        }
        $duration = hrtime(true) - $this->timers[$label];
        $this->results[$label] = $duration;
        unset($this->timers[$label]);
    }

    /**
     * Gibt alle gemessenen Ergebnisse aus (Label + Dauer in ms).
     */
    public function showResults(): void
    {
    	if($this->rst)echo $this->render_plugin_table($this->rst);
        echo "Timer-Ergebnisse:\n";
        foreach ($this->results as $label => $ns) {
            $ms = $ns / 1_000_000; // Nanosekunden → Millisekunden
            printf("  %s: %.3f ms\n", $label, $ms);
        }
    }
    
     /**
     * Gibt alle gemessenen Ergebnisse aus (Label + Dauer in ms).
     */
    public function giveResults(): string
    {
    	$res = "Timer-Ergebnisse:\n";
        foreach ($this->results as $label => $ns) {
        	 
            $ms = $ns / 1_000_000; // Nanosekunden → Millisekunden
            $res .= sprintf("  %s: %.3f ms\n", $label, $ms);
        }
        
        return $res;
    }

    // Beispiel‑Test‑Methode
    public function test(): void
    {
        // Beispiel: zwei Bereiche messen
        $this->startTimer('Einlesen');
        // … dein Dateilese‑Code …
        $this->stopTimer('Einlesen');

        $this->startTimer('Verarbeiten');
        // … dein Verarbeitungs‑Code …
        $this->stopTimer('Verarbeiten');

        $this->showResults();
    }
    
    /**
 * Erzeugt eine Text-Tabelle aus einem Cursor-basierten Plugin-Objekt.
 *
 * @param object $obj Das Plugin (z.B. Hash oder Database)
 * @return string Die formatierte Tabelle
 */
function render_plugin_table($obj) {
    $output = "";
    $fields = $obj->fields(); // Holt die Spaltennamen

    if (empty($fields)) {
        return "Keine Spalten gefunden.\n";
    }

    // 1. Header erstellen
    $output .= implode("\t", $fields) . "\n";
    $output .= str_repeat("-", count($fields) * 8) . "\n";

    // 2. Daten iterieren
    // Wir setzen den internen Zeiger auf den Anfang
    if ($obj->moveFirst()) {
        do {
            $row = [];
            foreach ($fields as $fieldName) {
                // Wert der aktuellen Spalte abfragen
                $row[] = $obj->col($fieldName);
            }
            $output .= implode("\t", $row) . "\n";
            
        } while ($obj->next()); // Gehe zur nächsten Zeile, solange vorhanden
    } else {
        $output .= "Keine Daten vorhanden.\n";
    }

    return $output;
}
}
