<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/EventManager.php';
require_once __DIR__ . '/../classes/MediaManager.php';

$em = new EventManager();
$mm = new MediaManager();
$mediaList = $mm->getAllMedia();
$event_id = $_GET['id'] ?? null;
if (!$event_id) die("Event ID missing");

$event = $em->getEvent($event_id);

if (isset($_GET['delete'])) {
    $em->deleteField($_GET['delete']);
    echo "<script>window.location='event-fields.php?id=$event_id';</script>";
    exit;
}

if (isset($_GET['toggle'])) {
    $em->toggleFieldActive($_GET['toggle']);
    echo "<script>window.location='event-fields.php?id=$event_id';</script>";
    exit;
}

$edit_field_id = $_GET['edit'] ?? null;
$editing_field = null;
if ($edit_field_id) {
    $editing_field = $em->getEventField($edit_field_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_field' || $_POST['action'] === 'edit_field') {
        $options_json = null;
        if (in_array($_POST['type'], ['dropdown', 'channel_membership']) && !empty($_POST['options_text'])) {
            $opts = array_map('trim', explode("\n", $_POST['options_text']));
            $opts = array_filter($opts);
            if (!empty($opts)) {
                $options_json = json_encode(array_values($opts), JSON_UNESCAPED_UNICODE);
            }
        }
        
        $data = [
            'label' => $_POST['label'],
            'field_key' => $_POST['field_key'],
            'type' => $_POST['type'],
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_ai_generated' => isset($_POST['is_ai_generated']) ? 1 : 0,
            'sort_order' => $_POST['sort_order'],
            'validation_rule' => '', // simplified
            'help_text' => $_POST['help_text'],
            'error_message' => $_POST['error_message'],
            'media_path' => null,
            'media_id' => $_POST['media_id'] ?: null,
            'options_json' => $options_json
        ];

        if ($_POST['action'] === 'edit_field' && $edit_field_id) {
            $em->updateField($edit_field_id, $data);
        } else {
            $em->addField($event_id, $data);
        }
        
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
            <div class="p-5 border-b border-[#f1f5f9] flex justify-between items-center">
                <h3 class="text-base font-semibold text-[#1e293b]"><?= $editing_field ? 'ویرایش فیلد' : 'افزودن فیلد جدید' ?></h3>
                <?php if ($editing_field): ?>
                    <a href="?id=<?= $event_id ?>" class="text-sm text-blue-600 hover:text-blue-800">لغو ویرایش</a>
                <?php endif; ?>
            </div>
            <div class="p-5">
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $editing_field ? 'edit_field' : 'add_field' ?>">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">نوع فیلد</label>
                        <select name="type" id="field_type" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                            <option value="text" <?= ($editing_field['type']??'') === 'text' ? 'selected' : '' ?>>متن کوتاه</option>
                            <option value="message" <?= ($editing_field['type']??'') === 'message' ? 'selected' : '' ?>>پیام نمایشی (ساده - بدون دریافت پاسخ)</option>
                            <option value="dropdown" <?= ($editing_field['type']??'') === 'dropdown' ? 'selected' : '' ?>>لیست کشویی (دکمه‌های شیشه‌ای)</option>
                            <option value="number" <?= ($editing_field['type']??'') === 'number' ? 'selected' : '' ?>>عدد</option>
                            <option value="contact" <?= ($editing_field['type']??'') === 'contact' ? 'selected' : '' ?>>دریافت شماره تماس (دکمه شیشه‌ای بله)</option>
                            <option value="photo" <?= ($editing_field['type']??'') === 'photo' ? 'selected' : '' ?>>تصویر</option>
                            <option value="document" <?= ($editing_field['type']??'') === 'document' ? 'selected' : '' ?>>فایل</option>
                            <option value="channel_membership" <?= ($editing_field['type']??'') === 'channel_membership' ? 'selected' : '' ?>>عضویت در کانال</option>
                        </select>
                    </div>

                    <?php 
                        $optsText = '';
                        if ($editing_field && in_array($editing_field['type'], ['dropdown', 'channel_membership']) && !empty($editing_field['options_json'])) {
                            $optsArray = json_decode($editing_field['options_json'], true) ?: [];
                            $optsText = implode("\n", $optsArray);
                        }
                    ?>
                    <div class="mb-4 <?= in_array(($editing_field['type']??''), ['dropdown', 'channel_membership']) ? '' : 'hidden' ?>" id="options_text_container">
                        <label class="block text-sm font-medium text-[#475569] mb-2" id="options_label">گزینه‌ها</label>
                        <textarea name="options_text" id="options_text" rows="3" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" placeholder="گزینه‌ها را وارد کنید"><?= htmlspecialchars($optsText) ?></textarea>
                        <p id="options_hint" class="text-xs text-[#64748b] mt-1"></p>
                    </div>

                    <script>
                    function updateOptionsDisplay() {
                        const type = document.getElementById('field_type').value;
                        const container = document.getElementById('options_text_container');
                        const label = document.getElementById('options_label');
                        const hint = document.getElementById('options_hint');
                        const aiOption = document.getElementById('ai_option_container');
                        
                        if (type === 'dropdown') {
                            container.classList.remove('hidden');
                            label.innerText = 'گزینه‌ها (هر خط یک گزینه)';
                            hint.innerText = '';
                        } else if (type === 'channel_membership') {
                            container.classList.remove('hidden');
                            label.innerText = 'شناسه یا آیدی کانال';
                            hint.innerText = 'مثال: @mychannel یا -100123456789. ربات باید در کانال ادمین باشد.';
                        } else {
                            container.classList.add('hidden');
                        }

                        if (type === 'message') {
                            aiOption.classList.remove('hidden');
                        } else {
                            aiOption.classList.add('hidden');
                        }
                    }
                    document.getElementById('field_type').addEventListener('change', updateOptionsDisplay);
                    updateOptionsDisplay();
                    </script>

                    <div class="mb-4" id="ai_option_container">
                        <div class="flex items-center p-3 bg-blue-50 rounded-lg border border-blue-100">
                             <input type="checkbox" name="is_ai_generated" id="is_ai_generated" <?= (!empty($editing_field['is_ai_generated'])) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 bg-white border-blue-200 rounded focus:ring-blue-500 focus:ring-2">
                             <label for="is_ai_generated" class="mr-2 text-xs font-semibold text-blue-800 cursor-pointer">پیام توسط هوش مصنوعی تولید شود ✨</label>
                        </div>
                        <p class="text-[10px] text-blue-600 mt-1 mr-1">در صورت فعال بودن، متن سوال بصورت پرامپت به هوش مصنوعی ارسال می‌شود.</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">عنوان (نمایش در فرم)</label>
                        <input type="text" name="label" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" required placeholder="مثال: نام و نام خانوادگی" value="<?= htmlspecialchars($editing_field['label'] ?? '') ?>">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">کلید دیتابیس (انگلیسی)</label>
                        <input type="text" name="field_key" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors text-left font-mono" dir="ltr" required placeholder="fullname" value="<?= htmlspecialchars($editing_field['field_key'] ?? '') ?>">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">متن سوال (پیام ربات)</label>
                        <textarea name="help_text" rows="2" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" placeholder="لطفاً نام و نام خانوادگی خود را وارد کنید:"><?= htmlspecialchars($editing_field['help_text'] ?? '') ?></textarea>
                        <p class="text-xs text-[#64748b] mt-1">راهنما: از فیلدهای قبلی استفاده کنید. مثلا: <code>{firstname}</code> عزیز، حالا کد ملیت رو وارد کن.</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">پیام خطای اعتبارسنجی</label>
                        <input type="text" name="error_message" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" placeholder="خطا، لطفا مجدد وارد کنید" value="<?= htmlspecialchars($editing_field['error_message'] ?? '') ?>">
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-[#475569] mb-2">ترتیب نمایش</label>
                        <input type="number" name="sort_order" value="<?= $editing_field['sort_order'] ?? (count($fields) * 10 + 10) ?>" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-[#475569] mb-2">رسانه فیلد (اختیاری)</label>
                        <select name="media_id" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                            <option value="">بدون رسانه</option>
                            <?php foreach ($mediaList as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($editing_field['media_id']??'') == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['title'] ?: $m['file_path']) ?> (<?= $m['file_type'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex gap-4 mb-6">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_required" id="is_required" <?= (!isset($editing_field) || $editing_field['is_required']) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 bg-[#f8fafc] border-[#e2e8f0] rounded focus:ring-blue-500 focus:ring-2">
                            <label for="is_required" class="mr-2 text-sm font-medium text-[#334155] cursor-pointer">پاسخ اجباری است</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" <?= (!isset($editing_field) || $editing_field['is_active']) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 bg-[#f8fafc] border-[#e2e8f0] rounded focus:ring-blue-500 focus:ring-2">
                            <label for="is_active" class="mr-2 text-sm font-medium text-[#334155] cursor-pointer">فیلد فعال است</label>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-[#3b82f6] hover:bg-blue-600 text-white font-medium py-2.5 rounded-lg text-[13px] transition-colors shadow-sm">
                        <?= $editing_field ? 'بروزرسانی فیلد' : 'ذخیره فیلد' ?>
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
                            <th class="p-4 border-b border-[#f1f5f9]">وضعیت</th>
                            <th class="p-4 border-b border-[#f1f5f9]">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="text-[#334155]">
                        <?php foreach ($fields as $f): ?>
                        <tr class="border-b border-[#f8fafc] hover:bg-gray-50 transition-colors <?= empty($f['is_active']) ? 'opacity-60' : '' ?>">
                            <td class="p-4 text-center w-12"><?= $f['sort_order'] ?></td>
                            <td class="p-4 font-medium text-[#1e293b]">
                                <?= htmlspecialchars($f['label']) ?>
                                <?php if ($f['is_required']): ?>
                                    <span class="text-red-500 text-xs mr-1" title="اجباری">*</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 font-mono text-xs text-[#64748b]" dir="ltr"><?= $f['field_key'] ?></td>
                            <td class="p-4">
                                <span class="bg-[#f1f5f9] text-[#475569] px-2 py-1 rounded text-xs"><?= $f['type'] ?></span>
                            </td>
                            <td class="p-4 text-center">
                                <?php if (!empty($f['is_active'])): ?>
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs">فعال</span>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs">غیرفعال</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="?id=<?= $event_id ?>&edit=<?= $f['id'] ?>" class="text-blue-500 hover:text-blue-700 p-1 rounded hover:bg-blue-50 transition-colors inline-block" title="ویرایش">
                                        <?= render_icon('pencil') ?>
                                    </a>
                                    <a href="?id=<?= $event_id ?>&toggle=<?= $f['id'] ?>" class="text-gray-500 hover:text-gray-700 p-1 rounded hover:bg-gray-50 transition-colors inline-block" title="<?= !empty($f['is_active']) ? 'غیرفعال کردن' : 'فعال کردن' ?>">
                                        <?= render_icon(!empty($f['is_active']) ? 'eye-slash' : 'eye') ?>
                                    </a>
                                    <a href="?id=<?= $event_id ?>&delete=<?= $f['id'] ?>" onclick="return confirm('آیا از حذف این فیلد اطمینان دارید؟')" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition-colors inline-block" title="حذف">
                                        <?= render_icon('trash') ?>
                                    </a>
                                </div>
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
