<?php
require_once __DIR__ . '/../classes/BotManager.php';
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

$botManager = new BotManager();
$bots = $botManager->getBots();

if (isset($_GET['test_token'])) {
    header('Content-Type: application/json');
    $token = $_GET['token'] ?? '';
    $platform = $_GET['platform'] ?? 'bale';
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'توکن وارد نشده است.']);
        exit;
    }

    try {
        if ($platform === 'telegram') {
            require_once __DIR__ . '/../classes/TelegramBot.php';
            $testBot = new TelegramBot($token);
            $res = $testBot->getMe();
            if ($res && isset($res['ok']) && $res['ok']) {
                echo json_encode(['success' => true, 'message' => 'اتصال تلگرام برقرار است: @' . $res['result']['username']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'توکن تلگرام نامعتبر است یا خطایی رخ داد.']);
            }
        } elseif ($platform === 'bale') {
            require_once __DIR__ . '/../classes/BaleBot.php';
            $testBot = new BaleBot($token);
            $res = $testBot->getMe();
            if ($res && isset($res['ok']) && $res['ok']) {
                echo json_encode(['success' => true, 'message' => 'اتصال بله برقرار است: @' . ($res['result']['username'] ?? 'Bot')]);
            } else {
                echo json_encode(['success' => false, 'message' => 'توکن بله نامعتبر است یا خطایی رخ داد.']);
            }
        } elseif ($platform === 'rubika') {
             // For Rubika, testing is harder without full API, we'll just check if token is provided
             echo json_encode(['success' => true, 'message' => 'توکن روبیکا ثبت شد (تست خودکار فعلا غیرفعال است)']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if (isset($_POST['sync_files'])) {
    $botManager->syncPhysicalBots(); 
    header("Location: bots.php?synced=1");
    exit;
}

if (isset($_POST['add_bot'])) {
    $name = $_POST['name'];
    $token = $_POST['token'];
    $telegram_token = $_POST['telegram_token'];
    $rubika_token = $_POST['rubika_token'];
    $username = $_POST['username'];
    $botManager->createBot($name, $username, $token, $telegram_token, $rubika_token);
    header("Location: bots.php?success=1");
    exit;
}

if (isset($_POST['edit_bot'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $username = $_POST['username'];
    $token = $_POST['token'];
    $telegram_token = $_POST['telegram_token'];
    $rubika_token = $_POST['rubika_token'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $botManager->updateBot($id, $name, $username, $token, $telegram_token, $rubika_token, $is_active);
    header("Location: bots.php?updated=1");
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
    <div class="flex gap-2">
        <form method="POST" class="inline">
            <button type="submit" name="sync_files" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                ⏳ بازنشانی فایل‌های وبهوک
            </button>
        </form>
        <button onclick="document.getElementById('addBotModal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
            + افزودن بات جدید
        </button>
    </div>
</div>

<div class="bg-white rounded-xl border border-[#e2e8f0] overflow-hidden">
    <table class="w-full text-right text-[13px]">
        <thead class="bg-[#f8fafc] text-[#64748b] font-semibold">
            <tr>
                <th class="p-4 border-b border-[#f1f5f9]">شناسه</th>
                <th class="p-4 border-b border-[#f1f5f9]">نام بات</th>
                <th class="p-4 border-b border-[#f1f5f9]">یوزرنیم</th>
                <th class="p-4 border-b border-[#f1f5f9]">وضعیت پیام‌رسان‌ها</th>
                <th class="p-4 border-b border-[#f1f5f9]">آدرس‌های وبهوک</th>
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
                    <div class="flex flex-col gap-1 text-[11px]">
                        <span class="<?= $bot['token'] ? 'text-green-600' : 'text-gray-400' ?>">● بله</span>
                        <span class="<?= $bot['telegram_token'] ? 'text-blue-600' : 'text-gray-400' ?>">● تلگرام</span>
                        <span class="<?= $bot['rubika_token'] ? 'text-orange-600' : 'text-gray-400' ?>">● روبیکا</span>
                    </div>
                </td>
                <td class="p-4">
                    <div class="flex flex-col gap-2 max-w-[220px]">
                        <?php foreach (['bale', 'telegram', 'rubika'] as $p): ?>
                        <div class="flex items-center justify-between gap-2 bg-gray-50 px-2 py-1 rounded">
                            <span class="text-[10px] text-gray-500 uppercase"><?= $p ?>:</span>
                            <?php $prettyUrl = $baseUrl . "/bots/" . $bot['username'] . "/webhook_{$p}.php?secret=" . WEBHOOK_SECRET; ?>
                            <button onclick="navigator.clipboard.writeText('<?= $prettyUrl ?>'); alert('کپی شد!')" class="text-[10px] text-blue-500 hover:underline truncate" title="<?= $prettyUrl ?>">کپی لینک</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </td>
                <td class="p-4 text-[#64748b]"><?= explode(' ', $bot['created_at'])[0] ?></td>
                <td class="p-4">
                    <div class="flex items-center gap-2">
                        <button onclick='editBot(<?= json_encode($bot) ?>)' class="text-blue-500 hover:text-blue-700">ویرایش</button>
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
                <label class="block text-sm font-medium text-[#475569] mb-1.5">توکن بات بله (Bale Token)</label>
                <div class="flex gap-2">
                    <input type="text" name="token" id="add_token_bale" class="flex-1 border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr">
                    <button type="button" onclick="testConnection('bale', 'add_token_bale')" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200">تست</button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">توکن بات تلگرام (Telegram Token)</label>
                <div class="flex gap-2">
                    <input type="text" name="telegram_token" id="add_token_telegram" class="flex-1 border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr">
                    <button type="button" onclick="testConnection('telegram', 'add_token_telegram')" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200">تست</button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">توکن بات روبیکا (Rubika Token)</label>
                <div class="flex gap-2">
                    <input type="text" name="rubika_token" id="add_token_rubika" class="flex-1 border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr">
                    <button type="button" onclick="testConnection('rubika', 'add_token_rubika')" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200">تست</button>
                </div>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="submit" name="add_bot" class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors">ذخیره بات</button>
                <button type="button" onclick="document.getElementById('addBotModal').classList.add('hidden')" class="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors">انصراف</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Bot -->
<div id="editBotModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-[#f1f5f9] flex justify-between items-center bg-[#f8fafc]">
            <h3 class="text-lg font-bold text-[#1e293b]">ویرایش بات</h3>
            <button onclick="document.getElementById('editBotModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">نام بات (نمایشی)</label>
                <input type="text" name="name" id="edit_name" required class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">یوزرنیم بات (بدون @)</label>
                <input type="text" name="username" id="edit_username" required class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-left" dir="ltr">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">توکن بات بله (Bale Token)</label>
                <div class="flex gap-2">
                    <input type="text" name="token" id="edit_token" class="flex-1 border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr">
                    <button type="button" onclick="testConnection('bale', 'edit_token')" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200">تست</button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">توکن بات تلگرام (Telegram Token)</label>
                <div class="flex gap-2">
                    <input type="text" name="telegram_token" id="edit_telegram_token" class="flex-1 border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr">
                    <button type="button" onclick="testConnection('telegram', 'edit_telegram_token')" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200">تست</button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#475569] mb-1.5">توکن بات روبیکا (Rubika Token)</label>
                <div class="flex gap-2">
                    <input type="text" name="rubika_token" id="edit_rubika_token" class="flex-1 border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr">
                    <button type="button" onclick="testConnection('rubika', 'edit_rubika_token')" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200">تست</button>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="edit_is_active">
                <label for="edit_is_active" class="text-sm text-[#475569]">بات فعال باشد</label>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="submit" name="edit_bot" class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors">بروزرسانی بات</button>
                <button type="button" onclick="document.getElementById('editBotModal').classList.add('hidden')" class="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-lg text-sm font-semibold hover:bg-gray-200 transition-colors">انصراف</button>
            </div>
        </form>
    </div>
</div>

<script>
function editBot(bot) {
    document.getElementById('edit_id').value = bot.id;
    document.getElementById('edit_name').value = bot.name;
    document.getElementById('edit_username').value = bot.username;
    document.getElementById('edit_token').value = bot.token || '';
    document.getElementById('edit_telegram_token').value = bot.telegram_token || '';
    document.getElementById('edit_rubika_token').value = bot.rubika_token || '';
    document.getElementById('edit_is_active').checked = bot.is_active == 1;
    document.getElementById('editBotModal').classList.remove('hidden');
}

async function testConnection(platform, inputId) {
    const token = document.getElementById(inputId).value;
    if (!token) {
        alert('لطفا ابتدا توکن را وارد کنید');
        return;
    }
    
    const btn = event.target;
    const oldText = btn.innerText;
    btn.innerText = '...';
    btn.disabled = true;
    
    try {
        const response = await fetch(`bots.php?test_token=1&platform=${platform}&token=${encodeURIComponent(token)}`);
        const result = await response.json();
        alert(result.message);
    } catch (e) {
        alert('خطا در برقراری ارتباط');
    } finally {
        btn.innerText = oldText;
        btn.disabled = false;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
