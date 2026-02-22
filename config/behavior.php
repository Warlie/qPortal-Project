<?PHP


try {
    $reg = $content->getRegObj();
    
    /*
    
    // Namespace 1 konfigurieren
    $reg->_addNS('http://trscript.de/tree')->_addLN('tree');
    $reg->program = function() { return "I am a program"; };
    
    // Namespace 2 konfigurieren
    $reg->_addNS('http://trscript.de/data')->_addLN('tree');
    $reg->record = function() { return "I am a record"; };
    
    // Zurückschalten und testen
    $reg->_useNS('http://trscript.de/tree')->_useLN('tree');
    echo "Test 1 (tree): " . ($reg->program)() . "\n";
    
    $reg->_useNS('http://trscript.de/data')->_useLN('tree');
    echo "Test 2 (data): " . ($reg->record)() . "\n";
    
    // Check isset
    echo "Has record? " . (isset($reg->record) ? 'Yes' : 'No') . "\n";
    echo "Has program here? " . (isset($reg->program) ? 'Yes' : 'No') . "\n";
    
    */

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

?>