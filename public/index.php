<?php
// Store and validate request data
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// return a 404 if a request for a .php file gets this far.
if (preg_match('/\.php$/', $path)) {
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    exit;
}

define('APP_ROOT', dirname(dirname(__FILE__)));

// Define all the important paths
define('SRC_DIR', APP_ROOT . '/src');
define('STORAGE_DIR', APP_ROOT . '/storage');
define('TEMPLATES_DIR', APP_ROOT . '/templates');
define('TICKS_DIR', STORAGE_DIR . '/ticks');
define('DATA_DIR', STORAGE_DIR . '/db');
define('DB_FILE', DATA_DIR . '/tkr.sqlite');

// Load all classes from the src/ directory
function loadClasses(): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(SRC_DIR)
    );

    // load base classes first
    require_once SRC_DIR . '/Controller/Controller.php';

    // load everything else
    foreach ($iterator as $file) {
        if ($file->isFile() && fnmatch('*.php', $file->getFilename())) {
            require_once $file;
        }
    }
}

loadClasses();

// Everything's loaded. Now we can start ticking.
Util::confirm_setup();
$config = ConfigModel::load();
Session::start();
Session::generateCsrfToken();

// Remove the base path from the URL
// and strip the trailing slash from the resulting route
if (strpos($path, $config->basePath) === 0) {
    $path = substr($path, strlen($config->basePath));
}

$path = trim($path, '/');

// Main router function
function route(string $requestPath, string $requestMethod, array $routeHandlers): bool {
    foreach ($routeHandlers as $routeHandler) {
        $routePattern = $routeHandler[0];
        $controller = $routeHandler[1];
        $methods = $routeHandler[2] ?? ['GET'];

        $routePattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePattern);
        $routePattern = '#^' . $routePattern . '$#';

        if (preg_match($routePattern, $requestPath, $matches)) {
            if (in_array($requestMethod, $methods)){
                // Save any path elements we're interested in
                // (but discard the match on the entire path)
                array_shift($matches);

                if (strpos($controller, '@')) {
                    [$controllerName, $methodName] = explode('@', $controller);
                } else {
                    // Default to 'index' method if no method specified
                    $controllerName = $controller;
                    $methodName = 'index';
                }

                $instance = new $controllerName();
                call_user_func_array([$instance, $methodName], $matches);
                return true;
            }
        }
    }
    
    return false;
}

$routeHandlers = [
    ['', 'HomeController'],
    ['', 'HomeController@handleTick', ['POST']],
    ['admin', 'AdminController'],
    ['admin', 'AdminController@handleSave', ['POST']],
    ['login', 'AuthController@showLogin'],
    ['login', 'AuthController@handleLogin', ['POST']],
    ['logout', 'AuthController@handleLogout', ['GET', 'POST']],
    ['mood', 'MoodController'],
    ['mood', 'MoodController@handleMood', ['POST']],
    ['feed/rss', 'FeedController@rss'],
    ['feed/atom', 'FeedController@atom'],
    ['tick/{y}/{m}/{d}/{h}/{i}/{s}', 'TickController'],
];

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Render the requested route or throw a 404
if (!route($path, $method, $routeHandlers)){
    http_response_code(404);
    echo "404 - Page Not Found";
    exit;
}
