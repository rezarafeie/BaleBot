<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/BotManager.php';

$bm = new BotManager();
echo "Syncing bots...\n";
$botsData = Database::getInstance()->getConnection()->query("SELECT id, name, username FROM bots")->fetchAll();
echo "Found " . count($botsData) . " bots in DB.\n";
foreach ($botsData as $bot) {
    echo "Processing: " . $bot['username'] . " (ID: " . $bot['id'] . ")\n";
    $bm->ensureWebhookFile($bot['username']);
    
    $path = __DIR__ . '/bots/' . ltrim($bot['username'], '@') . '/webhook_bale.php';
    if (file_exists($path)) {
        echo " SUCCESS: $path created.\n";
    } else {
        echo " FAILED: $path missing.\n";
        $dir = dirname($path);
        echo "  Target dir: $dir\n";
        echo "  Dir exists: " . (is_dir($dir) ? 'YES' : 'NO') . "\n";
        if (is_dir($dir)) {
            echo "  Dir perms: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
        }
    }
}
echo "Done.\n";
