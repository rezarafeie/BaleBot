<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/EventManager.php';
require_once __DIR__ . '/../classes/BroadcastManager.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance()->getConnection();
$em = new EventManager();
$bm = new BroadcastManager();

$events = $em->getAllEvents();
$media_list = $db->query("SELECT * FROM media_files ORDER BY id DESC")->fetchAll();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_broadcast'])) {
    $target_type = $_POST['target_type'];
    $target_event_id = $_POST['target_event_id'] ?: null;
    $message_text = $_POST['message_text'];
    $media_id = $_POST['media_id'] ?: null;

    $broadcast_id = $bm->createBroadcast($target_type, $target_event_id, $message_text, $media_id);
    // Process sync for simplicity (ideally this should be dispatched via cron for large lists to avoid timeout)
    $bm->processBroadcast($broadcast_id);
    
    $msg = "پیام با موفقیت در صف ارسال قرار گرفت و ارسال شد.";
}

$history = $db->query("SELECT b.*, e.title as event_title FROM broadcasts b LEFT JOIN events e ON b.target_event_id = e.id ORDER BY b.id DESC LIMIT 20")->fetchAll();
?>

<div class="bg-white p-5 rounded-xl border border-[#e2e8f0] mb-6">
    <h1 class="text-lg font-semibold text-[#1e293b]">ارسال پیام همگانی</h1>
</div>

<?php if ($msg): ?>
<div class="bg-[#dcfce7] border border-[#bbf7d0] text-[#166534] px-4 py-3 rounded-lg mb-6 text-sm font-medium"><?= $msg ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Form -->
    <div class="bg-white rounded-xl border border-[#e2e8f0]">
        <div class="p-5 border-b border-[#f1f5f9]">
            <h2 class="text-base font-semibold text-[#1e293b]">ایجاد پیام جدید</h2>
        </div>
        <div class="p-5">
            <form method="POST">
                <input type="hidden" name="send_broadcast" value="1">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-[#475569] mb-2">ارسال به:</label>
                    <select name="target_type" id="target_type" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500" onchange="document.getElementById('event_selector').style.display = this.value === 'event' ? 'block' : 'none';">
                        <option value="all">همه کاربران بات</option>
                        <option value="event">ثبت‌نام کنندگان رویداد خاص</option>
                    </select>
                </div>

                <div class="mb-4" id="event_selector" style="display:none;">
                    <label class="block text-sm font-medium text-[#475569] mb-2">انتخاب رویداد:</label>
                    <select name="target_event_id" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500">
                        <option value="">-- انتخاب کنید --</option>
                        <?php foreach ($events as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-[#475569] mb-2">متن پیام (اجباری)</label>
                    <textarea name="message_text" rows="5" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500" required placeholder="سلام، پیام شما..."></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-[#475569] mb-2">پیوست رسانه (اختیاری)</label>
                    <select name="media_id" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500">
                        <option value="">بدون رسانه</option>
                        <?php foreach ($media_list as $m): ?>
                            <option value="<?= $m['id'] ?>">رویداد: <?= htmlspecialchars($m['title']) ?> [<?= $m['file_type'] ?>]</option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[11px] text-[#94a3b8] mt-2">فایل‌ها را ابتدا از بخش مدیریت رسانه آپلود کنید.</p>
                </div>

                <button type="submit" onclick="return confirm('آیا از ارسال پیام در لحظه اطمینان دارید؟');" class="w-full bg-[#2563eb] hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg text-[13px] transition-colors">
                    ارسال پیام
                </button>
            </form>
        </div>
    </div>

    <!-- History -->
    <div class="bg-white rounded-xl border border-[#e2e8f0] flex flex-col h-full overflow-hidden">
        <div class="p-5 border-b border-[#f1f5f9]">
            <h2 class="text-base font-semibold text-[#1e293b]">تاریخچه ارسال‌ها (۲۰ مورد اخیر)</h2>
        </div>
        <div class="overflow-y-auto max-h-[500px]">
            <table class="w-full text-right text-[13px] border-collapse">
                <thead class="bg-[#f8fafc] text-[#64748b] font-semibold sticky top-0">
                    <tr>
                        <th class="p-3.5 border-b border-[#f1f5f9]">شناسه</th>
                        <th class="p-3.5 border-b border-[#f1f5f9]">هدف</th>
                        <th class="p-3.5 border-b border-[#f1f5f9]">وضعیت</th>
                        <th class="p-3.5 border-b border-[#f1f5f9]">موفق / ناموفق</th>
                        <th class="p-3.5 border-b border-[#f1f5f9]">تاریخ</th>
                    </tr>
                </thead>
                <tbody class="text-[#334155]">
                    <?php foreach ($history as $h): ?>
                    <tr class="border-b border-[#f8fafc] hover:bg-gray-50 transition-colors">
                        <td class="p-3.5"><?= $h['id'] ?></td>
                        <td class="p-3.5">
                            <?= $h['target_type'] == 'all' ? 'همه' : 'رویداد: ' . htmlspecialchars($h['event_title'] ?? '') ?>
                        </td>
                        <td class="p-3.5">
                            <?php if ($h['status'] == 'completed'): ?>
                                <span class="bg-[#ecfdf5] text-[#059669] px-2 py-0.5 rounded text-[11px] font-medium">پایان‌یافته</span>
                            <?php elseif ($h['status'] == 'sending'): ?>
                                <span class="bg-[#fffbeb] text-[#d97706] px-2 py-0.5 rounded text-[11px] font-medium">در حال ارسال</span>
                            <?php else: ?>
                                <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-[11px] font-medium">در صف</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3.5 font-mono" dir="ltr">
                            <span class="text-[#059669]"><?= $h['sent_count'] ?></span> / <span class="text-red-500"><?= $h['failed_count'] ?></span>
                        </td>
                        <td class="p-3.5 text-[#64748b]"><?= explode(' ', $h['created_at'])[0] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($history)): ?>
                    <tr><td colspan="5" class="p-6 text-center text-[#64748b]">تاریخچه‌ای وجود ندارد.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
