SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bot_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` varchar(50) NOT NULL,
  `bale_user_id` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `first_interaction_at` datetime NOT NULL,
  `last_interaction_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text,
  `welcome_message` text,
  `completion_message` text,
  `duplicate_message` text,
  `is_active` tinyint(1) DEFAULT 1,
  `duplicate_setting` enum('allow','block_chat_id','block_phone') DEFAULT 'allow',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `event_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `field_key` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `validation_rule` varchar(255) DEFAULT NULL,
  `help_text` text,
  `error_message` text,
  `media_path` varchar(255) DEFAULT NULL,
  `options_json` text,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` varchar(50) NOT NULL,
  `current_event_id` int(11) DEFAULT NULL,
  `current_step_index` int(11) DEFAULT 0,
  `answers_json` text,
  `status` enum('idle','registering','completed') DEFAULT 'idle',
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `chat_id` varchar(50) NOT NULL,
  `answers_json` text,
  `status` varchar(50) DEFAULT 'completed',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `registration_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `field_key` varchar(255) NOT NULL,
  `field_value` text,
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `media_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `bale_file_id` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `broadcasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_type` varchar(50) NOT NULL,
  `target_event_id` int(11) DEFAULT NULL,
  `message_text` text NOT NULL,
  `media_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `total_recipients` int(11) DEFAULT 0,
  `sent_count` int(11) DEFAULT 0,
  `failed_count` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `broadcast_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `broadcast_id` int(11) NOT NULL,
  `chat_id` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT 'sent',
  PRIMARY KEY (`id`),
  KEY `broadcast_id` (`broadcast_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('webhook_url', '');

-- Default admin: admin / admin123
INSERT IGNORE INTO `admins` (`username`, `password_hash`) VALUES ('admin', '$2y$10$wE/.76TzM8x1PWeQO3S3A.HwGzRkZ39.S.H.1.E//.tLXYI9M304C');

SET FOREIGN_KEY_CHECKS = 1;
