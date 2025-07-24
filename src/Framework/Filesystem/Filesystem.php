<?php
// Validates that required directories exists
// and files have correct formats
class Filesystem {
    public function validate(): void{
        $this->validateStorageDir();
        $this->validateStorageSubdirs();
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
}