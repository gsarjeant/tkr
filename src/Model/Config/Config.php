<?php
class Config {
    // properties and default values
    public string $siteTitle = 'My tkr';
    public string $siteDescription = '';
    public string $baseUrl = '';
    public string $basePath = '';
    public int $itemsPerPage = 25;
    public string $timezone = 'relative';

    public static function isFirstSetup(): bool {
        return !file_exists(STORAGE_DIR . '/init_complete');
    }

    public static function completeSetup(): void {
        touch(STORAGE_DIR . '/init_complete');
    }

    // load config from sqlite database
    public static function load(): self {
        $init = require APP_ROOT . '/config/init.php';
        $c = new self();
        $c->baseUrl = ($c->baseUrl === '') ? $init['base_url'] : $c->baseUrl;
        $c->basePath = ($c->basePath === '') ? $init['base_path'] : $c->basePath;

        $db = Util::get_db();
        $stmt = $db->query("SELECT site_title, site_description, base_url, base_path, items_per_page FROM settings WHERE id=1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $c->siteTitle = $row['site_title'];
            $c->siteDescription = $row['site_description'];
            $c->baseUrl = $row['base_url'];
            $c->basePath = $row['base_path'];
            $c->itemsPerPage = (int) $row['items_per_page'];
        }

        return $c;
    }

    public function save(): self {
        $db = Util::get_db();

        if (!Config::isFirstSetup()){
            $stmt = $db->prepare("UPDATE settings SET site_title=?, site_description=?, base_url=?, base_path=?, items_per_page=? WHERE id=1");
        } else {
            $stmt = $db->prepare("INSERT INTO settings (id, site_title, site_description, base_url, base_path, items_per_page) VALUES (1, ?, ?, ?, ?, ?)");
        }
        $stmt->execute([$this->siteTitle, $this->siteDescription, $this->baseUrl, $this->basePath, $this->itemsPerPage]);

        return self::load();
    }
}
