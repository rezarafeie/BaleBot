<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../classes/Database.php';

$db = Database::getInstance()->getConnection();
$users = $db->query("SELECT * FROM bot_users ORDER BY last_interaction_at DESC")->fetchAll();
?>

<div class="bg-white p-5 rounded-xl border border-[#e2e8f0] mb-6">
    <h1 class="text-lg font-semibold text-[#1e293b]">کاربران بات</h1>
</div>

<div class="bg-white rounded-xl border border-[#e2e8f0] overflow-x-auto">
    <table class="w-full text-right text-[13px] border-collapse min-w-max">
        <thead class="bg-[#f8fafc] text-[#64748b] font-semibold">
            <tr>
                <th class="p-3.5 border-b border-[#f1f5f9]">شناسه</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">شناسه بله (chat id)</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">نام</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">یوزرنیم</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">شماره تماس</th>
                <th class="p-3.5 border-b border-[#f1f5f9]">آخرین تعامل</th>
            </tr>
        </thead>
        <tbody class="text-[#334155]">
            <?php foreach ($users as $u): ?>
            <tr class="border-b border-[#f8fafc] hover:bg-gray-50 transition-colors">
                <td class="p-3.5"><?= $u['id'] ?></td>
                <td class="p-3.5 text-[#94a3b8] font-mono"><?= $u['chat_id'] ?></td>
                <td class="p-3.5 font-medium text-[#1e293b]"><?= htmlspecialchars($u['name'] ?? '-') ?></td>
                <td class="p-3.5 font-mono" dir="ltr"><?= $u['username'] ? '@'.htmlspecialchars($u['username']) : '-' ?></td>
                <td class="p-3.5 font-mono" dir="ltr"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                <td class="p-3.5 text-[#64748b]"><?= explode(' ', $u['last_interaction_at'])[0] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr><td colspan="6" class="p-6 text-center text-[#64748b]">هیچ کاربری یافت نشد.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
