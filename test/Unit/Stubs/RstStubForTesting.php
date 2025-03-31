<?php

// tests/Stubs/RstStubForTesting.php

namespace Tests\Stubs;

// Import base class and exception if they are namespaced.
// Assuming global namespace for now based on previous examples.
// Adjust if your plugin class is in a namespace like 'YourProject\Plugins'.
use plugin;
use NotAFieldnameException; // Assuming global or correctly imported

/**
 * A concrete test stub implementation for the abstract 'plugin' class.
 *
 * This stub simulates the behavior of a plugin data source (like a database result set)
 * by iterating over a predefined array of test data. It's designed to be injected
 * as the `$rst` dependency in tests for classes extending `plugin`.
 */
class RstStubForTesting extends plugin {
    /** @var array The array holding the test data rows. */
    private array $testData = [];
    /** @var int The current internal row index (-1 means before first row). */
    private int $rowIndex = -1;

    /**
     * Constructor to inject the test data array.
     *
     * @param array $data An array of associative arrays representing the data rows.
     */
    public function __construct(array $data) {
        $this->testData = $data;
        // Important: Initialize parent properties if the class needs them.
        // parent::__construct(); // Uncomment if the parent has a constructor that needs to be called.
    }

    /**
     * Moves the internal pointer to the first row of the test data.
     * Overrides the parent's protected method with test logic.
     * IMPORTANT: Keep `protected` visibility if the consuming class (e.g., Accumulation)
     * relies on calling the parent's protected method signature.
     *
     * @return bool True if successful (data exists), false otherwise.
     */
    protected function moveFirst(): bool {
        if (empty($this->testData)) {
            $this->rowIndex = -1; // No data, pointer remains invalid
            return false;
        }
        $this->rowIndex = 0; // Point to the first row
        return true;
    }

    /**
     * Moves the internal pointer to the next row in the test data.
     *
     * @return bool True if the next row exists and the pointer was moved, false otherwise.
     */
    public function next(): bool {
        // Check if currently pointing to a valid row within bounds
        if ($this->rowIndex >= 0 && $this->rowIndex < count($this->testData)) {
            $this->rowIndex++; // Advance the pointer
            // Check if the new pointer position is valid
            return isset($this->testData[$this->rowIndex]);
        }
        // If pointer was already invalid or past the end
        return false;
    }

    /**
     * Retrieves the value of a specific column from the current row.
     *
     * @param string $columnName The name of the column to retrieve.
     * @return mixed The value of the column, or false if the pointer is invalid.
     * @throws NotAFieldnameException If the column does not exist in the current row.
     */
    public function col($columnName) {
        // Check if pointer is valid
        if ($this->rowIndex < 0 || !isset($this->testData[$this->rowIndex])) {
            // Accumulation checks for $this->rst itself, but returns 'no dataset'.
            // For the stub, signaling an error might be better if col() is called
            // unexpectedly after next() returned false.
            trigger_error("StubRst: col('$columnName') called with invalid row index ($this->rowIndex).", E_USER_WARNING);
            return false; // Or maybe throw an exception?
        }
        // Check if the column exists in the current simulated row
        if (!array_key_exists($columnName, $this->testData[$this->rowIndex])) {
            throw new NotAFieldnameException("StubRst: Column '$columnName' not found in simulated data row $this->rowIndex.");
        }
        // Return the value from the test data
        return $this->testData[$this->rowIndex][$columnName];
    }

    /**
     * Returns a dummy data type for any column.
     * In a real scenario, this might inspect the actual data.
     *
     * @param string $columnname The name of the column.
     * @return string A dummy data type string.
     */
    public function datatype($columnname): string {
        // Return a dummy type or try to derive it from data if needed
        // Example: Check type of $this->testData[0][$columnname] if data exists.
        return 'stub_datatype';
    }

    /**
     * Returns the list of field (column) names.
     * Derives the fields from the keys of the first data row.
     *
     * @return array An array of column names, or an empty array if no data.
     */
    public function fields(): array {
        // Return column names from the first row, or empty if no data
        if (!empty($this->testData)) {
            return array_keys($this->testData[0]);
        }
        return [];
    }

    /**
     * Provides a reasonable dummy implementation for reset().
     * Delegates to moveFirst() as resetting usually means going to the start.
     *
     * @return bool Result of moveFirst().
     */
    public function reset(): bool {
        return $this->moveFirst();
    }

    // --- Implement other methods from 'plugin' if needed by consuming classes ---

    /**
     * Dummy implementation for moveLast().
     * @return bool Always returns false in this stub.
     */
    protected function moveLast(): bool {
        /* For a more complete stub, you could implement this:
        if (!empty($this->testData)) {
            $this->rowIndex = count($this->testData) - 1;
            return true;
        }
        $this->rowIndex = -1;
        return false;
        */
        return false; // Simple dummy
    }

    /**
     * Dummy implementation for prev().
     * @return bool Always returns false in this stub.
     */
    public function prev(): bool {
         /* For a more complete stub, you could implement this:
        if ($this->rowIndex > 0) {
            $this->rowIndex--;
            return true;
        }
        // Cannot move before the first element. Consider pointer state.
        return false;
         */
        return false; // Simple dummy
    }
}