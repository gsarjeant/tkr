<?php
require_once __DIR__ . '/../bootstrap.php';

confirm_setup();

require_once CLASSES_DIR . '/Config.php';
require LIB_DIR . '/session.php';
require LIB_DIR . '/ticks.php';
require LIB_DIR . '/util.php';


// ticks must be sent via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['tick'])) {
    // ensure that the session is valid before proceeding
    if (!validateCsrfToken($_POST['csrf_token'])) {
        // TODO: maybe redirect to login? Maybe with tick preserved?
        die('Invalid CSRF token');
    }

    // save the tick
    save_tick($_POST['tick']);
}

// get the config
$config = Config::load();

// go back to the index (will show the latest tick if one was sent)
header('Location: ' . $config->basePath);
exit;