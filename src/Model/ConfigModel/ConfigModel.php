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
    public ?int $logLevel = null;

    public function __construct(private PDO $db) {}

    // load config from sqlite database (backward compatibility)
    public static function load(): self {
        global $db;
        $instance = new self($db);
        return $instance->loadFromDatabase();
    }
    
    // Instance method that uses injected database
    public function loadFromDatabase(): self {
        $init = require APP_ROOT . '/config/init.php';
        $c = new self($this->db);
        $c->baseUrl = ($c->baseUrl === '') ? $init['base_url'] : $c->baseUrl;
        $c->basePath = ($c->basePath === '') ? $init['base_path'] : $c->basePath;

        $stmt = $this->db->query("SELECT site_title,
                                   site_description,
                                   base_url,
                                   base_path,
                                   items_per_page,
                                   css_id,
                                   strict_accessibility,
                                   log_level
                            FROM settings WHERE id=1");

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $c->siteTitle = $row['site_title'];
            $c->siteDescription = $row['site_description'];
            $c->baseUrl = $row['base_url'];
            $c->basePath = $row['base_path'];
            $c->itemsPerPage = (int) $row['items_per_page'];
            $c->cssId = (int) $row['css_id'];
            $c->strictAccessibility = (bool) $row['strict_accessibility'];
            $c->logLevel = $row['log_level'];
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
        $settingsCount = (int) $this->db->query("SELECT COUNT(*) FROM settings")->fetchColumn();

        if ($settingsCount === 0){
            $stmt = $this->db->prepare("INSERT INTO settings (
                id,
                site_title,
                site_description,
                base_url,
                base_path,
                items_per_page,
                css_id,
                strict_accessibility,
                log_level
                )
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)");
        } else {
            $stmt = $this->db->prepare("UPDATE settings SET
                site_title=?,
                site_description=?,
                base_url=?,
                base_path=?,
                items_per_page=?,
                css_id=?,
                strict_accessibility=?,
                log_level=?
                WHERE id=1");
        }

        $stmt->execute([$this->siteTitle,
                        $this->siteDescription,
                        $this->baseUrl,
                        $this->basePath,
                        $this->itemsPerPage,
                        $this->cssId,
                        $this->strictAccessibility,
                        $this->logLevel
                    ]);

        return $this->loadFromDatabase();
    }
}
