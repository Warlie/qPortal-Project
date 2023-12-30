<?php



use PHPUnit\Framework\TestCase;
				require_once('classes/finite_state_machine/enums.php');
				require_once('classes/finite_state_machine/class_Transducer.php');
				require_once('classes/finite_state_machine/class_Acceptor.php');
				require_once('classes/fs_parser/qp_workflow.php');
				//require_once('classes/class_compute_internal_statements.php');
				
                                include('classes/class_Contentgenerator.php');
                                include('mod_lib.php');
                                

class Command_Old_Object
{
	private $node_URI;
	private $insert;
	private $commands = array();
	private $commands1 = array();
	private $pair = array();
	private $pos_command = 0;
	private $current_node = '';
	private $node_stack = array();
	
	private $struct_tree = array();
	


	function __construct($command) 
	{

		//if(is_null()) var_dump(DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS));
		//if(is_null($command))$command ='';
		$this->insert = $command;
		
		
			if(!(false === ($tmp = strpos($command,'?'))))
			{
				$this->node_URI = substr($command,0,$tmp);
				$commandstr = substr($command,$tmp + 1);
				$this->commands = explode('&',$commandstr);
				
				for($i = 0;$i<count($this->commands);$i++)
				{
					$this->commands[$i] = explode('=',$this->commands[$i]);
					if($this->commands[$i][0] == '__redirect_node')
					{
						$this->commands[$i][1] = base64_decode(rawurldecode($this->commands[$i][1]));
					}
				}

			}
			
		//var_dump($this->struct_tree);
	}
	
	
	public function get_URI(){return $this->node_URI;}
	public function get_Command($num,$index)
	{
		//echo "-------------------------------------------------------\n";
	//var_dump($this->commands);
	//echo "-------------------------------------------------------\n";
	return $this->commands[$num][$index];}
	
	public function get_Insert(){return $this->insert;}
	
	public function open_node($parser, $node_name, $attribute)
	{
		//array_push($this->node_stack, $attribute['node_name']);

		
		//echo "open_node=" . $this->current_node . " \n";
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
	
	

	}

}
                                

                                
final class AutomatTest extends TestCase
{
	
	// checks before main tests
	public function testProducerFirst(): string
    {
        $this->assertTrue(true);

        return 'first';
    }



    public static function ProvideTestCases(): array
    {
    	return [
    	['*?start', ["Identifire" => '*', "Command" => ['Name' => 'start', 'Attribute' => [], 'Value'=> null]]],
    	['*', ["Identifire" => '*', "Command" => ['Name' => null, 'Attribute' => [], 'Value'=> null]]], //!
    	["*?__find_node(model=xpath_model,namespace='',query='wubb')", ["Identifire" => '*', "Command" => ['Name' => '__find_node', 'Attribute' => ['model'=>'xpath_model','namespace'=>"''",'query'=>"'wubb'"], 'Value'=> '']]],
    	['http://www.w3.org/2006/05/pedl-lib#Object_Constructor?construct', ["Identifire" => 'http://www.w3.org/2006/05/pedl-lib#Object_Constructor', "Command" => ['Name' => 'construct', 'Attribute' => [], 'Value'=> null]]],
    	['@registry_surface_system#DBO?__redirect_node=QHJlZ2lzdHJ5X3N1cmZhY2Vfc3lzdGVtI0RCTy5zZXRTdGF0ZW1lbnQuc3RhdGVtZW50P19fYWRkX2luX29iamVjdD0w', ["Identifire" => '@registry_surface_system#DBO', "Command" => ['Name' => '__redirect_node', 'Attribute' => [], 'Value'=> '@registry_surface_system#DBO.setStatement.statement?__add_in_object=0']]],
        ["*?__find_node=wup", ["Identifire" => '*', "Command" => ['Name' => '__find_node', 'Attribute' => [], 'Value'=> 'wup']]]
        ,["", ["Identifire" => '', "Command" => ['Name' => null, 'Attribute' => [], 'Value'=> null]]]
        ];
    }    

	
	
	
	 /**
     * @depends testProducerFirst
     * @dataProvider ProvideTestCases
     */
    public function testClassConstructor(string $value, array $pattern) : void
{
	//$test_string = "*?__find_node(model=xpath_model,namespace='',query='wubb')=wup";

	//echo "Next test with:" . $value . "\n";

    $test1 = new Command_Object($value);
    //$test2 = new Command_Old_Object($value);

   // var_dump($value, $test1, $pattern);

    // reference and finite state machine have to deliver same results
    
    // same URI
       
    $this->assertSame($test1->get_Result_Array(), $pattern);

    

    //$this->assertSame(18, $user->age);
    //$this->assertEmpty($user->favorite_movies);
}

    /**
     * 
     */
    public static function ProvideTestWrongExitStates(): array
    {
    	return [
    	['*?boom(kra'],
    	['*?boom(kra=boo'],
    	['*?boom(kra=boo,ua'],
    	['*?boom(kra=boo,ua=ba'],
        ];
    }    


    /**
     * @dataProvider ProvideTestWrongExitStates
     */
    public function testAutomatStateException(string $value) : void
{
    $this->expectException(Finite\Exception\StateException::class);
    new Command_Object($value);
}

    /**
     * 
     */
    public static function ProvideTestWrongTransition(): array
    {
    	return [
    	['*?b(--,'],
    	['*?boom((']
        ];
    }    


    /**
     * @dataProvider ProvideTestWrongTransition
     * TODO other exception is needed and there are still errors
     */
    public function testAutomatTransitionException(string $value) : void
{
    $this->expectException(Finite\Exception\StateException::class);
    new Command_Object($value);
}
    
}

?>