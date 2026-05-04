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
        try { $this->db->exec("ALTER TABLE `events` ADD `action_type` VARCHAR(50) DEFAULT 'none'"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `events` ADD `action_webhook_url` TEXT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `events` ADD `action_webhook_body` TEXT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `events` ADD `action_http_url` TEXT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `event_fields` ADD `is_active` TINYINT(1) DEFAULT 1"); } catch(PDOException $e) {}
    }

    public function syncCache($bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        if (!$bot_id) return;

        $events = $this->getAllEvents(true, $bot_id);
        $cache = [];
        foreach ($events as $event) {
            $fields = $this->getEventFields($event['id'], true);
            $event['fields'] = $fields;
            $cache[$event['id']] = $event;
            // Also index by slug if available
            if (!empty($event['slug'])) {
                $cache['slug_' . $event['slug']] = $event['id'];
            }
        }
        
        $cachePath = dirname(__DIR__) . "/data/events_cache_{$bot_id}.json";
        if (!is_dir(dirname($cachePath))) {
            @mkdir(dirname($cachePath), 0777, true);
        }
        file_put_contents($cachePath, json_encode($cache, JSON_UNESCAPED_UNICODE));
    }

    public function getCachedData($bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        if (!$bot_id) return [];

        $cachePath = dirname(__DIR__) . "/data/events_cache_{$bot_id}.json";
        if (file_exists($cachePath)) {
            $data = json_decode(file_get_contents($cachePath), true);
            if (!empty($data)) return $data;
        }
        $this->syncCache($bot_id);
        return json_decode(file_get_contents($cachePath), true) ?: [];
    }

    public function getAllEvents($activeOnly = false, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        $sql = "SELECT * FROM events";
        $params = [];
        $where = [];
        
        if ($bot_id) {
            $where[] = "bot_id = ?";
            $params[] = $bot_id;
        }
        
        if ($activeOnly) {
            $where[] = "is_active = 1";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getActiveEvents($bot_id) {
        $stmt = $this->db->prepare("SELECT * FROM events WHERE bot_id = ? AND is_active = 1");
        $stmt->execute([$bot_id]);
        return $stmt->fetchAll();
    }

    public function getEvent($id, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        $sql = "SELECT * FROM events WHERE id = ?";
        $params = [$id];
        if ($bot_id) {
            $sql .= " AND bot_id = ?";
            $params[] = $bot_id;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function createEvent($data) {
        $bot_id = $data['bot_id'] ?? ($_SESSION['selected_bot_id'] ?? 1);
        $stmt = $this->db->prepare("INSERT INTO events (bot_id, title, slug, description, welcome_message, welcome_media_id, completion_message, completion_media_id, duplicate_message, is_active, duplicate_setting, use_ai, ai_prompt, ai_wait_message, ai_wait_media_id, action_type, action_webhook_url, action_webhook_body, action_http_url, platforms, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $bot_id,
            $data['title'],
            $data['slug'],
            $data['description'],
            $data['welcome_message'],
            !empty($data['welcome_media_id']) ? $data['welcome_media_id'] : null,
            $data['completion_message'],
            !empty($data['completion_media_id']) ? $data['completion_media_id'] : null,
            $data['duplicate_message'],
            $data['is_active'] ? 1 : 0,
            $data['duplicate_setting'] ?? 'none',
            $data['use_ai'] ? 1 : 0,
            $data['ai_prompt'] ?? '',
            $data['ai_wait_message'] ?? '',
            !empty($data['ai_wait_media_id']) ? $data['ai_wait_media_id'] : null,
            $data['action_type'] ?? 'none',
            $data['action_webhook_url'] ?? '',
            $data['action_webhook_body'] ?? '',
            $data['action_http_url'] ?? '',
            isset($data['platforms']) ? (is_array($data['platforms']) ? json_encode($data['platforms']) : $data['platforms']) : '["bale"]'
        ]);
        $id = $this->db->lastInsertId();
        $this->syncCache($bot_id);
        return $id;
    }

    public function updateEvent($id, $data, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        $sql = "UPDATE events SET title=?, slug=?, description=?, welcome_message=?, welcome_media_id=?, completion_message=?, completion_media_id=?, duplicate_message=?, is_active=?, duplicate_setting=?, use_ai=?, ai_prompt=?, ai_wait_message=?, ai_wait_media_id=?, action_type=?, action_webhook_url=?, action_webhook_body=?, action_http_url=?, platforms=? WHERE id=?";
        $params = [
            $data['title'],
            $data['slug'],
            $data['description'],
            $data['welcome_message'],
            !empty($data['welcome_media_id']) ? $data['welcome_media_id'] : null,
            $data['completion_message'],
            !empty($data['completion_media_id']) ? $data['completion_media_id'] : null,
            $data['duplicate_message'],
            $data['is_active'] ? 1 : 0,
            $data['duplicate_setting'] ?? 'none',
            $data['use_ai'] ? 1 : 0,
            $data['ai_prompt'] ?? '',
            $data['ai_wait_message'] ?? '',
            !empty($data['ai_wait_media_id']) ? $data['ai_wait_media_id'] : null,
            $data['action_type'] ?? 'none',
            $data['action_webhook_url'] ?? '',
            $data['action_webhook_body'] ?? '',
            $data['action_http_url'] ?? '',
            isset($data['platforms']) ? (is_array($data['platforms']) ? json_encode($data['platforms']) : $data['platforms']) : '["bale"]',
            $id
        ];

        if ($bot_id) {
            $sql .= " AND bot_id = ?";
            $params[] = $bot_id;
        }

        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute($params);
        $event = $this->getEvent($id, $bot_id);
        if ($event) {
            $this->syncCache($event['bot_id']);
        }
        return $res;
    }

    public function deleteEvent($id, $bot_id = null) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        $event = $this->getEvent($id, $bot_id);
        if (!$event) return false;

        $sql = "DELETE FROM events WHERE id = ?";
        $params = [$id];
        if ($bot_id) {
            $sql .= " AND bot_id = ?";
            $params[] = $bot_id;
        }

        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute($params);
        if ($res) {
            $this->syncCache($event['bot_id']);
        }
        return $res;
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
        $stmt = $this->db->prepare("INSERT INTO event_fields (event_id, label, field_key, type, is_required, sort_order, validation_rule, help_text, error_message, media_path, media_id, options_json, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $res = $stmt->execute([
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
            $data['media_id'] ?? null,
            $data['options_json'],
            1
        ]);
        $event = $this->getEvent($event_id);
        if ($event) {
            $this->syncCache($event['bot_id']);
        }
        return $res;
    }

    public function updateField($id, $data) {
        $stmt = $this->db->prepare("UPDATE event_fields SET label=?, field_key=?, type=?, is_required=?, sort_order=?, validation_rule=?, help_text=?, error_message=?, media_path=?, media_id=?, options_json=?, is_active=? WHERE id=?");
        $res = $stmt->execute([
            $data['label'],
            $data['field_key'],
            $data['type'],
            $data['is_required'] ? 1 : 0,
            $data['sort_order'],
            $data['validation_rule'],
            $data['help_text'],
            $data['error_message'],
            $data['media_path'],
            $data['media_id'] ?? null,
            $data['options_json'],
            $data['is_active'] ? 1 : 0,
            $id
        ]);
        $field = $this->getEventField($id);
        if ($field) {
            $event = $this->getEvent($field['event_id']);
            if ($event) {
                $this->syncCache($event['bot_id']);
            }
        }
        return $res;
    }

    public function toggleFieldActive($id) {
        $stmt = $this->db->prepare("UPDATE event_fields SET is_active = NOT is_active WHERE id = ?");
        $res = $stmt->execute([$id]);
        $field = $this->getEventField($id);
        if ($field) {
            $event = $this->getEvent($field['event_id']);
            if ($event) {
                $this->syncCache($event['bot_id']);
            }
        }
        return $res;
    }

    public function deleteField($id) {
        $field = $this->getEventField($id);
        $stmt = $this->db->prepare("DELETE FROM event_fields WHERE id = ?");
        $res = $stmt->execute([$id]);
        if ($field) {
            $event = $this->getEvent($field['event_id']);
            if ($event) {
                $this->syncCache($event['bot_id']);
            }
        }
        return $res;
    }

    public function updateFieldOrders($orders) {
        $stmt = $this->db->prepare("UPDATE event_fields SET sort_order = ? WHERE id = ?");
        $event_id = null;
        foreach ($orders as $id => $order) {
            if (!$event_id) {
                $field = $this->getEventField($id);
                if ($field) $event_id = $field['event_id'];
            }
            $stmt->execute([$order, $id]);
        }
        if ($event_id) {
            $event = $this->getEvent($event_id);
            if ($event) {
                $this->syncCache($event['bot_id']);
            }
        }
    }

    public function duplicateEvent($id, $new_bot_id = null) {
        $event = $this->getEvent($id);
        if (!$event) return false;

        $fields = $this->getEventFields($id);

        $eventData = $event;
        $original_bot_id = $eventData['bot_id'];
        unset($eventData['id']);
        unset($eventData['created_at']);
        
        $eventData['title'] .= ' (کپی)';
        $eventData['slug'] .= '-' . rand(100, 999);
        
        if ($new_bot_id) {
            $eventData['bot_id'] = $new_bot_id;
        }

        $new_id = $this->createEvent($eventData);
        if ($new_id) {
            foreach ($fields as $field) {
                $fieldData = $field;
                unset($fieldData['id']);
                $this->addField($new_id, $fieldData);
            }
            return $new_id;
        }
        return false;
    }
}
