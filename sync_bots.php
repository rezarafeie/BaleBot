<?php
require_once __DIR__ . '/classes/BotManager.php';
$bm = new BotManager();
$bm->init();
$bots = $bm->getBots();
$log = "Sync complete. Bots found: " . count($bots) . "\n";
foreach ($bots as $bot) {
    $log .= "- " . $bot['username'] . " (ID: " . $bot['id'] . ")\n";
}
file_put_contents(__DIR__ . '/data/sync_test.log', $log);
echo "Sync complete.\n";
