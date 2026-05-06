<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BotMan | مدیریت هوشمند ربات‌ها</title>
    <link rel="stylesheet" href="https://lib.arvancloud.ir/vazir-font/33.003/Vazirmatn-font-face.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        :root {
            --font-persian: "Vazirmatn", Tahoma, Arial, "Segoe UI", Roboto, "Helvetica Neue", system-ui, sans-serif;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --slate-900: #0f172a;
            --slate-400: #94a3b8;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
        }
        body { 
            font-family: var(--font-persian); 
            background: white;
            color: var(--slate-900);
            margin: 0;
            direction: rtl;
            line-height: 1.5;
        }
        * { box-sizing: border-box; }
        .max-w-6xl { max-width: 1152px; margin: 0 auto; }
        .px-8 { padding-left: 32px; padding-right: 32px; }
        .flex { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .gap-8 { gap: 32px; }
        .fixed { position: fixed; }
        .top-0 { top: 0; }
        .w-full { width: 100%; }
        .z-50 { z-index: 50; }
        .bg-white { background-color: white; }
        .backdrop-blur-xl { backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px); }
        .border-b { border-bottom: 1px solid var(--slate-100); }
        .h-20 { height: 80px; }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 900; }
        .text-xl { font-size: 20px; }
        .text-sm { font-size: 14px; }
        .text-slate-400 { color: var(--slate-400); }
        .text-slate-900 { color: var(--slate-900); }
        .bg-blue-600 { background-color: var(--primary); }
        .text-white { color: white; }
        .rounded-2xl { border-radius: 16px; }
        .shadow-xl { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        /* Hero */
        .pt-48 { padding-top: 192px; }
        .pb-32 { padding-bottom: 128px; }
        .text-center { text-align: center; }
        .text-6xl { font-size: 60px; }
        .md-text-8xl { font-size: 88px; }
        .mb-10 { margin-bottom: 40px; }
        .mb-16 { margin-bottom: 64px; }
        .leading-relaxed { line-height: 1.625; }
        .max-w-2xl { max-width: 672px; margin-left: auto; margin-right: auto; }
        .btn { display: inline-flex; align-items: center; gap: 12px; padding: 20px 48px; border-radius: 24px; font-weight: 700; text-decoration: none; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 20px 30px -10px rgba(37, 99, 235, 0.3); }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-4px); }
        .btn-outline { border: 2px solid var(--slate-100); color: #475569; }
        .btn-outline:hover { background: var(--slate-50); }
        
        .hero-gradient {
            background: radial-gradient(circle at 50% 50%, rgba(37, 99, 235, 0.05) 0%, transparent 50%);
        }
        @keyframes float {
            0% { transform: translateY(0px) rotate(3deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(3deg); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        
        @media (max-width: 768px) {
            .text-6xl { font-size: 40px; }
            .flex-col-mobile { flex-direction: column; }
            .w-full-mobile { width: 100%; }
        }
    </style>
</head>
<body class="selection:bg-blue-600 selection:text-white">

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 bg-white backdrop-blur-xl border-b" style="background: rgba(255,255,255,0.8);">
        <div class="max-w-6xl mx-auto px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white" style="width: 40px; height: 40px;">
                    <svg style="width: 24px; height: 24px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <span class="font-bold text-xl text-slate-900">BotMan</span>
            </div>
            <div class="flex items-center gap-8">
                <div class="flex items-center gap-4">
                    <a href="admin/login.php" style="font-size: 14px; font-weight: 700; color: var(--slate-400); text-decoration: none; margin-left: 20px;">ورود</a>
                    <a href="register.php" class="bg-blue-600 text-white" style="padding: 12px 24px; border-radius: 16px; font-size: 14px; font-weight: 700; text-decoration: none; box-shadow: 0 10px 15px -3px rgba(37,99,235,0.3);">
                        شروع کنید
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="hero-gradient" style="overflow: hidden; position: relative;">
        <!-- Hero Section -->
        <section class="pt-48 pb-32 px-8">
            <div class="max-w-6xl mx-auto text-center" style="max-width: 1152px;">
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 16px; background: #eff6ff; color: #2563eb; border-radius: 9999px; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 40px; border: 1px solid #dbeafe;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #2563eb;"></span>
                    نسخه پیشرفته ۲.۵
                </div>
                <h1 class="text-6xl font-black text-slate-900 mb-10" style="line-height: 1.1;">
                    مدیریت ربات، <br />
                    <span style="background: linear-gradient(to right, #2563eb, #4f46e5); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-style: italic;">به سبک من</span>
                </h1>
                <p class="max-w-2xl text-slate-400 mb-16" style="font-size: 20px; font-weight: 500; line-height: 1.6;">
                    پاسخگویی هوشمند، مدیریت متمرکز و پایداری واقعی در تمامی پیام‌رسان‌های بله، تلگرام و روبیکا.
                </p>
                <div class="flex flex-col-mobile gap-4" style="display: flex; justify-content: center; align-items: center; gap: 24px;">
                    <a href="register.php" class="btn btn-primary w-full-mobile">
                        رایگان شروع کنید
                        <svg style="width: 20px; height: 20px; transform: rotate(180deg);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                    <a href="admin/login.php" class="btn btn-outline w-full-mobile">
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
