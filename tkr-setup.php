#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * tkr Setup Script
 *
 * Interactive CLI setup for tkr - run this once after installation
 * Usage: php tkr-setup.php [--validate-only]
 */

// Ensure this is run from command line only
if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

// Check for validate-only flag
$validateOnly = in_array('--validate-only', $argv);

// Load the bootstrap
require_once __DIR__ . '/config/bootstrap.php';

if (!$validateOnly) {
    echo "🚀 Welcome to tkr Setup!\n";
    echo "This will configure your tkr installation.\n\n";
}

// Check system requirements first
$prerequisites = new Prerequisites();
if (!$prerequisites->validateSystem()) {
    echo "\n❌ System requirements not met. Please resolve the issues above before continuing.\n";
    exit(1);
}

echo "✅ System requirements met\n\n";

// Check application state
$applicationReady = $prerequisites->validateApplication();

if ($applicationReady) {
    echo "✅ All prerequisites satisfied - tkr is ready to run!\n";
} else {
    echo "⚠️  Application components need to be created\n\n";
    if ($validateOnly) {
        echo "⚠️  Run 'php tkr-setup.php' (without --validate-only) to complete setup.\n";
    }
}

// If validate-only flag, exit here
if ($validateOnly) {
    // Always exit with success.
    // If app configuration needs to be completed, the script can handle that.
    exit(0);
}

// Continue with setup process
$db = null;
try {
    if ($applicationReady) {
        $db = $prerequisites->getDatabase();

        // Check if user already exists
        $userCount = (int) $db->query("SELECT COUNT(*) FROM user")->fetchColumn();
        if ($userCount > 0) {
            echo "⚠️  tkr appears to already be set up.\n";
            echo "Continue anyway? (y/N): ";
            $continue = trim(fgets(STDIN));
            if (strtolower($continue) !== 'y') {
                echo "Setup cancelled.\n";
                exit(0);
            }
            echo "\n";
        }
    }
} catch (Exception $e) {
    // Application not ready - will create below
}

// If application isn't ready, create missing components
if (!$db) {
    echo "Setting up application components...\n";
    if (!$prerequisites->createMissing()) {
        echo "❌ Failed to create application components. Check the errors above.\n";
        exit(1);
    }

    try {
        $db = $prerequisites->getDatabase();
        echo "✅ Application components created\n\n";
    } catch (Exception $e) {
        echo "❌ Failed to get database connection: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Prompt for configuration
echo "📝 Please provide the following information:\n\n";

// 1. Site URL (with auto-detect option)
echo "1. Site URL (including base path if not root)\n";
echo "   Examples: https://example.com or https://example.com/tkr\n";
echo "   Leave blank to auto-detect from first web request\n";
echo "   Site URL (optional): ";
$siteUrl = trim(fgets(STDIN));

if (empty($siteUrl)) {
    echo "✅ Will auto-detect URL on first web request\n";
    $baseUrl = '';
    $basePath = '';
} else {
    // Parse URL to extract base URL and base path
    $parsedUrl = parse_url($siteUrl);
    if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
        echo "❌ Invalid URL format\n";
        exit(1);
    }

    // Validate host for basic security
    if (!preg_match('/^[a-zA-Z0-9.-]+$/', $parsedUrl['host'])) {
        echo "❌ Invalid characters in hostname\n";
        exit(1);
    }

    $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    if (isset($parsedUrl['port']) && $parsedUrl['port'] != 80 && $parsedUrl['port'] != 443) {
        $baseUrl .= ':' . $parsedUrl['port'];
    }

    $basePath = isset($parsedUrl['path']) ? rtrim($parsedUrl['path'], '/') : '';
    if (empty($basePath)) {
        $basePath = '/';
    } else {
        $basePath = '/' . trim($basePath, '/') . '/';
    }
}

echo "\n";

// 2. Admin credentials
echo "2. Admin username: ";
$adminUsername = trim(fgets(STDIN));

if (empty($adminUsername)) {
    echo "❌ Admin username is required\n";
    exit(1);
}

echo "3. Admin password: ";
// Hide password input on Unix systems
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    system('stty -echo');
}
$adminPassword = trim(fgets(STDIN));
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    system('stty echo');
}
echo "\n";

if (empty($adminPassword)) {
    echo "❌ Admin password is required\n";
    exit(1);
}

echo "\n4. Site title (optional, default: 'My tkr Site'): ";
$siteTitle = trim(fgets(STDIN));
if (empty($siteTitle)) {
    $siteTitle = 'My tkr Site';
}

echo "\n";

// Save configuration
try {
    echo "💾 Saving configuration...\n";

    // Create/update settings
    $configModel = new ConfigModel($db);
    $configModel->siteTitle = $siteTitle;
    $configModel->baseUrl = $baseUrl;
    $configModel->basePath = $basePath;
    $config = $configModel->save();

    // Create admin user
    $userModel = new UserModel($db);
    $userModel->username = $adminUsername;
    $userModel->display_name = $adminUsername;
    $userModel->website = '';
    $userModel->mood = '';
    $user = $userModel->save();

    // Set admin password
    $userModel->setPassword($adminPassword);

    echo "✅ Configuration saved\n";
    echo "✅ Admin user created\n\n";

    echo "🎉 Setup complete!\n\n";

    if (!empty($baseUrl)) {
        echo "Your tkr site is ready at: $siteUrl\n";
    } else {
        echo "Your tkr site will be ready after you visit it in a web browser\n";
        echo "The URL will be auto-detected on first access\n";
    }

    echo "Login with username: $adminUsername\n\n";
    echo "You can now:\n";
    echo "• Point your web server document root to the 'public/' directory\n";
    echo "• Visit your site and log in\n";
    echo "• Customize additional settings through the admin interface\n\n";

} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    exit(1);
}