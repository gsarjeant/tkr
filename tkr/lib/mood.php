<?php
require_once __DIR__ . '/../bootstrap.php';
require_once LIB_ROOT . '/config.php';

require LIB_ROOT . '/emoji.php';

function get_mood(): ?string {
    $config = Config::load();
    $db = get_db();

    $stmt = $db->prepare("SELECT mood FROM user WHERE username=?");
    $stmt->execute([$_SESSION['username']]);
    $row = $stmt->fetch();

    return $row['mood'];
}

function save_mood(string $mood): void {
    $config = Config::load();
    $db = get_db();

    $stmt = $db->prepare("UPDATE user SET mood=? WHERE username=?");
    $stmt->execute([$mood, $_SESSION['username']]);

    header("Location: $config->basePath");
    exit;
}

function render_emoji_tabs(?string $selected_emoji = null): string {
    $emoji_groups = get_emojis_with_labels();

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

function render_mood_picker(?string $selected_emoji = null): string {
    ob_start();
    ?>
    <form action="set_mood.php" method="post" class="emoji-picker-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <?= render_emoji_tabs($selected_emoji) ?>
        <button type="submit">Set the mood</button>
    </form>
    <?php

    return ob_get_clean();
}
