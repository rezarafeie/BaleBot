<?php
// classes/BotManager.php
require_once __DIR__ . '/Database.php';

class BotManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        $this->init();
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

        // Add bot_id to existing tables if not present
        try { $this->db->exec("ALTER TABLE `events` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `registration_answers` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `registrations` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `bot_users` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `user_states` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `broadcasts` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `media_files` ADD `bot_id` INT DEFAULT 1"); } catch(PDOException $e) {}
        
        // Media support for all messages
        try { $this->db->exec("ALTER TABLE `events` ADD `welcome_media_id` INT DEFAULT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `events` ADD `completion_media_id` INT DEFAULT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `events` ADD `ai_wait_media_id` INT DEFAULT NULL"); } catch(PDOException $e) {}
        try { $this->db->exec("ALTER TABLE `event_fields` ADD `media_id` INT DEFAULT NULL"); } catch(PDOException $e) {}
        
        // Settings needs a bot_id to differentiate settings per bot
        try { 
            $this->db->exec("ALTER TABLE `settings` ADD `bot_id` INT DEFAULT 1"); 
            // Also need to adjust unique index if exists
            // Usually settings has unique(setting_key), we might need unique(bot_id, setting_key)
            try { $this->db->exec("ALTER TABLE `settings` DROP INDEX `setting_key` "); } catch(PDOException $ex) {}
            try { $this->db->exec("ALTER TABLE `settings` ADD UNIQUE KEY `bot_setting` (`bot_id`, `setting_key`) "); } catch(PDOException $ex) {}
        } catch(PDOException $e) {}
    }

    public function getBots() {
        $stmt = $this->db->query("SELECT * FROM bots ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function getBot($id) {
        $stmt = $this->db->prepare("SELECT * FROM bots WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createBot($name, $username, $token) {
        $stmt = $this->db->prepare("INSERT INTO bots (name, username, token) VALUES (?, ?, ?)");
        $stmt->execute([$name, $username, $token]);
        return $this->db->lastInsertId();
    }

    public function updateBot($id, $name, $username, $token, $is_active) {
        $stmt = $this->db->prepare("UPDATE bots SET name = ?, username = ?, token = ?, is_active = ? WHERE id = ?");
        return $stmt->execute([$name, $username, $token, $is_active ? 1 : 0, $id]);
    }

    public function deleteBot($id) {
        $stmt = $this->db->prepare("DELETE FROM bots WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
