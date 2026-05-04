<?php
// classes/BotManager.php
require_once __DIR__ . '/Database.php';

class BotManager {
    private $db;

    public function __construct() {
        $dbInstance = Database::getInstance();
        if ($dbInstance->isConnected()) {
            $this->db = $dbInstance->getConnection();
            $this->init();
        }
    }

    private function init() {
        // Create bots table
        $this->db->exec("CREATE TABLE IF NOT EXISTS `bots` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `username` varchar(255) DEFAULT NULL,
            `token` varchar(255) NOT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Add platform-specific tokens to bots table
        try { $this->db->exec("ALTER TABLE `bots` ADD `telegram_token` varchar(255) DEFAULT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `bots` ADD `rubika_token` varchar(255) DEFAULT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `bots` ADD `owner_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        
        // Add platform column to various tables
        try { $this->db->exec("ALTER TABLE `events` ADD `platforms` text DEFAULT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `bot_users` ADD `platform` varchar(50) DEFAULT 'bale'"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `user_states` ADD `platform` varchar(50) DEFAULT 'bale'"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `registrations` ADD `platform` varchar(50) DEFAULT 'bale'"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `broadcasts` ADD `platforms` text DEFAULT NULL"); } catch(PDOException $e) {}

        // Add bot_id to existing tables if not present
        try { $this->db->exec("ALTER TABLE `events` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `registration_answers` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `registrations` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `bot_users` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `user_states` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `broadcasts` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `media_files` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `system_logs` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `bot_users` ADD `verified_channels` TEXT NULL"); } catch(PDOException $e) {}

        // Fix constraints for multi-bot
        try {
            // bot_users unique constraint
            try { $this->db->exec("ALTER TABLE `bot_users` DROP INDEX `chat_id` "); } catch(PDOException $ex) {}
            try { $this->db->exec("ALTER TABLE `bot_users` ADD UNIQUE KEY `chat_bot` (`chat_id`, `bot_id`) "); } catch(PDOException $ex) {}
        } catch(PDOException $e) {}

        try {
            // user_states unique constraint
            try { $this->db->exec("ALTER TABLE `user_states` DROP INDEX `chat_id` "); } catch(PDOException $ex) {}
            try { $this->db->exec("ALTER TABLE `user_states` ADD UNIQUE KEY `chat_bot` (`chat_id`, `bot_id`) "); } catch(PDOException $ex) {}
        } catch(PDOException $e) {}
        
        // Media support for all messages
        try { $this->db->exec("ALTER TABLE `events` ADD `welcome_media_id` INT DEFAULT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `events` ADD `completion_media_id` INT DEFAULT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `events` ADD `ai_wait_media_id` INT DEFAULT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `event_fields` ADD `media_id` INT DEFAULT NULL"); } catch(PDOException $e) {}
        
        // Settings needs a bot_id to differentiate settings per bot
        try { 
            // 1. Add bot_id if missing
            $exists = $this->db->query("SHOW COLUMNS FROM `settings` LIKE 'bot_id'")->fetch();
            if (!$exists) {
                $this->db->exec("ALTER TABLE `settings` ADD `bot_id` INT DEFAULT 1");
            }
            
            // 2. Check if setting_key is still the only PK
            $pk = $this->db->query("SHOW KEYS FROM `settings` WHERE Key_name = 'PRIMARY'")->fetchAll();
            $isComposite = false;
            foreach ($pk as $key) {
                if ($key['Column_name'] === 'bot_id') {
                    $isComposite = true;
                    break;
                }
            }
            
            if (!$isComposite) {
                try { $this->db->exec("ALTER TABLE `settings` DROP PRIMARY KEY"); } catch(PDOException $ex) {}
                try { $this->db->exec("ALTER TABLE `settings` ADD PRIMARY KEY (`bot_id`, `setting_key`) "); } catch(PDOException $ex) {}
            }
        } catch(PDOException $e) {}
        
        try { $this->db->exec("CREATE INDEX idx_logs_bot ON system_logs(bot_id)"); } catch(PDOException $e) {}
        try { $this->db->exec("CREATE INDEX idx_media_bot ON media_files(bot_id)"); } catch(PDOException $e) {}

        $this->syncPhysicalBots();
    }

    private function syncPhysicalBots() {
        $botsData = $this->db->query("SELECT username FROM bots")->fetchAll();
        foreach ($botsData as $bot) {
            if ($bot['username']) {
                $this->ensureWebhookFile($bot['username']);
            }
        }
    }

    public function getBots($owner_id = null) {
        if (!$this->db) {
            return $this->getCachedBots();
        }
        if ($owner_id === null && isset($_SESSION['admin_id'])) {
            $owner_id = $_SESSION['admin_id'];
        }
        if ($owner_id) {
            $stmt = $this->db->prepare("SELECT * FROM bots WHERE owner_id = ? ORDER BY id ASC");
            $stmt->execute([$owner_id]);
            return $stmt->fetchAll();
        }
        $stmt = $this->db->query("SELECT * FROM bots ORDER BY id ASC");
        $res = $stmt->fetchAll();
        $this->syncCache();
        return $res;
    }

    private function syncCache() {
        if (!$this->db) return;
        $bots = $this->db->query("SELECT * FROM bots")->fetchAll();
        $cache = [];
        foreach ($bots as $bot) {
            $cache[$bot['id']] = $bot;
            $cache['user_' . ltrim($bot['username'], '@')] = $bot;
        }
        $cachePath = dirname(__DIR__) . '/data/bots_cache.json';
        if (!is_dir(dirname($cachePath))) @mkdir(dirname($cachePath), 0777, true);
        file_put_contents($cachePath, json_encode($cache, JSON_UNESCAPED_UNICODE));
    }

    private function getCachedBots() {
        $cachePath = dirname(__DIR__) . '/data/bots_cache.json';
        if (file_exists($cachePath)) {
            $data = json_decode(file_get_contents($cachePath), true) ?: [];
            // Convert indexed by ID to simple list for getBots
            $list = [];
            foreach ($data as $k => $v) {
                if (is_numeric($k)) $list[] = $v;
            }
            return $list;
        }
        return [];
    }

    public function getBot($id, $owner_id = null) {
        if (!$this->db) {
            $cache = $this->getCachedBots();
            foreach ($cache as $b) {
                if ($b['id'] == $id) return $b;
            }
            return null;
        }
        if ($owner_id === null && isset($_SESSION['admin_id'])) {
            $owner_id = $_SESSION['admin_id'];
        }
        $query = "SELECT * FROM bots WHERE id = ?";
        $params = [$id];
        if ($owner_id) {
            $query .= " AND owner_id = ?";
            $params[] = $owner_id;
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function getBotByUsername($username) {
        $username = ltrim($username, '@');
        if (!$this->db) {
            $cachePath = dirname(__DIR__) . '/data/bots_cache.json';
            if (file_exists($cachePath)) {
                $cache = json_decode(file_get_contents($cachePath), true);
                if (isset($cache['user_' . $username])) return $cache['user_' . $username];
            }
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM bots WHERE username = ?");
        $stmt->execute([$username]);
        $bot = $stmt->fetch();
        if ($bot) {
            $this->ensureWebhookFile($bot['username']);
        }
        return $bot;
    }

    public function ensureWebhookFile($bot_username) {
        $bot_username = ltrim($bot_username, '@');
        
        $baseDir = dirname(__DIR__);
        $botsDir = $baseDir . '/bots';
        if (!is_dir($botsDir)) {
            @mkdir($botsDir, 0777, true);
        }
        
        $botDir = $botsDir . '/' . $bot_username;
        if (!is_dir($botDir)) {
            @mkdir($botDir, 0777, true);
        }
        
        $platforms = ['bale', 'telegram', 'rubika'];
        foreach ($platforms as $platform) {
            $webhookFile = $botDir . "/webhook_{$platform}.php";
            $pretty_username = '@' . $bot_username;
            $content = "<?php
/**
 * {$platform} Bot Webhook Handler
 * Generated for: {$pretty_username}
 */

// Pass context to the core webhook logic
\$_GET['bot_user'] = '{$bot_username}';
\$_GET['platform'] = '{$platform}';

// Include the core webhook processing logic
require_once realpath(__DIR__ . '/../../webhook.php');
";
            file_put_contents($webhookFile, $content);
        }
        
        // Legacy support for bale if needed
        $legacy = $botDir . '/webhook.php';
        if (!file_exists($legacy)) {
            copy($botDir . "/webhook_bale.php", $legacy);
        }
    }

    public function createBot($name, $username, $token, $telegram_token = null, $rubika_token = null, $owner_id = null) {
        if ($owner_id === null && isset($_SESSION['admin_id'])) {
            $owner_id = $_SESSION['admin_id'];
        }

        if (!$this->db) {
            require_once __DIR__ . '/LocalStore.php';
            $id = time();
            $data = [
                'id' => $id,
                'name' => $name,
                'username' => $username,
                'token' => $token,
                'telegram_token' => $telegram_token,
                'rubika_token' => $rubika_token,
                'owner_id' => $owner_id,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            LocalStore::getInstance()->save('bots', $id, $data);
            $this->ensureWebhookFile($username);
            return $id;
        }

        $stmt = $this->db->prepare("INSERT INTO bots (name, username, token, telegram_token, rubika_token, owner_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $username, $token, $telegram_token, $rubika_token, $owner_id]);
        $id = $this->db->lastInsertId();
        if ($id) {
            $this->ensureWebhookFile($username);
            // Default settings for the new bot
            $this->db->prepare("INSERT IGNORE INTO settings (bot_id, setting_key, setting_value) VALUES (?, 'webhook_url', '')")->execute([$id]);
            $this->db->prepare("INSERT IGNORE INTO settings (bot_id, setting_key, setting_value) VALUES (?, 'gapgpt_api_key', '')")->execute([$id]);
            $this->db->prepare("INSERT IGNORE INTO settings (bot_id, setting_key, setting_value) VALUES (?, 'gapgpt_model', 'gemini-2.5-flash-lite')")->execute([$id]);
            $this->db->prepare("INSERT IGNORE INTO settings (bot_id, setting_key, setting_value) VALUES (?, 'event_selection_text', '')")->execute([$id]);
        }
        return $id;
    }

    public function updateBot($id, $name, $username, $token, $telegram_token, $rubika_token, $is_active, $owner_id = null) {
        if ($owner_id === null && isset($_SESSION['admin_id'])) {
            $owner_id = $_SESSION['admin_id'];
        }

        if (!$this->db) {
            require_once __DIR__ . '/LocalStore.php';
            $bot = LocalStore::getInstance()->get('bots', $id);
            if ($bot) {
                $bot['name'] = $name;
                $bot['username'] = $username;
                $bot['token'] = $token;
                $bot['telegram_token'] = $telegram_token;
                $bot['rubika_token'] = $rubika_token;
                $bot['is_active'] = $is_active ? 1 : 0;
                LocalStore::getInstance()->save('bots', $id, $bot);
                $this->ensureWebhookFile($username);
            }
            return true;
        }

        $query = "UPDATE bots SET name = ?, username = ?, token = ?, telegram_token = ?, rubika_token = ?, is_active = ? WHERE id = ?";
        $params = [$name, $username, $token, $telegram_token, $rubika_token, $is_active ? 1 : 0, $id];
        if ($owner_id) {
            $query .= " AND owner_id = ?";
            $params[] = $owner_id;
        }
        $stmt = $this->db->prepare($query);
        $res = $stmt->execute($params);
        if ($res) {
            $this->ensureWebhookFile($username);
        }
        return $res;
    }

    public function deleteBot($id, $owner_id = null) {
        if ($owner_id === null && isset($_SESSION['admin_id'])) {
            $owner_id = $_SESSION['admin_id'];
        }

        if (!$this->db) {
            require_once __DIR__ . '/LocalStore.php';
            LocalStore::getInstance()->delete('bots', $id);
            return true;
        }

        $query = "DELETE FROM bots WHERE id = ?";
        $params = [$id];
        if ($owner_id) {
            $query .= " AND owner_id = ?";
            $params[] = $owner_id;
        }
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }
}
