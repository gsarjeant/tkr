<?php /** @var Date $tickTime */ ?>
<?php /** @var string $tick */ ?>
        <h1>Tick from <?= $tickTime; ?></h1>
        <p><?= Util::linkify(Util::escape_html($tick)) ?></p>
