<?php
class Database{
    // TODO = Make this not static
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
        $this->validateTableContents();
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

    public function migrate(): void {
        $migrations = $this->getPendingMigrations();

        if (empty($migrations)) {
            # TODO: log
            return;
        }

        $db = self::get();
        $db->beginTransaction();

        try {
            foreach ($migrations as $version => $file) {
                $filename = basename($file);
                // TODO: log properly

                $sql = file_get_contents($file);
                if ($sql === false) {
                    throw new Exception("Could not read migration file: $file");
                }

                // Remove comments and split by semicolon
                $sql = preg_replace('/--.*$/m', '', $sql);
                $statements = preg_split('/;\s*$/m', $sql, -1, PREG_SPLIT_NO_EMPTY);

                // Execute each statement
                foreach ($statements as $statement){
                    if (!empty($statement)){
                        $db->exec($statement);
                    }
                }
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

    // make sure tables that need to be seeded have been
    public function confirmSetup(): void {
        $db = self::get();

        // make sure required tables (user, settings) are populated
        $user_count = (int) $db->query("SELECT COUNT(*) FROM user")->fetchColumn();
        $settings_count = (int) $db->query("SELECT COUNT(*) FROM settings")->fetchColumn();

        // If either required table has no records, throw an exception.
        // This will be caught and redirect to setup.
        if ($user_count === 0 || $settings_count === 0){
            throw new SetupException(
                "Required tables aren't populated. Please complete setup",
                'table_contents',
            );
        };
    }
}