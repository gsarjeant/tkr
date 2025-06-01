<?php

function escape_and_linkify(string $text): string {
    // escape dangerous characters, but preserve quotes
    $safe = htmlspecialchars($text, ENT_NOQUOTES | ENT_HTML5, 'UTF-8');

    // convert URLs to links
    $safe = preg_replace_callback(
        '~(https?://[^\s<>"\'()]+)~i',
        fn($matches) => '<a href="' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . $matches[1] . '</a>',
        $safe
    );

    return $safe;
}

// For relative time display, compare the stored time to the current time
// and display it as "X second/minutes/hours/days etc. "ago
function relative_time(string $tickTime): string {
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
