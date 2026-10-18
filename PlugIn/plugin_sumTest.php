<?php

// PlugIn/plugin_sumTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/plugin_interface.php';
require_once __DIR__ . '/plugin_sum.php';
require_once __DIR__ . '/formula/plugin_formula.php';
require_once __DIR__ . '/../test/Unit/Stubs/RstStubForTesting.php';

use Tests\Stubs\RstStubForTesting;

/**
 * Sum: je Gruppe Summe und Anzahl, dazu die Summe ueber alle (max_sum). Die Eingabe muss
 * NICHT nach der Gruppe sortiert sein - anders als Aggregation.
 *
 * @covers Sum
 */
class plugin_sumTest extends TestCase
{
    /** zwei Vertraege desselben Mieters liegen NICHT beieinander */
    private function rows(): RstStubForTesting
    {
        return new RstStubForTesting([
            ['mieter' => 'human_13', 'soll' => '1130', 'haben' => ''],
            ['mieter' => 'human_4',  'soll' => '500',  'haben' => '500'],
            ['mieter' => 'human_13', 'soll' => '200',  'haben' => '1390'],
        ]);
    }

    private function sum(): Sum
    {
        $s = new Sum();
        $src = $this->rows();
        $s->set_list($src);
        $s->groups('mieter', '');
        $s->sum('soll', '', '', '');
        $s->sum('haben', '', '', '');
        return $s;
    }

    private function readAll($p, array $cols): array
    {
        $out = [];
        if ($p->moveFirst()) {
            do {
                $out[] = implode('|', array_map(fn($c) => strval($p->col($c)), $cols));
            } while ($p->next());
        }
        return $out;
    }

    public function testGroupsUnsortedInput(): void
    {
        $this->assertSame([
            'human_13|1330|1390|2|1830',
            'human_4|500|500|1|1830',
        ], $this->readAll($this->sum(), ['mieter', 'soll.sum', 'haben.sum', 'soll.count', 'soll.max_sum']));
    }

    public function testFieldsAreItsOwn(): void
    {
        $this->assertSame([
            'mieter',
            'soll.sum', 'soll.count', 'soll.max_sum', 'soll.max_count',
            'haben.sum', 'haben.count', 'haben.max_sum', 'haben.max_count',
        ], $this->sum()->fields());
    }

    public function testDatatype(): void
    {
        $s = $this->sum();
        $this->assertSame('decimal(20,6)', $s->datatype('soll.sum'));
        $this->assertSame('decimal(20,6)', $s->datatype('soll.max_sum'));
        $this->assertSame('int(11)', $s->datatype('soll.count'));
        $this->assertSame('stub_datatype', $s->datatype('mieter'), "die Gruppe hat den Typ der Quelle");
        $this->assertFalse($s->datatype('gibt_es.sum'));
    }

    public function testFormulaReadsSum(): void
    {
        $f = new Formula();
        $s = $this->sum();
        $f->set_list($s);
        $f->formula('offen', 'soll.sum - haben.sum');

        $this->assertSame(['human_13|-60', 'human_4|0'], $this->readAll($f, ['mieter', 'offen']));
    }

    /** ohne sortGroups in der Reihenfolge des ersten Auftretens, mit nach dem Wert */
    public function testSortGroups(): void
    {
        $s = new Sum();
        $src = new RstStubForTesting([
            ['jahr' => '2024', 'soll' => '10'],
            ['jahr' => '2022', 'soll' => '1'],
            ['jahr' => '2024', 'soll' => '5'],
            ['jahr' => '2023', 'soll' => '2'],
        ]);
        $s->set_list($src);
        $s->groups('jahr', 'jahr');
        $s->sum('soll', 'soll', '', '');
        $this->assertSame(['2024|15', '2022|1', '2023|2'], $this->readAll($s, ['jahr', 'soll.sum']));

        $s->sortGroups();
        $this->assertSame(['2022|1', '2023|2', '2024|15'], $this->readAll($s, ['jahr', 'soll.sum']));
    }

    public function testOutputName(): void
    {
        $s = new Sum();
        $src = $this->rows();
        $s->set_list($src);
        $s->groups('mieter', 'wer');
        $s->sum('soll', 'forderung', '', '');

        $this->assertSame(['human_13|1330', 'human_4|500'], $this->readAll($s, ['wer', 'forderung.sum']));
    }

    /** wie bei Aggregation: 661 x 8265.88 (Woolworth, 2026-10-18) gab 5463746.6799999 */
    public function testFloatSumHasNoResidue(): void
    {
        $s = new Sum();
        $src = new RstStubForTesting(array_fill(0, 661, ['mieter' => 'construct_40', 'soll' => '8265.88']));
        $s->set_list($src);
        $s->groups('mieter', 'mieter');
        $s->sum('soll', 'soll', '', '');
        $this->assertSame(['construct_40|5463746.68'], $this->readAll($s, ['mieter', 'soll.sum']));
    }
}
