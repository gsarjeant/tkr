<?php /** @var ConfigModel $config */ ?>
<?php /** @var array $ticks */ ?>
<?php
// Need to have a little php here because the starting xml tag
// will mess up the PHP parser.
// TODO - I think short php tags can be disabled to prevent that.
header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title><?php echo htmlspecialchars($config->siteTitle, ENT_XML1, 'UTF-8') ?> RSS Feed</title>
    <link><?php echo htmlspecialchars($config->baseUrl . $config->basePath, ENT_XML1, 'UTF-8')?></link>
    <atom:link href="<?php echo htmlspecialchars($config->baseUrl . $config->basePath, ENT_XML1, 'UTF-8')?>feed/rss" rel="self" type="application/rss+xml" />
    <description><?php echo htmlspecialchars($config->siteDescription, ENT_XML1, 'UTF-8') ?></description>
    <language>en-us</language>
    <lastBuildDate><?php echo date(DATE_RSS); ?></lastBuildDate>
<?php foreach ($ticks as $tick):
    [$date, $time] = explode(' ', $tick['timestamp']);
    $dateParts = explode('-', $date);
    $timeParts = explode(':', $time);

    [$year, $month, $day] = $dateParts;
    [$hour, $minute, $second] = $timeParts;

    $tickPath = "$year/$month/$day/$hour/$minute/$second";
    $tickUrl = $config->baseUrl . $config->basePath . $tickPath;
?>
    <item>
        <title><?php echo htmlspecialchars($tick['tick'], ENT_XML1, 'UTF-8'); ?></title>
        <link><?php echo htmlspecialchars($config->baseUrl . $config->basePath . "tick/$tickPath", ENT_XML1, 'UTF-8'); ?></link>
        <description><?php echo Util::escape_and_linkify($tick['tick'], ENT_XML1, false); ?></description>
        <pubDate><?php echo date(DATE_RSS, strtotime($tick['timestamp'])); ?></pubDate>
        <guid><?php echo htmlspecialchars($tickUrl, ENT_XML1, 'UTF-8'); ?></guid>
    </item>
<?php endforeach; ?>
</channel>
</rss>
