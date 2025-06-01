<?php

// Define your base path (subdirectory)
$basePath = '/tkr';

// Get HTTP data
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];

// Remove the base path from the URL
// and strip the trailing slash from the resulting route
$path = parse_url($request, PHP_URL_PATH);

if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
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

// Define your routes
route('', function() {
    echo '<h1>Home Page</h1>';
    echo '<p>Welcome to the home page!</p>';
});
