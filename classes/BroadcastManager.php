<?php
// classes/BroadcastManager.php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/BaleBot.php';
require_once __DIR__ . '/TelegramBot.php';
require_once __DIR__ . '/RubikaBot.php';

class BroadcastManager {
    private $db;
    private $bot;

    public function __construct() {
        $dbInstance = Database::getInstance();
        if ($dbInstance->isConnected()) {
            $this->db = $dbInstance->getConnection();
            $this->bot = new BaleBot();
            
            $this->db->exec("CREATE TABLE IF NOT EXISTS `broadcasts` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `bot_id` int(11) DEFAULT 1,
              `target_type` varchar(50) NOT NULL,
              `target_event_id` int(11) DEFAULT NULL,
              `message_text` text NOT NULL,
              `media_id` int(11) DEFAULT NULL,
              `platforms` text DEFAULT NULL,
              `status` varchar(50) DEFAULT 'pending',
              `total_recipients` int(11) DEFAULT 0,
              `sent_count` int(11) DEFAULT 0,
              `failed_count` int(11) DEFAULT 0,
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            
            $this->db->exec("CREATE TABLE IF NOT EXISTS `broadcast_recipients` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `broadcast_id` int(11) NOT NULL,
              `chat_id` varchar(50) NOT NULL,
              `status` varchar(50) DEFAULT 'sent',
              PRIMARY KEY (`id`),
              KEY `broadcast_id` (`broadcast_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
    }

    public function createBroadcast($target_type, $event_id, $message_text, $media_id = null, $bot_id = null, $platforms = '["bale"]') {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }
        $platforms_json = is_array($platforms) ? json_encode($platforms) : $platforms;
        $stmt = $this->db->prepare("INSERT INTO broadcasts (bot_id, target_type, target_event_id, message_text, media_id, platforms, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$bot_id, $target_type, $event_id, $message_text, $media_id, $platforms_json]);
        return $this->db->lastInsertId();
    }

    public function getRecipients($target_type, $event_id = null, $bot_id = null, $platforms = []) {
        if ($bot_id === null && isset($_SESSION['selected_bot_id'])) {
            $bot_id = $_SESSION['selected_bot_id'];
        }

        $platform_filter = "";
        $params = [$bot_id];
        if (!empty($platforms)) {
            $placeholders = implode(',', array_fill(0, count($platforms), '?'));
            $platform_filter = " AND platform IN ($placeholders)";
            foreach ($platforms as $p) $params[] = $p;
        }

        if ($target_type === 'all') {
            $stmt = $this->db->prepare("SELECT chat_id, platform FROM bot_users WHERE bot_id = ?" . $platform_filter);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($target_type === 'event' && $event_id) {
            array_unshift($params, $event_id);
            $stmt = $this->db->prepare("SELECT DISTINCT chat_id, platform FROM registrations WHERE event_id = ? AND bot_id = ? AND status = 'completed'" . $platform_filter);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function processBroadcast($broadcast_id) {
        $stmt = $this->db->prepare("SELECT * FROM broadcasts WHERE id = ? AND status = 'pending'");
        $stmt->execute([$broadcast_id]);
        $b = $stmt->fetch();
        if (!$b) return false;

        $bot_id = $b['bot_id'];
        require_once __DIR__ . '/BotManager.php';
        $botData = (new BotManager())->getBot($bot_id);
        
        $bots = [
            'bale' => new BaleBot($botData['token'] ?? null, $bot_id),
            'telegram' => new TelegramBot($botData['telegram_token'] ?? null, $bot_id),
            'rubika' => new RubikaBot($botData['rubika_token'] ?? null, $bot_id)
        ];

        $this->db->prepare("UPDATE broadcasts SET status = 'sending' WHERE id = ?")->execute([$broadcast_id]);
        
        $target_platforms = json_decode($b['platforms'] ?? '["bale"]', true) ?: ['bale'];
        $recipients = $this->getRecipients($b['target_type'], $b['target_event_id'], $bot_id, $target_platforms);
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

        foreach ($recipients as $recipient) {
            $chat_id = $recipient['chat_id'];
            $platform = $recipient['platform'];
            $bot = $bots[$platform] ?? $bots['bale'];

            $res = null;
            if ($mediaFile) {
                // Determine file id or path
                $file = $mediaFile['bale_file_id'] ?: new CURLFile(dirname(__DIR__) . '/' . ltrim($mediaFile['file_path'], '/'));
                
                if ($mediaFile['file_type'] === 'photo') {
                    $res = $bot->sendPhoto($chat_id, $file, $b['message_text']);
                } elseif ($mediaFile['file_type'] === 'video') {
                    // Telegram and Rubika classes need to implement sendVideo
                    if (method_exists($bot, 'sendVideo')) {
                        $res = $bot->sendVideo($chat_id, $file, $b['message_text']);
                    } else {
                        $res = $bot->sendMessage($chat_id, $b['message_text'] . " (Media: " . $mediaFile['file_type'] . ")");
                    }
                } elseif ($mediaFile['file_type'] === 'document') {
                    $res = $bot->sendDocument($chat_id, $file, $b['message_text']);
                } elseif ($mediaFile['file_type'] === 'voice') {
                    if (method_exists($bot, 'sendVoice')) {
                        $res = $bot->sendVoice($chat_id, $file, $b['message_text']);
                    } else {
                        $res = $bot->sendMessage($chat_id, $b['message_text'] . " (Media: voice)");
                    }
                }
            } else {
                $res = $bot->sendMessage($chat_id, $b['message_text']);
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
