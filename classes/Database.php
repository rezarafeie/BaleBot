<?php
// classes/Database.php
class Database {
    private static $instance = null;
    private $conn = null;
    private $isConnected = false;
    private $connectionError = null;

    private function __construct() {
        if (!defined('DB_HOST')) return; // Not configured yet
        
        try {
            $dsn = "mysql:host=" . DB_HOST;
            if (defined('DB_PORT') && DB_PORT) {
                $dsn .= ";port=" . DB_PORT;
            }
            $dsn .= ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_TIMEOUT => 3, // 3 seconds timeout
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $this->isConnected = true;
        } catch(PDOException $e) {
            $this->isConnected = false;
            $this->connectionError = $e->getMessage();
            error_log("Database Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function isConnected() {
        return $this->isConnected;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getError() {
        return $this->connectionError;
    }
}
