<?php
class Config {
    // properties and default values
    public string $siteTitle = 'My tkr';
    public string $siteDescription = '';
    public string $baseUrl = 'http://localhost'; //TODO - make this work
    public string $basePath = '/';
    public int $itemsPerPage = 25;
    public string $timezone = 'relative';

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

    public function save(): self {
        $db = get_db();

        $stmt = $db->prepare("UPDATE settings SET site_title=?, site_description=?, base_path=?, items_per_page=? WHERE id=1");
        $stmt->execute([$this->siteTitle, $this->siteDescription, $this->basePath, $this->itemsPerPage]);

        return self::load();
    }
}
