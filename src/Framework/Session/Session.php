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

    public static function validateCsrfToken($token): bool{
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function getCsrfToken(): string{
        return $_SESSION['csrf_token'];
    }

    public static function isLoggedIn(): bool {
        //echo "User ID set: ". isset($_SESSION['user_id']). "<br/>";
        //exit;
        return isset($_SESSION['user_id']);
    }

    public static function end(): void {
        $_SESSION = [];
        session_destroy();
    }
}