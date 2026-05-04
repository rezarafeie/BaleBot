<?php
/**
 * Database Setup & Configuration
 * Provides an interface to setup DB connection and run SQL scripts.
 */

// Error reporting for setup phase
ini_set('display_errors', 1);
error_reporting(E_ALL);

$configFile = __DIR__ . '/config.php';
$sqlFile = __DIR__ . '/sql/install.sql';

$status = '';
$error = '';

// Load current config if exists
$currentConfig = [
    'host' => 'localhost',
    'port' => '3306',
    'db' => 'botman_db',
    'user' => 'root',
    'pass' => ''
];

if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    if (preg_match("/define\('DB_HOST', '(.*?)'\);/", $content, $m)) $currentConfig['host'] = $m[1];
    if (preg_match("/define\('DB_PORT', '(.*?)'\);/", $content, $m)) $currentConfig['port'] = $m[1];
    if (preg_match("/define\('DB_NAME', '(.*?)'\);/", $content, $m)) $currentConfig['db'] = $m[1];
    if (preg_match("/define\('DB_USER', '(.*?)'\);/", $content, $m)) $currentConfig['user'] = $m[1];
    if (preg_match("/define\('DB_PASS', '(.*?)'\);/", $content, $m)) $currentConfig['pass'] = $m[1];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? '3306';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $dbName = $_POST['db_name'] ?? '';

    if ($action === 'test' || $action === 'save' || $action === 'migrate') {
        try {
            $dsn_base = "mysql:host=$host" . ($port ? ";port=$port" : "");
            $pdo = new PDO("$dsn_base;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Try to create DB if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbName`;"); 
            $pdo = new PDO("$dsn_base;dbname=$dbName;charset=utf8mb4", $user, $pass);
            
            if ($action === 'test') {
                $status = 'اتصال با موفقیت برقرار شد و دیتابیس در دسترس است.';
            }

            if ($action === 'save' || $action === 'migrate') {
                // Update config.php content
                if (file_exists($configFile)) {
                    $content = file_get_contents($configFile);
                    $content = preg_replace("/define\('DB_HOST', '.*?'\);/", "define('DB_HOST', '$host');", $content);
                    
                    if (strpos($content, 'DB_PORT') !== false) {
                        $content = preg_replace("/define\('DB_PORT', '.*?'\);/", "define('DB_PORT', '$port');", $content);
                    } else {
                        $content = str_replace("define('DB_HOST', '$host');", "define('DB_HOST', '$host');\ndefine('DB_PORT', '$port');", $content);
                    }

                    $content = preg_replace("/define\('DB_NAME', '.*?'\);/", "define('DB_NAME', '$dbName');", $content);
                    $content = preg_replace("/define\('DB_USER', '.*?'\);/", "define('DB_USER', '$user');", $content);
                    $content = preg_replace("/define\('DB_PASS', '.*?'\);/", "define('DB_PASS', '$pass');", $content);
                    file_put_contents($configFile, $content);
                    $status = 'تنظیمات با موفقیت در فایل config.php ذخیره شد.';
                } else {
                    $error = 'فایل config.php یافت نشد.';
                }
            }

            if ($action === 'migrate') {
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    // Minimal splitter for SQL file (handles basic statements)
                    $queries = explode(';', $sql);
                    $count = 0;
                    foreach ($queries as $query) {
                        $query = trim($query);
                        if (!empty($query)) {
                            $pdo->exec($query);
                            $count++;
                        }
                    }
                    $status .= " | جداول پایگاه داده با موفقیت ساخته شدند ($count کوئری اجرا شد).";
                } else {
                    $error = 'فایل SQL نصب یافت نشد.';
                }
            }

        } catch (PDOException $e) {
            $error = 'خطا در اتصال یا اجرای عملیات: ' . $e->getMessage();
            
            // diagnostic info
            $error .= "<br><br><div class='text-left font-mono text-[10px] bg-slate-900 text-slate-300 p-4 rounded-xl space-y-1'>";
            $error .= "Attempted Connection Details:<br>";
            $error .= "Host: $host<br>";
            $error .= "Port: $port<br>";
            $error .= "User: $user<br>";
            $error .= "DB Name: $dbName<br>";
            $error .= "DSN: $dsn_base<br>";
            
            // Try to get server public IP
            $serverIp = $_SERVER['SERVER_ADDR'] ?? 'Could not detect';
            $cidr = ($serverIp !== 'Could not detect') ? $serverIp . '/32' : 'N/A';
            $error .= "Your Server Public IP: <span class='text-blue-400 font-bold'>$serverIp</span><br>";
            $error .= "Whitelist CIDR (ArvanCloud): <span class='text-amber-400 font-bold select-all'>$cidr</span> (Copy this to whitelist)<br>";
            
            // Try to resolve host
            $ip = gethostbyname($host);
            if ($ip === $host) {
                $error .= "Host Resolution: Failed (Could not resolve hostname)<br>";
            } else {
                $error .= "Host Resolution: Success (IP: $ip)<br>";
                
                // Try a basic socket test to check if port is open
                $fp = @fsockopen($host, (int)$port, $errno, $errstr, 2);
                if (!$fp) {
                    $error .= "Port Check ($port): Closed/Filtered ($errstr [$errno])<br>";
                } else {
                    $error .= "Port Check ($port): Open<br>";
                    fclose($fp);
                }
            }
            $error .= "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیمات پایگاه داده | BotMan</title>
    <link rel="stylesheet" href="assets/css/tailwind-compiled.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 overflow-hidden shadow-blue-100/50">
            <div class="p-10 md:p-14">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl shadow-lg shadow-blue-200">B</div>
                    <div>
                        <h1 class="text-2xl font-black">تنظیمات پایگاه داده</h1>
                        <p class="text-slate-400 text-sm">پیکربندی اتصال MySQL و نصب جداول</p>
                    </div>
                </div>

                <?php if ($status): ?>
                    <div class="bg-emerald-50 text-emerald-600 p-5 rounded-2xl mb-8 text-sm font-bold border border-emerald-100 flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></div>
                        <?= $status ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-rose-50 text-rose-600 p-5 rounded-2xl mb-8 text-sm font-bold border border-rose-100 italic">
                        <?= $error ?>
                        <?php if (strpos($error, 'timed out') !== false): ?>
                            <div class="mt-2 text-xs font-normal text-rose-500 not-italic">
                                راهنما: اگر با خطای Timeout مواجه شدید، اطمینان حاصل کنید که دیتابیس شما اجازه دسترسی از خارج (External Access) را دارد و آی‌پی‌های سرور در لیست سفید (Whitelist) قرار دارند.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-slate-400 text-xs font-black uppercase tracking-widest mb-3 pr-2">میزبان (Host)</label>
                            <input type="text" name="host" value="<?= htmlspecialchars($_POST['host'] ?? $currentConfig['host']) ?>" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition-all font-mono" required>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-black uppercase tracking-widest mb-3 pr-2">پورت (Port)</label>
                            <input type="text" name="port" value="<?= htmlspecialchars($_POST['port'] ?? $currentConfig['port']) ?>" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition-all font-mono" required>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-slate-400 text-xs font-black uppercase tracking-widest mb-3 pr-2">نام دیتابیس</label>
                        <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? $currentConfig['db']) ?>" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition-all font-mono" required>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-slate-400 text-xs font-black uppercase tracking-widest mb-3 pr-2">نام کاربری</label>
                            <input type="text" name="user" value="<?= htmlspecialchars($_POST['user'] ?? $currentConfig['user']) ?>" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition-all font-mono" required>
                        </div>
                        <div>
                            <label class="block text-slate-400 text-xs font-black uppercase tracking-widest mb-3 pr-2">رمز عبور</label>
                            <input type="password" name="pass" value="<?= htmlspecialchars($_POST['pass'] ?? $currentConfig['pass']) ?>" class="w-full bg-slate-50 border border-slate-100 rounded-2xl px-6 py-4 text-sm focus:outline-none focus:border-blue-500 focus:bg-white transition-all font-mono">
                        </div>
                    </div>

                    <div class="pt-6 flex flex-col gap-4">
                        <div class="grid grid-cols-2 gap-4">
                             <button type="submit" name="action" value="test" class="py-4 bg-white border border-slate-200 text-slate-600 rounded-2xl font-bold hover:bg-slate-50 transition-all">
                                تست اتصال
                            </button>
                            <button type="submit" name="action" value="save" class="py-4 bg-slate-900 text-white rounded-2xl font-bold hover:bg-slate-800 transition-all">
                                ذخیره تنظیمات
                            </button>
                        </div>
                        <button type="submit" name="action" value="migrate" class="py-5 bg-blue-600 text-white rounded-2xl font-bold shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            اجرای SQL و نهایی‌سازی
                        </button>
                    </div>
                </form>

                <div class="mt-10 pt-10 border-t border-slate-50 text-center">
                    <a href="index.php" class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] hover:text-blue-600 transition-colors">برگشت به صفحه اصلی</a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
