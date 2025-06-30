<?php
class Database{
    public static function get(): PDO {
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

    public function validate(): void{
        $this->validateTables();
        $this->validateTableContents();
        $this->migrate();
    }

    // The database version will just be an int
    // stored as PRAGMA user_version. It will
    // correspond to the most recent migration file applied to the db.
    private function getVersion(): int {
        $db = self::get();

        return $db->query("PRAGMA user_version")->fetchColumn() ?? 0;
    }

    private function migrationNumberFromFile(string $filename): int {
        $basename = basename($filename, '.sql');
        $parts = explode('_', $basename);
        return (int) $parts[0];
    }

    private function setVersion(int $newVersion): void {
        $currentVersion = $this->getVersion();

        if ($newVersion <= $currentVersion){
            throw new SetupException(
                "New version ($newVersion) must be greater than current version ($currentVersion)",
                'db_migration'
            );
        }

        $db = self::get();
        $db->exec("PRAGMA user_version = $newVersion");
    }

    private function getPendingMigrations(): array {
        $currentVersion = $this->getVersion();
        $files = glob(CONFIG_DIR . '/migrations/*.sql');

        $pending = [];
        foreach ($files as $file) {
            $version = $this->migrationNumberFromFile($file);
            if ($version > $currentVersion) {
                $pending[$version] = $file;
            }
        }

        ksort($pending);
        return $pending;
    }

    private function migrate(): void {
        $migrations = $this->getPendingMigrations();

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
            $this->setVersion($version);
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

    private function createTables(): void {
        $db = self::get();

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
    private function validateTables(): void {
        $appTables = array();
        $appTables[] = "settings";
        $appTables[] = "user";
        $appTables[] = "css";
        $appTables[] = "emoji";

        $db = self::get();

        foreach ($appTables as $appTable){
            $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
            $stmt->execute([$appTable]);
            if (!$stmt->fetch()){
                // At least one table doesn't exist.
                // Try creating tables (hacky, but I have 4 total tables)
                // Will throw an exception if it fails
                $this->createTables();
            }
        }
    }

    // make sure tables that need to be seeded have been
    private function validateTableContents(): void {
        $db = self::get();

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
}