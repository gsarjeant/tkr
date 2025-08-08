<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class UtilTest extends TestCase
{
    // Define test date (strings) and expected outputs for
    // testCanDisplayRelativeTime
    public static function dateProvider(): array {
        $datetime = new DateTimeImmutable();

        return [
            '1 minute ago' => [$datetime->modify('-1 minute')->format('c'), '1 minute ago'],
            '2 hours ago'  => [$datetime->modify('-2 hours')->format('c'), '2 hours ago'],
            '3 days ago'   => [$datetime->modify('-3 days')->format('c'), '3 days ago'],
            '4 months ago' => [$datetime->modify('-4 months')->format('c'), '4 months ago'],
            '5 years ago'  => [$datetime->modify('-5 years')->format('c'), '5 years ago']
        ];
    }

    // Validate that the datetime strings provided by dateProvider
    // yield the expected display strings
    #[DataProvider('dateProvider')]
    public function testCanDisplayRelativeTime(string $datetimeString, string $display): void {
        $relativeTime = Util::relative_time($datetimeString);
        $this->assertSame($relativeTime, $display);
    }

    public static function buildUrlProvider(): array {
        return [
            'basic path' => ['https://example.com', 'tkr', 'admin', 'https://example.com/tkr/admin'],
            'baseUrl with trailing slash' => ['https://example.com/', 'tkr', 'admin', 'https://example.com/tkr/admin'],
            'empty basePath' => ['https://example.com', '', 'admin', 'https://example.com/admin'],
            'root basePath' => ['https://example.com', '/', 'admin', 'https://example.com/admin'],
            'basePath no leading slash' => ['https://example.com', 'tkr', 'admin', 'https://example.com/tkr/admin'],
            'basePath with leading slash' => ['https://example.com', '/tkr', 'admin', 'https://example.com/tkr/admin'],
            'basePath with trailing slash' => ['https://example.com', 'tkr/', 'admin', 'https://example.com/tkr/admin'],
            'basePath with both slashes' => ['https://example.com', '/tkr/', 'admin', 'https://example.com/tkr/admin'],
            'complex path' => ['https://example.com', 'tkr', 'admin/css/upload', 'https://example.com/tkr/admin/css/upload'],
            'path with leading slash' => ['https://example.com', 'tkr', '/admin', 'https://example.com/tkr/admin'],
            'no path - empty basePath' => ['https://example.com', '', '', 'https://example.com/'],
            'no path - root basePath' => ['https://example.com', '/', '', 'https://example.com/'],
            'no path - tkr basePath' => ['https://example.com', 'tkr', '', 'https://example.com/tkr/'],
        ];
    }

    #[DataProvider('buildUrlProvider')]
    public function testBuildUrl(string $baseUrl, string $basePath, string $path, string $expected): void {
        $result = Util::buildUrl($baseUrl, $basePath, $path);
        $this->assertEquals($expected, $result);
    }

    public static function buildRelativeUrlProvider(): array {
        return [
            'empty basePath with path' => ['', 'admin', '/admin'],
            'root basePath with path' => ['/', 'admin', '/admin'],
            'tkr basePath with path' => ['tkr', 'admin', '/tkr/admin'],
            'tkr with leading slash' => ['/tkr', 'admin', '/tkr/admin'],
            'tkr with trailing slash' => ['tkr/', 'admin', '/tkr/admin'],
            'tkr with both slashes' => ['/tkr/', 'admin', '/tkr/admin'],
            'complex path' => ['tkr', 'admin/css/upload', '/tkr/admin/css/upload'],
            'path with leading slash' => ['tkr', '/admin', '/tkr/admin'],
            'no path - empty basePath' => ['', '', '/'],
            'no path - root basePath' => ['/', '', '/'],
            'no path - tkr basePath' => ['tkr', '', '/tkr'],
            'no path - tkr with slashes' => ['/tkr/', '', '/tkr'],
        ];
    }

    #[DataProvider('buildRelativeUrlProvider')]
    public function testBuildRelativeUrl(string $basePath, string $path, string $expected): void {
        $result = Util::buildRelativeUrl($basePath, $path);
        $this->assertEquals($expected, $result);
    }

    // Test data for escape_html function
    public static function escapeHtmlProvider(): array {
        return [
            'basic HTML' => ['<script>alert("xss")</script>', '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'],
            'quotes' => ['He said "Hello" & she said \'Hi\'', 'He said &quot;Hello&quot; &amp; she said &#039;Hi&#039;'],
            'empty string' => ['', ''],
            'normal text' => ['Hello World', 'Hello World'],
            'ampersand' => ['Tom & Jerry', 'Tom &amp; Jerry'],
            'unicode' => ['🚀 emoji & text', '🚀 emoji &amp; text'],
        ];
    }

    #[DataProvider('escapeHtmlProvider')]
    public function testEscapeHtml(string $input, string $expected): void {
        $result = Util::escape_html($input);
        $this->assertEquals($expected, $result);
    }

    // Test data for escape_xml function
    public static function escapeXmlProvider(): array {
        return [
            'basic XML' => ['<tag attr="value">content</tag>', '&lt;tag attr=&quot;value&quot;&gt;content&lt;/tag&gt;'],
            'quotes and ampersand' => ['Title & "Subtitle"', 'Title &amp; &quot;Subtitle&quot;'],
            'empty string' => ['', ''],
            'normal text' => ['Hello World', 'Hello World'],
            'unicode' => ['🎵 music & notes', '🎵 music &amp; notes'],
        ];
    }

    #[DataProvider('escapeXmlProvider')]
    public function testEscapeXml(string $input, string $expected): void {
        $result = Util::escape_xml($input);
        $this->assertEquals($expected, $result);
    }

    // Test data for linkify function
    public static function linkifyProvider(): array {
        return [
            'simple URL' => [
                'Check out https://example.com for more info',
                'Check out <a href="https://example.com" target="_blank" rel="noopener noreferrer">https://example.com</a> for more info',
                false // not strict accessibility
            ],
            'URL with path' => [
                'Visit https://example.com/path/to/page',
                'Visit <a href="https://example.com/path/to/page" target="_blank" rel="noopener noreferrer">https://example.com/path/to/page</a>',
                false
            ],
            'multiple URLs' => [
                'See https://example.com and https://other.com',
                'See <a href="https://example.com" target="_blank" rel="noopener noreferrer">https://example.com</a> and <a href="https://other.com" target="_blank" rel="noopener noreferrer">https://other.com</a>',
                false
            ],
            'URL with punctuation' => [
                'Check https://example.com.',
                'Check <a href="https://example.com" target="_blank" rel="noopener noreferrer">https://example.com</a>',
                false
            ],
            'no URL' => [
                'Just some regular text',
                'Just some regular text',
                false
            ],
            'strict accessibility mode' => [
                'Visit https://example.com now',
                'Visit <a tabindex="0" href="https://example.com" target="_blank" rel="noopener noreferrer">https://example.com</a> now',
                true // strict accessibility
            ],
        ];
    }

    #[DataProvider('linkifyProvider')]
    public function testLinkify(string $input, string $expected, bool $strictAccessibility): void {
        // Set up global $app with settings
        global $app;
        $app = [
            'settings' => (object)['strictAccessibility' => $strictAccessibility]
        ];

        $result = Util::linkify($input);
        $this->assertEquals($expected, $result);
    }

    public function testLinkifyNoNewWindow(): void {
        // Test linkify without new window
        global $app;
        $app = [
            'settings' => (object)['strictAccessibility' => false]
        ];

        $input = 'Visit https://example.com';
        $expected = 'Visit <a href="https://example.com">https://example.com</a>';

        $result = Util::linkify($input, false); // no new window
        $this->assertEquals($expected, $result);
    }

    public function testGetClientIp(): void {
        // Test basic case with REMOTE_ADDR
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP']);

        $result = Util::getClientIp();
        $this->assertEquals('192.168.1.100', $result);
    }

    public function testGetClientIpWithForwardedHeaders(): void {
        // Test precedence: HTTP_CLIENT_IP > HTTP_X_FORWARDED_FOR > HTTP_X_REAL_IP > REMOTE_ADDR
        $_SERVER['HTTP_CLIENT_IP'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.2';
        $_SERVER['HTTP_X_REAL_IP'] = '10.0.0.3';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.4';

        $result = Util::getClientIp();
        $this->assertEquals('10.0.0.1', $result); // Should use HTTP_CLIENT_IP
    }

    public function testGetClientIpUnknown(): void {
        // Test when no IP is available
        unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP'], $_SERVER['REMOTE_ADDR']);

        $result = Util::getClientIp();
        $this->assertEquals('unknown', $result);
    }

    protected function tearDown(): void {
        // Clean up $_SERVER after IP tests
        unset($_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP'], $_SERVER['REMOTE_ADDR']);
    }

}