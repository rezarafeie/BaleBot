<?php
if (file_exists('config.php')) {
    echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>خطا</title><link rel='stylesheet' href='assets/css/tailwind-compiled.css'></head><body class='bg-[#f8fafc] flex items-center justify-center min-h-screen text-[#1e293b] font-sans'><div class='bg-white p-8 rounded-xl border border-[#e2e8f0] shadow-sm max-w-md w-full text-center'><h1 class='text-xl font-bold mb-4 text-red-600'>سیستم قبلاً نصب شده است</h1><p class='text-[#475569] text-sm'>جهت نصب مجدد، فایل config.php را حذف کنید.</p></div></body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سیستم BotMan</title>
    <style>
        :root {
            --font-persian: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, Tahoma, sans-serif;
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
        .card { background: white; max-width: 512px; width: 100%; border-radius: 2.5rem; box-shadow: 0 25px 50px -12px rgba(37, 99, 235, 0.1); border: 1px solid #f1f5f9; padding: 48px; }
        .step { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 1.25rem; padding: 20px; display: flex; gap: 16px; margin-bottom: 16px; }
        .step-num { width: 24px; height: 24px; background: #dbeafe; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 900; flex-shrink: 0; }
        .btn { display: block; text-align: center; padding: 16px; border-radius: 1rem; text-decoration: none; font-weight: 700; transition: all 0.2s; font-size: 14px; }
        .btn-primary { background: #2563eb; color: white; margin-bottom: 12px; }
        .btn-secondary { background: white; border: 1px solid #e2e8f0; color: #475569; }
    </style>
</head>
<body>
    <div class="card">
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 32px; justify-content: center;">
             <div style="width: 48px; height: 48px; background: #2563eb; color: white; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 24px;">B</div>
             <h1 style="margin: 0; font-size: 24px; font-weight: 900;">راه‌اندازی BotMan</h1>
        </div>
        
        <div class="step">
            <div class="step-num">۱</div>
            <div style="font-size: 13px; color: #475569; line-height: 1.6;">ابتدا دیتابیس خود را آماده کنید. سپس از دکمه شروع زیر برای پیکربندی خودکار استفاده کنید.</div>
        </div>

        <div class="step">
            <div class="step-num">۲</div>
            <div style="font-size: 13px; color: #475569; line-height: 1.6;">پس از اتصال، جداول سیستم به صورت خودکار ساخته می‌شوند.</div>
        </div>

        <div style="margin-top: 32px;">
            <a href="db-setup.php" class="btn btn-primary">شروع راه‌اندازی خودکار</a>
            <a href="admin/login.php" class="btn btn-secondary">ورود به پنل (اگر قبلاً نصب شده)</a>
        </div>
    </div>
</body>
</html>
