<?php /** @var Config $config */ ?>
<?php /** @var string $csrfToken */ ?>
<?php /** @var string $error */ ?>

<!DOCTYPE html>
<html>
    <head>
        <title><?= $config->siteTitle ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?= htmlspecialchars($config->basePath) ?>css/tkr.css?v=<?= time() ?>">
    </head>
    <body>
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
