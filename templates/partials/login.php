<?php /** @var ConfigModel $config */ ?>
<?php /** @var string $csrf_token */ ?>
<?php /** @var string $error */ ?>
    <h2>Login</h2>
<?php if ($error): ?>
    <p style="color:red"><?=  htmlspecialchars($error) ?></p>
<?php endif; ?>
    <form method="post" action="<?= $config->basePath ?>login">
        <div class="fieldset-items">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required></label>
            <div></div>
            <button type="submit" class="submit-btn">Login</button>
        </div>
    </form>
