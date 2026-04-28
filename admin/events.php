<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Auth.php';
require_once dirname(__DIR__) . '/classes/EventManager.php';
require_once dirname(__DIR__) . '/classes/BotManager.php';

$auth = new Auth();
$auth->requireLogin();

$em = new EventManager();
$bm = new BotManager();
$bots = $bm->getBots();

if (isset($_GET['delete'])) {
    $em->deleteEvent($_GET['delete']);
    header("Location: events.php");
    exit;
}

if (isset($_GET['duplicate'])) {
    $new_id = $em->duplicateEvent($_GET['duplicate']);
    if ($new_id) {
        header("Location: events.php?success=1");
    } else {
        header("Location: events.php?error=1");
    }
    exit;
}

if (isset($_GET['copy_to']) && isset($_GET['bot_id'])) {
    $new_id = $em->duplicateEvent($_GET['copy_to'], $_GET['bot_id']);
    if ($new_id) {
        header("Location: events.php?success=1");
    } else {
        header("Location: events.php?error=1");
    }
    exit;
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">موفقیت!</strong>
        <span class="block sm:inline">عملیات با موفقیت انجام شد.</span>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <strong class="font-bold">خطا!</strong>
        <span class="block sm:inline">مشکلی در انجام عملیات رخ داد.</span>
    </div>
<?php endif; ?>

<?php
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
                    <td class="p-3.5">
                        <div class="flex items-center gap-3">
                            <a href="event-fields.php?id=<?= $e['id'] ?>" class="text-[#64748b] hover:text-blue-600 transition-colors" title="فیلدها">فرم‌ساز</a>
                            <a href="event-edit.php?id=<?= $e['id'] ?>" class="text-[#64748b] hover:text-blue-600 transition-colors" title="ویرایش">ویرایش</a>
                            <a href="events.php?duplicate=<?= $e['id'] ?>" onclick="return confirm('آیا از کپی این رویداد مطمئن هستید؟')" class="text-[#64748b] hover:text-blue-600" title="کپی">کپی</a>
                            
                            <!-- Copy to another bot -->
                            <?php if (count($bots) > 1): ?>
                            <div class="relative group inline-block">
                                <button class="text-[#64748b] hover:text-blue-600">انتقال بات</button>
                                <div class="absolute right-0 bottom-full mb-2 w-48 bg-white border border-gray-200 rounded-lg shadow-xl hidden group-hover:block z-50">
                                    <div class="p-2 text-xs font-bold text-gray-400 border-b">کپی به بات:</div>
                                    <?php foreach ($bots as $b): if($b['id'] == ($_SESSION['selected_bot_id'] ?? 0)) continue; ?>
                                        <a href="events.php?copy_to=<?= $e['id'] ?>&bot_id=<?= $b['id'] ?>" class="block px-3 py-2 text-xs text-gray-700 hover:bg-blue-50 rounded"><?= htmlspecialchars($b['name']) ?></a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <a href="events.php?delete=<?= $e['id'] ?>" onclick="return confirm('آیا مطمئن هستید؟');" class="text-red-500 hover:text-red-700 transition-colors" title="حذف">حذف</a>
                        </div>
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
