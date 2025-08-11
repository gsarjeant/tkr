<?php
declare(strict_types=1);

class SettingsModel {
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
    public ?int $tickDeleteHours = null;

    public function __construct(private PDO $db) {}

    // Instance method that uses injected database
    public function get(): self {
        $c = new self($this->db);

        $stmt = $this->db->query("SELECT site_title,
                                   site_description,
                                   base_url,
                                   base_path,
                                   items_per_page,
                                   css_id,
                                   strict_accessibility,
                                   log_level,
                                   tick_delete_hours
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
            $c->logLevel = (int) ($row['log_level'] ?? 2);
            $c->tickDeleteHours = (int) ($row['tick_delete_hours'] ?? 1);
        }

        return $c;
    }

    public function save(): self {
        $settingsCount = (int) $this->db->query("SELECT COUNT(*) FROM settings")->fetchColumn();

        if ($settingsCount === 0){
            Log::debug('Initializing settings');
            $stmt = $this->db->prepare("INSERT INTO settings (
                id,
                site_title,
                site_description,
                base_url,
                base_path,
                items_per_page,
                css_id,
                strict_accessibility,
                log_level,
                tick_delete_hours
                )
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        } else {
            Log::debug('Updating settings');
            $stmt = $this->db->prepare("UPDATE settings SET
                site_title=?,
                site_description=?,
                base_url=?,
                base_path=?,
                items_per_page=?,
                css_id=?,
                strict_accessibility=?,
                log_level=?,
                tick_delete_hours=?
                WHERE id=1");
        }

        Log::debug("Site title: " . $this->siteTitle);
        Log::debug("Site description: " . $this->siteDescription);
        Log::debug("Base URL: " . $this->baseUrl);
        Log::debug("Base path: " . $this->basePath);
        Log::debug("Items per page: " . $this->itemsPerPage);
        Log::debug("CSS ID: " . $this->cssId);
        Log::debug("Strict accessibility: " . $this->strictAccessibility);
        Log::debug("Log level: " . $this->logLevel);
        Log::debug("Tick delete window: " . $this->tickDeleteHours);

        $stmt->execute([$this->siteTitle,
                        $this->siteDescription,
                        $this->baseUrl,
                        $this->basePath,
                        $this->itemsPerPage,
                        $this->cssId,
                        $this->strictAccessibility,
                        $this->logLevel,
                        $this->tickDeleteHours
                    ]);

        return $this->get();
    }
}
