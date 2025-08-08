<?php /** @var bool $isLoggedIn */ ?>
<?php /** @var SettingsModel $settings */ ?>
<?php /** @var UserModel $user */ ?>
<?php /** @var string $childTemplateFile */ ?>
<?php /** @var string $customCssFilename */ ?>
<?php /** @var srting $flashSection */ ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?= $settings->siteTitle ?></title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet"
              href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'css/default.css')) ?>">
<?php if (!empty($settings->cssId)): ?>
        <link rel="stylesheet"
              href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'css/custom/' . $customCssFilename)) ?>">
<?php endif; ?>
        <link rel="alternate"
              type="application/rss+xml"
              title="<?php echo Util::escape_html($settings->siteTitle) ?> RSS Feed"
              href="<?php echo Util::escape_html($settings->baseUrl . $settings->basePath)?>feed/rss/">
        <link rel="alternate"
              type="application/atom+xml"
              title="<?php echo Util::escape_html($settings->siteTitle) ?> Atom Feed"
              href="<?php echo Util::escape_html($settings->baseUrl . $settings->basePath)?>feed/atom/">
    </head>
    <body>
<?php include TEMPLATES_DIR . '/partials/navbar.php'?>
<?php if( isset($flashSection) && !empty($flashSection) ): ?>
    <?php echo $flashSection; ?>
<?php endif; ?>
<?php include TEMPLATES_DIR . '/partials/' . $childTemplateFile?>
    </body>
</html>