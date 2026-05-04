<?php
// classes/RegistrationManager.php
require_once __DIR__ . '/Database.php';

class RegistrationManager {
    private $db;

    public function __construct() {
        $dbInstance = Database::getInstance();
        if ($dbInstance->isConnected()) {
            $this->db = $dbInstance->getConnection();
            $this->db->exec("CREATE TABLE IF NOT EXISTS `registration_answers` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `registration_id` int(11) NOT NULL,
                `field_key` varchar(255) NOT NULL,
                `field_value` text,
                `bot_id` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `registration_id` (`registration_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            // Try to sync queue
            require_once __DIR__ . '/SyncManager.php';
            SyncManager::processQueue();
        }
    }

    public function getUserState($chat_id, $bot_id = 1) {
        $key = "{$chat_id}_{$bot_id}";
        $cachePath = dirname(__DIR__) . "/data/states/{$key}.json";
        if (file_exists($cachePath)) {
            $data = json_decode(file_get_contents($cachePath), true);
            if ($data && (time() - ($data['updated_at_timestamp'] ?? 0) < 86400)) { // 24h expiry
                return $data;
            }
        }
        
        if ($this->db) {
            // Fallback to DB
            $stmt = $this->db->prepare("SELECT * FROM user_states WHERE chat_id = ? AND bot_id = ?");
            $stmt->execute([$chat_id, $bot_id]);
            $data = $stmt->fetch();
            if ($data) {
                $data['updated_at_timestamp'] = strtotime($data['updated_at']);
            }
            return $data;
        }
        return null;
    }

    public function setState($chat_id, $event_id, $step_index, $answers_json, $status, $bot_id = 1) {
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
        $key = "{$chat_id}_{$bot_id}";
        file_put_contents("{$dir}/{$key}.json", json_encode($data));

        if ($this->db) {
            // Sync with DB
            $stmt = $this->db->prepare("INSERT INTO user_states (chat_id, bot_id, current_event_id, current_step_index, answers_json, status, updated_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, NOW()) 
                                       ON DUPLICATE KEY UPDATE current_event_id=?, current_step_index=?, answers_json=?, status=?, updated_at=NOW()");
            $stmt->execute([
                $chat_id, $bot_id, $event_id, $step_index, $answers_json, $status,
                $event_id, $step_index, $answers_json, $status
            ]);
        } else {
            require_once __DIR__ . '/LocalStore.php';
            LocalStore::getInstance()->queueSync('save', 'user_states', $data);
        }

        return true;
    }

    public function clearState($chat_id, $bot_id = 1) {
        $key = "{$chat_id}_{$bot_id}";
        $cachePath = dirname(__DIR__) . "/data/states/{$key}.json";
        if (file_exists($cachePath)) {
            @unlink($cachePath);
        }
        // Also clear in DB just in case
        $stmt = $this->db->prepare("DELETE FROM user_states WHERE chat_id = ? AND bot_id = ?");
        $stmt->execute([$chat_id, $bot_id]);
        return true;
    }

    public function completeRegistration($chat_id, $event_id, $answers_json, $bot_id = null, $platform = 'bale') {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }

        $answers = json_decode($answers_json, true) ?: [];
        $created_at = date('Y-m-d H:i:s');

        if ($this->db) {
            $stmt = $this->db->prepare("INSERT INTO registrations (event_id, chat_id, bot_id, answers_json, status, platform, created_at) VALUES (?, ?, ?, ?, 'completed', ?, NOW())");
            $stmt->execute([$event_id, $chat_id, $bot_id, $answers_json, $platform]);
            $reg_id = $this->db->lastInsertId();

            $stmt_ans = $this->db->prepare("INSERT INTO registration_answers (registration_id, field_key, field_value, bot_id) VALUES (?, ?, ?, ?)");
            
            foreach ($answers as $k => $v) {
                $val_str = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v;
                $stmt_ans->execute([$reg_id, $k, $val_str, $bot_id]);
            }
        } else {
            require_once __DIR__ . '/LocalStore.php';
            $data = [
                'event_id' => $event_id,
                'chat_id' => $chat_id,
                'bot_id' => $bot_id,
                'answers_json' => $answers_json,
                'status' => 'completed',
                'platform' => $platform,
                'created_at' => $created_at,
                'answers' => $answers
            ];
            LocalStore::getInstance()->queueSync('save', 'registrations', $data);
            $reg_id = "local_" . time();
        }
        
        // Update contact info if phone was provided
        $phone_to_update = null;
        $name_to_update = null;

        foreach ($answers as $k => $v) {
            $val_str = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v;
            $k_lower = strtolower($k);
            if (strpos($k_lower, 'phone') !== false || strpos($k_lower, 'شماره') !== false) {
                $phone_to_update = $val_str;
            }
            if (strpos($k_lower, 'name') !== false || strpos($k_lower, 'نام') !== false) {
                $name_to_update = $val_str;
            }
        }

        if ($phone_to_update || $name_to_update) {
            $this->updateBotUser($chat_id, null, $name_to_update, null, $bot_id, $platform, $phone_to_update);
        }

        return $reg_id;
    }

    public function checkDuplicate($chat_id, $event_id, $setting, $bot_id = null) {
        if ($setting == 'allow') return false;
        
        if ($setting == 'block_chat_id') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM registrations WHERE chat_id = ? AND event_id = ?");
            $stmt->execute([$chat_id, $event_id]);
            return $stmt->fetchColumn() > 0;
        }

        if ($setting == 'block_phone') {
            // Need phone from bot_users to check accurately
            $stmt = $this->db->prepare("SELECT phone FROM bot_users WHERE chat_id = ? AND bot_id = ?");
            $stmt->execute([$chat_id, $bot_id]);
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

    public function updateBotUser($chat_id, $bale_user_id, $name, $username, $bot_id = null, $platform = 'bale', $phone = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        
        $data = [
            'chat_id' => $chat_id,
            'bale_user_id' => $bale_user_id,
            'name' => $name,
            'username' => $username,
            'phone' => $phone,
            'bot_id' => $bot_id,
            'platform' => $platform,
            'first_interaction_at' => date('Y-m-d H:i:s'),
            'last_interaction_at' => date('Y-m-d H:i:s')
        ];

        if ($this->db) {
            $q = "INSERT INTO bot_users (chat_id, bale_user_id, name, username, phone, bot_id, platform, first_interaction_at, last_interaction_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) 
                  ON DUPLICATE KEY UPDATE name=COALESCE(?, name), username=COALESCE(?, username), phone=COALESCE(?, phone), last_interaction_at=NOW()";
            $stmt = $this->db->prepare($q);
            $res = $stmt->execute([
                $chat_id, $bale_user_id, $name, $username, $phone, $bot_id, $platform,
                $name, $username, $phone
            ]);
        } else {
            require_once __DIR__ . '/LocalStore.php';
            LocalStore::getInstance()->queueSync('save', 'bot_users', $data);
            $res = true;
        }

        return $res;
    }

    public function getRegistrations($event_id = null, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }

        $results = [];
        if ($this->db) {
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
            $results = $stmt->fetchAll();
        }
        
        // Merge from local queue
        require_once __DIR__ . '/LocalStore.php';
        $queue = LocalStore::getInstance()->getQueue();
        foreach ($queue as $item) {
            if ($item['collection'] === 'registrations') {
                $data = $item['data'];
                if (($event_id && $data['event_id'] != $event_id) || ($bot_id && $data['bot_id'] != $bot_id)) continue;
                
                $results[] = [
                    'id' => 'pending_' . $item['timestamp'],
                    'event_id' => $data['event_id'],
                    'chat_id' => $data['chat_id'],
                    'bot_id' => $data['bot_id'],
                    'answers_json' => $data['answers_json'],
                    'status' => $data['status'] . ' (درحال همگام‌سازی)',
                    'platform' => $data['platform'],
                    'created_at' => $data['created_at'] . ' (محلی)',
                    'event_title' => 'رویداد #' . $data['event_id'],
                    'user_name' => 'کاربر محلی',
                    'user_phone' => '',
                    'user_username' => ''
                ];
            }
        }

        return $results;
    }
    
    public function deleteRegistration($id, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        $sql = "SELECT id FROM registrations WHERE id = ?";
        $params = [$id];
        if ($bot_id) {
            $sql .= " AND bot_id = ?";
            $params[] = $bot_id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) return false;

        $this->db->prepare("DELETE FROM registration_answers WHERE registration_id = ?")->execute([$id]);
        return $this->db->prepare("DELETE FROM registrations WHERE id = ?")->execute([$id]);
    }
}
