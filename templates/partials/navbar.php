<?php /** @var ConfigModel $config */ ?>
<?php /* https://www.w3schools.com/howto/howto_css_dropdown.asp */ ?>
        <div class="navbar">
            <a href="<?= $config->basePath ?>">home</a>
            <details class="dropdown">
                <summary>feeds</summary>
                <div class="dropdown-content">
                    <a href="<?= $config->basePath ?>feed/rss">rss</a>
                    <a href="<?= $config->basePath ?>feed/atom">atom</a>
                </div>
            </details>
<?php if (!Session::isLoggedIn()): ?>
            <a href="<?= $config->basePath ?>login">login</a>
<?php else: ?>
            <details class="dropdown">
                <summary>admin</summary>
                <div class="dropdown-content">
                    <a href="<?= $config->basePath ?>admin">settings</a>
                    <a href="<?= $config->basePath ?>admin/css">css</a>
                    <a href="<?= $config->basePath ?>admin/emoji">emoji</a>
                </div>
            </details>
            <a href="<?= $config->basePath ?>logout">logout</a>
<?php endif; ?>
        </div>