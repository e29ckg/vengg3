-- สร้างฐานข้อมูล (หากยังไม่มี)
CREATE DATABASE IF NOT EXISTS `vengg_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vengg_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. ตารางตั้งค่าหน่วยงาน
DROP TABLE IF EXISTS `agency_settings`;
CREATE TABLE `agency_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อหน่วยงาน/ศาล',
  `director_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อผู้บริหาร',
  `director_position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ตำแหน่งผู้บริหาร',
  `directors` text COLLATE utf8mb4_unicode_ci COMMENT 'ข้อมูลผู้บริหาร (ตัวจริงและตัวสำรอง JSON)',
  `admins` text COLLATE utf8mb4_unicode_ci COMMENT 'ข้อมูลผู้อำนวยการ (ตัวจริงและตัวสำรอง JSON)',
  `finances` text COLLATE utf8mb4_unicode_ci COMMENT 'ข้อมูลการเงิน (ตัวจริงและตัวสำรอง JSON)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `agency_settings` VALUES('1','ศาลจังหวัด...','(ลงชื่อ).......................................................','ตำแหน่งผู้บริหาร','[{\"name\":\"นาย...\",\"position\":\"ผู้พิพากษาหัวหน้า...\",\"is_active\":true},{\"name\":\"นาย...\",\"position\":\"รอง\",\"is_active\":true},{\"name\":\"www\",\"position\":\"www\",\"is_active\":false}]','[{\"name\":\"นางสาว...\",\"position\":\"ผู้อำนวยการ...\",\"is_active\":true},{\"name\":\"okpddsd\",\"position\":\"sssss\",\"is_active\":true}]','[{\"name\":\"นางสาว...\",\"position\":\"นักวิชาการเงิน...\",\"is_active\":true},{\"name\":\"sss\",\"position\":\"\",\"is_active\":true}]');


-- 2. ตารางตั้งค่า Google
DROP TABLE IF EXISTS `google_service_settings`;
CREATE TABLE `google_service_settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `google_service_settings` VALUES('google_calendar_id','','2026-05-09 15:28:18');
INSERT INTO `google_service_settings` VALUES('google_service_account','','2026-05-09 15:17:20');


-- 3. ตารางข้อมูลส่วนตัว
DROP TABLE IF EXISTS `profile`;
CREATE TABLE `profile` (
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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

INSERT INTO `profile` VALUES('1',NULL,'นาย','ผู้ดูแลระบบ','ทดสอบ','1',NULL,NULL,'ผู้พิพากษา','ผู้พิพากษา',NULL,'1123344','123','12311','10','2023-12-14 09:54:13','2024-05-16 06:58:08','1','user_1_1778175011.png');


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

INSERT INTO `system_settings` VALUES('1','ระบบบริหารจัดการเวรนอกเวลาทำการ','1','0','0','0','1','{\"prefixes\":[\"นาย\",\"นาง\",\"นางสาว\"],\"positions\":[\"ผู้พิพากษา\",\"ผู้อำนวยการฯ\",\"นิติกร\",\"เจ้าพนักงานศาลยุติธรรม\",\"ผู้พิพากษาหัวหน้าศาลฯ\",\"ผู้พิพากษาหัวหน้าคณะชั้นต้นในศาลฯ\",\"เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ\",\"นิติกรชำนาญการพิเศษ\"],\"departments\":[\"ผู้พิพากษา\",\"กลุ่มงานปริการประชาชนและประชาสัมพันธ์\",\"กลุ่มงานไกล่เกลี่ยและประนอมข้อพิพาท\",\"กลุ่มงานคดี\",\"กลุ่มงานช่วยพิจารณาคดี\",\"กลุ่มงานช่วยอำนวยการ\",\"กลุ่มงานเจ้าพนักงานตำรวจศาล\",\"กลุ่มงานคลัง\"]}','1');


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

INSERT INTO `user` VALUES('1','admin','745bb4f31dd9e00d84f43075baf9ac03','$2y$10$cagJUbgpXBGOcPaLgqXauep2t4utkj0MrtQOorkjPukwGLkLARXkK',NULL,NULL,'9','10','0','2023-12-14 09:54:13','2024-04-27 18:13:27');


-- 6. ตารางแจ้งเตือนเวลา Telegram
DROP TABLE IF EXISTS `telegram_notify_times`;
CREATE TABLE `telegram_notify_times` (
  `id` int NOT NULL AUTO_INCREMENT,
  `send_time` time NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `notify_day` tinyint(1) DEFAULT '0' COMMENT '0: Today, 1: Tomorrow',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `telegram_notify_times` VALUES('44','10:30:00','1','1');
INSERT INTO `telegram_notify_times` VALUES('45','10:32:00','1','0');


-- 7. ตารางการเปลี่ยนเวร
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
  `ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `word` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=ปกติ, 0=ลบ (Soft Delete)',
  `google_calendar_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ven_name` VALUES('24','เวรเปิดทำการศาลนอกเวลาราชการ (เวรตรวจสอบการจับ)','ให้ข้าราชการฝ่ายตุลาการศาลยุติธรรม พนักงานราชการศาลยุติธรรม และลูกจ้างในศาลเยาวชนและครอบครัวกลาง อยู่ปฏิบัติหน้าที่โดยเปิดทำการศาลนอกเวลาราชการในวันหยุดราชการ','กลางวัน(08.30-16.30)','ven_report_24_1756973949.docx','0','1','ddddxx');
INSERT INTO `ven_name` VALUES('27','หมายจับ - ค้น (กลางคืน 16.30-08.30 น.)','ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 16.30 – 08.30 นาฬิกา ','กลางคืน(16.30-08.30)',NULL,'2','1',NULL);
INSERT INTO `ven_name` VALUES('28','เวรปฏิบัติงานในวันหยุดราชการตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 8.30-16.30 น.','ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 08.30 – 16.30 นาฬิกา ตามโครงการเปิดทำการศาลนอกเวลาราชการเพื่อเร่งรัดการพิจารณาพิพากษาคดี หรือเพื่ออำนวยความสะดวกแก่ประชาชน ประจำปีงบประมาณ พ.ศ. ๒๕๖๗ ','กลางวัน(08.30-16.30)',NULL,'1','0',NULL);
INSERT INTO `ven_name` VALUES('36','เวรเร่งรัด','เวรเร่งรัด','nightCourt(16.30-20.00)',NULL,'4','0',NULL);


-- 10. ตารางหน้าที่ย่อย
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ven_name_sub` VALUES('113','ผู้พิพากษา','27','1500','Green','1','1');
INSERT INTO `ven_name_sub` VALUES('115','ผู้พิพากษา','28','3000','Brown','1','0');
INSERT INTO `ven_name_sub` VALUES('116','รับฟ้อง+ปชส ','28','1500','Brown','2','0');
INSERT INTO `ven_name_sub` VALUES('117','ผอ./แทน','24','1500','BlueViolet','2','1');
INSERT INTO `ven_name_sub` VALUES('118','จนท','24','1500','BlueViolet','3','1');
INSERT INTO `ven_name_sub` VALUES('119','งานหมาย','24','1500','BlueViolet','4','0');
INSERT INTO `ven_name_sub` VALUES('120','งานประชาสัมพันธ์','24','1500','BlueViolet','5','0');
INSERT INTO `ven_name_sub` VALUES('121','งานการเงิน','24','1500','BlueViolet','6','0');
INSERT INTO `ven_name_sub` VALUES('123','จนท','27','1200','Green','2','1');
INSERT INTO `ven_name_sub` VALUES('124','การเงิน+ปล่อยตัวชั่วคราว','27','1000','Green','2','0');
INSERT INTO `ven_name_sub` VALUES('125','หน้าบัลลังก์','27','1000','Green','3','0');
INSERT INTO `ven_name_sub` VALUES('128','การเงิน+ปล่อยตัวชั่วคราว ','28','1500','Brown','3','0');
INSERT INTO `ven_name_sub` VALUES('129','หน้าบัลลังก์','28','1500','Brown','4','0');
INSERT INTO `ven_name_sub` VALUES('130','ผู้พิพากษา','24','3000','BlueViolet','1','1');
INSERT INTO `ven_name_sub` VALUES('133','ผู้พิพากษาสมทบ','29','1100','Magenta','0','0');


-- 11. ตารางตารางเวรจริง
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
