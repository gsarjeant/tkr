<?php
#require_once __DIR__ . '/../bootstrap.php';
#require_once CLASSES_DIR . '/Config.php';
#require_once CLASSES_DIR . '/User.php';

#require LIB_DIR . '/emoji.php';

function save_mood(string $mood): void {
    $config = Config::load();
    $user = User::load();
    //$db = get_db();

    //$stmt = $db->prepare("UPDATE user SET mood=? WHERE username=?");
    //$stmt->execute([$mood, $_SESSION['username']]);

    $user->mood = $mood;
    $user = $user->save();
    header("Location: $config->basePath");
    exit;
}

function render_emoji_tabs(): string {
    $user = User::load();
    $emoji_groups = get_emojis_with_labels();
    $selected_emoji = $user->mood;

    ob_start();
    ?>

    <?php foreach ($emoji_groups as $group => $emojis): ?>
        <fieldset id="<?= htmlspecialchars($group) ?>" class="emoji-tab-content">
            <legend><?= ucfirst($group) ?></legend>
            <?php foreach ($emojis as [$emoji, $desctiption]): ?>
                <label class="emoji-option">
                    <input
                        type="radio"
                        name="mood"
                        value="<?= htmlspecialchars($emoji) ?>"
                        aria-label="<?=htmlspecialchars($desctiption ?? 'emoji') ?>"
                        <?= $emoji === $selected_emoji ? 'checked' : '' ?>
                    >
                    <span><?= htmlspecialchars($emoji) ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach;

    return ob_get_clean();
}

function render_mood_picker(): string {
    ob_start();
    ?>
    <form action="set_mood.php" method="post" class="emoji-picker-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <?= render_emoji_tabs() ?>
        <button type="submit">Set the mood</button>
    </form>
    <?php

    return ob_get_clean();
}
