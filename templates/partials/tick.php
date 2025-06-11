<?php /** @var ConfigModel $config */ ?>
<?php /** @var Date $tickTime */ ?>
<?php /** @var string $tick */ ?>
        <h1>Tick from <?= $tickTime->format('Y-m-d H:i:s'); ?></h1>
        <p><?= $tick ?></p>