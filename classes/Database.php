<?php
// classes/Database.php
class Database {
    private static $instance = null;
    private $conn = null;
    private $isConnected = false;
    private $connectionError = null;

    private function __construct() {
        if (!defined('DB_TYPE')) return;
        
        $type = defined('DB_TYPE') ? DB_TYPE : 'mysql';

        if ($type === 'mysql') {
            if (!defined('DB_HOST') || !defined('DB_NAME')) {
                $this->isConnected = false;
                $this->connectionError = "Database configuration (Host/Name) is missing.";
                return;
            }
            try {
                $dsn = "mysql:host=" . DB_HOST;
                if (defined('DB_PORT') && DB_PORT) {
                    $dsn .= ";port=" . DB_PORT;
                }
                $dsn .= ";dbname=" . DB_NAME . ";charset=utf8mb4";
                
                $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_TIMEOUT => 3,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                $this->isConnected = true;
            } catch(PDOException $e) {
                $this->isConnected = false;
                $this->connectionError = $e->getMessage();
                error_log("Database Connection failed: " . $e->getMessage());
            }
        } elseif ($type === 'd1') {
            if (!defined('CF_ACCOUNT_ID') || !CF_ACCOUNT_ID) {
                $this->isConnected = false;
                $this->connectionError = "Cloudflare Account ID is missing.";
                return;
            }
            require_once __DIR__ . '/CloudflareD1.php';
            $this->conn = new CloudflareD1(CF_ACCOUNT_ID, CF_DATABASE_ID, CF_API_TOKEN);
            
            // Basic ping to verify
            if ($this->conn->query("SELECT 1")) {
                $this->isConnected = true;
            } else {
                $this->isConnected = false;
                $this->connectionError = $this->conn->getError();
            }
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

    public function getType() {
        return defined('DB_TYPE') ? DB_TYPE : 'mysql';
    }
}
