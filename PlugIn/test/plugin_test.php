<?php
require_once("PlugIn/plugin_interface.php");

class Test extends plugin
{
    /** @var array<string,int> Start‑Zeitpunkte in Nanosekunden */
    private array $timers = [];

    /** @var array<string,int> Gemessene Dauern in Nanosekunden */
    private array $results = [];

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
}
