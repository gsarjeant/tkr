<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TickControllerTest extends TestCase
{
    private $mockPdo;
    private $config;
    private $user;

    protected function setUp(): void
    {
        // Reset Log state to prevent test pollution
        Log::init(sys_get_temp_dir() . '/tkr_controller_test.log');
        
        // Set up mocks
        $this->mockPdo = $this->createMock(PDO::class);
        
        $this->config = new ConfigModel($this->mockPdo);
        $this->config->baseUrl = 'https://example.com';
        $this->config->basePath = '/tkr/';
        $this->config->itemsPerPage = 10;
        
        $this->user = new UserModel($this->mockPdo);

        // Set up global $app for simplified dependency access
        global $app;
        $app = [
            'db' => $this->mockPdo,
            'config' => $this->config,
            'user' => $this->user,
        ];
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
    }

}