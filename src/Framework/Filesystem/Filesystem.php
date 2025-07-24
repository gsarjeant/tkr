<?php
// Validates that required directories exists
// and files have correct formats
class Filesystem {
    public function validate(): void{
        $this->validateStorageDir();
        $this->validateStorageSubdirs();
        $this->migrateTickFiles();
        $this->moveTicksToDatabase();
    }

    // Make sure the storage/ directory exists and is writable
    private function validateStorageDir(): void{
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
    private function validateStorageSubdirs(): void {
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

    // TODO: Delete this sometime before 1.0
    // Add mood to tick files
    private function migrateTickFiles(): void {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(TICKS_DIR, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.txt')) {
                $this->migrateTickFile($file->getPathname());
            }
        }
    }

    // TODO: Delete this sometime before 1.0
    // Add mood field to tick files if necessary
    private function migrateTickFile($filepath): void {
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $modified = false;

        // &$line creates a reference to the line in the array ($lines)
        // so I can modify it in place
        foreach ($lines as &$line) {
            $fields = explode('|', $line);
            if (count($fields) === 2) {
                // Convert id|text to id|emoji|text
                $line = $fields[0] . '||' . $fields[1];
                $modified = true;
            }
        }
        unset($line);

        if ($modified) {
            file_put_contents($filepath, implode("\n", $lines) . "\n");
            // TODO: log properly
        }
    }

    // TODO: Delete this sometime before 1.0
    // Move ticks into database
    private function moveTicksToDatabase(){
        // It's a temporary migration function, so I'm not going to sweat the
        // order of operations to let me reuse the global database.
        $db = Database::get();
        $count = $db->query("SELECT COUNT(*) FROM tick")->fetchColumn();

        // Only migrate from filesystem if there are no ticks already in the database.
        if ($count !== 0){
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(TICKS_DIR, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.txt')) {
                // Construct the date from the path and filename
                $dir = pathinfo($file, PATHINFO_DIRNAME);
                $dir_parts = explode('/', trim($dir, '/'));
                [$year, $month] = array_slice($dir_parts, -2);
                $day = pathinfo($file, PATHINFO_FILENAME);

                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    // Get the time and the text, but discard the mood.
                    // I've decided against using it
                    $fields = explode('|', $line);
                    $time = $fields[0];
                    $tick = $fields[2];

                    $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$year-$month-$day $time");
                    $tickDateTimeUTC = $dateTime->format('Y-m-d H:i:s');

                    $ticks[] = [$tickDateTimeUTC, $tick];
                }
            }
        }

        // Sort the ticks by dateTime
        usort($ticks, function($a, $b) {
            return strcmp($a[0], $b[0]);
        });

        // Save ticks to database
        foreach ($ticks as $tick){
            // Yes, silly, but I'm testing out the datetime/string SQLite conversion
            $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "$tick[0]");
            $timestamp = $dateTime->format('Y-m-d H:i:s');
            $tickText = $tick[1];

            $stmt = $db->prepare("INSERT INTO tick(timestamp, tick) values (?, ?)");
            $stmt->execute([$timestamp, $tickText]);
        }
    }
}