<?php
declare(strict_types=1);

class Migrator{
    public function __construct(private PDO $db) {}

    // The database version is an int stored as PRAGMA user_version.
    // It corresponds to the most recent migration file applied to the db.
    private function getVersion(): int {
        return $this->db->query("PRAGMA user_version")->fetchColumn() ?? 0;
    }

    private function migrationNumberFromFile(string $filename): int {
        $basename = basename($filename, '.sql');
        $parts = explode('_', $basename);
        return (int) $parts[0];
    }

    private function setVersion(int $newVersion): void {
        $currentVersion = $this->getVersion();

        if ($newVersion <= $currentVersion){
            throw new Exception(
                "New version ($newVersion) must be greater than current version ($currentVersion)"
            );
        }

        $this->db->exec("PRAGMA user_version = $newVersion");
    }

    private function getPendingMigrations(): array {
        $currentVersion = $this->getVersion();
        $files = glob(CONFIG_DIR . '/migrations/*.sql');

        $pending = [];
        foreach ($files as $file) {
            $version = $this->migrationNumberFromFile($file);
            if ($version > $currentVersion) {
                Log::debug("Found pending migration ({$version}): " . basename($file));
                $pending[$version] = $file;
            }
        }

        ksort($pending);
        return $pending;
    }

    public function migrate(): void {
        $migrations = $this->getPendingMigrations();

        if (empty($migrations)) {
            Log::debug("No pending migrations");
            return;
        }
        Log::info("Found " . count($migrations) . " pending migrations.");
        Log::info("Updating database. Current Version: " . $this->getVersion());

        $this->db->beginTransaction();

        try {
            foreach ($migrations as $version => $file) {
                $filename = basename($file);
                Log::debug("Starting migration: {$filename}");

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
                        Log::debug("Migration statement: {$statement}");
                        $this->db->exec($statement);
                    }
                }

                Log::info("Applied migration {$filename}");
            }

            // Update db version
            $this->db->commit();
            $this->setVersion($version);

            Log::info("Applied " . count($migrations) . " migrations.");
            Log::info("Updated database version to " . $this->getVersion());
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception(
                "Migration failed: $filename - " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}