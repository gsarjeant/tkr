<?php
require_once __DIR__ . '/../../bootstrap.php';

confirm_setup();

require_once CLASSES_DIR . '/Config.php';
require_once LIB_DIR . '/ticks.php';

$config = Config::load();
$ticks = iterator_to_array(stream_ticks($config->itemsPerPage));
$siteTitle = htmlspecialchars($config->siteTitle);
$siteUrl = htmlspecialchars($config->baseUrl);
$basePath = $siteUrl . htmlspecialchars($config->basePath);
$updated = date(DATE_ATOM, strtotime($ticks[0]['timestamp'] ?? 'now'));

header('Content-Type: application/atom+xml; charset=utf-8');

echo <<<XML
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>{$siteTitle}</title>
  <link href="{$siteUrl}atom" rel="self"/>
  <link href="{$siteUrl}"/>
  <updated>{$updated}</updated>
  <id>{$siteUrl}</id>
  <author>
    <name>{$siteTitle}</name>
  </author>
XML;

foreach ($ticks as $tick) {
    [$date, $time] = explode(' ', $tick['timestamp']);
    $dateParts = explode('-', $date);
    $timeParts = explode(':', $time);

    [$year, $month, $day] = $dateParts;
    [$hour, $minute, $second] = $timeParts;

    $tickPath = "$year/$month/$day/$hour/$minute/$second";
    $tickUrl = htmlspecialchars($basePath . "tick.php?path=" . $tickPath);
    $tickTime = date(DATE_ATOM, strtotime($tick['timestamp']));
    $tickText = htmlspecialchars($tick['tick']);


    echo <<<ENTRY
  <entry>
    <title>{$tickText}</title>
    <link href="{$tickUrl}"/>
    <id>{$tickUrl}</id>
    <updated>{$tickTime}</updated>
    <content type="html">{$tickText}</content>
  </entry>

ENTRY;
}

echo "</feed>";
