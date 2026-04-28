<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/EventManager.php';

$em = new EventManager();
$event_id = $_GET['id'] ?? null;
if (!$event_id) die("Event ID missing");

$event = $em->getEvent($event_id);

if (isset($_GET['delete'])) {
    $em->deleteField($_GET['delete']);
    echo "<script>window.location='event-fields.php?id=$event_id';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_field') {
        $em->addField($event_id, [
            'label' => $_POST['label'],
            'field_key' => $_POST['field_key'],
            'type' => $_POST['type'],
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'sort_order' => $_POST['sort_order'],
            'validation_rule' => '', // simplified
            'help_text' => $_POST['help_text'],
            'error_message' => $_POST['error_message'],
            'media_path' => null, // simple text fields for now
            'options_json' => null
        ]);
        echo "<script>window.location='event-fields.php?id=$event_id';</script>";
        exit;
    }
}

$fields = $em->getEventFields($event_id);
?>

<div class="mb-6 flex items-center bg-white p-5 rounded-xl border border-[#e2e8f0]">
    <a href="events.php" class="text-[#64748b] hover:text-[#1e293b] ml-4 transition-colors">
        <?= render_icon('arrow-right', 'text-xl') ?>
    </a>
    <h1 class="text-lg font-semibold text-[#1e293b]">فرم‌ساز: <?= htmlspecialchars($event['title']) ?></h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-[#e2e8f0]">
            <div class="p-5 border-b border-[#f1f5f9]">
                <h3 class="text-base font-semibold text-[#1e293b]">افزودن فیلد جدید</h3>
            </div>
            <div class="p-5">
                <form method="POST">
                    <input type="hidden" name="action" value="add_field">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">نوع فیلد</label>
                        <select name="type" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                            <option value="text">متن کوتاه</option>
                            <option value="number">عدد</option>
                            <option value="contact">دریافت شماره تماس (دکمه شیشه‌ای بله)</option>
                            <option value="photo">تصویر</option>
                            <option value="document">فایل</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">عنوان (نمایش در فرم)</label>
                        <input type="text" name="label" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" required placeholder="مثال: نام و نام خانوادگی">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">کلید دیتابیس (انگلیسی)</label>
                        <input type="text" name="field_key" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors text-left font-mono" dir="ltr" required placeholder="fullname">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">متن سوال (پیام ربات)</label>
                        <textarea name="help_text" rows="2" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" placeholder="لطفاً نام و نام خانوادگی خود را وارد کنید:"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">پیام خطای اعتبارسنجی</label>
                        <input type="text" name="error_message" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" placeholder="خطا، لطفا مجدد وارد کنید">
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-[#475569] mb-2">ترتیب نمایش</label>
                        <input type="number" name="sort_order" value="<?= count($fields) * 10 + 10 ?>" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                    </div>

                    <div class="mb-6 flex items-center">
                        <input type="checkbox" name="is_required" id="is_required" checked class="w-4 h-4 text-blue-600 bg-[#f8fafc] border-[#e2e8f0] rounded focus:ring-blue-500 focus:ring-2">
                        <label for="is_required" class="mr-2 text-sm font-medium text-[#334155] cursor-pointer">پاسخ اجباری است</label>
                    </div>

                    <button type="submit" class="w-full bg-[#3b82f6] hover:bg-blue-600 text-white font-medium py-2.5 rounded-lg text-[13px] transition-colors shadow-sm">
                        ذخیره فیلد
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-[#e2e8f0] overflow-hidden">
             <div class="p-5 border-b border-[#f1f5f9]">
                <h3 class="text-base font-semibold text-[#1e293b]">فیلدهای تعریف شده</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-[13px] border-collapse">
                    <thead class="bg-[#f8fafc] text-[#64748b] font-semibold">
                        <tr>
                            <th class="p-4 border-b border-[#f1f5f9]">ترتیب</th>
                            <th class="p-4 border-b border-[#f1f5f9]">عنوان</th>
                            <th class="p-4 border-b border-[#f1f5f9]">کلید</th>
                            <th class="p-4 border-b border-[#f1f5f9]">نوع</th>
                            <th class="p-4 border-b border-[#f1f5f9]">الزامی</th>
                            <th class="p-4 border-b border-[#f1f5f9]">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#334155]">
                        <?php foreach ($fields as $f): ?>
                        <tr class="border-b border-[#f8fafc] hover:bg-gray-50 transition-colors">
                            <td class="p-4 text-center w-12"><?= $f['sort_order'] ?></td>
                            <td class="p-4 font-medium text-[#1e293b]"><?= htmlspecialchars($f['label']) ?></td>
                            <td class="p-4 font-mono text-xs text-[#64748b]" dir="ltr"><?= $f['field_key'] ?></td>
                            <td class="p-4">
                                <span class="bg-[#f1f5f9] text-[#475569] px-2 py-1 rounded text-xs"><?= $f['type'] ?></span>
                            </td>
                            <td class="p-4 text-center">
                                <?php if ($f['is_required']): ?>
                                    <?= render_icon('check-circle-fill', 'text-[#10b981]') ?>
                                <?php else: ?>
                                    <?= render_icon('x-circle', 'text-[#cbd5e1]') ?>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <a href="?id=<?= $event_id ?>&delete=<?= $f['id'] ?>" onclick="return confirm('آیا از حذف این فیلد اطمینان دارید؟')" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition-colors inline-block">
                                    <?= render_icon('trash') ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($fields)): ?>
                        <tr><td colspan="6" class="p-8 text-center text-[#64748b]">فیلدی تعریف نشده است. فرم ثبت‌نام فعلاً خالی است.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
