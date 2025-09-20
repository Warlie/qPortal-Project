<?php
abstract class AbstactFactoryClass
{
    // Die abgeleitete Klasse zwingen, diese Methoden zu definieren
    abstract protected function getValue();
    abstract protected function prefixValue($prefix);

    // Gemeinsame Methode
    public function factory_Methode() 
    {
        print $this->getValue() . "\n";
    }
}
?>
