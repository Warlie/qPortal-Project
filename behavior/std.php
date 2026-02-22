<?PHP


try {
    $reg = $content->getRegObj();
    
    $reg->_useGeneral();
    
    
    $reg->__redirect_node= function($node, $obj, $event)
    {
    		$node->send_messages($event->get_Command(0,1),$obj) ;
			return true;
    };
    /*
    	if($com_elemnet->matchesCommand('__redirect_node'))
		{
		$logger_class->setAssert('  Redirect was send to "' . $this->full_URI() . '"(Interface_node:event_message_check)' ,5);
			//$com_elemnet = $this->parseCommand($type);
			//echo $com_elemnet->get_Command(0,1) . ' ' . get_Class($obj->get_Node()) . ' ' . get_Class($this) . ' <br>';

			if(is_null($com_elemnet->get_Command(0,1)))throw new Exception("Null detected: " . $com_elemnet->get_Insert());
			

			$this->send_messages($com_elemnet->get_Command(0,1),$obj) ;
			return true;
			
		}
    */
    //__find_node : finds aspecific node and continues with next command
    $reg->__find_node= function($node, $obj, $event) 
    { 
    	$structur = $event->get_Result_Array();
    	
    	$json = json_decode($structur['Command']['Attribute']['json'], true);

    		$name = null;
			$attribute = null;
			$value = $structur['Command']['Value'];
			if(array_key_exists('attribute', $json))$attribute =  $json['attribute'];
			if(array_key_exists('name', $json))$name =  $json['name'];

			 $node->get_parser()->flash_result();
			// var_dump("name",$name, "attrib", $attribute);
			if($node->get_parser()->seek_node($name,$attribute) && (count($node->get_parser()->get_result())>0))
			{

			$obj->set_node($node);

		foreach( $node->get_parser()->get_result() as $value_obj){

			
			$value_obj->event_message_in($value,$obj) ;
		}

		 	$node->get_parser()->flash_result();

			}
			else
			throw new NotExistingBranchException( " $name does not exist");

			return true;
    };
    
    // adds get_node to 
    $reg->__add_in_object= function($node, $obj, $event) 
		{
			if(is_Object($myNode = &$obj->get_node()))
			{
				$myNode->addToOutListener($node);
			}
			
			
			return true;
			
		};

    

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

?>