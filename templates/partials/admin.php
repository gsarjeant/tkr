<?php /** @var ConfigModel $config */ ?>
<?php /** @var UserModel $user */ ?>
<?php /** @var isSetup bool */ ?>
        <h1><?php if ($isSetup): ?>Setup<?php else: ?>Admin<?php endif; ?></h1>
        <main>
            <form
                action="<?php echo $config->basePath . ($isSetup ? 'setup' : 'admin') ?>"
                method="post">
                <input type="hidden" name="csrf_token" value="<?= Util::escape_html($_SESSION['csrf_token']) ?>">
                <fieldset>
                    <legend>User settings</legend>
                    <div class="fieldset-items">
                        <label for="username">Username <span class=required>*</span></label>
                        <input type="text"
                            id="username"
                            name="username"
                            value="<?= Util::escape_html($user->username) ?>"
                            required>
                        <label for="display_name">Display name <span class=required>*</span></label>
                        <input type="text"
                               id="display_name"
                               name="display_name"
                               value="<?= Util::escape_html($user->displayName) ?>"
                               required>
                        <label for="about">About </label>
                        <input type="text"
                            id="about"
                            name="about"
                            value="<?= Util::escape_html($user->about) ?>">
                        <label for="website">Website </label>
                        <input type="text"
                            id="website"
                            name="website"
                            value="<?= Util::escape_html($user->website) ?>">
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Site settings</legend>
                    <div class="fieldset-items">
                        <label for="site_title">Title <span class=required>*</span></label>
                        <input type="text"
                            id="site_title"
                            name="site_title"
                            value="<?= Util::escape_html($config->siteTitle) ?>"
                            required>
                        <label for="site_description">Description <span class=required>*</span></label>
                        <input type="text"
                            id="site_description"
                            name="site_description"
                            value="<?= Util::escape_html($config->siteDescription) ?>">
                        <label for="base_url">Base URL <span class=required>*</span></label>
                        <input type="text"
                            id="base_url"
                            name="base_url"
                            value="<?= Util::escape_html($config->baseUrl) ?>"
                            required>
                        <label for="base_path">Base path <span class=required>*</span></label>
                        <input type="text"
                            id="base_path"
                            name="base_path"
                            value="<?= Util::escape_html($config->basePath) ?>"
                            required>
                        <label for="items_per_page">Items per page (max 50) <span class=required>*</span></label>
                        <input type="number"
                            id="items_per_page"
                            name="items_per_page"
                            value="<?= $config->itemsPerPage ?>" min="1" max="50"
                            required>
                        <label for="strict_accessibility">Strict accessibility</label>
                        <input type="checkbox"
                               id="strict_accessibility"
                               name="strict_accessibility"
                               value="1"
                               <?php if ($config->strictAccessibility): ?> checked <?php endif; ?>>
                        <label for="show_tick_mood">Show tick mood</label>
                        <input type="checkbox"
                               id="show_tick_mood"
                               name="show_tick_mood"
                               value="1"
                               <?php if ($config->showTickMood): ?> checked <?php endif; ?>>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Change password</legend>
                    <div class="fieldset-items">
                        <label for="password">New password
                            <?php if($isSetup): ?><span class=required>*</span><?php endif; ?>
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               <?php if($isSetup): ?>required <?php endif; ?>
                        >
                        <label for="confirm_password">Confirm new password
                            <?php if($isSetup): ?><span class=required>*</span><?php endif; ?>
                        </label>
                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               <?php if($isSetup): ?>required <?php endif; ?>
                        >
                    </div>
                </fieldset>
                <button type="submit" class="submit-btn">Save Settings</button>
            </form>
        </main>