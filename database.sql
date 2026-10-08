-- Schema-only installation database. No production data or credentials.



DROP TABLE IF EXISTS `agency_settings`;
CREATE TABLE `agency_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อหน่วยงาน/ศาล',
  `directors` text COLLATE utf8mb4_unicode_ci COMMENT 'ข้อมูลผู้บริหาร (ตัวจริงและตัวสำรอง JSON)',
  `admins` text COLLATE utf8mb4_unicode_ci COMMENT 'ข้อมูลผู้อำนวยการ (ตัวจริงและตัวสำรอง JSON)',
  `finances` text COLLATE utf8mb4_unicode_ci COMMENT 'ข้อมูลการเงิน (ตัวจริงและตัวสำรอง JSON)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `google_service_settings`;
CREATE TABLE `google_service_settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `profile`;
CREATE TABLE `profile` (
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  UNIQUE KEY `user_id` (`user_id`),
  KEY `user_id_2` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'เช่น LOGIN, CREATE, UPDATE, DELETE',
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'เช่น USER, SCHEDULE, SWAP, SETTING',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_data` json DEFAULT NULL,
  `new_data` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `system_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'ระบบบริหารจัดการเวรนอกเวลาทำการ',
  `allow_swap` tinyint(1) DEFAULT '1' COMMENT '1 = อนุญาตให้แลกเวร, 0 = ปิดระบบแลกเวร',
  `advance_swap_days` int DEFAULT '3' COMMENT 'ต้องขอแลกเวรล่วงหน้ากี่วัน',
  `maintenance_mode` tinyint(1) DEFAULT '0' COMMENT '1 = ปิดปรับปรุงระบบ',
  `allow_retroactive_swap` tinyint(1) DEFAULT '0' COMMENT '1 = เปลี่ยนย้อนหลังได้, 0 = ไม่ได้',
  `check_24h_consecutive` tinyint(1) DEFAULT '1' COMMENT '1 = เปิดระบบแจ้งเตือนเวร 24 ชม.',
  `user_options` text COLLATE utf8mb4_unicode_ci COMMENT 'เก็บ JSON ของ คำนำหน้า, ตำแหน่ง, กลุ่มงาน',
  `compact_schedule_view` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=แบบเดิม(2 บรรทัด), 1=แบบกะทัดรัด(บรรทัดเดียว)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `telegram_notify_times`;
CREATE TABLE `telegram_notify_times` (
  `id` int NOT NULL AUTO_INCREMENT,
  `send_time` time NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `notify_day` tinyint(1) DEFAULT '0' COMMENT '0: Today, 1: Tomorrow',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `telegram_settings`;
CREATE TABLE `telegram_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bot_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chat_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notify_confirmed` tinyint(1) DEFAULT '1',
  `notify_change_request` tinyint(1) DEFAULT '1',
  `notify_approval` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



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
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `password_reset_token` (`password_reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `ven_change`;
CREATE TABLE `ven_change` (
  `id` int NOT NULL AUTO_INCREMENT,
  `change_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `s1_id` int NOT NULL COMMENT 'รหัสตารางเวรของผู้ขอ (ven_schedule.id)',
  `user1_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'รหัสผู้ขอแลก',
  `s2_id` int DEFAULT NULL COMMENT 'รหัสตารางเวรของเพื่อน (ven_schedule.id)',
  `user2_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'รหัสเพื่อนที่ถูกขอแลก',
  `status` int DEFAULT '0' COMMENT '0=รออนุมัติ, 1=ยอมรับแล้ว, 2=ปฏิเสธ',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_swap` tinyint(1) DEFAULT '0' COMMENT '0=ยกเวรให้ปกติ, 1=สลับเวรกัน',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



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
  `ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1772680497 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `ven_name`;
CREATE TABLE `ven_name` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_full` text COLLATE utf8mb4_unicode_ci,
  `dn` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `word` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=ปกติ, 0=ลบ (Soft Delete)',
  `google_calendar_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `ven_name_sub`;
CREATE TABLE `ven_name_sub` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ven_name_id` int NOT NULL,
  `price` int DEFAULT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=ปกติ, 0=ลบ (Soft Delete)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `ven_schedule`;
CREATE TABLE `ven_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ven_date` date NOT NULL COMMENT 'วันที่ปฏิบัติหน้าที่ (YYYY-MM-DD)',
  `ven_com_id` int NOT NULL COMMENT 'อ้างอิงรหัสคำสั่งจากตาราง ven_com',
  `ven_name_sub_id` int NOT NULL COMMENT 'อ้างอิงรหัสหน้าที่ย่อยจากตาราง ven_name_sub',
  `user_id` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'อ้างอิงรหัสพนักงานจากตาราง user',
  `status` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `google_event_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ven_date` (`ven_date`),
  KEY `idx_ven_com` (`ven_com_id`),
  KEY `idx_ven_sub` (`ven_name_sub_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2005 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `ven_time`;
CREATE TABLE `ven_time` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_th` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อเรียก เช่น กลางวัน',
  `time_period` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ช่วงเวลา เช่น 08.30-16.30',
  `srt` int NOT NULL DEFAULT '99' COMMENT 'ลำดับการแสดงผล',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



DROP TABLE IF EXISTS `ven_user`;
CREATE TABLE `ven_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_num` int DEFAULT NULL,
  `ven_name_sub_id` int DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999' COMMENT 'ลำดับคิวการเข้าเวร',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=912 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `agency_settings` (`id`, `agency_name`, `directors`, `admins`, `finances`) VALUES (1, '', '[]', '[]', '[]');
INSERT INTO `google_service_settings` (`setting_key`, `setting_value`) VALUES ('google_calendar_id', ''), ('google_service_account', '');
INSERT INTO `system_settings` (`id`, `user_options`) VALUES (1, '{}');
INSERT INTO `telegram_settings` (`id`, `bot_token`, `chat_id`) VALUES (1, '', '');
INSERT INTO `ven_time` (`name_th`, `time_period`, `srt`) VALUES ('กลางวัน', '08.30-16.30', 1), ('กลางคืน', '16.30-08.30', 2);
