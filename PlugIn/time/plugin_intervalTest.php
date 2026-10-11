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
}
