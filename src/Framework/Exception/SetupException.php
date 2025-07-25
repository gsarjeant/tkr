<?php
// Define an exception for validation errors
class SetupException extends Exception {
    private $setupIssue;

    public function __construct(string $message, string $setupIssue = '', int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->setupIssue = $setupIssue;
    }

    // Exception handler
    // Exceptions don't generally define their own handlers,
    // but this is a very specific case.
    public function handle(){
        switch ($this->setupIssue){
            case 'storage_missing':
            case 'storage_permissions':
            case 'directory_creation':
            case 'directory_permissions':
            case 'database_connection':
            case 'load_classes':
            case 'table_creation':
                // Unrecoverable errors.
                // Show error message and exit
                http_response_code(500);
                echo "<h1>Configuration Error</h1>";
                echo "<p>" . Util::escape_html($this->setupIssue) . '-' . Util::escape_html($this->getMessage()) . "</p>";
                exit;
            case 'table_contents':
                // Recoverable error.
                // Redirect to setup if we aren't already headed there.
                // NOTE: Just read directly from init.php instead of
                //       trying to use the config object. This is the initial
                //       setup. It shouldn't assume anything can be loaded.
                $init = require APP_ROOT . '/config/init.php';
                $currentPath = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

                if (strpos($currentPath, 'setup') === false) {
                    header('Location: ' . $init['base_path'] . 'setup');
                    exit;
                }
        }
    }


}