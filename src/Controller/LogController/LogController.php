<?php
class LogController extends Controller {
    private string $storageDir;

    public function __construct(PDO $db, ConfigModel $config, UserModel $user, ?string $storageDir = null) {
        parent::__construct($db, $config, $user);
        $this->storageDir = $storageDir ?? STORAGE_DIR;
    }

    public function index() {
        // Ensure user is logged in
        if (!Session::isLoggedIn()) {
            global $config;
            header('Location: ' . Util::buildRelativeUrl($config->basePath, 'login'));
            exit;
        }

        // Get filter parameters
        $levelFilter = $_GET['level'] ?? '';
        $routeFilter = $_GET['route'] ?? '';

        // Get the data for the template
        $data = $this->getLogData($levelFilter, $routeFilter);

        $this->render('logs.php', $data);
    }

    public function getLogData(string $levelFilter = '', string $routeFilter = ''): array {
        global $config;

        $limit = 300; // Show last 300 log entries

        // Read and parse log entries
        $logEntries = $this->getLogEntries($limit, $levelFilter, $routeFilter);

        // Get available routes and levels for filter dropdowns
        $availableRoutes = $this->getAvailableRoutes();
        $availableLevels = ['DEBUG', 'INFO', 'WARNING', 'ERROR'];

        return [
            'config' => $config,
            'logEntries' => $logEntries,
            'availableRoutes' => $availableRoutes,
            'availableLevels' => $availableLevels,
            'currentLevelFilter' => $levelFilter,
            'currentRouteFilter' => $routeFilter,
        ];
    }

    private function getLogEntries(int $limit, string $levelFilter = '', string $routeFilter = ''): array {
        $logFile = $this->storageDir . '/logs/tkr.log';
        $entries = [];

        // Read from current log file and rotated files
        $logFiles = [$logFile];
        for ($i = 1; $i <= 5; $i++) {
            $rotatedFile = $logFile . '.' . $i;
            if (file_exists($rotatedFile)) {
                $logFiles[] = $rotatedFile;
            }
        }

        foreach ($logFiles as $file) {
            if (file_exists($file)) {
                $lines = file($file, FILE_IGNORE_NEW_LINES);
                foreach (array_reverse($lines) as $line) {
                    if (count($entries) >= $limit) break 2;

                    $entry = $this->parseLogLine($line);
                    if ($entry && $this->matchesFilters($entry, $levelFilter, $routeFilter)) {
                        $entries[] = $entry;
                    }
                }
            }
        }

        return $entries;
    }

    private function parseLogLine(string $line): ?array {
        // Parse format: [2025-01-31 08:30:15] DEBUG: 192.168.1.100 [GET feed/rss] - message
        $pattern = '/^\[([^\]]+)\] (\w+): ([^\s]+)(?:\s+\[([^\]]+)\])? - (.+)$/';

        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'ip' => $matches[3],
                'route' => $matches[4] ?? '',
                'message' => $matches[5],
                'raw' => $line
            ];
        }

        return null;
    }

    private function matchesFilters(array $entry, string $levelFilter, string $routeFilter): bool {
        if ($levelFilter && $entry['level'] !== $levelFilter) {
            return false;
        }

        if ($routeFilter && $entry['route'] !== $routeFilter) {
            return false;
        }

        return true;
    }

    private function getAvailableRoutes(): array {
        $routes = [];
        $entries = $this->getLogEntries(1000); // Sample more entries to get route list

        foreach ($entries as $entry) {
            if ($entry['route'] && !in_array($entry['route'], $routes)) {
                $routes[] = $entry['route'];
            }
        }

        sort($routes);
        return $routes;
    }
}