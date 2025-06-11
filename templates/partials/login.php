<?php /** @var ConfigModel $config */ ?>
<?php /** @var string $csrf_token */ ?>
<?php /** @var string $error */ ?>
    <h2>Login</h2>
<?php if ($error): ?>
    <p style="color:red"><?=  htmlspecialchars($error) ?></p>
<?php endif; ?>
    <form method="post" action="<?= $config->basePath ?>login">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <label>Username: <input type="text" name="username" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit" class="submit-btn">Login</button>
    </form>
