<?php

// PlugIn/lookup/plugin_lookupTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../plugin_interface.php';
require_once __DIR__ . '/plugin_lookup.php';
require_once __DIR__ . '/../../test/Unit/Stubs/RstStubForTesting.php';

use Tests\Stubs\RstStubForTesting;

/**
 * Lookup: je Zeile der Quelle Spalten aus der ersten passenden Zeile einer zweiten Liste.
 *
 * @covers Lookup
 */
class plugin_lookupTest extends TestCase
{
    private function rechnungen(): RstStubForTesting
    {
        return new RstStubForTesting([
            ['qry_cost_invoice.ID' => 3, 'qry_cost_invoice.company' => 'construct_3'],
            ['qry_cost_invoice.ID' => 2, 'qry_cost_invoice.company' => 'human_20'],
            ['qry_cost_invoice.ID' => 1, 'qry_cost_invoice.company' => 'construct_3'],
            ['qry_cost_invoice.ID' => 4, 'qry_cost_invoice.company' => 'construct_999'],
        ]);
    }

    private function akteure(): RstStubForTesting
    {
        return new RstStubForTesting([
            ['qry_actors_owl.ID' => 'construct_3',  'name_html' => 'Stadtwerke'],
            ['qry_actors_owl.ID' => 'human_20',     'name_html' => 'Muster, Erika'],
            ['qry_actors_owl.ID' => 'construct_30', 'name_html' => 'Andere'],
            ['qry_actors_owl.ID' => 'construct_3',  'name_html' => 'Doppelt'],
        ]);
    }

    private function lookup(): Lookup
    {
        $l = new Lookup();
        $q = $this->rechnungen(); $a = $this->akteure();
        $l->set_list($q);
        $l->set_lookup($a);
        $l->key('qry_cost_invoice.company', 'qry_actors_owl.ID');
        $l->take('steller_html', 'name_html');
        return $l;
    }

    private function column(Lookup $l, string $name): array
    {
        $out = [];
        if ($l->moveFirst()) {
            do { $out[] = $l->col($name); } while ($l->next());
        }
        return $out;
    }

    public function testManyRowsMayHitTheSameEntryAndOrderStays(): void
    {
        $l = $this->lookup();
        $this->assertSame(['Stadtwerke', 'Muster, Erika', 'Stadtwerke', ''], $this->column($l, 'steller_html'),
            "construct_3 zweimal getroffen, erster Eintrag gilt, ohne Treffer leer");
        $this->assertSame([3, 2, 1, 4], $this->column($l, 'qry_cost_invoice.ID'), "Quellspalten und Reihenfolge bleiben");
    }

    public function testKeyIsExactNotPrefix(): void
    {
        $l = new Lookup();
        $q = new RstStubForTesting([['k' => 'construct_3'], ['k' => 'construct_30']]);
        $a = new RstStubForTesting([['id' => 'construct_30', 'n' => 'dreissig']]);
        $l->set_list($q); $l->set_lookup($a); $l->key('k', 'id'); $l->take('n', 'n');
        $this->assertSame(['', 'dreissig'], $this->column($l, 'n'));
    }

    public function testMissingCountsRowsWithoutHit(): void
    {
        $l = $this->lookup();
        $this->column($l, 'steller_html');
        $this->assertSame(1, $l->missing());
    }

    public function testFieldsAddTakenColumns(): void
    {
        $l = $this->lookup();
        $l->moveFirst();
        $this->assertSame(['qry_cost_invoice.ID', 'qry_cost_invoice.company', 'steller_html'], $l->fields());
    }

    public function testEmptySourceGivesNoRows(): void
    {
        $l = new Lookup();
        $q = new RstStubForTesting([]); $a = $this->akteure();
        $l->set_list($q); $l->set_lookup($a); $l->key('k', 'qry_actors_owl.ID'); $l->take('n', 'name_html');
        $this->assertFalse($l->moveFirst());
    }
}
