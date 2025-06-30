<?php
// Validates that required directories exists
// and files have correct formats
class Filesystem {
    public function validate(): void{
        $this->validateStorageDir();
        $this->validateStorageSubdirs();
        $this->migrateTickFiles();
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
        }
    }
}