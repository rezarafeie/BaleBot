<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Auth.php';
require_once dirname(__DIR__) . '/includes/icons.php';

$auth = new Auth();
$auth->requireLogin();

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
        <header class="h-[80px] bg-white border-b border-[#e2e8f0] flex items-center justify-between px-8 shrink-0">
            <div class="flex items-center gap-4">
                <div class="bg-[#dcfce7] text-[#166534] px-3 py-1 rounded-full text-xs font-semibold">
                    وضعیت بات: متصل
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
