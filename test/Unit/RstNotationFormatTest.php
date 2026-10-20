<?php

// test/Unit/RstNotationFormatTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/class_database.php';

/** Steht fuer mysqli: nur das Maskieren wird gebraucht. */
class NotationEscapeFake
{
    public function real_escape_string($s) { return addslashes($s); }
}

/**
 * rst::applyNotationFormat - wie ein Wert fuer INSERT/UPDATE geschrieben wird.
 *
 * Gefunden 2026-10-17 an der Vertragseingabe (tbl_rental_party): "tenant" in einer
 * enum-Spalte kam als NULL an (MariaDB nahm den ersten enum-Wert, "landlord"), und ein
 * Datum lief durch strtotime - aus dem 30. Februar wurde der 2. Maerz, aus Unlesbarem
 * der 1.1.1970. Beides ohne Fehler.
 *
 * @covers rst
 */
class RstNotationFormatTest extends TestCase
{
    private function format($type, $value, $null = 'NO', $default = null)
    {
        $ref = new ReflectionClass('rst');
        $rst = $ref->newInstanceWithoutConstructor();
        $f = $ref->getProperty('field');
        $f->setAccessible(true);
        $f->setValue($rst, ['t.c' => ['Type' => $type, 'Null' => $null, 'Default' => $default]]);
        $c = $ref->getProperty('db_connection');
        $c->setAccessible(true);
        $c->setValue($rst, new NotationEscapeFake());

        $rst->applyNotationFormat($value, 't.c');
        return $value;
    }

    public function testEnumIsQuotedText()
    {
        $this->assertSame("'tenant'", $this->format("enum('landlord','tenant')", 'tenant'));
    }

    public function testEnumIsEscaped()
    {
        $this->assertSame("'x\\' OR 1=1'", $this->format("enum('a','b')", "x' OR 1=1"));
    }

    public function testValidDateStays()
    {
        $this->assertSame("'2025-01-01'", $this->format('date', '2025-01-01'));
    }

    public function testImpossibleIsoDateDoesNotRollOver()
    {
        // vorher '2025-03-02'
        $this->assertSame('NULL', $this->format('date', '2025-02-30', 'YES'));
    }

    public function testUnreadableDateIsNotEpoch()
    {
        // vorher '1970-01-01'
        $this->assertSame('NULL', $this->format('date', 'kein Datum', 'YES'));
    }

    public function testImpossibleDateInNotNullColumnStops()
    {
        // NULL genuegte nicht: INSERT IGNORE schrieb dann 0000-00-00. Die Ausnahme faengt
        // get_insert_array_statements - die Zeile wird nicht geschrieben.
        $this->expectException(RuntimeException::class);
        $this->format('date', 'morgen', 'NO');
    }

    public function testOtherDateNotationStillParsed()
    {
        // Bestand: andere Schreibweisen gehen weiter durch strtotime
        $this->assertSame("'2025-10-05'", $this->format('date', '05.10.2025'));
    }

    public function testVarcharUnchanged()
    {
        $this->assertSame("'O\\'Brien'", $this->format('varchar(50)', "O'Brien"));
    }

    public function testNumberUnchanged()
    {
        $this->assertSame('42', $this->format('int(10) unsigned', '42'));
    }

    public function testDecimalCommaBecomesPoint()
    {
        $this->assertSame('12.5', $this->format('decimal(14,4)', '12,5'));
    }

    public function testNoNumberInNotNullColumnStops()
    {
        // vorher NULL - INSERT IGNORE schrieb daraus 0 (gemessen 2026-10-20: Gewicht "abc" wurde 0)
        $this->expectException(RuntimeException::class);
        $this->format('decimal(14,4)', 'abc', 'NO');
    }

    public function testNoNumberInNullColumnIsNull()
    {
        // vorher eine Ausnahme - auch fuer ein leeres Feld
        $this->assertSame('NULL', $this->format('decimal(10,2)', 'abc', 'YES'));
        $this->assertSame('NULL', $this->format('decimal(10,2)', '', 'YES'));
    }

    public function testEmptyNumberInNotNullColumnTakesDefault()
    {
        // das leere Steuerfeld am Mietgegenstand (tbl_rental_item.tax_rate NOT NULL DEFAULT 0.00)
        $this->assertSame('0.00', $this->format('decimal(5,2)', '', 'NO', '0.00'));
    }
}
