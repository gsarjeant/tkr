<?php /** @var bool $isLoggedIn */ ?>
<?php /** @var ConfigModel $config */ ?>
<?php /** @var UserModel $user */ ?>
<?php /** @var string $tickList */ ?>
        <div class="home-container">
            <aside id="sidebar" class="home-sidebar">
                <dl class="profile-data">
                    <dt>Current Status</dt>
                    <dd class="profile-greeting">
                        <span class="profile-greeting-content">
                            <span class="profile-greeting-content-text">Hi, I'm <?php echo Util::escape_html($user->displayName) ?></span>
                            <span class="profile-greeting-content-mood"><?php echo Util::escape_html($user->mood) ?></span>
                        </span>
<?php if (Session::isLoggedIn()): ?>
                        <a tabindex="0" href="<?= Util::escape_html($config->basePath) ?>mood" class="change-mood">Change mood</a>
<?php endif ?>
                    </dd>
<?php if (!empty($user->about)): ?>
                    <dt>About</dt>
                    <dd class="profile-about">
                        <?php echo Util::escape_html($user->about) ?>
                    </dd>
<?php endif ?>
<?php if (!empty($user->website)): ?>
                    <dt>Website</dt>
                    <dd class="profile-website">
                        <?php echo Util::linkify(Util::escape_html($user->website)) ?>
                    </dd>
<?php endif ?>
                </dl>
<?php if (Session::isLoggedIn()): ?>
                <div class="profile-tick">
                    <form class="profile-tick-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?= Util::escape_html($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="tick_mood" value="<?= Util::escape_html($user->mood) ?>">
                        <textarea name="new_tick"
                                  aria-label="What's ticking?"
                                  placeholder="What's ticking?"
                                  minlength="1"
                                  maxlength="200"
                                  rows="3"></textarea>
                        <button type="submit" class="submit-btn">Tick</button>
                    </form>
                </div>
<?php endif; ?>
            </aside>
            <main id="ticks">
                <h1 class="site-description"><?= Util::escape_html($config->siteDescription) ?></h1>
                <?php echo $tickList ?>
            </main>
        </div>
