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
        // try to log the error, but keep going if it fails
        try {
            Log::error($this->setupIssue . ", " . $this->getMessage());
        } catch (Exception $e) {
            // Do nothing and move on to the normal error handling
            // We don't want to short-circuit this if there's a problem logging
        }

        // TODO: This doesn't need to be a switch anymore
        //       May not need to exist at all
        switch ($this->setupIssue){
            case 'database_connection':
            case 'db_migration':
                // Unrecoverable errors.
                // Show error message and exit
                http_response_code(500);
                echo "<h1>Configuration Error</h1>";
                echo "<p>" . Util::escape_html($this->setupIssue) . '-' . Util::escape_html($this->getMessage()) . "</p>";
                exit;
        }
    }


}