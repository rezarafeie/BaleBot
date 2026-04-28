<?php
// classes/EventManager.php
require_once __DIR__ . '/Database.php';

class EventManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        try { $this->db->exec("ALTER TABLE `events` ADD `use_ai` TINYINT(1) DEFAULT 0"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `events` ADD `ai_prompt` TEXT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `events` ADD `ai_wait_message` TEXT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `event_fields` ADD `is_active` TINYINT(1) DEFAULT 1"); } catch(PDOException $e) {}
        
        try {
            $this->db->exec("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('gapgpt_api_key', '')");
            $this->db->exec("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('event_selection_text', '')");
        } catch(PDOException $e) {}
    }

    public function getAllEvents($activeOnly = false) {
        $sql = "SELECT * FROM events";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getEvent($id) {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createEvent($data) {
        $stmt = $this->db->prepare("INSERT INTO events (title, slug, description, welcome_message, completion_message, duplicate_message, is_active, duplicate_setting, use_ai, ai_prompt, ai_wait_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['description'],
            $data['welcome_message'],
            $data['completion_message'],
            $data['duplicate_message'],
            $data['is_active'] ? 1 : 0,
            $data['duplicate_setting'],
            $data['use_ai'] ? 1 : 0,
            $data['ai_prompt'] ?? '',
            $data['ai_wait_message'] ?? ''
        ]);
        return $this->db->lastInsertId();
    }

    public function updateEvent($id, $data) {
        $stmt = $this->db->prepare("UPDATE events SET title=?, slug=?, description=?, welcome_message=?, completion_message=?, duplicate_message=?, is_active=?, duplicate_setting=?, use_ai=?, ai_prompt=?, ai_wait_message=? WHERE id=?");
        return $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['description'],
            $data['welcome_message'],
            $data['completion_message'],
            $data['duplicate_message'],
            $data['is_active'] ? 1 : 0,
            $data['duplicate_setting'],
            $data['use_ai'] ? 1 : 0,
            $data['ai_prompt'] ?? '',
            $data['ai_wait_message'] ?? '',
            $id
        ]);
    }

    public function deleteEvent($id) {
        $stmt = $this->db->prepare("DELETE FROM events WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // --- Fields ---
    public function getEventFields($event_id, $activeOnly = false) {
        if ($activeOnly) {
            $stmt = $this->db->prepare("SELECT * FROM event_fields WHERE event_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        } else {
            $stmt = $this->db->prepare("SELECT * FROM event_fields WHERE event_id = ? ORDER BY sort_order ASC");
        }
        $stmt->execute([$event_id]);
        return $stmt->fetchAll();
    }

    public function getEventField($id) {
        $stmt = $this->db->prepare("SELECT * FROM event_fields WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function addField($event_id, $data) {
        $stmt = $this->db->prepare("INSERT INTO event_fields (event_id, label, field_key, type, is_required, sort_order, validation_rule, help_text, error_message, media_path, options_json, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $event_id,
            $data['label'],
            $data['field_key'],
            $data['type'],
            $data['is_required'] ? 1 : 0,
            $data['sort_order'],
            $data['validation_rule'],
            $data['help_text'],
            $data['error_message'],
            $data['media_path'],
            $data['options_json'],
            1
        ]);
    }

    public function updateField($id, $data) {
        $stmt = $this->db->prepare("UPDATE event_fields SET label=?, field_key=?, type=?, is_required=?, sort_order=?, validation_rule=?, help_text=?, error_message=?, media_path=?, options_json=?, is_active=? WHERE id=?");
        return $stmt->execute([
            $data['label'],
            $data['field_key'],
            $data['type'],
            $data['is_required'] ? 1 : 0,
            $data['sort_order'],
            $data['validation_rule'],
            $data['help_text'],
            $data['error_message'],
            $data['media_path'],
            $data['options_json'],
            $data['is_active'] ? 1 : 0,
            $id
        ]);
    }

    public function toggleFieldActive($id) {
        $stmt = $this->db->prepare("UPDATE event_fields SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteField($id) {
        $stmt = $this->db->prepare("DELETE FROM event_fields WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateFieldOrders($orders) {
        $stmt = $this->db->prepare("UPDATE event_fields SET sort_order = ? WHERE id = ?");
        foreach ($orders as $id => $order) {
            $stmt->execute([$order, $id]);
        }
    }
}
