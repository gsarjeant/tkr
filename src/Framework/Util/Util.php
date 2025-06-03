<?php
class Util {
    public static function escape_and_linkify(string $text): string {
        // escape dangerous characters, but preserve quotes
        $safe = htmlspecialchars($text, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');

        // convert URLs to links
        $safe = preg_replace_callback(
            '~(https?://[^\s<>"\'()]+)~i',
            fn($matches) => '<a href="' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . $matches[1] . '</a>',
            $safe
        );

        return $safe;
    }

    // For relative time display, compare the stored time to the current time
    // and display it as "X second/minutes/hours/days etc. "ago
    public static function relative_time(string $tickTime): string {
        $datetime = new DateTime($tickTime);
        $now = new DateTime('now', $datetime->getTimezone());
        $diff = $now->diff($datetime);

        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        }
        if ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        }
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        }
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return $diff->s . ' second' . ($diff->s != 1 ? 's' : '') . ' ago';
    }

    public static function verify_data_dir(string $dir, bool $allow_create = false): void {
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
    public static function confirm_setup(): void {
        $db = Util::get_db();

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

    // TODO: Move to model base class?
    public static function get_db(): PDO {
        Util::verify_data_dir(DATA_DIR, true);

        try {
            $db = new PDO("sqlite:" . DB_FILE);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }

        return $db;
    }
}