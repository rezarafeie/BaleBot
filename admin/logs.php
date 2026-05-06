<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/Logger.php';

$logger = new Logger();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$filter_type = !empty($_GET['type']) ? $_GET['type'] : null;
$filter_bot = isset($_GET['bot_filter']) ? $_GET['bot_filter'] : null;

$logs = $logger->getLogs($limit, $offset, $filter_type, $filter_bot);
$total_logs = $logger->countLogs($filter_type, $filter_bot);
$total_pages = ceil($total_logs / $limit);

$types = ['webhook', 'webhook_raw', 'api', 'gapgpt', 'auth', 'database', 'system']; // Common log types
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
    <h1 class="text-2xl font-bold text-[#1e293b]">لاگ‌های سیستم</h1>
    
    <form method="GET" class="flex flex-wrap gap-2">
        <select name="bot_filter" class="border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 bg-[#f8fafc]">
            <option value="">بات فعلی</option>
            <option value="all" <?= $filter_bot === 'all' ? 'selected' : '' ?>>همه بات‌ها</option>
        </select>
        <select name="type" class="border border-[#e2e8f0] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 bg-[#f8fafc]">
            <option value="">همه لاگ‌ها</option>
            <?php foreach ($types as $t): ?>
                <option value="<?= $t ?>" <?= $filter_type === $t ? 'selected' : '' ?>><?= strtoupper($t) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors shadow-sm">اعمال فیلتر</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-[#e2e8f0] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-right text-[13px]">
            <thead class="bg-[#f8fafc] text-[#64748b] font-semibold border-b border-[#e2e8f0]">
                <tr>
                    <th class="p-4 w-20 text-center">شناسه</th>
                    <th class="p-4 w-32">نوع</th>
                    <th class="p-4">پیام</th>
                    <th class="p-4 w-48">تاریخ</th>
                    <th class="p-4 w-24 text-center">جزئیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr class="border-b border-[#f1f5f9] hover:bg-gray-50 transition-colors">
                            <td class="p-4 text-center font-mono text-[#94a3b8]"><?= $log['id'] ?></td>
                            <td class="p-4">
                                <span class="bg-<?= $log['log_type'] === 'error' ? 'red' : 'gray' ?>-100 text-<?= $log['log_type'] === 'error' ? 'red' : 'gray' ?>-700 px-2 py-1 rounded text-xs font-semibold uppercase">
                                    <?= htmlspecialchars($log['log_type']) ?>
                                </span>
                            </td>
                            <td class="p-4 text-[#334155]"><?= htmlspecialchars($log['message']) ?></td>
                            <td class="p-4 text-[#64748b]" dir="ltr"><?= $log['created_at'] ?></td>
                            <td class="p-4 text-center">
                                <?php if (!empty($log['details'])): ?>
                                    <button onclick="document.getElementById('details_<?= $log['id'] ?>').classList.toggle('hidden')" class="text-blue-500 hover:text-blue-700 p-1 bg-blue-50 rounded text-xs transition-colors">مشاهده</button>
                                <?php else: ?>
                                    - 
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($log['details'])): ?>
                            <tr id="details_<?= $log['id'] ?>" class="hidden bg-[#f8fafc] border-b border-[#f1f5f9]">
                                <td colspan="5" class="p-4 text-left font-mono text-[11px] text-[#475569] whitespace-pre-wrap" dir="ltr"><?= htmlspecialchars(json_encode(json_decode($log['details'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">لاگی یافت نشد.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1): ?>
    <div class="p-4 border-t border-[#f1f5f9] flex justify-between items-center bg-[#f8fafc]">
        <div class="text-sm text-[#64748b]">
            نمایش <?= $offset + 1 ?> تا <?= min($offset + $limit, $total_logs) ?> از <?= $total_logs ?> رکورد
        </div>
        <div class="flex gap-1">
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= $filter_type ? '&type='.$filter_type : '' ?>" class="w-8 h-8 flex items-center justify-center rounded <?= $i === $page ? 'bg-blue-600 text-white font-bold' : 'bg-white border border-[#e2e8f0] text-[#64748b] hover:bg-gray-50' ?> text-sm transition-colors">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
