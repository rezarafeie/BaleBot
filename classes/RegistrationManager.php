<?php
// classes/RegistrationManager.php
require_once __DIR__ . '/Database.php';

class RegistrationManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserState($chat_id) {
        $stmt = $this->db->prepare("SELECT * FROM user_states WHERE chat_id = ?");
        $stmt->execute([$chat_id]);
        return $stmt->fetch();
    }

    public function setState($chat_id, $event_id, $step_index, $answers_json, $status) {
        $stmt = $this->db->prepare("INSERT INTO user_states (chat_id, current_event_id, current_step_index, answers_json, status, updated_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE current_event_id=?, current_step_index=?, answers_json=?, status=?, updated_at=NOW()");
        return $stmt->execute([
            $chat_id, $event_id, $step_index, $answers_json, $status,
            $event_id, $step_index, $answers_json, $status
        ]);
    }

    public function clearState($chat_id) {
        return $this->setState($chat_id, null, 0, json_encode([]), 'idle');
    }

    public function completeRegistration($chat_id, $event_id, $answers_json) {
        $stmt = $this->db->prepare("INSERT INTO registrations (event_id, chat_id, answers_json, status, created_at) VALUES (?, ?, ?, 'completed', NOW())");
        $stmt->execute([$event_id, $chat_id, $answers_json]);
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

    public function updateBotUser($chat_id, $bale_user_id, $name, $username) {
        $stmt = $this->db->prepare("INSERT INTO bot_users (chat_id, bale_user_id, name, username, first_interaction_at, last_interaction_at) VALUES (?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE name=COALESCE(?, name), username=?, last_interaction_at=NOW()");
        return $stmt->execute([
            $chat_id, $bale_user_id, $name, $username,
            $name, $username
        ]);
    }

    public function getRegistrations($event_id = null) {
        $sql = "SELECT r.*, e.title as event_title, u.name as user_name, u.phone as user_phone, u.username as user_username FROM registrations r JOIN events e ON r.event_id = e.id LEFT JOIN bot_users u ON r.chat_id = u.chat_id";
        if ($event_id) {
            $sql .= " WHERE r.event_id = ?";
        }
        $sql .= " ORDER BY r.id DESC";

        $stmt = $this->db->prepare($sql);
        if ($event_id) {
            $stmt->execute([$event_id]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }
    
    public function deleteRegistration($id) {
        $this->db->prepare("DELETE FROM registration_answers WHERE registration_id = ?")->execute([$id]);
        return $this->db->prepare("DELETE FROM registrations WHERE id = ?")->execute([$id]);
    }
}
