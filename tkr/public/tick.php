<?php
require '/app/Config.php';

$path = $_GET['path'] ?? '';
$parts = explode('/', $path);

if (count($parts) !== 6) {
    http_response_code(400);
    echo "Invalid tick path.";
    exit;
}

[$y, $m, $d, $H, $i, $s] = $parts;
$timestamp = "$H:$i:$s";
$file = "$tickLocation/$y/$m/$d.txt";

if (!file_exists($file)) {
    http_response_code(404);
    echo "Tick not found.";
    exit;
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (str_starts_with($line, $timestamp)) {
        echo "<h1>Tick from $timestamp on $y-$m-$d</h1>";
        echo "<p>" . htmlspecialchars(explode('|', $line)[1]) . "</p>";
        exit;
    }
}

http_response_code(404);
echo "Tick not found.";
