<?PHP
/**
 * Dataprovider
 *
 * may be put in external file
 */

final class ExternalDataProvider
{
    public static function PlugInTestDataProvider(): array
    {   //provides Testdata for PlugIn-Classes
        $testData1= [
            ['grp' => 'A', 'wert' => 10,    'date' => '7.22'],
            ['grp' => 'B', 'wert' => 100,   'date' => '7.22'],
            ['grp' => 'A', 'wert' => -5,     'date' => '7.22'],
            ['grp' => 'B', 'wert' => 20,    'date' => '8.22']
        ];
        
        // SHOULD BE CHANGED FOR EFECTIVE TESTS
        $testData2= [
            ['grp' => 'A', 'wert' => 10,    'date' => '7.22'],
            ['grp' => 'B', 'wert' => 100,   'date' => '7.22'],
            ['grp' => 'A', 'wert' => -5,     'date' => '7.22'],
            ['grp' => 'B', 'wert' => 20,    'date' => '8.22']
        ];
        
        $testData=[[$testData1], [$testData2]];
        
        return $testData;
    }
}
?>