<?php
require_once __DIR__ . '/../includes/header.php';

$file = $_GET['file'] ?? '';

// Try multiple path resolutions
$paths_to_try = [
    __DIR__ . '/../data/' . $file,
    __DIR__ . '/../' . $file
];

$path = null;
foreach ($paths_to_try as $p) {
    if (file_exists($p)) {
        $path = realpath($p);
        break;
    }
}

// Security Check
$base_root = realpath(__DIR__ . '/../');
$is_allowed = ($path && strpos($path, $base_root) === 0);

if (!$is_allowed || !$path || !file_exists($path)) {
    echo "File not found or access denied. (Path: " . htmlspecialchars($file) . ")";
    exit;
}

$content = file_get_contents($path);
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-[#1e293b]">مشاهده لاگ: <?= htmlspecialchars($file) ?></h1>
    <div class="flex gap-2">
        <a href="dashboard.php" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">بازگشت</a>
        <form method="POST" onsubmit="return confirm('آیا از پاک کردن این لاگ اطمینان دارید؟')">
            <button type="submit" name="clear" value="1" class="bg-red-50 text-red-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors">پاک کردن لاگ</button>
        </form>
    </div>
</div>

<?php
if (isset($_POST['clear']) && $_POST['clear'] == '1') {
    file_put_contents($path, "");
    header("Location: view-log.php?file=" . urlencode($file));
    exit;
}
?>

<div class="bg-slate-900 text-slate-100 p-6 rounded-xl overflow-auto max-h-[70vh] font-mono text-sm dir-ltr text-left">
    <pre><?= htmlspecialchars($content ?: 'لاگ خالی است.') ?></pre>
</div>

<div class="mt-4 text-xs text-slate-500 italic">
    * این لاگ برای عیب‌یابی در زمانی که درخواست‌ها به دیتابیس نمی‌رسند استفاده می‌شود. اگر در اینجا چیزی نمی‌بینید، یعنی درخواست حتی به فایل webhook.php هم نرسیده است.
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
