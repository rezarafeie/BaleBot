import React from 'react';
import { motion } from 'motion/react';
import { Bot, Shield, Zap, ArrowRight, Globe, Layers } from 'lucide-react';

export default function App() {
  return (
    <div className="min-h-screen bg-white text-slate-900 font-sans selection:bg-blue-600 selection:text-white" dir="rtl">
      {/* Navigation */}
      <nav className="fixed top-0 w-full z-50 bg-white/50 backdrop-blur-xl border-b border-slate-50">
        <div className="max-w-6xl mx-auto px-8 h-20 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-blue-200">
              <Bot size={22} />
            </div>
            <span className="font-bold text-xl tracking-tight text-slate-900">BotMan</span>
          </div>
          <div className="flex items-center gap-8">
            <nav className="hidden md:flex gap-10 text-sm font-semibold text-slate-500">
              <a href="#features" className="hover:text-blue-600 transition-colors">امکانات</a>
              <a href="#platforms" className="hover:text-blue-600 transition-colors">پلتفرم‌ها</a>
            </nav>
            <div className="flex items-center gap-4">
              <a href="admin/login.php" className="text-sm font-bold text-slate-400 hover:text-slate-900 transition-colors">ورود</a>
              <a href="register.php" className="bg-blue-600 text-white px-6 py-3 rounded-2xl text-sm font-bold shadow-xl shadow-blue-200 hover:bg-blue-700 hover:-translate-y-0.5 transition-all active:scale-95">
                شروع کنید
              </a>
            </div>
          </div>
        </div>
      </nav>

      <main className="relative">
        {/* Animated Background Gradient */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-4xl h-[600px] bg-blue-50/50 blur-[120px] rounded-full pointer-events-none -z-10" />

        {/* Hero Section */}
        <section className="pt-48 pb-32 px-8">
          <div className="max-w-6xl mx-auto text-center">
            <motion.div
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, ease: [0.16, 1, 0.3, 1] }}
            >
              <div className="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-widest mb-10 border border-blue-100 shadow-sm">
                <span className="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse" />
                نسخه پیشرفته ۲.۵
              </div>
              <h1 className="text-6xl md:text-[5.5rem] font-black text-slate-900 mb-10 leading-[1.05] tracking-tight">
                مدیریت ربات، <br />
                <span className="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent italic">به سبک من</span>
              </h1>
              <p className="text-xl md:text-2xl text-slate-400 mb-16 max-w-2xl mx-auto leading-relaxed font-medium">
                پاسخگویی هوشمند، مدیریت متمرکز و پایداری واقعی در تمامی پیام‌رسان‌های بله، تلگرام و روبیکا.
              </p>
              <div className="flex flex-col sm:flex-row gap-6 justify-center items-center">
                <a href="register.php" className="w-full sm:w-auto px-12 py-5 bg-blue-600 text-white rounded-[1.5rem] font-bold text-lg shadow-2xl shadow-blue-200 hover:bg-blue-700 hover:shadow-blue-300 hover:-translate-y-1 transition-all flex items-center justify-center gap-3 active:scale-95">
                  رایگان شروع کنید
                  <ArrowRight size={20} className="rotate-180" />
                </a>
                <a href="admin/login.php" className="w-full sm:w-auto px-12 py-5 border-2 border-slate-100 text-slate-600 rounded-[1.5rem] font-bold text-lg hover:bg-slate-50 transition-all">
                  ورود به حساب
                </a>
              </div>
            </motion.div>
          </div>
        </section>

        {/* Minimal Grid Section */}
        <section id="features" className="py-32 px-8">
          <div className="max-w-6xl mx-auto">
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
              {[
                {
                  icon: <Zap size={24} />,
                  title: "سرعت لحظه‌ای",
                  desc: "پردازش و پاسخگویی به پیام‌ها در کمتر از ۱۰۰ میلی‌ثانیه.",
                  bg: "bg-amber-50",
                  text: "text-amber-600"
                },
                {
                  icon: <Globe size={24} />,
                  title: "مولتی پلتفرم",
                  desc: "یک ربات بسازید و در بله، تلگرام و روبیکا منتشر کنید.",
                  bg: "bg-blue-50",
                  text: "text-blue-600"
                },
                {
                  icon: <Shield size={24} />,
                  title: "امنیت بانکی",
                  desc: "رمزنگاری پیشرفته داده‌ها و ایزولاسیون کامل پلتفرم.",
                  bg: "bg-emerald-50",
                  text: "text-emerald-600"
                }
              ].map((item, i) => (
                <motion.div
                  key={i}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ delay: i * 0.1 }}
                  className="group p-10 bg-white border border-slate-100 rounded-[2.5rem] hover:shadow-2xl hover:shadow-slate-100 transition-all duration-500"
                >
                  <div className={`w-14 h-14 ${item.bg} ${item.text} rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 transition-transform`}>
                    {item.icon}
                  </div>
                  <h3 className="text-2xl font-bold mb-4 text-slate-900">{item.title}</h3>
                  <p className="text-slate-400 leading-relaxed font-medium">{item.desc}</p>
                </motion.div>
              ))}
            </div>
          </div>
        </section>

        {/* Simple CTA Section */}
        <section id="platforms" className="py-32 px-8 bg-slate-950 rounded-[3rem] mx-4 overflow-hidden relative">
          <div className="absolute top-0 right-0 w-96 h-96 bg-blue-600/10 blur-[120px] rounded-full" />
          <div className="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-16 relative z-10">
            <div className="text-right flex-1">
              <h2 className="text-4xl md:text-5xl font-black text-white mb-8 leading-tight">
                با هر پلتفرمی که <br />دوست دارید کار کنید.
              </h2>
              <p className="text-slate-400 text-lg mb-10 max-w-md">
                BotMan با تمامی کتابخانه‌های محبوب و پلتفرم‌های اصلی سازگاری کامل دارد.
              </p>
              <div className="flex flex-wrap gap-4">
                <span className="px-6 py-3 bg-white/5 border border-white/10 rounded-full text-white text-sm font-bold">بله (Bale)</span>
                <span className="px-6 py-3 bg-white/5 border border-white/10 rounded-full text-white text-sm font-bold">تلگرام</span>
                <span className="px-6 py-3 bg-white/5 border border-white/10 rounded-full text-white text-sm font-bold">روبیکا</span>
              </div>
            </div>
            <div className="flex-1 flex justify-center">
              <div className="w-64 h-64 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-[3.5rem] flex items-center justify-center shadow-3xl shadow-blue-500/20 rotate-3 animate-float">
                <Layers size={80} className="text-white" />
              </div>
            </div>
          </div>
        </section>
      </main>

      <footer className="pt-32 pb-16 px-8">
        <div className="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-12 border-t border-slate-50 pt-16">
          <div className="flex flex-col items-center md:items-start gap-4">
             <div className="flex items-center gap-2">
                <div className="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-800">B</div>
                <span className="font-bold text-lg">BotMan</span>
             </div>
             <p className="text-slate-400 text-sm font-bold uppercase tracking-widest">Minimal. Powerful. Simple.</p>
          </div>
          <div className="flex gap-12 text-sm font-bold text-slate-400">
             <a href="#" className="hover:text-blue-600 transition-colors">Documentation</a>
             <a href="#" className="hover:text-blue-600 transition-colors">Changelog</a>
             <a href="#" className="hover:text-blue-600 transition-colors">Support</a>
          </div>
        </div>
        <div className="max-w-6xl mx-auto mt-16 text-center md:text-right text-[10px] font-black text-slate-200 uppercase tracking-[0.2em]">
          © ۲۰۲۴ BotMan Project — Built with precision.
        </div>
      </footer>

      {/* Floating Elements Styles */}
      <style>{`
        @keyframes float {
          0% { transform: translateY(0px) rotate(3deg); }
          50% { transform: translateY(-20px) rotate(5deg); }
          100% { transform: translateY(0px) rotate(3deg); }
        }
        .animate-float {
          animation: float 6s ease-in-out infinite;
        }
      `}</style>
    </div>
  );
}
