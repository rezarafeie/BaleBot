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
    'pass' => '',
    'cf_account_id' => '',
    'cf_database_id' => '',
    'cf_api_token' => '',
    'db_type' => 'mysql'
];

if (file_exists($configFile)) {
    $content = file_get_contents($configFile);
    $keys = [
        'host' => 'DB_HOST',
        'port' => 'DB_PORT',
        'db' => 'DB_NAME',
        'user' => 'DB_USER',
        'pass' => 'DB_PASS',
        'cf_account_id' => 'CF_ACCOUNT_ID',
        'cf_database_id' => 'CF_DATABASE_ID',
        'cf_api_token' => 'CF_API_TOKEN',
        'db_type' => 'DB_TYPE'
    ];
    foreach ($keys as $cfgKey => $constKey) {
        // Handle both single and double quotes, and optional getenv pattern
        $pattern = "/define\s*\(\s*['\"]$constKey['\"]\s*,\s*(?:getenv\(['\"]$constKey['\"]\)\s*\?\:\s*)?['\"](.*?)['\"]\s*\)\s*;/i";
        if (preg_match($pattern, $content, $m)) {
            $currentConfig[$cfgKey] = $m[1];
        }
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $dbType = $_POST['db_type'] ?? 'mysql';
    
    // MySQL Params
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? ''; // Remove default 3306 to allow blank
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $dbName = $_POST['db_name'] ?? '';

    // D1 Params
    $cfAccountId = $_POST['cf_account_id'] ?? '';
    $cfDatabaseId = $_POST['cf_database_id'] ?? '';
    $cfApiToken = $_POST['cf_api_token'] ?? '';

    if ($action === 'test' || $action === 'save' || $action === 'migrate') {
        try {
            if ($dbType === 'mysql') {
                $dsn_base = "mysql:host=$host" . ($port ? ";port=$port" : "");
                $pdo = new PDO("$dsn_base;charset=utf8mb4", $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Try to create DB if not exists
                if ($dbName) {
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `$dbName`;"); 
                    $pdo = new PDO("$dsn_base;dbname=$dbName;charset=utf8mb4", $user, $pass);
                }
                $conn = $pdo;
            } else {
                // Cloudflare D1
                require_once __DIR__ . '/classes/CloudflareD1.php';
                $conn = new CloudflareD1($cfAccountId, $cfDatabaseId, $cfApiToken);
                if (!$conn->query("SELECT 1")) {
                    throw new Exception($conn->getError() ?: "D1 Connection Failed");
                }
            }
            
            if ($action === 'test') {
                $status = 'اتصال با موفقیت برقرار شد.';
            }

            if ($action === 'save' || $action === 'migrate') {
                // Update config.php content
                if (file_exists($configFile)) {
                    $content = file_get_contents($configFile);
                    $keys = [
                        'DB_TYPE' => $dbType,
                        'DB_HOST' => $host,
                        'DB_PORT' => $port,
                        'DB_NAME' => $dbName,
                        'DB_USER' => $user,
                        'DB_PASS' => $pass,
                        'CF_ACCOUNT_ID' => $cfAccountId,
                        'CF_DATABASE_ID' => $cfDatabaseId,
                        'CF_API_TOKEN' => $cfApiToken
                    ];
                    
                    foreach ($keys as $key => $val) {
                        // More robust pattern that handles missing defines or varied whitespace
                        $pattern = "/define\s*\(\s*['\"]$key['\"]\s*,\s*(?:getenv\(['\"]$key['\"]\)\s*\?\:\s*)?['\"].*?['\"]\s*\)\s*;/i";
                        $replacement = "define('$key', getenv('$key') ?: '$val');";
                        
                        if (preg_match($pattern, $content)) {
                            $content = preg_replace($pattern, $replacement, $content);
                        } else {
                            // If not found, append before the require_once lines
                            $content = preg_replace("/(require_once)/", "define('$key', getenv('$key') ?: '$val');\n$1", $content, 1);
                        }
                    }
                    
                    if (@file_put_contents($configFile, $content)) {
                        $status = 'تنظیمات با موفقیت در فایل config.php ذخیره شد.';
                    } else {
                        $error = 'خطا: سیستم فایل "فقط خواندنی" (Read-only) است. امکان تغییر config.php وجود ندارد.<br>لطفاً متغیرهای محیطی (Environment Variables) زیر را در پنل هاست خود تنظیم کنید:';
                        $manual_env = [];
                        foreach ($keys as $k => $v) {
                            $manual_env[] = "<strong>$k</strong>: $v";
                        }
                        $error .= '<div style="background: #f8fafc; padding: 12px; margin-top: 8px; border: 1px solid #e2e8f0; font-family: monospace; font-size: 12px; direction: ltr; text-align: left;">' . implode('<br>', $manual_env) . '</div>';
                    }
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
                            // Basic translation for D1 if needed
                            if ($dbType === 'd1') {
                                // SQLite doesn't like backticks often or some MySQL specifics
                                $query = str_replace('NOT NULL AUTO_INCREMENT', 'PRIMARY KEY AUTOINCREMENT', $query);
                                $query = preg_replace('/PRIMARY KEY \(`id`\)/', '', $query); // Remove separate primary key if handled above
                                $query = str_replace('ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', '', $query);
                                $query = preg_replace('/int\(\d+\)/', 'INTEGER', $query);
                                $query = str_replace('datetime', 'DATETIME', $query);
                            }
                            
                            $conn->exec($query);
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
            
            // Try to detect Outbound Public IP
            $publicIp = 'Could not detect';
            $services = ['https://api.ipify.org', 'https://ifconfig.me/ip', 'https://icanhazip.com'];
            foreach ($services as $service) {
                if ($content = @file_get_contents($service, false, stream_context_create(['http' => ['timeout' => 2]]))) {
                    $publicIp = trim($content);
                    break;
                }
            }
            
            $serverIpDetected = ($publicIp !== 'Could not detect') ? $publicIp : ($_SERVER['SERVER_ADDR'] ?? 'N/A');
            $cidr = ($serverIpDetected !== 'N/A') ? $serverIpDetected . '/32' : 'N/A';
            
            $error .= "Your Server Public IP: <span class='text-blue-400 font-bold'>$serverIpDetected</span><br>";
            $error .= "ArvanCloud Whitelist CIDR: <span class='text-amber-400 font-bold select-all'>$cidr</span> (Add this to your DB Whitelist)<br>";
            
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
    <link rel="stylesheet" href="https://lib.arvancloud.ir/vazir-font/33.003/Vazirmatn-font-face.css">
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        :root {
            --font-persian: "Vazirmatn", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, Tahoma, sans-serif;
        }
        body { 
            font-family: var(--font-persian); 
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: screen;
            padding: 24px;
        }
        .container { max-width: 672px; width: 100%; }
        .card { background: white; border-radius: 2.5rem; box-shadow: 0 25px 50px -12px rgba(37, 99, 235, 0.1); border: 1px solid #f1f5f9; overflow: hidden; padding: 40px; }
        .input-group { margin-bottom: 24px; }
        .label { display: block; color: #94a3b8; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
        .input { width: 100%; box-sizing: border-box; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 1rem; padding: 16px 24px; font-size: 14px; outline: none; transition: all 0.2s; font-family: monospace; }
        .input:focus { border-color: #2563eb; background: white; }
        .btn { display: block; width: 100%; padding: 20px; border-radius: 1.5rem; font-weight: 700; cursor: pointer; transition: all 0.2s; appearance: none; border: none; text-align: center; text-decoration: none; font-family: inherit; }
        .btn-primary { background: #2563eb; color: white; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2); }
        .btn-secondary { background: #0f172a; color: white; }
        .btn-ghost { background: white; border: 1px solid #e2e8f0; color: #475569; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .status { padding: 20px; border-radius: 1.5rem; margin-bottom: 32px; font-size: 14px; font-weight: 700; border: 1px solid transparent; display: flex; align-items: center; gap: 12px; }
        .status-success { background: #ecfdf5; color: #059669; border-color: #d1fae5; }
        .status-error { background: #fef2f2; color: #dc2626; border-color: #fee2e2; }
        .text-left { text-align: left; }
        .arvan-tip { margin-top: 15px; padding: 15px; background: #fff7ed; border: 1px solid #ffedd5; border-radius: 1rem; color: #9a3412; font-size: 12px; font-weight: 500; }
    </style>
</head>
<body dir="rtl">

    <div class="max-w-2xl w-full" style="max-width: 672px; margin: 0 auto;">
        <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 overflow-hidden shadow-blue-100/50">
            <div class="p-10 md:p-14" style="padding: 40px;">
                <div class="flex items-center gap-4 mb-10" style="display: flex; align-items: center; gap: 16px; margin-bottom: 40px;">
                    <div style="width: 48px; height: 48px; background: #2563eb; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 24px; box-shadow: 0 10px 15px -3px rgba(37,99,235,0.4);">B</div>
                    <div style="margin-right: 15px;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: 900;">تنظیمات پایگاه داده</h1>
                        <p style="margin: 0; color: #94a3b8; font-size: 14px;">پیکربندی اتصال MySQL و نصب جداول</p>
                    </div>
                </div>

                <?php if ($status): ?>
                    <div class="status status-success">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: #059669; animation: pulse 2s infinite;"></div>
                        <?= $status ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="status status-error" style="flex-direction: column; align-items: flex-start;">
                        <div><?= $error ?></div>
                        
                        <?php if (strpos($error, 'Connection timed out') !== false): ?>
                            <div class="arvan-tip">
                                <strong>⚠️ نکته مهم برای کاربران ابر آروان (ArvanCloud):</strong><br>
                                علاوه بر Whitelist کردن آی‌پی، باید در پنل دیتابیس آروان گزینه <strong>«دسترسی عمومی (Public Data Access)»</strong> را فعال کنید. در غیر این صورت حتی با وایت‌لیست هم امکان اتصال وجود ندارد.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" style="display: flex; flex-direction: column; gap: 24px;">
                    <div class="input-group">
                        <label class="label">نوع پایگاه داده</label>
                        <select name="db_type" id="db_type" class="input" style="font-family: inherit;" onchange="toggleFields()">
                            <option value="mysql" <?= ($currentConfig['db_type'] === 'mysql') ? 'selected' : '' ?>>MySQL / MariaDB</option>
                            <option value="d1" <?= ($currentConfig['db_type'] === 'd1') ? 'selected' : '' ?>>Cloudflare D1 (HTTP API)</option>
                        </select>
                    </div>

                    <!-- MySQL Fields -->
                    <div id="mysql_fields">
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
                            <div class="input-group">
                                <label class="label">میزبان (Host)</label>
                                <input type="text" name="host" value="<?= htmlspecialchars($_POST['host'] ?? $currentConfig['host']) ?>" class="input">
                            </div>
                            <div class="input-group">
                                <label class="label">پورت (Port)</label>
                                <input type="text" name="port" value="<?= htmlspecialchars($_POST['port'] ?? $currentConfig['port']) ?>" class="input">
                            </div>
                        </div>
                        
                        <div class="input-group">
                            <label class="label">نام دیتابیس</label>
                            <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? $currentConfig['db']) ?>" class="input">
                        </div>

                        <div class="grid">
                            <div class="input-group">
                                <label class="label">نام کاربری</label>
                                <input type="text" name="user" value="<?= htmlspecialchars($_POST['user'] ?? $currentConfig['user']) ?>" class="input">
                            </div>
                            <div class="input-group">
                                <label class="label">رمز عبور</label>
                                <input type="password" name="pass" value="<?= htmlspecialchars($_POST['pass'] ?? $currentConfig['pass']) ?>" class="input">
                            </div>
                        </div>
                    </div>

                    <!-- Cloudflare D1 Fields -->
                    <div id="d1_fields" style="display: <?= ($currentConfig['db_type'] === 'd1') ? 'block' : 'none' ?>;">
                        <div class="input-group">
                            <label class="label">Account ID</label>
                            <input type="text" name="cf_account_id" value="<?= htmlspecialchars($_POST['cf_account_id'] ?? $currentConfig['cf_account_id']) ?>" class="input" placeholder="e.g. 5e... (from CF Dashboard)">
                        </div>
                        <div class="input-group">
                            <label class="label">Database ID</label>
                            <input type="text" name="cf_database_id" value="<?= htmlspecialchars($_POST['cf_database_id'] ?? $currentConfig['cf_database_id']) ?>" class="input">
                        </div>
                        <div class="input-group">
                            <label class="label">API Token</label>
                            <input type="password" name="cf_api_token" value="<?= htmlspecialchars($_POST['cf_api_token'] ?? $currentConfig['cf_api_token']) ?>" class="input" placeholder="D1 Edit Permissions Token">
                        </div>
                    </div>

                    <script>
                        function toggleFields() {
                            const type = document.getElementById('db_type').value;
                            document.getElementById('mysql_fields').style.display = type === 'mysql' ? 'block' : 'none';
                            document.getElementById('d1_fields').style.display = type === 'd1' ? 'block' : 'none';
                        }
                        // Init
                        window.onload = toggleFields;
                    </script>

                    <div style="display: flex; flex-direction: column; gap: 16px; padding-top: 24px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                             <button type="submit" name="action" value="test" class="btn btn-ghost">
                                تست اتصال
                            </button>
                            <button type="submit" name="action" value="save" class="btn btn-secondary">
                                ذخیره تنظیمات
                            </button>
                        </div>
                        <button type="submit" name="action" value="migrate" class="btn btn-primary">
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
