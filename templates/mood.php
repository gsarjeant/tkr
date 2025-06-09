<?php /** @var ConfigModel $config */ ?>
<?php /** @var string $moodPicker */ ?>
<!DOCTYPE html>
<html>
    <head>
<?php include TEMPLATES_DIR . '/partials/head.php'?>
    </head>
    <body>
<?php include TEMPLATES_DIR . '/partials/navbar.php'?>
        <h2>How are you feeling?</h2>
<?php echo $moodPicker; ?>
    </body>
</html>