<?php
declare(strict_types=1);

// This is the initialization code that needs to be run before anything else.
// - define paths
// - set up autoloader

// Define all the important paths
define('APP_ROOT', dirname(dirname(__FILE__)));
// Root-level directories
define('CONFIG_DIR', APP_ROOT . '/config');
define('PUBLIC_DIR', APP_ROOT . '/public');
define('SRC_DIR', APP_ROOT . '/src');
define('STORAGE_DIR', APP_ROOT . '/storage');
// Storage subdirectories
define('CSS_UPLOAD_DIR', STORAGE_DIR . '/upload/css');
define('DATA_DIR', STORAGE_DIR . '/db');
define('TEMPLATES_DIR', APP_ROOT . '/templates');
// Database file
define('DB_FILE', DATA_DIR . '/tkr.sqlite');

// Janky autoloader function
// This is a bit more consistent with current frameworks
function autoloader($className) {
    $classFilename = $className . '.php';

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(SRC_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    // I'm just going to let this fail hard if a requested class doesn't exist.
    foreach ($files as $file) {
        if ($file->getFilename() === $classFilename) {
            include_once $file->getPathname();
            return;
        }
    }
}

// Register the autoloader
spl_autoload_register('autoloader');
