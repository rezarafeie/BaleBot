<?php
// classes/Logger.php
class Logger {
    private $db;

    public function __construct() {
        $dbInstance = Database::getInstance();
        if ($dbInstance->isConnected()) {
            $this->db = $dbInstance->getConnection();
            
            try {
                $this->db->exec("CREATE TABLE IF NOT EXISTS `system_logs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `bot_id` int(11) DEFAULT 1,
                  `log_type` varchar(50) NOT NULL,
                  `message` text NOT NULL,
                  `details` text,
                  `created_at` datetime NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } catch(PDOException $e) {}
        }
    }

    public function logHtml($type, $message, $details = null, $bot_id = 1) {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("INSERT INTO system_logs (bot_id, log_type, message, details, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $bot_id,
                    $type,
                    $message,
                    $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null
                ]);
            } catch(PDOException $e) {}
        } else {
            // Local fallback
            $logDir = dirname(__DIR__) . '/data/logs';
            if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
            $logFile = $logDir . '/' . date('Y-m-d') . '.log';
            $logEntry = [
                'bot_id' => $bot_id,
                'type' => $type,
                'message' => $message,
                'details' => $details,
                'created_at' => date('Y-m-d H:i:s')
            ];
            file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
        }
    }

    public static function log($type, $message, $details = null, $bot_id = 1) {
        $logger = new self();
        $logger->logHtml($type, $message, $details, $bot_id);
    }

    public function getLogs($limit = 100, $offset = 0, $type = null, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }

        $sql = "SELECT * FROM system_logs";
        $params = [];
        $where = [];

        if ($bot_id) {
            $where[] = "bot_id = ?";
            $params[] = $bot_id;
        }

        if ($type) {
            $where[] = "log_type = ?";
            $params[] = $type;
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p);
        }
        $stmt->bindValue($i++, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countLogs($type = null, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }

        $sql = "SELECT COUNT(*) FROM system_logs";
        $params = [];
        $where = [];

        if ($bot_id) {
            $where[] = "bot_id = ?";
            $params[] = $bot_id;
        }

        if ($type) {
            $where[] = "log_type = ?";
            $params[] = $type;
        }

        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
