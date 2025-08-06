<?php
declare(strict_types=1);

/**
 * tkr Prerequisites Checker
 *
 * This class checks all system requirements for tkr and provides
 * detailed logging of any missing components or configuration issues.
 *
 */

class Prerequisites {
    private $checks = array();
    private $warnings = array();
    private $errors = array();
    private $baseDir;
    private $logFile;
    private $isCli;
    private $isWeb;
    private $database = null;
    private $storageSubdirs = [
        'storage/db',
        'storage/logs',
        'storage/upload',
        'storage/upload/css',
    ];

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
        if (!is_dir($logDir)) {
            if (!@mkdir($logDir, 0770, true)) {
                // Can't create storage dir - just output, don't log to file
                if ($this->isCli) {
                    echo $message . "\n";
                }
                return;
            }
        }

        // Overwrite the log if $overwrite is set
        // I overwrite the log for each new validation run,
        // because prior results are irrelevant.
        // This keeps it from growing without bound.
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

    // Record the result of a validation check.
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
        // TODO - move to bootstrap.php?
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
        $recommendedExtensions = array('mbstring', 'fileinfo', 'session');

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

    private function checkExistingStoragePermissions() {
        // Issue a warning if running as root in CLI context
        // Write out guidance for storage directory permissions
        // if running the CLI script as root (since it will always appear to be writable)
        if ($this->isCli && function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->addCheck(
                'Root User Warning',
                false,
                'Running as root - permission checks may be inaccurate. After setup, ensure storage/ is owned by your web server user',
                'warning'
            );
        } elseif ($this->isCli && !function_exists('posix_getuid')) {
            $this->addCheck(
                'POSIX Extension',
                false,
                'POSIX extension not available - cannot detect if running as root',
                'warning'
            );
        }

        $storageDirs = array_merge(
            array('storage'),
            $this->storageSubdirs
        );

        $allWritable = true;
        foreach ($storageDirs as $dir) {
            $path = $this->baseDir . '/' . $dir;

            // Only check directories that exist - missing ones are handled in application validation
            if (is_dir($path)) {
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
        }

        return $allWritable;
    }

    private function checkStorageDirectoriesExist() {
        $allPresent = true;
        foreach ($this->storageSubdirs as $dir) {
            $path = $this->baseDir . '/' . $dir;
            $exists = is_dir($path);

            $this->addCheck(
                "Storage Directory: {$dir}",
                $exists,
                $exists ? "Present" : "Missing - will be created during setup",
                $exists ? 'info' : 'info' // Not an error - can be auto-created
            );

            if (!$exists) {
                $allPresent = false;
            }
        }

        return $allPresent;
    }

    private function createStorageDirectories() {
        $storageDirs = array_merge(
            array('storage'),
            $this->storageSubdirs
        );

        $allCreated = true;
        foreach ($storageDirs as $dir) {
            $path = $this->baseDir . '/' . $dir;

            if (!is_dir($path)) {
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
                    $allCreated = false;
                }
            } else {
                $this->addCheck(
                    "Storage Directory: {$dir}",
                    true,
                    "Already exists"
                );
            }
        }

        return $allCreated;
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


    private function checkDatabase() {
        $dbFile = $this->baseDir . '/storage/db/tkr.sqlite';
        $dbDir = dirname($dbFile);

        if (!is_dir($dbDir)) {
            $this->addCheck(
                'Database Directory',
                false,
                'Database directory does not exist',
                'error'
            );
            return false;
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

            if ($dbReadable && $dbWritable) {
                // Test database connection
                try {
                    $db = new PDO("sqlite:" . $dbFile);
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                    // Test basic query to ensure database is functional
                    $db->query("SELECT 1")->fetchColumn();

                    $this->addCheck(
                        'Database Connection',
                        true,
                        'Successfully connected to database'
                    );

                    // Store working database connection
                    $this->database = $db;

                    return true;

                } catch (PDOException $e) {
                    $this->addCheck(
                        'Database Connection',
                        false,
                        'Failed to connect: ' . $e->getMessage(),
                        'error'
                    );
                    return false;
                }
            } else {
                return false;
            }
        } else {
            $this->addCheck(
                'Database File',
                $canCreateDb,
                $canCreateDb ? 'Will be created during setup' : 'Cannot create - directory not writable',
                $canCreateDb ? 'info' : 'error'
            );
            return $canCreateDb;
        }
    }

    private function createDatabase() {
        $dbFile = $this->baseDir . '/storage/db/tkr.sqlite';

        // Test database connection (will create file if needed)
        try {
            $db = new PDO("sqlite:" . $dbFile);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Test basic query to ensure database is functional
            $db->query("SELECT 1")->fetchColumn();

            $this->addCheck(
                'Database Connection',
                true,
                'Successfully connected to database'
            );

            // Store working database connection
            $this->database = $db;

            // Run migrations
            return $this->applyMigrations($db);

        } catch (PDOException $e) {
            $this->addCheck(
                'Database Connection',
                false,
                'Failed to connect: ' . $e->getMessage(),
                'error'
            );
            return false;
        }
    }

    private function applyMigrations($db) {
        try {
            $migrator = new Migrator($db);
            $migrator->migrate();

            $this->addCheck(
                'Database Migrations',
                true,
                'All database migrations applied successfully'
            );
            return true;

        } catch (Exception $e) {
            $this->addCheck(
                'Database Migrations',
                false,
                'Migration failed: ' . $e->getMessage(),
                'error'
            );
            return false;
        }
    }

    // Validate system requirements that can't be fixed by the script
    public function validateSystem(): bool {
        $this->log("=== tkr system validation started at " . date('Y-m-d H:i:s') . " ===", true);

        if ($this->isCli) {
            $this->log("\n🔍 Validating system requirements...\n");
        }

        $results = array(
            'php_version' => $this->checkPhpVersion(),
            'critical_extensions' => $this->checkRequiredExtensions(),
            'directory_structure' => $this->checkDirectoryStructure(),
            'existing_storage_permissions' => $this->checkExistingStoragePermissions(),
            'web_server' => $this->checkWebServerConfig()
        );

        // Check recommended extensions too
        $this->checkRecommendedExtensions();

        if ($this->isCli) {
            $this->generateCliSummary($results);
        }

        // Return true only if no errors occurred
        return count($this->errors) === 0;
    }

    // Validate application state - things that can be fixed
    public function validateApplication(): bool {
        $currentErrors = count($this->errors);

        if ($this->isCli) {
            $this->log("\n🔍 Validating application state...\n");
        }

        $results = array(
            'storage_directories' => $this->checkStorageDirectoriesExist(),
            'database' => $this->checkDatabase()
        );

        // Return true if no NEW errors occurred
        return count($this->errors) === $currentErrors;
    }

    // Create missing application components
    public function createMissing(): bool {
        $this->log("=== tkr setup started at " . date('Y-m-d H:i:s') . " ===", true);

        if ($this->isCli) {
            $this->log("\n🚀 Creating missing components...\n");
        }

        $results = array(
            'storage_setup' => $this->createStorageDirectories(),
            'database_setup' => $this->createDatabase()
        );

        if ($this->isCli) {
            $this->generateCliSummary($results);
        }

        // Return true only if no errors occurred
        return count($this->errors) === 0;
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

        if ($this->isCli && function_exists('posix_getuid') && posix_getuid() === 0) {
            $this->log("\n📋 ROOT USER SETUP RECOMMENDATIONS:");
            $this->log("After uploading to your web server,");
            $this->log("make sure the storage directory is writable by the web server user by running:");
            $this->log("  chown -R www-data:www-data storage/     # Debian/Ubuntu");
            $this->log("  chown -R apache:apache storage/         # RHEL/CentOS/Fedora");
            $this->log("  chmod -R 770 storage/                   # Ensure writability");
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

    /**
     * Get working database connection (only call after validate() returns true)
     */
    public function getDatabase(): PDO {
        if ($this->database === null) {
            throw new RuntimeException('Database not available - call validate() first');
        }
        return $this->database;
    }
}