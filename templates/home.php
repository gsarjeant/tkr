<?php /** @var bool $isLoggedIn */ ?>
<?php /** @var Config $config */ ?>
<?php /** @var User $user */ ?>
<?php /** @var string $tickList */ ?>

<!DOCTYPE html>
<html>
    <head>
        <title><?= $config->siteTitle ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?= htmlspecialchars($config->basePath) ?>/css/tkr.css?v=<?= time() ?>">
    </head>
    <body>
        <div class="home-navbar">
            <a href="<?= $config->basePath ?>rss">rss</a>
            <a href="<?= $config->basePath ?>atom">atom</a>
<?php if (!$isLoggedIn): ?>
            <a href="<?= $config->basePath ?>login.php">login</a>
<?php else: ?>
            <a href="<?= $config->basePath ?>admin.php">admin</a>
            <a href="<?= $config->basePath ?>logout.php">logout</a>
<?php endif; ?>
        </div>
        <div class="home-container">
            <section id="sidebar" class="home-sidebar">
                <div class="home-header">
                    <h2>Hi, I'm <?= $user->displayName ?></h2>
                </div>
                <p><?= $user->about ?></p>
                <p>Website: <?= escape_and_linkify($user->website) ?></p>
                <div class="profile-row">
                    <div class="mood-bar">
                        <span>Current mood: <?= $user->mood ?></span>
<?php if ($isLoggedIn): ?>
                        <a href="<?= $config->basePath ?>set_mood.php">Change</a>
<?php endif; ?>
                    </div>
                </div>
<?php if ($isLoggedIn): ?>
                <hr/>
                <div class="profile-row">
                    <form class="tick-form" action="save_tick.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <textarea name="tick" placeholder="What's ticking?" rows="3"></textarea>
                        <button type="submit">Tick</button>
                    </form>
                </div>
<?php endif; ?>
            </section>
            <?php echo $tickList ?>
        </div>
    </body>
</html>
