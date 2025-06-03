<?php

$_SESSION = [];
session_destroy();

$config = Config::load();
header('Location: ' . $config->basePath);
exit;