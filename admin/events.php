<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/EventManager.php';

$em = new EventManager();

if (isset($_GET['delete'])) {
    $em->deleteEvent($_GET['delete']);
    echo "<script>window.location='events.php';</script>";
    exit;
}

$events = $em->getAllEvents();
?>

<div class="flex justify-between items-center bg-white p-5 rounded-xl border border-[#e2e8f0]">
    <h1 class="text-lg font-semibold text-[#1e293b]">مدیریت رویدادها</h1>
    <a href="event-edit.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors flex items-center">
        رویداد جدید
    </a>
</div>

<div class="bg-white rounded-xl border border-[#e2e8f0] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-[13px] border-collapse">
            <thead class="bg-[#f8fafc] text-[#64748b] font-semibold">
                <tr>
                    <th class="p-3.5 border-b border-[#f1f5f9]">شناسه</th>
                    <th class="p-3.5 border-b border-[#f1f5f9]">عنوان رویداد</th>
                    <th class="p-3.5 border-b border-[#f1f5f9]">وضعیت</th>
                    <th class="p-3.5 border-b border-[#f1f5f9]">ایجاد شده</th>
                    <th class="p-3.5 border-b border-[#f1f5f9]">عملیات</th>
                </tr>
            </thead>
            <tbody class="text-[#334155]">
                <?php foreach ($events as $e): ?>
                <tr class="border-b border-[#f8fafc] hover:bg-gray-50 transition-colors">
                    <td class="p-3.5"><?= $e['id'] ?></td>
                    <td class="p-3.5 font-semibold text-[#1e293b]"><?= htmlspecialchars($e['title']) ?></td>
                    <td class="p-3.5">
                        <?php if ($e['is_active']): ?>
                            <span class="text-[#059669] bg-[#ecfdf5] px-2 py-1 rounded text-[11px] font-medium">فعال</span>
                        <?php else: ?>
                            <span class="text-[#d97706] bg-[#fffbeb] px-2 py-1 rounded text-[11px] font-medium">غیرفعال</span>
                        <?php endif; ?>
                    </td>
                    <td class="p-3.5"><?= explode(' ', $e['created_at'])[0] ?></td>
                    <td class="p-3.5 flex gap-3">
                        <a href="event-fields.php?id=<?= $e['id'] ?>" class="text-[#64748b] hover:text-blue-600 transition-colors" title="فیلدها">فرم‌ساز</a>
                        <a href="event-edit.php?id=<?= $e['id'] ?>" class="text-[#64748b] hover:text-blue-600 transition-colors" title="ویرایش">ویرایش</a>
                        <a href="events.php?delete=<?= $e['id'] ?>" onclick="return confirm('آیا مطمئن هستید؟');" class="text-red-500 hover:text-red-700 transition-colors" title="حذف">حذف</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($events)): ?>
                <tr><td colspan="5" class="p-6 text-center text-[#64748b]">هیچ رویدادی ثبت نشده است.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
