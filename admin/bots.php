<?php
require_once __DIR__ . '/../classes/BotManager.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

$botManager = new BotManager();
$bots = $botManager->getBots();

if (isset($_POST['add_bot'])) {
    $name = $_POST['name'];
    $token = $_POST['token'];
    $username = $_POST['username'];
    $botManager->createBot($name, $username, $token);
    header("Location: bots.php?success=1");
    exit;
}

if (isset($_GET['delete'])) {
    $botManager->deleteBot($_GET['delete']);
    header("Location: bots.php?deleted=1");
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-[#1e293b]">مدیریت بات‌ها</h1>
    <button onclick="document.getElementById('addBotModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
        + افزودن بات جدید
    </button>
</div>

<div class="bg-white rounded-xl border border-[#e2e8f0] overflow-hidden">
    <table class="w-full text-right text-[13px]">
        <thead class="bg-[#f8fafc] text-[#64748b] font-semibold">
            <tr>
                <th class="p-4 border-b border-[#f1f5f9]">شناسه</th>
                <th class="p-4 border-b border-[#f1f5f9]">نام بات</th>
                <th class="p-4 border-b border-[#f1f5f9]">یوزرنیم</th>
                <th class="p-4 border-b border-[#f1f5f9]">آدرس وبهوک (برای تنظیم در بله)</th>
                <th class="p-4 border-b border-[#f1f5f9]">تاریخ ایجاد</th>
                <th class="p-4 border-b border-[#f1f5f9]">عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $dir = dirname($_SERVER['PHP_SELF']); // /admin
            $parentDir = dirname($dir); // /
            if ($parentDir === DIRECTORY_SEPARATOR) $parentDir = '';
            $baseUrl = $protocol . "://" . $host . $parentDir;
            ?>
            <?php foreach ($bots as $bot): ?>
            <tr class="border-b border-[#f8fafc] hover:bg-[#fbfcfd] transition-colors">
                <td class="p-4"><?= $bot['id'] ?></td>
                <td class="p-4 font-medium text-[#1e293b]"><?= htmlspecialchars($bot['name']) ?></td>
                <td class="p-4 text-[#64748b]">@<?= htmlspecialchars($bot['username']) ?></td>
                <td class="p-4">
                    <div class="flex items-center gap-2">
                        <?php $prettyUrl = $baseUrl . "/bot/" . $bot['username'] . "/webhook.php"; ?>
                        <code class="bg-gray-100 px-2 py-1 rounded text-[10px] text-blue-600 truncate max-w-[200px]" title="<?= $prettyUrl ?>">
                            .../bot/<?= $bot['username'] ?>/webhook.php
                        </code>
                        <button onclick="navigator.clipboard.writeText('<?= $prettyUrl ?>'); alert('کپی شد!')" class="text-xs text-blue-500 hover:underline">کپی</button>
                    </div>
                </td>
                <td class="p-4 text-[#64748b]"><?= explode(' ', $bot['created_at'])[0] ?></td>
                <td class="p-4">
                    <div class="flex items-center gap-2">
                        <a href="bots.php?delete=<?= $bot['id'] ?>" onclick="return confirm('آیا از حذف این بات مطمئن هستید؟')" class="text-red-500 hover:text-red-700">حذف</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($bots)): ?>
            <tr><td colspan="6" class="p-8 text-center text-[#64748b]">هنوز باتی اضافه نشده است.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Add Bot -->
<div id="addBotModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-[#f1f5f9] flex justify-between items-center bg-[#f8fafc]">
            <h3 class="text-lg font-bold text-[#1e293b]">افزودن بات جدید</h3>
            <button onclick="document.getElementById('addBotModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">نام بات (نمایشی)</label>
                <input type="text" name="name" required class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">یوزرنیم بات (بدون @)</label>
                <input type="text" name="username" required class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-left" dir="ltr">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">توکن بات (Token)</label>
                <input type="text" name="token" required class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr">
            </div>
            <div class="pt-4 flex gap-3">
                <button type="submit" name="add_bot" class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors">ذخیره بات</button>
                <button type="button" onclick="document.getElementById('addBotModal').classList.add('hidden')" class="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors">انصراف</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
