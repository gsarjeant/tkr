<?php
define('APP_ROOT', realpath(__DIR__ . '/../../'));
define('ITEMS_PER_PAGE', 25);

require APP_ROOT . '/config.php';
require_once APP_ROOT . '/stream_ticks.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$ticks = iterator_to_array(stream_ticks($tickLocation, ITEMS_PER_PAGE));

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
        <title><?php echo htmlspecialchars(date(DATE_RFC7231, strtotime($tick['timestamp']))); ?></title>
        <link><?php echo htmlspecialchars("$basePath/tick.php?path=$tickPath"); ?></link>
        <description><?php echo htmlspecialchars($tick['tick']); ?></description>
        <pubDate><?php echo date(DATE_RSS, strtotime($tick['timestamp'])); ?></pubDate>
        <guid><?php echo htmlspecialchars($tickPath); ?></guid>
    </item>
    <?php endforeach; ?>

</channel>
</rss>
