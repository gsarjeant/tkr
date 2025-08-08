<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class LogControllerTest extends TestCase
{
    private string $tempLogDir;
    private string $testLogFile;
    private $originalGet;

    protected function setUp(): void
    {
        $this->tempLogDir = sys_get_temp_dir() . '/tkr_test_logs_' . uniqid();
        mkdir($this->tempLogDir, 0777, true);

        $this->testLogFile = $this->tempLogDir . '/logs/tkr.log';
        mkdir(dirname($this->testLogFile), 0777, true);

        // Store original $_GET and clear it
        $this->originalGet = $_GET;
        $_GET = [];

        // Set up global $app for simplified dependency access
        $mockPdo = $this->createMock(PDO::class);
        $mockSettings = new SettingsModel($mockPdo);
        $mockSettings->baseUrl = 'https://example.com';
        $mockSettings->basePath = '/tkr/';

        $mockUser = new UserModel($mockPdo);

        global $app;
        $app = [
            'db' => $mockPdo,
            'settings' => $mockSettings,
            'user' => $mockUser,
        ];
    }

    protected function tearDown(): void
    {
        // Restore original $_GET
        $_GET = $this->originalGet;

        if (is_dir($this->tempLogDir)) {
            $this->deleteDirectory($this->tempLogDir);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testGetLogDataWithNoLogFiles(): void
    {
        // Uses global $app set up in setUp()
        $controller = new LogController($this->tempLogDir);
        $data = $controller->getLogData();

        // Should return empty log entries but valid structure
        $this->assertArrayHasKey('logEntries', $data);
        $this->assertArrayHasKey('availableRoutes', $data);
        $this->assertArrayHasKey('availableLevels', $data);
        $this->assertArrayHasKey('currentLevelFilter', $data);
        $this->assertArrayHasKey('currentRouteFilter', $data);

        $this->assertEmpty($data['logEntries']);
        $this->assertEmpty($data['availableRoutes']);
        $this->assertEquals(['DEBUG', 'INFO', 'WARNING', 'ERROR'], $data['availableLevels']);
        $this->assertEquals('', $data['currentLevelFilter']);
        $this->assertEquals('', $data['currentRouteFilter']);
    }

    public function testGetLogDataWithValidEntries(): void
    {
        // Create test log content with various scenarios
        $logContent = implode("\n", [
            '[2025-01-31 12:00:00] DEBUG: 127.0.0.1 [GET /] - Debug home page',
            '[2025-01-31 12:01:00] INFO: 127.0.0.1 [GET /admin] - Info admin page',
            '[2025-01-31 12:02:00] WARNING: 127.0.0.1 [POST /admin] - Warning admin save',
            '[2025-01-31 12:03:00] ERROR: 127.0.0.1 [GET /feed/rss] - Error feed generation',
            '[2025-01-31 12:04:00] INFO: 127.0.0.1 - Info without route',
            'Invalid log line that should be ignored'
        ]);

        file_put_contents($this->testLogFile, $logContent);

        // Uses global $app set up in setUp()
        $controller = new LogController($this->tempLogDir);
        $data = $controller->getLogData();

        // Should parse all valid entries and ignore invalid ones
        $this->assertCount(5, $data['logEntries']);

        // Verify entries are in reverse chronological order (newest first)
        $entries = $data['logEntries'];
        $this->assertEquals('Info without route', $entries[0]['message']);
        $this->assertEquals('Debug home page', $entries[4]['message']);

        // Verify entry structure
        $firstEntry = $entries[0];
        $this->assertArrayHasKey('timestamp', $firstEntry);
        $this->assertArrayHasKey('level', $firstEntry);
        $this->assertArrayHasKey('ip', $firstEntry);
        $this->assertArrayHasKey('route', $firstEntry);
        $this->assertArrayHasKey('message', $firstEntry);

        // Test route extraction
        $adminEntry = array_filter($entries, fn($e) => $e['message'] === 'Info admin page');
        $adminEntry = array_values($adminEntry)[0];
        $this->assertEquals('GET /admin', $adminEntry['route']);
        $this->assertEquals('INFO', $adminEntry['level']);

        // Test entry without route
        $noRouteEntry = array_filter($entries, fn($e) => $e['message'] === 'Info without route');
        $noRouteEntry = array_values($noRouteEntry)[0];
        $this->assertEquals('', $noRouteEntry['route']);
    }

    public function testGetLogDataWithLevelFilter(): void
    {
        $logContent = implode("\n", [
            '[2025-01-31 12:00:00] DEBUG: 127.0.0.1 - Debug message',
            '[2025-01-31 12:01:00] INFO: 127.0.0.1 - Info message',
            '[2025-01-31 12:02:00] ERROR: 127.0.0.1 - Error message'
        ]);

        file_put_contents($this->testLogFile, $logContent);

        // Uses global $app set up in setUp()
        $controller = new LogController($this->tempLogDir);
        $data = $controller->getLogData('ERROR');

        // Should only include ERROR entries
        $this->assertCount(1, $data['logEntries']);
        $this->assertEquals('ERROR', $data['logEntries'][0]['level']);
        $this->assertEquals('Error message', $data['logEntries'][0]['message']);
        $this->assertEquals('ERROR', $data['currentLevelFilter']);
    }

    public function testGetLogDataWithRouteFilter(): void
    {
        $logContent = implode("\n", [
            '[2025-01-31 12:00:00] INFO: 127.0.0.1 [GET /] - Home page',
            '[2025-01-31 12:01:00] INFO: 127.0.0.1 [GET /admin] - Admin page',
            '[2025-01-31 12:02:00] INFO: 127.0.0.1 [POST /admin] - Admin save'
        ]);

        file_put_contents($this->testLogFile, $logContent);

        // Uses global $app set up in setUp()
        $controller = new LogController($this->tempLogDir);
        $data = $controller->getLogData('', 'GET /admin');

        // Should only include GET /admin entries
        $this->assertCount(1, $data['logEntries']);
        $this->assertEquals('GET /admin', $data['logEntries'][0]['route']);
        $this->assertEquals('Admin page', $data['logEntries'][0]['message']);
        $this->assertEquals('GET /admin', $data['currentRouteFilter']);
    }

    public function testGetLogDataWithBothFilters(): void
    {
        $logContent = implode("\n", [
            '[2025-01-31 12:00:00] ERROR: 127.0.0.1 [GET /admin] - Admin error',
            '[2025-01-31 12:01:00] INFO: 127.0.0.1 [GET /admin] - Admin info',
            '[2025-01-31 12:02:00] ERROR: 127.0.0.1 [GET /] - Home error'
        ]);

        file_put_contents($this->testLogFile, $logContent);

        // Uses global $app set up in setUp()
        $controller = new LogController($this->tempLogDir);
        $data = $controller->getLogData('ERROR', 'GET /admin');

        // Should only include entries matching both filters
        $this->assertCount(1, $data['logEntries']);
        $this->assertEquals('ERROR', $data['logEntries'][0]['level']);
        $this->assertEquals('GET /admin', $data['logEntries'][0]['route']);
        $this->assertEquals('Admin error', $data['logEntries'][0]['message']);
    }

    public function testGetLogDataWithRotatedLogs(): void
    {
        // Create main log file
        $mainLogContent = '[2025-01-31 14:00:00] INFO: 127.0.0.1 - Current log entry';
        file_put_contents($this->testLogFile, $mainLogContent);

        // Create rotated log files
        $rotatedLog1 = '[2025-01-31 13:00:00] ERROR: 127.0.0.1 - Rotated log entry 1';
        file_put_contents($this->testLogFile . '.1', $rotatedLog1);

        $rotatedLog2 = '[2025-01-31 12:00:00] WARNING: 127.0.0.1 - Rotated log entry 2';
        file_put_contents($this->testLogFile . '.2', $rotatedLog2);

        // Uses global $app set up in setUp()
        $controller = new LogController($this->tempLogDir);
        $data = $controller->getLogData();

        // Should read from all log files, newest first
        $this->assertCount(3, $data['logEntries']);
        $this->assertEquals('Current log entry', $data['logEntries'][0]['message']);
        $this->assertEquals('Rotated log entry 1', $data['logEntries'][1]['message']);
        $this->assertEquals('Rotated log entry 2', $data['logEntries'][2]['message']);
    }

    public function testGetLogDataExtractsAvailableRoutes(): void
    {
        $logContent = implode("\n", [
            '[2025-01-31 12:00:00] INFO: 127.0.0.1 [GET /] - Home',
            '[2025-01-31 12:01:00] INFO: 127.0.0.1 [GET /admin] - Admin',
            '[2025-01-31 12:02:00] INFO: 127.0.0.1 [POST /admin] - Admin post',
            '[2025-01-31 12:03:00] INFO: 127.0.0.1 [GET /admin] - Admin again',
            '[2025-01-31 12:04:00] INFO: 127.0.0.1 - No route'
        ]);

        file_put_contents($this->testLogFile, $logContent);

        // Uses global $app set up in setUp()
        $controller = new LogController($this->tempLogDir);
        $data = $controller->getLogData();

        // Should extract unique routes, sorted
        $expectedRoutes = ['GET /', 'GET /admin', 'POST /admin'];
        $this->assertEquals($expectedRoutes, $data['availableRoutes']);
    }

    public function testGetLogDataHandlesInvalidLogLines(): void
    {
        $logContent = implode("\n", [
            '[2025-01-31 12:00:00] INFO: 127.0.0.1 - Valid entry',
            'This is not a valid log line',
            'Neither is this one',
            '[2025-01-31 12:01:00] ERROR: 127.0.0.1 - Another valid entry'
        ]);

        file_put_contents($this->testLogFile, $logContent);

        // Uses global $app set up in setUp()
        $controller = new LogController($this->tempLogDir);
        $data = $controller->getLogData();

        // Should only include valid entries, ignore invalid ones
        $this->assertCount(2, $data['logEntries']);
        $this->assertEquals('Another valid entry', $data['logEntries'][0]['message']);
        $this->assertEquals('Valid entry', $data['logEntries'][1]['message']);
    }
}