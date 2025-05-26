<?php
// TODO: Replace this whole thing with a setup.php

function prompt($prompt) {
    echo $prompt;
    return trim(fgets(STDIN));
}

function promptSilent($prompt = "Enter Password: ") {
    if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
        // Windows doesn't support shell-based hidden input
        echo "Warning: Password input not hidden on Windows.\n";
        return prompt($prompt);
    } else {
        // Use shell to disable echo for password input
        echo $prompt;
        system('stty -echo');
        $password = rtrim(fgets(STDIN), "\n");
        system('stty echo');
        echo "\n";
        return $password;
    }
}

$dbFile = __DIR__ . '/tkr.sqlite';

try {
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to DB: " . $e->getMessage() . "\n");
}

$pdo->exec("CREATE TABLE IF NOT EXISTS user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL
)");

$username = prompt("Enter username: ");
$password = promptSilent("Enter password: ");
$confirm  = promptSilent("Confirm password: ");

if ($password !== $confirm) {
    die("Error: Passwords do not match.\n");
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO user(username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $passwordHash]);
    echo "User '$username' created successfully.\n";
} catch (PDOException $e) {
    echo "Failed to create user: " . $e->getMessage() . "\n";
}
