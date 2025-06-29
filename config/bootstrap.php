<?php
// This is the initialization code that needs to be run before anything else.
// - define paths
// - confirm /storage directory exists and is writable
// - make sure database is ready
// - load classes

// Define all the important paths
define('APP_ROOT', dirname(dirname(__FILE__)));
define('CONFIG_DIR', APP_ROOT . '/config');
define('SRC_DIR', APP_ROOT . '/src');
define('STORAGE_DIR', APP_ROOT . '/storage');
define('TEMPLATES_DIR', APP_ROOT . '/templates');
define('TICKS_DIR', STORAGE_DIR . '/ticks');
define('DATA_DIR', STORAGE_DIR . '/db');
define('CSS_UPLOAD_DIR', STORAGE_DIR . '/upload/css');
define('DB_FILE', DATA_DIR . '/tkr.sqlite');

// Define an exception for validation errors
class SetupException extends Exception {
    private $setupIssue;

    public function __construct(string $message, string $setupIssue = '', int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->setupIssue = $setupIssue;
    }

    public function getSetupIssue(): string {
        return $this->setupIssue;
    }
}

// Exception handler
function handle_setup_exception(SetupException $e){
    switch ($e->getSetupIssue()){
        case 'storage_missing':
        case 'storage_permissions':
        case 'directory_creation':
        case 'directory_permissions':
        case 'database_connection':
        case 'load_classes':
        case 'table_creation':
            // Unrecoverable errors.
            // Show error message and exit
            http_response_code(500);
            echo "<h1>Configuration Error</h1>";
            echo "<p>" . Util::escape_html($e->getSetupIssue) . '-' . Util::escape_html($e->getMessage()) . "</p>";
            exit;
        case 'table_contents':
            // Recoverable error.
            // Redirect to setup if we aren't already headed there.
            // NOTE: Just read directly from init.php instead of
            //       trying to use the config object. This is the initial
            //       setup. It shouldn't assume anything can be loaded.
            $init = require APP_ROOT . '/config/init.php';
            $currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

            if (strpos($currentPath, 'setup') === false) {
                header('Location: ' . $init['base_path'] . 'setup');
                exit;
            }
    }
}

// Janky autoloader function
// This is a bit more consistent with current frameworks
function autoloader($className) {
    $classFilename = $className . '.php';

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(SRC_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->getFilename() === $classFilename) {
            include_once $file->getPathname();
            return;
        }
    }

    throw new SetupException(
        "Could not load Class $className: " . $e->getMessage(),
        'load_classes'
    );
}

// Register the autoloader
spl_autoload_register('autoloader');

// Main validation function
// Any failures will throw a SetupException
function confirm_setup(): void {
    validate_storage_dir();
    validate_storage_subdirs();
    validate_tables();
    validate_table_contents();
    migrate_db();
    migrate_tick_files();
}

// Make sure the storage/ directory exists and is writable
function validate_storage_dir(): void{
    if (!is_dir(STORAGE_DIR)) {
        throw new SetupException(
            STORAGE_DIR . "does not exist. Please check your installation.",
            'storage_missing'
        );
    }

    if (!is_writable(STORAGE_DIR)) {
        throw new SetupException(
            STORAGE_DIR . "is not writable. Exiting.",
            'storage_permissions'
        );
    }
}

// validate that the required storage subdirectories exist
// attempt to create them if they don't
function validate_storage_subdirs(): void {
    $storageSubdirs = array();
    $storageSubdirs[] = CSS_UPLOAD_DIR;
    $storageSubdirs[] = DATA_DIR;
    $storageSubdirs[] = TICKS_DIR;

    foreach($storageSubdirs as $storageSubdir){
        if (!is_dir($storageSubdir)) {
            if (!mkdir($storageSubdir, 0770, true)) {
                throw new SetupException(
                    "Failed to create required directory: $dir",
                    'directory_creation'
                );
            }
        }

        if (!is_writable($storageSubdir)) {
            if (!chmod($storageSubdir, 0770)) {
                throw new SetupException(
                    "Required directory is not writable: $dir",
                    'directory_permissions'
                );
            }
        }
    }
}

function migrate_tick_files() {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(TICKS_DIR, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($files as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.txt')) {
            migrate_tick_file($file->getPathname());
        }
    }
}

function migrate_tick_file($filepath) {
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $modified = false;

    foreach ($lines as &$line) {
        $fields = explode('|', $line);
        if (count($fields) === 2) {
            // Convert id|text to id|emoji|text
            $line = $fields[0] . '||' . $fields[1];
            $modified = true;
        }
    }

    if ($modified) {
        file_put_contents($filepath, implode("\n", $lines) . "\n");
        // TODO: log properly
        //echo "Migrated: " . basename($filepath) . "\n";
    }
}

function get_db(): PDO {
    try {
        // SQLite will just create this if it doesn't exist.
        $db = new PDO("sqlite:" . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new SetupException(
            "Database connection failed: " . $e->getMessage(),
            'database_connection',
            0,
            $e
        );
    }

    return $db;
}

// The database version will just be an int
// stored as PRAGMA user_version. It will
// correspond to the most recent migration file applied to the db.
function get_db_version(): int {
    $db = get_db();

    return $db->query("PRAGMA user_version")->fetchColumn() ?? 0;
}

function migration_number_from_file(string $filename): int {
    $basename = basename($filename, '.sql');
    $parts = explode('_', $basename);
    return (int) $parts[0];
}

function set_db_version(int $newVersion): void {
    $currentVersion = get_db_version();

    if ($newVersion <= $currentVersion){
        throw new SetupException(
            "New version ($newVersion) must be greater than current version ($currentVersion)",
            'db_migration'
        );
    }

    $db = get_db();
    $db->exec("PRAGMA user_version = $newVersion");
}

function get_pending_migrations(): array {
    $currentVersion = get_db_version();
    $files = glob(CONFIG_DIR . '/migrations/*.sql');

    $pending = [];
    foreach ($files as $file) {
        $version = migration_number_from_file($file);
        if ($version > $currentVersion) {
            $pending[$version] = $file;
        }
    }

    ksort($pending);
    return $pending;
}

function migrate_db(): void {
    $migrations = get_pending_migrations();

    if (empty($migrations)) {
        # TODO: log
        return;
    }

    $db = get_db();
    $db->beginTransaction();

    try {
        foreach ($migrations as $version => $file) {
            $filename = basename($file);
            // TODO: log properly

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new Exception("Could not read migration file: $file");
            }

            // Execute the migration SQL
            $db->exec($sql);
        }

        // Update db version
        $db->commit();
        set_db_version($version);
        //TODO: log properly
        //echo "All migrations completed successfully.\n";

    } catch (Exception $e) {
        $db->rollBack();
        throw new SetupException(
            "Migration failed: $filename",
            'db_migration',
            0,
            $e
        );
    }
}

function create_tables(): void {
    $db = get_db();

    try {
        // user table
        $db->exec("CREATE TABLE IF NOT EXISTS user (
            id INTEGER PRIMARY KEY,
            username TEXT NOT NULL,
            display_name TEXT NOT NULL,
            password_hash TEXT NULL,
            about TEXT NULL,
            website TEXT NULL,
            mood TEXT NULL
        )");

        // settings table
        $db->exec("CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY,
            site_title TEXT NOT NULL,
            site_description TEXT NULL,
            base_url TEXT NOT NULL,
            base_path TEXT NOT NULL,
            items_per_page INTEGER NOT NULL,
            css_id INTEGER NULL
        )");

        // css table
        $db->exec("CREATE TABLE IF NOT EXISTS css (
            id INTEGER PRIMARY KEY,
            filename TEXT UNIQUE NOT NULL,
            description TEXT NULL
        )");

        // mood table
        $db->exec("CREATE TABLE IF NOT EXISTS emoji(
            id INTEGER PRIMARY KEY,
            emoji TEXT UNIQUE NOT NULL,
            description TEXT NOT NULL
        )");
    } catch (PDOException $e) {
        throw new SetupException(
            "Table creation failed: " . $e->getMessage(),
            'table_creation',
            0,
            $e
        );
    }
}

// make sure all tables exist
// attempt to create them if they don't
function validate_tables(): void {
    $appTables = array();
    $appTables[] = "settings";
    $appTables[] = "user";
    $appTables[] = "css";
    $appTables[] = "emoji";

    $db = get_db();

    foreach ($appTables as $appTable){
        $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$appTable]);
        if (!$stmt->fetch()){
            // At least one table doesn't exist.
            // Try creating tables (hacky, but I have 4 total tables)
            // Will throw an exception if it fails
            create_tables();
        }
    }
}

// make sure tables that need to be seeded have been
function validate_table_contents(): void {
    $db = get_db();

    // make sure required tables (user, settings) are populated
    $user_count = (int) $db->query("SELECT COUNT(*) FROM user")->fetchColumn();
    $settings_count = (int) $db->query("SELECT COUNT(*) FROM settings")->fetchColumn();

    // If either required table has no records and we aren't on /admin,
    // redirect to /admin to complete setup
    if ($user_count === 0 || $settings_count === 0){
        throw new SetupException(
            "Required tables aren't populated. Please complete setup",
            'table_contents',
        );
    };
}
