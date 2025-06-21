<?php /** @var ConfigModel $config */ ?>
<?php /* https://www.w3schools.com/howto/howto_css_dropdown.asp */ ?>
        <nav aria-label="Main navigation">
            <a href="<?= $config->basePath ?>">home</a>
            <details>
                <summary aria-haspopup="true">feeds</summary>
                <div class="dropdown-items">
                    <a href="<?= $config->basePath ?>feed/rss">rss</a>
                    <a href="<?= $config->basePath ?>feed/atom">atom</a>
                </div>
            </details>
<?php if (!Session::isLoggedIn()): ?>
            <a href="<?= $config->basePath ?>login">login</a>
<?php else: ?>
            <details>
                <summary aria-haspopup="true">admin</summary>
                <div class="dropdown-items">
                    <a href="<?= $config->basePath ?>admin">settings</a>
                    <a href="<?= $config->basePath ?>admin/css">css</a>
                    <a href="<?= $config->basePath ?>admin/emoji">emoji</a>
                </div>
            </details>
            <a href="<?= $config->basePath ?>logout">logout</a>
<?php endif; ?>
        </nav>