<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();
if ($auth->isLoggedIn()) {
    header("Location: admin/dashboard.php");
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = 'رمز عبور و تکرار آن یکسان نیستند.';
    } else {
        $res = $auth->register($username, $password);
        if ($res['success']) {
            $success = $res['message'] . ' اکنون می‌توانید وارد شوید.';
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام | BotMan</title>
    <link rel="stylesheet" href="assets/css/tailwind-compiled.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-bg {
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(124, 58, 237, 0.1) 0%, transparent 40%);
        }
    </style>
</head>
<body class="bg-gray-50 text-slate-900 hero-bg min-h-screen flex items-center justify-center p-6">

    <div class="max-w-4xl w-full">
        <div class="bg-white/80 backdrop-blur-xl border border-white rounded-[3rem] shadow-2xl overflow-hidden flex flex-col md:flex-row shadow-blue-200/50">
            <!-- Right side / Info -->
            <div class="md:w-[40%] bg-slate-900 p-12 text-white flex flex-col justify-between text-right">
                <div>
                   <div class="flex items-center gap-3 mb-10">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-violet-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">B</div>
                        <span class="text-xl font-black italic">BotMan</span>
                    </div>
                    <h2 class="text-3xl font-black mb-6">به جمع ما بپیوندید</h2>
                    <p class="text-slate-400 text-sm leading-loose">
                        با ثبت‌نام در BotMan، می‌توانید ربات‌های اختصاصی خود را در بله، تلگرام و روبیکا مدیریت کنید.
                    </p>
                </div>
                <div class="mt-12 space-y-4">
                    <div class="flex items-center gap-3">
                         <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                         <span class="text-xs text-slate-400">مدیریت نامحدود ربات‌ها</span>
                    </div>
                    <div class="flex items-center gap-3">
                         <div class="w-2 h-2 rounded-full bg-violet-500"></div>
                         <span class="text-xs text-slate-400">پنل گزارشات پیشرفته</span>
                    </div>
                </div>
            </div>

            <!-- Left side / Form -->
            <div class="md:w-[60%] p-12 md:p-16 text-right">
                <h3 class="text-2xl font-black mb-8 text-slate-800">بر ساخت حساب کاربری</h3>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-6 text-sm flex items-center gap-2">
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 text-green-600 p-4 rounded-2xl mb-6 text-sm flex items-center gap-2">
                        <span><?= $success ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" class="space-y-5">
                    <div>
                        <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2 pr-2">نام کاربری</label>
                        <input type="text" name="username" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition-all" required>
                    </div>
                    <div>
                        <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2 pr-2">رمز عبور</label>
                        <input type="password" name="password" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition-all" required>
                    </div>
                    <div>
                        <label class="block text-slate-500 text-xs font-bold uppercase tracking-wider mb-2 pr-2">تکرار رمز عبور</label>
                        <input type="password" name="confirm_password" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-5 py-4 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition-all" required>
                    </div>
                    <button type="submit" name="register" class="w-full py-4 bg-gradient-to-r from-blue-600 to-violet-600 text-white rounded-2xl font-bold shadow-lg shadow-blue-200 hover:opacity-95 transition-all mt-4">
                        ثبت‌نام و ایجاد حساب
                    </button>
                    
                    <div class="text-center mt-6">
                        <span class="text-sm text-slate-400">قبلاً ثبت‌نام کرده‌اید؟</span>
                        <a href="index.php#login" class="text-sm text-blue-600 font-bold hover:underline pr-1">وارد شوید</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
