<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Auth.php';
require_once dirname(__DIR__) . '/classes/BotManager.php';
require_once dirname(__DIR__) . '/includes/icons.php';

$auth = new Auth();
$auth->requireLogin();

$botManager = new BotManager();
$bots = $botManager->getBots();

if (isset($_GET['switch_bot'])) {
    $_SESSION['selected_bot_id'] = (int)$_GET['switch_bot'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if (!isset($_SESSION['selected_bot_id']) && !empty($bots)) {
    $_SESSION['selected_bot_id'] = $bots[0]['id'];
}

$currentBot = null;
if (isset($_SESSION['selected_bot_id'])) {
    foreach ($bots as $b) {
        if ($b['id'] == $_SESSION['selected_bot_id']) {
            $currentBot = $b;
            break;
        }
    }
}

function url($path = '') {
    return BASE_URL . '/admin/' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت ربات بله</title>
    <link rel="stylesheet" href="../assets/css/tailwind-compiled.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-y-auto w-full">
        <!-- Top bar -->
        <header class="h-[80px] bg-white border-b border-[#e2e8f0] flex items-center justify-between px-6 md:px-8 shrink-0">
            <div class="flex items-center gap-4 md:gap-6">
                <!-- Burger Menu -->
                <button onclick="toggleSidebar()" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg">
                    <?= render_icon('list', 'text-2xl') ?>
                </button>

                <!-- Bot Switcher -->
                <div class="relative">
                    <button onclick="document.getElementById('botList').classList.toggle('hidden')" class="flex items-center gap-3 bg-[#f8fafc] border border-[#e2e8f0] px-4 py-2 rounded-xl text-sm font-semibold text-[#1e293b] hover:border-blue-300 transition-all">
                        <div class="w-2 h-2 rounded-full <?= $currentBot ? 'bg-[#10b981]' : 'bg-gray-300' ?>"></div>
                        <?= $currentBot ? htmlspecialchars($currentBot['name']) : 'انتخاب بات' ?>
                        <?= render_icon('chevron-down', 'text-[10px] text-[#64748b]') ?>
                    </button>
                    <div id="botList" class="absolute top-full right-0 mt-2 w-56 bg-white border border-[#e2e8f0] rounded-xl shadow-xl hidden z-50">
                        <div class="p-2 flex flex-col gap-1">
                            <?php foreach ($bots as $b): ?>
                                <a href="?switch_bot=<?= $b['id'] ?>" class="flex items-center justify-between px-3 py-2.5 rounded-lg hover:bg-[#eff6ff] transition-colors <?= $b['id'] == ($_SESSION['selected_bot_id'] ?? 0) ? 'bg-[#eff6ff] text-blue-600' : 'text-[#475569]' ?>">
                                    <span class="text-[13px] font-medium"><?= htmlspecialchars($b['name']) ?></span>
                                    <?php if ($b['id'] == ($_SESSION['selected_bot_id'] ?? 0)): ?>
                                        <?= render_icon('check2', 'text-blue-600') ?>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                            <div class="border-t border-[#f1f5f9] mt-1 pt-1">
                                <a href="bots.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-blue-600 hover:bg-blue-50">
                                    <?= render_icon('plus', 'text-sm') ?> مدیریت بات‌ها
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                window.addEventListener('click', function(e) {
                    const menu = document.getElementById('botList');
                    if (!menu.contains(e.target) && !e.target.closest('button')) {
                        menu.classList.add('hidden');
                    }
                });
                </script>

                <div class="bg-[#dcfce7] text-[#166534] px-3 py-1 rounded-full text-xs font-semibold">
                    وضعیت بات: <?= $currentBot ? 'متصل' : 'نامشخص' ?>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-left">
                    <div class="text-sm font-semibold text-[#1e293b]"><?= htmlspecialchars($_SESSION['admin_username']) ?></div>
                    <a href="logout.php" class="text-xs text-red-500 hover:text-red-700 mt-1 block text-right">خروج از حساب</a>
                </div>
                <div class="w-10 h-10 bg-[#f1f5f9] rounded-full flex items-center justify-center text-lg">👤</div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-8 flex flex-col gap-6">
