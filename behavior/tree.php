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
    $reg->_addLN('indextree');
    $reg->start = function($node, $obj, $event)
    {
    	
    		 $structur = $event->get_Result_Array();
    		 $listTreeNames = $structur["Attribute"];
    		 
    		 
    		 $node->send_messages(
    		 	["Identifire"=>"http://www.trscript.de/tree#first", "Command"=> ["Name"=> "start" ], "Attribute"=>[]]
				,new EventObject('',$node,$booh));
    		 
			 if(empty($listTreeNames))
    		 $node->send_messages(
    		 	["Identifire"=>"http://www.trscript.de/tree#final", "Command"=> ["Name"=> "start" ], "Attribute"=>[]]
				,new EventObject('',$node,$booh));
			 else
			    $node->send_messages(
    		 	["Identifire"=>"http://www.trscript.de/tree#tree", "Command"=> ["Name"=> "start" ], "Attribute"=>$listTreeNames]
				,new EventObject('',$node,$booh));
			 
    		//$node->hold_messages($event->get_Command(0,1),$obj) ;
			return true;
    };
    
    $reg->addLog(function($node, $obj, $event){return "start in " . $node->full_URI();}, 4);

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

?>