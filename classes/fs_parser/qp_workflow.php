<?php
use Finite\Elements\{Acceptor,TransitionType , Transducer, TransduceProjectionBehavior, TransduceInformationHarvest};

class qp_workflow extends Transducer
{
	private $node_URI;
	private $commands = array();
	private Acceptor $acceptor;
	private $pos_command = -1;
	private $current_node = ["node" => "undefined", "attribute" => array()];
	private $node_stack = array(["node" => "undefined", "attribute" => array()]);
	protected $listOfInformation = ["Identifire" => '', "Command" => ['Name' => null, 'Attribute' => [], 'Value'=> null]];
	private $attributePieces = [];
	
    
	function __construct($command) 
	{

	$this->acceptor = 
	$Acceptor     = new Acceptor(array(
    'class'       => 'Acceptor',
    'states'      => array(
    	'Start'    => array(
            'type'       => Finite\State\StateInterface::TYPE_INITIAL,
            'properties' => [ Acceptor::createTransition('',TransitionType::PassByDefault, 'to_identifire', 0 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::NoResult, array('node_name' => 'Identifire')))]
        ),
        'Identifire'    => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => [ Acceptor::createTransition('/^(.*?)(?=\?)/',TransitionType::PassByHit, 'to_questionmark', 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::NextNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Command', 'var_name' => 'valueX')))
            , Acceptor::createTransition('/^(.*?)(?=$)/',TransitionType::PassByHit, 'to_end' , 0 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::EndsNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Command', 'var_name' => 'valueY')))]
        ),
        'Command' => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => [ Acceptor::createTransition('/([A-Za-z0-9_]+)(?=\()/',TransitionType::PassByHit, 'to_open_bracet' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::ContinuesNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Command', 'var_name' => 'valuea')))
            	, Acceptor::createTransition('/([A-Za-z0-9_\s]*)(?==)/',TransitionType::PassByHit, 'to_equal' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::ContinuesNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Command', 'var_name' => 'valueb')))
            	, Acceptor::createTransition('/(.*?)(?=$)/',TransitionType::PassByHit, 'to_end' , 0 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::EndsNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Command', 'var_name' => 'valuec')))],
        ),
        'Bracet' => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => [ Acceptor::createTransition('',TransitionType::PassByDefault, 'to_parameters', 0 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::StartsNode, TransduceInformationHarvest::NoResult, array('node_name' => 'Parameter', 'var_name1' => 'blub')))],
        ),
        'Parameters' => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => [ Acceptor::createTransition('/([A-Za-z0-9_]+)(?==)/',TransitionType::PassByHit, 'to_param_equal' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::ContinuesNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Parameter', 'var_name' => 'paramID')))],
        ),
        'Parameter' => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => [Acceptor::createTransition('/([A-Za-z0-9\'_=]+)(?=,)/',TransitionType::PassByHit, 'to_param_comma' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::NextNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Parameter', 'var_name' => 'value')))
            	, Acceptor::createTransition('/([A-Za-z0-9\'_=]+)(?=\))/',TransitionType::PassByHit, 'to_close_bracet' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::EndsNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Parameter', 'var_name' => 'value2')))],
        ),
        'Value' => array(
            'type'       => Finite\State\StateInterface::TYPE_NORMAL,
            'properties' => [Acceptor::createTransition('/(.*?)(?=&|,)/',TransitionType::PassByHit, 'to_comma' , 1 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::NextNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Command', 'var_name' => 'value3')))
            , Acceptor::createTransition('/(.*?)(?=$)/',TransitionType::PassByHit, 'to_end' , 0 , 
            		Transducer::createTransduce( TransduceProjectionBehavior::EndsNode, TransduceInformationHarvest::ReturnsResult, array('node_name' => 'Command', 'var_name' => 'value4')))]
        ),
        'EOF' => array(
            'type'       => Finite\State\StateInterface::TYPE_FINAL,
            'properties' => [],
        )
    ),
    'transitions' => array(
    	'to_identifire' => array('from' => array('Start'), 'to' => 'Identifire'),
        'to_questionmark' => array('from' => array('Identifire'), 'to' => 'Command'),
        'to_open_bracet'  => array('from' => array('Command'), 'to' => 'Bracet'),
        'to_parameters'  => array('from' => array('Bracet'), 'to' => 'Parameters'),
        'to_close_bracet'  => array('from' => array('Parameter'), 'to' => 'Command'),
        'to_param_equal'  => array('from' => array('Parameters'), 'to' => 'Parameter'),
        'to_param_comma'  => array('from' => array('Parameter'), 'to' => 'Parameters'),
        'to_equal'  => array('from' => array('Command'), 'to' => 'Value'),
        'to_comma'  => array('from' => array('Value'), 'to' => 'Command'),
        'to_end'  => array('from' => array('Command', 'Value', 'Identifire'), 'to' => 'EOF')
    )
), $this);




    			$Acceptor->setStringToCheck($command);
    			$Acceptor->initialize();
/*    			
    					$result;
		$command;
		$value;
		$param;

			if(!(false === ($tmp = strpos($command,'?'))))
			{
				$this->node_URI = substr($command,0,$tmp);
				$commandstr = substr($command,$tmp + 1);

				$result = explode('&',$commandstr);
				
				
				
				for($i = 0;$i<count($result);$i++)
				{

					// Regulärer Ausdruck, um den Text zwischen Klammern zu extrahieren
					preg_match('/\((.*?)\)/', $result[$i], $param);
					// Regulärer Ausdruck, um den Text hinter dem letzten Gleichheitszeichen zu extrahieren
					preg_match('/=([^=]*)$/', $result[$i], $value);
					// Regulärer Ausdruck, um den Text vor einem Gleichheitszeichen oder einer offenen Klammer zu extrahieren
					preg_match('/([^=()]*)(?:=|\()/', $result[$i], $command);
					

					if($command == '__redirect_node')
					{
						$value = base64_decode(rawurldecode($value));

					}
					
					$this->commands[$i] = array();
					$this->commands[$i][] = $command[1];
					if(! strpos($value[1],')') )$this->commands[$i][] = $value[1]; else $this->commands[$i][] = '';
					$this->commands[$i][] = $param[1];
					
				}
var_dump($this->commands, "Command");
			}
			*/
	}
	
//	public function get_URI(){return $this->node_URI;}
//	public function get_Command($num,$index){return $this->commands[$num][$index];}
	
	function internal_open_node($parser, $node_name, $attribute){
	
	
			
	if(array_key_exists('node_name', $attribute)){
	$this->current_node = ["node" => $attribute['node_name'], "attribute" => &$attribute];
	array_push($this->node_stack , $this->current_node );
		
		unset($attribute['node_name']);
		//var_dump('internal_open', $this->current_node, $this->node_stack);
		//$this->open_node($this->current_node['node'], $this->current_node['attribute']);
	$this->open_node(...$this->current_node);
	}
	else
echo "MISSING NODENAME!" . $node_name;
	
	//$this->open_node($parser, $node_name, $attribute);
	}
	
	
	function internal_c_data($node_name,$data){
	//var_dump($node_name,$data);
		//var_dump('wub', $node_name, $this->current_node);
		//var_dump('internal_c_data', $this->current_node, $this->node_stack);
		if(!is_array($this->current_node))throw new ErrorException('needs an array');
		//echo "this is node in c_data: $node_name\n";
		$this->c_data(...$this->current_node, data: $data );
	
	//$this->c_data($node_name,$data);
	}
	
	
	function internal_close_node($parser, $node_name){
		
					$this->close_node(...$this->current_node);
	$tmp =  array_pop($this->node_stack);
	//echo "close_node=" . $tmp['node']  . " \n";
	$this->current_node = current($this->node_stack);

//var_dump('internal_close', $this->current_node, $this->node_stack);

			


		
	//$this->close_node($parser, $node_name);
	}


	public function open_node($node, $attribute){}
	
	// TODO When parsed the Parameter section, it returns the wrong name "undefined" 
	public function c_data($node, $attribute, $data)
	{

		switch ($node) {
		case 'Identifire':
			$this->listOfInformation["Identifire"] = trim($data);
			break;
		case 'Command' :
			if($this->listOfInformation["Command"]['Name'] == '')
				$this->listOfInformation["Command"]['Name'] = trim($data);
			else
			{
				if($this->listOfInformation["Command"]['Name'] == '__redirect_node')
					$this->listOfInformation["Command"]['Value'] = base64_decode(rawurldecode(trim($data)));
				else
					$this->listOfInformation["Command"]['Value'] = trim($data);
			}
			break;
		case 'Parameter' :
			$this->attributePieces[] = trim($data);
			break;
		case 'undefined' :
			$this->listOfInformation["Command"]['Value'] = trim($data);
			break;
		}
	
	}
public function close_node($node, $attribute){
	if(count($this->attributePieces) == 2)
	$this->listOfInformation["Command"]['Attribute'][$this->attributePieces[0]] = $this->attributePieces[1];
	$this->attributePieces = [];
		//$this->listOfInformation["Command"]['Attribute'][$this->attributePieces[0]] = $this->attributePieces[1];
	}

	
	public function __debugInfo()
	{
		return $this->listOfInformation;
	}
	
	public function test_open_node($node, $attribute)
	{
		echo "\n========================open_node ($node)=====================\n"; var_dump($attribute);echo "\n=================================\n";
	}

	
	public function test_c_data($node, $attribute, $data){echo "data($node)='$data'\n";var_dump( $attribute);}
	public function test_close_node($node, $attribute){echo "\n-----------close_node ($node)------------\n";}

	
	/*
	public function open_node($parser, $node_name, $attribute)
	{
		array_push($this->node_stack, $attribute['node_name']);
		$this->current_node = $attribute['node_name'];
		
		echo "open_node=" . $this->current_node . " \n";
		//var_dump($this->current_node);
		if($node_name == 'Command')$this->pos_command++;
		
	}
	public function c_data($node_name, $data){
		echo $data . "(" . $this->current_node . ")\n";
			switch ($node_name)
			{
			case 'Identifire' :
				$this->node_URI = $data;
				
				break;
			case 'Command':
				$this->commands[$this->pos_command][0] = $data;
				break;
			case 'Value':
				
					if($this->commands[$this->pos_command][0] == '__redirect_node')
					{
						$this->commands[$this->pos_command][1] = base64_decode(rawurldecode($data));
					}
					else
						$this->commands[$this->pos_command][1] = $data;				
				break;
				
			}
}
	public function close_node($parser, $node_name){
	
	
	echo "close_node=" . array_pop($this->node_stack) . " \n";
	$this->current_node = current($this->node_stack);
	}
*/
	
}

/*


		$result;
		$command;
		$value;
		$param;

			if(!(false === ($tmp = strpos($command,'?'))))
			{
				$this->node_URI = substr($command,0,$tmp);
				$commandstr = substr($command,$tmp + 1);

				$result = explode('&',$commandstr);
				
				
				
				for($i = 0;$i<count($result);$i++)
				{

					// Regulärer Ausdruck, um den Text zwischen Klammern zu extrahieren
					preg_match('/\((.*?)\)/', $result[$i], $param);
					// Regulärer Ausdruck, um den Text hinter dem letzten Gleichheitszeichen zu extrahieren
					preg_match('/=([^=]*)$/', $result[$i], $value);
					// Regulärer Ausdruck, um den Text vor einem Gleichheitszeichen oder einer offenen Klammer zu extrahieren
					preg_match('/([^=()]*)(?:=|\()/', $result[$i], $command);
					

					if($command == '__redirect_node')
					{
						$value = base64_decode(rawurldecode($value));

					}
					
					$this->commands[$i] = array();
					$this->commands[$i][] = $command[1];
					if(! strpos($value[1],')') )$this->commands[$i][] = $value[1]; else $this->commands[$i][] = '';
					$this->commands[$i][] = $param[1];
					
				}
var_dump($this->commands, "Command");
			}


public function is_Node($name)
{
	//echo $name . "=" . $this->full_URI() . "\n";

	if(!(false === ($tmp = strpos($name,'?'))))
	{
		
		$name = substr($name,0,$tmp);
	}
	
	if(!(false === ($tmp = strpos($name,'*'))))
	{
		
		if( substr($name,0,$tmp - 1) == $this->get_NS())return true;
	}
	
	if($name == '')return true;
	if($name == '*')return true;
	if($name == $this->full_URI())return true;
	
	if(is_object($this->link_to_class))
		return $this->link_to_class->is_Node($name);
	else
		return false;

}

public function is_Command($name,$funcName)
{
	//echo '(' . $name . ')<br>';
if($this->is_Node($name))
	{
		
		return !(false === ($tmp = strpos($name,$funcName)));
		
		//echo $name . ' !<br>';
			if(!(false === ($tmp = strpos($name,'?'))))
			{
		
				if(false === ($tmp2 = strpos($name,'=')))
					$tmp2 = strpos($name,'&');
					
				
				
				if(!(false === $tmp2))
				{
					//echo '1 ' . $funcName . ' == ' .  substr($name,$tmp + 1 ,$tmp2 - ($tmp + 1)) . ' ';
					return ($funcName == substr($name,$tmp + 1 ,$tmp2 - ($tmp + 1)));
				}
				else
				{
					//echo '2 ' . $funcName . ' == ' . substr($name,$tmp + 1)  . ' ';
					return ($funcName == substr($name,$tmp + 1));
				}
			}
	}
}

public function parseCommand($command)
{
return new Command_Object($command);
}


*/

?>
