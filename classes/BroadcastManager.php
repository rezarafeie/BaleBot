<?php
// classes/BroadcastManager.php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/BaleBot.php';

class BroadcastManager {
    private $db;
    private $bot;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->bot = new BaleBot();
    }

    public function createBroadcast($target_type, $event_id, $message_text, $media_id = null) {
        $stmt = $this->db->prepare("INSERT INTO broadcasts (target_type, target_event_id, message_text, media_id, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$target_type, $event_id, $message_text, $media_id]);
        return $this->db->lastInsertId();
    }

    public function getRecipients($target_type, $event_id = null) {
        if ($target_type === 'all') {
            return $this->db->query("SELECT chat_id FROM bot_users")->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($target_type === 'event' && $event_id) {
            $stmt = $this->db->prepare("SELECT DISTINCT chat_id FROM registrations WHERE event_id = ? AND status = 'completed'");
            $stmt->execute([$event_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return [];
    }

    public function processBroadcast($broadcast_id) {
        $stmt = $this->db->prepare("SELECT * FROM broadcasts WHERE id = ? AND status = 'pending'");
        $stmt->execute([$broadcast_id]);
        $b = $stmt->fetch();
        if (!$b) return false;

        $this->db->prepare("UPDATE broadcasts SET status = 'sending' WHERE id = ?")->execute([$broadcast_id]);
        
        $recipients = $this->getRecipients($b['target_type'], $b['target_event_id']);
        $total = count($recipients);
        $this->db->prepare("UPDATE broadcasts SET total_recipients = ? WHERE id = ?")->execute([$total, $broadcast_id]);

        $sent = 0;
        $failed = 0;

        // Fetch media if any
        $mediaFile = null;
        if ($b['media_id']) {
            $stmtMedia = $this->db->prepare("SELECT * FROM media_files WHERE id = ?");
            $stmtMedia->execute([$b['media_id']]);
            $mediaFile = $stmtMedia->fetch();
        }

        foreach ($recipients as $chat_id) {
            $res = null;
            if ($mediaFile) {
                // Determine file id or path
                $file = $mediaFile['bale_file_id'] ?: new CURLFile(dirname(__DIR__) . '/' . ltrim($mediaFile['file_path'], '/'));
                
                if ($mediaFile['file_type'] === 'photo') {
                    $res = $this->bot->sendPhoto($chat_id, $file, $b['message_text']);
                } elseif ($mediaFile['file_type'] === 'video') {
                    $res = $this->bot->sendVideo($chat_id, $file, $b['message_text']);
                } elseif ($mediaFile['file_type'] === 'document') {
                    $res = $this->bot->sendDocument($chat_id, $file, $b['message_text']);
                } elseif ($mediaFile['file_type'] === 'voice') {
                    $res = $this->bot->sendVoice($chat_id, $file, $b['message_text']);
                }
            } else {
                $res = $this->bot->sendMessage($chat_id, $b['message_text']);
            }

            $stat = 'sent';
            if (!$res || !isset($res['ok']) || !$res['ok']) {
                $stat = 'failed';
                $failed++;
            } else {
                $sent++;
            }

            $this->db->prepare("INSERT INTO broadcast_recipients (broadcast_id, chat_id, status) VALUES (?, ?, ?)")->execute([$broadcast_id, $chat_id, $stat]);
            
            // Basic rate limit
            usleep(100000); // 100ms
        }

        $this->db->prepare("UPDATE broadcasts SET status = 'completed', sent_count = ?, failed_count = ? WHERE id = ?")->execute([$sent, $failed, $broadcast_id]);
        return true;
    }
}
