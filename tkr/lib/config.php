<?php
require_once __DIR__ . '/../bootstrap.php';

confirm_setup();

// Made this a class so it could be more obvious where config settings are coming from.
// Felt too much like magic constants in other files before.
class Config {
    // properties and default values
    public string $siteTitle = 'My tkr';
    public string $siteDescription = '';
    public string $basePath = '/';
    public int $itemsPerPage = 25;

    // load config from sqlite database
    public static function load(): self {
        $db = get_db();
        $stmt = $db->query("SELECT site_title, site_description, base_path, items_per_page FROM settings WHERE id=1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $c = new self();

        if ($row) {
            $c->siteTitle = $row['site_title'];
            $c->siteDescription = $row['site_description'];
            $c->basePath = $row['base_path'];
            $c->itemsPerPage = (int) $row['items_per_page'];
        }

        return $c;
    }
}