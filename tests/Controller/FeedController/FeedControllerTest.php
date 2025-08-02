<?php
use PHPUnit\Framework\TestCase;

class FeedControllerTest extends TestCase
{
    private PDO $mockPdo;
    private PDOStatement $mockStatement;
    private ConfigModel $mockConfig;
    private UserModel $mockUser;
    private string $tempLogDir;

    protected function setUp(): void
    {
        // Set up temporary logging
        $this->tempLogDir = sys_get_temp_dir() . '/tkr_test_logs_' . uniqid();
        mkdir($this->tempLogDir . '/logs', 0777, true);
        Log::init($this->tempLogDir . '/logs/tkr.log');
        
        // Create mock PDO and PDOStatement
        $this->mockStatement = $this->createMock(PDOStatement::class);
        $this->mockPdo = $this->createMock(PDO::class);
        
        // Mock config with feed-relevant properties
        $this->mockConfig = new ConfigModel($this->mockPdo);
        $this->mockConfig->itemsPerPage = 10;
        $this->mockConfig->basePath = '/tkr';
        $this->mockConfig->siteTitle = 'Test Site';
        $this->mockConfig->siteDescription = 'Test Description';
        $this->mockConfig->baseUrl = 'https://test.example.com';
        
        // Mock user
        $this->mockUser = new UserModel($this->mockPdo);
        $this->mockUser->displayName = 'Test User';
        
        // Set up global $app for simplified dependency access
        global $app;
        $app = [
            'db' => $this->mockPdo,
            'config' => $this->mockConfig,
            'user' => $this->mockUser,
        ];
        
        // Set log level on config for Log class
        $this->mockConfig->logLevel = 1; // Allow DEBUG level logs
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

    private function setupMockDatabase(array $tickData): void
    {
        // Mock PDO prepare method to return our mock statement
        $this->mockPdo->method('prepare')
                      ->willReturn($this->mockStatement);
        
        // Mock statement execute method
        $this->mockStatement->method('execute')
                           ->willReturn(true);
        
        // Mock statement fetchAll to return our test data
        $this->mockStatement->method('fetchAll')
                           ->willReturn($tickData);
    }

    public function testControllerInstantiationWithNoTicks(): void
    {
        $this->setupMockDatabase([]);
        
        $controller = new FeedController();
        
        // Verify it was created successfully
        $this->assertInstanceOf(FeedController::class, $controller);
        
        // Check logs
        $logFile = $this->tempLogDir . '/logs/tkr.log';
        $this->assertFileExists($logFile);
        
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('Loaded 0 ticks for feeds', $logContent);
    }

    public function testControllerInstantiationWithTicks(): void
    {
        $testTicks = [
            ['id' => 1, 'timestamp' => '2025-01-31 12:00:00', 'tick' => 'First tick'],
            ['id' => 2, 'timestamp' => '2025-01-31 13:00:00', 'tick' => 'Second tick'],
        ];
        
        $this->setupMockDatabase($testTicks);
        
        $controller = new FeedController();
        
        // Verify it was created successfully
        $this->assertInstanceOf(FeedController::class, $controller);
        
        // Check logs
        $logFile = $this->tempLogDir . '/logs/tkr.log';
        $this->assertFileExists($logFile);
        
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('Loaded 2 ticks for feeds', $logContent);
    }

    public function testControllerCallsDatabaseCorrectly(): void
    {
        $this->setupMockDatabase([]);
        
        // Verify that PDO prepare is called with the correct SQL for tick loading
        $this->mockPdo->expects($this->once())
                     ->method('prepare')
                     ->with('SELECT id, timestamp, tick FROM tick ORDER BY timestamp DESC LIMIT ? OFFSET ?')
                     ->willReturn($this->mockStatement);
        
        // Verify that execute is called with correct parameters (page 1, offset 0)
        $this->mockStatement->expects($this->once())
                           ->method('execute')
                           ->with([10, 0]); // itemsPerPage=10, page 1 = offset 0
        
        new FeedController();
    }

    public function testRssMethodLogsCorrectly(): void
    {
        $testTicks = [
            ['id' => 1, 'timestamp' => '2025-01-31 12:00:00', 'tick' => 'Test tick']
        ];
        
        $this->setupMockDatabase($testTicks);
        
        $controller = new FeedController();
        
        // Capture output to prevent headers/content from affecting test
        ob_start();
        $controller->rss();
        ob_end_clean();
        
        // Check logs for RSS generation
        $logFile = $this->tempLogDir . '/logs/tkr.log';
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('Generating RSS feed with 1 ticks', $logContent);
    }

    public function testAtomMethodLogsCorrectly(): void
    {
        $testTicks = [
            ['id' => 1, 'timestamp' => '2025-01-31 12:00:00', 'tick' => 'Test tick'],
            ['id' => 2, 'timestamp' => '2025-01-31 13:00:00', 'tick' => 'Another tick']
        ];
        
        $this->setupMockDatabase($testTicks);
        
        $controller = new FeedController();
        
        // Capture output to prevent headers/content from affecting test
        ob_start();
        $controller->atom();
        ob_end_clean();
        
        // Check logs for Atom generation
        $logFile = $this->tempLogDir . '/logs/tkr.log';
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('Generating Atom feed with 2 ticks', $logContent);
    }
}