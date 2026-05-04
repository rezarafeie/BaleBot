<?php
// classes/SyncManager.php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/LocalStore.php';

class SyncManager {
    public static function processQueue() {
        $dbInstance = Database::getInstance();
        if (!$dbInstance->isConnected()) return false;

        $db = $dbInstance->getConnection();
        $store = LocalStore::getInstance();
        $queue = $store->getQueue();

        if (empty($queue)) return true;

        $remaining = [];
        $successCount = 0;

        foreach ($queue as $item) {
            try {
                $action = $item['action'];
                $collection = $item['collection'];
                $data = $item['data'];

                if ($collection === 'registrations') {
                    self::syncRegistration($db, $data);
                } elseif ($collection === 'bot_users') {
                    self::syncBotUser($db, $data);
                } elseif ($collection === 'user_states') {
                    self::syncUserState($db, $data);
                }
                $successCount++;
            } catch (Exception $e) {
                error_log("Sync failed for item: " . json_encode($item) . " Error: " . $e->getMessage());
                $remaining[] = $item;
            }
        }

        if (empty($remaining)) {
            $store->clearQueue();
        } else {
            // Update queue with remaining items
            file_put_contents(dirname(__DIR__) . '/data/local_store/sync_queue.json', json_encode($remaining, JSON_UNESCAPED_UNICODE));
        }

        return $successCount;
    }

    private static function syncRegistration($db, $data) {
        $stmt = $db->prepare("INSERT INTO registrations (event_id, chat_id, bot_id, answers_json, status, platform, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['event_id'], 
            $data['chat_id'], 
            $data['bot_id'], 
            $data['answers_json'], 
            $data['status'], 
            $data['platform'], 
            $data['created_at']
        ]);
        $regId = $db->lastInsertId();

        if (!empty($data['answers'])) {
            $stmt_ans = $db->prepare("INSERT INTO registration_answers (registration_id, field_key, field_value, bot_id) VALUES (?, ?, ?, ?)");
            foreach ($data['answers'] as $k => $v) {
                $val_str = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v;
                $stmt_ans->execute([$regId, $k, $val_str, $data['bot_id']]);
            }
        }
    }

    private static function syncBotUser($db, $data) {
        $stmt = $db->prepare("INSERT INTO bot_users (chat_id, bale_user_id, name, username, phone, bot_id, platform, first_interaction_at, last_interaction_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=COALESCE(?, name), username=COALESCE(?, username), phone=COALESCE(?, phone), last_interaction_at=?");
        $stmt->execute([
            $data['chat_id'], $data['bale_user_id'], $data['name'], $data['username'], $data['phone'] ?? null, $data['bot_id'], $data['platform'], $data['first_interaction_at'], $data['last_interaction_at'],
            $data['name'], $data['username'], $data['phone'] ?? null, $data['last_interaction_at']
        ]);
    }

    private static function syncUserState($db, $data) {
        $stmt = $db->prepare("INSERT INTO user_states (chat_id, bot_id, current_event_id, current_step_index, answers_json, status, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE current_event_id=?, current_step_index=?, answers_json=?, status=?, updated_at=?");
        $stmt->execute([
            $data['chat_id'], $data['bot_id'], $data['current_event_id'], $data['current_step_index'], $data['answers_json'], $data['status'], $data['updated_at'],
            $data['current_event_id'], $data['current_step_index'], $data['answers_json'], $data['status'], $data['updated_at']
        ]);
    }
}
