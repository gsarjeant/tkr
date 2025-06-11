<?php /** @var ConfigModel $config */ ?>
<?php /** @var Date $tickTime */ ?>
<?php /** @var string $tick */ ?>
<!DOCTYPE html>
<html lang="en">
    <head>
<?php include TEMPLATES_DIR . '/partials/head.php'?>
    </head>
    <body>
<?php include TEMPLATES_DIR . '/partials/navbar.php'?>
<!DOCTYPE html>
        <h1>Tick from <?= $tickTime->format('Y-m-d H:i:s'); ?></h1>
        <p><?= $tick ?></p>
    </body>
</html>