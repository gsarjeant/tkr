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

// Check prerequisites.
$prerequisites = new Prerequisites();
$results = $prerequisites->validate();
if (count($prerequisites->getErrors()) > 0) {
    $prerequisites->generateWebSummary($results);
    exit;
}

// Do any necessary database migrations
$dbMgr = new Database();
$dbMgr->migrate();

// Make sure the initial setup is complete
// unless we're already heading to setup
//
// TODO: Consider simplifying this.
// Might not need the custom exception now that the prereq checker is more robust.
if (!(preg_match('/setup$/', $path))) {
    try {
        // Make sure setup has been completed
        $dbMgr->confirmSetup();
    } catch (SetupException $e) {
        $e->handle();
        exit;
    }
}

// Initialize application context with all dependencies
global $app;
$db = Database::get();
$app = [
    'db' => $db,
    'config' => (new ConfigModel($db))->loadFromDatabase(),
    'user' => (new UserModel($db))->loadFromDatabase(),
];

// Start a session and generate a CSRF Token
// if there isn't already an active session
Session::start();
Session::generateCsrfToken();

// Remove the base path from the URL
if (strpos($path, $app['config']->basePath) === 0) {
    $path = substr($path, strlen($app['config']->basePath));
}

// strip the trailing slash from the resulting route
$path = trim($path, '/');

// Set route context for logging
Log::setRouteContext("$method $path");
Log::debug("Path requested: {$path}");

// if this is a POST and we aren't in setup,
// make sure there's a valid session
// if not, redirect to /login or die as appropriate
if ($method === 'POST' && $path != 'setup') {
    if ($path != 'login'){
        if (!Session::isValid($_POST['csrf_token'])) {
            // Invalid session - redirect to /login
            Log::info('Attempt to POST with invalid session. Redirecting to login.');
            header('Location: ' . Util::buildRelativeUrl($config->basePath, 'login'));
            exit;
        }
    } else {
        if (!Session::isValidCsrfToken($_POST['csrf_token'])) {
            // Just die if the token is invalid on login
            Log::error("Attempt to log in with invalid CSRF token.");
            die('Invalid CSRF token');
            exit;
        }
    }
}

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Render the requested route or throw a 404
$router = new Router();
if (!$router->route($path, $method)){
    http_response_code(404);
    echo "404 - Page Not Found";
    exit;
}
