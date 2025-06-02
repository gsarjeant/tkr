<?php
class HomeView {
    public function renderTicksSection(string $siteDescription, array $ticks, int $page, int $limit){
        ob_start();
        ?>

        <section id="ticks" class="home-ticks">
            <div class="home-ticks-header">
                <h2><?= $siteDescription ?></h2>
            </div>
            <div class="home-ticks-list">
            <?php foreach ($ticks as $tick): ?>
                <article class="tick">
                    <div class="tick-time"><?= htmlspecialchars(relative_time($tick['timestamp'])) ?></div>
                    <span class="tick-text"><?= escape_and_linkify($tick['tick']) ?></span>
                </article>
            <?php endforeach; ?>
            </div>
            <div class="home-ticks-pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">&laquo; Newer</a>
            <?php endif; ?>
            <?php if (count($ticks) === $limit): ?>
                <a href="?page=<?= $page + 1 ?>">Older &raquo;</a>
            <?php endif; ?>
            </div>
        </section>

        <?php return ob_get_clean();
    }
}