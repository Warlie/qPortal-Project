<?php

// PlugIn/formula/plugin_formulaTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../plugin_interface.php';
require_once __DIR__ . '/plugin_formula.php';
require_once __DIR__ . '/../../test/Unit/Stubs/RstStubForTesting.php';

use Tests\Stubs\RstStubForTesting;

/**
 * Formula: neue Spalten aus Formeln ueber die Spalten der Quelle, nach Kahn geordnet.
 *
 * @covers Formula
 */
class plugin_formulaTest extends TestCase
{
    /** wie Aggregation sie liefert: Haben leer, wo keine Zahlung war */
    private function rows(): RstStubForTesting
    {
        return new RstStubForTesting([
            ['monat' => '2023-01-01 00:00:00', 'soll' => 1130, 'nebenkosten' => 150, 'haben' => 1390],
            ['monat' => '2023-02-01 00:00:00', 'soll' => 1130, 'nebenkosten' => 150, 'haben' => ''],
        ]);
    }

    private function column(Formula $f, string $name): array
    {
        $out = [];
        if ($f->moveFirst()) {
            do { $out[] = $f->col($name); } while ($f->next());
        }
        return $out;
    }

    public function testSumAndDifferenceOfColumns(): void
    {
        $f = new Formula();
        $src = $this->rows();
        $f->set_list($src);
        $f->formula('summe', 'soll + nebenkosten');
        $f->formula('saldo', 'haben - soll - nebenkosten');

        $this->assertSame([1280.0, 1280.0], $this->column($f, 'summe'));
        $this->assertSame([110.0, -1280.0], $this->column($f, 'saldo'), "leeres Haben zaehlt 0");
        $this->assertSame(['2023-01-01 00:00:00', '2023-02-01 00:00:00'], $this->column($f, 'monat'), "Quellspalten laufen durch");
    }

    public function testFormulaMayUseAnotherInAnyOrder(): void
    {
        $f = new Formula();
        $src = $this->rows();
        $f->set_list($src);
        $f->formula('saldo', 'haben - summe');      // nennt summe, die erst danach kommt
        $f->formula('summe', 'soll + nebenkosten');

        $this->assertSame([110.0, -1280.0], $this->column($f, 'saldo'));
        $this->assertSame(['summe', 'saldo'], array_column($f->explain(), 'name'), "Kahn: summe vor saldo");
    }

    public function testCoefficientsAndConstants(): void
    {
        $f = new Formula();
        $src = $this->rows();
        $f->set_list($src);
        $f->formula('x', '0.5soll + 10 - 2nebenkosten');

        $this->assertSame([275.0, 275.0], $this->column($f, 'x'));
    }

    public function testCycleGivesEmptyAndOthersStillWork(): void
    {
        $f = new Formula();
        $src = $this->rows();
        $f->set_list($src);
        $f->formula('a', 'b + 1');
        $f->formula('b', 'a + 1');
        $f->formula('c', 'c + soll');               // sich selbst
        $f->formula('summe', 'soll + nebenkosten');

        $this->assertSame(['', ''], $this->column($f, 'a'));
        $this->assertSame(['', ''], $this->column($f, 'c'));
        $this->assertSame([1280.0, 1280.0], $this->column($f, 'summe'));
        $this->assertSame([null, null, null], array_column(array_slice($f->explain(), 1), 'reihenfolge'));
    }

    public function testUnknownColumnCountsZero(): void
    {
        $f = new Formula();
        $src = $this->rows();
        $f->set_list($src);
        $f->formula('x', 'soll + gibt_es_nicht');

        $this->assertSame([1130.0, 1130.0], $this->column($f, 'x'));
    }

    public function testQualifiedColumnNames(): void
    {
        $f = new Formula();
        $src = new RstStubForTesting([['qry.net_rent' => '690.00', 'qry.service_charge' => '150.00']]);
        $f->set_list($src);
        $f->formula('warm', 'qry.net_rent + qry.service_charge');

        $this->assertSame([840.0], $this->column($f, 'warm'));
    }

    public function testNoFloatingRest(): void
    {
        $f = new Formula();
        $src = new RstStubForTesting([['a' => '0.1', 'b' => '0.2']]);
        $f->set_list($src);
        $f->formula('s', 'a + b');

        $this->assertSame([0.3], $this->column($f, 's'));
    }

    public function testInvalidFormulaThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Formula())->formula('x', 'soll / 2');
    }

    public function testParseTerms(): void
    {
        $this->assertSame([
            ['sign' => -1, 'coef' => 2.0, 'syms' => ['w']],
            ['sign' => 1,  'coef' => 1.0, 'syms' => ['o']],
        ], Formula::parseTerms('-2w+o'));
        $this->assertSame([['sign' => 1, 'coef' => 3.0, 'syms' => ['a', 'b']]], Formula::parseTerms('a*3*b'));
        $this->assertNull(Formula::parseTerms(''));
        $this->assertNull(Formula::parseTerms('a**b'));
        $this->assertNull(Formula::parseTerms('(a+b)'));
    }

    /** 2026-10-13: Spalte mal Spalte - der Betrag eines Stuecks aus Slice */
    public function testProductOfColumns(): void
    {
        $f = new Formula();
        $src = new RstStubForTesting([['gross' => '1000.00', 'slice_share' => 184 / 366, 'nebenkosten' => '150']]);
        $f->set_list($src);
        $f->formula('kosten', 'gross * slice_share');
        $f->formula('rest', 'kosten - 0.5 * nebenkosten');

        $this->assertSame([502.732240], $this->column($f, 'kosten'));
        $this->assertSame([427.73224], $this->column($f, 'rest'));
    }

    public function testFieldsAndDatatype(): void
    {
        $f = new Formula();
        $src = $this->rows();
        $f->set_list($src);
        $f->formula('summe', 'soll + nebenkosten');

        $this->assertSame(['monat', 'soll', 'nebenkosten', 'haben', 'summe'], $f->fields());
        $this->assertSame('decimal(20,6)', $f->datatype('summe'));
    }
}
