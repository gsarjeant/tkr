<?php
class HomeView {
    public function renderTicksSection(string $siteDescription, array $ticks, int $page, int $limit){
        global $config;
        ob_start();
        ?>

            <ul class="tick-feed" aria-label="Recent updates">
            <?php foreach ($ticks as $tick): ?>
                <?php
                    $datetime = new DateTime($tick['timestamp'], new DateTimeZone('UTC'));
                    $relativeTime = Util::relative_time($tick['timestamp']);
                ?>
                <li class="tick" tabindex="0">
                    <time datetime="<?php echo $datetime->format('c') ?>"><?php echo Util::escape_html($relativeTime) ?></time>
                    <?php if ($config->showTickMood): ?>
                        <span><?php echo $tick['mood'] ?></span>
                    <?php endif; ?>
                    <span class="tick-text"><?php echo Util::linkify(Util::escape_html($tick['tick'])) ?></span>
                </li>
            <?php endforeach; ?>
            </ul>
            <div class="tick-pagination">
            <?php if ($page > 1): ?>
                <a <?php if($config->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                   href="?page=<?php echo $page - 1 ?>">&laquo; Newer</a>
            <?php endif; ?>
            <?php if (count($ticks) === $limit): ?>
                <a <?php if($config->strictAccessibility): ?>tabindex="0"<?php endif; ?>
                   href="?page=<?php echo $page + 1 ?>">Older &raquo;</a>
            <?php endif; ?>
            </div>

        <?php return ob_get_clean();
    }
}