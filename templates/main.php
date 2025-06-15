<?php /** @var bool $isLoggedIn */ ?>
<?php /** @var ConfigModel $config */ ?>
<?php /** @var UserModel $user */ ?>
<?php /** @var string $childTemplateFile */ ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?= $config->siteTitle ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet"
              href="<?= htmlspecialchars($config->basePath) ?>css/tkr.css">
<?php if (!empty($config->cssId)): ?>
        <link rel="stylesheet"
              href="<?= htmlspecialchars($config->basePath) ?>css/custom/<?= htmlspecialchars($config->customCssFilename()) ?>">
<?php endif; ?>
        <link rel="alternate"
              type="application/rss+xml"
              title="<?php echo htmlspecialchars($config->siteTitle) ?> RSS Feed"
              href="<?php echo htmlspecialchars($config->baseUrl . $config->basePath)?>feed/rss/">   
        <link rel="alternate"
              type="application/atom+xml"
              title="<?php echo htmlspecialchars($config->siteTitle) ?> Atom Feed"
              href="<?php echo htmlspecialchars($config->baseUrl . $config->basePath)?>feed/atom/">   
    </head>
    <body>
<?php include TEMPLATES_DIR . '/partials/navbar.php'?>
<?php include TEMPLATES_DIR . '/partials/' . $childTemplateFile?>
    </body>
</html>