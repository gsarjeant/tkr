<?php
require_once __DIR__ . '/../bootstrap.php';

confirm_setup();

require_once CLASSES_DIR . '/Config.php';
require LIB_DIR . '/session.php';

if (!$isLoggedIn){
    header('Location: ' . $config->basePath . 'login.php');
}

require CLASSES_DIR . '/User.php';

$config = Config::load();
$user = User::load();

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // User profile
    $username        = trim($_POST['username'] ?? '');
    $displayName     = trim($_POST['display_name'] ?? '');
    $about           = trim($_POST['about'] ?? '');
    $website         = trim($_POST['website'] ?? '');

    // Site settings
    $siteTitle       = trim($_POST['site_title']) ?? '';
    $siteDescription = trim($_POST['site_description']) ?? '';
    $basePath        = trim($_POST['base_path'] ?? '/');
    $itemsPerPage    = (int) ($_POST['items_per_page'] ?? 25);
    // Password
    // TODO - Make sure I really shouldn't trim these
    //        (I'm assuming there may be people who end their password with a space character)
    $password                = $_POST['password'] ?? '';
    $confirmPassword         = $_POST['confirm_password'] ?? '';

    // Validate user profile
    if (!$username) {
        $errors[] = "Username is required.";
    }
    if (!$displayName) {
        $errors[] = "Display name is required.";
    }
    // Make sure the website looks like a URL and starts with a protocol
    if ($website) {
        if (!filter_var($website, FILTER_VALIDATE_URL)) {
            $errors[] = "Please enter a valid URL (including http:// or https://).";
        } elseif (!preg_match('/^https?:\/\//i', $website)) {
            $errors[] = "URL must start with http:// or https://.";
        }
    }


    // Validate site settings
    if (!$siteTitle) {
        $errors[] = "Site title is required.";
    }
    if (!preg_match('#^/[^?<>:"|\\*]*$#', $basePath)) {
        $errors[] = "Base path must look like a valid URL path (e.g. / or /tkr/).";
    }
    if ($itemsPerPage < 1 || $itemsPerPage > 50) {
        $errors[] = "Items per page must be a number between 1 and 50.";
    }

    // If a password was sent, make sure it matches the confirmation
    if ($password && !($password = $confirmPassword)){
        $errors[] = "Passwords do not match";
    }

    // TODO: Actually handle errors
    if (empty($errors)) {
        // Update site settings
        $config->siteTitle = $siteTitle;
        $config->siteDescription = $siteDescription;
        $config->basePath = $basePath;
        $config->itemsPerPage = $itemsPerPage;

        // Save site settings and reload config from database
        $config = $config->save();

        // Update user profile
        $user->username = $username;
        $user->displayName = $displayName;
        $user->about = $about;
        $user->website = $website;

        // Save user profile and reload user from database
        $user = $user->save();

        // Update the password if one was sent
        if($password){
            $user->set_password($password);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?= $config->siteTitle ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?= htmlspecialchars($config->basePath) ?>css/tkr.css">
    </head>
    <body>
        <h1>Admin</h1>
        <div><a href="<?= $config->basePath ?>">Back to home</a></div>
        <div>
            <form method="post">
                <fieldset id="user_settings" class="admin-settings-group">
                    <legend>User settings</legend>
                    <label class="admin-option">Username: <input type="text" name="username" value="<?= $user->username ?>" required></label><br>
                    <label class="admin-option">Display name: <input type="text" name="display_name" value="<?= $user->displayName ?>" required></label><br>
                    <label class="admin-option">About: <input type="text" name="about" value="<?= $user->about ?>"></label><br>
                    <label class="admin-option">Website: <input type="text" name="website" value="<?= $user->website ?>"></label><br>
                </fieldset>
                <fieldset id="site_settings" class="admin-settings-group">
                    <legend>Site settings</legend>
                    <label class="admin-option">Title: <input type="text" name="site_title" value="<?= $config->siteTitle ?>" required></label><br>
                    <label class="admin-option">Description: <input type="text" name="site_description" value="<?= $config->siteDescription ?>"></label><br>
                    <label class="admin-option">Base path: <input type="text" name="base_path" value="<?= $config->basePath ?>" required></label><br>
                    <label class="admin-option">Items per page (max 50): <input type="number" name="items_per_page" value="<?= $config->itemsPerPage ?>" min="1" max="50" required></label><br>
                </fieldset>
                <fieldset id="change_password" class="admin-settings-group">
                    <legend>Change password</legend>
                    <label class="admin-option">New password: <input type="password" name="password"></label><br>
                    <label class="admin-option">Confirm new password: <input type="password" name="confirm_password"></label><br>
                </fieldset>
                <button type="submit">Save Settings</button>
            </form>
        </div>
    </body>
</html>