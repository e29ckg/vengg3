-- Backup Date: 2026-06-30 04:38:45


DROP TABLE IF EXISTS `agency_settings`;
CREATE TABLE `agency_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อหน่วยงาน/ศาล',
  `directors` text COLLATE utf8mb4_unicode_ci COMMENT 'ข้อมูลผู้บริหาร (ตัวจริงและตัวสำรอง JSON)',
  `admins` text COLLATE utf8mb4_unicode_ci COMMENT 'ข้อมูลผู้อำนวยการ (ตัวจริงและตัวสำรอง JSON)',
  `finances` text COLLATE utf8mb4_unicode_ci COMMENT 'ข้อมูลการเงิน (ตัวจริงและตัวสำรอง JSON)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `agency_settings` VALUES('1','ศาล...','[{\"name\":\"นาย...\",\"position\":\"ผู้พิพากษาหัวหน้าศาล...\",\"is_active\":true},{\"name\":\"นาย...\",\"position\":\"ผู้พิพากษาหัวหน้าคณะชั้นต้... ทำการแทน ผู้พิพากษาหัวหน้าศาล...\",\"is_active\":true}]','[{\"name\":\"นางสาว...\",\"position\":\"ผู้อำนวยการสำนัก...\",\"is_active\":true},{\"name\":\"นางสาว...\",\"position\":\"นิติกรชำนาญการพิเศษรักษาราชการแทน ผู้อำนวยการสำนัก...\",\"is_active\":false}]','[{\"name\":\"นางสาว...\",\"position\":\"นักวิชาการเงินและบัญชี...\",\"is_active\":true}]');


DROP TABLE IF EXISTS `google_service_settings`;
CREATE TABLE `google_service_settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `google_service_settings` VALUES('google_calendar_id','','2026-05-18 13:34:05');
INSERT INTO `google_service_settings` VALUES('google_service_account','','2026-05-18 13:33:30');


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

INSERT INTO `profile` VALUES('1',NULL,'นาย','ผู้ดูแลระบ','ทดสอบ','1',NULL,NULL,'นักวิชาการคอมพิวเตอร์ปฏิบัติการ','กลุ่มงานช่วยอำนวยการ',NULL,'1123344','123','12311','10','2023-12-14 09:54:13','2024-05-16 06:58:08','1','user_1_1778175011.png');
INSERT INTO `profile` VALUES('11c9768a-6c56-4b36-841f-7d4c2658bacb',NULL,'นาย','dd','dd','999',NULL,NULL,'ผู้พิพากษา','กลุ่มงานคลัง',NULL,'dd',NULL,NULL,'10',NULL,NULL,'1',NULL);


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

INSERT INTO `system_logs` VALUES('28','1','CREATE','TRANSFER','สร้างคำขอยกเวร (ใบเปลี่ยนเลขที่: CH-202606-1413)',NULL,'{\"is_swap\": 0, \"new_user_id\": \"11c9768a-6c56-4b36-841f-7d4c2658bacb\", \"schedule_id\": 2000}',NULL,'2026-06-30 09:35:20');
INSERT INTO `system_logs` VALUES('29','1','CANCEL','TRANSFER','ยกเลิกคำขอเปลี่ยนเวร (ใบเปลี่ยนเลขที่: CH-202606-1413)','{\"id\": 108, \"date1\": \"2026-06-30\", \"date2\": null, \"s1_id\": 2000, \"s2_id\": null, \"status\": 0, \"is_swap\": 0, \"user1_id\": \"1\", \"user2_id\": \"11c9768a-6c56-4b36-841f-7d4c2658bacb\", \"change_no\": \"CH-202606-1413\", \"created_at\": \"2026-06-30 09:35:20\"}',NULL,NULL,'2026-06-30 09:36:25');
INSERT INTO `system_logs` VALUES('30','1','LOGIN','AUTH','ผู้ใช้งาน admin เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"admin\"}',NULL,'2026-06-30 09:40:03');
INSERT INTO `system_logs` VALUES('31','1','CREATE','TRANSFER','สร้างคำขอยกเวร (ใบเปลี่ยนเลขที่: CH-202606-7557)',NULL,'{\"is_swap\": 0, \"new_user_id\": \"11c9768a-6c56-4b36-841f-7d4c2658bacb\", \"schedule_id\": 2000}',NULL,'2026-06-30 09:49:56');
INSERT INTO `system_logs` VALUES('32','1','UPDATE','APPROVE','อัปเดตสถานะใบเปลี่ยนเวร (ID อ้างอิง: 105, อัปเดตเป็นสถานะ: รออนุมัติ)',NULL,'{\"status\": 0, \"change_id\": 105}',NULL,'2026-06-30 09:54:41');
INSERT INTO `system_logs` VALUES('33','1','UPDATE','APPROVE','อัปเดตสถานะใบเปลี่ยนเวร (ID อ้างอิง: 105, อัปเดตเป็นสถานะ: อนุมัติ)',NULL,'{\"status\": 1, \"change_id\": 105}',NULL,'2026-06-30 09:55:00');
INSERT INTO `system_logs` VALUES('34','1','UPDATE','APPROVE','อัปเดตสถานะใบเปลี่ยนเวร (ID อ้างอิง: 106, อัปเดตเป็นสถานะ: อนุมัติ)',NULL,'{\"status\": 1, \"change_id\": 106}',NULL,'2026-06-30 09:55:10');
INSERT INTO `system_logs` VALUES('35','1','CANCEL','TRANSFER','ยกเลิกคำขอเปลี่ยนเวร (ใบเปลี่ยนเลขที่: CH-202606-7557)','{\"id\": 109, \"date1\": \"2026-06-30\", \"date2\": null, \"s1_id\": 2000, \"s2_id\": null, \"status\": 0, \"is_swap\": 0, \"user1_id\": \"1\", \"user2_id\": \"11c9768a-6c56-4b36-841f-7d4c2658bacb\", \"change_no\": \"CH-202606-7557\", \"created_at\": \"2026-06-30 09:49:56\"}',NULL,NULL,'2026-06-30 11:08:55');
INSERT INTO `system_logs` VALUES('36','1','LOGIN','AUTH','ผู้ใช้งาน admin เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"admin\"}',NULL,'2026-06-30 11:17:41');
INSERT INTO `system_logs` VALUES('37','1','LOGIN','AUTH','ผู้ใช้งาน admin เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"admin\"}',NULL,'2026-06-30 11:18:40');
INSERT INTO `system_logs` VALUES('38','1','CREATE','TRANSFER','สร้างคำขอยกเวร (ใบเปลี่ยนเลขที่: CH-202606-4199)',NULL,'{\"is_swap\": 0, \"new_user_id\": \"11c9768a-6c56-4b36-841f-7d4c2658bacb\", \"schedule_id\": 2000}',NULL,'2026-06-30 11:19:48');
INSERT INTO `system_logs` VALUES('39','1','CANCEL','TRANSFER','ยกเลิกคำขอเปลี่ยนเวร (ใบเปลี่ยนเลขที่: CH-202606-4199)','{\"id\": 110, \"date1\": \"2026-06-30\", \"date2\": null, \"s1_id\": 2000, \"s2_id\": null, \"status\": 0, \"is_swap\": 0, \"user1_id\": \"1\", \"user2_id\": \"11c9768a-6c56-4b36-841f-7d4c2658bacb\", \"change_no\": \"CH-202606-4199\", \"created_at\": \"2026-06-30 11:19:48\"}',NULL,NULL,'2026-06-30 11:20:11');
INSERT INTO `system_logs` VALUES('40','1','LOGIN','AUTH','ผู้ใช้งาน admin เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"admin\"}',NULL,'2026-06-30 11:21:40');
INSERT INTO `system_logs` VALUES('41','1','LOGIN','AUTH','ผู้ใช้งาน admin เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"admin\"}',NULL,'2026-06-30 11:26:38');
INSERT INTO `system_logs` VALUES('42','11c9768a-6c56-4b36-841f-7d4c2658bacb','LOGIN','AUTH','ผู้ใช้งาน dd เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"dd\"}',NULL,'2026-06-30 11:27:10');
INSERT INTO `system_logs` VALUES('43','1','LOGIN','AUTH','ผู้ใช้งาน admin เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"admin\"}',NULL,'2026-06-30 11:28:33');
INSERT INTO `system_logs` VALUES('44','11c9768a-6c56-4b36-841f-7d4c2658bacb','CREATE','SCHEDULE','เพิ่มข้อมูลจัดเวร (วันที่: ไม่ระบุวันที่)',NULL,'{\"date\": \"2026-06-29\", \"com_id\": 1772680496, \"sub_id\": 130, \"user_id\": \"1\"}',NULL,'2026-06-30 11:31:14');
INSERT INTO `system_logs` VALUES('45','11c9768a-6c56-4b36-841f-7d4c2658bacb','CREATE','SCHEDULE','เพิ่มข้อมูลจัดเวร (วันที่: ไม่ระบุวันที่)',NULL,'{\"date\": \"2026-06-27\", \"com_id\": 1772680496, \"sub_id\": 130, \"user_id\": \"1\"}',NULL,'2026-06-30 11:31:59');
INSERT INTO `system_logs` VALUES('46','1','LOGIN','AUTH','ผู้ใช้งาน admin เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"admin\"}',NULL,'2026-06-30 11:36:20');
INSERT INTO `system_logs` VALUES('47','11c9768a-6c56-4b36-841f-7d4c2658bacb','LOGIN','AUTH','ผู้ใช้งาน dd เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"dd\"}',NULL,'2026-06-30 11:37:19');
INSERT INTO `system_logs` VALUES('48','1','LOGIN','AUTH','ผู้ใช้งาน admin เข้าสู่ระบบสำเร็จ',NULL,'{\"username\": \"admin\"}',NULL,'2026-06-30 11:38:20');


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

INSERT INTO `system_settings` VALUES('1','ระบบบริหารจัดการเวรนอกเวลาทำการ','1','0','0','1','1','{\"prefixes\":[\"นาย\",\"นาง\",\"นางสาว\"],\"positions\":[\"ผู้พิพากษา\",\"นิติกร\",\"เจ้าพนักงานศาลยุติธรรม\",\"เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ\",\"นิติกรชำนาญการพิเศษ\",\"ผู้พิพากษาหัวหน้าคณะชั้นต้นในศาล...\",\"ผู้พิพากษาหัวหน้าศาล...\",\"ผู้อำนวยการสำนัก...\",\"นักจิตวิทยาชำนาญการ\",\"เจ้าพนักงานศาลยุติธรรมชำนาญการ\",\"เจ้าพนักงานศาลยุติธรรมปฏิบัติการ\",\"นักวิชาการเงินและบัญชีปฏิบัติการ\",\"นักจิตวิทยาปฏิบัติการ\",\"นักวิชาการพัสดุปฏิบัติการ\",\"เจ้าพนักงานตำรวจศาลปฏิบัติการ\",\"เจ้าหน้าที่ศาลยุติธรรมชำนาญงาน\",\"เจ้าพนักงานการเงินและบัญชีปฏิบัติงาน\",\"เจ้าหน้าที่ศาลยุติธรรมปฏิบัติงาน\",\"พนักงานขับรถยนต์\",\"นักวิชาการคอมพิวเตอร์ปฏิบัติการ\"],\"departments\":[\"ผู้พิพากษา\",\"กลุ่มงานปริการประชาชนและประชาสัมพันธ์\",\"กลุ่มงานไกล่เกลี่ยและประนอมข้อพิพาท\",\"กลุ่มงานคดี\",\"กลุ่มงานช่วยพิจารณาคดี\",\"กลุ่มงานช่วยอำนวยการ\",\"กลุ่มงานเจ้าพนักงานตำรวจศาล\",\"กลุ่มงานคลัง\"]}','1');


DROP TABLE IF EXISTS `telegram_notify_times`;
CREATE TABLE `telegram_notify_times` (
  `id` int NOT NULL AUTO_INCREMENT,
  `send_time` time NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `notify_day` tinyint(1) DEFAULT '0' COMMENT '0: Today, 1: Tomorrow',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `telegram_notify_times` VALUES('54','07:00:00','1','0');
INSERT INTO `telegram_notify_times` VALUES('55','19:30:00','1','1');


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

INSERT INTO `telegram_settings` VALUES('1','7785178042:AAHHa-qbxlyJy7Ff0F3QS_F0NEQr5Qbk3Wc','7873635913','1','1','1');


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

INSERT INTO `user` VALUES('1','admin','ec326ec963b6ae67d764e4e7c80c9d6a','$2y$10$cagJUbgpXBGOcPaLgqXauep2t4utkj0MrtQOorkjPukwGLkLARXkK',NULL,NULL,'9','10','0','2023-12-14 09:54:13','2024-04-27 18:13:27');
INSERT INTO `user` VALUES('11c9768a-6c56-4b36-841f-7d4c2658bacb','dd','349958adbad79ccfdee98386d5e43ab1','$2y$10$4cYm8qqR5UQ1E/uGvyXHMewxeQvRa30w2IzRznyNiFXfR410Xbk.G',NULL,NULL,'3','10','0','2026-05-28 12:00:10',NULL);


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

INSERT INTO `ven_change` VALUES('105','CH-202606-4179','1997','1',NULL,'11c9768a-6c56-4b36-841f-7d4c2658bacb','1','2026-06-30 08:24:58','0');
INSERT INTO `ven_change` VALUES('106','CH-202606-9989','1995','1',NULL,'11c9768a-6c56-4b36-841f-7d4c2658bacb','1','2026-06-30 08:31:07','0');


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

INSERT INTO `ven_com` VALUES('1772680495','00','2026-05-28','2026-05','1','24','1,2,3',NULL,NULL,NULL,NULL);
INSERT INTO `ven_com` VALUES('1772680496','0666','2026-06-29','2026-06','0','24','27,28,29,30',NULL,NULL,NULL,NULL);


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

INSERT INTO `ven_name` VALUES('24','เวรเปิดทำการศาลนอกเวลาราชการ (เวรตรวจสอบการจับ)','ให้ข้าราชการฝ่ายตุลาการศาลยุติธรรม พนักงานราชการศาลยุติธรรม และลูกจ้างในศาลเยาวชนและครอบครัวกลาง อยู่ปฏิบัติหน้าที่โดยเปิดทำการศาลนอกเวลาราชการในวันหยุดราชการ','กลางวัน(08.30-16.30)','ven_report_24_1756973949.docx','0','1','ddddxx');
INSERT INTO `ven_name` VALUES('27','หมายจับ - ค้น (กลางคืน 16.30-08.30 น.)','ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 16.30 – 08.30 นาฬิกา ','กลางคืน(16.30-08.30)',NULL,'2','1',NULL);
INSERT INTO `ven_name` VALUES('28','เวรปฏิบัติงานในวันหยุดราชการตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 8.30-16.30 น.','ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 08.30 – 16.30 นาฬิกา ตามโครงการเปิดทำการศาลนอกเวลาราชการเพื่อเร่งรัดการพิจารณาพิพากษาคดี หรือเพื่ออำนวยความสะดวกแก่ประชาชน ประจำปีงบประมาณ พ.ศ. ๒๕๖๗ ','กลางวัน(08.30-16.30)',NULL,'1','0',NULL);
INSERT INTO `ven_name` VALUES('36','เวรเร่งรัด','เวรเร่งรัด','nightCourt(16.30-20.00)',NULL,'4','0',NULL);
INSERT INTO `ven_name` VALUES('39','ผู้ตรวจ','ผู้ตรวจ','กลางคืน(16.30-08.30)',NULL,'3','1',NULL);
INSERT INTO `ven_name` VALUES('40','ddd','ddd','กลางวัน(08.30-16.30)',NULL,'4','0',NULL);


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

INSERT INTO `ven_name_sub` VALUES('113','ผู้พิพากษา','27','1500','Brown','1','1');
INSERT INTO `ven_name_sub` VALUES('115','ผู้พิพากษา','28','3000','Brown','1','0');
INSERT INTO `ven_name_sub` VALUES('116','รับฟ้อง+ปชส ','28','1500','Brown','2','0');
INSERT INTO `ven_name_sub` VALUES('117','ผอ./แทน','24','1500','#944cd6','2','1');
INSERT INTO `ven_name_sub` VALUES('118','จนท.','24','1500','#a86edd','3','1');
INSERT INTO `ven_name_sub` VALUES('119','งานหมาย','24','1500','BlueViolet','4','0');
INSERT INTO `ven_name_sub` VALUES('120','งานประชาสัมพันธ์','24','1500','BlueViolet','5','0');
INSERT INTO `ven_name_sub` VALUES('121','งานการเงิน','24','1500','BlueViolet','6','0');
INSERT INTO `ven_name_sub` VALUES('123','จนท','27','1200','#b65454','2','1');
INSERT INTO `ven_name_sub` VALUES('124','การเงิน+ปล่อยตัวชั่วคราว','27','1000','Green','2','0');
INSERT INTO `ven_name_sub` VALUES('125','หน้าบัลลังก์','27','1000','Green','3','0');
INSERT INTO `ven_name_sub` VALUES('128','การเงิน+ปล่อยตัวชั่วคราว ','28','1500','Brown','3','0');
INSERT INTO `ven_name_sub` VALUES('129','หน้าบัลลังก์','28','1500','Brown','4','0');
INSERT INTO `ven_name_sub` VALUES('130','ผู้พิพากษา','24','3000','#5774e5','1','1');
INSERT INTO `ven_name_sub` VALUES('133','ผู้พิพากษาสมทบ','29','1100','Magenta','0','0');
INSERT INTO `ven_name_sub` VALUES('148','ผู้ตรวจ','39','0','#eadcb3','1','1');
INSERT INTO `ven_name_sub` VALUES('149','dd','40','0','BlueViolet','1','0');


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

INSERT INTO `ven_schedule` VALUES('1993','2026-07-01','1772680495','130','1','1','2026-05-28 17:09:21',NULL);
INSERT INTO `ven_schedule` VALUES('1994','2026-07-01','1772680495','130','11c9768a-6c56-4b36-841f-7d4c2658bacb','1','2026-05-28 17:09:23',NULL);
INSERT INTO `ven_schedule` VALUES('1996','2026-06-28','1772680496','130','11c9768a-6c56-4b36-841f-7d4c2658bacb','1','2026-06-29 14:31:15',NULL);
INSERT INTO `ven_schedule` VALUES('1997','2026-06-29','1772680496','130','11c9768a-6c56-4b36-841f-7d4c2658bacb','1','2026-06-29 14:31:15',NULL);
INSERT INTO `ven_schedule` VALUES('2000','2026-06-30','1772680496','130','1','1','2026-06-30 08:30:19',NULL);
INSERT INTO `ven_schedule` VALUES('2002','2026-06-30','1772680496','130','11c9768a-6c56-4b36-841f-7d4c2658bacb','1','2026-06-30 08:32:31',NULL);
INSERT INTO `ven_schedule` VALUES('2003','2026-06-29','1772680496','130','1','1','2026-06-30 11:31:14',NULL);
INSERT INTO `ven_schedule` VALUES('2004','2026-06-27','1772680496','130','1','1','2026-06-30 11:31:59',NULL);


DROP TABLE IF EXISTS `ven_time`;
CREATE TABLE `ven_time` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_th` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อเรียก เช่น กลางวัน',
  `time_period` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ช่วงเวลา เช่น 08.30-16.30',
  `srt` int NOT NULL DEFAULT '99' COMMENT 'ลำดับการแสดงผล',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ven_time` VALUES('1','กลางวัน','08.30-16.30','1');
INSERT INTO `ven_time` VALUES('2','กลางคืน','16.30-08.30','2');
INSERT INTO `ven_time` VALUES('3','nightCourt','16.30-20.00','3');


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

INSERT INTO `ven_user` VALUES('894','1',NULL,'130',NULL,NULL,'2');
INSERT INTO `ven_user` VALUES('902','11c9768a-6c56-4b36-841f-7d4c2658bacb',NULL,'117',NULL,NULL,'1');
INSERT INTO `ven_user` VALUES('903','1',NULL,'117',NULL,NULL,'2');
INSERT INTO `ven_user` VALUES('910','11c9768a-6c56-4b36-841f-7d4c2658bacb',NULL,'130',NULL,NULL,'3');
INSERT INTO `ven_user` VALUES('911','1',NULL,'149',NULL,NULL,'1');

