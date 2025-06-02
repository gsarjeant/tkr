<?php
#require_once __DIR__ . '/../bootstrap.php';

#confirm_setup();

#require_once CLASSES_DIR . '/Config.php';
#require LIB_DIR . '/session.php';
#require LIB_DIR . '/mood.php';


// get the config
$config = Config::load();

if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['mood'])) {
    // ensure that the session is valid before proceeding
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    // set the mood 
    save_mood($_POST['mood']);

    // go back to the index and show the latest tick
    header('Location: ' . $config->basePath);
    exit;
}
?>

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

<?= render_mood_picker(); ?>

        <a class="back-link" href="<?= htmlspecialchars($config->basePath) ?>">Back to home</a>
    </body>
</html>