<?PHP
/*
* _addNS($ns)
* _useNS($ns)
* _addLN($localName)
* _useLN($localName)
*/

try {
    $reg = $content->getRegObj();
    
    $reg->_addNS('http://www.trscript.de/tree');

    $reg->addNamespaceDescription(
		'Der Namensraum der Dokumentstruktur. Seine Knoten bilden das ausfuehrbare'
		. ' Geruest eines qPortal-Dokuments - Wurzel, Ausfuehrungsbereiche, Verzweigung,'
		. ' Unteraufrufe und Vorlagen. Nur ein Teil davon ist heute hier registriert, das'
		. ' uebrige Verhalten steht noch in den Klassen unter classes/ns/tree/.');

    $reg->_addLN('indextree');

    $reg->addLocalNameDescription(
		'Die Wurzel jedes qPortal-Dokuments. Hier kommt der Startbefehl an, der die'
		. ' Ausfuehrung ausloest.');
    $reg->start = function($node, $obj, $event)
    {

    		 $structur = $event->get_Result_Array();
    		 $listTreeNames = $structur["Attribute"];
    		 
    		 $node->send_messages(
    		 	["Identifire"=>"http://www.trscript.de/tree#first", "Command"=> ["Name"=> "start" ], "Attribute"=>[]]
				, $obj);

			 if(empty($listTreeNames))
    		 $node->send_messages(
    		 	["Identifire"=>"http://www.trscript.de/tree#final", "Command"=> ["Name"=> "start" ], "Attribute"=>[]]
				, $obj);
			 else
			    $node->send_messages(
    		 	["Identifire"=>"http://www.trscript.de/tree#tree", "Command"=> ["Name"=> "start" ], "Attribute"=>$listTreeNames]
				, $obj);
			 
    		//$node->hold_messages($event->get_Command(0,1),$obj) ;
			return true;
    };
    
    $reg->addLog(function($node, $obj, $event){return "start in " . $node->full_URI();}, 4);

    $reg->addDescription(
		'Startet die Ausfuehrung des Dokuments. Der Pfad steht im AEUSSEREN Attribute der'
		. ' Nachricht (neben Command, nicht darin) als Liste von tree-Namen. Leer laufen first'
		. ' und dann final; sonst wird der passende tree-Knoten gesucht, der seinen Namen vom'
		. ' Kopf der Liste nimmt und mit dem Rest weiterstartet. Ein Skript wird'
		. ' ausschliesslich durch start aktiv - ohne start liegt es still und ist ueber die'
		. ' Intern-Befehle editierbar. Jeden anderen Befehl reicht ein tree-Knoten unveraendert'
		. ' durch. Beispiel: {"Identifire":"http://www.trscript.de/tree#indextree",'
		. '"Command":{"Name":"start"},"Attribute":["fridge"]}',
		[]);

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

?>