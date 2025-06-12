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
    // TODO - pass to exception handler (maybe also defined in bootstrap to keep this smaller)
    echo $e->getMessage();
    exit;
}

// Everything's loaded. Now we can start ticking.
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

// Main router function
function route(string $requestPath, string $requestMethod, array $routeHandlers): bool {
    foreach ($routeHandlers as $routeHandler) {
        $routePattern = $routeHandler[0];
        $controller = $routeHandler[1];
        $methods = $routeHandler[2] ?? ['GET'];

        # Only allow valid route and filename characters
        # to prevent directory traversal and other attacks
        $routePattern = preg_replace('/\{([^}]+)\}/', '([a-zA-Z0-9._-]+)', $routePattern);
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

// Define the recognized routes.
// Anything else will 404.
$routeHandlers = [
    ['', 'HomeController'],
    ['', 'HomeController@handleTick', ['POST']],
    ['admin', 'AdminController'],
    ['admin', 'AdminController@handleSave', ['POST']],
    ['admin/css', 'CssController'],
    ['admin/css', 'CssController@handlePost', ['POST']],
    ['feed/rss', 'FeedController@rss'],
    ['feed/atom', 'FeedController@atom'],
    ['login', 'AuthController@showLogin'],
    ['login', 'AuthController@handleLogin', ['POST']],
    ['logout', 'AuthController@handleLogout', ['GET', 'POST']],
    ['mood', 'MoodController'],
    ['mood', 'MoodController@handleMood', ['POST']],
    ['tick/{y}/{m}/{d}/{h}/{i}/{s}', 'TickController'],
    ['css/custom/{filename}.css', 'CssController@serveCustomCss'],
];

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Render the requested route or throw a 404
if (!route($path, $method, $routeHandlers)){
    http_response_code(404);
    echo "404 - Page Not Found";
    exit;
}
