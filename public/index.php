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
load_classes();

// Initialize core entities
// Defining these as globals isn't great practice,
// but this is a small, single-user app and this data will rarely change.
global $db;
global $config;
global $user;

$db = get_db();
$config = ConfigModel::load();
$user = UserModel::load();

// Remove the base path from the URL
if (strpos($path, $config->basePath) === 0) {
    $path = substr($path, strlen($config->basePath));
}

// strip the trailing slash from the resulting route
$path = trim($path, '/');

// Make sure the initial setup is complete
// unless we're already heading to setup
if (!($path === 'setup')){
    try {
        confirm_setup();
    } catch (SetupException $e) {
        handle_setup_exception($e);
        exit;
    }
}

// Everything's loaded and setup is confirmed.
// Let's start ticking.

// Start a session and generate a CSRF Token
// if there isn't already an active session
Session::start();
Session::generateCsrfToken();

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
