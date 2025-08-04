<?php
/*
 *  Initialize fundamental configuration
 */

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

/*
 *  Validate application state before processing request
 */

// Check prerequisites (includes database connection and migrations)
$prerequisites = new Prerequisites();
if (!$prerequisites->validate()) {
    $prerequisites->generateWebSummary();
    exit;
}

// Get the working database connection from prerequisites
$db = $prerequisites->getDatabase();

// Make sure the initial setup is complete unless we're already heading to setup
if (!(preg_match('/setup$/', $path))) {
    // Make sure required tables (user, settings) are populated
    $user_count = (int) $db->query("SELECT COUNT(*) FROM user")->fetchColumn();
    $settings_count = (int) $db->query("SELECT COUNT(*) FROM settings")->fetchColumn();

    // If either required table has no records, redirect to setup.
    if ($user_count === 0 || $settings_count === 0){
        $init = require APP_ROOT . '/config/init.php';
        header('Location: ' . $init['base_path'] . 'setup');
        exit;
    };
}

/*
 *  Begin processing request
 */

// Initialize application context with all dependencies
global $app;

$app = [
    'db' => $db,
    'config' => (new ConfigModel($db))->get(),
    'user' => (new UserModel($db))->get(),
];

// Start a session and generate a CSRF Token
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
            header('Location: ' . Util::buildRelativeUrl($app->config->basePath, 'login'));
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
