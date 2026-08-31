-- PortGuard core tables (phpMyAdmin)
-- Database: u2604068_portguard

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `activity_logs` (
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

CREATE TABLE IF NOT EXISTS `targets` (
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

CREATE TABLE IF NOT EXISTS `scans` (
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

CREATE TABLE IF NOT EXISTS `scan_hosts` (
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

CREATE TABLE IF NOT EXISTS `scan_services` (
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

CREATE TABLE IF NOT EXISTS `cve_findings` (
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

CREATE TABLE IF NOT EXISTS `user_notifications` (
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

CREATE TABLE IF NOT EXISTS `scheduled_scans` (
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

CREATE TABLE IF NOT EXISTS `user_settings` (
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

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_12_230000_create_portguard_core_tables', COALESCE(MAX(batch), 0) + 1 FROM `migrations`;

SET FOREIGN_KEY_CHECKS = 1;
