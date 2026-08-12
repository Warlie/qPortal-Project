<?php



use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
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



 

	
	
	
	 #[Depends('testProducerFirst')]
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

	/* Fixed source for the characterisation tests below. The comment in line 1
	*  deliberately contains the words "class" and "function": the scanner works on
	*  plain substrings and cannot tell prose from a declaration.
	*/
	private const PROBE_SOURCE = <<<'PHP'
<?php
// a comment that mentions class Foo and function bar on purpose
class Demo_Thing
{
	private $x = 1;

	function __construct($back, $treepos)
	{
		$this->x = $back;
	}

	public function set_Pattern($name, $pattern)
	{
		return true;
	}
}
PHP;

	private function scan_probe() : array
	{
		$filescanner = new File_Scan();
		$filescanner->insert_str(self::PROBE_SOURCE, 'probe.php');
		$filescanner->add_tag('class ');
		$filescanner->add_tag('function ');
		$filescanner->seeking();

		return $filescanner->result();
	}

	/* Records what the scanner does today, so a later intake can be diffed against it.
	*  Entries 0 and 1 are false positives produced by the comment line - they are part
	*  of the current behaviour on purpose, not an accident of this test.
	*/
	public function testTagScanRecordsCurrentBehaviour() : void
	{
		$this->assertSame(
			[
				['tag' => 'class Foo and function bar on purpose', 'pos' => 1,  'file' => 'probe.php'],
				['tag' => 'function bar on purpose',               'pos' => 1,  'file' => 'probe.php'],
				['tag' => 'class Demo_Thing',                      'pos' => 2,  'file' => 'probe.php'],
				['tag' => 'function __construct($back, $treepos)', 'pos' => 6,  'file' => 'probe.php'],
				['tag' => 'function set_Pattern($name, $pattern)', 'pos' => 11, 'file' => 'probe.php'],
			],
			$this->scan_probe()
		);
	}

	/* The declarations that really exist in the probe. This is the part a parser based
	*  intake has to reproduce; the two entries above are the part it should drop.
	*/
	public function testTagScanFindsTheRealDeclarations() : void
	{
		$tags = array_column($this->scan_probe(), 'tag');

		$this->assertContains('class Demo_Thing', $tags);
		$this->assertContains('function __construct($back, $treepos)', $tags);
		$this->assertContains('function set_Pattern($name, $pattern)', $tags);
	}

	/* One single comment line yields two entries, and the first of them carries both
	*  keywords - so Obj_Class_Collection runs its class branch and its function branch
	*  on the same string.
	*/
	public function testCommentLineProducesTwoEntriesAndMatchesBothBranches() : void
	{
		$from_comment = array_values(array_filter(
			$this->scan_probe(),
			static fn(array $entry) : bool => $entry['pos'] === 1
		));

		$this->assertCount(2, $from_comment);
		$this->assertStringContainsString('class ',    $from_comment[0]['tag']);
		$this->assertStringContainsString('function ', $from_comment[0]['tag']);
	}

    /* The automat tests that used to live here were verbatim copies of
    *  AutomatTest::testAutomatStateException / ::testAutomatTransitionException.
    *  They still expected the removed Finite\Exception\StateException and relied on
    *  Command_Object being loaded by another testfile. The maintained versions are
    *  in AutomatTest.
    */
}

?>