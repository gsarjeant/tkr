<?php /** @var Config $config */ ?>
<?php /** @var Date $tickTime */ ?>
<?php /** @var string $tick */ ?>
<!DOCTYPE html>
<html>
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