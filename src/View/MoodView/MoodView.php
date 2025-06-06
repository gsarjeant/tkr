<?php
class MoodView {
    private function render_emoji_groups(array $emojiGroups, string $currentMood): string {
        $selected_emoji = $currentMood;  //user->mood;

        ob_start();
        ?>

        <?php foreach ($emojiGroups as $group => $emojis): ?>
            <fieldset id="<?= htmlspecialchars($group) ?>" class="emoji-group">
                <legend><?= ucfirst($group) ?></legend>
            <?php foreach ($emojis as [$emoji, $description]): ?>
                <label class="emoji-option">
                    <input
                        type="radio"
                        name="mood"
                        value="<?= htmlspecialchars($emoji) ?>"
                        aria-label="<?=htmlspecialchars($description ?? 'emoji') ?>"
                        <?= $emoji === $selected_emoji ? 'checked' : '' ?>
                    >
                    <span><?= htmlspecialchars($emoji) ?></span>
                </label>
            <?php endforeach; ?>
            </fieldset>
        <?php endforeach;

        return ob_get_clean();
    }

    function render_mood_picker(array $emojiGroups, string $currentMood): string {
        ob_start();
        ?>
        <form method="post" class="emoji-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <?= $this->render_emoji_groups($emojiGroups, $currentMood) ?>
            <button type="submit">Set the mood</button>
        </form>
        <?php

        return ob_get_clean();
    }
}
?>