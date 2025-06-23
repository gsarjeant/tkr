<?php
class ConfigModel {
    // properties and default values
    public string $siteTitle = 'My tkr';
    public string $siteDescription = '';
    public string $baseUrl = '';
    public string $basePath = '';
    public int $itemsPerPage = 25;
    public string $timezone = 'relative';
    public ?int $cssId = null;
    public bool $strictAccessibility = true;
    public bool $showTickMood = true;

    // load config from sqlite database
    public static function load(): self {
        $init = require APP_ROOT . '/config/init.php';
        $c = new self();
        $c->baseUrl = ($c->baseUrl === '') ? $init['base_url'] : $c->baseUrl;
        $c->basePath = ($c->basePath === '') ? $init['base_path'] : $c->basePath;

        global $db;
        $stmt = $db->query("SELECT site_title,
                                   site_description,
                                   base_url,
                                   base_path,
                                   items_per_page,
                                   css_id,
                                   strict_accessibility,
                                   show_tick_mood
                            FROM settings WHERE id=1");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $c->siteTitle = $row['site_title'];
            $c->siteDescription = $row['site_description'];
            $c->baseUrl = $row['base_url'];
            $c->basePath = $row['base_path'];
            $c->itemsPerPage = (int) $row['items_per_page'];
            $c->strictAccessibility = (bool) $row['strict_accessibility'];
            $c->showTickMood = (bool) $row['show_tick_mood'];
        }

        return $c;
    }

    public function customCssFilename() {
        if (empty($this->cssId)) {
            return null;
        }

        // Fetch filename from css table using cssId
        $cssModel = new CssModel();
        $cssRecord = $cssModel->getById($this->cssId);

        return $cssRecord ? $cssRecord['filename'] : null;
    }

    public function save(): self {
        global $db;
        $settingsCount = (int) $db->query("SELECT COUNT(*) FROM settings")->fetchColumn();

        if ($settingsCount === 0){
            $stmt = $db->prepare("INSERT INTO settings (
                id,
                site_title,
                site_description,
                base_url,
                base_path,
                items_per_page,
                css_id,
                strict_accessibility,
                show_tick_mood
                )
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)");
        } else {
            $stmt = $db->prepare("UPDATE settings SET
                site_title=?,
                site_description=?,
                base_url=?,
                base_path=?,
                items_per_page=?,
                css_id=?,
                strict_accessibility=?,
                show_tick_mood=?
                WHERE id=1");
        }
        $stmt->execute([$this->siteTitle,
                        $this->siteDescription,
                        $this->baseUrl,
                        $this->basePath,
                        $this->itemsPerPage,
                        $this->cssId,
                        $this->strictAccessibility,
                        $this->showTickMood
                    ]);

        return self::load();
    }
}
