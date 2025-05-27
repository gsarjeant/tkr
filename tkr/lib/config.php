<?php
require_once __DIR__ . '/../bootstrap.php';

confirm_setup();

class Config {
    public string $siteTitle = 'My tkr';
    public string $siteDescription = '';
    public string $basePath = '/';
    public int $itemsPerPage = 25;

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