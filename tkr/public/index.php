<?php
require_once __DIR__ . '/../bootstrap.php';

confirm_setup();

require_once LIB_ROOT . '/config.php';
require_once LIB_ROOT . '/user.php';
require LIB_ROOT . '/session.php';
require LIB_ROOT . '/ticks.php';
require LIB_ROOT . '/util.php';

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
        <h2><?= $config->siteDescription ?></h2>

        <div class="flex-container">
                <div class="profile">
<?php if ($isLoggedIn): ?>
                        <form class="tickform" action="save_tick.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <label for="tick">What's ticking?</label>
                                <input name="tick" id="tick" type="text">
                                <button type="submit">Tick</button>
                        </form>
<?php endif; ?>
                        <p>Hi, I'm <?= $user->displayName ?></p>
                        <p><?= $user->about ?></p>
                        <p>Website: <?= escape_and_linkify($user->website) ?></p>
                        <p>Current mood: <?= $user->mood ?></p> 
<?php if ($isLoggedIn): ?>
                        <a href="<?= $config->basePath ?>set_mood.php">Set your mood</a></p>
                        <p><a href="<?= $config->basePath . '/admin.php' ?>">Admin</a></p>
                        <p><a href="<?= $config->basePath ?>logout.php">Logout</a> <?= htmlspecialchars($user->username) ?> </p>
<?php else: ?>
                        <p><a href="<?= $config->basePath ?>login.php">Login</a></p>
<?php endif; ?>
                </div>
                <div class="ticks">
<?php foreach ($ticks as $tick): ?>
                        <div class="tick">
                            <span class="ticktime"><?= htmlspecialchars($tick['timestamp']) ?></span>
                            <span class="ticktext"><?= escape_and_linkify($tick['tick']) ?></span>
                        </div>
<?php endforeach; ?>
                </div>
        <div class="pagination">

<?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; Newer</a>
<?php endif; ?>

<?php if (count($ticks) === $limit): ?>
            <a href="?page=<?= $page + 1 ?>">Older &raquo;</a>
<?php endif; ?>
        </div>
</body>
</html>
