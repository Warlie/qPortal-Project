<?php

// tests/Accumulation/plugin_accumulationTest.php

// PHPUnit base class namespace
use PHPUnit\Framework\TestCase;

// --- Include or Autoload required classes ---

// Adjust paths as needed for your project structure
require_once __DIR__ . '/../plugin_interface.php'; // The abstract base class (adjust path)
require_once __DIR__ . '/plugin_accumulation.php';    // The class under test

// --- Load the Test Stub ---
// Option 1: Autoloading (Recommended - Configure composer.json)
// Option 2: Manual require (adjust path relative to this file)
require_once __DIR__ . '/../../test/Unit/Stubs/RstStubForTesting.php';

// --- Import the Stub Class ---
use Tests\Stubs\RstStubForTesting; // Use the stub from its namespace

// --- Auxiliary definitions for the test ---
// Define the Exception class if it's not globally available/autoloaded
// Ideally, this exception would also be in its own file and namespace.
if (!class_exists('NotAFieldnameException')) {
    class NotAFieldnameException extends \Exception {}
}
// --- End auxiliary definitions ---


/**
 * Tests the Accumulation class, focusing on grouped accumulation.
 *
 * @covers Accumulation
 */
class plugin_accumulationTest extends TestCase
{
    /**
     * Tests the core accumulation logic when grouping is active.
     *
     * It simulates a data source ($rst) using the RstStubForTesting stub
     * with test data for two groups ('A' and 'B'). It verifies that the values
     * in the 'wert' column are correctly accumulated within each group
     * while iterating through the data set.
     */
    public function testAccumulationWithGroups(): void
    {
        // 1. Define test data
        $testData = [
            ['grp' => 'A', 'wert' => 10],
            ['grp' => 'B', 'wert' => 100],
            ['grp' => 'A', 'wert' => 5],
            ['grp' => 'B', 'wert' => 20]
        ];

        // 2. Instantiate the concrete stub with the test data
        $stubRst = new RstStubForTesting($testData); // Pass the test data

        // 3. Create Accumulation instance
        $accumulation = new Accumulation();

        // 4. Configure and inject dependency (stub)
        $accumulation->set_list($stubRst); // Pass the stub instance
        $accumulation->forAccumulation('wert'); // Column to accumulate
        $accumulation->group('grp');          // Column to group by

        // 5. Execute test logic and check results (Assertions)
        //    (This part remains exactly the same as before!)

        // --- First row ---
        $this->assertTrue($accumulation->moveFirst(), "moveFirst should succeed");
        $this->assertSame('A', $accumulation->col('grp'), "Row 1: Group value should be 'A'");
        $this->assertSame(10.0, $accumulation->col('wert'), "Row 1: Accumulated value should be 10.0 (Start value Group A)");

        // --- Second row ---
        $this->assertTrue($accumulation->next(), "next() should succeed for row 2");
        $this->assertSame('B', $accumulation->col('grp'), "Row 2: Group value should be 'B'");
        $this->assertSame(100.0, $accumulation->col('wert'), "Row 2: Accumulated value should be 100.0 (Start value Group B)");

        // --- Third row ---
        $this->assertTrue($accumulation->next(), "next() should succeed for row 3");
        $this->assertSame('A', $accumulation->col('grp'), "Row 3: Group value should be 'A'");
        $this->assertSame(15.0, $accumulation->col('wert'), "Row 3: Accumulated value should be 15.0 (10.0 [from row 1] + 5)");

        // --- Fourth row ---
        $this->assertTrue($accumulation->next(), "next() should succeed for row 4");
        $this->assertSame('B', $accumulation->col('grp'), "Row 4: Group value should be 'B'");
        $this->assertSame(120.0, $accumulation->col('wert'), "Row 4: Accumulated value should be 120.0 (100.0 [from row 2] + 20)");

        // --- End of data ---
        $this->assertFalse($accumulation->next(), "next() should return false after the last row");
    }

    // ... other tests could follow here ...
    // e.g., testAccumulationWithoutGroup(), testDelegationOfOtherMethods() etc.
}
?>