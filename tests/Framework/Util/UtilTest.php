<?php
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

}