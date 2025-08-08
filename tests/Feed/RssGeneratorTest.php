<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RssGeneratorTest extends TestCase
{
    private function createMockConfig() {
        $mockPdo = $this->createMock(PDO::class);
        $settings = new SettingsModel($mockPdo);
        $settings->siteTitle = 'Test Site';
        $settings->siteDescription = 'Test Description';
        $settings->baseUrl = 'https://example.com';
        $settings->basePath = '/tkr/';
        return $settings;
    }

    private function createSampleTicks() {
        return [
            ['id' => 1, 'timestamp' => '2025-01-15 12:00:00', 'tick' => 'First test tick'],
            ['id' => 2, 'timestamp' => '2025-01-15 13:00:00', 'tick' => 'Second test tick']
        ];
    }

    public function testCanGenerateValidRss() {
        $settings = $this->createMockConfig();
        $ticks = $this->createSampleTicks();

        $generator = new RssGenerator($settings, $ticks);
        $xml = $generator->generate();

        // Test XML structure
        $this->assertStringStartsWith('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('<rss version="2.0"', $xml);
        $this->assertStringContainsString('<title>Test Site RSS Feed</title>', $xml);
        $this->assertStringContainsString('<link>https://example.com/tkr/</link>', $xml);
        $this->assertStringContainsString('<atom:link href="https://example.com/tkr/feed/rss"', $xml);
        $this->assertStringContainsString('<channel>', $xml);
        $this->assertStringContainsString('<item>', $xml);
        $this->assertStringContainsString('</item>', $xml);
        $this->assertStringContainsString('</channel>', $xml);
        $this->assertStringEndsWith('</rss>' . "\n", $xml);

        // Test tick content
        $this->assertStringContainsString('First test tick', $xml);
        $this->assertStringContainsString('Second test tick', $xml);

        // Ensure the XML is still valid
        $doc = new DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'Valid RSS should load into an XML document');
    }

    public function testReturnsCorrectContentType() {
        $generator = new RssGenerator($this->createMockConfig(), []);
        $this->assertEquals('application/rss+xml; charset=utf-8', $generator->getContentType());
    }

    public function testCanHandleEmptyTickList() {
        $settings = $this->createMockConfig();
        $generator = new RssGenerator($settings, []);
        $xml = $generator->generate();

        // Should still be valid RSS with no items
        // Test XML structure
        $this->assertStringStartsWith('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('<rss version="2.0"', $xml);
        $this->assertStringContainsString('<title>Test Site RSS Feed</title>', $xml);
        $this->assertStringContainsString('<link>https://example.com/tkr/</link>', $xml);
        $this->assertStringContainsString('<atom:link href="https://example.com/tkr/feed/rss"', $xml);
        $this->assertStringContainsString('<channel>', $xml);
        $this->assertStringContainsString('</channel>', $xml);
        $this->assertStringEndsWith('</rss>' . "\n", $xml);

        // Test tick content
        $this->assertStringNotContainsString('<item>', $xml);
        $this->assertStringNotContainsString('</item>', $xml);

        // Ensure the XML is still valid
        $doc = new DOMDocument();
        $this->assertTrue($doc->loadXML($xml), 'XML with no items should still be valid');
    }

    public function testCanHandleSpecialCharactersAndUnicode() {
        $settings = $this->createMockConfig();

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

        $generator = new RssGenerator($settings, $ticks);
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
