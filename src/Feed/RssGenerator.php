<?php
declare(strict_types=1);

class RssGenerator extends FeedGenerator {
    public function generate(): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= $this->buildChannel();
        $xml .= '</rss>' . "\n";

        Log::debug("Generated RSS feed: " . strlen($xml) . " bytes");
        return $xml;
    }

    public function getContentType(): string {
        return 'application/rss+xml; charset=utf-8';
    }

    private function buildChannel(): string {
        Log::debug("Building RSS channel for " . $this->settings->siteTitle);
        ob_start();
        ?>
<channel>
    <title><?php echo Util::escape_xml($this->settings->siteTitle . ' RSS Feed') ?></title>
    <link><?php echo Util::escape_xml(Util::buildUrl($this->settings->baseUrl, $this->settings->basePath))?></link>
    <atom:link href="<?php echo Util::escape_xml(Util::buildUrl($this->settings->baseUrl, $this->settings->basePath, 'feed/rss'))?>"
               rel="self"
               type="application/rss+xml" />
    <description><?php echo Util::escape_xml($this->settings->siteDescription) ?></description>
    <language>en-us</language>
    <lastBuildDate><?php echo date(DATE_RSS); ?></lastBuildDate>
<?php foreach ($this->ticks as $tick):
    // build the tick entry components
    $tickPath = "tick/" . $tick['id'];
    $tickUrl = Util::escape_xml($this->buildTickUrl($tick['id']));
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
<?php
        return ob_get_clean();
    }
}