<?php
require_once __DIR__ . '/../bootstrap.php';

require LIB_ROOT . '/config.php';
require LIB_ROOT . '/session.php';

$config = Config::load();
$_SESSION = [];
session_destroy();

header('Location: ' . $config->basePath);
exit;