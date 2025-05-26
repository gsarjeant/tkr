<?php
define('APP_ROOT', realpath(__DIR__ . '/../'));
define('ITEMS_PER_PAGE', 25);

require APP_ROOT . '/config.php';
require APP_ROOT . '/session.php';
require_once APP_ROOT . '/stream_ticks.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

$ticks = iterator_to_array(stream_ticks($tickLocation, $limit, $offset));
?>
<!DOCTYPE html>
<html>
    <head>
        <title>My ticker</title>
        <style>
            body { font-family: sans-serif; margin: 2em; }
            .tick { margin-bottom: 1em; }
            .ticktime { color: gray; font-size: 0.9em; }
            .ticktext {color: black; font-size: 1.0em; }
            .pagination a { margin: 0 5px; text-decoration: none; }
        </style>
    </head>
    <body>
        <h2>Welcome! Here's what's ticking.</h2>

<?php foreach ($ticks as $tick): ?>
        <div class="tick">
            <spam class="ticktime"><?= $tick['timestamp'] ?></span>
            <span class="ticktext"><?= $tick['tick'] ?></spam>
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

<?php if (!$isLoggedIn): ?>
    <p><a href="<?= $basePath ?>/login.php">Login</a></p>
<?php else: ?>
    <form action="save_tick.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <label for="tick">What's ticking?</label>
        <input name="tick" id="tick" type="text">
  
        <button type="submit">Tick</button>
    </form>
    <p><a href="<?= $basePath ?>/logout.php">Logout</a> <?= htmlspecialchars($_SESSION['username']) ?> </p>
<?php endif; ?>
</body>
</html>
