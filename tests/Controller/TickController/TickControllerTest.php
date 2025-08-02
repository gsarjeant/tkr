<?php
use PHPUnit\Framework\TestCase;

class TickControllerTest extends TestCase
{
    private $mockPdo;
    private $config;
    private $user;
    private string $tempLogDir;
    private string $testLogFile;

    protected function setUp(): void
    {
        // Set up log capture
        $this->tempLogDir = sys_get_temp_dir() . '/tkr_test_logs_' . uniqid();
        mkdir($this->tempLogDir, 0777, true);
        
        $this->testLogFile = $this->tempLogDir . '/tkr.log';
        Log::init($this->testLogFile);
        Log::setRouteContext('GET tick/123');

        // Set up mocks
        $this->mockPdo = $this->createMock(PDO::class);
        
        $this->config = new ConfigModel($this->mockPdo);
        $this->config->baseUrl = 'https://example.com';
        $this->config->basePath = '/tkr/';
        $this->config->itemsPerPage = 10;
        $this->config->logLevel = 1; // DEBUG level for testing
        
        $this->user = new UserModel($this->mockPdo);

        // Set up global $app for simplified dependency access
        global $app;
        $app = [
            'db' => $this->mockPdo,
            'config' => $this->config,
            'user' => $this->user,
        ];
    }

    protected function tearDown(): void
    {
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

    public function testIndexWithValidTick(): void
    {
        // Set up mock database response for successful tick retrieval
        $expectedTickData = [
            'tickTime' => '2025-01-31 12:00:00',
            'tick' => 'This is a test tick with some content'
        ];

        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
                      ->method('execute')
                      ->with([123]);
        $mockStatement->expects($this->once())
                      ->method('fetch')
                      ->with(PDO::FETCH_ASSOC)
                      ->willReturn([
                          'timestamp' => '2025-01-31 12:00:00',
                          'tick' => 'This is a test tick with some content'
                      ]);

        $this->mockPdo->expects($this->once())
                      ->method('prepare')
                      ->with('SELECT timestamp, tick FROM tick WHERE id=?')
                      ->willReturn($mockStatement);

        // Capture output since render() outputs directly
        ob_start();
        
        $controller = new TickController();
        $controller->index(123);
        
        $output = ob_get_clean();

        // Should not be a 404 or 500 error
        $this->assertStringNotContainsString('404', $output);
        $this->assertStringNotContainsString('500', $output);

        // Should contain the tick content (through the template)
        // Note: We can't easily test the full template rendering without more setup,
        // but we can verify no error occurred

        // Verify logging
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Fetching tick with ID: 123', $logContent);
        $this->assertStringContainsString('Successfully loaded tick 123: This is a test tick with some content', $logContent);
    }

    public function testIndexWithNonexistentTick(): void
    {
        // Mock database returns null/empty for non-existent tick
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
                      ->method('execute')
                      ->with([999]);
        $mockStatement->expects($this->once())
                      ->method('fetch')
                      ->with(PDO::FETCH_ASSOC)
                      ->willReturn(false); // No row found

        $this->mockPdo->expects($this->once())
                      ->method('prepare')
                      ->with('SELECT timestamp, tick FROM tick WHERE id=?')
                      ->willReturn($mockStatement);

        // Capture output
        ob_start();
        
        $controller = new TickController();
        $controller->index(999);
        
        $output = ob_get_clean();

        // Should return 404 error
        $this->assertStringContainsString('404 - Tick Not Found', $output);

        // Verify logging
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Fetching tick with ID: 999', $logContent);
        $this->assertStringContainsString('Tick not found for ID: 999', $logContent);
    }

    public function testIndexWithEmptyTickData(): void
    {
        // Mock database returns empty array (edge case)
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
                      ->method('execute')
                      ->with([456]);
        $mockStatement->expects($this->once())
                      ->method('fetch')
                      ->with(PDO::FETCH_ASSOC)
                      ->willReturn([]); // Empty array

        $this->mockPdo->expects($this->once())
                      ->method('prepare')
                      ->with('SELECT timestamp, tick FROM tick WHERE id=?')
                      ->willReturn($mockStatement);

        // Capture output
        ob_start();
        
        $controller = new TickController();
        $controller->index(456);
        
        $output = ob_get_clean();

        // Should return 404 error for empty data
        $this->assertStringContainsString('404 - Tick Not Found', $output);

        // Verify logging
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Tick not found for ID: 456', $logContent);
    }

    public function testIndexWithDatabaseException(): void
    {
        // Mock database throws exception
        $this->mockPdo->expects($this->once())
                      ->method('prepare')
                      ->with('SELECT timestamp, tick FROM tick WHERE id=?')
                      ->willThrowException(new PDOException('Database connection failed'));

        // Capture output
        ob_start();
        
        $controller = new TickController();
        $controller->index(123);
        
        $output = ob_get_clean();

        // Should return 500 error
        $this->assertStringContainsString('500 - Internal Server Error', $output);

        // Verify error logging
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Failed to load tick 123: Database connection failed', $logContent);
    }

    public function testIndexWithLongTickContent(): void
    {
        // Test logging truncation for long tick content
        $longContent = str_repeat('This is a very long tick content that should be truncated in the logs. ', 10);
        
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
                      ->method('execute')
                      ->with([789]);
        $mockStatement->expects($this->once())
                      ->method('fetch')
                      ->with(PDO::FETCH_ASSOC)
                      ->willReturn([
                          'timestamp' => '2025-01-31 15:30:00',
                          'tick' => $longContent
                      ]);

        $this->mockPdo->expects($this->once())
                      ->method('prepare')
                      ->with('SELECT timestamp, tick FROM tick WHERE id=?')
                      ->willReturn($mockStatement);

        // Capture output
        ob_start();
        
        $controller = new TickController();
        $controller->index(789);
        
        $output = ob_get_clean();

        // Verify logging shows truncated content with ellipsis
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Successfully loaded tick 789:', $logContent);
        $this->assertStringContainsString('...', $logContent); // Should be truncated
        
        // Verify the log doesn't contain the full long content
        $this->assertStringNotContainsString($longContent, $logContent);
    }

    public function testIndexWithShortTickContent(): void
    {
        // Test that short content is not truncated in logs
        $shortContent = 'Short tick';
        
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->expects($this->once())
                      ->method('execute')
                      ->with([100]);
        $mockStatement->expects($this->once())
                      ->method('fetch')
                      ->with(PDO::FETCH_ASSOC)
                      ->willReturn([
                          'timestamp' => '2025-01-31 09:15:00',
                          'tick' => $shortContent
                      ]);

        $this->mockPdo->expects($this->once())
                      ->method('prepare')
                      ->with('SELECT timestamp, tick FROM tick WHERE id=?')
                      ->willReturn($mockStatement);

        // Capture output
        ob_start();
        
        $controller = new TickController();
        $controller->index(100);
        
        $output = ob_get_clean();

        // Verify logging shows full content without ellipsis
        $logContent = file_get_contents($this->testLogFile);
        $this->assertStringContainsString('Successfully loaded tick 100: Short tick', $logContent);
        $this->assertStringNotContainsString('...', $logContent); // Should NOT be truncated
    }
}