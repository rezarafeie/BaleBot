<?php
// classes/RegistrationManager.php
require_once __DIR__ . '/Database.php';

class RegistrationManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        $this->db->exec("CREATE TABLE IF NOT EXISTS `registration_answers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `registration_id` int(11) NOT NULL,
            `field_key` varchar(255) NOT NULL,
            `field_value` text,
            PRIMARY KEY (`id`),
            KEY `registration_id` (`registration_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    public function getUserState($chat_id, $bot_id = null) {
        $key = $bot_id ? "{$chat_id}_{$bot_id}" : $chat_id;
        $cachePath = dirname(__DIR__) . "/data/states/{$key}.json";
        if (file_exists($cachePath)) {
            $data = json_decode(file_get_contents($cachePath), true);
            if ($data && (time() - ($data['updated_at_timestamp'] ?? 0) < 86400)) { // 24h expiry
                return $data;
            }
        }
        
        // Fallback to DB
        $sql = "SELECT * FROM user_states WHERE chat_id = ?";
        $params = [$chat_id];
        if ($bot_id) {
            $sql .= " AND bot_id = ?";
            $params[] = $bot_id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetch();
        if ($data) {
            $data['updated_at_timestamp'] = strtotime($data['updated_at']);
        }
        return $data;
    }

    public function setState($chat_id, $event_id, $step_index, $answers_json, $status, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        
        $data = [
            'chat_id' => $chat_id,
            'bot_id' => $bot_id,
            'current_event_id' => $event_id,
            'current_step_index' => $step_index,
            'answers_json' => $answers_json,
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_at_timestamp' => time()
        ];
        
        $dir = dirname(__DIR__) . "/data/states";
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $key = $bot_id ? "{$chat_id}_{$bot_id}" : $chat_id;
        file_put_contents("{$dir}/{$key}.json", json_encode($data));

        return true;
    }

    public function clearState($chat_id, $bot_id = null) {
        $key = $bot_id ? "{$chat_id}_{$bot_id}" : $chat_id;
        $cachePath = dirname(__DIR__) . "/data/states/{$key}.json";
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
        // Also clear in DB just in case
        $sql = "DELETE FROM user_states WHERE chat_id = ?";
        $params = [$chat_id];
        if ($bot_id) {
            $sql .= " AND bot_id = ?";
            $params[] = $bot_id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return true;
    }

    public function completeRegistration($chat_id, $event_id, $answers_json, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        $stmt = $this->db->prepare("INSERT INTO registrations (event_id, chat_id, bot_id, answers_json, status, created_at) VALUES (?, ?, ?, ?, 'completed', NOW())");
        $stmt->execute([$event_id, $chat_id, $bot_id, $answers_json]);
        $reg_id = $this->db->lastInsertId();

        $answers = json_decode($answers_json, true) ?: [];
        $stmt_ans = $this->db->prepare("INSERT INTO registration_answers (registration_id, field_key, field_value) VALUES (?, ?, ?)");
        
        // Also update contact info in bot_users if phone was provided
        $phone_to_update = null;
        $name_to_update = null;

        foreach ($answers as $k => $v) {
            $val_str = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v;
            $stmt_ans->execute([$reg_id, $k, $val_str]);

            if ($k === 'phone' || $k === 'phone_number') $phone_to_update = $val_str;
            if ($k === 'name' || $k === 'full_name' || $k === 'first_name') $name_to_update = $val_str;
        }

        if ($phone_to_update || $name_to_update) {
            $q = "UPDATE bot_users SET ";
            $params = [];
            if ($name_to_update) { $q .= "name = ?, "; $params[] = $name_to_update; }
            if ($phone_to_update) { $q .= "phone = ?, "; $params[] = $phone_to_update; }
            $q = rtrim($q, ", ");
            $q .= " WHERE chat_id = ?";
            $params[] = $chat_id;
            $this->db->prepare($q)->execute($params);
        }

        return $reg_id;
    }

    public function checkDuplicate($chat_id, $event_id, $setting) {
        if ($setting == 'allow') return false;
        
        if ($setting == 'block_chat_id') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM registrations WHERE chat_id = ? AND event_id = ?");
            $stmt->execute([$chat_id, $event_id]);
            return $stmt->fetchColumn() > 0;
        }

        if ($setting == 'block_phone') {
            // Need phone from bot_users to check accurately
            $stmt = $this->db->prepare("SELECT phone FROM bot_users WHERE chat_id = ?");
            $stmt->execute([$chat_id]);
            $phone = $stmt->fetchColumn();
            
            if ($phone) {
                // Check if any other registration for this event has this phone
                $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM registrations r JOIN registration_answers a ON r.id = a.registration_id WHERE r.event_id = ? AND a.field_key IN ('phone', 'phone_number') AND a.field_value = ?");
                $stmt2->execute([$event_id, $phone]);
                return $stmt2->fetchColumn() > 0;
            }
        }
        return false;
    }

    public function updateBotUser($chat_id, $bale_user_id, $name, $username, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        // Cache to avoid frequent DB updates for same user session
        $key = $bot_id ? "{$chat_id}_{$bot_id}" : $chat_id;
        $cachePath = dirname(__DIR__) . "/data/users/{$key}.json";
        if (file_exists($cachePath)) {
            $data = json_decode(file_get_contents($cachePath), true);
            if ($data && (time() - ($data['last_sync'] ?? 0) < 3600)) { // Sync once per hour
                return true; 
            }
        }

        $stmt = $this->db->prepare("INSERT INTO bot_users (chat_id, bale_user_id, name, username, bot_id, first_interaction_at, last_interaction_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE name=COALESCE(?, name), username=?, last_interaction_at=NOW()");
        $res = $stmt->execute([
            $chat_id, $bale_user_id, $name, $username, $bot_id,
            $name, $username
        ]);

        $dir = dirname(__DIR__) . "/data/users";
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        file_put_contents($cachePath, json_encode(['last_sync' => time()]));

        return $res;
    }

    public function getRegistrations($event_id = null, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }

        $sql = "SELECT r.*, e.title as event_title, u.name as user_name, u.phone as user_phone, u.username as user_username FROM registrations r JOIN events e ON r.event_id = e.id LEFT JOIN bot_users u ON r.chat_id = u.chat_id AND r.bot_id = u.bot_id";
        $where = [];
        $params = [];

        if ($event_id) {
            $where[] = "r.event_id = ?";
            $params[] = $event_id;
        }
        if ($bot_id) {
            $where[] = "r.bot_id = ?";
            $params[] = $bot_id;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY r.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function deleteRegistration($id) {
        $this->db->prepare("DELETE FROM registration_answers WHERE registration_id = ?")->execute([$id]);
        return $this->db->prepare("DELETE FROM registrations WHERE id = ?")->execute([$id]);
    }
}
