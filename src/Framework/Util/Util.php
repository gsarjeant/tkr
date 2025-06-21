<?php
class Util {
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
                $escaped_url = rtrim($matches[1], '.,!?;:)]}>');
                $clean_url = html_entity_decode($escaped_url, ENT_QUOTES, 'UTF-8');

                return '<a tabindex="0" href="' . $clean_url . '"' . $link_attrs . '>' . $escaped_url . '</a>';
            },
            $text
        );
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