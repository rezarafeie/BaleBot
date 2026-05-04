<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BotMan | مدیریت هوشمند ربات‌ها</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
        @keyframes float {
            0% { transform: translateY(0px) rotate(3deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(3deg); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .hero-gradient {
            background: radial-gradient(circle at 50% 50%, rgba(37, 99, 235, 0.05) 0%, transparent 50%);
        }
    </style>
</head>
<body class="bg-white text-slate-900 selection:bg-blue-600 selection:text-white">

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-white/50 backdrop-blur-xl border-b border-slate-50">
        <div class="max-w-6xl mx-auto px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-blue-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <span class="font-bold text-xl tracking-tight text-slate-900">BotMan</span>
            </div>
            <div class="flex items-center gap-8">
                <nav class="hidden md:flex gap-10 text-sm font-semibold text-slate-500">
                    <a href="#features" class="hover:text-blue-600 transition-colors">امکانات</a>
                    <a href="#platforms" class="hover:text-blue-600 transition-colors">پلتفرم‌ها</a>
                </nav>
                <div class="flex items-center gap-4">
                    <a href="admin/login.php" class="text-sm font-bold text-slate-400 hover:text-slate-900 transition-colors">ورود</a>
                    <a href="register.php" class="bg-blue-600 text-white px-6 py-3 rounded-2xl text-sm font-bold shadow-xl shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all active:scale-95">
                        شروع کنید
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="relative overflow-hidden hero-gradient">
        <!-- Hero Section -->
        <section class="pt-48 pb-32 px-8">
            <div class="max-w-6xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-widest mb-10 border border-blue-100 shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                    نسخه پیشرفته ۲.۵
                </div>
                <h1 class="text-6xl md:text-[5.5rem] font-black text-slate-900 mb-10 leading-[1.05] tracking-tight">
                    مدیریت ربات، <br />
                    <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent italic">به سبک من</span>
                </h1>
                <p class="text-xl md:text-2xl text-slate-400 mb-16 max-w-2xl mx-auto leading-relaxed font-medium">
                    پاسخگویی هوشمند، مدیریت متمرکز و پایداری واقعی در تمامی پیام‌رسان‌های بله، تلگرام و روبیکا.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                    <a href="register.php" class="w-full sm:w-auto px-12 py-5 bg-blue-600 text-white rounded-[1.5rem] font-bold text-lg shadow-2xl shadow-blue-200 hover:bg-blue-700 hover:shadow-blue-300 hover:-translate-y-1 transition-all flex items-center justify-center gap-3">
                        رایگان شروع کنید
                        <svg class="w-5 h-5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                    <a href="admin/login.php" class="w-full sm:w-auto px-12 py-5 border-2 border-slate-100 text-slate-600 rounded-[1.5rem] font-bold text-lg hover:bg-slate-50 transition-all">
                        ورود به حساب
                    </a>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="py-32 px-8">
            <div class="max-w-6xl mx-auto">
                <div class="grid md:grid-cols-3 gap-10">
                    <div class="p-10 bg-white border border-slate-100 rounded-[2.5rem] hover:shadow-2xl hover:shadow-slate-100 transition-all duration-500 group">
                        <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">سرعت لحظه‌ای</h3>
                        <p class="text-slate-400 leading-relaxed font-medium">پردازش و پاسخگویی به پیام‌ها در کمتر از ۱۰۰ میلی‌ثانیه.</p>
                    </div>
                    <div class="p-10 bg-white border border-slate-100 rounded-[2.5rem] hover:shadow-2xl hover:shadow-slate-100 transition-all duration-500 group">
                        <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">مولتی پلتفرم</h3>
                        <p class="text-slate-400 leading-relaxed font-medium">یک ربات بسازید و در بله، تلگرام و روبیکا منتشر کنید.</p>
                    </div>
                    <div class="p-10 bg-white border border-slate-100 rounded-[2.5rem] hover:shadow-2xl hover:shadow-slate-100 transition-all duration-500 group">
                        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">امنیت بانکی</h3>
                        <p class="text-slate-400 leading-relaxed font-medium">رمزنگاری پیشرفته داده‌ها و ایزولاسیون کامل پلتفرم.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section id="platforms" class="py-32 px-8 bg-slate-950 rounded-[3rem] mx-4 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-96 h-96 bg-blue-600/10 blur-[120px] rounded-full"></div>
            <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-16 relative z-10">
                <div class="text-right flex-1">
                    <h2 class="text-4xl md:text-5xl font-black text-white mb-8 leading-tight">
                        با هر پلتفرمی که <br>دوست دارید کار کنید.
                    </h2>
                    <p class="text-slate-400 text-lg mb-10 max-w-md leading-loose">
                        BotMan با تمامی کتابخانه‌های محبوب و پلتفرم‌های اصلی سازگاری کامل دارد.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <span class="px-6 py-3 bg-white/5 border border-white/10 rounded-full text-white text-sm font-bold">بله (Bale)</span>
                        <span class="px-6 py-3 bg-white/5 border border-white/10 rounded-full text-white text-sm font-bold">تلگرام</span>
                        <span class="px-6 py-3 bg-white/5 border border-white/10 rounded-full text-white text-sm font-bold">روبیکا</span>
                    </div>
                </div>
                <div class="flex-1 flex justify-center">
                    <div class="w-64 h-64 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-[3.5rem] flex items-center justify-center shadow-3xl shadow-blue-500/20 rotate-3 animate-float">
                         <svg class="w-24 h-24 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="pt-32 pb-16 px-8">
        <div class="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-12 border-t border-slate-50 pt-16">
            <div class="flex flex-col items-center md:items-start gap-4">
                 <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-800 font-bold">B</div>
                    <span class="font-bold text-lg">BotMan</span>
                 </div>
                 <p class="text-slate-400 text-sm font-bold uppercase tracking-widest">Minimal. Powerful. Simple.</p>
            </div>
            <div class="flex gap-12 text-sm font-bold text-slate-400">
                 <a href="#" class="hover:text-blue-600 transition-colors">مستندات</a>
                 <a href="#" class="hover:text-blue-600 transition-colors">تغییرات</a>
                 <a href="https://t.me/your_support" class="hover:text-blue-600 transition-colors">پشتیبانی</a>
            </div>
        </div>
        <div class="max-w-6xl mx-auto mt-16 text-center md:text-right text-[10px] font-black text-slate-200 uppercase tracking-[0.2em]">
            © ۲۰۲۴ پروژه‌ی BotMan — طراحی شده با دقت فراوان.
        </div>
    </footer>

</body>
</html>
