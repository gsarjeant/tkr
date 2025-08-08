<?php
declare(strict_types=1);

require_once dirname(dirname(dirname(__DIR__))) . "/config/bootstrap.php";
use PHPUnit\Framework\TestCase;

class AdminControllerTest extends TestCase
{
    private PDO $mockPdo;
    private SettingsModel $settings;
    private UserModel $user;

    protected function setUp(): void
    {
        // Create mock PDO
        $this->mockPdo = $this->createMock(PDO::class);

        // Create real settings and user objects with mocked PDO
        $this->settings = new SettingsModel($this->mockPdo);
        $this->settings->siteTitle = 'Test Site';
        $this->settings->siteDescription = 'Test Description';
        $this->settings->baseUrl = 'https://example.com';
        $this->settings->basePath = '/tkr';
        $this->settings->itemsPerPage = 10;

        $this->user = new UserModel($this->mockPdo);
        $this->user->username = 'testuser';
        $this->user->displayName = 'Test User';
        $this->user->website = 'https://example.com';

        // Set up global $app for simplified dependency access
        global $app;
        $app = [
            'db' => $this->mockPdo,
            'settings' => $this->settings,
            'user' => $this->user,
        ];
    }

    public function testGetAdminDataRegularMode(): void
    {
        $controller = new AdminController();
        $data = $controller->getAdminData(false);

        // Should return proper structure
        $this->assertArrayHasKey('settings', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('isSetup', $data);

        // Should be the injected instances
        $this->assertSame($this->settings, $data['settings']);
        $this->assertSame($this->user, $data['user']);
        $this->assertFalse($data['isSetup']);
    }

    public function testGetAdminDataSetupMode(): void
    {
        $controller = new AdminController();
        $data = $controller->getAdminData(true);

        // Should return proper structure
        $this->assertArrayHasKey('settings', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('isSetup', $data);

        // Should be the injected instances
        $this->assertSame($this->settings, $data['settings']);
        $this->assertSame($this->user, $data['user']);
        $this->assertTrue($data['isSetup']);
    }

    public function testProcessSettingsSaveWithEmptyData(): void
    {
        $controller = new AdminController();
        $result = $controller->saveSettings([], false);

        $this->assertFalse($result['success']);
        $this->assertContains('No data provided', $result['errors']);
    }

    public function testProcessSettingsSaveValidationErrors(): void
    {
        $controller = new AdminController();

        // Test data with multiple validation errors
        $postData = [
            'username' => '',  // Missing username
            'display_name' => '',  // Missing display name
            'website' => 'invalid-url',  // Invalid URL
            'site_title' => '',  // Missing site title
            'base_url' => '',  // Missing base URL
            'base_path' => 'invalid',  // Invalid base path
            'items_per_page' => 100,  // Too high
            'password' => 'test123',
            'confirm_password' => 'different'  // Passwords don't match
        ];

        $result = $controller->saveSettings($postData, false);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);

        // Should have multiple validation errors
        $this->assertGreaterThan(5, count($result['errors']));
    }

    public function testProcessSettingsSaveValidData(): void
    {
        // Mock PDO to simulate successful database operations
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetchColumn')->willReturn(1); // Existing record count
        $mockStatement->method('fetch')->willReturnOnConsecutiveCalls(
            [
                'site_title' => 'Updated Site',
                'site_description' => 'Updated Description',
                'base_url' => 'https://updated.com',
                'base_path' => '/updated',
                'items_per_page' => 15,
                'css_id' => null,
                'strict_accessibility' => true,
                'log_level' => 2
            ],
            [
                'username' => 'newuser',
                'display_name' => 'New User',
                'website' => 'https://example.com',
                'mood' => ''
            ]
        );

        $this->mockPdo->method('prepare')->willReturn($mockStatement);
        $this->mockPdo->method('query')->willReturn($mockStatement);

        // Create models with mocked PDO
        $settings = new SettingsModel($this->mockPdo);
        $user = new UserModel($this->mockPdo);

        // Update global $app with test models
        global $app;
        $app['settings'] = $settings;
        $app['user'] = $user;

        $controller = new AdminController();

        $postData = [
            'username' => 'newuser',
            'display_name' => 'New User',
            'website' => 'https://example.com',
            'site_title' => 'Updated Site',
            'site_description' => 'Updated Description',
            'base_url' => 'https://updated.com',
            'base_path' => '/updated',
            'items_per_page' => 15,
            'strict_accessibility' => 'on',
            'log_level' => 2
        ];

        $result = $controller->saveSettings($postData, false);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
    }

    public function testProcessSettingsSaveWithPassword(): void
    {
        // Mock PDO for successful save operations
        $mockStatement = $this->createMock(PDOStatement::class);
        $mockStatement->method('execute')->willReturn(true);
        $mockStatement->method('fetchColumn')->willReturn(1);
        $mockStatement->method('fetch')->willReturnOnConsecutiveCalls(
            [
                'site_title' => 'Test Site',
                'site_description' => 'Test Description',
                'base_url' => 'https://example.com',
                'base_path' => '/tkr',
                'items_per_page' => 10,
                'css_id' => null,
                'strict_accessibility' => true,
                'log_level' => 2
            ],
            [
                'username' => 'testuser',
                'display_name' => 'Test User',
                'website' => '',
                'mood' => ''
            ]
        );

        // Verify password hash is called
        $this->mockPdo->expects($this->atLeastOnce())
                     ->method('prepare')
                     ->willReturn($mockStatement);

        $this->mockPdo->method('query')->willReturn($mockStatement);

        // Create models with mocked PDO
        $settings = new SettingsModel($this->mockPdo);
        $user = new UserModel($this->mockPdo);

        // Update global $app with test models
        global $app;
        $app['settings'] = $settings;
        $app['user'] = $user;

        $controller = new AdminController();

        $postData = [
            'username' => 'testuser',
            'display_name' => 'Test User',
            'site_title' => 'Test Site',
            'site_description' => 'Test Description',
            'base_url' => 'https://example.com',
            'base_path' => '/tkr',
            'items_per_page' => 10,
            'password' => 'newpassword',
            'confirm_password' => 'newpassword'
        ];

        $result = $controller->saveSettings($postData, false);

        $this->assertTrue($result['success']);
    }

    public function testProcessSettingsSaveDatabaseError(): void
    {
        // Mock PDO to throw exception on save
        $this->mockPdo->method('query')
                     ->willThrowException(new PDOException("Database error"));

        $settings = new SettingsModel($this->mockPdo);
        $user = new UserModel($this->mockPdo);

        // Update global $app with test models
        global $app;
        $app['settings'] = $settings;
        $app['user'] = $user;

        $controller = new AdminController();

        $postData = [
            'username' => 'testuser',
            'display_name' => 'Test User',
            'site_title' => 'Test Site',
            'site_description' => 'Test Description',
            'base_url' => 'https://example.com',
            'base_path' => '/tkr',
            'items_per_page' => 10
        ];

        $result = $controller->saveSettings($postData, false);

        $this->assertFalse($result['success']);
        $this->assertContains('Failed to save settings', $result['errors']);
    }

}