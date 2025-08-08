<?php /** @var SettingsModel $settings */ ?>
<?php /* https://www.w3schools.com/howto/howto_css_dropdown.asp */ ?>
        <nav aria-label="Main navigation">
            <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
               href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath)) ?>">home</a>
            <details>
                <summary aria-haspopup="true">feeds</summary>
                <div class="dropdown-items">
                    <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'feed/rss')) ?>">rss</a>
                    <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'feed/atom')) ?>">atom</a>
                </div>
            </details>
<?php if (!Session::isLoggedIn()): ?>
            <a tabindex="0"
               href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'login')) ?>">login</a>
<?php else: ?>
            <details>
                <summary aria-haspopup="true">admin</summary>
                <div class="dropdown-items">
                    <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'admin')) ?>">settings</a>
                    <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'admin/css')) ?>">css</a>
                    <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'admin/emoji')) ?>">emoji</a>
                    <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                       href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'admin/logs')) ?>">logs</a>
                </div>
            </details>
            <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
               href="<?= Util::escape_html(Util::buildRelativeUrl($settings->basePath, 'logout')) ?>">logout</a>
<?php endif; ?>
        </nav>