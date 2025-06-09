<?php /** @var ConfigModel $config */ ?>
<?php /** @var array $ticks */ ?>
<?php
$siteTitle = htmlspecialchars($config->siteTitle);
$siteUrl = htmlspecialchars($config->baseUrl);
$basePath = $siteUrl . htmlspecialchars($config->basePath);
$updated = date(DATE_ATOM, strtotime($ticks[0]['timestamp'] ?? 'now'));

header('Content-Type: application/atom+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title><?= $siteTitle ?></title>
  <link ref="<?= $siteUrl ?>feed/atom" rel="self"/>
  <link href="<?= $siteUrl ?>"/>
  <updated><?= $updated ?></updated>
  <id><?= $siteUrl ?></id>
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
    $tickUrl = htmlspecialchars($basePath . "tick/$tickPath");
    $tickTime = date(DATE_ATOM, strtotime($tick['timestamp']));
    $tickText = htmlspecialchars($tick['tick']);
?>
  <entry>
    <title><?= $tickText ?></title>
    <link href="<?= $tickUrl ?>"/>
    <id><?= $tickUrl ?></id>
    <updated><?= $tickTime ?></updated>
    <content type="html"><?= $tickText ?></content>
  </entry>
<?php endforeach; ?>
</feed>
