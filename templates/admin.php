<?php /** @var ConfigModel $config */ ?>
<?php /** @var UserModel $user */ ?>
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
                    <legend>UserModel settings</legend>
                    <div class="fieldset-items">
                        <label>Username <span class=required>*</span></label>
                        <input type="text"
                            name="username"
                            value="<?= $user->username ?>"
                            required>
                        <label>Display name <span class=required>*</span></label>
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
                        <label>Title <span class=required>*</span></label>
                        <input type="text"
                            name="site_title"
                            value="<?= $config->siteTitle ?>" 
                            required>
                        <label>Description <span class=required>*</span></label>
                        <input type="text"
                            name="site_description"
                            value="<?= $config->siteDescription ?>">
                        <label>Base URL <span class=required>*</span></label>
                        <input type="text"
                            name="base_url"
                            value="<?= $config->baseUrl ?>"
                            required>
                        <label>Base path <span class=required>*</span></label> 
                        <input type="text"
                            name="base_path"
                            value="<?= $config->basePath ?>"
                            required>
                        <label>Items per page (max 50) <span class=required>*</span></label>
                        <input type="number"
                            name="items_per_page"
                            value="<?= $config->itemsPerPage ?>" min="1" max="50"
                            required>
                    </div>
                    <div class="fieldset-items">
                        <label for="setCssFile">Set CSS File</label>
                        <select id="setCssFile" name="css_file">
                            <option value="">Default</option>
                        </select>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Change password</legend>
                    <div class="fieldset-items">
                        <label>New password</label>
                        <input type="password" name="password">
                        <label>Confirm new password</label>
                        <input type="password" name="confirm_password">
                    </div>
                </fieldset>
                <fieldset>
                    <legend>CSS Upload</legend>
                    <div class="fieldset-items">
                        <form action="/upload-css" method="post" enctype="multipart/form-data">
                            <label for="uploadCssFile">Select File to Upload</label>
                            <input type="file" 
                                   id="uploadCssFile" 
                                   name="uploadCssFile" 
                                   accept=".css">
                            <div class="file-info">
                                <strong>File Requirements:</strong><br>
                                • Must be a valid CSS file (.css extension)<br>
                                • Maximum size: 2MB<br>
                                • Will be scanned for malicious content
                            </div>
                            <label for="description">Description (optional)</label>
                            <textarea id="description" 
                                      name="description" 
                                      placeholder="Describe this CSS file..."></textarea>
                            <button type="submit" class="upload-btn">Upload CSS File</button>
                        </form>
                    </div>
                </fieldset>
                <button type="submit" class="submit-btn">Save Settings</button>
            </form>
        </div>
    </body>
</html>