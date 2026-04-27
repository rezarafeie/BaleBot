/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

export default function App() {
  return (
    <div className="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-4">
      <div className="bg-white p-8 rounded-lg shadow-xl max-w-2xl w-full text-center">
        <h1 className="text-3xl font-bold text-slate-800 mb-6">پروژه بات بله آماده است! 🎉</h1>
        <p className="text-gray-600 mb-6 leading-relaxed">
          این یک پروژه PHP برای راه‌اندازی بات تلگرام/بله است و تمام فایل‌های پنل مدیریت، 
          کلاس‌های PHP، دیتابیس SQL و وب‌هوک به طور کامل ساخته شدند.
        </p>
        
        <div className="bg-blue-50 border-r-4 border-blue-500 p-4 mb-6 text-right" dir="rtl">
          <ul className="list-disc list-inside text-slate-700 space-y-2">
            <li>فایل پایگاه داده: <span className="font-mono bg-gray-200 px-1 rounded text-sm">sql/install.sql</span></li>
            <li>تنظیمات دیتابیس و توکن: <span className="font-mono bg-gray-200 px-1 rounded text-sm">config.php</span></li>
            <li>فایل هندلر وب‌هوک بله: <span className="font-mono bg-gray-200 px-1 rounded text-sm">webhook.php</span></li>
            <li>پنل مدیریت کامل: <span className="font-mono bg-gray-200 px-1 rounded text-sm">/admin/</span></li>
          </ul>
        </div>

        <p className="text-lg text-slate-700 font-semibold mb-8">
          برای استفاده از این پروژه، لطفاً آن را از منوی سمت راست-بالا (سه نقطه)
          <strong> Export به فایل ZIP </strong> کنید و در یک هاست PHP 8+ آپلود نمایید.
        </p>
        
        <p className="text-sm text-gray-500">
          **نکته امنیتی:** یوزرنیم و پسورد پیش‌فرض ادمین در دیتابیس <code>admin</code> و <code>admin123</code> می‌باشد.
          حتما بعد از نصب آن را از پنل تغییر دهید.
        </p>
      </div>
    </div>
  );
}
