<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Auth.php';

$dbConnected = Database::getInstance()->isConnected();
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
    <link rel="stylesheet" href="assets/fonts/vazir/font-face.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
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
            min-height: 100vh;
            padding: 24px;
            direction: rtl;
        }
        .card { background: white; max-width: 448px; width: 100%; border-radius: 2.5rem; box-shadow: 0 25px 50px -12px rgba(37, 99, 235, 0.1); border: 1px solid #f1f5f9; padding: 48px; }
        .input-group { margin-bottom: 24px; text-align: right; }
        .label { display: block; color: #94a3b8; font-size: 12px; font-weight: 900; text-transform: uppercase; margin-bottom: 8px; }
        .input { width: 100%; box-sizing: border-box; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 1.25rem; padding: 16px 24px; font-size: 14px; outline: none; transition: all 0.2s; font-family: inherit; }
        .input:focus { border-color: #2563eb; background: white; }
        .btn { display: block; width: 100%; padding: 20px; border-radius: 1.5rem; font-weight: 700; cursor: pointer; border: none; text-align: center; text-decoration: none; transition: all 0.2s; font-family: inherit; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .alert { padding: 16px; border-radius: 1rem; margin-bottom: 24px; font-size: 14px; font-weight: 700; text-align: center; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
        .alert-success { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
    </style>
</head>
<body>
    <div class="card">
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 40px; justify-content: flex-start;">
             <div style="width: 40px; height: 40px; background: #2563eb; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 20px;">B</div>
             <h1 style="margin: 0; font-size: 24px; font-weight: 900; margin-right: 12px;">ساخت حساب کاربری</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
                <div style="margin-top: 12px;">
                    <a href="admin/login.php" style="color: inherit; text-decoration: underline;">ورود به پنل مدیریت</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" action="register.php">
                <div class="input-group">
                    <label class="label">نام کاربری</label>
                    <input type="text" name="username" class="input" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="input-group">
                    <label class="label">رمز عبور</label>
                    <input type="password" name="password" class="input" required>
                </div>

                <div class="input-group">
                    <label class="label">تکرار رمز عبور</label>
                    <input type="password" name="confirm_password" class="input" required>
                </div>

                <button type="submit" name="register" class="btn btn-primary">ثبت‌نام و ایجاد حساب</button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 32px; text-align: center; border-top: 1px solid #f8fafc; padding-top: 24px;">
            <a href="admin/login.php" style="font-size: 12px; font-weight: 900; color: #94a3b8; text-decoration: none; text-transform: uppercase; letter-spacing: 0.1em;">قبلاً ثبت‌نام کرده‌اید؟ ورود</a>
        </div>
    </div>
</body>
</html>
