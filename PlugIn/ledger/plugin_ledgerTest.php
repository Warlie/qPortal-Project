<?php

// PlugIn/ledger/plugin_ledgerTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../plugin_interface.php';
require_once __DIR__ . '/plugin_ledger.php';
require_once __DIR__ . '/../time/plugin_interval.php';
require_once __DIR__ . '/../aggregation/plugin_aggregation.php';
require_once __DIR__ . '/../../test/Unit/Stubs/RstStubForTesting.php';

use Tests\Stubs\RstStubForTesting;

/** Quelle mit Datentypen (der gemeinsame Stub kennt nur 'stub_datatype') */
class LedgerSourceStub extends RstStubForTesting
{
    private array $types;

    public function __construct(array $data, array $types)
    {
        parent::__construct($data);
        $this->types = $types;
    }

    public function datatype($columnname): string
    {
        return $this->types[$columnname] ?? '';
    }
}

/**
 * Ledger: Eintraege (Haben) neben die passende Rasterzeile (Soll), danach je Monat summiert.
 *
 * @covers Ledger
 */
class plugin_ledgerTest extends TestCase
{
    /** Soll wie im Triebel-Vertrag: Laden 690, Wohnung 480, Jan bis Maerz 2026 */
    private function soll(): Interval
    {
        $items = new LedgerSourceStub([
            ['agreement' => 'agreement_3', 'tenant' => 'human_13', 'unit' => 'Laden',   'net_rent' => '690.00', 'start_date' => '2022-01-01', 'end_date' => ''],
            ['agreement' => 'agreement_3', 'tenant' => 'human_13', 'unit' => 'Wohnung', 'net_rent' => '480.00', 'start_date' => '2022-01-01', 'end_date' => ''],
        ], ['net_rent' => 'decimal(10,2)', 'agreement' => 'varchar(20)', 'tenant' => 'varchar(50)', 'unit' => 'varchar(20)']);

        $soll = new Interval();
        $soll->set_list($items);
        $soll->getDate('2026-01-01', '', '+3 month');
        $soll->setColumns('start_date', '', ',', 'agreement,tenant,unit,net_rent', ',,,', '');
        $soll->setExpand('end_date');
        return $soll;
    }

    /** Zahlungen wie DBO sie liefert: date 'Y-m-d' */
    private function payments(array $rows): LedgerSourceStub
    {
        return new LedgerSourceStub($rows, ['value' => 'decimal(10,2)', 'party' => 'varchar(50)', 'booking_date' => 'date', 'purpose' => 'varchar(255)']);
    }

    private function ledger($payments): Ledger
    {
        $l = new Ledger();
        $soll = $this->soll();
        $l->set_list($soll);
        $l->set_entries($payments);
        $l->sameAs('tenant', 'party');
        $l->between('start-dt', 'end-dt', 'booking_date');
        $l->inherit('monat', 'start-dt');
        $l->inherit('agreement', 'agreement');
        $l->frame('soll', 'net_rent');
        $l->entry('haben', 'value');
        $l->frame('text', 'unit');
        $l->entry('zweck', 'purpose');
        return $l;
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

    private function triebel(): LedgerSourceStub
    {
        return $this->payments([
            ['party' => 'human_13', 'booking_date' => '2026-01-01', 'value' => '415.00', 'purpose' => 'Miete Laden Jan'],
            ['party' => 'human_13', 'booking_date' => '2026-01-31', 'value' => '280.00', 'purpose' => 'Miete Wohnung Jan'],
            ['party' => 'human_13', 'booking_date' => '2026-02-01', 'value' => '695.00', 'purpose' => 'Miete Feb'],
            ['party' => 'human_99', 'booking_date' => '2026-02-10', 'value' => '50.00',  'purpose' => 'fremd'],
        ]);
    }

    public function testEntriesFollowTheirRowAndTheFirstOfTheMonthBelongsToIt(): void
    {
        $this->assertSame([
            'frame|2026-01-01 00:00:00|690.00||Laden|',
            'entry|2026-01-01 00:00:00||415.00||Miete Laden Jan',
            'entry|2026-01-01 00:00:00||280.00||Miete Wohnung Jan',
            'frame|2026-01-01 00:00:00|480.00||Wohnung|',
            'frame|2026-02-01 00:00:00|690.00||Laden|',
            'entry|2026-02-01 00:00:00||695.00||Miete Feb',
            'frame|2026-02-01 00:00:00|480.00||Wohnung|',
            'frame|2026-03-01 00:00:00|690.00||Laden|',
            'frame|2026-03-01 00:00:00|480.00||Wohnung|',
        ], $this->rowsOf($this->ledger($this->triebel()), ['ledger-row', 'monat', 'soll', 'haben', 'text', 'zweck']),
           "eine Zahlung gehoert der ERSTEN passenden Zeile - nicht Laden UND Wohnung");
    }

    public function testForeignPartyIsNotAssigned(): void
    {
        $l = $this->ledger($this->triebel());
        $l->moveFirst();
        $this->assertSame(1, $l->unassigned(), "human_99 ist kein Mieter des Vertrags");
    }

    public function testAggregationSumsSollAndHabenPerMonth(): void
    {
        $agg = new Aggregation();
        $l = $this->ledger($this->triebel());
        $agg->set_list($l);
        $agg->group('monat');

        $res = [];
        if ($agg->moveFirst()) {
            do {
                $res[] = [$agg->col('monat'), $agg->col('soll'), $agg->col('haben'), $agg->col('zweck')];
            } while ($agg->next());
        }

        $this->assertCount(3, $res);
        $this->assertEqualsWithDelta(1170.0, $res[0][1], 0.0001);
        $this->assertEqualsWithDelta(695.0, $res[0][2], 0.0001, "415 + 280 im Januar");
        $this->assertSame('Miete Laden Jan,Miete Wohnung Jan', $res[0][3], "Texte verbunden, die leeren der Soll-Zeilen zaehlen nicht");
        $this->assertEqualsWithDelta(695.0, $res[1][2], 0.0001, "die Zahlung vom 1.2. gehoert dem Februar");
        $this->assertEqualsWithDelta(0.0, $res[2][2], 0.0001);
    }

    public function testTenantListMatchesEachMember(): void
    {
        $frame = new LedgerSourceStub([
            ['from' => '2026-01-01', 'until' => '2026-02-01', 'tenants' => 'human_13,human_14', 'rent' => '1000'],
        ], ['rent' => 'decimal(10,2)']);
        $l = new Ledger();
        $l->set_list($frame);
        $pay = $this->payments([
            ['party' => 'human_14', 'booking_date' => '2026-01-05', 'value' => '500', 'purpose' => ''],
            ['party' => 'human_1',  'booking_date' => '2026-01-05', 'value' => '1',   'purpose' => ''],
        ]);
        $l->set_entries($pay);
        $l->sameAs('tenants', 'party');
        $l->between('from', 'until', 'booking_date');
        $l->entry('haben', 'value');

        $this->assertSame(['', '500'], $this->rowsOf($l, ['haben']), "human_14 steht in der Liste, human_1 nicht");
    }

    public function testOpenUntilAcceptsEverythingLater(): void
    {
        $frame = new LedgerSourceStub([['from' => '2026-01-01', 'until' => '']], []);
        $l = new Ledger();
        $l->set_list($frame);
        $pay = $this->payments([['party' => '', 'booking_date' => '2030-12-31', 'value' => '7', 'purpose' => '']]);
        $l->set_entries($pay);
        $l->between('from', 'until', 'booking_date');
        $l->entry('haben', 'value');

        $this->assertSame(['', '7'], $this->rowsOf($l, ['haben']));
    }

    public function testCompareOnTheCoarserPrecision(): void
    {
        $this->assertSame(0,  Ledger::compare('2026-02-01', '2026-02-01 00:00:00'));
        $this->assertSame(-1, Ledger::compare('2026-01-31', '2026-02-01 00:00:00'));
        $this->assertSame(1,  Ledger::compare('2026-02-02', '2026-02-01 00:00:00'));
    }

    public function testDatatypesComeFromTheSources(): void
    {
        $l = $this->ledger($this->triebel());
        $this->assertSame('decimal(10,2)', $l->datatype('soll'));
        $this->assertSame('decimal(10,2)', $l->datatype('haben'), "Raster hat keine Spalte - der Typ kommt aus den Eintraegen");
        $this->assertSame('datetime', $l->datatype('monat'));
        $this->assertFalse($l->datatype('gibt_es_nicht'));
    }

    public function testSecondPassGivesTheSame(): void
    {
        $l = $this->ledger($this->triebel());
        $cols = ['ledger-row', 'monat', 'haben'];
        $this->assertSame($this->rowsOf($l, $cols), $this->rowsOf($l, $cols));
    }

    public function testEmptyEntriesGiveTheFrame(): void
    {
        $this->assertCount(6, $this->rowsOf($this->ledger($this->payments([])), ['soll']));
    }
}
