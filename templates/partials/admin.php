<?php /** @var ConfigModel $config */ ?>
<?php /** @var UserModel $user */ ?>
<?php /** @var isSetup bool */ ?>
        <h1><?php if ($isSetup): ?>Setup<?php else: ?>Admin<?php endif; ?></h1>
        <div>
            <form
                action="<?php echo $config->basePath . ($isSetup ? 'setup' : 'admin') ?>"  
                method="post">
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