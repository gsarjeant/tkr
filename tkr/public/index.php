<?php
require_once __DIR__ . '/../bootstrap.php';

confirm_setup();

require_once CLASSES_DIR . '/Config.php';
require_once CLASSES_DIR . '/User.php';
require LIB_DIR . '/session.php';
require LIB_DIR . '/ticks.php';
require LIB_DIR . '/util.php';

$config = Config::load();
// I can get away with this before login because there's only one user.
$user = User::load();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = $config->itemsPerPage;
$offset = ($page - 1) * $limit;

$ticks = iterator_to_array(stream_ticks($limit, $offset));
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= $config->siteTitle ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?= htmlspecialchars($config->basePath) ?>css/tkr.css?v=<?= time() ?>">
    </head>
    <body>
        <div class="home-navbar">
            <a href="<?= $config->basePath ?>rss">rss</a>
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
            <section id="ticks" class="home-ticks">
                <div class="home-ticks-header">
                    <h2><?= $config->siteDescription ?></h2>
                </div>
                <div class="home-ticks-list">
<?php foreach ($ticks as $tick): ?>
                    <article class="tick">
                        <div class="tick-time"><?= htmlspecialchars(relative_time($tick['timestamp'])) ?></div>
                        <span class="tick-text"><?= escape_and_linkify($tick['tick']) ?></span>
                    </article>
<?php endforeach; ?>
                </div>
                <div class="home-ticks-pagination">
<?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">&laquo; Newer</a>
<?php endif; ?>
<?php if (count($ticks) === $limit): ?>
                    <a href="?page=<?= $page + 1 ?>">Older &raquo;</a>
<?php endif; ?>
                </div>
            </section>
        </div>
    </body>
</html>
