-- PortGuard FULL schema (local)
-- Database: portguard
-- phpMyAdmin: portguard DB seçiliyken İçe Aktar / SQL sekmesine yapıştır
-- Charset: utf8mb4

CREATE DATABASE IF NOT EXISTS `portguard` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `portguard`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `cve_findings`;
DROP TABLE IF EXISTS `scan_services`;
DROP TABLE IF EXISTS `scan_hosts`;
DROP TABLE IF EXISTS `scans`;
DROP TABLE IF EXISTS `scheduled_scans`;
DROP TABLE IF EXISTS `user_notifications`;
DROP TABLE IF EXISTS `user_settings`;
DROP TABLE IF EXISTS `targets`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admin_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`(191),`queue`(191),`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `properties` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `activity_logs_action_created_at_index` (`action`,`created_at`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `targets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `cidr` varchar(50) DEFAULT NULL,
  `start_ip` varchar(45) DEFAULT NULL,
  `end_ip` varchar(45) DEFAULT NULL,
  `ports` varchar(255) NOT NULL DEFAULT '22,80,443,3306',
  `notes` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `targets_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `targets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `scans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `target_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(20) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `cidr` varchar(50) DEFAULT NULL,
  `start_ip` varchar(45) DEFAULT NULL,
  `end_ip` varchar(45) DEFAULT NULL,
  `ports` varchar(255) NOT NULL DEFAULT '22,80,443,3306',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `total_hosts` int unsigned NOT NULL DEFAULT 0,
  `active_hosts` int unsigned NOT NULL DEFAULT 0,
  `service_count` int unsigned NOT NULL DEFAULT 0,
  `cve_count` int unsigned NOT NULL DEFAULT 0,
  `error_message` longtext,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scans_user_id_status_index` (`user_id`,`status`),
  KEY `scans_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `scans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scans_target_id_foreign` FOREIGN KEY (`target_id`) REFERENCES `targets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `scan_hosts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scan_id` bigint unsigned NOT NULL,
  `ip` varchar(45) NOT NULL,
  `is_up` tinyint(1) NOT NULL DEFAULT 0,
  `hostname` varchar(255) DEFAULT NULL,
  `raw_output` longtext,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scan_hosts_scan_id_is_up_index` (`scan_id`,`is_up`),
  CONSTRAINT `scan_hosts_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `scan_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scan_host_id` bigint unsigned NOT NULL,
  `scan_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `product` varchar(255) DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  `port` int unsigned DEFAULT NULL,
  `protocol` varchar(10) DEFAULT NULL,
  `raw_line` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scan_services_scan_id_name_index` (`scan_id`,`name`),
  CONSTRAINT `scan_services_scan_host_id_foreign` FOREIGN KEY (`scan_host_id`) REFERENCES `scan_hosts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scan_services_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cve_findings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `scan_id` bigint unsigned NOT NULL,
  `scan_service_id` bigint unsigned DEFAULT NULL,
  `service_name` varchar(255) NOT NULL,
  `cve_id` varchar(32) NOT NULL,
  `description` text,
  `severity` varchar(20) DEFAULT NULL,
  `raw` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cve_findings_user_id_cve_id_index` (`user_id`,`cve_id`),
  KEY `cve_findings_scan_id_service_name_index` (`scan_id`,`service_name`),
  CONSTRAINT `cve_findings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cve_findings_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cve_findings_scan_service_id_foreign` FOREIGN KEY (`scan_service_id`) REFERENCES `scan_services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text,
  `data` json DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_notifications_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `user_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `scheduled_scans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `target_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `frequency` varchar(20) NOT NULL,
  `ports` varchar(255) NOT NULL DEFAULT '22,80,443,3306',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheduled_scans_user_id_is_active_index` (`user_id`,`is_active`),
  KEY `scheduled_scans_next_run_at_is_active_index` (`next_run_at`,`is_active`),
  CONSTRAINT `scheduled_scans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scheduled_scans_target_id_foreign` FOREIGN KEY (`target_id`) REFERENCES `targets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `default_ports` varchar(255) NOT NULL DEFAULT '22,80,443,3306',
  `notify_on_scan_complete` tinyint(1) NOT NULL DEFAULT 1,
  `notify_on_cve_found` tinyint(1) NOT NULL DEFAULT 1,
  `max_hosts_per_scan` int unsigned NOT NULL DEFAULT 64,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_settings_user_id_unique` (`user_id`),
  CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `scan_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scan_id` bigint unsigned NOT NULL,
  `download_token` varchar(64) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `mime_type` varchar(80) NOT NULL DEFAULT 'application/pdf',
  `byte_size` bigint unsigned NOT NULL,
  `content_sha256` varchar(64) NOT NULL,
  `content_hmac` varchar(64) NOT NULL,
  `storage_path` varchar(255) DEFAULT NULL,
  `content_encrypted` longtext DEFAULT NULL,
  `created_by_admin_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scan_reports_scan_id_unique` (`scan_id`),
  UNIQUE KEY `scan_reports_download_token_unique` (`download_token`),
  KEY `scan_reports_content_sha256_index` (`content_sha256`),
  CONSTRAINT `scan_reports_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scan_reports_created_by_admin_id_foreign` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2026_07_12_230000_create_portguard_core_tables', 1),
('2026_07_13_010000_create_admin_users_table', 1),
('2026_07_13_020000_create_scan_reports_table', 1),
('2026_07_13_030000_store_scan_reports_on_disk', 1);

-- Panel girişi: /login → admin@portguard.com.tr / PortGuard!2026
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'PortGuard Admin', 'admin@portguard.com.tr', NOW(), '$2y$10$zVoUYRZOWw99QkDqMKq1D.YhsdNjdHC9cok5lwYNkzMh7NmZIHYVi', NULL, NOW(), NOW());

INSERT INTO `user_settings` (`user_id`, `default_ports`, `notify_on_scan_complete`, `notify_on_cve_found`, `max_hosts_per_scan`, `created_at`, `updated_at`) VALUES
(1, '22,80,443,3306', 1, 1, 64, NOW(), NOW());

-- Yönetim girişi: /yonetim/giris → admin@portguard.com.tr / PortGuard!2026
INSERT INTO `admin_users` (`id`, `name`, `email`, `password`, `is_active`, `last_login_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Süper Admin', 'admin@portguard.com.tr', '$2y$12$LLhiGc8.GpzLgCWZZmy8heq9PDrpwcZ0owXAdlJ7G4GIlpEsEaQm.', 1, NULL, NULL, NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;
