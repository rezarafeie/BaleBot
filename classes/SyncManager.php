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
                } elseif ($collection === 'bots') {
                    self::syncBot($db, $data, $action);
                } elseif ($collection === 'events') {
                    self::syncEvent($db, $data, $action);
                } elseif ($collection === 'admins') {
                    self::syncAdmin($db, $data, $action);
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
        $type = defined('DB_TYPE') ? DB_TYPE : 'mysql';
        if ($type === 'd1') {
            $sql = "INSERT INTO bot_users (chat_id, bale_user_id, name, username, phone, bot_id, platform, first_interaction_at, last_interaction_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                    ON CONFLICT(chat_id, bot_id) DO UPDATE SET name=COALESCE(excluded.name, name), username=COALESCE(excluded.username, username), phone=COALESCE(excluded.phone, phone), last_interaction_at=excluded.last_interaction_at";
        } else {
            $sql = "INSERT INTO bot_users (chat_id, bale_user_id, name, username, phone, bot_id, platform, first_interaction_at, last_interaction_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE name=COALESCE(?, name), username=COALESCE(?, username), phone=COALESCE(?, phone), last_interaction_at=?";
        }
        
        $stmt = $db->prepare($sql);
        if ($type === 'd1') {
             $stmt->execute([
                $data['chat_id'], $data['bale_user_id'], $data['name'], $data['username'], $data['phone'] ?? null, $data['bot_id'], $data['platform'], $data['first_interaction_at'], $data['last_interaction_at']
            ]);
        } else {
            $stmt->execute([
                $data['chat_id'], $data['bale_user_id'], $data['name'], $data['username'], $data['phone'] ?? null, $data['bot_id'], $data['platform'], $data['first_interaction_at'], $data['last_interaction_at'],
                $data['name'], $data['username'], $data['phone'] ?? null, $data['last_interaction_at']
            ]);
        }
    }

    private static function syncUserState($db, $data) {
        $type = defined('DB_TYPE') ? DB_TYPE : 'mysql';
        if ($type === 'd1') {
            $sql = "INSERT INTO user_states (chat_id, bot_id, current_event_id, current_step_index, answers_json, status, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?) 
                    ON CONFLICT(chat_id, bot_id) DO UPDATE SET current_event_id=excluded.current_event_id, current_step_index=excluded.current_step_index, answers_json=excluded.answers_json, status=excluded.status, updated_at=excluded.updated_at";
        } else {
            $sql = "INSERT INTO user_states (chat_id, bot_id, current_event_id, current_step_index, answers_json, status, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE current_event_id=?, current_step_index=?, answers_json=?, status=?, updated_at=?";
        }

        $stmt = $db->prepare($sql);
        if ($type === 'd1') {
             $stmt->execute([
                $data['chat_id'], $data['bot_id'], $data['current_event_id'], $data['current_step_index'], $data['answers_json'], $data['status'], $data['updated_at']
            ]);
        } else {
            $stmt->execute([
                $data['chat_id'], $data['bot_id'], $data['current_event_id'], $data['current_step_index'], $data['answers_json'], $data['status'], $data['updated_at'],
                $data['current_event_id'], $data['current_step_index'], $data['answers_json'], $data['status'], $data['updated_at']
            ]);
        }
    }

    private static function syncBot($db, $data, $action) {
        if ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM bots WHERE id = ?");
            $stmt->execute([$data['id']]);
            return;
        }
        $type = defined('DB_TYPE') ? DB_TYPE : 'mysql';
        if ($type === 'd1') {
            $sql = "INSERT INTO bots (id, name, username, token, telegram_token, rubika_token, owner_id, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                    ON CONFLICT(id) DO UPDATE SET name=excluded.name, username=excluded.username, token=excluded.token, telegram_token=excluded.telegram_token, rubika_token=excluded.rubika_token, is_active=excluded.is_active";
            $db->prepare($sql)->execute([
                $data['id'], $data['name'], $data['username'], $data['token'], $data['telegram_token'], $data['rubika_token'], $data['owner_id'], $data['is_active'], $data['created_at']
            ]);
        } else {
            $sql = "INSERT INTO bots (id, name, username, token, telegram_token, rubika_token, owner_id, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=?, username=?, token=?, telegram_token=?, rubika_token=?, is_active=?";
            $db->prepare($sql)->execute([
                $data['id'], $data['name'], $data['username'], $data['token'], $data['telegram_token'], $data['rubika_token'], $data['owner_id'], $data['is_active'], $data['created_at'],
                $data['name'], $data['username'], $data['token'], $data['telegram_token'], $data['rubika_token'], $data['is_active']
            ]);
        }
    }

    private static function syncAdmin($db, $data, $action) {
        if ($action === 'delete') return;
        $type = defined('DB_TYPE') ? DB_TYPE : 'mysql';
        if ($type === 'd1') {
            $sql = "INSERT INTO admins (id, username, password_hash, email, created_at) VALUES (?, ?, ?, ?, ?) 
                    ON CONFLICT(id) DO UPDATE SET username=excluded.username, password_hash=excluded.password_hash, email=excluded.email";
            $db->prepare($sql)->execute([$data['id'], $data['username'], $data['password_hash'], $data['email'], $data['created_at']]);
        } else {
            $sql = "INSERT INTO admins (id, username, password_hash, email, created_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE username=?, password_hash=?, email=?";
            $db->prepare($sql)->execute([
                $data['id'], $data['username'], $data['password_hash'], $data['email'], $data['created_at'],
                $data['username'], $data['password_hash'], $data['email']
            ]);
        }
    }

    private static function syncEvent($db, $data, $action) {
        if ($action === 'delete') {
            $db->prepare("DELETE FROM events WHERE id = ?")->execute([$data['id']]);
            return;
        }
        $type = defined('DB_TYPE') ? DB_TYPE : 'mysql';
        if ($type === 'd1') {
             $sql = "INSERT INTO events (id, bot_id, title, slug, description, welcome_message, welcome_media_id, completion_message, completion_media_id, duplicate_message, is_active, duplicate_setting, use_ai, ai_prompt, ai_wait_message, ai_wait_media_id, action_type, action_webhook_url, action_webhook_body, action_http_url, platforms, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                    ON CONFLICT(id) DO UPDATE SET title=excluded.title, slug=excluded.slug, description=excluded.description, welcome_message=excluded.welcome_message, welcome_media_id=excluded.welcome_media_id, completion_message=excluded.completion_message, completion_media_id=excluded.completion_media_id, duplicate_message=excluded.duplicate_message, is_active=excluded.is_active, duplicate_setting=excluded.duplicate_setting, use_ai=excluded.use_ai, ai_prompt=excluded.ai_prompt, ai_wait_message=excluded.ai_wait_message, ai_wait_media_id=excluded.ai_wait_media_id, action_type=excluded.action_type, action_webhook_url=excluded.action_webhook_url, action_webhook_body=excluded.action_webhook_body, action_http_url=excluded.action_http_url, platforms=excluded.platforms";
             $db->prepare($sql)->execute([
                $data['id'], $data['bot_id'], $data['title'], $data['slug'], $data['description'], $data['welcome_message'], $data['welcome_media_id'], $data['completion_message'], $data['completion_media_id'], $data['duplicate_message'], $data['is_active'], $data['duplicate_setting'], $data['use_ai'], $data['ai_prompt'], $data['ai_wait_message'], $data['ai_wait_media_id'], $data['action_type'], $data['action_webhook_url'], $data['action_webhook_body'], $data['action_http_url'], $data['platforms'], $data['created_at']
            ]);
        } else {
            $sql = "INSERT INTO events (id, bot_id, title, slug, description, welcome_message, welcome_media_id, completion_message, completion_media_id, duplicate_message, is_active, duplicate_setting, use_ai, ai_prompt, ai_wait_message, ai_wait_media_id, action_type, action_webhook_url, action_webhook_body, action_http_url, platforms, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title=?, slug=?, description=?, welcome_message=?, welcome_media_id=?, completion_message=?, completion_media_id=?, duplicate_message=?, is_active=?, duplicate_setting=?, use_ai=?, ai_prompt=?, ai_wait_message=?, ai_wait_media_id=?, action_type=?, action_webhook_url=?, action_webhook_body=?, action_http_url=?, platforms=?";
            $db->prepare($sql)->execute([
                $data['id'], $data['bot_id'], $data['title'], $data['slug'], $data['description'], $data['welcome_message'], $data['welcome_media_id'], $data['completion_message'], $data['completion_media_id'], $data['duplicate_message'], $data['is_active'], $data['duplicate_setting'], $data['use_ai'], $data['ai_prompt'], $data['ai_wait_message'], $data['ai_wait_media_id'], $data['action_type'], $data['action_webhook_url'], $data['action_webhook_body'], $data['action_http_url'], $data['platforms'], $data['created_at'],
                $data['title'], $data['slug'], $data['description'], $data['welcome_message'], $data['welcome_media_id'], $data['completion_message'], $data['completion_media_id'], $data['duplicate_message'], $data['is_active'], $data['duplicate_setting'], $data['use_ai'], $data['ai_prompt'], $data['ai_wait_message'], $data['ai_wait_media_id'], $data['action_type'], $data['action_webhook_url'], $data['action_webhook_body'], $data['action_http_url'], $data['platforms']
            ]);
        }

        // Sync Fields if present
        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $f) {
                if ($type === 'd1') {
                    $sql_f = "INSERT INTO event_fields (id, event_id, label, field_key, type, is_required, sort_order, validation_rule, help_text, error_message, media_path, media_id, options_json, is_active) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                              ON CONFLICT(id) DO UPDATE SET label=excluded.label, field_key=excluded.field_key, type=excluded.type, is_required=excluded.is_required, sort_order=excluded.sort_order, validation_rule=excluded.validation_rule, help_text=excluded.help_text, error_message=excluded.error_message, media_path=excluded.media_path, media_id=excluded.media_id, options_json=excluded.options_json, is_active=excluded.is_active";
                    $db->prepare($sql_f)->execute([
                        $f['id'], $data['id'], $f['label'], $f['field_key'], $f['type'], $f['is_required'], $f['sort_order'], $f['validation_rule'], $f['help_text'], $f['error_message'], $f['media_path'], $f['media_id'], $f['options_json'], $f['is_active']
                    ]);
                } else {
                    $sql_f = "INSERT INTO event_fields (id, event_id, label, field_key, type, is_required, sort_order, validation_rule, help_text, error_message, media_path, media_id, options_json, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE label=?, field_key=?, type=?, is_required=?, sort_order=?, validation_rule=?, help_text=?, error_message=?, media_path=?, media_id=?, options_json=?, is_active=?";
                    $db->prepare($sql_f)->execute([
                        $f['id'], $data['id'], $f['label'], $f['field_key'], $f['type'], $f['is_required'], $f['sort_order'], $f['validation_rule'], $f['help_text'], $f['error_message'], $f['media_path'], $f['media_id'], $f['options_json'], $f['is_active'],
                        $f['label'], $f['field_key'], $f['type'], $f['is_required'], $f['sort_order'], $f['validation_rule'], $f['help_text'], $f['error_message'], $f['media_path'], $f['media_id'], $f['options_json'], $f['is_active']
                    ]);
                }
            }
        }
    }
}
