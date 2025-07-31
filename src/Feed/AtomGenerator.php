<?php
class AtomGenerator extends FeedGenerator {
    public function generate(): string {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= $this->buildFeed();

        Log::debug("Generated Atom feed: " . strlen($xml) . " bytes");
        return $xml;
    }

    public function getContentType(): string {
        return 'application/atom+xml; charset=utf-8';
    }

    private function buildFeed(): string {
        Log::debug("Building Atom feed for " . $this->config->siteTitle);
        $feedTitle = Util::escape_xml($this->config->siteTitle . " Atom Feed");
        $siteUrl = Util::escape_xml(Util::buildUrl($this->config->baseUrl, $this->config->basePath));
        $feedUrl = Util::escape_xml(Util::buildUrl($this->config->baseUrl, $this->config->basePath, 'feed/atom'));
        $updated = date(DATE_ATOM, strtotime($this->ticks[0]['timestamp'] ?? 'now'));

        ob_start();
        ?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title><?php echo $feedTitle ?></title>
  <link rel="self"
        type="application/atom+xml"
        title="<?php echo $feedTitle ?>"
        href="<?php echo $feedUrl ?>" />
  <link rel="alternate" href="<?php echo $siteUrl  ?>"/>
  <updated><?php echo $updated ?></updated>
  <id><?php echo $siteUrl ?></id>
  <author>
        <name><?= Util::escape_xml($this->config->siteTitle) ?></name>
  </author>
<?php foreach ($this->ticks as $tick):
    // build the tick entry components
    $tickPath = "tick/" . $tick['id'];
    $tickUrl = Util::escape_xml($siteUrl . $tickPath);
    $tickTime = date(DATE_ATOM, strtotime($tick['timestamp']));
    $tickTitle = Util::escape_xml($tick['tick']);
    $tickContent = Util::linkify($tickTitle);
?>
  <entry>
    <title><?= $tickTitle ?></title>
    <link href="<?= $tickUrl ?>"/>
    <id><?= $tickUrl ?></id>
    <updated><?= $tickTime ?></updated>
    <content type="html"><?= $tickContent ?></content>
  </entry>
<?php endforeach; ?>
</feed>
<?php
        return ob_get_clean();
    }
}
