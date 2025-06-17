<?php
class MoodView {
    private function render_emoji_groups(array $emojiGroups, string $currentMood): string {
        $selected_emoji = $currentMood;  //user->mood;

        ob_start();
        ?>

        <?php foreach ($emojiGroups as $group => $emojis): ?>
            <fieldset id="<?= Util::escape_html($group) ?>" class="emoji-group">
                <legend><?= ucfirst($group) ?></legend>
            <?php foreach ($emojis as [$emoji, $description]): ?>
                <label class="emoji-option">
                    <input
                        type="radio"
                        name="mood"
                        value="<?= Util::escape_html($emoji) ?>"
                        aria-label="<?=Util::escape_html($description ?? 'emoji') ?>"
                        <?= $emoji === $selected_emoji ? 'checked' : '' ?>
                    >
                    <span><?= Util::escape_html($emoji) ?></span>
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
            <input type="hidden" name="csrf_token" value="<?= Util::escape_html($_SESSION['csrf_token']) ?>">
            <?= $this->render_emoji_groups($emojiGroups, $currentMood) ?>
            <div class="button-group">
                <button type="submit" name="action" value="set">Set the mood</button>
                <button type="submit" name="action" value="clear" class="clear-button">Clear mood</button>
            </div>
        </form>
        <?php

        return ob_get_clean();
    }
}
?>