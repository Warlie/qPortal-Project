<?php

// test/Unit/XmlHandleDownloadTest.php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../classes/handles/Interface_handle.php';
require_once __DIR__ . '/../../classes/handles/XML_handle.php';

/**
 * XML_handle::download_header - ein Ausgabedokument als Datei ausliefern.
 *
 * Gebaut 2026-10-30 fuer den Bestand als OWL-Datei (STW): doctype_out="XML;download:bestand.owl".
 * Ohne die Option bleibt send_header, wie es war.
 *
 * @covers XML_handle
 */
class XmlHandleDownloadTest extends TestCase
{
    private function kopf($type)
    {
        $parser = new stdClass();
        $parser->TYPE = [0 => $type];
        $parser->idx = 0;

        $handle = new XML_handle();
        $handle->set_object($parser);
        return $handle->download_header();
    }

    public function testWithoutOptionNothingChanges()
    {
        $this->assertNull($this->kopf('XML'));
        $this->assertNull($this->kopf('XML;mime:application/rdf+xml'));
    }

    public function testDownloadWithMime()
    {
        $this->assertSame([
            'Content-Type: application/rdf+xml; charset=UTF-8',
            'Content-Disposition: attachment; filename="bestand.owl"',
            'Cache-Control: no-cache, no-store, must-revalidate',
        ], $this->kopf('XML;download:bestand.owl;mime:application/rdf+xml'));
    }

    public function testDefaultMimeIsXml()
    {
        $this->assertSame('Content-Type: application/xml; charset=UTF-8', $this->kopf('XML;download:a.xml')[0]);
    }

    public function testNameKeepsCase()
    {
        $this->assertSame('Content-Disposition: attachment; filename="Bestand_2026.OWL"',
            $this->kopf('XML;download:Bestand_2026.OWL')[1]);
    }

    public function testNameCannotBreakTheHeader()
    {
        // nur der Dateiteil, Anfuehrungszeichen und Zeilenumbruch werden zu _
        $this->assertSame('Content-Disposition: attachment; filename="b_c_.owl"',
            $this->kopf("XML;download:../a/b\"c\r.owl")[1]);
        $this->assertSame('Content-Disposition: attachment; filename="download.xml"',
            $this->kopf('XML;download:..')[1]);
    }

    public function testBadMimeFallsBack()
    {
        $this->assertSame('Content-Type: application/xml; charset=UTF-8',
            $this->kopf("XML;download:a.owl;mime:text/html\r\nX-Evil: 1")[0]);
    }
}
