<?php
class HomeView {
    public function renderTicksSection(string $siteDescription, array $ticks, int $page, int $limit){
        ob_start();
        ?>

        <main id="ticks" class="home-main">
            <div class="tick-feed">
            <?php foreach ($ticks as $tick): ?>
                <article class="tick">
                    <div class="tick-time"><?= htmlspecialchars(Util::relative_time($tick['timestamp'])) ?></div>
                    <span class="tick-text"><?= Util::escape_and_linkify($tick['tick']) ?></span>
                </article>
            <?php endforeach; ?>
            </div>
            <div class="tick-pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">&laquo; Newer</a>
            <?php endif; ?>
            <?php if (count($ticks) === $limit): ?>
                <a href="?page=<?= $page + 1 ?>">Older &raquo;</a>
            <?php endif; ?>
            </div>
        </main>

        <?php return ob_get_clean();
    }
}