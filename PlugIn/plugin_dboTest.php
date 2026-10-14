<?php

// PlugIn/plugin_dboTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/plugin_interface.php';
require_once __DIR__ . '/plugin_dbo.php';
require_once __DIR__ . '/../test/Unit/Stubs/RstStubForTesting.php';

use Tests\Stubs\RstStubForTesting;

/** Faengt das SQL ab, das DBO::execute() bauen wuerde - ohne Datenbank. */
class DboSqlCatcher
{
    public string $sql = '';
    public function set_db_encode($enc) {}
    public function escape($s) { return addslashes($s); }
    public function get_rst($sql) { $this->sql = $sql; return new DboEmptyRst(); }
}

class DboEmptyRst
{
    public function first_ds() { return false; }
}

/**
 * setWhereRST: die Werte einer Menge als ODER-Liste. Eine leere Menge heisst "trifft nichts".
 *
 * @covers DBO
 */
class plugin_dboTest extends TestCase
{
    private function sqlFor(array $rows, bool $isString = true): string
    {
        $db = new DboSqlCatcher();
        $dbo = new DBO($db);
        $dbo->setStatement('SELECT * FROM t');
        $rst = new RstStubForTesting($rows);
        $dbo->setWhereRST('t.unit', '=', $rst, 'id', $isString);
        $dbo->execute();
        return $db->sql;
    }

    /** vorher: name = '' plus Deprecated trim(null); bei einer Zahlenspalte traf das ID 0 */
    public function testEmptySetMatchesNothing(): void
    {
        $this->assertSame('SELECT * FROM t WHERE 1 = 0;', $this->sqlFor([]));
        $this->assertSame('SELECT * FROM t WHERE 1 = 0;', $this->sqlFor([], false));
    }

    public function testOneValue(): void
    {
        $this->assertSame("SELECT * FROM t WHERE t.unit = '7';", $this->sqlFor([['id' => '7']], false));
    }

    public function testSeveralValuesAreBracketedOr(): void
    {
        $this->assertSame(
            'SELECT * FROM t WHERE (t.unit = 7 OR t.unit = 9);',
            $this->sqlFor([['id' => '7'], ['id' => '9']])
        );
    }

    /** davor stehende Bedingungen bleiben stehen - die leere Menge macht das Ganze falsch */
    public function testEmptySetAfterOtherCondition(): void
    {
        $db = new DboSqlCatcher();
        $dbo = new DBO($db);
        $dbo->setStatement('SELECT * FROM t');
        $dbo->setWhere('t.a', '=', 'x', false);
        $rst = new RstStubForTesting([]);
        $dbo->setWhereRST('t.unit', '=', $rst, 'id');
        $dbo->execute();
        $this->assertSame("SELECT * FROM t WHERE t.a = 'x' AND 1 = 0;", $db->sql);
    }
}
