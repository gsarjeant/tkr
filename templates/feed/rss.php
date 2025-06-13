<?php /** @var ConfigModel $config */ ?>
<?php /** @var array $ticks */ ?>
<?php
// Need to have a little php here because the starting xml tag
// will mess up the PHP parser.
// TODO - I think short php tags can be disabled to prevent that.
header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
<channel>
    <title>My tkr</title>
    <link rel="self"
          type="application/rss+xml"
          title="<?php echo htmlspecialchars($config->siteTitle) ?> RSS Feed"
          href="<?php echo htmlspecialchars($config->baseUrl . $config->basePath)?>feed/rss/" />
    <link rel="alternate"
          type="text/html"
          href=<?php echo htmlspecialchars($config->baseUrl . $config->basePath) ?> />
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
        <link><?php echo htmlspecialchars($config->baseUrl . $config->basePath . "tick/$tickPath"); ?></link>
        <description><?php echo htmlspecialchars($tick['tick']); ?></description>
        <pubDate><?php echo date(DATE_RSS, strtotime($tick['timestamp'])); ?></pubDate>
        <guid><?php echo htmlspecialchars($tickPath); ?></guid>
    </item>
<?php endforeach; ?>
</channel>
</rss>
