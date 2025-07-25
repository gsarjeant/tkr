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

// Define base paths and load classes
include_once(dirname(dirname(__FILE__)) . "/config/bootstrap.php");

// validate that necessary directories exist and are writable
$fsMgr = new Filesystem();
$fsMgr->validate();

// do any necessary database migrations
$dbMgr = new Database();
$dbMgr->migrate();

// Make sure the initial setup is complete
// unless we're already heading to setup
if (!(preg_match('/setup$/', $path))) {
    try {
        // database validation
        $dbMgr->validate();
    } catch (SetupException $e) {
        $e->handle();
        exit;
    }
}

// Get a database connection
// TODO: Change from static function.
global $db;
$db = Database::get();

// Initialize core entities
// Defining these as globals isn't great practice,
// but this is a small, single-user app and this data will rarely change.
global $config;
global $user;

$config = ConfigModel::load();
$user = UserModel::load();

// Start a session and generate a CSRF Token
// if there isn't already an active session
Session::start();
Session::generateCsrfToken();

// Remove the base path from the URL
if (strpos($path, $config->basePath) === 0) {
    $path = substr($path, strlen($config->basePath));
}

// strip the trailing slash from the resulting route
$path = trim($path, '/');

// if this is a POST and we aren't in setup,
// make sure there's a valid session
// if not, redirect to /login or die as appropriate
if ($method === 'POST' && $path != 'setup') {
    if ($path != 'login'){
        if (!Session::isValid($_POST['csrf_token'])) {
            // Invalid session - redirect to /login
            header('Location: ' . $config->basePath . '/login');
            exit;
        }
    } else {
        if (!Session::isValidCsrfToken($_POST['csrf_token'])) {
            // Just die if the token is invalid on login
            die('Invalid CSRF token');
            exit;
        }
    }
}

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Render the requested route or throw a 404
if (!Router::route($path, $method)){
    http_response_code(404);
    echo "404 - Page Not Found";
    exit;
}
