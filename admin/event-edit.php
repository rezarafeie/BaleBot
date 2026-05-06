<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/EventManager.php';
require_once __DIR__ . '/../classes/MediaManager.php';

$em = new EventManager();
$mm = new MediaManager();
$mediaList = $mm->getAllMedia();

$id = $_GET['id'] ?? null;

$event = [
    'title' => '', 'slug' => '', 'description' => '', 'welcome_message' => '', 'welcome_media_id' => null,
    'completion_message' => '', 'completion_media_id' => null, 'duplicate_message' => '', 'is_active' => 1, 'duplicate_setting' => 'allow',
    'use_ai' => 0, 'ai_prompt' => '', 'ai_wait_message' => '', 'ai_wait_media_id' => null
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
        'welcome_media_id' => $_POST['welcome_media_id'] ?: null,
        'completion_message' => $_POST['completion_message'],
        'completion_media_id' => $_POST['completion_media_id'] ?: null,
        'duplicate_message' => $_POST['duplicate_message'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'duplicate_setting' => $_POST['duplicate_setting'],
        'use_ai' => isset($_POST['use_ai']) ? 1 : 0,
        'ai_prompt' => $_POST['ai_prompt'] ?? '',
        'ai_wait_message' => $_POST['ai_wait_message'] ?? '',
        'ai_wait_media_id' => $_POST['ai_wait_media_id'] ?? null,
        'action_type' => $_POST['action_type'] ?? 'none',
        'action_webhook_url' => $_POST['action_webhook_url'] ?? '',
        'action_webhook_body' => $_POST['action_webhook_body'] ?? '',
        'action_http_url' => $_POST['action_http_url'] ?? '',
        'platforms' => $_POST['platforms'] ?? ['bale'],
        'next_event_id' => $_POST['next_event_id'] ?: null
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
        <?= render_icon('arrow-right', 'text-xl') ?>
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

        <h3 class="text-base font-semibold text-[#1e293b] mb-5">تنظیمات انتشار</h3>
        <div class="mb-8">
            <label class="block text-[13px] font-medium text-[#475569] mb-3">پیام‌رسان‌های فعال برای این رویداد</label>
            <div class="flex flex-wrap gap-6 p-4 bg-[#f8fafc] rounded-lg border border-[#e2e8f0]">
                <?php 
                $active_platforms = json_decode($event['platforms'] ?? '["bale"]', true) ?: ['bale'];
                $available_platforms = [
                    'bale' => 'بله',
                    'telegram' => 'تلگرام',
                    'rubika' => 'روبیکا'
                ];
                foreach ($available_platforms as $key => $label): ?>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="platforms[]" value="<?= $key ?>" <?= in_array($key, $active_platforms) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 bg-white border-[#e2e8f0] rounded focus:ring-blue-500">
                    <span class="text-sm text-[#334155]"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="text-[11px] text-[#64748b] mt-2">توجه: توکن هر پیام‌رسان باید در بخش مدیریت بات‌ها تنظیم شده باشد.</p>
        </div>

        <div class="border-t border-[#f1f5f9] mb-6"></div>

        <h3 class="text-base font-semibold text-[#1e293b] mb-5">پیام‌های ربات</h3>
        
        <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[13px] font-medium text-[#475569] mb-2">پیام خوش‌آمد (شروع رویه)</label>
                <textarea name="welcome_message" rows="3" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors"><?= htmlspecialchars($event['welcome_message']) ?></textarea>
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#475569] mb-2">رسانه خوش‌آمد (تصویر/فایل)</label>
                <select name="welcome_media_id" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                    <option value="">بدون رسانه</option>
                    <?php foreach ($mediaList as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($event['welcome_media_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['title'] ?: $m['file_path']) ?> (<?= $m['file_type'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[13px] font-medium text-[#475569] mb-2">پیام پایان (تکمیل ثبت‌نام)</label>
                <textarea name="completion_message" rows="3" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors"><?= htmlspecialchars($event['completion_message']) ?></textarea>
            </div>
            <div>
                <label class="block text-[13px] font-medium text-[#475569] mb-2">رسانه پایان</label>
                <select name="completion_media_id" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                    <option value="">بدون رسانه</option>
                    <?php foreach ($mediaList as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= ($event['completion_media_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['title'] ?: $m['file_path']) ?> (<?= $m['file_type'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-8">
            <label class="block text-[13px] font-medium text-[#475569] mb-2">پیام خطای تکراری</label>
            <textarea name="duplicate_message" rows="2" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors"><?= htmlspecialchars($event['duplicate_message']) ?></textarea>
        </div>

        <div class="border-t border-[#f1f5f9] mb-6"></div>

        <h3 class="text-base font-semibold text-[#1e293b] mb-5">تنظیمات هوش مصنوعی (GapGPT)</h3>

        <div class="mb-5">
            <div class="flex items-center mb-4">
                <input type="checkbox" name="use_ai" id="use_ai" value="1" <?= $event['use_ai'] ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 bg-[#f8fafc] border-[#e2e8f0] rounded focus:ring-blue-500 focus:ring-2">
                <label for="use_ai" class="mr-2 text-sm font-medium text-[#334155] cursor-pointer">تحلیل داده‌های کاربری با هوش مصنوعی در پایان ثبت‌نام</label>
            </div>
            
            <div id="ai_prompt_container" class="<?= $event['use_ai'] ? '' : 'hidden' ?>">
                <div class="mb-4">
                    <label class="block text-[13px] font-medium text-[#475569] mb-2">پرامپت (دستورالعمل) هوش مصنوعی</label>
                    <textarea name="ai_prompt" rows="3" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" placeholder="مثال: بر اساس داده‌های دریافت شده زیر، یک رژیم غذایی مناسب برای شخص پیشنهاد بده."><?= htmlspecialchars($event['ai_prompt'] ?? '') ?></textarea>
                    <p class="text-xs text-[#64748b] mt-1">راهنما: اطلاعات وارد شده توسط کاربر در انتهای این پرامپت به هوش مصنوعی ارسال خواهد شد. در صورت فعال بودن هوش مصنوعی، پیام پایان فرم جایگزین پاسخ هوش مصنوعی خواهد شد.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-medium text-[#475569] mb-2">متن انتظار پردازش هوش مصنوعی</label>
                        <input type="text" name="ai_wait_message" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors" placeholder="درحال پردازش اطلاعات شما با هوش مصنوعی... ⏳" value="<?= htmlspecialchars($event['ai_wait_message'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-[13px] font-medium text-[#475569] mb-2">رسانه انتظار هوش مصنوعی</label>
                        <select name="ai_wait_media_id" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                            <option value="">بدون رسانه</option>
                            <?php foreach ($mediaList as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= ($event['ai_wait_media_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['title'] ?: $m['file_path']) ?> (<?= $m['file_type'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.getElementById('use_ai').addEventListener('change', function() {
            document.getElementById('ai_prompt_container').classList.toggle('hidden', !this.checked);
        });
        </script>

        <div class="border-t border-[#f1f5f9] mb-6"></div>

        <h3 class="text-base font-semibold text-[#1e293b] mb-5">ارسال اطلاعات پس از تکمیل ثبت‌نام (ورکشاپ / وب‌هوک / API)</h3>
        
        <div class="mb-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                <div>
                    <label class="block text-[13px] font-medium text-[#475569] mb-2">نوع اقدام پشتیبان</label>
                    <select name="action_type" id="action_type" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                        <option value="none" <?= ($event['action_type'] ?? 'none') == 'none' ? 'selected' : '' ?>>بدون اقدام</option>
                        <option value="webhook" <?= ($event['action_type'] ?? 'none') == 'webhook' ? 'selected' : '' ?>>ارسال به Webhook (POST)</option>
                        <option value="http_request" <?= ($event['action_type'] ?? 'none') == 'http_request' ? 'selected' : '' ?>>ارسال HTTP Request (GET/POST سفارشی)</option>
                    </select>
                </div>
            </div>

            <div id="webhook_container" class="<?= ($event['action_type'] ?? 'none') == 'webhook' ? '' : 'hidden' ?> p-4 bg-gray-50 rounded-lg border border-gray-200 mb-4">
                <h4 class="font-medium text-sm text-gray-700 mb-3">تنظیمات وب‌هوک (Webhook)</h4>
                <div class="mb-4">
                    <label class="block text-[13px] font-medium text-[#475569] mb-2">آدرس (URL) وب‌هوک</label>
                    <input type="text" name="action_webhook_url" value="<?= htmlspecialchars($event['action_webhook_url'] ?? '') ?>" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-white transition-colors text-left" dir="ltr" placeholder="https://domain.com/webhook">
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-[#475569] mb-2">بدنه سفارشی (JSON) اختیاری</label>
                    <textarea name="action_webhook_body" rows="4" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm font-mono text-[#1e293b] focus:outline-none focus:border-blue-500 bg-white transition-colors text-left" dir="ltr" placeholder='{"receptor": "{phone}", "token": "ثبت‌نام {event_title}"}'><?= htmlspecialchars($event['action_webhook_body'] ?? '') ?></textarea>
                    <p class="text-[11px] text-[#64748b] mt-2 leading-relaxed">
                        راهنما: اگر خالی باشد کل مقادیر ثبت‌نام بصورت آرایه JSON ارسال می‌شود. می‌توانید با فرمت JSON بالا مقادیر را دلخواه تنظیم کنید.<br>
                        متغیرهای مجاز: <code>{field_key}</code> کلیدهای تعریف شده (مثل <code>{phone}</code>), <code>{chat_id}</code>, <code>{event_id}</code>, <code>{event_title}</code>
                    </p>
                </div>
            </div>

            <div id="http_request_container" class="<?= ($event['action_type'] ?? 'none') == 'http_request' ? '' : 'hidden' ?> p-4 bg-gray-50 rounded-lg border border-gray-200 mb-4">
                <h4 class="font-medium text-sm text-gray-700 mb-3">تنظیمات HTTP Request سفارشی</h4>
                <div>
                    <label class="block text-[13px] font-medium text-[#475569] mb-2">آدرس (URL) درخواست HTTP</label>
                    <textarea name="action_http_url" rows="3" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm font-mono text-[#1e293b] focus:outline-none focus:border-blue-500 bg-white transition-colors text-left" dir="ltr" placeholder="https://api.kavenegar.com/v1/API_KEY/verify/lookup.json?receptor={phone}&token={event_title}&template=webinarlogin"><?= htmlspecialchars($event['action_http_url'] ?? '') ?></textarea>
                    <p class="text-[11px] text-[#64748b] mt-2 leading-relaxed">
                        راهنما: بصورت پیش‌فرض درخواست GET ارسال می‌شود. از متغیرهای دریافتی درون URL استفاده کنید.<br>
                        متغیرهای مجاز: <code>{field_key}</code> کلیدهای تعریف شده (مثل <code>{phone}</code>), <code>{chat_id}</code>, <code>{event_id}</code>, <code>{event_title}</code>
                    </p>
                </div>
            </div>
        </div>

        <script>
        document.getElementById('action_type').addEventListener('change', function() {
            document.getElementById('webhook_container').classList.add('hidden');
            document.getElementById('http_request_container').classList.add('hidden');
            
            if (this.value === 'webhook') {
                document.getElementById('webhook_container').classList.remove('hidden');
            } else if (this.value === 'http_request') {
                document.getElementById('http_request_container').classList.remove('hidden');
            }
        });
        </script>

        <h3 class="text-base font-semibold text-[#1e293b] mb-5">تنظیمات نهایی و انتقال کاربر</h3>
        
        <div class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[13px] font-medium text-[#475569] mb-2">پس از اتمام، انتقال به رویداد...</label>
                <select name="next_event_id" class="w-full border border-[#e2e8f0] rounded-lg px-3 py-2.5 text-sm text-[#1e293b] focus:outline-none focus:border-blue-500 bg-[#f8fafc] focus:bg-white transition-colors">
                    <option value="">بدون انتقال (پایان)</option>
                    <?php 
                    $allEvents = $em->getAllEvents();
                    foreach ($allEvents as $e): if ($e['id'] == $id) continue; ?>
                        <option value="<?= $e['id'] ?>" <?= ($event['next_event_id'] ?? '') == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-[#64748b] mt-1 italic">کاربر بلافاصله پس از تکمیل این فرم، وارد رویه رویداد انتخابی خواهد شد.</p>
            </div>
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
