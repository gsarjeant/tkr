<?php

define('APP_ROOT', dirname(__FILE__));
define('CLASSES_DIR', APP_ROOT . '/classes');
define('LIB_DIR', APP_ROOT . '/lib');
define('TICKS_DIR', APP_ROOT . '/storage/ticks');
define('DATA_DIR', APP_ROOT . '/storage/db');
define('DB_FILE', DATA_DIR . '/tkr.sqlite');

function verify_data_dir(string $dir, bool $allow_create = false): void {
    if (!is_dir($dir)) {
        if ($allow_create) {
            if (!mkdir($dir, 0770, true)) {
                http_response_code(500);
                echo "Failed to create required directory: $dir";
                exit;
            }
        } else {
            http_response_code(500);
            echo "Required directory does not exist: $dir";
            exit;
        }
    }

    if (!is_writable($dir)) {
        http_response_code(500);
        echo "Directory is not writable: $dir";
        exit;
    }
}

// Verify that setup is complete (i.e. the databse is populated).
// Redirect to setup.php if it isn't.
function confirm_setup(): void {
    $db = get_db();

    // Ensure required tables exist
    $db->exec("CREATE TABLE IF NOT EXISTS user (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        display_name TEXT NOT NULL,
        password_hash TEXT NOT NULL,
        about TEXT NULL,
        website TEXT NULL,
        mood TEXT NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY,
        site_title TEXT NOT NULL,
        site_description TEXT NULL,
        base_path TEXT NOT NULL,
        items_per_page INTEGER NOT NULL
    )");

    // See if there's any data in the tables
    $user_count = (int) $db->query("SELECT COUNT(*) FROM user")->fetchColumn();
    $settings_count = (int) $db->query("SELECT COUNT(*) FROM settings")->fetchColumn();

    // If either table has no records and we aren't on setup.php, redirect to setup.php
    if ($user_count === 0 || $settings_count === 0){
        if (basename($_SERVER['PHP_SELF']) !== 'setup.php'){
            header('Location: setup.php');
            exit;
        }
    } else {
        // If setup is complete and we are on setup.php, redirect to index.php.
        if (basename($_SERVER['PHP_SELF']) === 'setup.php'){
            header('Location: index.php');
            exit;
        }
    };
}

function get_db(): PDO {
    verify_data_dir(DATA_DIR, true);

    try {
        $db = new PDO("sqlite:" . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
    
    return $db;
}
