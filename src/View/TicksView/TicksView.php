<?php
declare(strict_types=1);

class TicksView {
    private $html;

    public function __construct(SettingsModel $settings, array $ticks, int $page){
        $this->html = $this->render($settings, $ticks, $page);
    }

    public function getHtml(): string {
        return $this->html;
    }

    private function render(SettingsModel $settings, array $ticks, int $page): string{
        ob_start();
        ?>

            <ul class="tick-feed" aria-label="Recent updates">
            <?php foreach ($ticks as $tick): ?>
                <?php
                    $datetime = new DateTime($tick['timestamp'], new DateTimeZone('UTC'));
                    $relativeTime = Util::relative_time($tick['timestamp']);
                ?>
                <li class="tick" tabindex="0">
                    <?php if ($tick['can_delete']): ?>
                    <form method="post"
                          action="<?= Util::buildRelativeUrl($settings->basePath, "tick/{$tick['id']}/delete") ?>"
                          class="delete-tick-form">
                        <input type="hidden" name="csrf_token" value="<?= Util::escape_html($_SESSION['csrf_token']) ?>">
                        <button type="submit" class="delete-tick-button">🗑️</button>
                    </form>
                    <?php endif ?>
                    <time datetime="<?php echo $datetime->format('c') ?>"><?php echo Util::escape_html($relativeTime) ?></time>
                    <span class="tick-text"><?php echo Util::linkify(Util::escape_html($tick['tick'])) ?></span>
                </li>
            <?php endforeach; ?>
            </ul>
            <div class="tick-pagination">
            <?php if ($page > 1): ?>
                <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                   href="?page=<?php echo $page - 1 ?>">&laquo; Newer</a>
            <?php endif; ?>
            <?php if (count($ticks) === $settings->itemsPerPage): ?>
                <a <?php if($settings->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                   href="?page=<?php echo $page + 1 ?>">Older &raquo;</a>
            <?php endif; ?>
            </div>

        <?php return ob_get_clean();
    }
}