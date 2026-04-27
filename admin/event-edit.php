<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/EventManager.php';

$em = new EventManager();
$id = $_GET['id'] ?? null;

$event = [
    'title' => '', 'slug' => '', 'description' => '', 'welcome_message' => '',
    'completion_message' => '', 'duplicate_message' => '', 'is_active' => 1, 'duplicate_setting' => 'allow'
];

if ($id) {
    $event = $em->getEvent($id);
    if (!$event) die("Event not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'],
        'slug' => $_POST['slug'] ?: uniqid('ev_'),
        'description' => $_POST['description'],
        'welcome_message' => $_POST['welcome_message'],
        'completion_message' => $_POST['completion_message'],
        'duplicate_message' => $_POST['duplicate_message'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'duplicate_setting' => $_POST['duplicate_setting']
    ];

    if ($id) {
        $em->updateEvent($id, $data);
    } else {
        $id = $em->createEvent($data);
    }
    echo "<script>window.location='events.php';</script>";
    exit;
}
?>

<div class="mb-6 flex items-center bg-white p-5 rounded-xl border border-[#e2e8f0]">
    <a href="events.php" class="text-[#64748b] hover:text-[#1e293b] ml-4 transition-colors">
        <i class="bi bi-arrow-right text-xl"></i>
    </a>
    <h1 class="text-lg font-semibold text-[#1e293b]"><?= $id ? 'ویرایش رویداد' : 'رویداد جدید' ?></h1>
</div>

<form method="POST" class="bg-white rounded-xl border border-[#e2e8f0] pb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-[13px] font-medium text-[#475569] mb-2">عنوان رویداد *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($event['title']) ?>" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" required>
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#475569] mb-2">شناسه یکتا (انگلیسی)</label>
                <input type="text" name="slug" value="<?= htmlspecialchars($event['slug']) ?>" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors text-left" dir="ltr">
            </div>
        </div>

        <div class="mb-8">
            <label class="block text-[13px] font-medium text-[#475569] mb-2">توضیحات داخلی</label>
            <textarea name="description" rows="2" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors"><?= htmlspecialchars($event['description']) ?></textarea>
        </div>

        <div class="border-t border-[#f1f5f9] mb-6"></div>

        <h3 class="text-base font-semibold text-[#1e293b] mb-5">پیام‌های ربات</h3>
        
        <div class="mb-5">
            <label class="block text-[13px] font-medium text-[#475569] mb-2">پیام خوش‌آمد (شروع رویه)</label>
            <textarea name="welcome_message" rows="3" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors"><?= htmlspecialchars($event['welcome_message']) ?></textarea>
        </div>
        
        <div class="mb-5">
            <label class="block text-[13px] font-medium text-[#475569] mb-2">پیام پایان (تکمیل ثبت‌نام)</label>
            <textarea name="completion_message" rows="3" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors"><?= htmlspecialchars($event['completion_message']) ?></textarea>
        </div>

        <div class="mb-8">
            <label class="block text-[13px] font-medium text-[#475569] mb-2">پیام خطای تکراری</label>
            <textarea name="duplicate_message" rows="2" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors"><?= htmlspecialchars($event['duplicate_message']) ?></textarea>
        </div>

        <div class="border-t border-[#f1f5f9] mb-6"></div>

        <h3 class="text-base font-semibold text-[#1e293b] mb-5">تنظیمات</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-[13px] font-medium text-[#475569] mb-2">بررسی تکراری بودن کاربر</label>
                <select name="duplicate_setting" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                    <option value="allow" <?= $event['duplicate_setting']=='allow'?'selected':'' ?>>اجازه ثبت‌نام مجدد</option>
                    <option value="block_chat_id" <?= $event['duplicate_setting']=='block_chat_id'?'selected':'' ?>>جلوگیری بر اساس شناسه کاربری بله</option>
                    <option value="block_phone" <?= $event['duplicate_setting']=='block_phone'?'selected':'' ?>>جلوگیری بر اساس شماره تماس</option>
                </select>
            </div>
            <div class="flex items-center pt-8">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?= $event['is_active'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 bg-[#f8fafc] border-[#e2e8f0] rounded focus:ring-blue-500 focus:ring-2">
                <label for="is_active" class="mr-2 text-sm font-medium text-[#334155] cursor-pointer">رویداد فعال باشد</label>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-[#f1f5f9]">
            <button type="submit" class="bg-[#10b981] hover:bg-green-600 text-white font-medium py-2.5 px-6 rounded-lg text-[13px] transition-colors shadow-sm">
                بازنشانی و ذخیره تغییرات
            </button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
