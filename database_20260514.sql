-- สร้างฐานข้อมูล (หากยังไม่มี)
CREATE DATABASE IF NOT EXISTS `vengg_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vengg_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. ตารางตั้งค่าหน่วยงาน
DROP TABLE IF EXISTS `agency_settings`;
CREATE TABLE `agency_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `director_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `director_position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `directors` text COLLATE utf8mb4_unicode_ci,
  `admins` text COLLATE utf8mb4_unicode_ci,
  `finances` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ตารางตั้งค่า Google
DROP TABLE IF EXISTS `google_service_settings`;
CREATE TABLE `google_service_settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ตารางข้อมูลส่วนตัว
DROP TABLE IF EXISTS `profile`;
CREATE TABLE `profile` (
  `user_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_card` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prefix_name` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999',
  `birthday` date DEFAULT NULL,
  `bloodtype` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_comment` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` smallint DEFAULT '10',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `st` smallint DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ตารางตั้งค่าระบบ
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `system_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'ระบบบริหารจัดการเวรนอกเวลาทำการ',
  `allow_swap` tinyint(1) DEFAULT '1',
  `advance_swap_days` int DEFAULT '3',
  `maintenance_mode` tinyint(1) DEFAULT '0',
  `allow_retroactive_swap` tinyint(1) DEFAULT '0',
  `check_24h_consecutive` tinyint(1) DEFAULT '1',
  `user_options` text COLLATE utf8mb4_unicode_ci,
  `compact_schedule_view` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ตารางผู้ใช้
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_key` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` smallint NOT NULL DEFAULT '1',
  `status` smallint NOT NULL DEFAULT '10',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ตารางบันทึกการทำงาน (Log)
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_data` json DEFAULT NULL,
  `new_data` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ตารางการเปลี่ยนเวร
DROP TABLE IF EXISTS `ven_change`;
CREATE TABLE `ven_change` (
  `id` int NOT NULL AUTO_INCREMENT,
  `change_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `s1_id` int NOT NULL,
  `user1_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `s2_id` int DEFAULT NULL,
  `user2_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_swap` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ตารางคำสั่งเวร
DROP TABLE IF EXISTS `ven_com`;
CREATE TABLE `ven_com` (
  `id` int NOT NULL AUTO_INCREMENT,
  `com_num` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `com_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ven_month` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ven_name_id` int DEFAULT NULL,
  `ven_com_days` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ตารางชื่อเวรหลัก
DROP TABLE IF EXISTS `ven_name`;
CREATE TABLE `ven_name` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_full` text COLLATE utf8mb4_unicode_ci,
  `dn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. ตารางหน้าที่ย่อย
DROP TABLE IF EXISTS `ven_name_sub`;
CREATE TABLE `ven_name_sub` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ven_name_id` int NOT NULL,
  `price` int DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. ตารางตารางเวรจริง
DROP TABLE IF EXISTS `ven_schedule`;
CREATE TABLE `ven_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ven_date` date NOT NULL,
  `ven_com_id` int NOT NULL,
  `ven_name_sub_id` int NOT NULL,
  `user_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. ตารางช่วงเวลา
DROP TABLE IF EXISTS `ven_time`;
CREATE TABLE `ven_time` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_th` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_period` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `srt` int NOT NULL DEFAULT '99',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. ตารางผู้มีสิทธิ์ (Queue)
DROP TABLE IF EXISTS `ven_user`;
CREATE TABLE `ven_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ven_name_sub_id` int DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. ตารางตั้งค่าแจ้งเตือน Telegram
DROP TABLE IF EXISTS `telegram_settings`;
CREATE TABLE `telegram_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bot_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chat_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- บันทึก Admin เริ่มต้น (รหัสผ่าน: password123)
INSERT INTO `user` (`id`, `username`, `password_hash`, `role`, `status`, `created_at`) VALUES 
('1', 'admin', '$2y$10$cagJUbgpXBGOcPaLgqXauep2t4utkj0MrtQOorkjPukwGLkLARXkK', 9, 10, NOW());
INSERT INTO `profile` (`user_id`, `first_name`, `last_name`) VALUES ('1', 'System', 'Administrator');
