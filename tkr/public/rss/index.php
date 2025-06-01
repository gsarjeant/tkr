<?php
require_once __DIR__ . '/../../bootstrap.php';

confirm_setup();

require_once CLASSES_DIR . '/Config.php';
require_once LIB_DIR . '/ticks.php';

$config = Config::load();
$ticks = iterator_to_array(stream_ticks($config->itemsPerPage));

header('Content-Type: application/rss+xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
<channel>
    <title>My tkr</title>
    <link rel="alternate" type="application/rss+xml" title="Tick RSS" href="/tkr/rss/">
    <description>My tkr</description>
    <language>en-us</language>
    <lastBuildDate><?php echo date(DATE_RSS); ?></lastBuildDate>

<?php foreach ($ticks as $tick):
    [$date, $time] = explode(' ', $tick['timestamp']);
    $dateParts = explode('-', $date);
    $timeParts = explode(':', $time);

    [$year, $month, $day] = $dateParts;
    [$hour, $minute, $second] = $timeParts;

    $tickPath = "$year/$month/$day/$hour/$minute/$second";
?>
    
    <item>
        <title><?php echo htmlspecialchars($tick['tick']); ?></title>
        <link><?php echo htmlspecialchars("$config->basePath/tick.php?path=$tickPath"); ?></link>
        <description><?php echo htmlspecialchars($tick['tick']); ?></description>
        <pubDate><?php echo date(DATE_RSS, strtotime($tick['timestamp'])); ?></pubDate>
        <guid><?php echo htmlspecialchars($tickPath); ?></guid>
    </item>
    <?php endforeach; ?>

</channel>
</rss>
