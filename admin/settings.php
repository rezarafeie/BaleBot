<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/BaleBot.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/GapGPT.php';

$auth = new Auth();
$db = Database::getInstance()->getConnection();
$bot_id = $_SESSION['selected_bot_id'] ?? 1;

$botData = (new BotManager())->getBot($bot_id);
$bot = new BaleBot($botData['token'] ?? null);

$msg = '';

if ($db) {
    if (isset($_POST['set_webhook'])) {
        $url = $_POST['webhook_url'];
        $stmt = $db->prepare("INSERT INTO settings (bot_id, setting_key, setting_value) VALUES (?, 'webhook_url', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$bot_id, $url, $url]);
        $res = $bot->setWebhook($url . "?bot_id=" . $bot_id . "&secret=" . WEBHOOK_SECRET);
        $msg = "وب‌هوک ست شد. وضعیت: " . (isset($res['ok']) && $res['ok'] ? 'موفق' : 'ناموفق');
    } elseif (isset($_POST['delete_webhook'])) {
        $res = $bot->deleteWebhook();
        $db->prepare("UPDATE settings SET setting_value = '' WHERE setting_key = 'webhook_url' AND bot_id = ?")->execute([$bot_id]);
        $msg = "وب‌هوک غیرفعال شد. وضعیت: " . (isset($res['ok']) && $res['ok'] ? 'موفق' : 'ناموفق');
    } elseif (isset($_POST['save_gapgpt'])) {
        $key = $_POST['gapgpt_api_key'] ?? '';
        $model = $_POST['gapgpt_model'] ?? 'gemini-2.5-flash-lite';
        $stmt = $db->prepare("INSERT INTO settings (bot_id, setting_key, setting_value) VALUES (?, 'gapgpt_api_key', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$bot_id, $key, $key]);
        $stmt = $db->prepare("INSERT INTO settings (bot_id, setting_key, setting_value) VALUES (?, 'gapgpt_model', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$bot_id, $model, $model]);
        $msg = "تنظیمات GapGPT ذخیره شد.";
    } elseif (isset($_POST['save_event_selection'])) {
        $text = $_POST['event_selection_text'] ?? '';
        $stmt = $db->prepare("INSERT INTO settings (bot_id, setting_key, setting_value) VALUES (?, 'event_selection_text', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$bot_id, $text, $text]);
        $msg = "متن پیش‌فرض انتخاب رویداد ذخیره شد.";
    }
} else {
    // Local fallback for POST
    require_once __DIR__ . '/../classes/LocalStore.php';
    if (isset($_POST['set_webhook'])) {
        $url = $_POST['webhook_url'];
        LocalStore::getInstance()->save('settings', "webhook_{$bot_id}", ['value' => $url]);
        $res = $bot->setWebhook($url . "?bot_id=" . $bot_id . "&secret=" . WEBHOOK_SECRET);
        $msg = "وب‌هوک ست شد (محلی). وضعیت: " . (isset($res['ok']) && $res['ok'] ? 'موفق' : 'ناموفق');
    } elseif (isset($_POST['save_gapgpt'])) {
        LocalStore::getInstance()->save('settings', "gapgpt_key_{$bot_id}", ['value' => $_POST['gapgpt_api_key']]);
        LocalStore::getInstance()->save('settings', "gapgpt_model_{$bot_id}", ['value' => $_POST['gapgpt_model']]);
        $msg = "تنظیمات GapGPT ذخیره شد (محلی).";
    } elseif (isset($_POST['save_event_selection'])) {
        LocalStore::getInstance()->save('settings', "event_text_{$bot_id}", ['value' => $_POST['event_selection_text']]);
        $msg = "متن پیش‌فرض انتخاب رویداد ذخیره شد (محلی).";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_pass'])) {
        if (!empty($_POST['new_pass'])) {
            $auth->updatePassword($_POST['new_pass']);
            $msg = "رمز عبور مدیر تغییر کرد.";
        }
    } elseif (isset($_POST['test_gapgpt'])) {
        // ... same test logic ...
    }
}

// Get current settings
$current_webhook = '';
$current_gapgpt_key = '';
$current_gapgpt_model = 'gemini-2.5-flash-lite';
$current_event_selection_text = '';

if ($db) {
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'webhook_url' AND bot_id = ?");
        $stmt->execute([$bot_id]);
        $current_webhook = $stmt->fetchColumn() ?: '';

        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'gapgpt_api_key' AND bot_id = ?");
        $stmt->execute([$bot_id]);
        $current_gapgpt_key = $stmt->fetchColumn() ?: '';

        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'gapgpt_model' AND bot_id = ?");
        $stmt->execute([$bot_id]);
        $current_gapgpt_model = $stmt->fetchColumn() ?: 'gemini-2.5-flash-lite';

        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'event_selection_text' AND bot_id = ?");
        $stmt->execute([$bot_id]);
        $current_event_selection_text = $stmt->fetchColumn() ?: '';
    } catch (Exception $e) {}
}

if (empty($current_webhook)) {
    require_once __DIR__ . '/../classes/LocalStore.php';
    $s = LocalStore::getInstance()->get('settings', "webhook_{$bot_id}");
    $current_webhook = $s['value'] ?? '';
}
if (empty($current_gapgpt_key)) {
    require_once __DIR__ . '/../classes/LocalStore.php';
    $s = LocalStore::getInstance()->get('settings', "gapgpt_key_{$bot_id}");
    $current_gapgpt_key = $s['value'] ?? '';
    
    $s = LocalStore::getInstance()->get('settings', "gapgpt_model_{$bot_id}");
    if ($s) $current_gapgpt_model = $s['value'] ?? 'gemini-2.5-flash-lite';
}
if (empty($current_event_selection_text)) {
    require_once __DIR__ . '/../classes/LocalStore.php';
    $s = LocalStore::getInstance()->get('settings', "event_text_{$bot_id}");
    $current_event_selection_text = $s['value'] ?? '';
}


// Determine what URL should be auto-filled
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$guessedUrl = $protocol . $host . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\') . '/webhook.php';

?>

<div class="bg-white p-5 rounded-xl border border-[#e2e8f0] mb-6">
    <h1 class="text-lg font-semibold text-[#1e293b]">تنظیمات سیستمی</h1>
</div>

<?php if ($msg): ?>
<div class="bg-[#eff6ff] border border-[#bfdbfe] text-[#1d4ed8] px-4 py-3 rounded-lg mb-6 text-sm font-medium"><?= $msg ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-[#e2e8f0]">
        <div class="p-5 border-b border-[#f1f5f9]">
            <h2 class="text-base font-semibold text-[#1e293b]">تنظیمات وب‌هوک (اتصال بات)</h2>
        </div>
        <div class="p-5">
            <form method="POST">
                <div class="mb-5">
                    <label class="block text-sm font-medium text-[#475569] mb-2">آدرس فایل `webhook.php` سرور شما:</label>
                    <input type="url" name="webhook_url" value="<?= $current_webhook ?: $guessedUrl ?>" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr" required>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" name="set_webhook" class="bg-[#2563eb] hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-[13px] transition-colors text-center w-full sm:w-auto">SET Webhook</button>
                    <button type="submit" name="delete_webhook" class="bg-white hover:bg-gray-50 border border-[#e2e8f0] text-red-600 hover:text-red-700 font-medium py-2 px-4 rounded-lg text-[13px] transition-colors text-center w-full sm:w-auto">Delete Webhook</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-[#e2e8f0]">
        <div class="p-5 border-b border-[#f1f5f9]">
            <h2 class="text-base font-semibold text-[#1e293b]">تغییر رمز عبور مدیر</h2>
        </div>
        <div class="p-5">
            <form method="POST">
                <div class="mb-5">
                    <label class="block text-sm font-medium text-[#475569] mb-2">رمز عبور جدید</label>
                    <input type="password" name="new_pass" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 text-left font-mono tracking-widest" dir="ltr" required>
                </div>
                <button type="submit" name="change_pass" class="bg-white hover:bg-gray-50 border border-[#e2e8f0] text-[#10b981] font-medium py-2 px-6 rounded-lg text-[13px] transition-colors">ذخیره رمز جدید</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-[#e2e8f0]">
        <div class="p-5 border-b border-[#f1f5f9]">
            <h2 class="text-base font-semibold text-[#1e293b]">تنظیمات هوش مصنوعی GapGPT</h2>
        </div>
        <div class="p-5">
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-[#475569] mb-2">کلید دسترسی (API Key)</label>
                    <input type="text" name="gapgpt_api_key" value="<?= htmlspecialchars($current_gapgpt_key) ?>" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr" placeholder="sk-...">
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-medium text-[#475569] mb-2">مدل هوش مصنوعی</label>
                    <select name="gapgpt_model" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500">
                        <option value="gemini-2.5-flash" <?= $current_gapgpt_model == 'gemini-2.5-flash' ? 'selected' : '' ?>>Gemini 2.5 Flash (جدید)</option>
                        <option value="gemini-2.5-flash-lite" <?= $current_gapgpt_model == 'gemini-2.5-flash-lite' ? 'selected' : '' ?>>Gemini 2.5 Flash Lite</option>
                        <option value="gemini-2.0-flash-lite" <?= $current_gapgpt_model == 'gemini-2.0-flash-lite' ? 'selected' : '' ?>>Gemini 2.0 Flash Lite</option>
                        <option value="gpt-4o-mini" <?= $current_gapgpt_model == 'gpt-4o-mini' ? 'selected' : '' ?>>GPT-4o Mini</option>
                        <option value="gpt-4o" <?= $current_gapgpt_model == 'gpt-4o' ? 'selected' : '' ?>>GPT-4o</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" name="save_gapgpt" class="bg-[#2563eb] hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg text-[13px] transition-colors">ذخیره</button>
                    <button type="submit" name="test_gapgpt" class="bg-white hover:bg-gray-50 border border-[#e2e8f0] text-gray-700 font-medium py-2 px-6 rounded-lg text-[13px] transition-colors">تست اتصال 🔌</button>
                </div>
            </form>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-[#e2e8f0]">
        <div class="p-5 border-b border-[#f1f5f9]">
            <h2 class="text-base font-semibold text-[#1e293b]">متن انتخاب رویداد</h2>
        </div>
        <div class="p-5">
            <form method="POST">
                <div class="mb-5">
                    <label class="block text-sm font-medium text-[#475569] mb-2">متن ارسال شده برای انتخاب رویدادها</label>
                    <textarea name="event_selection_text" rows="3" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" placeholder="سلام 👋&#10;به سامانه ثبتنام خوش آمدید.&#10;برای شروع، لطفاً رویداد موردنظر خود را انتخاب کنید."><?= htmlspecialchars($current_event_selection_text) ?></textarea>
                </div>
                <button type="submit" name="save_event_selection" class="bg-[#2563eb] hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg text-[13px] transition-colors">ذخیره</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
