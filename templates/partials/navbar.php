<?php /** @var ConfigModel $config */ ?>
        <div class="navbar">
            <a href="<?= $config->basePath ?>">home</a>
            <a href="<?= $config->basePath ?>feed/rss">rss</a>
            <a href="<?= $config->basePath ?>feed/atom">atom</a>
<?php if (!Session::isLoggedIn()): ?>
            <a href="<?= $config->basePath ?>login">login</a>
<?php else: ?>
            <a href="<?= $config->basePath ?>admin">admin</a>
            <a href="<?= $config->basePath ?>admin/css">css</a>
            <a href="<?= $config->basePath ?>logout">logout</a>
<?php endif; ?>
        </div>