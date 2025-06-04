<?php /** @var Config $config */ ?>
<?php /** @var string $csrf_token */ ?>
<?php /** @var string $error */ ?>
<!DOCTYPE html>
<html>
    <head>
<?php include TEMPLATES_DIR . '/partials/head.php'?>
    </head>
    <body>
<?php include TEMPLATES_DIR . '/partials/navbar.php'?>
    <h2>Login</h2>
<?php if ($error): ?>
    <p style="color:red"><?=  htmlspecialchars($error) ?></p>
<?php endif; ?>
    <form method="post" action="<?= $config->basePath ?>login">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <label>Username: <input type="text" name="username" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Login</button>
    </form>
    </body>
</html>
