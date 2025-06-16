<?php

class Session {
    // These can all just be static functions
    // Since they're essentially just manipulating the
    // global $_SESSION associative array
    public static function start(): void{
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function generateCsrfToken(bool $regenerate = false): void{
        if (!isset($_SESSION['csrf_token']) || $regenerate) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function getCsrfToken(): string{
        return $_SESSION['csrf_token'];
    }

    public static function isValidCsrfToken($token): bool{
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    // A session is valid if the user is logged in and has a valid csrf token
    // Test this before processing POST requests
    public static function isValid(string $token): bool {
        return self::isLoggedIn() && self::isValidCsrfToken($token);
    }

    // valid types are:
    // - success
    // - error
    // - info
    // - warning
    public static function setFlashMessage(string $type, string $message): void {
        if (!isset($_SESSION['flash'][$type])){
            $_SESSION['flash'][$type] = [];
        }
        $_SESSION['flash'][$type][] = $message;
    }

    public static function getFlashMessages(): ?array {
        if (isset($_SESSION['flash'])) {
            $messages = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $messages;
        }
    }

    public static function hasFlashMessages(): bool {
        return isset($_SESSION['flash']) && !empty($_SESSION['flash']);
    }

    public static function end(): void {
        $_SESSION = [];
        session_destroy();
    }
}