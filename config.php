<?php
// config.php
session_start();

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bale_bot_db');

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
