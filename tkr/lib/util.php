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
