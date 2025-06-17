<?php
class HomeView {
    public function renderTicksSection(string $siteDescription, array $ticks, int $page, int $limit){
        ob_start();
        ?>

            <ul class="tick-feed">
            <?php foreach ($ticks as $tick): ?>
                <li class="tick">
                    <div class="tick-time"><?= Util::escape_html(Util::relative_time($tick['timestamp'])) ?></div>
                    <span class="tick-text"><?= Util::linkify(Util::escape_html($tick['tick'])) ?></span>
                </li>
            <?php endforeach; ?>
            </ul>
            <div class="tick-pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">&laquo; Newer</a>
            <?php endif; ?>
            <?php if (count($ticks) === $limit): ?>
                <a href="?page=<?= $page + 1 ?>">Older &raquo;</a>
            <?php endif; ?>
            </div>

        <?php return ob_get_clean();
    }
}