<?php
require_once __DIR__ . '/../bootstrap.php';

confirm_setup();

require LIB_ROOT . '/config.php';
require LIB_ROOT . '/session.php';
require LIB_ROOT . '/ticks.php';

$config = Config::load();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = $config->itemsPerPage;
$offset = ($page - 1) * $limit;

$ticks = iterator_to_array(stream_ticks($limit, $offset));
?>
<!DOCTYPE html>
<html>
    <head>
        <title><?= $config->siteTitle ?></title>
        <style>
            body { font-family: sans-serif; margin: 2em; }
            .tick { margin-bottom: 1em; }
            .ticktime { color: gray; font-size: 0.9em; }
            .ticktext {color: black; font-size: 1.0em; }
            .pagination a { margin: 0 5px; text-decoration: none; }
        </style>
    </head>
    <body>
        <h2><?= $config->siteDescription ?></h2>

<?php foreach ($ticks as $tick): ?>
        <div class="tick">
            <span class="ticktime"><?= htmlspecialchars($tick['timestamp']) ?></span>
            <span class="ticktext"><?= escape_and_linkify_tick($tick['tick']) ?></span>
        </div>
<?php endforeach; ?>
  
        <div class="pagination">

<?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; Newer</a>
<?php endif; ?>

<?php if (count($ticks) === $limit): ?>
            <a href="?page=<?= $page + 1 ?>">Older &raquo;</a>
<?php endif; ?>
        </div>
        <div>
<?php if (!$isLoggedIn): ?>
         <p><a href="<?= $config->basePath ?>login.php">Login</a></p>
<?php else: ?>
            <form action="save_tick.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <label for="tick">What's ticking?</label>
                <input name="tick" id="tick" type="text">

                <button type="submit">Tick</button>
            </form>
            <p><a href="<?= $config->basePath ?>logout.php">Logout</a> <?= htmlspecialchars($_SESSION['username']) ?> </p>
<?php endif; ?>
        </div>
</body>
</html>
