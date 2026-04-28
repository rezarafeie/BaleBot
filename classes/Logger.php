<?php
// classes/Logger.php
class Logger {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS `system_logs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `log_type` varchar(50) NOT NULL,
              `message` text NOT NULL,
              `details` text,
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch(PDOException $e) {}
    }

    public function logHtml($type, $message, $details = null) {
        try {
            $stmt = $this->db->prepare("INSERT INTO system_logs (log_type, message, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $type,
                $message,
                $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null
            ]);
        } catch(PDOException $e) {}
    }

    public static function log($type, $message, $details = null) {
        $logger = new self();
        $logger->logHtml($type, $message, $details);
    }

    public function getLogs($limit = 100, $offset = 0, $type = null) {
        if ($type) {
            $stmt = $this->db->prepare("SELECT * FROM system_logs WHERE log_type = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bindValue(1, $type, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public function countLogs($type = null) {
        if ($type) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM system_logs WHERE log_type = ?");
            $stmt->execute([$type]);
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) FROM system_logs");
        }
        return $stmt->fetchColumn();
    }
}
