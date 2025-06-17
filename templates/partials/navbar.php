<?php /** @var ConfigModel $config */ ?>
<?php /* https://www.w3schools.com/howto/howto_css_dropdown.asp */ ?>
        <div class="navbar">
            <a href="<?= $config->basePath ?>">home</a>
            <div class="dropdown">
                <button class="dropbtn">feeds</button>
                <div class="dropdown-content">
                    <a href="<?= $config->basePath ?>feed/rss">rss</a>
                    <a href="<?= $config->basePath ?>feed/atom">atom</a>
                </div>
            </div>
<?php if (!Session::isLoggedIn()): ?>
            <a href="<?= $config->basePath ?>login">login</a>
<?php else: ?>
            <div class="dropdown">
                <button class="dropbtn">admin</button>
                <div class="dropdown-content">
                    <a href="<?= $config->basePath ?>admin">settings</a>
                    <a href="<?= $config->basePath ?>admin/css">css</a>
                    <a href="<?= $config->basePath ?>admin/emoji">emoji</a>
                </div>
            </div>
            <a href="<?= $config->basePath ?>logout">logout</a>
<?php endif; ?>
        </div>