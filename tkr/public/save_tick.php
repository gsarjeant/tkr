<?php
define('APP_ROOT', realpath(__DIR__ . '/../'));

require APP_ROOT . '/config.php';
require APP_ROOT . '/session.php';

// ticks must be sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['tick'])) {
    // csrf check
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    $tick = htmlspecialchars($_POST['tick'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
} else {
    // just go back to the index
    header('Location: index.php');
    exit;
}

# write the tick to a new entry 
$date = new DateTime();

$year = $date->format('Y');
$month = $date->format('m');
$day = $date->format('d');
$time = $date->format('H:i:s');

// build the full path to the tick file
$dir = "$tickLocation/$year/$month";
$filename = "$dir/$day.txt";

// create the directory if it doesn't exist
if (!is_dir($dir)) {
    mkdir($dir, 0770, true);
}

// write the tick to the file (the file will be created if it doesn't exist)
$content = $time . "|" . $tick . "\n";
file_put_contents($filename, $content, FILE_APPEND);

// go back to the index and show the latest tick
header('Location: index.php');
exit;