<?php
class Util {
    public static function escape_and_linkify(string $text, int $flags = ENT_NOQUOTES | ENT_HTML5, bool $new_window = true ): string {
        // escape dangerous characters, but preserve quotes
        $safe = htmlspecialchars($text, $flags, 'UTF-8');

        $link_attrs = $new_window ? ' target="_blank" rel="noopener noreferrer"' : '';

        // convert URLs to links
        $safe = preg_replace_callback(
            '~(https?://[^\s<>"\'()]+)~i',
            fn($matches) => '<a href="' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '"' . $link_attrs . '>' . $matches[1] . '</a>',
            $safe
        );

        return $safe;
    }

    // For relative time display, compare the stored time to the current time
    // and display it as "X seconds/minutes/hours/days etc." ago
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

    public static function tick_time_to_tick_path($tickTime){
        [$date, $time] = explode(' ', $tickTime);
        $dateParts = explode('-', $date);
        $timeParts = explode(':', $time);

        [$year, $month, $day] = $dateParts;
        [$hour, $minute, $second] = $timeParts;

        return "$year/$month/$day/$hour/$minute/$second";        
    }
}