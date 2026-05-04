import React, { useState } from 'react';
import { motion } from 'motion/react';
import { 
  Bot, 
  ShieldCheck, 
  Zap, 
  Layers, 
  Cpu, 
  ChevronRight, 
  CheckCircle2, 
  Menu, 
  X
} from 'lucide-react';

export default function App() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  return (
    <div className="min-h-screen bg-gray-50 text-slate-900 font-sans selection:bg-blue-100 selection:text-blue-600" dir="rtl">
      {/* Background patterns */}
      <div className="fixed inset-0 z-0 pointer-events-none overflow-hidden">
        <div className="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] bg-blue-400/10 rounded-full blur-[120px]" />
        <div className="absolute -bottom-[10%] -left-[10%] w-[40%] h-[40%] bg-violet-400/10 rounded-full blur-[120px]" />
      </div>

      {/* Header */}
      <header className="fixed top-0 w-full z-50 px-6 py-4">
        <div className="max-w-7xl mx-auto flex justify-between items-center bg-white/70 backdrop-blur-xl border border-white/20 rounded-2xl px-6 py-3 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-gradient-to-br from-blue-600 to-violet-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-500/20">
              B
            </div>
            <span className="text-xl font-black tracking-tight text-slate-800">BotMan</span>
          </div>

          <nav className="hidden md:flex items-center gap-10 text-sm font-semibold text-slate-600">
            <a href="#features" className="hover:text-blue-600 transition-colors">امکانات</a>
            <a href="#platforms" className="hover:text-blue-600 transition-colors">پیام‌رسان‌ها</a>
            <a href="admin/login.php" className="bg-slate-900 text-white px-6 py-2.5 rounded-xl hover:bg-slate-800 transition-all shadow-md active:scale-95">
              ورود به پنل
            </a>
          </nav>

          <button className="md:hidden" onClick={() => setIsMenuOpen(!isMenuOpen)}>
            {isMenuOpen ? <X /> : <Menu />}
          </button>
        </div>

        {/* Mobile Menu */}
        {isMenuOpen && (
          <motion.div 
            initial={{ opacity: 0, y: -20 }}
            animate={{ opacity: 1, y: 0 }}
            className="md:hidden absolute top-20 left-6 right-6 bg-white rounded-2xl shadow-xl p-6 border border-slate-100"
          >
            <div className="flex flex-col gap-4">
              <a href="#features" className="text-lg font-bold p-2" onClick={() => setIsMenuOpen(false)}>امکانات</a>
              <a href="#platforms" className="text-lg font-bold p-2" onClick={() => setIsMenuOpen(false)}>پیام‌رسان‌ها</a>
              <a href="admin/login.php" className="bg-blue-600 text-white text-center py-4 rounded-xl font-bold">ورود به پنل</a>
            </div>
          </motion.div>
        )}
      </header>

      <main className="relative z-10">
        {/* Hero Section */}
        <section className="pt-40 pb-24 px-6">
          <div className="max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-center">
            <motion.div 
              initial={{ opacity: 0, x: 50 }}
              animate={{ opacity: 1, x: 0 }}
              transition={{ duration: 0.6 }}
              className="text-right"
            >
              <div className="inline-flex items-center gap-2 px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-xs font-bold mb-8 tracking-wide uppercase border border-blue-100">
                <span className="relative flex h-2 w-2">
                  <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                  <span className="relative inline-flex rounded-full h-2 w-2 bg-blue-600"></span>
                </span>
                نسخه هوشمند ۲.۰
              </div>
              <h1 className="text-5xl md:text-7xl font-black leading-[1.1] mb-8 text-slate-950">
                لذت مدیریت ربات با <span className="bg-gradient-to-r from-blue-600 to-violet-600 bg-clip-text text-transparent">BotMan</span>
              </h1>
              <p className="text-lg md:text-xl text-slate-600 mb-12 max-w-xl leading-relaxed">
                اولین پلتفرم جامع مدیریت ربات‌های بله، تلگرام و روبیکا. با استفاده از هوش‌مصنوعی، تعامل با مخاطبان خود را به سطحی جدید ارتقا دهید.
              </p>
              <div className="flex flex-wrap gap-5 text-right justify-start" dir="rtl">
                <a href="admin/login.php" className="px-10 py-5 bg-blue-600 text-white rounded-2xl font-bold shadow-2xl shadow-blue-300 hover:bg-blue-700 hover:-translate-y-1 transition-all flex items-center gap-3">
                  شروع رایگان
                  <ChevronRight size={20} className="rotate-180" />
                </a>
                <a href="#features" className="px-10 py-5 bg-white text-slate-700 rounded-2xl font-bold border border-slate-200 hover:bg-slate-50 transition-all">
                  مشاهده امکانات
                </a>
              </div>
            </motion.div>

            <motion.div 
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ duration: 0.8, delay: 0.2 }}
              className="relative hidden lg:block"
            >
              <div className="relative bg-white/40 backdrop-blur-md border border-white/50 rounded-[3rem] p-6 shadow-2xl overflow-hidden -rotate-2 hover:rotate-0 transition-all duration-700 group">
                <div className="bg-slate-900 rounded-[2.5rem] p-8 text-slate-400 font-mono text-xs shadow-inner">
                  <div className="flex gap-2 mb-6">
                    <div className="w-3 h-3 rounded-full bg-red-400/80" />
                    <div className="w-3 h-3 rounded-full bg-amber-400/80" />
                    <div className="w-3 h-3 rounded-full bg-green-400/80" />
                  </div>
                  
                  <div className="space-y-4 mb-8">
                    <div className="p-3 bg-white/5 rounded-xl border border-white/10 flex items-center justify-between group-hover:bg-white/10 transition-colors">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-400">
                          <Bot size={16} />
                        </div>
                        <span className="text-slate-200 font-bold">Bale Official Bot</span>
                      </div>
                      <span className="flex items-center gap-2 text-[10px] text-green-400">
                        <span className="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
                        Connected
                      </span>
                    </div>
                    <div className="p-3 bg-white/5 rounded-xl border border-white/10 flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-lg bg-violet-500/20 flex items-center justify-center text-violet-400">
                          <Zap size={16} />
                        </div>
                        <span className="text-slate-200 font-bold">Telegram Assistant</span>
                      </div>
                      <span className="text-[10px] text-slate-500">Standby</span>
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div className="bg-white/5 rounded-2xl p-5 border border-white/5">
                      <p className="text-[10px] uppercase text-slate-500 mb-2">Active Users</p>
                      <p className="text-3xl font-black text-white">42.8k</p>
                    </div>
                    <div className="bg-white/5 rounded-2xl p-5 border border-white/5">
                      <p className="text-[10px] uppercase text-slate-500 mb-2">Events</p>
                      <p className="text-3xl font-black text-white">124</p>
                    </div>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        </section>

        {/* Features */}
        <section id="features" className="py-32 bg-white relative">
          <div className="max-w-7xl mx-auto px-6">
            <div className="text-center max-w-3xl mx-auto mb-20">
              <h2 className="text-4xl font-black mb-6 text-slate-950">قدرتمند در عین سادگی</h2>
              <p className="text-slate-500 text-lg">BotMan تمامی ابزارهای لازم برای مدیریت یک ربات حرفه‌ای را در اختیار شما قرار می‌دهد.</p>
            </div>

            <div className="grid md:grid-cols-3 gap-8">
              {[
                {
                  icon: <Layers className="text-blue-600" />,
                  title: "مولتی پلتفرم واقعی",
                  desc: "یک محتوا تولید کنید و آن را همزمان در تمامی پیام‌رسان‌ها منتشر کنید.",
                  color: "blue"
                },
                {
                  icon: <Cpu className="text-violet-600" />,
                  title: "یکپارچگی با هوش مصنوعی",
                  desc: "پاسخ‌های ربات را به ChatGPT یا Gemini متصل کنید تا هوشمندانه پاسخ دهد.",
                  color: "violet"
                },
                {
                  icon: <ShieldCheck className="text-emerald-600" />,
                  title: "امنیت و پایداری",
                  desc: "زیرساخت ابری قدرتمند که پایداری ربات شما را در هر لحظه تضمین می‌کند.",
                  color: "emerald"
                }
              ].map((f, i) => (
                <motion.div 
                  key={i}
                  whileHover={{ y: -10 }}
                  className="p-10 rounded-[2.5rem] bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-2xl hover:shadow-slate-200/50 transition-all duration-500"
                >
                  <div className={`w-14 h-14 bg-white rounded-2xl flex items-center justify-center mb-8 shadow-sm`}>
                    {f.icon}
                  </div>
                  <h3 className="text-2xl font-bold mb-4 text-slate-900">{f.title}</h3>
                  <p className="text-slate-500 leading-relaxed">{f.desc}</p>
                </motion.div>
              ))}
            </div>
          </div>
        </section>

        {/* Platforms */}
        <section id="platforms" className="py-32 px-6">
          <div className="max-w-7xl mx-auto bg-slate-900 rounded-[4rem] p-12 md:p-24 overflow-hidden relative">
            <div className="absolute top-0 right-0 w-96 h-96 bg-blue-600/20 blur-[130px] -mr-48 -mt-48" />
            
            <div className="grid lg:grid-cols-2 gap-20 items-center relative z-10">
              <div className="text-right">
                <h2 className="text-4xl md:text-5xl font-black text-white mb-8 leading-tight">
                  پشتیبانی از محبوب‌ترین پیام‌رسان‌ها
                </h2>
                <div className="space-y-6">
                  {[
                    "پشتیبانی کامل از تمامی متدهای بله (Bale Bot API)",
                    "اتصال مستقیم به سرورهای تلگرام بدون نیاز به پروکسی",
                    "یکپارچگی با وب‌سرویس‌های روبیکا به صورت اختصاصی",
                    "مدیریت متمرکز کاربران تمامی پلتفرم‌ها در یک پنل"
                  ].map((text, i) => (
                    <div key={i} className="flex items-center gap-4 justify-start">
                      <div className="w-6 h-6 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
                        <CheckCircle2 size={16} />
                      </div>
                      <span className="text-slate-300 text-lg">{text}</span>
                    </div>
                  ))}
                </div>
              </div>

              <div className="flex justify-center gap-6">
                <div className="flex flex-col gap-6 pt-12">
                   <div className="w-32 h-32 bg-white/5 backdrop-blur-md rounded-3xl border border-white/10 flex items-center justify-center hover:bg-white/10 transition-colors cursor-pointer group">
                      <img src="https://ble.ir/static/images/favicon.ico" alt="Bale" className="w-16 h-16 rounded-2xl group-hover:scale-110 transition-transform" />
                   </div>
                   <div className="w-32 h-32 bg-white/5 backdrop-blur-md rounded-3xl border border-white/10 flex items-center justify-center hover:bg-white/10 transition-colors cursor-pointer group">
                      <img src="https://telegram.org/favicon.ico" alt="Telegram" className="w-16 h-16 rounded-2xl group-hover:scale-110 transition-transform" />
                   </div>
                </div>
                <div className="flex flex-col gap-6">
                   <div className="w-32 h-32 bg-white/5 backdrop-blur-md rounded-3xl border border-white/10 flex items-center justify-center hover:bg-white/10 transition-colors cursor-pointer group">
                      <img src="https://rubika.ir/favicon.ico" alt="Rubika" className="w-16 h-16 rounded-2xl group-hover:scale-110 transition-transform" />
                   </div>
                   <div className="w-32 h-32 bg-blue-600 rounded-3xl flex items-center justify-center shadow-2xl shadow-blue-500/40">
                      <Bot size={48} className="text-white" />
                   </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* CTA / Login Form */}
        <section id="login" className="py-32 px-6">
          <div className="max-w-4xl mx-auto">
            <div className="bg-white rounded-[3.5rem] shadow-2xl overflow-hidden border border-slate-100 flex flex-col md:flex-row">
              <div className="md:w-[45%] bg-slate-900 p-12 text-white flex flex-col justify-between text-right">
                <div>
                  <h2 className="text-4xl font-black mb-8 italic">BotMan</h2>
                  <p className="text-slate-400 leading-relaxed mb-8">
                    آینده مدیریت ربات‌ها همینجاست. وارد شوید و قدرت را در دستان خود بگیرید.
                  </p>
                </div>
                <div className="p-6 bg-white/5 rounded-3xl border border-white/10">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center font-bold">R</div>
                    <div className="text-right">
                      <div className="text-sm font-bold text-white">تیم توسعه BotMan</div>
                      <div className="text-[10px] text-slate-500">پشتیبانی آنلاین</div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div className="md:w-[55%] p-12 md:p-16 text-right">
                <h3 className="text-3xl font-black mb-10 text-slate-900">ورود به پنل</h3>
                
                <form action="admin/login.php" method="POST" className="space-y-6">
                  <div className="text-right">
                    <label className="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 pr-2">نام کاربری</label>
                    <input 
                      type="text" 
                      name="username"
                      required
                      className="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 focus:outline-none focus:border-blue-500 focus:bg-white transition-all text-sm" 
                      placeholder="Username"
                      dir="ltr"
                    />
                  </div>
                  <div className="text-right">
                    <label className="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 pr-2">رمز عبور</label>
                    <input 
                      type="password" 
                      name="password"
                      required
                      className="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 focus:outline-none focus:border-blue-500 focus:bg-white transition-all text-sm" 
                      placeholder="••••••••"
                      dir="ltr"
                    />
                  </div>
                  <button type="submit" className="w-full py-5 bg-gradient-to-r from-blue-600 to-violet-600 text-white rounded-2xl font-bold shadow-xl shadow-blue-200 hover:shadow-blue-300 transition-all flex items-center justify-center gap-3">
                    ورود به حساب کاربری
                    <ChevronRight size={18} className="translate-x-1 rotate-180" />
                  </button>
                </form>

                <p className="mt-8 text-center text-slate-400 text-xs text-right">
                  فراموشی رمز عبور؟ <a href="#" className="text-blue-600 font-bold">با پشتیبانی تماس بگیرید</a>
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>

      <footer className="py-20 bg-slate-50 border-t border-slate-200">
        <div className="max-w-7xl mx-auto px-6">
          <div className="flex flex-col md:flex-row justify-between items-center gap-12 text-center md:text-right">
            <div className="flex flex-col items-center md:items-start gap-4">
              <div className="flex items-center gap-3">
                <div className="w-8 h-8 bg-slate-900 rounded-lg flex items-center justify-center text-white font-bold text-sm">B</div>
                <span className="text-lg font-black text-slate-800">BotMan</span>
              </div>
              <p className="text-slate-400 text-sm max-w-xs text-right">هوشمندترین پلتفرم مدیریت ربات در پیام‌رسان‌های ایرانی.</p>
            </div>
            
            <div className="flex flex-wrap justify-center gap-10 text-sm font-bold text-slate-500 underline-offset-8">
              <a href="#" className="hover:text-blue-600 hover:underline decoration-2">درباره ما</a>
              <a href="#" className="hover:text-blue-600 hover:underline decoration-2">بلاگ</a>
              <a href="#" className="hover:text-blue-600 hover:underline decoration-2">امنیت</a>
              <a href="#" className="hover:text-blue-600 hover:underline decoration-2">قوانین</a>
            </div>
          </div>
          <div className="mt-20 pt-8 border-t border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">
             <div className="flex gap-4">
                <span className="text-green-500">System Status: Online</span>
                <span>V2.4.0-Stable</span>
             </div>
             <span>© ۲۰۲۴ BotMan Project. All rights reserved.</span>
          </div>
        </div>
      </footer>
    </div>
  );
}
