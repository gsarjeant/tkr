<?php
require_once __DIR__ . '/../bootstrap.php';

require LIB_ROOT . '/config.php';
require LIB_ROOT . '/session.php';
require LIB_ROOT . '/ticks.php';

confirm_setup();

// ticks must be sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['tick'])) {
    // ensure that the session is valid before proceeding
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
} else {
    // just go back to the index if it's not a POST
    header('Location: index.php');
    exit;
}

// get the config
$config = Config::load();

// save the tick
save_tick($_POST['tick']);

// go back to the index and show the latest tick
header('Location: ' . $config->basePath);
exit;