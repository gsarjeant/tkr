<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FeedGeneratorTest extends TestCase
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

    private function createTestGenerator($settings = null, $ticks = null) {
        $settings = $settings ?? $this->createMockConfig();
        $ticks = $ticks ?? $this->createSampleTicks();

        return new class($settings, $ticks) extends FeedGenerator {
            public function generate(): string {
                return '<test>content</test>';
            }

            public function getContentType(): string {
                return 'application/test+xml';
            }

            // Expose protected methods for testing
            public function testBuildTickUrl(int $tickId): string {
                return $this->buildTickUrl($tickId);
            }

            public function testGetSiteUrl(): string {
                return $this->getSiteUrl();
            }
        };
    }

    public function testConstructorStoresConfigAndTicks() {
        $generator = $this->createTestGenerator();

        $this->assertEquals('<test>content</test>', $generator->generate());
        $this->assertEquals('application/test+xml', $generator->getContentType());
    }

    public function testBuildTickUrlGeneratesCorrectUrl() {
        $generator = $this->createTestGenerator();

        $tickUrl = $generator->testBuildTickUrl(123);
        $this->assertEquals('https://example.com/tkr/tick/123', $tickUrl);
    }

    public function testGetSiteUrlGeneratesCorrectUrl() {
        $generator = $this->createTestGenerator();

        $siteUrl = $generator->testGetSiteUrl();
        $this->assertEquals('https://example.com/tkr/', $siteUrl);
    }

    public function testUrlMethodsHandleSubdomainConfiguration() {
        $mockPdo = $this->createMock(PDO::class);
        $settings = new SettingsModel($mockPdo);
        $settings->siteTitle = 'Test Site';
        $settings->baseUrl = 'https://tkr.example.com';
        $settings->basePath = '/';

        $generator = $this->createTestGenerator($settings, []);

        $this->assertEquals('https://tkr.example.com/', $generator->testGetSiteUrl());
        $this->assertEquals('https://tkr.example.com/tick/456', $generator->testBuildTickUrl(456));
    }

    public function testUrlMethodsHandleEmptyBasePath() {
        $mockPdo = $this->createMock(PDO::class);
        $settings = new SettingsModel($mockPdo);
        $settings->siteTitle = 'Test Site';
        $settings->baseUrl = 'https://example.com';
        $settings->basePath = '';

        $generator = $this->createTestGenerator($settings, []);

        $this->assertEquals('https://example.com/', $generator->testGetSiteUrl());
        $this->assertEquals('https://example.com/tick/789', $generator->testBuildTickUrl(789));
    }

    public function testUrlMethodsHandleVariousBasePathFormats() {
        $testCases = [
            // [basePath, expectedSiteUrl, expectedTickUrl]
            ['', 'https://example.com/', 'https://example.com/tick/123'],
            ['/', 'https://example.com/', 'https://example.com/tick/123'],
            ['tkr', 'https://example.com/tkr/', 'https://example.com/tkr/tick/123'],
            ['/tkr', 'https://example.com/tkr/', 'https://example.com/tkr/tick/123'],
            ['tkr/', 'https://example.com/tkr/', 'https://example.com/tkr/tick/123'],
            ['/tkr/', 'https://example.com/tkr/', 'https://example.com/tkr/tick/123'],
        ];

        foreach ($testCases as [$basePath, $expectedSiteUrl, $expectedTickUrl]) {
            $mockPdo = $this->createMock(PDO::class);
            $settings = new SettingsModel($mockPdo);
            $settings->siteTitle = 'Test Site';
            $settings->baseUrl = 'https://example.com';
            $settings->basePath = $basePath;

            $generator = $this->createTestGenerator($settings, []);

            $this->assertEquals($expectedSiteUrl, $generator->testGetSiteUrl(), "Failed for basePath: '$basePath'");
            $this->assertEquals($expectedTickUrl, $generator->testBuildTickUrl(123), "Failed for basePath: '$basePath'");
        }
    }
}