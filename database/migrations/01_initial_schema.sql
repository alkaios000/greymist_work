-- File: database/migrations/01_initial_schema.sql
-- Fungsi: Pembuatan struktur tabel utama aplikasi Greymist

CREATE TABLE IF NOT EXISTS `gm_users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `nickname` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gm_projects` (
  `project_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `project_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Untitled Project',
  `thumbnail` mediumtext COLLATE utf8mb4_unicode_ci,
  `thumbnail_updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `project_status` enum('unpublished','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpublished',
  `is_template` tinyint(1) NOT NULL DEFAULT '0',
  `color_tokens` json DEFAULT NULL,
  `bg_color_settings` json DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`project_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `gm_projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `gm_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gm_frames` (
  `frame_id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `frame_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Untitled Frame',
  `core_content` longtext COLLATE utf8mb4_unicode_ci,
  `thumbnail` mediumtext COLLATE utf8mb4_unicode_ci,
  `thumbnail_updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `sort_order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`frame_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `gm_frames_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `gm_projects` (`project_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gm_colors` (
  `color_id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `color_variable` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`color_id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `gm_colors_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `gm_projects` (`project_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gm_bg_images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `gm_bg_images_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `gm_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

