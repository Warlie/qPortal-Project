<?php

// PlugIn/bank_csv/plugin_bank_csvTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../plugin_interface.php';
require_once __DIR__ . '/plugin_bank_csv.php';
require_once __DIR__ . '/../../classes/exceptions/not_a_fieldname_exception.php';

/**
 * BankCsv: Kopfzeile pruefen (DATEV abweisen), BOM weg, UTF-8 -> Latin-1, Kontonummern angleichen.
 * Erfundene Buchungen - keine echten Daten.
 *
 * @covers BankCsv
 */
class plugin_bank_csvTest extends TestCase
{
    const SPARKASSE = 'Auftragskonto;Buchungstag;Valutadatum;Buchungstext;Verwendungszweck;Glaeubiger ID;Mandatsreferenz;Kundenreferenz (End-to-End);Sammlerreferenz;Lastschrift Ursprungsbetrag;Auslagenersatz Ruecklastschrift;Beguenstigter/Zahlungspflichtiger;Kontonummer/IBAN;BIC (SWIFT-Code);Betrag;Waehrung;Info';
    const VOLKSBANK = 'Bezeichnung Auftragskonto;IBAN Auftragskonto;BIC Auftragskonto;Bankname Auftragskonto;Buchungstag;Valutadatum;Name Zahlungsbeteiligter;IBAN Zahlungsbeteiligter;BIC (SWIFT-Code) Zahlungsbeteiligter;Buchungstext;Verwendungszweck;Betrag;Waehrung';

    private string $quelle;
    private string $ziel = 'test/tmp_bank_csv/inbox.csv';

    protected function setUp(): void
    {
        $this->quelle = tempnam(sys_get_temp_dir(), 'bankcsv');
        @mkdir(dirname($this->ziel), 0777, true);
        @unlink($this->ziel);
    }

    protected function tearDown(): void
    {
        @unlink($this->quelle);
        @unlink($this->ziel);
        @rmdir(dirname($this->ziel));
    }

    private function bank(): BankCsv
    {
        $b = new BankCsv();
        $b->pattern('sparkasse', self::SPARKASSE);
        $b->pattern('volksbank', self::VOLKSBANK);
        return $b;
    }

    private function sparkassenzeile(string $konto, string $zweck = 'Miete'): string
    {
        return "154963;02.01.25;02.01.25;GUTSCHRIFT;$zweck;;;;;;;Erika Muster;$konto;;100,00;EUR;Umsatz gebucht";
    }

    public function testSparkasseUtf8BecomesLatin1AndZerosAreStripped(): void
    {
        file_put_contents($this->quelle, implode("\r\n", [self::SPARKASSE, $this->sparkassenzeile('0000123456', 'Miete März'), $this->sparkassenzeile('0000000000'), $this->sparkassenzeile('DE02120300000000202051')]) . "\r\n");
        $b = $this->bank();

        $this->assertSame('true', $b->prepare($this->quelle, $this->ziel));
        $this->assertTrue($b->moveFirst());
        $this->assertSame(['angenommen', 'sparkasse', 'UTF-8', 3, 2], [$b->col('status'), $b->col('format'), $b->col('kodierung'), $b->col('zeilen'), $b->col('nullen')]);

        $out = file_get_contents($this->ziel);
        $this->assertStringContainsString("Miete M\xE4rz", $out, "Latin-1: ä ist ein Byte");
        $this->assertStringContainsString(';123456;', $out);
        $this->assertStringContainsString(';0;', $out);
        $this->assertStringContainsString(';DE02120300000000202051;', $out, "eine IBAN bleibt");
        $this->assertStringEndsWith("\r\n", $out);
    }

    public function testLatin1StaysByteForByte(): void
    {
        $roh = implode("\r\n", [self::SPARKASSE, $this->sparkassenzeile('DE02120300000000202051', "Miete M\xE4rz")]) . "\r\n";
        file_put_contents($this->quelle, $roh);
        $b = $this->bank();

        $this->assertSame('true', $b->prepare($this->quelle, $this->ziel));
        $b->moveFirst();
        $this->assertSame('ISO-8859-1', $b->col('kodierung'));
        $this->assertSame($roh, file_get_contents($this->ziel));
    }

    public function testVolksbankBomIsRemoved(): void
    {
        $zeile = 'Konto;DE43586619010005205531;GENODED1WSC;Volksbank;02.01.2025;02.01.2025;Max Muster;DE02120300000000202051;BYLADEM1001;Gutschrift;Miete;100,00;EUR';
        file_put_contents($this->quelle, "\xEF\xBB\xBF" . self::VOLKSBANK . ";Saldo nach Buchung\n" . $zeile . ";1000,00\n");
        $b = $this->bank();

        $this->assertSame('true', $b->prepare($this->quelle, $this->ziel));
        $b->moveFirst();
        $this->assertSame('volksbank', $b->col('format'), "Teilmenge: zusaetzliche Spalten stoeren nicht");
        $this->assertStringStartsWith('Bezeichnung Auftragskonto', file_get_contents($this->ziel));
    }

    /** STW: DATEV kuerzt die Texte - neue ID, doppelte Buchung. Das Ziel bleibt unberuehrt. */
    public function testDatevIsRejectedAndTargetUntouched(): void
    {
        file_put_contents($this->ziel, 'vorher');
        file_put_contents($this->quelle, '"WUPSDE33XXX";"DE07330500000000154963";;;02.01.2025;02.01.2025;-504,00;"Muster";;;"DE41550104000428562326";"gekuerzt"' . "\r\n");
        $b = $this->bank();

        $this->assertSame('false', $b->prepare($this->quelle, $this->ziel));
        $b->moveFirst();
        $this->assertSame('abgewiesen', $b->col('status'));
        $this->assertStringContainsString('DATEV', $b->col('meldung'));
        $this->assertSame('vorher', file_get_contents($this->ziel));
        $this->assertSame('false', $b->accepted());
    }

    public function testUnknownHeaderIsRejected(): void
    {
        file_put_contents($this->quelle, "Auftragskonto;Buchungstag;Valutadatum;Buchungstext;Verwendungszweck;Beguenstigter/Zahlungspflichtiger;Kontonummer;BLZ;Betrag;Waehrung;Info\r\n");
        $b = $this->bank();

        $this->assertSame('false', $b->prepare($this->quelle, $this->ziel));
        $b->moveFirst();
        $this->assertStringContainsString('Unbekanntes Format', $b->col('meldung'));
        $this->assertFileDoesNotExist($this->ziel);
    }

    public function testQuotedLineAbortsZeroStripping(): void
    {
        file_put_contents($this->quelle, self::SPARKASSE . "\r\n" . '154963;02.01.25;02.01.25;GUTSCHRIFT;"Miete; Maerz";;;;;;;Erika;0000123456;;100,00;EUR;x' . "\r\n");
        $b = $this->bank();

        $this->assertSame('false', $b->prepare($this->quelle, $this->ziel));
        $b->moveFirst();
        $this->assertStringContainsString('bytegleich', $b->col('meldung'));
    }

    public function testTargetOutsideInstallationIsRejected(): void
    {
        file_put_contents($this->quelle, self::SPARKASSE . "\r\n");
        $b = $this->bank();

        $this->assertSame('false', $b->prepare($this->quelle, '../ausserhalb.csv'));
        $b->moveFirst();
        $this->assertStringContainsString('ausserhalb', $b->col('meldung'));
    }
}
