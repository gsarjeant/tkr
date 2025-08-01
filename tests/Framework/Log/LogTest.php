<?php
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    private string $tempLogDir;
    private string $testLogFile;

    protected function setUp(): void
    {
        // Create a temporary directory for test logs
        $this->tempLogDir = sys_get_temp_dir() . '/tkr_test_logs_' . uniqid();
        mkdir($this->tempLogDir, 0777, true);
        
        $this->testLogFile = $this->tempLogDir . '/tkr.log';
        
        // Initialize Log with test file and reset route context
        Log::init($this->testLogFile);
        Log::setRouteContext('');
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
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

    public function testSetRouteContext(): void
    {
        Log::setRouteContext('GET /admin');
        
        // Create a mock config for log level
        global $config;
        $config = new stdClass();
        $config->logLevel = 1; // DEBUG level
        
        Log::debug('Test message');
        
        $this->assertFileExists($this->testLogFile);
        
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('[GET /admin]', $logContent);
        $this->assertStringContainsString('Test message', $logContent);
    }

    public function testEmptyRouteContext(): void
    {
        Log::setRouteContext('');
        
        global $config;
        $config = new stdClass();
        $config->logLevel = 1;
        
        Log::info('Test without route');
        
        $logContent = file_get_contents($this->testLogFile);
        
        // Should match format without route context: [timestamp] LEVEL: IP - message
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] INFO: .+ - Test without route/',
            $logContent
        );
    }

    public function testLogLevelFiltering(): void
    {
        global $config;
        $config = new stdClass();
        $config->logLevel = 3; // WARNING level
        
        Log::debug('Debug message');   // Should be filtered out
        Log::info('Info message');     // Should be filtered out  
        Log::warning('Warning message'); // Should be logged
        Log::error('Error message');   // Should be logged
        
        $logContent = file_get_contents($this->testLogFile);
        
        $this->assertStringNotContainsString('Debug message', $logContent);
        $this->assertStringNotContainsString('Info message', $logContent);
        $this->assertStringContainsString('Warning message', $logContent);
        $this->assertStringContainsString('Error message', $logContent);
    }

    public function testLogMessageFormat(): void
    {
        Log::setRouteContext('POST /admin');
        
        global $config;
        $config = new stdClass();
        $config->logLevel = 1;
        
        Log::error('Test error message');
        
        $logContent = file_get_contents($this->testLogFile);
        
        // Check log format: [timestamp] LEVEL: IP [route] - message
        $this->assertMatchesRegularExpression(
            '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] ERROR: .+ \[POST \/admin\] - Test error message/',
            $logContent
        );
    }

    public function testInitCreatesLogDirectory(): void
    {
        $newLogFile = $this->tempLogDir . '/nested/logs/test.log';
        
        // Directory doesn't exist yet
        $this->assertDirectoryDoesNotExist(dirname($newLogFile));
        
        Log::init($newLogFile);
        
        // init() should create the directory
        $this->assertDirectoryExists(dirname($newLogFile));
    }

    public function testLogRotation(): void
    {
        global $config;
        $config = new stdClass();
        $config->logLevel = 1;
        
        // Create a log file with exactly 1000 lines (the rotation threshold)
        $logLines = str_repeat("[2025-01-31 12:00:00] INFO: 127.0.0.1 - Test line\n", 1000);
        file_put_contents($this->testLogFile, $logLines);
        
        // This should trigger rotation
        Log::info('This should trigger rotation');
        
        // Original log should be rotated to .1
        $this->assertFileExists($this->testLogFile . '.1');
        
        // New log should contain the new message
        $newLogContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('This should trigger rotation', $newLogContent);
        
        // Rotated log should contain old content
        $rotatedContent = file_get_contents($this->testLogFile . '.1');
        $this->assertStringContainsString('Test line', $rotatedContent);
    }

    public function testDefaultLogLevelWhenConfigMissing(): void
    {
        // Clear global config
        global $config;
        $config = null;
        
        // Should not throw errors and should default to INFO level
        Log::debug('Debug message');  // Should be filtered out (default INFO level = 2)
        Log::info('Info message');    // Should be logged
        
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringNotContainsString('Debug message', $logContent);
        $this->assertStringContainsString('Info message', $logContent);
    }
}