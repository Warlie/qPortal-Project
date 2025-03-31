<?php

//namespace DeinNamespace\Plugins\Tests; // Passe den Namespace an!


use PHPUnit\Framework\TestCase;


class plugin_accumulationTest extends TestCase
{
	
    public static function ProvideTestCases(): array
    {
    	$meineDaten = [
    [
        'id' => 1,
        'name' => 'Max Mustermann',
        'alter' => 30,
        'stadt' => 'Berlin',
        'vermoegen' => 100000.50
    ],
    [
        'id' => 2,
        'name' => 'Erika Musterfrau',
        'alter' => 25,
        'stadt' => 'Hamburg',
        'vermoegen' => 50000.00
    ],
    [
        'id' => 3,
        'name' => 'Hans Dampf',
        'alter' => 40,
        'stadt' => 'München',
        'vermoegen' => 75000.00
    ],
    [
        'id' => 4,
        'name' => 'Peter Lustig',
        'alter' => 60,
        'stadt' => 'Berlin',
        'vermoegen' => 200000.00
    ],
    [
        'id' => 5,
        'name' => 'Anna Blume',
        'alter' => 28,
        'stadt' => 'Köln',
        'vermoegen' => 120000.00
    ],
    [
        'id' => 6,
        'name' => 'Thomas Müller',
        'alter' => 35,
        'stadt' => 'München',
        'vermoegen' => 300000.00
    ]
];
    	
    	return [
    		
                $meineDaten
        ];
    }    
	
    /**
     * @dataProvider ProvideTestCases
     */
    public function testAccumulation()
    {
        $this->assertTrue(true);
        echo "\nTest 'BeispielTest::testBeispiel' erfolgreich geladen.\n";
    }
}

?>