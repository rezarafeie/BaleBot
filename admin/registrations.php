<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Auth.php';
require_once dirname(__DIR__) . '/classes/RegistrationManager.php';
require_once dirname(__DIR__) . '/classes/EventManager.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$auth = new Auth();
$auth->requireLogin();

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

if (isset($_GET['export_csv'])) {
    // Make sure no HTML output
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="registrations_' . date('Y-m-d_H-i') . '.csv"');
    
    echo "\xEF\xBB\xBF";
    
    $fp = fopen('php://output', 'w');
    
    $dynamic_keys = [];
    $parsed_answers = [];
    foreach ($registrations as $r) {
        $ans = json_decode($r['answers_json'], true) ?: [];
        $parsed_answers[$r['id']] = $ans;
        foreach (array_keys($ans) as $k) {
            if (!in_array($k, $dynamic_keys)) {
                $dynamic_keys[] = $k;
            }
        }
    }
    
    $headers = ['شناسه', 'رویداد', 'نام', 'شماره تماس', 'شناسه کاربری', 'تاریخ ثبت‌نام'];
    foreach ($dynamic_keys as $dk) {
        $headers[] = $dk;
    }
    fputcsv($fp, $headers);
    
    foreach ($registrations as $r) {
        $row = [
            $r['id'],
            $r['event_title'],
            $r['user_name'] ?? '-',
            '="' . ($r['user_phone'] ?? '-') . '"',
            '="' . $r['chat_id'] . '"',
            $r['created_at']
        ];
        $ans = $parsed_answers[$r['id']];
        foreach ($dynamic_keys as $dk) {
            $raw_val = isset($ans[$dk]) ? (is_array($ans[$dk]) ? json_encode($ans[$dk], JSON_UNESCAPED_UNICODE) : $ans[$dk]) : '';
            // If it's a long number (like a phone), wrap it for Excel
            if (is_numeric($raw_val) && strlen($raw_val) > 9) {
                $val = '="' . $raw_val . '"';
            } else {
                $val = $raw_val;
            }
            $row[] = $val;
        }
        fputcsv($fp, $row);
    }
    fclose($fp);
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex justify-between items-center bg-white p-5 rounded-xl border border-[#e2e8f0] mb-6">
    <h1 class="text-lg font-semibold text-[#1e293b]">ثبت‌نام‌ها</h1>
    
    <div class="flex items-center gap-3">
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
        <a href="?export_csv=1<?= $filter_event ? '&event_id='.$filter_event : '' ?>" class="bg-[#10b981] hover:bg-green-600 text-white font-medium py-2 px-4 rounded-lg text-[13px] transition-colors whitespace-nowrap">
            دانلود فایل اکسل (CSV)
        </a>
    </div>
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
                    <button onclick="document.getElementById('details_<?= $r['id'] ?>').classList.toggle('hidden')" class="text-[#2563eb] text-[12px] bg-[#eff6ff] px-2.5 py-1.5 rounded-md hover:bg-blue-100 transition-colors">
                        مشاهده فرم
                    </button>
                </td>
                <td class="p-3.5">
                    <a href="?delete=<?= $r['id'] ?>&event_id=<?= $filter_event ?>" onclick="return confirm('آیا از حذف این رکورد مطمئن هستید؟');" class="text-red-500 hover:text-red-700 transition-colors">
                        حذف
                    </a>
                </td>
            </tr>
            <tr id="details_<?= $r['id'] ?>" class="hidden bg-[#f8fafc]">
                <td colspan="7" class="p-4">
                    <div class="bg-white border border-[#e2e8f0] rounded-lg p-4 shadow-sm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php 
                            $ans = json_decode($r['answers_json'], true) ?: [];
                            foreach ($ans as $k => $v): 
                            ?>
                                <div class="border-b border-[#f1f5f9] pb-2">
                                    <span class="text-[#64748b] text-[11px] block"><?= htmlspecialchars($k) ?></span>
                                    <span class="text-[#1e293b] font-medium"><?= is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : htmlspecialchars($v) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
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
