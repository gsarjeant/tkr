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
    <title><?php echo Util::escape_xml($config->siteTitle . 'RSS Feed') ?></title>
    <link><?php echo Util::escape_xml($config->baseUrl . $config->basePath)?></link>
    <atom:link href="<?php echo Util::escape_xml($config->baseUrl . $config->basePath. 'feed/rss')?>"
               rel="self"
               type="application/rss+xml" />
    <description><?php echo Util::escape_xml($config->siteDescription) ?></description>
    <language>en-us</language>
    <lastBuildDate><?php echo date(DATE_RSS); ?></lastBuildDate>
<?php foreach ($ticks as $tick):
    // build the tick entry components
    //$tickPath = "tick/$year/$month/$day/$hour/$minute/$second";
    $tickPath = "tick/" . $tick['id'];
    $tickUrl = Util::escape_xml($config->baseUrl . $config->basePath . $tickPath);
    $tickDate = date(DATE_RSS, strtotime($tick['timestamp']));
    $tickTitle = Util::escape_xml($tick['tick']);
    $tickDescription = Util::linkify($tickTitle);
?>
    <item>
        <title><?php echo $tickTitle ?></title>
        <link><?php echo $tickUrl; ?></link>
        <description><?php echo $tickDescription; ?></description>
        <pubDate><?php echo $tickDate; ?></pubDate>
        <guid><?php echo $tickUrl; ?></guid>
    </item>
<?php endforeach; ?>
</channel>
</rss>
