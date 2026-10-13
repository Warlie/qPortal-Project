<?php

// PlugIn/time/plugin_sliceTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../plugin_interface.php';
require_once __DIR__ . '/plugin_slice.php';
require_once __DIR__ . '/../../test/Unit/Stubs/RstStubForTesting.php';
require_once __DIR__ . '/../../classes/exceptions/not_a_fieldname_exception.php';

use Tests\Stubs\RstStubForTesting;

/**
 * Slice: Zeitraeume an Rastergrenzen zerteilen, Anteil je Stueck nach Tagen (Sekunden in UTC).
 *
 * @covers Slice
 */
class plugin_sliceTest extends TestCase
{
    private function read(Slice $s, array $cols): array
    {
        $out = [];
        if ($s->moveFirst()) {
            do {
                $out[] = implode('|', array_map(fn($c) => is_float($v = $s->col($c)) ? sprintf('%.6f', $v) : strval($v), $cols));
            } while ($s->next());
        }
        return $out;
    }

    private function slice(array $rows, string $unit = 'year'): Slice
    {
        $s = new Slice();
        $src = new RstStubForTesting($rows);
        $s->set_list($src);
        $s->span('von', 'bis');
        $s->grid($unit);
        return $s;
    }

    /** STW: "Start bis 31.12 und dann immer weiter" - Gas Juli bis Juni, 2024 ist ein Schaltjahr */
    public function testYearByDays(): void
    {
        $s = $this->slice([['dienst' => 'Gas', 'von' => '2023-07-01', 'bis' => '2024-07-01', 'brutto' => '1000.00']]);
        $this->assertSame([
            'Gas|2023-07-01 00:00:00|2024-01-01 00:00:00|' . sprintf('%.6f', 184 / 366),
            'Gas|2024-01-01 00:00:00|2024-07-01 00:00:00|' . sprintf('%.6f', 182 / 366),
        ], $this->read($s, ['dienst', 'slice_start', 'slice_end', 'slice_share']));
    }

    public function testInsideOneYearStaysOneRow(): void
    {
        $s = $this->slice([['von' => '2024-01-01', 'bis' => '2025-01-01']]);
        $this->assertSame(['2024-01-01 00:00:00|1.000000'], $this->read($s, ['slice_start', 'slice_share']));
    }

    public function testOverSeveralYearsSharesAddUpToOne(): void
    {
        $s = $this->slice([['von' => '2022-03-15', 'bis' => '2025-03-15']]);
        $this->assertSame(4, $s->many());
        $summe = 0.0;
        $s->moveFirst();
        do { $summe += $s->col('slice_share'); } while ($s->next());
        $this->assertSame(1.0, $summe);
    }

    public function testMonthAndHour(): void
    {
        $this->assertSame(['2024-01-20 00:00:00', '2024-02-01 00:00:00', '2024-03-01 00:00:00'],
            $this->read($this->slice([['von' => '2024-01-20', 'bis' => '2024-03-10']], 'month'), ['slice_start']));
        // die Nachtschicht: 22 bis 6 Uhr, je Tag geteilt
        $this->assertSame(['2024-05-01 22:00:00|0.250000', '2024-05-02 00:00:00|0.750000'],
            $this->read($this->slice([['von' => '2024-05-01 22:00:00', 'bis' => '2024-05-02 06:00:00']], 'day'), ['slice_start', 'slice_share']));
    }

    public function testUnreadableSpanPassesThroughOnce(): void
    {
        $s = $this->slice([
            ['von' => '', 'bis' => '2024-01-01'],
            ['von' => '2024-02-01', 'bis' => '2024-02-01'],
        ]);
        $this->assertSame(['|', '|'], $this->read($s, ['slice_start', 'slice_share']));
    }

    public function testFieldsAndDatatype(): void
    {
        $s = $this->slice([['von' => '2024-01-01', 'bis' => '2024-02-01', 'brutto' => '1']]);
        $this->assertSame(['von', 'bis', 'brutto', 'slice_start', 'slice_end', 'slice_share'], $s->fields());
        $this->assertSame('double', $s->datatype('slice_share'));
        $this->assertSame('stub_datatype', $s->datatype('brutto'));
    }

    public function testUnknownUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Slice())->grid('fortnight');
    }
}
