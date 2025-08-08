<?php /** @var SettingsModel $settings */ ?>
<?php /** @var string $csrf_token */ ?>
<?php /** @var string $error */ ?>
    <h2>Login</h2>
    <form method="post" action="<?= Util::buildRelativeUrl($settings->basePath, 'login') ?>">
        <div class="fieldset-items">
            <input type="hidden" name="csrf_token" value="<?= Util::escape_html($csrf_token) ?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required></label>
            <div></div>
            <button type="submit">Login</button>
        </div>
    </form>
