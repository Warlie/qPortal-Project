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
require_once  $ProjectDir.'/PlugIn/hash/plugin_hash.php';  // The class under test

// --- Load the Test Stub ---
// Option 1: Autoloading (Recommended - Configure composer.json)
// Option 2: Manual require (adjust path relative to this file)
require_once $ProjectDir.'/test/Unit/Stubs/RstStubForTesting.php';

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
 * Dataprovider
 *
 * may be put in external file
 */

final class ExternalDataProvider
{
    public static function PlugInTestDataProvider(): array
    {   //provides Testdata for PlugIn-Classes
        $testData1= [
            ['grp' => 'A', 'wert' => 10,    'date' => '7.22'],
            ['grp' => 'B', 'wert' => 100,   'date' => '7.22'],
            ['grp' => 'A', 'wert' => -5,     'date' => '7.22'],
            ['grp' => 'B', 'wert' => 20,    'date' => '8.22']
        ];
        
        // SHOULD BE CHANGED FOR EFECTIVE TESTS
        $testData2= [
            ['grp' => 'A', 'wert' => 10,    'date' => '7.22'],
            ['grp' => 'B', 'wert' => 100,   'date' => '7.22'],
            ['grp' => 'A', 'wert' => -5,     'date' => '7.22'],
            ['grp' => 'B', 'wert' => 20,    'date' => '8.22']
        ];
        
        $testData=[[$testData1], [$testData2]];
        
        return $testData;
    }
}




/**
 * Tests the Hash class.
 *
 * @covers Hash
 */
class Remodelplugin_hashTest extends TestCase
{       
    /**
     * Tests the core hash.
     */
    // 1. Define test data
    #[DataProviderExternal(ExternalDataProvider::class, 'PlugInTestDataProvider')]
    public function testHashInitialisation($testData):void
    {
        //$spacer='----------------------------------------------';
        //var_dump($hash);var_dump($spacer);
        
        // 2. Instantiate the concrete stub with the test data
        $stubRst = new RstStubForTesting($testData); // Pass the test data
        
        // 3. Create Hash instance
        $hash = new Hash();
        
        // 4. Configure and inject dependency (stub)
        $this->assertNotSame('no element received',$hash->set_list($stubRst),"set_list should get dataobject (RST)");
        $this->assertSame('no element received',$hash->set_list($testData),"set_list should object getting anything not an object");
        
        return;
    }
    
    #[DataProviderExternal(ExternalDataProvider::class, 'PlugInTestDataProvider')]
    public function testHashWithoutRowToHashSelected($testData): void
    {           
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
        
        
    }
    
    #[DataProviderExternal(ExternalDataProvider::class, 'PlugInTestDataProvider')]
    public function testHashWithRowToHashSelected($testData): void
    {   
        $stubRst = new RstStubForTesting($testData); // Pass the test data
        $hash = new Hash();
        $hash->set_list($stubRst);
        //col($columnName), where $columName = $tag_name 
        //
        //Row 1: 
        $hash->columnName('grp');
        $this->assertTrue($hash->moveFirst(), "moveFirst should succeed");
        $this->assertSame(hash( "md5", $testData[0]['grp'] . 'chilli', false),$hash->col('grp'),"should give back md5 hash of  Achilli (testdata: 'grp', row1 + salt)");
        //var_dump($testData[0]['grp']);
        
        //Row 2: change salt
        $hash->salt('pepper');        
        $this->assertTrue($hash->next(), "next should succeed");
        $this->assertSame(hash( "md5", 'B' . 'pepper', false),$hash->col('grp'),"should give back md5 hash of  Bpepper (testdata: 'grp', row2)");
        
        //Row 3: change hash algorithm
        $hash->algo('sha256');
        $this->assertTrue($hash->next(), "next should succeed");
        $this->assertSame(hash( "sha256", 'A' . 'pepper', false),$hash->col('grp'),"should give back SHA-256 hash of  Apepper (testdata: 'grp', row3)");
     
        //Row 4:run tough end of data
        $this->assertTrue($hash->next(), "next should succeed");
        $this->assertSame(hash( "sha256", 'B' . 'pepper', false),$hash->col('grp'),"should give back SHA-256 hash of  Bpepper (testdata: 'grp', row4)");
        
        $this->assertFalse($hash->next(), "next should fail");
       
        
    }
    
    #[DataProviderExternal(ExternalDataProvider::class, 'PlugInTestDataProvider')]
    public function testHashWithMutipleRowsToHashSelected($testData): void
    {
        $stubRst = new RstStubForTesting($testData); // Pass the test data
        $hash = new Hash();
        $hash->set_list($stubRst);
        //col($columnName), where $columName = $tag_name and composit is set
        //
        //Row 1: 
        $hash->salt('chilli');   
        $hash->algo('md5');
        $hash->columnName('grp');
        $this->assertTrue($hash->moveFirst(), "moveFirst should succeed");
        $this->assertSame(hash( "md5", 'A' . 'chilli', false),$hash->col('grp'),"should give back md5 hash of  Achilli (testdata: 'grp', row1 + salt)");
        
        //Row 2: change salt
        $hash->composite('grp');
        $this->assertTrue($hash->next(), "next should succeed");
        $this->assertSame(hash( "md5", 'B' . 'chilli', false),$hash->col('grp'),"should give back md5 hash of  Bpepper (testdata: 'grp', row2)");
        
        //Row 3: change hash algorithm
        $hash->composite('wert');
        $this->assertTrue($hash->next(), "next should succeed");
        $this->assertSame(hash( "md5", 'A'.'-5' . 'chilli', false),$hash->col('grp'),"should give back SHA-256 hash of  Apepper (testdata: 'grp', row3)");
        
        //Row 4:run tough end of data
        $hash->composite('date');
        $this->assertTrue($hash->next(), "next should succeed");
        $this->assertSame(hash( "md5", 'B' . '20'. '8.22' . 'chilli', false),$hash->col('grp'),"should give back SHA-256 hash of  Bpepper (testdata: 'grp', row4)");
        
        $this->assertFalse($hash->next(), "next should fail");
        
    }
    
    #[DataProviderExternal(ExternalDataProvider::class, 'PlugInTestDataProvider')]
    public function testHashNoStubRSTException($testData): void
    {
        $stubRst = new RstStubForTesting($testData); // Pass the test data
        $hash = new Hash();
        $hash->columnName('grp');
        $this->assertFalse($hash->moveFirst(), "moveFirst should succeed");
        $this->expectException(ObjectBlockException::class);
        $this->assertSame(hash( "md5", $testData[0]['grp'] . 'chilli', false),$hash->col('grp'),"should give back md5 hash of  Achilli (testdata: 'grp', row1 + salt)");
            
        
    }
    
    #[DataProviderExternal(ExternalDataProvider::class, 'PlugInTestDataProvider')]
    public function testHashColumnNameNotInDataset($testData): void
    {
        $stubRst = new RstStubForTesting($testData); // Pass the test data
        $hash = new Hash();
        $hash->set_list($stubRst);
        $hash->columnName('NotAFieldName');
        $this->assertTrue($hash->moveFirst(), "moveFirst should succeed");
        $this->expectException(NotAFieldnameException::class);
        $this->assertSame(hash( "md5", $testData[0]['grp'] . 'chilli', false),$hash->col('NotAFieldName'),"should give back md5 hash of  Achilli (testdata: 'grp', row1 + salt)");
        
    }
}
?>