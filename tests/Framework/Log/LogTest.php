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
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $path) {
            $path->isDir() ? rmdir($path->getRealPath()) : unlink($path->getRealPath());
        }
        rmdir($dir);
    }
    
    private function setLogLevel(int $level): void
    {
        global $app;
        $app = ['config' => (object)['logLevel' => $level]];
    }
    
    private function assertLogContains(string $message): void
    {
        $this->assertFileExists($this->testLogFile);
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString($message, $logContent);
    }
    
    private function assertLogDoesNotContain(string $message): void
    {
        $this->assertFileExists($this->testLogFile);
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringNotContainsString($message, $logContent);
    }

    public function testSetRouteContext(): void
    {
        Log::setRouteContext('GET /admin');
        $this->setLogLevel(1); // DEBUG level
        
        Log::debug('Test message');
        
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('[GET /admin]', $logContent);
        $this->assertStringContainsString('Test message', $logContent);
    }

    public function testEmptyRouteContext(): void
    {
        Log::setRouteContext('');
        $this->setLogLevel(1);
        
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
        $this->setLogLevel(3); // WARNING level
        
        Log::debug('Debug message');   // Should be filtered out
        Log::info('Info message');     // Should be filtered out  
        Log::warning('Warning message'); // Should be logged
        Log::error('Error message');   // Should be logged
        
        $this->assertLogDoesNotContain('Debug message');
        $this->assertLogDoesNotContain('Info message');
        $this->assertLogContains('Warning message');
        $this->assertLogContains('Error message');
    }

    public function testLogMessageFormat(): void
    {
        Log::setRouteContext('POST /admin');
        $this->setLogLevel(1);
        
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
        
        // Verify we can actually write to it
        $this->setLogLevel(1);
        Log::info('Test directory creation');
        $this->assertFileExists($newLogFile);
    }

    public function testLogRotation(): void
    {
        $this->setLogLevel(1);
        
        // Create a log file with exactly 1000 lines (the rotation threshold)
        $logLines = str_repeat("[2025-01-31 12:00:00] INFO: 127.0.0.1 - Test line\n", 1000);
        file_put_contents($this->testLogFile, $logLines);
        
        // This should trigger rotation
        Log::info('This should trigger rotation');
        
        // Verify rotation happened
        $this->assertFileExists($this->testLogFile . '.1');
        $this->assertLogContains('This should trigger rotation');
    }
    
    public function testLogRotationLimitsFileCount(): void
    {
        $this->setLogLevel(1);
        
        // Create 5 existing rotated log files (.1 through .5)
        for ($i = 1; $i <= 5; $i++) {
            file_put_contents($this->testLogFile . '.' . $i, "Old log file $i\n");
        }
        
        // Create main log file at rotation threshold
        $logLines = str_repeat("[2025-01-31 12:00:00] INFO: 127.0.0.1 - Test line\n", 1000);
        file_put_contents($this->testLogFile, $logLines);
        
        // This should trigger rotation and delete the oldest file (.5)
        Log::info('Trigger rotation with max files');
        
        // Verify rotation happened and file count is limited
        $this->assertFileExists($this->testLogFile . '.1'); // New rotated file
        $this->assertFileExists($this->testLogFile . '.2'); // Old .1 became .2
        $this->assertFileExists($this->testLogFile . '.3'); // Old .2 became .3
        $this->assertFileExists($this->testLogFile . '.4'); // Old .3 became .4
        $this->assertFileExists($this->testLogFile . '.5'); // Old .4 became .5
        $this->assertFileDoesNotExist($this->testLogFile . '.6'); // Old .5 was deleted
        
        $this->assertLogContains('Trigger rotation with max files');
    }

    public function testDefaultLogLevelWhenConfigMissing(): void
    {
        // Set up config without logLevel property (simulates missing config value)
        global $app;
        $app = ['config' => (object)[]];
        
        // Should not throw errors and should default to INFO level
        Log::debug('Debug message');  // Should be filtered out (default INFO level = 2)
        Log::info('Info message');    // Should be logged
        
        $this->assertLogDoesNotContain('Debug message');
        $this->assertLogContains('Info message');
    }
}