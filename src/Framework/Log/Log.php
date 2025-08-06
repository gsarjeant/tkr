<?php
declare(strict_types=1);

class Log {
    const LEVELS = [
        'DEBUG' => 1,
        'INFO' => 2,
        'WARNING' => 3,
        'ERROR' => 4
    ];

    private static $logFile;
    private static $maxLines = 1000;
    private static $maxFiles = 5;
    private static $routeContext = '';

    public static function init(?string $logFile = null) {
        self::$logFile = $logFile ?? STORAGE_DIR . '/logs/tkr.log';

        // Ensure log directory exists
        // (should be handled by Prerequisites, but doesn't hurt)
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            try {
                mkdir($logDir, 0770, true);
            } catch (Exception $e) {
                // Fall back to error_log if we can't create log directory
                error_log("Failed to create log directory {$logDir}: " . $e->getMessage());
            }
        }
    }

    public static function setRouteContext(string $route): void {
        self::$routeContext = $route ? "[$route]" : '';
    }

    public static function debug($message) {
        self::write('DEBUG', $message);
    }

    public static function info($message) {
        self::write('INFO', $message);
    }

    public static function error($message) {
        self::write('ERROR', $message);
    }

    public static function warning($message) {
        self::write('WARNING', $message);
    }

    private static function write($level, $message) {
        global $app;
        $logLevel = $app['config']->logLevel ?? self::LEVELS['INFO'];

        // Only log messages if they're at or above the configured log level.
        if (self::LEVELS[$level] < $logLevel){
            return;
        }

        if (!self::$logFile) {
            self::init();
        }

        $timestamp = date('Y-m-d H:i:s');
        $context = self::$routeContext ? ' ' . self::$routeContext : '';
        $logEntry = "[{$timestamp}] {$level}: " . Util::getClientIp() . "{$context} - {$message}\n";

        // Rotate if we're at the max file size (1000 lines)
        try {
            if (file_exists(self::$logFile)) {
                $lineCount = count(file(self::$logFile));
                if ($lineCount >= self::$maxLines) {
                    self::rotate();
                    Log::info("Log rotated at {$timestamp}");
                }
            }

            file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Fall back to error_log if file operations fail
            error_log("Log write failed: " . $e->getMessage() . " - Original message: " . trim($logEntry));
        }
    }

    private static function rotate() {
        try {
            // Rotate existing history files: tkr.4.log -> tkr.5.log, etc.
            for ($i = self::$maxFiles - 1; $i >= 1; $i--) {
                $oldFile = self::$logFile . '.' . $i;
                $newFile = self::$logFile . '.' . ($i + 1);

                if (file_exists($oldFile)) {
                    if ($i == self::$maxFiles - 1) {
                        unlink($oldFile); // Delete oldest log if we already have 5 files of history
                    } else {
                        rename($oldFile, $newFile); // Bump the file number up by one
                    }
                }
            }

            // Move current active log to .1
            if (file_exists(self::$logFile)) {
                rename(self::$logFile, self::$logFile . '.1');
            }
        } catch (Exception $e) {
            // Log rotation failure - not critical, just continue
            error_log("Log rotation failed: " . $e->getMessage());
        }
    }
}