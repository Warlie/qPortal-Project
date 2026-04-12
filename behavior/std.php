<?PHP


try {
    $reg = $content->getRegObj();
    
    $reg->_useGeneral();
    
    
    $reg->__redirect_node= function($node, $obj, $event)
    {
    		$node->send_messages($event->get_Command(0,1),$obj) ;
			return true;
    };
    
$reg->addLog(function($node, $obj, $event){return "__redirect_node in " . $node->full_URI();}, 5);
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

			
			$value_obj->hold_messages($value,$obj) ;
		}

		 	$node->get_parser()->flash_result();

			}
			else
			throw new NotExistingBranchException( " $name does not exist");

			return true;
    };
    
    $reg->addLog(function($node, $obj, $event){return "__find_node in " . $node->full_URI();}, 5);
    
    $reg->__add_node = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$json = json_decode($structur['Command']['Attribute']['json'], true);

			$name    = $json['name'];
			$attrib  = $json['attribute'] ?? [];
			$text    = $json['text'] ?? null;

			// Resolve namespace and local name
			$posinStr = strpos($name, '#');
			if (false === $posinStr)
			{
				$new_node = $node->get_parser()->get_Object_of_Namespace($obj->get_requester()->get_Main_NS() . '#' . $name);
				$new_node->name = $name;
				$prefix_full  = $obj->get_requester()->get_Main_NS();
				$postfix_full = $name;
			}
			else
			{
				$prefix_full  = substr($name, 0, $posinStr);
				$postfix_full = substr($name, $posinStr + 1);
				$new_node = $node->get_parser()->get_Object_of_Namespace($name);
				$prefix = $node->get_parser()->get_Prefix($prefix_full, $node->get_idx());
				$new_node->name = (!is_null($prefix) && strlen($prefix) > 0)
					? $prefix . ':' . $postfix_full
					: $postfix_full;
			}

			$new_node->type      = $postfix_full;
			$new_node->namespace = $prefix_full;
			$new_node->set_idx($node->get_idx());

			// Add attributes
			foreach ($attrib as $key => $value)
			{
				$ns_qname = strpos($key, '#');
				if (false === $ns_qname)
				{
					$attrib_obj = $node->get_parser()->get_Object_of_Namespace($obj->get_requester()->get_Main_NS() . '#' . $key);
					$attrib_obj->setdata($value, 0);
					$new_node->attribute($key, $attrib_obj);
				}
				else
				{
					$attrib_obj = $node->get_parser()->get_Object_of_Namespace($key);
					$att_prefix  = $node->get_parser()->get_Prefix(substr($key, 0, $ns_qname), $node->get_idx());
					$att_postfix = substr($key, $ns_qname + 1);
					$attrib_obj->namespace = substr($key, 0, $ns_qname);
					$attrib_obj->type      = $att_postfix;
					$attrib_obj->setdata($value, 0);
					$attrib_obj->name = (strlen($att_prefix) > 0)
						? $att_prefix . ':' . $att_postfix
						: $att_postfix;
					$new_node->attribute($attrib_obj->name, $attrib_obj);
				}
				unset($attrib_obj);
			}

			// Register and attach
			$node->get_parser()->set_new_index($new_node);
			$cur_element = $node;
			$cur_element->setRefnext($new_node);
			$new_node->setRefprev($cur_element);

			// Optional text content
			if (!is_null($text))
				$new_node->setdata($text, 0);

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

    $reg->addLog(function($node, $obj, $event){return "__add_in_object:" . $node->full_URI() . " added into " . $obj->get_node()->full_URI();}, 5);
		
    $reg->__give_log= function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$node->get_contentGen()->switchOutput();

			$value = $structur['Command']['Value'];

			$node->hold_messages($value,$obj) ;

			return true;

		};

    $reg->__save_back = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$attrib   = $structur['Command']['Attribute'];
			$format   = $attrib['format'] ?? '';
			$file     = $attrib['file']   ?? false;
			$node->get_parser()->save_file($format, false, $file ?: false);
			return true;
		};
	$reg->addLog(fn($node, $obj, $event) => '__save_back: ' . $node->get_parser()->loaded_URI[$node->get_parser()->idx], 3);

    $reg->__set_data = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$value = $structur['Value'];
			
			$text = $obj->get_context();
			$position = $event->get_Command(0,1);

			if(is_array($value))
			{
				$text = $value['Text'];
				$position = $value['Position'];

			}
				
			$node->setdata($text, $position, alter_sensity: false);
			return true;
		};

	$reg->addLog(function($node, $obj, $event){return "__set_data:" . $obj->get_context(). " was add to " . $node->full_URI();}, 5);

		
    $reg->__insert_data = function($node, $obj, $event)
		{
			$node->setdata($obj->get_context(), $event->get_Command(0,1), true, false);
			return true;
		};

    $reg->__get_data = function($node, $obj, $event)
		{
			global $logger_class;
			if(!is_null($tmp = &$node->getdata($event->get_Command(0,1))))
			{
				$logger_class->setAssert($obj->get_requester()->full_URI() . " gets $tmp to its datapart " ,5);
				if(is_Object($tmp))
				{
					$obj->get_requester()->setdata($tmp, 0, false, false);
				}
				elseif(strlen($tmp) > 0)
				{
					$booh = $tmp;
					$obj->get_requester()->setdata($booh, 0, false, false);
				}
			}
			$logger_class->setAssert('__get_data of requester "' . $obj->get_requester()->full_URI() . '" was send to "' . $node->full_URI() . '" context is "' . $tmp . '"(std.php:__get_data)' ,5);
			return true;
		};

    $reg->__set_namespace = function($node, $obj, $event)
		{
			if(!is_null($tmp = &$node->getdata($event->get_Command(0,1))))
			{
				$node->namespace = $tmp;
			}
			return true;
		};

    $reg->__position_stamp = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$mode     = $structur['Attribute']['mode'] ?? 'relative';
			$parser   = $node->get_parser();
			$path     = '';
			$hash     = $node->position_hash_map($path);

			switch ($mode)
			{
				case 'internal':
					$node_idx = $node->get_idx();
					if ($node_idx == $parser->idx)           $idx_part = 'me';
					elseif ($node_idx == $parser->idx - 1)   $idx_part = 'prev';
					else                                      $idx_part = $node_idx;
					break;
				case 'absolute':
					$idx_part = '[' . $parser->loaded_URI[$node->get_idx()] . ']';
					break;
				case 'external':
					$filepath  = $parser->loaded_URI[$node->get_idx()];
					$iv        = random_bytes(openssl_cipher_iv_length(SECURITY_CIPHER));
					$encrypted = openssl_encrypt($filepath, SECURITY_CIPHER, hex2bin(SECURITY_STAMP_KEY), OPENSSL_RAW_DATA, $iv);
					$idx_part  = '[' . base64_encode($iv . $encrypted) . ']';
					break;
				default: // relative
					$idx_part = $node->get_idx();
					break;
			}

			$stamp = sprintf('%04d', $hash) . '.' . $idx_part . $path;
			$value = $structur['Command']['Value'];
			if (!empty($value))
			{
				$new_obj = new EventObject($obj->get_request(), $obj->get_requester(), $stamp);
				$node->hold_messages($value, $new_obj);
			}
			return true;
		};

    $reg->addLog(fn($node, $obj, $event) => '__position_stamp: ' . (function() use ($node, $event) {
		$mode   = $event->get_Result_Array()['Attribute']['mode'] ?? 'relative';
		$parser = $node->get_parser();
		$p = ''; $h = $node->position_hash_map($p);
		$idx_part = match($mode) {
			'internal' => ($node->get_idx() == $parser->idx ? 'me' : ($node->get_idx() == $parser->idx - 1 ? 'prev' : $node->get_idx())),
			'absolute' => '[' . $parser->loaded_URI[$node->get_idx()] . ']',
			default    => $node->get_idx(),
		};
		return sprintf('%04d', $h) . '.' . $idx_part . $p;
	})(), 5);

    $reg->__go_to_stamp = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$stamp    = $structur['Attribute']['stamp'] ?? $obj->get_context();
			if (!$node->get_parser()->go_to_stamp($stamp))
				throw new Exception('__go_to_stamp: could not resolve "' . $stamp . '"');
			$target = &$node->get_parser()->show_xmlelement();
			$obj->set_node($target);
			$value = $structur['Command']['Value'];
			if (!empty($value))
				$target->hold_messages($value, $obj);
			return true;
		};

    $reg->addLog(fn($node, $obj, $event) => '__go_to_stamp: ' . ($event->get_Result_Array()['Command']['Attribute']['stamp'] ?? $obj->get_context()), 5);

    $reg->__set_attribute = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$json = json_decode($structur['Command']['Attribute']['json'], true);
			$name = $json['name'];
			$value = $json['value'];
			if (strpos($name, '#') === false)
				$name = $obj->get_requester()->get_Main_NS() . '#' . $name;
			$node->set_ns_attribute($name, $value);
			return true;
		};

    $reg->__remove_attribute = function($node, $obj, $event)
		{
			$structur = $event->get_Result_Array();
			$json = json_decode($structur['Command']['Attribute']['json'], true);
			$node->remove_attribute($json['name']);
			return true;
		};

    $reg->__remove_node = function($node, $obj, $event)
		{
			return $node->removeNode();
		};

    $reg->__look_around = function($node, $obj, $event)
		{
			$value = $event->get_Result_Array()['Command']['Value'];
			if (!empty($value))
				$node->hold_messages($value, $obj);

			return true;
		};

    $reg->addLog(function($node, $obj, $event)
		{
			$children = [];
			for ($i = 0; $i < $node->index_max(); $i++)
				$children[] = $node->getRefnext($i)->full_URI();

			$prev = $node->getRefprev();
			$survey = "look_around:" 
				. 'act:' . $node->full_URI()
				. ';next:' . implode(',', $children)
				. ';prev:' . ($prev ? $prev->full_URI() : '');


			return $survey;
		}, 5);

} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage();
}

?>