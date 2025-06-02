<?php
#require_once __DIR__ . '/../bootstrap.php';

#confirm_setup();

#require_once CLASSES_DIR . '/Config.php';
#require LIB_DIR . '/session.php';

$config = Config::load();
$_SESSION = [];
session_destroy();

header('Location: ' . $config->basePath);
exit;