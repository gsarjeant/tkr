<?php /** @var Config $config */ ?>
<?php /** @var string $moodPicker */ ?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?= $config->siteTitle ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="<?= htmlspecialchars($config->basePath) ?>css/tkr.css">
    </head>
    <body>
        <h2>How are you feeling?</h2>

<?php echo $moodPicker; ?>

        <a class="back-link" href="<?= htmlspecialchars($config->basePath) ?>">Back to home</a>
    </body>
</html>