<?php
define('APP_ROOT', realpath(__DIR__ . '/../'));

require APP_ROOT . '/config.php';
require APP_ROOT . '/session.php';

$_SESSION = [];
session_destroy();

header('Location: ' . $basePath . '/');
exit;