<?php
declare(strict_types=1);

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

// Check system requirements first
$prerequisites = new Prerequisites();
if (!$prerequisites->validateSystem()) {
    $prerequisites->generateWebSummary();
    exit;
}

// Check application state and create missing components if needed
if (!$prerequisites->validateApplication()) {
    if (!$prerequisites->createMissing()) {
        $prerequisites->generateWebSummary();
        exit;
    }
}

// Get the working database connection from prerequisites
$db = $prerequisites->getDatabase();

// Apply any pending database migrations
if (!$prerequisites->applyMigrations($db)){
    $prerequisites->generateWebSummary();
    exit;
}

// Check if setup is complete (user exists and URL is configured)
if (!(preg_match('/tkr-setup$/', $path))) {
    try {
        $user_count = (int) $db->query("SELECT COUNT(*) FROM user")->fetchColumn();
        $settings = (new SettingsModel($db))->get();

        $hasUser = $user_count > 0;
        $hasUrl = !empty($settings->baseUrl) && !empty($settings->basePath);

        if (!$hasUser || !$hasUrl) {
            // Redirect to setup with auto-detected URL
            $autodetected = Util::getAutodetectedUrl();
            header('Location: ' . $autodetected['fullUrl'] . '/tkr-setup');
            exit;
        }
    } catch (Exception $e) {
        // Database error during setup validation - show error page
        error_log("Database error during setup validation: " . $e->getMessage());
        http_response_code(500);
        echo "<h1>Database Error</h1>";
        echo "<p>Cannot validate setup status. The database may be corrupted or locked.</p>";
        echo "<p>Please check your installation or contact your hosting provider.</p>";
        exit;
    }
}

/*
 *  Begin processing request
 */

// Initialize application context with all dependencies
global $app;

$app = [
    'db' => $db,
    'settings' => (new SettingsModel($db))->get(),
    'user' => (new UserModel($db))->get(),
];

// Start a session and generate a CSRF Token
Session::start();
Session::generateCsrfToken();

// Remove the base path from the URL
if (strpos($path, $app['settings']->basePath) === 0) {
    $path = substr($path, strlen($app['settings']->basePath));
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
            header('Location: ' . Util::buildRelativeUrl($app['settings']->basePath, 'login'));
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
