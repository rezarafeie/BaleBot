<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/Database.php';

$dbInstance = Database::getInstance();
$db = $dbInstance->getConnection();
$bot_id = $_SESSION['selected_bot_id'] ?? 1;

$total_users = 0; $total_events = 0; $total_regs = 0; $today_regs = 0; $recent = [];

if ($db) {
    try {
        $total_users = $db->prepare("SELECT COUNT(*) FROM bot_users WHERE bot_id = ?");
        $total_users->execute([$bot_id]);
        $total_users = $total_users->fetchColumn();

        $total_events = $db->prepare("SELECT COUNT(*) FROM events WHERE bot_id = ?");
        $total_events->execute([$bot_id]);
        $total_events = $total_events->fetchColumn();

        $total_regs = $db->prepare("SELECT COUNT(*) FROM registrations WHERE bot_id = ?");
        $total_regs->execute([$bot_id]);
        $total_regs = $total_regs->fetchColumn();

        $today_regs = $db->prepare("SELECT COUNT(*) FROM registrations WHERE DATE(created_at) = CURDATE() AND bot_id = ?");
        $today_regs->execute([$bot_id]);
        $today_regs = $today_regs->fetchColumn();

        $stmt = $db->prepare("SELECT r.*, e.title as event_title, u.name as user_name FROM registrations r JOIN events e ON r.event_id = e.id LEFT JOIN bot_users u ON r.chat_id = u.chat_id AND r.bot_id = u.bot_id WHERE r.bot_id = ? ORDER BY r.id DESC LIMIT 5");
        $stmt->execute([$bot_id]);
        $recent = $stmt->fetchAll();
    } catch (Exception $e) { $db = null; }
}

if (!$db): ?>
<div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <?= render_icon('exclamation-triangle', 'text-red-500') ?>
        </div>
        <div class="mr-3">
            <p class="text-sm text-red-700 font-bold">پایگاه داده متصل نیست!</p>
            <p class="text-xs text-red-600 mt-1">اطلاعات نمایش داده شده ممکن است ناقص باشد یا بارگذاری نشود. لطفاً تنظیمات پایگاه داده را بررسی کنید.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white p-5 rounded-xl border border-[#e2e8f0]">
        <div class="text-xs text-[#64748b] mb-2">کل کاربران بات</div>
        <div class="text-2xl font-bold text-[#1e293b]"><?= number_format($total_users) ?></div>
        <div class="text-[11px] text-[#10b981] mt-1">تعداد یکتا</div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-[#e2e8f0]">
        <div class="text-xs text-[#64748b] mb-2">ثبت‌نام‌های امروز</div>
        <div class="text-2xl font-bold text-[#1e293b]"><?= number_format($today_regs) ?></div>
        <div class="text-[11px] text-[#3b82f6] mt-1">امروز</div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-[#e2e8f0]">
        <div class="text-xs text-[#64748b] mb-2">کل ثبت‌نام‌ها</div>
        <div class="text-2xl font-bold text-[#1e293b]"><?= number_format($total_regs) ?></div>
        <div class="text-[11px] text-[#64748b] mt-1">مجموع آمار مجاز</div>
    </div>
    <div class="bg-white p-5 rounded-xl border border-[#e2e8f0]">
        <div class="text-xs text-[#64748b] mb-2">رویدادها</div>
        <div class="text-2xl font-bold text-[#1e293b]"><?= number_format($total_events) ?></div>
        <div class="text-[11px] text-[#f59e0b] mt-1">فعال در سایت</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-[#e2e8f0] overflow-hidden">
            <div class="p-5 border-b border-[#f1f5f9] flex justify-between items-center">
                <h2 class="text-base font-semibold text-[#1e293b]">آخرین ثبت‌نام‌ها</h2>
                <a href="registrations.php" class="text-xs text-blue-600 hover:text-blue-800">مشاهده همه</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right text-[13px]">
                    <thead class="bg-[#f8fafc] text-[#64748b] font-semibold">
                        <tr>
                            <th class="p-3 border-b border-[#f1f5f9]">شناسه</th>
                            <th class="p-3 border-b border-[#f1f5f9]">رویداد</th>
                            <th class="p-3 border-b border-[#f1f5f9]">کاربر</th>
                            <th class="p-3 border-b border-[#f1f5f9]">تاریخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent) > 0): ?>
                            <?php foreach ($recent as $r): ?>
                                <tr class="border-b border-[#f8fafc]">
                                    <td class="p-3.5"><?= $r['id'] ?></td>
                                    <td class="p-3.5"><?= htmlspecialchars($r['event_title']) ?></td>
                                    <td class="p-3.5"><?= htmlspecialchars($r['user_name'] ?? $r['chat_id']) ?></td>
                                    <td class="p-3.5"><?= explode(' ', $r['created_at'])[0] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-5 text-center text-gray-500">موردی یافت نشد.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl border border-[#e2e8f0] flex flex-col h-full">
            <div class="p-5 border-b border-[#f1f5f9]">
                <h2 class="text-base font-semibold text-[#1e293b]">عملیات سریع</h2>
            </div>
            <div class="p-5 flex flex-col gap-4">
                <a href="event-edit.php" class="w-full text-center bg-[#f1f5f9] text-[#475569] hover:bg-[#e2e8f0] py-2.5 rounded-lg text-[13px] font-semibold transition-colors">
                    + ایجاد رویداد جدید
                </a>
                <a href="broadcast.php" class="w-full text-center bg-[#f1f5f9] text-[#475569] hover:bg-[#e2e8f0] py-2.5 rounded-lg text-[13px] font-semibold transition-colors">
                    ارسال پیام انبوه
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
