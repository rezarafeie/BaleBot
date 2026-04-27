<aside class="w-[260px] bg-white border-l border-[#e2e8f0] flex-col shadow-sm hidden md:flex shrink-0">
    <div class="p-6 border-b border-[#f1f5f9]">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">B</div>
            <h1 class="text-[18px] font-bold text-[#1e293b]">پنل مدیریت بله</h1>
        </div>
    </div>
    
    <nav class="p-4 flex-grow overflow-y-auto">
        <ul class="flex flex-col gap-1">
            <?php 
                $current_page = basename($_SERVER['PHP_SELF']);
                
                function navLink($url, $icon, $label, $current_page) {
                    $isActive = $current_page === $url;
                    $bg = $isActive ? 'bg-[#eff6ff] text-blue-600 font-semibold' : 'text-[#64748b] hover:bg-gray-50';
                    return "<a href='$url' class='flex items-center gap-3 px-4 py-3 rounded-lg text-sm $bg transition-colors'><i class='bi $icon text-lg'></i> $label</a>";
                }
            ?>
            <li><?= navLink('dashboard.php', 'bi-house-door', 'پیشخوان', $current_page) ?></li>
            <li><?= navLink('events.php', 'bi-calendar-event', 'مدیریت رویدادها', $current_page) ?></li>
            <li><?= navLink('registrations.php', 'bi-card-list', 'لیست ثبت‌نام‌ها', $current_page) ?></li>
            <li><?= navLink('users.php', 'bi-people', 'کاربران بات', $current_page) ?></li>
            <li><?= navLink('broadcast.php', 'bi-megaphone', 'ارسال پیام انبوه', $current_page) ?></li>
            <li><?= navLink('media.php', 'bi-images', 'کتابخانه رسانه', $current_page) ?></li>
            <li><?= navLink('settings.php', 'bi-gear', 'تنظیمات سیستم', $current_page) ?></li>
        </ul>
    </nav>
    <div class="p-6 border-t border-[#f1f5f9] text-xs text-[#94a3b8]">
        نسخه ۱.۴.۲ - محلی
    </div>
</aside>
