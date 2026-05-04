<?php
// classes/Database.php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST;
            if (defined('DB_PORT') && DB_PORT) {
                $dsn .= ";port=" . DB_PORT;
            }
            $dsn .= ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $msg = "Database Connection failed: " . $e->getMessage() . "\n";
            $msg .= "Attempted Connection Info:\n";
            $msg .= "Host: " . DB_HOST . "\n";
            if (defined('DB_PORT')) $msg .= "Port: " . DB_PORT . "\n";
            $msg .= "DB: " . DB_NAME . "\n";
            $msg .= "User: " . DB_USER . "\n";
            
            // diagnostic
            $ip = gethostbyname(DB_HOST);
            $msg .= "Host Resolution: " . ($ip === DB_HOST ? "Failed" : "Success ($ip)") . "\n";
            
            error_log($msg);
            die(nl2br(htmlspecialchars($msg)));
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
