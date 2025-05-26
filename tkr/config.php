<?php
$dbLocation = __DIR__ . '/db/tkr.sqlite';
$tickLocation = __DIR__ . '/ticks';
$basePath = '/tkr';

try {
    $pdo = new PDO("sqlite:$dbLocation");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
