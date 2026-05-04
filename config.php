<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cloudflare D1 Support
define('CF_ACCOUNT_ID', getenv('CF_ACCOUNT_ID') ?: ''); 
define('CF_DATABASE_ID', getenv('CF_DATABASE_ID') ?: '1ef8dd3e-1f18-429c-b42c-dca29b965c8d');
define('CF_API_TOKEN', getenv('CF_API_TOKEN') ?: '');
define('DB_TYPE', getenv('DB_TYPE') ?: 'mysql'); // mysql, d1
define('DB_NAME', getenv('DB_NAME') ?: 'bale_bot_db');

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

define('BOT_TOKEN', 'YOUR_BALE_BOT_TOKEN');
define('WEBHOOK_SECRET', 'my_super_secret_string');

define('BASE_URL', 'https://yourdomain.com'); // Without trailing slash

// Set timezone
date_default_timezone_set('Asia/Tehran');

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Logger.php';
