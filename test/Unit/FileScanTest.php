<?php



use PHPUnit\Framework\TestCase;
				require_once('classes/class_FileScan.php');

                                
function filter($var)
{
    // returns whether the input integer is odd
    return $var[file] == "classes/xml_multitree_xPath.php";
}
                                

                                
final class FileScanTest extends TestCase
{
	
	// checks before main tests
	public function testProducerFirst(): string
    {
        
    			
    		$filescanner = new File_Scan();
			
			//$filescanner->insert_str($str_source, $this->attribute_values['URI']);
			$filescanner->add_path('classes/');
			$filescanner->add_fix('*.php');
			//$filescanner->add_tag('class ');
			//$filescanner->add_tag('function ');
			//$filescanner->switch_cross_seek(array('include("','")'));
			//$filescanner->switch_cross_seek(array('require("','")'));
			//$filescanner->switch_cross_seek(array('require_once("','")'));
			$filescanner->seeking();

			$this->assertContains(["tag"=>"no tag parameter", "pos" => 0, "file" => "classes/class_FileScan.php"], $filescanner->result());


        return 'first';
    }



 

	
	
	
	 /**
     * @depends testProducerFirst
     */
    public function testFileScan(string $value) : void
{
	//$test_string = "*?__find_node(model=xpath_model,namespace='',query='wubb')=wup";

	//echo "Next test with:" . $value . "\n";

    			
    		$filescanner = new File_Scan();
			
			//$filescanner->insert_str($str_source, $this->attribute_values['URI']);
			$filescanner->add_path('classes/', 1);
			$filescanner->add_fix('*.php');
			//$filescanner->add_tag('class ');
			//$filescanner->add_tag('function ');
			//$filescanner->switch_cross_seek(array('include("','")'));
			//$filescanner->switch_cross_seek(array('require("','")'));
			//$filescanner->switch_cross_seek(array('require_once("','")'));
			$filescanner->seeking();

			$this->assertContains(["tag"=>"no tag parameter", "pos" => 0, "file" => "classes/handles/PHP_handle.php"], $filescanner->result());
			

    //$test2 = new Command_Old_Object($value);

   // var_dump($value, $test1, $pattern);

    // reference and finite state machine have to deliver same results
    
    // same URI
       
    $this->assertSame(1, 1);

    

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