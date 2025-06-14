<?php /** @var bool $isLoggedIn */ ?>
<?php /** @var ConfigModel $config */ ?>
<?php /** @var UserModel $user */ ?>
<?php /** @var string $tickList */ ?>
        <div class="home-container">
            <section id="sidebar" class="home-sidebar">
                <div class="home-header">
                    <h1 class="site-description"><?= $config->siteDescription ?></h1>
                </div>
<?php if (!empty($user->about)): ?>
                <p>About: <?= $user->about ?></p>
<?php endif ?>
<?php if (!empty($user->website)): ?>
                <p>Website: <?= Util::escape_and_linkify($user->website) ?></p>
<?php endif ?>
<?php if (!empty($user->mood) || Session::isLoggedIn()): ?>
                <div class="profile-row">
                    <div class="mood-bar">
                        <span>Current mood: <?= $user->mood ?></span>
<?php if (Session::isLoggedIn()): ?>
                        <a href="<?= $config->basePath ?>mood">Change</a>
<?php endif; ?>
                    </div>
                </div>
<?php endif; ?>
<?php if (Session::isLoggedIn()): ?>
                <hr/>
                <div class="profile-row">
                    <form class="tick-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <textarea name="tick" placeholder="What's ticking?" rows="3"></textarea>
                        <button type="submit" class="submit-btn">Tick</button>
                    </form>
                </div>
<?php endif; ?>
            </section>
            <?php echo $tickList ?>
        </div>
