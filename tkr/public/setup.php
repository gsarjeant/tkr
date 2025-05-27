<?php
require_once __DIR__ . '/../bootstrap.php';

confirm_setup();

// If we got past confirm_setup(), then setup isn't complete.
$db = get_db();

// Handle submitted form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username     = trim($_POST['username'] ?? '');
    $display_name = trim($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $site_title = trim($_POST['site_title']) ?? '';
    $site_description = trim($_POST['site_description']) ?? '';
    $base_path = trim($_POST['base_path'] ?? '/');
    $items_per_page = (int) ($_POST['items_per_page'] ?? 25);

    // Sanitize base path
    if (substr($base_path, -1) !== '/') {
        $base_path .= '/';
    }

    // Validate
    $errors = [];
    if (!$username || !$password) {
        $errors[] = "Username and password are required.";
    }
    if (!$display_name) {
        $errors[] = "Display name is required.";
    }
    if (!$site_title) {
        $errors[] = "Site title is required.";
    }
    if (!preg_match('#^/[^?<>:"|\\*]*$#', $base_path)) {
        $errors[] = "Base path must look like a valid URL path (e.g. / or /tkr/).";
    }
    if ($items_per_page < 1 || $items_per_page > 50) {
        $errors[] = "Items per page must be a number between 1 and 50.";
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO user (username, display_name, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $display_name, $hash]);

        $stmt = $db->prepare("INSERT INTO settings (id, site_title, site_description, base_path, items_per_page) VALUES (1, ?, ?, ?, ?)");
        $stmt->execute([$site_title, $site_description, $base_path, $items_per_page]);

        header("Location: index.php");
        exit;
    }
}

?>

<h1>Let’s Set Up Your tkr</h1>
<form method="post">
    <h3>User settings</h3>
    <label>Username: <input type="text" name="username" required></label><br>
    <label>Display name: <input type="text" name="display_name" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <br/><br/>
    <h3>Site settings</h3>
    <label>Title: <input type="text" name="site_title" value="My tkr" required></label><br>
    <label>Description: <input type="text" name="site_description"></label><br>
    <label>Base path: <input type="text" name="base_path" value="/" required></label><br>
    <label>Items per page (max 50): <input type="number" name="items_per_page" value="25" min="1" max="50" required></label><br>
    <br/>
    <button type="submit">Complete Setup</button>
</form>
