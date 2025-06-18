<?php /** @var ConfigModel $config */ ?>
<?php /** @var UserModel $user */ ?>
<?php /** @var isSetup bool */ ?>
        <h1><?php if ($isSetup): ?>Setup<?php else: ?>Admin<?php endif; ?></h1>
        <div>
            <form
                action="<?php echo $config->basePath . ($isSetup ? 'setup' : 'admin') ?>"
                method="post">
                <input type="hidden" name="csrf_token" value="<?= Util::escape_html($_SESSION['csrf_token']) ?>">
                <fieldset>
                    <legend>User settings</legend>
                    <div class="fieldset-items">
                        <label>Username <span class=required>*</span></label>
                        <input type="text"
                            name="username"
                            value="<?= Util::escape_html($user->username) ?>"
                            required>
                        <label>Display name <span class=required>*</span></label>
                        <input type="text"
                               name="display_name"
                               value="<?= Util::escape_html($user->displayName) ?>"
                               required>
                        <label>About </label>
                        <input type="text"
                            name="about"
                            value="<?= Util::escape_html($user->about) ?>">
                        <label>Website </label>
                        <input type="text"
                            name="website"
                            value="<?= Util::escape_html($user->website) ?>">
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Site settings</legend>
                    <div class="fieldset-items">
                        <label>Title <span class=required>*</span></label>
                        <input type="text"
                            name="site_title"
                            value="<?= Util::escape_html($config->siteTitle) ?>"
                            required>
                        <label>Description <span class=required>*</span></label>
                        <input type="text"
                            name="site_description"
                            value="<?= Util::escape_html($config->siteDescription) ?>">
                        <label>Base URL <span class=required>*</span></label>
                        <input type="text"
                            name="base_url"
                            value="<?= Util::escape_html($config->baseUrl) ?>"
                            required>
                        <label>Base path <span class=required>*</span></label>
                        <input type="text"
                            name="base_path"
                            value="<?= Util::escape_html($config->basePath) ?>"
                            required>
                        <label>Items per page (max 50) <span class=required>*</span></label>
                        <input type="number"
                            name="items_per_page"
                            value="<?= $config->itemsPerPage ?>" min="1" max="50"
                            required>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Change password</legend>
                    <div class="fieldset-items">
                        <label>New password
                            <?php if($isSetup): ?><span class=required>*</span><?php endif; ?>
                        </label>
                        <input type="password"
                               name="password"
                               <?php if($isSetup): ?>required <?php endif; ?>
                        >
                        <label>Confirm new password
                            <?php if($isSetup): ?><span class=required>*</span><?php endif; ?>
                        </label>
                        <input type="password"
                               name="confirm_password"
                               <?php if($isSetup): ?>required <?php endif; ?>
                        >
                    </div>
                </fieldset>
                <button type="submit" class="submit-btn">Save Settings</button>
            </form>
        </div>