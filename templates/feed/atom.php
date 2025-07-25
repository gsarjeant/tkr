<?php /** @var ConfigModel $config */ ?>
<?php /** @var array $ticks */ ?>
<?php
$feedTitle = Util::escape_xml("$config->siteTitle Atom Feed");
$siteUrl = Util::escape_xml($config->baseUrl . $config->basePath);
$feedUrl = Util::escape_xml($config->baseUrl . $config->basePath . 'feed/atom');
$updated = date(DATE_ATOM, strtotime($ticks[0]['timestamp'] ?? 'now'));

header('Content-Type: application/atom+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
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
        <name><?= Util::escape_xml($config->siteTitle) ?></name>
  </author>
<?php foreach ($ticks as $tick):
    // build the tick entry components
    $tickPath = "tick/" . $tick['id'];
    $tickUrl = Util::escape_xml($siteUrl . $basePath . $tickPath);
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
