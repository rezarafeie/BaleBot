<?php
session_start();
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Auth.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($username, $password)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'کد کاربری یا رمز عبور اشتباه است.';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود | مدیریت</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-[#f8fafc] flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-xl border border-[#e2e8f0] w-full max-w-sm">
        <div class="flex justify-center mb-6">
            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">B</div>
        </div>
        <h2 class="text-xl font-bold mb-6 text-center text-[#1e293b]">ورود به پنل مدیریت</h2>
        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm text-center border border-red-100"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="mb-4">
                <label class="block text-[#64748b] text-sm font-semibold mb-2">نام کاربری</label>
                <input type="text" name="username" class="w-full px-4 py-2 border border-[#e2e8f0] rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <label class="block text-[#64748b] text-sm font-semibold mb-2">رمز عبور</label>
                <input type="password" name="password" class="w-full px-4 py-2 border border-[#e2e8f0] rounded-lg focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg focus:outline-none transition-colors">
                ورود
            </button>
        </form>
    </div>
</body>
</html>
