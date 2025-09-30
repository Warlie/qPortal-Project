<?php

// tests/Hash/plugin_hashTest.php

// PHPUnit base class namespace
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

//--- Include or Autoload required classes ---
//get local Path to project directory dirname(__DIR__,3) possible
$CurrentDir= explode('qPortal-Project', __DIR__);
$ProjectDir=$CurrentDir[0].'qPortal-Project';

require_once $ProjectDir.'/PlugIn/plugin_interface.php'; // The abstract base class (adjust path)
require_once  $ProjectDir.'/PlugIn/datetime/plugin_datetime.php';  // The class under test

// --- Load the Test Stub ---
// Option 1: Autoloading (Recommended - Configure composer.json)
// Option 2: Manual require (adjust path relative to this file)
require_once $ProjectDir.'/test/Unit/Stubs/RstStubForTesting.php';
require_once $ProjectDir.'/test/Unit/Stubs/ExternalDataProvider.php';

// --- Import the Stub Class ---
use Tests\Stubs\RstStubForTesting; // Use the stub from its namespace
use PhpParser\Node\NullableType;

//load required Exceptions
require_once $ProjectDir.'/classes/exceptions/not_a_fieldname_exception.php'; // The abstract base class (adjust path)
require_once  $ProjectDir.'/classes/exceptions/object_block_exception.php';  

// --- Auxiliary definitions for the test ---
// Define the Exception class if it's not globally available/autoloaded
// Ideally, this exception would also be in its own file and namespace.
if (!class_exists('NotAFieldnameException')) {
    class NotAFieldnameException extends \Exception {}
}
// --- End auxiliary definitions ---






/**
 * Tests the Hash class.
 *
 * @covers Hash
 */
class plugin_datetimeTest extends TestCase
{       
    /**
     * Tests the core hash.
     */
    // 1. Define test data
    #[DataProviderExternal(ExternalDataProvider::class, 'PlugInTestDataProvider')]
    public function testQPDateTimeInitialisation($testData):void
    {
        //$spacer='----------------------------------------------';
        //var_dump($hash);var_dump($spacer);
        
        // 2. Instantiate the concrete stub with the test data
        $stubRst = new RstStubForTesting($testData); // Pass the test data
        
        // 3. Create QPDateTime instance
        $hash = new QPDateTime();
        
        // 4. Configure and inject dependency (stub)
        $this->assertNotSame('no element received',$hash->set_list($stubRst),"set_list should get dataobject (RST)");
        $this->assertSame('no element received',$hash->set_list($testData),"set_list should object getting anything not an object");
        
        return;
    }
    
    #[DataProviderExternal(ExternalDataProvider::class, 'PlugInTestDataProvider')]
    public function testQPDateTimeCheckFortoDateTime($testData): void
    {
    	$td = new QPDateTime();
    	$res = $td->toDateTime('Y-m-d H:i:s', '1980', '6', '2', '6', '30', '45');
    	$this->assertSame("1980-06-02 06:30:45", $res, "compose timedate from components");

    	
    	$this->assertSame($td->now('Y-m-d H:i:s'), $td->toDateTime('Y-m-d H:i:s', '', '', '', '', '', ''),"current time when no components are given");
    	
    	// 2. Instantiate the concrete stub with the test data
        $stubRst = new RstStubForTesting($testData); // Pass the test data
    	$td->set_list($stubRst);
    	$td->moveFirst();
    	var_dump($td->col('grp'));
    	//$this->expectException(\RuntimeException::class);

    	/*
        $stubRst = new RstStubForTesting($testData); // Pass the test data
        $hash = new Hash();
        $hash->set_list($stubRst);
        // 5. Execute test logic and check results (Assertions)
        //    Test public functions 
        
        //col($columnName), where $columName != $tag_name, also moveFirst() and next() 
        //Row 1:
        $this->assertTrue($hash->moveFirst(), "moveFirst should succeed");
        $this->assertSame('A',$hash->col('grp'),"should give back A (testdata: 'grp' row1)");
        
        //Row 2:
        $this->assertTrue($hash->next(), "next should succeed");
        $this->assertSame('B',$hash->col('grp'),"should give back B (testdata: 'grp' row2)");
        
        //Row 3:
        $this->assertTrue($hash->next(), "next should succeed");
        $this->assertSame('A',$hash->col('grp'),"should give back A (testdata: 'grp' row3)");
        
        //Row 4:
        $this->assertTrue($hash->next(), "next should succeed");
        $this->assertSame('B',$hash->col('grp'),"should give back B (testdata: 'grp' row2)");
        //End data:
        $this->assertFalse($hash->next(), "next should fail");
        */
        
    }
    

}
?>