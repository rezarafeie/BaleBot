<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/classes/Auth.php';

$dbConnected = Database::getInstance()->isConnected();
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
    <title>ورود | BotMan</title>
    <link rel="stylesheet" href="https://lib.arvancloud.ir/vazir-font/33.003/Vazirmatn-font-face.css">
    <link rel="stylesheet" href="https://lib.arvancloud.ir/tailwindcss/2.2.19/tailwind.min.css">
    <style>
        :root {
            --font-persian: "Vazirmatn", Tahoma, Arial, "Segoe UI", Roboto, "Helvetica Neue", system-ui, sans-serif;
        }
        body { 
            font-family: var(--font-persian); 
            background: #f8fafc;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            direction: rtl;
        }
        .card { background: white; max-width: 384px; width: 100%; border-radius: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; padding: 32px; }
        .input-group { margin-bottom: 24px; text-align: right; }
        .label { display: block; color: #64748b; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .input { width: 100%; box-sizing: border-box; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 0.75rem; outline: none; transition: border-color 0.2s; font-family: inherit; }
        .input:focus { border-color: #2563eb; }
        .btn { width: 100%; padding: 12px; background: #2563eb; color: white; border-radius: 0.75rem; font-weight: 600; cursor: pointer; border: none; font-family: inherit; transition: background 0.2s; }
        .btn:hover { background: #1d4ed8; }
        .alert { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; padding: 12px; border-radius: 0.75rem; margin-bottom: 24px; font-size: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div style="display: flex; justify-content: center; margin-bottom: 24px;">
            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #2563eb, #7c3aed); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 24px;">B</div>
        </div>
        <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 24px; text-align: center;">ورود به پنل BotMan</h2>
        
        <?php if ($error): ?>
            <div class="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="input-group">
                <label class="label">نام کاربری</label>
                <input type="text" name="username" class="input" required autofocus>
            </div>
            <div class="input-group" style="margin-bottom: 32px;">
                <label class="label">رمز عبور</label>
                <input type="password" name="password" class="input" required>
            </div>
            <button type="submit" class="btn">ورود به حساب</button>
            <div style="margin-top: 24px; text-align: center;">
                <span style="font-size: 12px; color: #94a3b8;">حساب کاربری ندارید؟</span>
                <a href="../register.php" style="font-size: 12px; color: #2563eb; font-weight: 700; text-decoration: none; margin-right: 4px;">ثبت‌نام کنید</a>
            </div>
        </form>
    </div>
</body>
</html>
