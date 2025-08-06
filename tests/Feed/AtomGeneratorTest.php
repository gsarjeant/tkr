<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AtomGeneratorTest extends TestCase
{
    private function createMockConfig() {
        $mockPdo = $this->createMock(PDO::class);
        $config = new ConfigModel($mockPdo);
        $config->siteTitle = 'Test Site';
        $config->siteDescription = 'Test Description';
        $config->baseUrl = 'https://example.com';
        $config->basePath = '/tkr/';
        return $config;
    }

    private function createSampleTicks() {
        return [
            ['id' => 1, 'timestamp' => '2025-01-15 12:00:00', 'tick' => 'First test tick'],
            ['id' => 2, 'timestamp' => '2025-01-15 13:00:00', 'tick' => 'Second test tick']
        ];
    }

    public function testCanGenerateValidAtom() {
        $config = $this->createMockConfig();
        $ticks = $this->createSampleTicks();

        $generator = new AtomGenerator($config, $ticks);
        $xml = $generator->generate();

        // Test XML structure
        $this->assertStringStartsWith('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('<feed xmlns="http://www.w3.org/2005/Atom">', $xml);
        $this->assertStringContainsString('<title>Test Site Atom Feed</title>', $xml);
        $this->assertStringContainsString('<link rel="alternate" href="https://example.com/tkr/"/>', $xml);
        $this->assertStringContainsString('<link rel="self"', $xml);
        $this->assertStringContainsString('href="https://example.com/tkr/feed/atom"', $xml);
        $this->assertStringContainsString('<id>https://example.com/tkr/</id>', $xml);
        $this->assertStringContainsString('<author>', $xml);
        $this->assertStringContainsString('<name>Test Site</name>', $xml);
        $this->assertStringContainsString('<entry>', $xml);
        $this->assertStringContainsString('</entry>', $xml);
        $this->assertStringEndsWith('</feed>' . "\n", $xml);

        // Test tick content
        $this->assertStringContainsString('First test tick', $xml);
        $this->assertStringContainsString('Second test tick', $xml);

        // Ensure the XML is still valid
        $doc = new DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'Valid Atom should load into an XML document');
    }

    public function testReturnsCorrectContentType() {
        $generator = new AtomGenerator($this->createMockConfig(), []);
        $this->assertEquals('application/atom+xml; charset=utf-8', $generator->getContentType());
    }

    public function testCanHandleEmptyTickList() {
        $config = $this->createMockConfig();
        $generator = new AtomGenerator($config, []);
        $xml = $generator->generate();

        // Should still be valid Atom with no entries
        // Test XML structure
        $this->assertStringStartsWith('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('<feed xmlns="http://www.w3.org/2005/Atom">', $xml);
        $this->assertStringContainsString('<title>Test Site Atom Feed</title>', $xml);
        $this->assertStringContainsString('<link rel="alternate" href="https://example.com/tkr/"/>', $xml);
        $this->assertStringContainsString('<link rel="self"', $xml);
        $this->assertStringContainsString('href="https://example.com/tkr/feed/atom"', $xml);
        $this->assertStringContainsString('<id>https://example.com/tkr/</id>', $xml);
        $this->assertStringContainsString('<author>', $xml);
        $this->assertStringContainsString('<name>Test Site</name>', $xml);
        $this->assertStringEndsWith('</feed>' . "\n", $xml);

        // Test tick content
        $this->assertStringNotContainsString('<entry>', $xml);
        $this->assertStringNotContainsString('</entry>', $xml);

        // Ensure the XML is still valid
        $doc = new DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'XML with no entries should still be valid');
    }

    public function testCanHandleSpecialCharactersAndUnicode() {
        $config = $this->createMockConfig();

        // Test various challenging characters
        $ticks = [
            [
                'id' => 1,
                'timestamp' => '2025-01-15 12:00:00',
                'tick' => 'Testing emojis 🎉🔥💯 and unicode characters'
            ],
            [
                'id' => 2,
                'timestamp' => '2025-01-15 13:00:00',
                'tick' => 'XML entities: <tag> & "quotes" & \'apostrophes\''
            ],
            [
                'id' => 3,
                'timestamp' => '2025-01-15 14:00:00',
                'tick' => 'International: café naïve résumé 北京 москва'
            ],
            [
                'id' => 4,
                'timestamp' => '2025-01-15 15:00:00',
                'tick' => 'Math symbols: ∑ ∆ π ∞ ≠ ≤ ≥'
            ]
        ];

        $generator = new AtomGenerator($config, $ticks);
        $xml = $generator->generate();

        // Test that emojis are preserved
        $this->assertStringContainsString('🎉🔥💯', $xml);

        // Test that XML entities are properly escaped
        $this->assertStringContainsString('&lt;tag&gt;', $xml);
        $this->assertStringContainsString('&amp;', $xml);
        $this->assertStringContainsString('&quot;quotes&quot;', $xml);
        $this->assertStringContainsString('&apos;apostrophes&apos;', $xml);

        // Test that international characters are preserved
        $this->assertStringContainsString('café naïve résumé', $xml);
        $this->assertStringContainsString('北京', $xml);
        $this->assertStringContainsString('москва', $xml);

        // Test that math symbols are preserved
        $this->assertStringContainsString('∑ ∆ π ∞', $xml);

        // Ensure no raw < > & characters (security)
        $this->assertStringNotContainsString('<tag>', $xml);
        $this->assertStringNotContainsString(' & "', $xml);

        // Ensure the XML is still valid
        $doc = new DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'XML with Unicode should still be valid');
    }
}