<?php
class Util {
    public static function getClientIp() {
        return $_SERVER['HTTP_CLIENT_IP'] ??
               $_SERVER['HTTP_X_FORWARDED_FOR'] ??
               $_SERVER['HTTP_X_REAL_IP'] ??
               $_SERVER['REMOTE_ADDR'] ??
               'unknown';
    }

    public static function escape_html(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public static function escape_xml(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // Convert URLs in text to links (anchor tags)
    // NOTE: This function expects pre-escaped text.
    //       It will unescape URLs if there are any.
    public static function linkify(string $text, bool $new_window = true): string {
        $link_attrs = $new_window ? ' target="_blank" rel="noopener noreferrer"' : '';

        return preg_replace_callback(
            '~(https?://[^\s<>"\'()]+)~i',
            function($matches) use ($link_attrs) {
                global $app;
                $escaped_url = rtrim($matches[1], '.,!?;:)]}>');
                $clean_url = html_entity_decode($escaped_url, ENT_QUOTES, 'UTF-8');
                $tabIndex = $app['config']->strictAccessibility ? ' tabindex="0"' : '';

                return '<a' . $tabIndex . ' href="' . $clean_url . '"' . $link_attrs . '>' . $escaped_url . '</a>';
            },
            $text
        );
    }

    // For relative time display, compare the stored time to the current time
    // and display it as "X seconds/minutes/hours/days etc." ago
    //
    // TODO: Convert to either accepting a DateTime or use DateTime->fromFormat()
    public static function relative_time(string $tickTime): string {
        $datetime = new DateTime($tickTime);
        $now = new DateTime('now', $datetime->getTimezone());
        $diff = $now->diff($datetime);

        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        }
        if ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        }
        if ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        }
        if ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return $diff->s . ' second' . ($diff->s != 1 ? 's' : '') . ' ago';
    }

    public static function buildUrl(string $baseUrl, string $basePath, string $path = ''): string {
        // Normalize baseUrl (remove trailing slash)
        $baseUrl = rtrim($baseUrl, '/');

        // Normalize basePath (ensure leading slash, remove trailing slash unless it's just '/')
        if ($basePath === '' || $basePath === '/') {
            $basePath = '/';
        } else {
            $basePath = '/' . trim($basePath, '/') . '/';
        }

        // Normalize path (remove leading slash if present)
        $path = ltrim($path, '/');

        return $baseUrl . $basePath . $path;
    }

    public static function buildRelativeUrl(string $basePath, string $path = ''): string {
        // Ensure basePath starts with / for relative URLs
        $basePath = '/' . ltrim($basePath, '/');

        // Remove trailing slash unless it's just '/'
        if ($basePath !== '/') {
            $basePath = rtrim($basePath, '/');
        }

        // Add path
        $path = ltrim($path, '/');

        if ($path === '') {
            return $basePath;
        }

        // If basePath is root, don't add extra slash
        if ($basePath === '/') {
            return '/' . $path;
        }

        return $basePath . '/' . $path;
    }

    /**
     * Auto-detect base URL and path from HTTP request headers
     * Returns array with baseUrl, basePath, and fullUrl
     */
    public static function getAutodetectedUrl(): array {
        // Detect base URL
        $baseUrl = ($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https://' : 'http://';
        $baseUrl .= $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Don't include standard ports in URL
        $port = $_SERVER['SERVER_PORT'] ?? null;
        if ($port && $port != 80 && $port != 443) {
            $baseUrl .= ':' . $port;
        }
        
        // Detect base path from script location
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $basePath = dirname($scriptName);
        
        if ($basePath === '/' || $basePath === '.' || $basePath === '') {
            $basePath = '/';
        } else {
            $basePath = '/' . trim($basePath, '/') . '/';
        }
        
        // Construct full URL
        $fullUrl = $baseUrl;
        if ($basePath !== '/') {
            $fullUrl .= ltrim($basePath, '/');
        }
        
        return [
            'baseUrl' => $baseUrl,
            'basePath' => $basePath,
            'fullUrl' => rtrim($fullUrl, '/')
        ];
    }
}