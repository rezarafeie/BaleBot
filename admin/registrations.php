<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/RegistrationManager.php';
require_once __DIR__ . '/../classes/EventManager.php';

$rm = new RegistrationManager();
$em = new EventManager();
$events = $em->getAllEvents();

$filter_event = $_GET['event_id'] ?? null;
$registrations = $rm->getRegistrations($filter_event);

if (isset($_GET['delete'])) {
    $rm->deleteRegistration($_GET['delete']);
    echo "<script>window.location='registrations.php" . ($filter_event ? "?event_id=$filter_event" : "") . "';</script>";
    exit;
}
?>

<div class="flex justify-between items-center bg-white p-5 rounded-xl border border-[#e2e8f0] mb-6">
    <h1 class="text-lg font-semibold text-[#1e293b]">ثبت‌نام‌ها</h1>
    
    <form method="GET" class="flex items-center gap-2">
        <select name="event_id" class="border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm text-[#334155] focus:outline-none focus:border-blue-500" onchange="this.form.submit()">
            <option value="">همه رویدادها</option>
            <?php foreach ($events as $e): ?>
                <option value="<?= $e['id'] ?>" <?= $filter_event == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filter_event): ?>
           <a href="registrations.php" class="text-[#64748b] text-sm hover:text-red-500">✕</a>
        <?php endif; ?>
    </form>
</div>

<div class="bg-white rounded-xl border border-[#e2e8f0] overflow-x-auto">
    <table class="w-full text-right text-[13px] border-collapse min-w-max">
        <thead class="bg-[#f8fafc] text-[#64748b] font-semibold">
            <tr>
                <th class="p-3.5 border-b border-[#f1f5f9]">شناسه</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">رویداد</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">نام کاربر / چت آیدی</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">شماره تماس</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">تاریخ ثبت‌نام</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">نمایش کامل</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">عملیات</th>
            </tr>
        </thead>
        <tbody class="text-[#334155]">
            <?php foreach ($registrations as $r): ?>
            <tr class="border-b border-[#f8fafc] hover:bg-gray-50 transition-colors">
                <td class="p-3.5"><?= $r['id'] ?></td>
                <td class="p-3.5 text-[#1e293b] font-medium"><?= htmlspecialchars($r['event_title']) ?></td>
                <td class="p-3.5">
                    <div class="font-medium"><?= htmlspecialchars($r['user_name'] ?? '-') ?></div>
                    <div class="text-[11px] text-[#94a3b8] font-mono mt-0.5"><?= $r['chat_id'] ?></div>
                </td>
                <td class="p-3.5 font-mono" dir="ltr"><?= htmlspecialchars($r['user_phone'] ?? '-') ?></td>
                <td class="p-3.5"><?= explode(' ', $r['created_at'])[0] ?></td>
                <td class="p-3.5">
                    <button onclick="alert('پاسخ‌ها:\n<?= str_replace("\"", "\\\"", addslashes(print_r(json_decode($r['answers_json'], true), true))) ?>');" class="text-[#2563eb] text-[12px] bg-[#eff6ff] px-2.5 py-1.5 rounded-md hover:bg-blue-100 transition-colors">
                        مشاهده فرم
                    </button>
                </td>
                <td class="p-3.5">
                    <a href="?delete=<?= $r['id'] ?>&event_id=<?= $filter_event ?>" onclick="return confirm('آیا از حذف این رکورد مطمئن هستید؟');" class="text-red-500 hover:text-red-700 transition-colors">
                        حذف
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($registrations)): ?>
            <tr><td colspan="7" class="p-6 text-center text-[#64748b]">اطلاعاتی یافت نشد.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
