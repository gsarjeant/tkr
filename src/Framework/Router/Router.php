<?php
// Very simple router class
class Router {
    // Define the recognized routes.
    // Anything else will 404.
    private static $routeHandlers = [
        ['', 'HomeController'],
        ['', 'HomeController@handleTick', ['POST']],
        ['admin', 'AdminController'],
        ['admin', 'AdminController@handleSave', ['POST']],
        ['admin/css', 'CssController'],
        ['admin/css', 'CssController@handlePost', ['POST']],
        ['admin/emoji', 'EmojiController'],
        ['admin/emoji', 'EmojiController@handlePost', ['POST']],
        ['feed/rss', 'FeedController@rss'],
        ['feed/atom', 'FeedController@atom'],
        ['login', 'AuthController@showLogin'],
        ['login', 'AuthController@handleLogin', ['POST']],
        ['logout', 'AuthController@handleLogout', ['GET', 'POST']],
        ['mood', 'MoodController'],
        ['mood', 'MoodController@handlePost', ['POST']],
        ['setup', 'AdminController@showSetup'],
        ['setup', 'AdminController@handleSetup', ['POST']],
        ['tick/{y}/{m}/{d}/{h}/{i}/{s}', 'TickController'],
        ['css/custom/{filename}.css', 'CssController@serveCustomCss'],
    ];


    // Main router function
    public static function route(string $requestPath, string $requestMethod): bool {
        foreach (self::$routeHandlers as $routeHandler) {
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
                        // Get the controller and method that handle this route
                        [$controllerName, $methodName] = explode('@', $controller);
                    } else {
                        // Default to 'index' if no method specified
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

}