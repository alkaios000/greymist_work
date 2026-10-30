-- File: database/migrations/02_add_color_and_component_columns.sql
-- Fungsi: Penambahan tabel gm_components untuk komponen kustom pengguna

CREATE TABLE IF NOT EXISTS `gm_components` (
  `component_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `component_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `html_content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Custom',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`component_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `gm_components_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `gm_users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

