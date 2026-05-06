<?php
require_once __DIR__ . '/classes/BotManager.php';
$bm = new BotManager();
$bots = $bm->getBots();
echo "Total bots: " . count($bots) . "\n";
foreach ($bots as $bot) {
    echo "ID: " . $bot['id'] . " | Name: " . $bot['name'] . " | Username: " . $bot['username'] . " | Token: " . substr($bot['token'], 0, 10) . "...\n";
}
