<?php
#require_once __DIR__ . '/../bootstrap.php';

define('APP_ROOT', dirname(dirname(__FILE__)));

define('CLASSES_DIR', APP_ROOT . '/src/classes');
define('LIB_DIR', APP_ROOT . '/src/lib');
define('STORAGE_DIR', APP_ROOT . '/storage');
define('TEMPLATES_DIR', APP_ROOT . '/templates');

define('TICKS_DIR', STORAGE_DIR . '/ticks');
define('DATA_DIR', STORAGE_DIR . '/db');
define('DB_FILE', DATA_DIR . '/tkr.sqlite');

$include_dirs = [
    LIB_DIR,
    CLASSES_DIR,
];

foreach ($include_dirs as $include_dir){
    foreach (glob($include_dir . '/*.php') as $file) {
        require_once $file;
    }
}

confirm_setup();

$isLoggedIn = isset($_SESSION['user_id']);
$config = Config::load();
$user = User::load();

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

function route($pattern, $callback, $methods = ['GET']) {
    global $path, $method;
    
    if (!in_array($method, $methods)) {
        return false;
    }
    
    // Convert route pattern to regex
    $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
    $pattern = '#^' . $pattern . '$#';
    
    if (preg_match($pattern, $path, $matches)) {
        array_shift($matches); // Remove full match
        call_user_func_array($callback, $matches);
        return true;
    }
    
    return false;
}

// Set content type
header('Content-Type: text/html; charset=utf-8');
echo "Path: " . $path;

// routes
route('', function() use ($isLoggedIn, $config, $user) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = $config->itemsPerPage;
    $offset = ($page - 1) * $limit;
    $ticks = iterator_to_array(stream_ticks($limit, $offset));

    $vars = [
        'isLoggedIn' => $isLoggedIn,
        'config'     => $config,
        'user'       => $user,
        'ticks'      => $ticks,
    ];

    echo render_template(TEMPLATES_DIR . "/home.php", $vars);
});
