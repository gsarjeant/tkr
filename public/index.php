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

// Make sure the initial setup is complete
try {
    confirm_setup();
} catch (SetupException $e) {
    handle_setup_exception($e);
    exit;
}

// Everything's loaded and setup is confirmed.
// Let's start ticking.
global $db;
$db = get_db();
$config = ConfigModel::load();
Session::start();
Session::generateCsrfToken();

// Remove the base path from the URL
if (strpos($path, $config->basePath) === 0) {
    $path = substr($path, strlen($config->basePath));
}

// strip the trailing slash from the resulting route
$path = trim($path, '/');

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Render the requested route or throw a 404
if (!Router::route($path, $method)){
    http_response_code(404);
    echo "404 - Page Not Found";
    exit;
}
