<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TickModelTest extends TestCase
{
    private $mockPdo;
    private $settings;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->settings = new SettingsModel($this->mockPdo);
        $this->settings->tickDeleteHours = 1; // 1 hour deletion window
    }

    public function testDeleteWithRecentTick(): void
    {
        // Mock successful deletion of recent tick
        $recentTimestamp = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        
        $mockStatement1 = $this->createMock(PDOStatement::class);
        $mockStatement1->method('execute')->with([123]);
        $mockStatement1->method('fetch')->willReturn([
            'tick' => 'Test content',
            'timestamp' => $recentTimestamp
        ]);

        $mockStatement2 = $this->createMock(PDOStatement::class);
        $mockStatement2->method('execute')->with([123]);

        $this->mockPdo->method('prepare')
            ->willReturnOnConsecutiveCalls($mockStatement1, $mockStatement2);

        $tickModel = new TickModel($this->mockPdo, $this->settings);
        $result = $tickModel->delete(123);

        $this->assertTrue($result);
    }

    public function testDeleteWithNonexistentTick(): void
    {
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->with([999]);
        $mockStatement->method('fetch')->willReturn(false);

        $this->mockPdo->method('prepare')->willReturn($mockStatement);

        $tickModel = new TickModel($this->mockPdo, $this->settings);
        $result = $tickModel->delete(999);

        $this->assertFalse($result);
    }

    public function testDeleteWithOldTick(): void
    {
        // Tick from 3 hours ago (outside 1-hour window)
        $oldTimestamp = (new DateTimeImmutable('-3 hours'))->format('Y-m-d H:i:s');
        
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->with([456]);
        $mockStatement->method('fetch')->willReturn([
            'tick' => 'Old content',
            'timestamp' => $oldTimestamp
        ]);

        $this->mockPdo->method('prepare')->willReturn($mockStatement);

        $tickModel = new TickModel($this->mockPdo, $this->settings);
        $result = $tickModel->delete(456);

        $this->assertFalse($result);
    }
}