<?php /** @var ConfigModel $config */ ?>
<?php /** @var array $ticks */ ?>
<?php
$siteTitle = htmlspecialchars($config->siteTitle);
$siteUrl = htmlspecialchars($config->baseUrl);
$basePath = htmlspecialchars($config->basePath);
$updated = date(DATE_ATOM, strtotime($ticks[0]['timestamp'] ?? 'now'));

header('Content-Type: application/atom+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title><?= "$siteTitle Atom Feed" ?></title>
  <link rel="self"
        type="application/atom+xml"
        title="<?php echo htmlspecialchars($config->siteTitle, ENT_XML1, 'UTF-8') ?> Atom Feed"
        href="<?php echo htmlspecialchars($siteUrl . $basePath, ENT_XML1, 'UTF-8') ?>feed/atom">
  <link rel="alternate" href="<?= $siteUrl . $basePath ?>">
  <updated><?= $updated ?></updated>
  <id><?= $siteUrl . $basePath . 'feed/atom'?></id>
  <author>
        <name><?= $siteTitle ?></name>
  </author>
<?php foreach ($ticks as $tick):
    [$date, $time] = explode(' ', $tick['timestamp']);
    $dateParts = explode('-', $date);
    $timeParts = explode(':', $time);

    [$year, $month, $day] = $dateParts;
    [$hour, $minute, $second] = $timeParts;

    $tickPath = "$year/$month/$day/$hour/$minute/$second";
    $tickUrl = htmlspecialchars($siteUrl . $basePath . "tick/$tickPath", ENT_XML1, 'UTF-8');
    $tickTime = date(DATE_ATOM, strtotime($tick['timestamp']));
    $tickTitle = htmlspecialchars($tick['tick'], ENT_XML1, 'UTF-8');
    $tickContent = Util::escape_and_linkify($tick['tick'], ENT_XML1, false);
?>
  <entry>
    <title><?= $tickTitle ?></title>
    <link rel="alternate" type="text/html" href="<?= $tickUrl ?>"/>
    <id><?= $tickUrl ?></id>
    <updated><?= $tickTime ?></updated>
    <content type="html"><?= $tickContent ?></content>
  </entry>
<?php endforeach; ?>
</feed>
