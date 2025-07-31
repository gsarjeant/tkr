<?php /** @var ConfigModel $config */ ?>
<?php /* https://www.w3schools.com/howto/howto_css_dropdown.asp */ ?>
        <nav aria-label="Main navigation">
            <a <?php if($config->strictAccessibility): ?>tabindex="0"<?php endif; ?>
               href="<?= Util::escape_html(Util::buildRelativeUrl($config->basePath)) ?>">home</a>
            <details>
                <summary aria-haspopup="true">feeds</summary>
                <div class="dropdown-items">
                    <a <?php if($config->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($config->basePath, 'feed/rss')) ?>">rss</a>
                    <a <?php if($config->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($config->basePath, 'feed/atom')) ?>">atom</a>
                </div>
            </details>
<?php if (!Session::isLoggedIn()): ?>
            <a tabindex="0"
               href="<?= Util::escape_html(Util::buildRelativeUrl($config->basePath, 'login')) ?>">login</a>
<?php else: ?>
            <details>
                <summary aria-haspopup="true">admin</summary>
                <div class="dropdown-items">
                    <a <?php if($config->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($config->basePath, 'admin')) ?>">settings</a>
                    <a <?php if($config->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($config->basePath, 'admin/css')) ?>">css</a>
                    <a <?php if($config->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($config->basePath, 'admin/emoji')) ?>">emoji</a>
                </div>
            </details>
            <a <?php if($config->strictAccessibility): ?>tabindex="0"<?php endif; ?>
               href="<?= Util::escape_html(Util::buildRelativeUrl($config->basePath, 'logout')) ?>">logout</a>
<?php endif; ?>
        </nav>