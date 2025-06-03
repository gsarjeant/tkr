<?php
// TODO - I *think* what I want to do is define just this, then load up all the classes.
// Then I can define all this other boilerplate in Config or Util or whatever.
// I'll have one chicken-and-egg problem with the source directory, but that's not a big deal.
define('APP_ROOT', dirname(dirname(__FILE__)));

// TODO - move all this to a config class?
define('SRC_DIR', APP_ROOT . '/src');
define('STORAGE_DIR', APP_ROOT . '/storage');
define('TEMPLATES_DIR', APP_ROOT . '/templates');

define('TICKS_DIR', STORAGE_DIR . '/ticks');
define('DATA_DIR', STORAGE_DIR . '/db');
define('DB_FILE', DATA_DIR . '/tkr.sqlite');

// Defining this in the index instead of lib/util.php
// to avoid chicken-and-egg issues with including it
function recursive_glob(string $pattern, string $directory): array {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

// load base classes first
require_once SRC_DIR . '/Controller/Controller.php';
// load everything else
foreach (recursive_glob('*.php', SRC_DIR) as $file) {
    require_once $file;
}

Util::confirm_setup();
Session::start();
Session::generateCsrfToken();
$config = Config::load();

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// return a 404 if s request for a .php file gets this far.
if (preg_match('/\.php$/', $path)) {
    http_response_code(404);
    echo '<h1>404 Not Found</h1>';
    exit;
}

// Remove the base path from the URL
// and strip the trailing slash from the resulting route
if (strpos($path, $config->basePath) === 0) {
    $path = substr($path, strlen($config->basePath));
}

$path = trim($path, '/');

function route(string $pattern, string $controller, array $methods = ['GET']) {
    global $path, $method;
    
    if (!in_array($method, $methods)) {
        return false;
    }
    
    $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
    $pattern = '#^' . $pattern . '$#';
    
    if (preg_match($pattern, $path, $matches)) {
        array_shift($matches);

        if (strpos($controller, '@') !== false) {
            [$className, $methodName] = explode('@', $controller);
        } else {
            // Default to 'index' method if no method specified
            $className = $controller;
            $methodName = 'index';
        }
        $instance = new $className();
        call_user_func_array([$instance, $methodName], $matches);
        return true;
    }
    
    return false;
}

// Set content type
header('Content-Type: text/html; charset=utf-8');

// routes
$routes = [
    ['', 'HomeController'],
    ['', 'HomeController@handleTick', ['POST']],
    ['admin', 'AdminController'],
    ['admin', 'AdminController@save', ['POST']],
    ['login', 'AuthController@showLogin'],
    ['login', 'AuthController@handleLogin', ['POST']],
    ['logout', 'AuthController@handleLogout', ['GET', 'POST']],
    ['mood', 'MoodController'],
    ['mood', 'MoodController@handleMood', ['POST']],
    ['feed/rss', 'FeedController@rss'],
    ['feed/atom', 'FeedController@atom'],
];

foreach ($routes as $routeConfig) {
    $pattern = $routeConfig[0];
    $controller = $routeConfig[1];
    $methods = $routeConfig[2] ?? ['GET'];
    
    if (route($pattern, $controller, $methods)) {
        break;
    }
};