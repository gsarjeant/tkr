<?php
class FlashView {
    public function renderFlashSection(array $flashMessages): string {
        ob_start();
        ?>

        <?php if (count($flashMessages) > 0): ?>
            <div class="flash-messages">
            <?php foreach ($flashMessages as $type => $messages): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="flash-message flash-<?php echo $type; ?>">
                        <?php echo Util::escape_html($message); ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </div>
        <?php endif;

        return ob_get_clean();
    }
}