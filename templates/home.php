<?php /** @var bool $isLoggedIn */ ?>
<?php /** @var Config $config */ ?>
<?php /** @var User $user */ ?>
<?php /** @var string $tickList */ ?>
<!DOCTYPE html>
<html>
    <head>
<?php include TEMPLATES_DIR . '/partials/head.php'?>
    </head>
    <body>
<?php include TEMPLATES_DIR . '/partials/navbar.php'?>
        <div class="home-container">
            <section id="sidebar" class="home-sidebar">
                <div class="home-header">
                    <h2>Hi, I'm <?= $user->displayName ?></h2>
                </div>
                <p><?= $user->about ?></p>
                <p>Website: <?= Util::escape_and_linkify($user->website) ?></p>
                <div class="profile-row">
                    <div class="mood-bar">
                        <span>Current mood: <?= $user->mood ?></span>
<?php if (Session::isLoggedIn()): ?>
                        <a href="<?= $config->basePath ?>mood">Change</a>
<?php endif; ?>
                    </div>
                </div>
<?php if (Session::isLoggedIn()): ?>
                <hr/>
                <div class="profile-row">
                    <form class="tick-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <textarea name="tick" placeholder="What's ticking?" rows="3"></textarea>
                        <button type="submit">Tick</button>
                    </form>
                </div>
<?php endif; ?>
            </section>
            <?php echo $tickList ?>
        </div>
    </body>
</html>
