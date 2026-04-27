<?php
if (file_exists('config.php')) {
    echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>خطا</title><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-[#f8fafc] flex items-center justify-center min-h-screen text-[#1e293b] font-sans'><div class='bg-white p-8 rounded-xl border border-[#e2e8f0] shadow-sm max-w-md w-full text-center'><h1 class='text-xl font-bold mb-4 text-red-600'>سیستم قبلاً نصب شده است</h1><p class='text-[#475569] text-sm'>جهت نصب مجدد، فایل config.php را حذف کنید.</p></div></body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سیستم بات بله</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; }
    </style>
</head>
<body class="bg-[#f8fafc] text-[#1e293b] min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-xl border border-[#e2e8f0] shadow-sm max-w-lg w-full">
        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        </div>
        <h2 class="text-2xl font-bold text-center mb-6">نصب و راه‌اندازی سیستم</h2>
        
        <div class="space-y-4">
            <div class="flex items-start bg-[#f8fafc] p-4 rounded-lg border border-[#f1f5f9]">
                <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-bold flex-shrink-0 ml-3">۱</div>
                <p class="text-sm text-[#475569] leading-relaxed">ابتدا فایل <code class="bg-gray-100 px-1 py-0.5 rounded text-gray-800" dir="ltr">config.php</code> را با توجه به تنظیمات دیتابیس و توکن بات خود ایجاد یا ویرایش کنید.</p>
            </div>
            <div class="flex items-start bg-[#f8fafc] p-4 rounded-lg border border-[#f1f5f9]">
                <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-bold flex-shrink-0 ml-3">۲</div>
                <p class="text-sm text-[#475569] leading-relaxed">فایل‌های <code class="bg-gray-100 px-1 py-0.5 rounded text-gray-800" dir="ltr">sql/install.sql</code> را در پایگاه داده (MySQL) خود آپلود (Import) نمایید.</p>
            </div>
            <div class="flex items-start bg-[#f8fafc] p-4 rounded-lg border border-[#f1f5f9]">
                <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-bold flex-shrink-0 ml-3">۳</div>
                <p class="text-sm text-[#475569] leading-relaxed">پس از نصب، می‌توانید با نام کاربری <strong class="text-gray-900" dir="ltr">admin</strong> و رمز عبور <strong class="text-gray-900" dir="ltr">admin123</strong> وارد پنل مدیریت شوید.<br><span class="text-xs text-red-500 mt-1 block">توجه: پس از ورود اول حتماً رمز عبور را تغییر دهید.</span></p>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="admin/login.php" class="inline-block bg-[#2563eb] hover:bg-blue-700 text-white font-medium py-2.5 px-8 rounded-lg text-[13px] transition-colors">
                ورود به پنل مدیریت
            </a>
        </div>
    </div>
</body>
</html>
