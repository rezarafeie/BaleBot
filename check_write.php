<?php
$file = __DIR__ . '/data/write_test.log';
$res = @file_put_contents($file, "Test at " . date('Y-m-d H:i:s'));
if ($res === false) {
    echo "FAILED to write to $file\n";
    $dir = __DIR__ . '/data';
    echo "Dir exists: " . (is_dir($dir) ? 'YES' : 'NO') . "\n";
    echo "Dir perms: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
    echo "Current user: " . get_current_user() . "\n";
} else {
    echo "SUCCESS: Wrote to $file\n";
}
