<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/BaleBot.php';
require_once __DIR__ . '/../classes/Auth.php';

$auth = new Auth();
$db = Database::getInstance()->getConnection();
$bot = new BaleBot();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_webhook'])) {
        $url = $_POST['webhook_url'];
        $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'webhook_url'")->execute([$url]);
        $res = $bot->setWebhook($url . "?secret=" . WEBHOOK_SECRET);
        $msg = "وب‌هوک ست شد. وضعیت: " . (isset($res['ok']) && $res['ok'] ? 'موفق' : 'ناموفق');
    } elseif (isset($_POST['delete_webhook'])) {
        $res = $bot->deleteWebhook();
        $db->prepare("UPDATE settings SET setting_value = '' WHERE setting_key = 'webhook_url'")->execute();
        $msg = "وب‌هوک غیرفعال شد. وضعیت: " . (isset($res['ok']) && $res['ok'] ? 'موفق' : 'ناموفق');
    } elseif (isset($_POST['change_pass'])) {
        if (!empty($_POST['new_pass'])) {
            $auth->updatePassword($_POST['new_pass']);
            $msg = "رمز عبور مدیر تغییر کرد.";
        }
    } elseif (isset($_POST['save_gapgpt'])) {
        $key = $_POST['gapgpt_api_key'] ?? '';
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gapgpt_api_key', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $key]);
        $msg = "کلید API GapGPT ذخیره شد.";
    } elseif (isset($_POST['save_event_selection'])) {
        $text = $_POST['event_selection_text'] ?? '';
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('event_selection_text', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$text, $text]);
        $msg = "متن پیش‌فرض انتخاب رویداد ذخیره شد.";
    }
}

// Get current setting
$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'webhook_url'");
$current_webhook = $stmt->fetchColumn() ?: '';

$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'gapgpt_api_key'");
$current_gapgpt_key = $stmt->fetchColumn() ?: '';

$stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'event_selection_text'");
$current_event_selection_text = $stmt->fetchColumn() ?: '';

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
                <div class="mb-5">
                    <label class="block text-sm font-medium text-[#475569] mb-2">کلید دسترسی (API Key)</label>
                    <input type="text" name="gapgpt_api_key" value="<?= htmlspecialchars($current_gapgpt_key) ?>" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 text-left font-mono" dir="ltr" placeholder="sk-...">
                </div>
                <button type="submit" name="save_gapgpt" class="bg-[#2563eb] hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg text-[13px] transition-colors">ذخیره</button>
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
