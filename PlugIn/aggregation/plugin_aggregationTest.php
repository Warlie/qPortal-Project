<?php

// PlugIn/aggregation/plugin_aggregationTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../plugin_interface.php';
require_once __DIR__ . '/plugin_aggregation.php';
require_once __DIR__ . '/../../test/Unit/Stubs/RstStubForTesting.php';

use Tests\Stubs\RstStubForTesting;

/**
 * Der gemeinsame Stub kennt nur den Typ 'stub_datatype' - Aggregation entscheidet aber nach dem
 * Datentyp, ob summiert (DECIMAL, INT, ...) oder als Text verbunden wird. Darum hier mit Typen.
 */
class TypedRstStub extends RstStubForTesting
{
    private array $types;

    public function __construct(array $data, array $types)
    {
        parent::__construct($data);
        $this->types = $types;
    }

    public function datatype($columnname): string
    {
        return $this->types[$columnname] ?? 'varchar(255)';
    }
}

/**
 * Aggregation: eine Zeile je Gruppe (wie GROUP BY), die Eingabe ist nach der Gruppe vorsortiert.
 * Zahlenspalten werden summiert, Text wird verschiedenwertig mit Komma verbunden.
 *
 * @covers Aggregation
 */
class plugin_aggregationTest extends TestCase
{
    private function rows(): array
    {
        // Soll negativ, Ist positiv - je Mieter der Saldo
        return [
            ['party' => 'human_13', 'betrag' => '-690.00', 'art' => 'Soll'],
            ['party' => 'human_13', 'betrag' => '415.00',  'art' => 'Ist'],
            ['party' => 'human_13', 'betrag' => '280.00',  'art' => 'Ist'],
            ['party' => 'human_4',  'betrag' => '-500.00', 'art' => 'Soll'],
            ['party' => 'human_4',  'betrag' => '500.00',  'art' => 'Ist'],
        ];
    }

    private function types(): array
    {
        return ['party' => 'varchar(50)', 'betrag' => 'decimal(10,2)', 'art' => 'varchar(10)'];
    }

    private function aggregation(array $rows): Aggregation
    {
        $agg = new Aggregation();
        $stub = new TypedRstStub($rows, $this->types());
        $agg->set_list($stub);
        $agg->group('party');
        return $agg;
    }

    /** liest alle Gruppen wie ein Konsument: moveFirst, col, next */
    private function readAll(Aggregation $agg): array
    {
        $out = [];
        if ($agg->moveFirst()) {
            do {
                $out[] = [$agg->col('party'), $agg->col('betrag'), $agg->col('art')];
            } while ($agg->next());
        }
        return $out;
    }

    public function testEveryGroupIncludingTheLast(): void
    {
        $res = $this->readAll($this->aggregation($this->rows()));

        $this->assertCount(2, $res, "zwei Mieter = zwei Zeilen; die letzte Gruppe darf nicht fehlen");
        $this->assertSame('human_13', $res[0][0]);
        $this->assertEqualsWithDelta(5.0, $res[0][1], 0.0001, "human_13: -690 + 415 + 280");
        $this->assertSame('Soll,Ist', $res[0][2], "Text: verschiedene Werte, mit Komma verbunden");
        $this->assertSame('human_4', $res[1][0]);
        $this->assertEqualsWithDelta(0.0, $res[1][1], 0.0001, "human_4: -500 + 500");
    }

    public function testLastGroupWithASingleRow(): void
    {
        $rows = $this->rows();
        $rows[] = ['party' => 'human_9', 'betrag' => '12.50', 'art' => 'Ist'];
        $res = $this->readAll($this->aggregation($rows));

        $this->assertCount(3, $res);
        $this->assertSame('human_9', $res[2][0]);
        $this->assertEqualsWithDelta(12.5, $res[2][1], 0.0001);
    }

    public function testSingleGroup(): void
    {
        $res = $this->readAll($this->aggregation(array_slice($this->rows(), 0, 3)));

        $this->assertCount(1, $res);
        $this->assertEqualsWithDelta(5.0, $res[0][1], 0.0001);
    }

    public function testSingleRow(): void
    {
        $res = $this->readAll($this->aggregation(array_slice($this->rows(), 0, 1)));

        $this->assertCount(1, $res);
        $this->assertEqualsWithDelta(-690.0, $res[0][1], 0.0001);
    }

    public function testSecondPassGivesTheSame(): void
    {
        $agg = $this->aggregation($this->rows());
        $first = $this->readAll($agg);
        $second = $this->readAll($agg);

        $this->assertSame($first, $second, "moveFirst muss den Zustand zuruecksetzen");
    }

    public function testNextAfterTheEndStaysFalse(): void
    {
        $agg = $this->aggregation($this->rows());
        $this->assertTrue($agg->moveFirst());
        $this->assertTrue($agg->next(), "zweite Gruppe");
        $this->assertFalse($agg->next(), "keine dritte Gruppe");
        $this->assertFalse($agg->next(), "auch danach nicht");
        $this->assertSame('human_4', $agg->col('party'), "die letzte Gruppe bleibt lesbar");
    }

    public function testEmptySource(): void
    {
        $agg = new Aggregation();
        $stub = new TypedRstStub([], $this->types());
        $agg->set_list($stub);
        $agg->group('party');

        $this->assertFalse($agg->moveFirst());
    }

    public function testWithoutGroupEverythingIsOneRow(): void
    {
        $agg = new Aggregation();
        $stub = new TypedRstStub($this->rows(), $this->types());
        $agg->set_list($stub);
        $res = $this->readAll($agg);

        $this->assertCount(1, $res, "ohne group ist alles eine Gruppe");
        $this->assertEqualsWithDelta(5.0, $res[0][1], 0.0001, "-690 + 415 + 280 - 500 + 500");
        $this->assertSame('human_13,human_4', $res[0][0]);
    }
}
