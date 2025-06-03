<?php /** @var Config $config */ ?>
<?php /** @var User $user */ ?>

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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
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