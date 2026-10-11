<?php

// PlugIn/time/plugin_intervalTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../plugin_interface.php';
require_once __DIR__ . '/plugin_interval.php';
require_once __DIR__ . '/../aggregation/plugin_aggregation.php';
require_once __DIR__ . '/../../test/Unit/Stubs/RstStubForTesting.php';

use Tests\Stubs\RstStubForTesting;

/** Quelle mit Datentypen (der gemeinsame Stub kennt nur 'stub_datatype') */
class IntervalSourceStub extends RstStubForTesting
{
    private array $types;

    public function __construct(array $data, array $types)
    {
        parent::__construct($data);
        $this->types = $types;
    }

    public function datatype($columnname): string
    {
        return $this->types[$columnname] ?? '';   // leer = unbekannt
    }
}

/**
 * Interval: Zeitraster, in das Datensaetze einer Quelle fallen.
 *
 * @covers Interval
 */
class plugin_intervalTest extends TestCase
{
    /** ein Monatsraster; $source optional */
    private function monthly(string $from, string $length, $source = null, string $format = ''): Interval
    {
        $grid = new Interval();
        if ($source) $grid->set_list($source);
        $grid->getDate($from, $format, $length);
        $grid->setInterval(1, '+1 month');
        $grid->setColumns('start', '', ',', 'betrag', '-690', '');
        return $grid;
    }

    private function column($plugin, string $name): array
    {
        $out = [];
        if ($plugin->moveFirst()) {
            do { $out[] = $plugin->col($name); } while ($plugin->next());
        }
        return $out;
    }

    public function testGridHasOneSlotPerMonth(): void
    {
        $this->assertSame(
            ['2024-01-01 00:00:00', '2024-02-01 00:00:00', '2024-03-01 00:00:00', '2024-04-01 00:00:00'],
            $this->column($this->monthly('2024-01-01', '+4 month'), 'start-dt'));
    }

    public function testGridEndDoesNotDependOnTheTimeOfDay(): void
    {
        // mit Format nahm createFromFormat die aktuelle Uhrzeit mit - ein Platz zu viel
        $this->assertCount(4, $this->column($this->monthly('2024-01-01', '+4 month', null, 'Y-m-d'), 'start-dt'));
    }

    public function testSecondsAreReal(): void
    {
        $this->assertSame([31 * 86400, 29 * 86400],
            $this->column($this->monthly('2024-01-01', '+2 month'), 'interval-in_seconds'));
    }

    public function testRecordReplacesItsSlotAndTheRestIsOrderedByTime(): void
    {
        $source = new IntervalSourceStub([
            ['start' => '2024-02-01 00:00:00', 'betrag' => '700', 'note' => 'auf dem Platz'],
            ['start' => '2023-12-15 00:00:00', 'betrag' => '5',   'note' => 'vor dem Raster'],
            ['start' => '2024-01-20 10:30:00', 'betrag' => '6',   'note' => 'mitten drin'],
        ], ['betrag' => 'decimal(10,2)']);
        $grid = $this->monthly('2024-01-01', '+3 month', $source);

        $this->assertSame(
            ['2023-12-15 00:00:00', '2024-01-01 00:00:00', '2024-01-20 10:30:00', '2024-02-01 00:00:00', '2024-03-01 00:00:00'],
            $this->column($grid, 'start-dt'));
        $this->assertSame(['5', '-690', '6', '700', '-690'], array_map('strval', $this->column($grid, 'betrag')));
    }

    public function testWeeklyHolidaysOnly(): void
    {
        $grid = new Interval();
        $grid->getDate('2024-05-01', '', '+1 month', 'first day of this month');
        $grid->setConfigString('weekly{ ho{9:00(+8hours)} }');
        $grid->setColumns('start', '', ',', 'x', '', '');

        $days = array_map(fn($dt) => substr($dt, 0, 10), $this->column($grid, 'start-dt'));
        $this->assertContains('2024-05-01', $days, "der erste Feiertag fehlte");
        foreach (['2024-05-08', '2024-05-15', '2024-05-22', '2024-05-29'] as $wednesday)
            $this->assertNotContains($wednesday, $days, "nur Feiertage, nicht jeder Mittwoch");
    }

    public function testStartEndFormat(): void
    {
        $grid = new Interval();
        $grid->getDate('2024-01-01', '', '+1 day');
        $grid->setInterval(1, '+8 hour');
        $grid->setIntervalFormat('H.i', 'start_end');
        $grid->setColumns('start', '', ',', 'x', '', '');
        $grid->moveFirst();
        $this->assertSame('00.00-08.00', $grid->col('interval-time'));
    }

    public function testFieldsAreTheColumnsOfASlot(): void
    {
        $grid = $this->monthly('2024-01-01', '+1 month');
        $grid->moveFirst();
        foreach ($grid->fields() as $field)
            $this->assertNotNull($grid->col($field) ?? ($field === 'note' ? '' : null), "fields() nennt '$field', col() kennt es nicht");
        $this->assertContains('betrag', $grid->fields());
        $this->assertContains('start-dt', $grid->fields());
    }

    public function testDatatype(): void
    {
        $grid = $this->monthly('2024-01-01', '+1 month');
        $this->assertSame('datetime', $grid->datatype('start-dt'));
        $this->assertSame('bigint(20)', $grid->datatype('interval-in_seconds'));
        $this->assertSame('decimal(20,6)', $grid->datatype('betrag'), "Vorgabe -690 ist eine Zahl");
        $this->assertFalse($grid->datatype('gibt_es_nicht'));

        $source = new IntervalSourceStub([['start' => '2024-01-01 00:00:00', 'betrag' => '1', 'note' => '']], ['betrag' => 'decimal(10,2)']);
        $this->assertSame('decimal(10,2)', $this->monthly('2024-01-01', '+1 month', $source)->datatype('betrag'), "die Quelle weiss es");
    }

    /** der Fall der Miete: Soll als Raster (negativ), Ist als Datensaetze (positiv), Aggregation = Saldo */
    public function testAggregationOverIntervalGivesTheBalance(): void
    {
        $payments = new IntervalSourceStub([
            ['start' => '2024-01-03 00:00:00', 'betrag' => '690', 'note' => 'Miete Januar'],
            ['start' => '2024-02-05 00:00:00', 'betrag' => '690', 'note' => 'Miete Februar'],
        ], ['betrag' => 'decimal(10,2)']);
        $grid = $this->monthly('2024-01-01', '+3 month', $payments);

        $sum = new Aggregation();
        $sum->set_list($grid);

        $this->assertTrue($sum->moveFirst());
        $this->assertEqualsWithDelta(-690.0, $sum->col('betrag'), 0.0001, "3 x -690 Soll + 2 x 690 Ist");
        $this->assertFalse($sum->next());
    }

    // ===== Auffaechern (setExpand): eine Zeile ist ein Zeitraum =====

    /** Mietgegenstaende wie im Triebel-Vertrag; date-Spalten wie DBO sie liefert ('Y-m-d') */
    private function items(): IntervalSourceStub
    {
        return new IntervalSourceStub([
            ['agreement' => 'agreement_3', 'unit' => 'Laden',   'net_rent' => '650.00', 'start_date' => '2022-01-01', 'end_date' => '2024-01-01'],
            ['agreement' => 'agreement_3', 'unit' => 'Laden',   'net_rent' => '690.00', 'start_date' => '2024-01-01', 'end_date' => ''],
            ['agreement' => 'agreement_3', 'unit' => 'Wohnung', 'net_rent' => '480.00', 'start_date' => '2022-01-01', 'end_date' => ''],
        ], ['net_rent' => 'decimal(10,2)', 'agreement' => 'varchar(20)', 'unit' => 'varchar(20)']);
    }

    private function expanded($source, string $from, string $length): Interval
    {
        $soll = new Interval();
        $soll->set_list($source);
        if ($from !== '') $soll->getDate($from, '', $length);
        $soll->setColumns('start_date', '', ',', 'agreement,unit,net_rent', ',,', '');
        $soll->setExpand('end_date');
        return $soll;
    }

    private function rowsOf($plugin, array $cols): array
    {
        $out = [];
        if ($plugin->moveFirst()) {
            do {
                $out[] = implode('|', array_map(fn($c) => strval($plugin->col($c)), $cols));
            } while ($plugin->next());
        }
        return $out;
    }

    public function testExpandGivesOneRowPerItemAndMonth(): void
    {
        // Nov 2023 bis Feb 2024: Laden 650 -> 690 zum Jahreswechsel, Wohnung durchgehend 480
        $rows = $this->rowsOf($this->expanded($this->items(), '2023-11-01', '+4 month'), ['start-dt', 'unit', 'net_rent']);
        $this->assertSame([
            '2023-11-01 00:00:00|Laden|650.00',   '2023-11-01 00:00:00|Wohnung|480.00',
            '2023-12-01 00:00:00|Laden|650.00',   '2023-12-01 00:00:00|Wohnung|480.00',
            '2024-01-01 00:00:00|Laden|690.00',   '2024-01-01 00:00:00|Wohnung|480.00',
            '2024-02-01 00:00:00|Laden|690.00',   '2024-02-01 00:00:00|Wohnung|480.00',
        ], $rows);
    }

    public function testPriceChangeMidMonthHasNoGapAndNoDouble(): void
    {
        // Stichtag Monatserster: der Maerz gehoert noch dem alten Preis (gilt am 1.3.), der neue ab 1.4.
        $source = new IntervalSourceStub([
            ['agreement' => 'a', 'unit' => 'u', 'net_rent' => '500', 'start_date' => '2024-01-01', 'end_date' => '2024-03-15'],
            ['agreement' => 'a', 'unit' => 'u', 'net_rent' => '520', 'start_date' => '2024-03-15', 'end_date' => ''],
        ], ['net_rent' => 'decimal(10,2)']);
        $rows = $this->rowsOf($this->expanded($source, '2024-01-01', '+5 month'), ['start-month', 'net_rent']);
        $this->assertSame(['1|500', '2|500', '3|500', '4|520', '5|520'], $rows);
    }

    public function testOpenEndRunsThroughTheCurrentMonth(): void
    {
        $start = (new DateTime('first day of this month'))->modify('-2 month')->format('Y-m-d');
        $source = new IntervalSourceStub([
            ['agreement' => 'a', 'unit' => 'u', 'net_rent' => '100', 'start_date' => $start, 'end_date' => ''],
        ], ['net_rent' => 'decimal(10,2)']);
        $this->assertCount(3, $this->rowsOf($this->expanded($source, '', ''), ['start-dt']),
            "zwei Monate zurueck bis einschliesslich des laufenden");
    }

    public function testPointRecordStaysOnePoint(): void
    {
        $source = new IntervalSourceStub([
            ['agreement' => 'a', 'unit' => 'u', 'net_rent' => '-50', 'start_date' => '2024-02-10', 'end_date' => '2024-02-10'],
        ], ['net_rent' => 'decimal(10,2)']);
        $this->assertSame(['2024-02-10 00:00:00|-50'], $this->rowsOf($this->expanded($source, '', ''), ['start-dt', 'net_rent']),
            "Ende = Beginn ist ein Zeitpunkt (eine Gutschrift), kein Zeitraum");
    }

    public function testExpandGuardStopsAStepThatDoesNotMove(): void
    {
        $soll = $this->expanded($this->items(), '', '');
        $soll->setExpand('end_date', '+0 month');
        $this->expectException(Exception::class);
        $soll->moveFirst();
    }

    public function testPartitionKeepsGroupsTogetherForAggregation(): void
    {
        // zwei Vertraege, absichtlich durcheinander; je Vertrag die Soll-Summe Jan-Mrz 2024
        $source = new IntervalSourceStub([
            ['agreement' => 'agreement_7', 'unit' => 'Garage',  'net_rent' => '50',  'start_date' => '2023-06-01', 'end_date' => ''],
            ['agreement' => 'agreement_3', 'unit' => 'Laden',   'net_rent' => '690', 'start_date' => '2024-01-01', 'end_date' => ''],
            ['agreement' => 'agreement_3', 'unit' => 'Wohnung', 'net_rent' => '480', 'start_date' => '2022-01-01', 'end_date' => ''],
        ], ['net_rent' => 'decimal(10,2)', 'agreement' => 'varchar(20)', 'unit' => 'varchar(20)']);
        $soll = $this->expanded($source, '2024-01-01', '+3 month');
        $soll->setPartition('agreement');

        $sum = new Aggregation();
        $sum->set_list($soll);
        $sum->group('agreement');
        $res = $this->rowsOf($sum, ['agreement', 'net_rent']);

        $this->assertSame(['agreement_3|3510', 'agreement_7|150'], $res, "3 x (690 + 480) und 3 x 50");
    }
}
