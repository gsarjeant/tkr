<?php /** @var Config $config */ ?>
<?php /** @var User $user */ ?>
<!DOCTYPE html>
<html>
    <head>
<?php include TEMPLATES_DIR . '/partials/head.php'?>
    </head>
    <body>
<?php include TEMPLATES_DIR . '/partials/navbar.php'?>
<html lang="en">
        <h1>Admin</h1>
        <div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <fieldset>
                    <legend>User settings</legend>
                    <div class="fieldset-items">
                        <label>Username <span class=required></span></label>
                        <input type="text"
                            name="username"
                            value="<?= $user->username ?>"
                            required>
                        <label>Display name <span class=required></span></label>
                            <input type="text" 
                                name="display_name"
                                value="<?= $user->displayName ?>"
                                required>
                        <label>About </label>
                        <input type="text"
                            name="about"
                            value="<?= $user->about ?>">
                        <label>Website </label>
                        <input type="text"
                            name="website"
                            value="<?= $user->website ?>">
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Site settings</legend>
                    <div class="fieldset-items">
                        <label>Title <span class=required></span></label>
                        <input type="text"
                            name="site_title"
                            value="<?= $config->siteTitle ?>" 
                            required>
                        <label>Description <span class=required></span></label>
                        <input type="text"
                            name="site_description"
                            value="<?= $config->siteDescription ?>">
                        <label>Base URL </label>
                        <input type="text"
                            name="base_url"
                            value="<?= $config->baseUrl ?>"
                            required>
                        <label>Base path <span class=required></span></label> 
                        <input type="text"
                            name="base_path"
                            value="<?= $config->basePath ?>"
                            required>
                        <label>Items per page (max 50) <span class=required></span></label>
                        <input type="number"
                            name="items_per_page"
                            value="<?= $config->itemsPerPage ?>" min="1" max="50"
                            required>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Change password</legend>
                    <div class="fieldset-items">
                        <label>New password: </label>
                        <input type="password" name="password">
                        <label>Confirm new password: </label>
                        <input type="password" name="confirm_password">
                    </div>
                </fieldset>
                <button type="submit" class="submit-btn">Save Settings</button>
            </form>
        </div>
    </body>
</html>