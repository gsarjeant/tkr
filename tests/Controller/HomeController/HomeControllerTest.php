<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class HomeControllerTest extends TestCase
{
    private PDO $mockPdo;
    private PDOStatement $mockStatement;
    private ConfigModel $mockConfig;
    private UserModel $mockUser;

    protected function setUp(): void
    {
        // Reset Log state to prevent test pollution
        Log::init(sys_get_temp_dir() . '/tkr_controller_test.log');
        
        // Create mock PDO and PDOStatement
        $this->mockStatement = $this->createMock(PDOStatement::class);
        $this->mockPdo = $this->createMock(PDO::class);
        
        // Mock config
        $this->mockConfig = new ConfigModel($this->mockPdo);
        $this->mockConfig->itemsPerPage = 10;
        $this->mockConfig->basePath = '/tkr';
        
        // Mock user
        $this->mockUser = new UserModel($this->mockPdo);
        $this->mockUser->displayName = 'Test User';
        $this->mockUser->mood = '😊';
        
        // Set up global $app for simplified dependency access
        global $app;
        $app = [
            'db' => $this->mockPdo,
            'config' => $this->mockConfig,
            'user' => $this->mockUser,
        ];
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

    private function setupMockDatabaseForInsert(bool $shouldSucceed = true): void
    {
        if ($shouldSucceed) {
            // Mock successful insert
            $this->mockPdo->method('prepare')
                          ->willReturn($this->mockStatement);
            
            $this->mockStatement->method('execute')
                               ->willReturn(true);
        } else {
            // Mock database error
            $this->mockPdo->method('prepare')
                          ->willThrowException(new PDOException("Database error"));
        }
    }

    public function testGetHomeDataWithNoTicks(): void
    {
        $this->setupMockDatabase([]); // Empty array = no ticks
        
        $controller = new HomeController();
        $data = $controller->getHomeData(1);
        
        // Should return proper structure
        $this->assertArrayHasKey('config', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('tickList', $data);
        
        // Config and user should be the injected instances
        $this->assertSame($this->mockConfig, $data['config']);
        $this->assertSame($this->mockUser, $data['user']);
        
        // Should have tick list HTML (even if empty)
        $this->assertIsString($data['tickList']);
    }

    public function testGetHomeDataWithTicks(): void
    {
        // Set up test tick data that the database would return
        $testTicks = [
            ['id' => 1, 'timestamp' => '2025-01-31 12:00:00', 'tick' => 'First tick'],
            ['id' => 2, 'timestamp' => '2025-01-31 13:00:00', 'tick' => 'Second tick'],
            ['id' => 3, 'timestamp' => '2025-01-31 14:00:00', 'tick' => 'Third tick'],
        ];
        
        $this->setupMockDatabase($testTicks);
        
        $controller = new HomeController();
        $data = $controller->getHomeData(1);
        
        // Should return proper structure
        $this->assertArrayHasKey('config', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('tickList', $data);
        
        // Should contain tick content in HTML
        $this->assertStringContainsString('First tick', $data['tickList']);
        $this->assertStringContainsString('Second tick', $data['tickList']);
        $this->assertStringContainsString('Third tick', $data['tickList']);
    }

    public function testGetHomeDataCallsDatabaseCorrectly(): void
    {
        $this->setupMockDatabase([]);
        
        // Verify that PDO prepare is called with the correct SQL
        $this->mockPdo->expects($this->once())
                     ->method('prepare')
                     ->with('SELECT id, timestamp, tick FROM tick ORDER BY timestamp DESC LIMIT ? OFFSET ?')
                     ->willReturn($this->mockStatement);
        
        // Verify that execute is called with correct parameters for page 2
        $this->mockStatement->expects($this->once())
                           ->method('execute')
                           ->with([10, 10]); // itemsPerPage=10, page 2 = offset 10
        
        $controller = new HomeController();
        $controller->getHomeData(2); // Page 2
    }

    public function testProcessTickSuccess(): void
    {
        $this->setupMockDatabaseForInsert(true);
        
        // Verify the INSERT SQL is called correctly
        $this->mockPdo->expects($this->once())
                     ->method('prepare')
                     ->with('INSERT INTO tick(timestamp, tick) values (?, ?)')
                     ->willReturn($this->mockStatement);
        
        // Verify execute is called with timestamp and content
        $this->mockStatement->expects($this->once())
                           ->method('execute')
                           ->with($this->callback(function($params) {
                               // First param should be a timestamp, second should be the tick content
                               return count($params) === 2 
                                   && is_string($params[0]) 
                                   && $params[1] === 'This is a test tick';
                           }));
        
        $controller = new HomeController();
        $postData = ['new_tick' => 'This is a test tick'];
        
        $result = $controller->processTick($postData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Tick saved successfully', $result['message']);
    }

    public function testProcessTickEmptyContent(): void
    {
        // PDO shouldn't be called at all for empty content
        $this->mockPdo->expects($this->never())->method('prepare');
        
        $controller = new HomeController();
        $postData = ['new_tick' => '   '];  // Just whitespace
        
        $result = $controller->processTick($postData);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Empty tick ignored', $result['message']);
    }

    public function testProcessTickMissingField(): void
    {
        // PDO shouldn't be called at all for missing field
        $this->mockPdo->expects($this->never())->method('prepare');
        
        $controller = new HomeController();
        $postData = [];  // No new_tick field
        
        $result = $controller->processTick($postData);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('No tick content provided', $result['message']);
    }

    public function testProcessTickTrimsWhitespace(): void
    {
        $this->setupMockDatabaseForInsert(true);
        
        // Verify execute is called with trimmed content
        $this->mockStatement->expects($this->once())
                           ->method('execute')
                           ->with($this->callback(function($params) {
                               return $params[1] === 'This has whitespace'; // Should be trimmed
                           }));
        
        $controller = new HomeController();
        $postData = ['new_tick' => '  This has whitespace  '];
        
        $result = $controller->processTick($postData);
        
        $this->assertTrue($result['success']);
    }

    public function testProcessTickHandlesDatabaseError(): void
    {
        $this->setupMockDatabaseForInsert(false); // Will throw exception
        
        $controller = new HomeController();
        $postData = ['new_tick' => 'This will fail'];
        
        $result = $controller->processTick($postData);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to save tick', $result['message']);
    }

}