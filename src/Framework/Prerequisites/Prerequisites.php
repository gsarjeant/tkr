<?php
/**
 * tkr Prerequisites Checker
 *
 * This class checks all system requirements for tkr and provides
 * detailed logging of any missing components or configuration issues.
 *
 * ZERO DEPENDENCIES - Uses only core PHP functions available since PHP 5.3
 */

class Prerequisites {
    private $checks = array();
    private $warnings = array();
    private $errors = array();
    private $baseDir;
    private $logFile;
    private $isCli;
    private $isWeb;

    public function __construct() {
        $this->isCli = php_sapi_name() === 'cli';
        $this->isWeb = !$this->isCli && isset($_SERVER['HTTP_HOST']);
        $this->baseDir = APP_ROOT;
        $this->logFile = $this->baseDir . '/storage/prerequisite-check.log';

        if ($this->isWeb) {
            header('Content-Type: text/html; charset=utf-8');
        }
    }

    /** Log validation output
     *
     * This introduces a chicken-and-egg problem, because
     * if the storage directory isn't writable, this will fail.
     * In that case, I'll just write to stdout.
     *
     */
    private function log($message, $overwrite=false) {
        $logDir = dirname($this->logFile);
        //print("Log dir: {$logDir}");
        if (!is_dir($logDir)) {
            if (!@mkdir($logDir, 0770, true)) {
                // Can't create storage dir - just output, don't log to file
                if ($this->isCli) {
                    echo $message . "\n";
                }
                return;
            }
        }

        $flags = LOCK_EX;
        if (!$overwrite) {
            $flags |= FILE_APPEND;
        }

        // Try to write to log file
        if (@file_put_contents($this->logFile, $message . "\n", $flags) === false) {
            // Logging failed, but continue - just output to CLI if possible
            if ($this->isCli) {
                echo "Warning: Could not write to log file\n";
            }
        }

        if ($this->isCli) {
            echo $message . "\n";
        }
    }

    private function addCheck($name, $status, $message, $severity = 'info') {
        $this->checks[] = array(
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'severity' => $severity
        );

        if ($severity === 'error') {
            $this->errors[] = $message;
        } elseif ($severity === 'warning') {
            $this->warnings[] = $message;
        }

        $statusIcon = $status ? '✓' : '✗';
        $this->log("[{$statusIcon}] {$name}: {$message}");
    }

    private function checkPhpVersion() {
        $minVersion = '8.2.0';
        $currentVersion = PHP_VERSION;
        $versionOk = version_compare($currentVersion, $minVersion, '>=');

        if ($versionOk) {
            $this->addCheck(
                'PHP Version',
                true,
                "PHP {$currentVersion} (meets minimum requirement of {$minVersion})"
            );
        } else {
            $this->addCheck(
                'PHP Version',
                false,
                "PHP {$currentVersion} is below minimum requirement of {$minVersion}",
                'error'
            );
        }

        return $versionOk;
    }

    private function checkRequiredExtensions() {
        $requiredExtensions = array('PDO', 'pdo_sqlite');

        $allRequired = true;
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->addCheck(
                "PHP Extension: {$ext}",
                $loaded,
                $loaded ? 'Available' : "Missing (REQUIRED) - {$ext}",
                $loaded ? 'info' : 'error'
            );
            if (!$loaded) {
                $allRequired = false;
            }
        }

        return $allRequired;
    }

    private function checkRecommendedExtensions() {
        $recommendedExtensions = array('mbstring', 'curl', 'fileinfo', 'session');

        foreach ($recommendedExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->addCheck(
                "PHP Extension: {$ext}",
                $loaded,
                $loaded ? 'Available' : "Missing (recommended) - {$ext}",
                $loaded ? 'info' : 'warning'
            );
        }
    }

    private function checkDirectoryStructure() {
        $baseDir = $this->baseDir;
        $requiredDirs = array(
            'config' => 'Configuration files',
            'public' => 'Web server document root',
            'src' => 'Application source code',
            'storage' => 'Data storage (must be writable)',
            'templates' => 'Template files'
        );

        $allPresent = true;
        foreach ($requiredDirs as $dir => $description) {
            $path = $baseDir . '/' . $dir;
            $exists = is_dir($path);
            $this->addCheck(
                "Directory: {$dir}",
                $exists,
                $exists ? "Present - {$description}" : "Missing - {$description} at {$path}",
                $exists ? 'info' : 'error'
            );
            if (!$exists) {
                $allPresent = false;
            }
        }

        return $allPresent;
    }

    private function checkStoragePermissions() {
        $storageDirs = array(
            'storage',
            'storage/db',
            'storage/upload',
            'storage/upload/css'
        );

        $allWritable = true;
        foreach ($storageDirs as $dir) {
            $path = $this->baseDir . '/' . $dir;

            if (!is_dir($path)) {
                // Try to create the directory
                $created = @mkdir($path, 0770, true);
                if ($created) {
                    $this->addCheck(
                        "Storage Directory: {$dir}",
                        true,
                        "Created with correct permissions (0770)"
                    );
                } else {
                    $this->addCheck(
                        "Storage Directory: {$dir}",
                        false,
                        "Could not create directory: {$dir}",
                        'error'
                    );
                    $allWritable = false;
                    continue;
                }
            }

            $writable = is_writable($path);
            $permissions = substr(sprintf('%o', fileperms($path)), -4);

            $this->addCheck(
                "Storage Permissions: {$dir}",
                $writable,
                $writable ? "Writable (permissions: {$permissions})" : "Not writable (permissions: {$permissions})",
                $writable ? 'info' : 'error'
            );

            if (!$writable) {
                $allWritable = false;
            }
        }

        return $allWritable;
    }

    private function checkWebServerConfig() {
        if ($this->isCli) {
            $this->addCheck(
                'Web Server Test',
                false,
                'Cannot test web server configuration from CLI - run via web browser',
                'warning'
            );
            return false;
        }

        // Check if we're being served from the correct document root
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
        $expectedPath = realpath($this->baseDir . '/public');
        $correctRoot = ($documentRoot === $expectedPath);

        $this->addCheck(
            'Document Root',
            $correctRoot,
            $correctRoot ?
                "Correctly set to {$expectedPath}" :
                "Should be {$expectedPath}, currently {$documentRoot}",
            $correctRoot ? 'info' : 'warning'
        );

        // Check for URL rewriting
        $rewriteWorking = isset($_SERVER['REQUEST_URI']);
        $this->addCheck(
            'URL Rewriting',
            $rewriteWorking,
            $rewriteWorking ? 'Available' : 'May not be properly configured',
            $rewriteWorking ? 'info' : 'warning'
        );

        return true;
    }

    private function checkConfiguration() {
        $configFile = $this->baseDir . '/config/init.php';
        $configExists = file_exists($configFile);

        if (!$configExists) {
            $this->addCheck(
                'Configuration File',
                false,
                'config/init.php not found',
                'error'
            );
            return false;
        }

        try {
            $config = include $configFile;
            $hasBaseUrl = isset($config['base_url']) && !empty($config['base_url']);
            $hasBasePath = isset($config['base_path']) && !empty($config['base_path']);

            $this->addCheck(
                'Configuration File',
                true,
                'config/init.php exists and is readable'
            );

            $this->addCheck(
                'Base URL Configuration',
                $hasBaseUrl,
                $hasBaseUrl ? "Set to: {$config['base_url']}" : 'Not configured',
                $hasBaseUrl ? 'info' : 'warning'
            );

            $this->addCheck(
                'Base Path Configuration',
                $hasBasePath,
                $hasBasePath ? "Set to: {$config['base_path']}" : 'Not configured',
                $hasBasePath ? 'info' : 'warning'
            );

            return $hasBaseUrl && $hasBasePath;

        } catch (Exception $e) {
            $this->addCheck(
                'Configuration File',
                false,
                'Error reading config/init.php: ' . $e->getMessage(),
                'error'
            );
            return false;
        }
    }

    private function checkDatabase() {
        $dbFile = $this->baseDir . '/storage/db/tkr.sqlite';
        $dbDir = dirname($dbFile);

        if (!is_dir($dbDir)) {
            $created = @mkdir($dbDir, 0770, true);
            if (!$created) {
                $this->addCheck(
                    'Database Directory',
                    false,
                    'Could not create storage/db directory',
                    'error'
                );
                return false;
            }
        }

        $canCreateDb = is_writable($dbDir);
        $this->addCheck(
            'Database Directory',
            $canCreateDb,
            $canCreateDb ? 'Writable - can create database' : 'Not writable - cannot create database',
            $canCreateDb ? 'info' : 'error'
        );

        if (file_exists($dbFile)) {
            $dbReadable = is_readable($dbFile);
            $dbWritable = is_writable($dbFile);

            $this->addCheck(
                'Database File',
                $dbReadable && $dbWritable,
                $dbReadable && $dbWritable ? 'Exists and is accessible' : 'Exists but has permission issues',
                $dbReadable && $dbWritable ? 'info' : 'error'
            );
        } else {
            $this->addCheck(
                'Database File',
                true,
                'Will be created on first run'
            );
        }

        return $canCreateDb;
    }

    // validate prereqs
    // runs on each request and can be run from CLI
    public function validate() {
        $this->log("=== tkr prerequisites check started at " . date('Y-m-d H:i:s') . " ===", true);

        if ($this->isCli) {
            $this->log("\n🔍 Validating prerequisites...\n");
        }

        $results = array(
            'php_version' => $this->checkPhpVersion(),
            'critical_extensions' => $this->checkRequiredExtensions(),
            'directory_structure' => $this->checkDirectoryStructure(),
            'storage_permissions' => $this->checkStoragePermissions(),
            'web_server' => $this->checkWebServerConfig(),
            'configuration' => $this->checkConfiguration(),
            'database' => $this->checkDatabase()
        );

        // Check recommended extensions too
        $this->checkRecommendedExtensions();

        if ($this->isCli) {
            $this->generateCliSummary($results);
        }

        return $results;
    }

    /**
     * Display web-friendly error page when minimum requirements aren't met
     */
    public function generateWebSummary() {
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tkr - Setup Required</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 2rem; line-height: 1.6; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #dee2e6; }
        .header h1 { color: #dc3545; margin: 0; }
        .header p { color: #6c757d; margin: 0.5rem 0 0 0; }
        .error-item { margin: 1rem 0; padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; border-left: 4px solid #dc3545; }
        .warning-item { margin: 1rem 0; padding: 1rem; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; border-left: 4px solid #ffc107; }
        .error-title { font-weight: 600; color: #721c24; margin-bottom: 0.5rem; }
        .warning-title { font-weight: 600; color: #856404; margin-bottom: 0.5rem; }
        .resolution { margin-top: 2rem; padding: 1rem; background: #e9ecef; border-radius: 4px; }
        .resolution h3 { margin-top: 0; color: #495057; }
        .resolution ul { margin: 0; }
        .resolution li { margin: 0.5rem 0; }
        .log-info { margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 4px; font-size: 0.9em; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Setup Required</h1>
            <p>tkr cannot start due to system configuration issues</p>
        </div>';

        $hasErrors = false;
        $hasWarnings = false;

        // Display errors
        foreach ($this->checks as $check) {
            if (!$check['status'] && $check['severity'] === 'error') {
                if (!$hasErrors) {
                    echo '<h2>Critical Issues</h2>';
                    $hasErrors = true;
                }
                echo '<div class="error-item">
                    <div class="error-title">✗ ' . htmlspecialchars($check['name']) . '</div>
                    ' . htmlspecialchars($check['message']) . '
                </div>';
            }
        }

        // Display warnings
        foreach ($this->checks as $check) {
            if (!$check['status'] && $check['severity'] === 'warning') {
                if (!$hasWarnings) {
                    echo '<h2>Warnings</h2>';
                    $hasWarnings = true;
                }
                echo '<div class="warning-item">
                    <div class="warning-title">⚠ ' . htmlspecialchars($check['name']) . '</div>
                    ' . htmlspecialchars($check['message']) . '
                </div>';
            }
        }

        // Resolution steps
        echo '<div class="resolution">
            <h3>How to Fix These Issues</h3>
            <ul>';

        if (!version_compare(PHP_VERSION, '8.2.0', '>=')) {
            echo '<li><strong>PHP Version:</strong> Contact your hosting provider to upgrade PHP to version 8.2 or higher</li>';
        }

        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            echo '<li><strong>SQLite Support:</strong> Contact your hosting provider to enable PDO and PDO_SQLITE extensions</li>';
        }

        if (count($this->errors) > 0) {
            echo '<li><strong>File Permissions:</strong> Ensure the storage directory and subdirectories are writable by the web server</li>';
            echo '<li><strong>Missing Directories:</strong> Upload the complete tkr application with all required directories</li>';
        }

        echo '    </ul>
            <p><strong>Need Help?</strong> Check the tkr documentation or contact your hosting provider with the error details above.</p>
        </div>

        <div class="log-info">
            <p><strong>Technical Details:</strong> Full diagnostic information has been logged to ' . htmlspecialchars($this->logFile) . '</p>
            <p><strong>Check Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';
    }

    private function generateCliSummary($results) {
        $this->log("\n" . str_repeat("=", 60));
        $this->log("PREREQUISITE CHECK SUMMARY");
        $this->log(str_repeat("=", 60));

        $totalChecks = count($this->checks);
        $passedChecks = 0;
        foreach ($this->checks as $check) {
            if ($check['status']) {
                $passedChecks++;
            }
        }

        $this->log("Total checks: {$totalChecks}");
        $this->log("Passed: {$passedChecks}");
        $this->log("Errors: " . count($this->errors));
        $this->log("Warnings: " . count($this->warnings));

        if (count($this->errors) === 0) {
            $this->log("\n✅ ALL PREREQUISITES SATISFIED");
            $this->log("tkr should install and run successfully.");
        } else {
            $this->log("\n❌ CRITICAL ISSUES FOUND");
            $this->log("The following issues must be resolved before installing tkr:");
            foreach ($this->errors as $error) {
                $this->log("  • {$error}");
            }
        }

        if (count($this->warnings) > 0) {
            $this->log("\n⚠️  WARNINGS:");
            foreach ($this->warnings as $warning) {
                $this->log("  • {$warning}");
            }
        }

        $this->log("\n📝 Full log saved to: " . $this->logFile);
        $this->log("=== Check completed at " . date('Y-m-d H:i:s') . " ===");
    }

    /**
     * Get array of errors for external use
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get array of warnings for external use
     */
    public function getWarnings() {
        return $this->warnings;
    }
}