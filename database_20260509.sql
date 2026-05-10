-- Backup Date: 2026-05-09 18:22:04


DROP TABLE IF EXISTS `agency_settings`;
CREATE TABLE `agency_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(255) NOT NULL COMMENT 'ชื่อหน่วยงาน/ศาล',
  `director_name` varchar(255) NOT NULL COMMENT 'ชื่อผู้บริหาร',
  `director_position` varchar(255) NOT NULL COMMENT 'ตำแหน่งผู้บริหาร',
  `directors` text COMMENT 'ข้อมูลผู้บริหาร (ตัวจริงและตัวสำรอง JSON)',
  `admins` text COMMENT 'ข้อมูลผู้อำนวยการ (ตัวจริงและตัวสำรอง JSON)',
  `finances` text COMMENT 'ข้อมูลการเงิน (ตัวจริงและตัวสำรอง JSON)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `agency_settings` VALUES('1','ศาลจังหวัดเพชรบุรี','(ลงชื่อ).......................................................','ตำแหน่งผู้บริหาร','[{\"name\":\"นายชูเกียรติ ภานุกรอุดม\",\"position\":\"ผู้พิพากษาหัวหน้าคณะชั้นต้นในศาลเยาวชนและครอบครัวจังหวัดลพบุรี ช่วยฯ ผู้พิพากษาหัวหน้าศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์\",\"is_active\":true},{\"name\":\"นายพพพ\",\"position\":\"รอง\",\"is_active\":true},{\"name\":\"www\",\"position\":\"www\",\"is_active\":false}]','[{\"name\":\"นางสาวศรีชมภู อุ่นจิตรพันธ์\",\"position\":\"ผู้อำนวยการสำนักงานประจำศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์\",\"is_active\":true},{\"name\":\"okpddsd\",\"position\":\"sssss\",\"is_active\":true}]','[{\"name\":\"นางสาวพจนา เทพพิชิตสมุทร\",\"position\":\"นักวิชาการเงินและบัญชีปฏิบัติการ\",\"is_active\":true},{\"name\":\"sss\",\"position\":\"\",\"is_active\":false}]');


DROP TABLE IF EXISTS `google_service_settings`;
CREATE TABLE `google_service_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `google_service_settings` VALUES('google_calendar_id','32efdc39d1d3eebad8b56b49562a1d418fdc9904a7efee08a0c98e939b68f64b@group.calendar.google.com','2026-05-09 15:28:18');
INSERT INTO `google_service_settings` VALUES('google_service_account','cal-service-test@poetic-shell-451413-e7.iam.gserviceaccount.com','2026-05-09 15:17:20');


DROP TABLE IF EXISTS `profile`;
CREATE TABLE `profile` (
  `user_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `id_card` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `prefix_name` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999',
  `birthday` date DEFAULT NULL,
  `bloodtype` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `position` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `department` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bank_account` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bank_comment` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `status` smallint DEFAULT '10',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `st` smallint DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  UNIQUE KEY `user_id` (`user_id`),
  KEY `user_id_2` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `profile` VALUES('1',NULL,'นาย','ผู้ดูแลระบ','ทดสอบ','1',NULL,NULL,'ผู้พิพากษา','กลุ่มงานอำนวยการ',NULL,'1123344','123','12311','10','2023-12-14 09:54:13','2024-05-16 06:58:08','1','user_1_1778175011.png');
INSERT INTO `profile` VALUES('1680162049',NULL,'นาง','just1','just1','999',NULL,NULL,'ผู้พิพากษา','ผู้พิพากษา',NULL,'1111',NULL,NULL,'10','2025-09-02 11:14:34','2025-09-02 11:14:34','1',NULL);
INSERT INTO `profile` VALUES('1680162050',NULL,'นาง','just2','just2','999',NULL,NULL,'ผู้พิพากษา','ผู้พิพากษา',NULL,'2222',NULL,NULL,'10','2025-09-02 11:15:07','2025-09-02 11:15:07','2',NULL);
INSERT INTO `profile` VALUES('1680162051',NULL,'นางสาว','just3','just3','999',NULL,NULL,'ผู้พิพากษา','ผู้พิพากษา',NULL,'3333','','','10','2025-09-02 11:15:44','2025-09-02 11:15:44','3',NULL);
INSERT INTO `profile` VALUES('1680162052',NULL,'นาย','user1','user1','999',NULL,NULL,'เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ','ผู้อำนวยการฯ',NULL,'0001',NULL,NULL,'10','2025-09-02 11:17:28','2025-09-02 11:28:48','201',NULL);
INSERT INTO `profile` VALUES('1680162053',NULL,'นาย','user2','user2','999',NULL,NULL,'เจ้าพนักงานศาลยุติธรรม','กลุ่มช่วยอำนวยการ',NULL,'0002','','','10','2025-09-02 11:25:08','2025-09-02 11:28:38','202',NULL);
INSERT INTO `profile` VALUES('1680162054',NULL,'นาย','user3','user3','999',NULL,NULL,'เจ้าหน้าที่ศาลยุติธรรม','กลุ่มงานคดี',NULL,'0003','','','10','2025-09-02 11:27:09','2025-09-02 11:28:15','203',NULL);
INSERT INTO `profile` VALUES('1680162055',NULL,'นางสาว','user4','user4','999',NULL,NULL,'เจ้าพนักงานศาลยุติธรรมปฏิบัติการ','กลุ่มงานคดี',NULL,'0004',NULL,NULL,'10','2025-09-02 11:29:32','2025-09-02 11:29:32','204',NULL);
INSERT INTO `profile` VALUES('1680162056',NULL,'นางสาว','user5','user5','999',NULL,NULL,'นักวิชาการคอมพิวเตอร์','ส่วนเทคโนโลยีสารสนเทศ',NULL,'0005',NULL,NULL,'10','2025-09-02 11:30:11','2025-09-02 11:30:11','1',NULL);
INSERT INTO `profile` VALUES('865368c8-440a-409d-bce9-1ffa28f072b0',NULL,'ฟฟฟ','sss','sss','999',NULL,NULL,NULL,NULL,NULL,'ss','','','10',NULL,NULL,'0',NULL);
INSERT INTO `profile` VALUES('e9d85622-0fcc-43c3-996a-865d6cf73f16',NULL,'นาย','ss','aa','999',NULL,NULL,'นักวิชาการคอมพิวเตอร์','ผู้พิพากษาสมทบ',NULL,'','','','10',NULL,NULL,'0',NULL);
INSERT INTO `profile` VALUES('0fe42647-50be-422a-b75b-25a792a9e79a',NULL,'สิบตำรวจเอก','d','f','2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'10',NULL,NULL,'0',NULL);
INSERT INTO `profile` VALUES('4dcc6854-a3b2-4fae-9617-bc8fd9158255',NULL,'นาย','dd',NULL,'201',NULL,NULL,'ผู้พิพากษา','กลุ่มงานอำนวยการ',NULL,'dd',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('59cdd87f-420b-4e4d-ad59-0d65b41da9bb',NULL,NULL,'u1','u1','202',NULL,NULL,'พนักงานคอมพิวเตอร์','ผู้อำนวยการฯ',NULL,NULL,NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('0aa9692e-1f46-4ade-b57d-93ba0856bbe5',NULL,'ฟฟฟ','j01','j01','2',NULL,NULL,'ผู้พิพากษาสมทบ','ผู้พิพากษาสมทบ',NULL,'ww',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('4df27804-e670-48a6-b66a-e279631147a9',NULL,'นาย','j02','j02','3',NULL,NULL,'เจ้าพนักงานศาลยุติธรรมปฏิบัติการ','กลุ่มงานช่วยพิจารณาคดี',NULL,'j02',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('ee9222da-25c3-484a-9772-7046a51b0402',NULL,'นาย','พเยาว์','สนพลาย','6',NULL,NULL,'เจ้าพนักงานศาลยุติธรรม',NULL,NULL,'0623984242',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('66ee9c73-24c7-4109-b0f2-9562b9a7d24e',NULL,'นาย','j1','j1','7',NULL,NULL,'ผู้พิพากษา',NULL,NULL,'j1',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('1fc3e2ea-d925-461f-be7d-471efcd1b4ab',NULL,NULL,'j2','j2','9',NULL,NULL,'ผู้พิพากษา',NULL,NULL,'000',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('7e92bb9f-1031-4299-859b-7d063d86cbd8',NULL,NULL,'j3','j3','10',NULL,NULL,'ผู้พิพากษา',NULL,NULL,'j3',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('d1db3e5c-da4f-4409-b498-a574a8ba0cde',NULL,NULL,'u2','u2','5',NULL,NULL,'เจ้าพนักงานศาลยุติธรรม',NULL,NULL,'u2',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('3e575911-b13d-4e11-8a51-2da6484596ba',NULL,NULL,'u3','u3','8',NULL,NULL,'เจ้าพนักงานศาลยุติธรรม',NULL,NULL,'u3',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('a8d3f0ab-3b72-482a-bdf6-70d67f2ed585',NULL,'','s','s','11',NULL,NULL,'ผู้พิพากษา','',NULL,'s','','','10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('49f9dc25-b46f-4a14-ae58-dd0e72e159ea',NULL,'นาย','ชูเกียรติ','ภานุกรอุดม','2',NULL,NULL,'ผู้พิพากษาหัวหน้าศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์','ผู้พิพากษา',NULL,'06-5517-0682',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('a1deb96f-0f92-47ed-8161-b517b2986c28',NULL,'นาย','พิเชฐ','ศรมยุรา','3',NULL,NULL,'ผู้พิพากษาหัวหน้าคณะชั้นต้นในศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์','ผู้พิพากษา',NULL,'065-517-0477',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b',NULL,'นางสาว','พิจิตรา','สุทธิเกษม','4',NULL,NULL,'ผู้พิพากษา','ผู้พิพากษา',NULL,'090-658-8558',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('dd4c0ca9-05f6-4862-9c27-dd631b87b3b7',NULL,'นางสาว','ศรีชมภู','อุ่นจิตรพันธ์','5',NULL,NULL,'ผู้อำนวยการฯ','',NULL,'08-4153-5635','','','10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('5d4a5a56-6417-4031-a854-007b334a7489',NULL,'นางสาว','วัชราวลี ','ฝ่ายเดช','6',NULL,NULL,'นิติกรชำนาญการพิเศษ','กลุ่มงานปริการประชาชนและประชาสัมพันธ์',NULL,'06-1449-4653',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('2e35495c-8e40-4f93-a252-154613974929',NULL,'นาง','สายฝน ','กุญชร ณ อยุธยา','7',NULL,NULL,'นิติกรชำนาญการพิเศษ','กลุ่มงานไกล่เกลี่ยและประนอมข้อพิพาท',NULL,' 08-4360-9069',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('c21c2300-074b-4b72-bf08-d90e0bc0ae63',NULL,'นาง','อุไร ','เทพบัณฑิต','8',NULL,NULL,'เจ้าพนักงานศาลยุติธรรม','กลุ่มงานคดี',NULL,'08-0011-6464',NULL,NULL,'10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('7c7d835e-fbb5-4df9-9c3a-a885976c7d98',NULL,'นาย','เอกชวัทธน์ ','สาระเกตุ','9',NULL,NULL,'เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ','กลุ่มงานช่วยพิจารณาคดี',NULL,'08-6752-6064','','','10',NULL,NULL,'1',NULL);
INSERT INTO `profile` VALUES('043c8099-c140-4ca6-9f2b-29d90b0460e1',NULL,'นางสาว','ดลยา','เยาวหลี','10',NULL,NULL,'เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ','กลุ่มงานช่วยอำนวยการ',NULL,'08-9521-3842',NULL,NULL,'10',NULL,NULL,'1',NULL);


DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `system_name` varchar(255) DEFAULT 'ระบบบริหารจัดการเวรนอกเวลาทำการ',
  `allow_swap` tinyint(1) DEFAULT '1' COMMENT '1 = อนุญาตให้แลกเวร, 0 = ปิดระบบแลกเวร',
  `advance_swap_days` int DEFAULT '3' COMMENT 'ต้องขอแลกเวรล่วงหน้ากี่วัน',
  `maintenance_mode` tinyint(1) DEFAULT '0' COMMENT '1 = ปิดปรับปรุงระบบ',
  `allow_retroactive_swap` tinyint(1) DEFAULT '0' COMMENT '1 = เปลี่ยนย้อนหลังได้, 0 = ไม่ได้',
  `check_24h_consecutive` tinyint(1) DEFAULT '1' COMMENT '1 = เปิดระบบแจ้งเตือนเวร 24 ชม.',
  `user_options` text COMMENT 'เก็บ JSON ของ คำนำหน้า, ตำแหน่ง, กลุ่มงาน',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `system_settings` VALUES('1','ระบบบริหารจัดการเวรนอกเวลาทำการ','1','0','0','0','1','{\"prefixes\":[\"นาย\",\"นาง\",\"นางสาว\"],\"positions\":[\"ผู้พิพากษา\",\"ผู้อำนวยการฯ\",\"นิติกร\",\"เจ้าพนักงานศาลยุติธรรม\",\"ผู้พิพากษาหัวหน้าศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์\",\"ผู้พิพากษาหัวหน้าคณะชั้นต้นในศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์\",\"เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ\",\"นิติกรชำนาญการพิเศษ\"],\"departments\":[\"ผู้พิพากษา\",\"กลุ่มงานปริการประชาชนและประชาสัมพันธ์\",\"กลุ่มงานไกล่เกลี่ยและประนอมข้อพิพาท\",\"กลุ่มงานคดี\",\"กลุ่มงานช่วยพิจารณาคดี\",\"กลุ่มงานช่วยอำนวยการ\",\"กลุ่มงานเจ้าพนักงานตำรวจศาล\",\"กลุ่มงานคลัง\"]}');


DROP TABLE IF EXISTS `telegram_notify_times`;
CREATE TABLE `telegram_notify_times` (
  `id` int NOT NULL AUTO_INCREMENT,
  `send_time` time NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `notify_day` tinyint(1) DEFAULT '0' COMMENT '0: Today, 1: Tomorrow',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `telegram_notify_times` VALUES('42','22:03:00','1','1');


DROP TABLE IF EXISTS `telegram_settings`;
CREATE TABLE `telegram_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bot_token` varchar(255) NOT NULL,
  `chat_id` varchar(100) NOT NULL,
  `notify_confirmed` tinyint(1) DEFAULT '1',
  `notify_change_request` tinyint(1) DEFAULT '1',
  `notify_approval` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `telegram_settings` VALUES('1','xxxx','xxxx','1','1','1');


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `username` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `auth_key` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `password_reset_token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `role` smallint NOT NULL DEFAULT '1',
  `status` smallint NOT NULL DEFAULT '10',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `password_reset_token` (`password_reset_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `user` VALUES('043c8099-c140-4ca6-9f2b-29d90b0460e1','4565',NULL,'$2y$10$JZRuYSnnX.h3FyeVwNy8BuKUx/.QSP5ugUKiRyfJFCOhutFxjoc26',NULL,NULL,'1','10','0','2026-05-10 00:54:52',NULL);
INSERT INTO `user` VALUES('1','admin','19410b505e5993f1fe3a202f02e2ab28','$2y$10$cagJUbgpXBGOcPaLgqXauep2t4utkj0MrtQOorkjPukwGLkLARXkK',NULL,NULL,'9','10','0','2023-12-14 09:54:13','2024-04-27 18:13:27');
INSERT INTO `user` VALUES('1fc3e2ea-d925-461f-be7d-471efcd1b4ab','j2',NULL,'$2y$10$DmazDZNRJT/U1CLy9fTgrevvTSLGY0H5s1B.yHc2J43Js4FKbHvTa',NULL,NULL,'1','10','1','2026-05-06 20:28:54',NULL);
INSERT INTO `user` VALUES('2e35495c-8e40-4f93-a252-154613974929','1234',NULL,'$2y$10$3IKQbhnH5T0G19Ns5IIPxON9z0WHSOlY5DcA.8wTIRlSzOGnVc3OG',NULL,NULL,'1','10','0','2026-05-10 00:44:28',NULL);
INSERT INTO `user` VALUES('3e575911-b13d-4e11-8a51-2da6484596ba','u3',NULL,'$2y$10$6dpN9g40.iHY6TySallCXuZw8ZKbfp3TlQksAUYKdh8czboFWn2/O',NULL,NULL,'1','10','1','2026-05-06 20:30:56',NULL);
INSERT INTO `user` VALUES('49f9dc25-b46f-4a14-ae58-dd0e72e159ea','j6901','9480d2da990fcc0307403493bea4033c','$2y$10$yFWY4XsOANcHv4I7vmFhZOqGpXuXkEwRLP3MdQGFfNh6pCRqnJrIe',NULL,NULL,'1','10','0','2026-05-10 00:36:14',NULL);
INSERT INTO `user` VALUES('5d4a5a56-6417-4031-a854-007b334a7489','3770400152825',NULL,'$2y$10$sMOVkVTB4x61hEOGx8VxwuFnBhAo3vJF8jGETPCvBQuyTb8Dr.eny',NULL,NULL,'1','10','0','2026-05-10 00:43:20',NULL);
INSERT INTO `user` VALUES('66ee9c73-24c7-4109-b0f2-9562b9a7d24e','j1',NULL,'$2y$10$ItmlgrRzwQGqaExyq0mHHu7oRN1bsiXtylWMxLV4GWdEvaKzfBFyu',NULL,NULL,'1','10','1','2026-05-06 20:28:21',NULL);
INSERT INTO `user` VALUES('7c7d835e-fbb5-4df9-9c3a-a885976c7d98','3770700042944',NULL,'$2y$10$2joAxMz0uKrw26ePVZTtOuS8X.ITww7lKkQYgkcIHbUlVUFG5.4xG',NULL,NULL,'1','10','0','2026-05-10 00:53:49',NULL);
INSERT INTO `user` VALUES('7e92bb9f-1031-4299-859b-7d063d86cbd8','j3',NULL,'$2y$10$zeBijYGNyL5537oKdSDZQuV6isbAiI7rA3HnnFXSDLP9KqtkEsVGW',NULL,NULL,'1','10','1','2026-05-06 20:29:26',NULL);
INSERT INTO `user` VALUES('a1deb96f-0f92-47ed-8161-b517b2986c28','j6802',NULL,'$2y$10$ygGnNDf9GYFENqOprimY1O8R0IYjHKs06iATzIKXtT.4JkPwS7usu',NULL,NULL,'1','10','0','2026-05-10 00:38:09',NULL);
INSERT INTO `user` VALUES('a8d3f0ab-3b72-482a-bdf6-70d67f2ed585','as',NULL,'$2y$10$Kuoc9Kw470yUi2/d0SH6quuymZ2BUdVeQPc2yDVziaUtkjBEU6rzq',NULL,NULL,'1','10','1','2026-05-10 00:03:30',NULL);
INSERT INTO `user` VALUES('b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','j6803','e10054250badcf2e33aca39c8638e642','$2y$10$KZ0c8YDOl6D9js6OtMFN9Oa4U23jBlcIHnbgRdgYEckmMAj1QTejW',NULL,NULL,'1','10','0','2026-05-10 00:39:53',NULL);
INSERT INTO `user` VALUES('c21c2300-074b-4b72-bf08-d90e0bc0ae63','6464',NULL,'$2y$10$T7GPp01yrk8apt8VFKfz.ObPOLlDc9o84hd4wfNGpVuFIBa3Gtt16',NULL,NULL,'1','10','0','2026-05-10 00:45:18',NULL);
INSERT INTO `user` VALUES('d1db3e5c-da4f-4409-b498-a574a8ba0cde','u2',NULL,'$2y$10$RI7sA2hniKXT8g.12V50QuCaWLZml0HJIJn3G41jRwdZ4WAA2B7Mi',NULL,NULL,'1','10','1','2026-05-06 20:30:23',NULL);
INSERT INTO `user` VALUES('dd4c0ca9-05f6-4862-9c27-dd631b87b3b7','3170300244103',NULL,'$2y$10$pOZQY5nHIMsqo8oG7YmRDOW6/gcpOCoR54qwIISQKGX42sF04I0rq',NULL,NULL,'1','10','0','2026-05-10 00:42:29',NULL);
INSERT INTO `user` VALUES('ee9222da-25c3-484a-9772-7046a51b0402','u1',NULL,'$2y$10$gsbgDRQ0ItXdapYr2TMs9uNa5b9LtldJHkDecl4NpKxWm4jUemYHa',NULL,NULL,'1','10','1','2026-05-06 20:26:39',NULL);


DROP TABLE IF EXISTS `ven_change`;
CREATE TABLE `ven_change` (
  `id` int NOT NULL AUTO_INCREMENT,
  `change_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `s1_id` int NOT NULL COMMENT 'รหัสตารางเวรของผู้ขอ (ven_schedule.id)',
  `user1_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'รหัสผู้ขอแลก',
  `s2_id` int DEFAULT NULL COMMENT 'รหัสตารางเวรของเพื่อน (ven_schedule.id)',
  `user2_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'รหัสเพื่อนที่ถูกขอแลก',
  `status` int DEFAULT '0' COMMENT '0=รออนุมัติ, 1=ยอมรับแล้ว, 2=ปฏิเสธ',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_swap` tinyint(1) DEFAULT '0' COMMENT '0=ยกเวรให้ปกติ, 1=สลับเวรกัน',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ven_change` VALUES('40','CH-202605-9047','1067','1',NULL,'3e575911-b13d-4e11-8a51-2da6484596ba','1','2026-05-06 20:59:24','0');
INSERT INTO `ven_change` VALUES('41','CH-202605-6840','1071','1','1067','3e575911-b13d-4e11-8a51-2da6484596ba','1','2026-05-06 21:06:22','1');
INSERT INTO `ven_change` VALUES('42','CH-202605-8545','1079','1',NULL,'d1db3e5c-da4f-4409-b498-a574a8ba0cde','1','2026-05-06 21:10:11','0');
INSERT INTO `ven_change` VALUES('43','CH-202605-6882','1067','1','1066','3e575911-b13d-4e11-8a51-2da6484596ba','1','2026-05-06 21:16:59','1');
INSERT INTO `ven_change` VALUES('45','CH-202605-5482','1066','1',NULL,'ee9222da-25c3-484a-9772-7046a51b0402','1','2026-05-06 21:44:09','0');
INSERT INTO `ven_change` VALUES('54','CH-202605-9336','1086','1','1085','d1db3e5c-da4f-4409-b498-a574a8ba0cde','1','2026-05-06 23:11:42','1');
INSERT INTO `ven_change` VALUES('58','CH-202605-1715','1111','1',NULL,'ee9222da-25c3-484a-9772-7046a51b0402','1','2026-05-07 10:45:43','0');
INSERT INTO `ven_change` VALUES('60','CH-202605-1387','1119','1',NULL,'ee9222da-25c3-484a-9772-7046a51b0402','0','2026-05-08 00:18:29','0');
INSERT INTO `ven_change` VALUES('62','CH-202605-4176','1009','1',NULL,'ee9222da-25c3-484a-9772-7046a51b0402','1','2026-05-09 23:03:21','0');
INSERT INTO `ven_change` VALUES('64','CH-202605-6769','1087','1','1073','d1db3e5c-da4f-4409-b498-a574a8ba0cde','0','2026-05-09 23:38:25','1');
INSERT INTO `ven_change` VALUES('67','CH-202605-1114','1017','1','1021','ee9222da-25c3-484a-9772-7046a51b0402','0','2026-05-09 23:41:21','1');


DROP TABLE IF EXISTS `ven_com`;
CREATE TABLE `ven_com` (
  `id` int NOT NULL AUTO_INCREMENT,
  `com_num` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `com_date` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ven_month` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ven_name_id` int DEFAULT NULL,
  `ven_com_days` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `file` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ref` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1772680485 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `ven_com` VALUES('1772680478','33/2569','2026-05-06','2026-05','1','24','2,3,4,9,10,13,16,17,23,24,30,31',NULL,NULL,NULL,NULL);
INSERT INTO `ven_com` VALUES('1772680479','34/2569','2026-05-06','2026-05','0','27','1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31',NULL,NULL,NULL,NULL);
INSERT INTO `ven_com` VALUES('1772680480','xc','2026-05-07','2026-05','0','35','1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31',NULL,NULL,NULL,NULL);
INSERT INTO `ven_com` VALUES('1772680482','555','2026-05-07','2026-06','0','24','1,2,3,4,5',NULL,NULL,NULL,NULL);
INSERT INTO `ven_com` VALUES('1772680483','222','2026-05-07','2026-06','0','36','2,3,4,5,7',NULL,NULL,NULL,NULL);
INSERT INTO `ven_com` VALUES('1772680484','666','2026-05-07','2026-05','0','36','1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31',NULL,NULL,NULL,NULL);


DROP TABLE IF EXISTS `ven_name`;
CREATE TABLE `ven_name` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_full` text,
  `dn` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `word` varchar(255) DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=ปกติ, 0=ลบ (Soft Delete)',
  `google_calendar_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb3;

INSERT INTO `ven_name` VALUES('24','เวรเปิดทำการศาลนอกเวลาราชการ (เวรตรวจสอบการจับ)','ให้ข้าราชการฝ่ายตุลาการศาลยุติธรรม พนักงานราชการศาลยุติธรรม และลูกจ้างในศาลเยาวชนและครอบครัวกลาง อยู่ปฏิบัติหน้าที่โดยเปิดทำการศาลนอกเวลาราชการในวันหยุดราชการ','กลางวัน(08.30-16.30)','ven_report_24_1756973949.docx','0','1','ddddxx');
INSERT INTO `ven_name` VALUES('27','หมายจับ - ค้น (กลางคืน 16.30-08.30 น.)','ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 16.30 – 08.30 นาฬิกา ','กลางคืน(16.30-08.30)',NULL,'2','1',NULL);
INSERT INTO `ven_name` VALUES('28','เวรปฏิบัติงานในวันหยุดราชการตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 8.30-16.30 น.','ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 08.30 – 16.30 นาฬิกา ตามโครงการเปิดทำการศาลนอกเวลาราชการเพื่อเร่งรัดการพิจารณาพิพากษาคดี หรือเพื่ออำนวยความสะดวกแก่ประชาชน ประจำปีงบประมาณ พ.ศ. ๒๕๖๗ ','กลางวัน(08.30-16.30)',NULL,'1','0',NULL);
INSERT INTO `ven_name` VALUES('29','เวรปฏิบัติงานนอกเวลาราชการในวันทำการปกติตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 16.30-8.30 น.','ๆๆ','กลางคืน(16.30-08.30)',NULL,'4','0',NULL);
INSERT INTO `ven_name` VALUES('32','aaaaqq','qqqqqq','nightCourt(16.30-20.00)',NULL,'6','0',NULL);
INSERT INTO `ven_name` VALUES('33','ฟหดด','ผผปก','กลางคืน(16.30-08.30)',NULL,'5','0',NULL);
INSERT INTO `ven_name` VALUES('34','aa','aa','กลางวัน(08.30-16.30)',NULL,'3','0',NULL);
INSERT INTO `ven_name` VALUES('35','ผู้ตรวจ','ตรวจ','กลางคืน(16.30-08.30)',NULL,'3','1',NULL);
INSERT INTO `ven_name` VALUES('36','เวรเร่งรัด','เวรเร่งรัด','nightCourt(16.30-20.00)',NULL,'4','0',NULL);


DROP TABLE IF EXISTS `ven_name_sub`;
CREATE TABLE `ven_name_sub` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `ven_name_id` int NOT NULL,
  `price` int DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=ปกติ, 0=ลบ (Soft Delete)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb3;

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
INSERT INTO `ven_name_sub` VALUES('135','z^hrld','31','160','Magenta',NULL,'1');
INSERT INTO `ven_name_sub` VALUES('138','aa','29','0','BlueViolet','2','0');
INSERT INTO `ven_name_sub` VALUES('139','www','32','1000','Brown','1','0');
INSERT INTO `ven_name_sub` VALUES('140','111','32','0','#198754','2','0');
INSERT INTO `ven_name_sub` VALUES('141','หกกด','33','0','Magenta','1','0');
INSERT INTO `ven_name_sub` VALUES('142','จนท','29','1200','BlueViolet','2','0');
INSERT INTO `ven_name_sub` VALUES('143','sss','34','150','BlueViolet','1','0');
INSERT INTO `ven_name_sub` VALUES('144','ผู้ตรวจ','35','0','DarkOrange','1','1');
INSERT INTO `ven_name_sub` VALUES('145','ผู้พิพากษา','36','2000','Magenta','1','0');
INSERT INTO `ven_name_sub` VALUES('146','จนท','36','1500','Teal','2','0');


DROP TABLE IF EXISTS `ven_schedule`;
CREATE TABLE `ven_schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ven_date` date NOT NULL COMMENT 'วันที่ปฏิบัติหน้าที่ (YYYY-MM-DD)',
  `ven_com_id` int NOT NULL COMMENT 'อ้างอิงรหัสคำสั่งจากตาราง ven_com',
  `ven_name_sub_id` int NOT NULL COMMENT 'อ้างอิงรหัสหน้าที่ย่อยจากตาราง ven_name_sub',
  `user_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'อ้างอิงรหัสพนักงานจากตาราง user',
  `status` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `google_event_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ven_date` (`ven_date`),
  KEY `idx_ven_com` (`ven_com_id`),
  KEY `idx_ven_sub` (`ven_name_sub_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1263 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ven_schedule` VALUES('1189','2026-05-09','1772680478','130','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 00:59:01',NULL);
INSERT INTO `ven_schedule` VALUES('1198','2026-05-02','1772680478','117','dd4c0ca9-05f6-4862-9c27-dd631b87b3b7','1','2026-05-10 00:59:12',NULL);
INSERT INTO `ven_schedule` VALUES('1199','2026-05-03','1772680478','117','2e35495c-8e40-4f93-a252-154613974929','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1200','2026-05-04','1772680478','117','c21c2300-074b-4b72-bf08-d90e0bc0ae63','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1201','2026-05-09','1772680478','117','5d4a5a56-6417-4031-a854-007b334a7489','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1202','2026-05-10','1772680478','117','7c7d835e-fbb5-4df9-9c3a-a885976c7d98','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1203','2026-05-13','1772680478','117','043c8099-c140-4ca6-9f2b-29d90b0460e1','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1204','2026-05-16','1772680478','117','dd4c0ca9-05f6-4862-9c27-dd631b87b3b7','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1205','2026-05-17','1772680478','117','2e35495c-8e40-4f93-a252-154613974929','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1206','2026-05-23','1772680478','117','c21c2300-074b-4b72-bf08-d90e0bc0ae63','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1207','2026-05-24','1772680478','117','5d4a5a56-6417-4031-a854-007b334a7489','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1208','2026-05-30','1772680478','117','7c7d835e-fbb5-4df9-9c3a-a885976c7d98','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1209','2026-05-31','1772680478','117','043c8099-c140-4ca6-9f2b-29d90b0460e1','1','2026-05-10 00:59:13',NULL);
INSERT INTO `ven_schedule` VALUES('1210','2026-05-03','1772680478','130','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:03:59',NULL);
INSERT INTO `ven_schedule` VALUES('1211','2026-05-04','1772680478','130','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:04:10',NULL);
INSERT INTO `ven_schedule` VALUES('1212','2026-05-10','1772680478','130','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:04:34',NULL);
INSERT INTO `ven_schedule` VALUES('1214','2026-05-13','1772680478','130','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:05:00',NULL);
INSERT INTO `ven_schedule` VALUES('1216','2026-05-16','1772680478','130','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:05:11',NULL);
INSERT INTO `ven_schedule` VALUES('1217','2026-05-17','1772680478','130','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:05:30',NULL);
INSERT INTO `ven_schedule` VALUES('1220','2026-05-02','1772680478','130','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:06:07',NULL);
INSERT INTO `ven_schedule` VALUES('1225','2026-05-23','1772680478','130','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:07:37',NULL);
INSERT INTO `ven_schedule` VALUES('1226','2026-05-24','1772680478','130','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:07:39',NULL);
INSERT INTO `ven_schedule` VALUES('1227','2026-05-30','1772680478','130','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:08:04',NULL);
INSERT INTO `ven_schedule` VALUES('1228','2026-05-31','1772680478','130','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:08:07',NULL);
INSERT INTO `ven_schedule` VALUES('1229','2026-05-02','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:11:05',NULL);
INSERT INTO `ven_schedule` VALUES('1230','2026-05-03','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:11:08',NULL);
INSERT INTO `ven_schedule` VALUES('1231','2026-05-04','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:11:11',NULL);
INSERT INTO `ven_schedule` VALUES('1232','2026-05-05','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:11:13',NULL);
INSERT INTO `ven_schedule` VALUES('1233','2026-05-06','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:11:14',NULL);
INSERT INTO `ven_schedule` VALUES('1234','2026-05-07','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:11:16',NULL);
INSERT INTO `ven_schedule` VALUES('1235','2026-05-08','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:11:19',NULL);
INSERT INTO `ven_schedule` VALUES('1236','2026-05-09','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:11:35',NULL);
INSERT INTO `ven_schedule` VALUES('1237','2026-05-10','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:11:38',NULL);
INSERT INTO `ven_schedule` VALUES('1238','2026-05-11','1772680479','113','a1deb96f-0f92-47ed-8161-b517b2986c28','1','2026-05-10 01:12:06',NULL);
INSERT INTO `ven_schedule` VALUES('1239','2026-05-12','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:12:08',NULL);
INSERT INTO `ven_schedule` VALUES('1240','2026-05-13','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:12:17',NULL);
INSERT INTO `ven_schedule` VALUES('1241','2026-05-14','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:12:20',NULL);
INSERT INTO `ven_schedule` VALUES('1242','2026-05-15','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:12:31',NULL);
INSERT INTO `ven_schedule` VALUES('1243','2026-05-16','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:12:38',NULL);
INSERT INTO `ven_schedule` VALUES('1244','2026-05-17','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:12:42',NULL);
INSERT INTO `ven_schedule` VALUES('1245','2026-05-18','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:12:50',NULL);
INSERT INTO `ven_schedule` VALUES('1246','2026-05-19','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:12:52',NULL);
INSERT INTO `ven_schedule` VALUES('1247','2026-05-20','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:12:54',NULL);
INSERT INTO `ven_schedule` VALUES('1248','2026-05-21','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:13:12',NULL);
INSERT INTO `ven_schedule` VALUES('1249','2026-05-22','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:13:14',NULL);
INSERT INTO `ven_schedule` VALUES('1250','2026-05-23','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:13:34',NULL);
INSERT INTO `ven_schedule` VALUES('1251','2026-05-24','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:13:36',NULL);
INSERT INTO `ven_schedule` VALUES('1252','2026-05-29','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:16:31',NULL);
INSERT INTO `ven_schedule` VALUES('1253','2026-05-28','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:16:40',NULL);
INSERT INTO `ven_schedule` VALUES('1254','2026-05-27','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:16:47',NULL);
INSERT INTO `ven_schedule` VALUES('1255','2026-05-26','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:16:55',NULL);
INSERT INTO `ven_schedule` VALUES('1256','2026-05-25','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:17:04',NULL);
INSERT INTO `ven_schedule` VALUES('1259','2026-05-30','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:18:43',NULL);
INSERT INTO `ven_schedule` VALUES('1260','2026-05-31','1772680479','113','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b','1','2026-05-10 01:18:46',NULL);
INSERT INTO `ven_schedule` VALUES('1262','2026-05-01','1772680479','113','49f9dc25-b46f-4a14-ae58-dd0e72e159ea','1','2026-05-10 01:20:27',NULL);


DROP TABLE IF EXISTS `ven_time`;
CREATE TABLE `ven_time` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name_th` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อเรียก เช่น กลางวัน',
  `time_period` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ช่วงเวลา เช่น 08.30-16.30',
  `srt` int NOT NULL DEFAULT '99' COMMENT 'ลำดับการแสดงผล',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ven_time` VALUES('1','กลางวัน','08.30-16.30','1');
INSERT INTO `ven_time` VALUES('2','กลางคืน','16.30-08.30','2');
INSERT INTO `ven_time` VALUES('3','nightCourt','16.30-20.00','3');


DROP TABLE IF EXISTS `ven_user`;
CREATE TABLE `ven_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `order_num` int DEFAULT NULL,
  `ven_name_sub_id` int DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999' COMMENT 'ลำดับคิวการเข้าเวร',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=828 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

INSERT INTO `ven_user` VALUES('793','66ee9c73-24c7-4109-b0f2-9562b9a7d24e',NULL,'130',NULL,NULL,'2');
INSERT INTO `ven_user` VALUES('794','1fc3e2ea-d925-461f-be7d-471efcd1b4ab',NULL,'130',NULL,NULL,'3');
INSERT INTO `ven_user` VALUES('795','7e92bb9f-1031-4299-859b-7d063d86cbd8',NULL,'130',NULL,NULL,'4');
INSERT INTO `ven_user` VALUES('796','ee9222da-25c3-484a-9772-7046a51b0402',NULL,'117',NULL,NULL,'1');
INSERT INTO `ven_user` VALUES('797','d1db3e5c-da4f-4409-b498-a574a8ba0cde',NULL,'117',NULL,NULL,'2');
INSERT INTO `ven_user` VALUES('798','3e575911-b13d-4e11-8a51-2da6484596ba',NULL,'117',NULL,NULL,'3');
INSERT INTO `ven_user` VALUES('799','66ee9c73-24c7-4109-b0f2-9562b9a7d24e',NULL,'113',NULL,NULL,'1');
INSERT INTO `ven_user` VALUES('800','1fc3e2ea-d925-461f-be7d-471efcd1b4ab',NULL,'113',NULL,NULL,'2');
INSERT INTO `ven_user` VALUES('801','7e92bb9f-1031-4299-859b-7d063d86cbd8',NULL,'113',NULL,NULL,'3');
INSERT INTO `ven_user` VALUES('802','ee9222da-25c3-484a-9772-7046a51b0402',NULL,'123',NULL,NULL,'1');
INSERT INTO `ven_user` VALUES('803','d1db3e5c-da4f-4409-b498-a574a8ba0cde',NULL,'123',NULL,NULL,'2');
INSERT INTO `ven_user` VALUES('804','3e575911-b13d-4e11-8a51-2da6484596ba',NULL,'123',NULL,NULL,'3');
INSERT INTO `ven_user` VALUES('805','1',NULL,'123',NULL,NULL,'4');
INSERT INTO `ven_user` VALUES('806','1',NULL,'118',NULL,NULL,'1');
INSERT INTO `ven_user` VALUES('807','ee9222da-25c3-484a-9772-7046a51b0402',NULL,'118',NULL,NULL,'2');
INSERT INTO `ven_user` VALUES('808','d1db3e5c-da4f-4409-b498-a574a8ba0cde',NULL,'118',NULL,NULL,'3');
INSERT INTO `ven_user` VALUES('809','3e575911-b13d-4e11-8a51-2da6484596ba',NULL,'118',NULL,NULL,'4');
INSERT INTO `ven_user` VALUES('810','1',NULL,'144',NULL,NULL,'1');
INSERT INTO `ven_user` VALUES('811','ee9222da-25c3-484a-9772-7046a51b0402',NULL,'144',NULL,NULL,'2');
INSERT INTO `ven_user` VALUES('812','1',NULL,'145',NULL,NULL,'1');
INSERT INTO `ven_user` VALUES('813','66ee9c73-24c7-4109-b0f2-9562b9a7d24e',NULL,'145',NULL,NULL,'2');
INSERT INTO `ven_user` VALUES('814','1fc3e2ea-d925-461f-be7d-471efcd1b4ab',NULL,'145',NULL,NULL,'3');
INSERT INTO `ven_user` VALUES('815','7e92bb9f-1031-4299-859b-7d063d86cbd8',NULL,'145',NULL,NULL,'4');
INSERT INTO `ven_user` VALUES('816','49f9dc25-b46f-4a14-ae58-dd0e72e159ea',NULL,'130',NULL,NULL,'5');
INSERT INTO `ven_user` VALUES('817','a1deb96f-0f92-47ed-8161-b517b2986c28',NULL,'130',NULL,NULL,'6');
INSERT INTO `ven_user` VALUES('818','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b',NULL,'130',NULL,NULL,'7');
INSERT INTO `ven_user` VALUES('819','dd4c0ca9-05f6-4862-9c27-dd631b87b3b7',NULL,'117',NULL,NULL,'4');
INSERT INTO `ven_user` VALUES('820','2e35495c-8e40-4f93-a252-154613974929',NULL,'117',NULL,NULL,'5');
INSERT INTO `ven_user` VALUES('821','c21c2300-074b-4b72-bf08-d90e0bc0ae63',NULL,'117',NULL,NULL,'6');
INSERT INTO `ven_user` VALUES('822','5d4a5a56-6417-4031-a854-007b334a7489',NULL,'117',NULL,NULL,'7');
INSERT INTO `ven_user` VALUES('823','7c7d835e-fbb5-4df9-9c3a-a885976c7d98',NULL,'117',NULL,NULL,'8');
INSERT INTO `ven_user` VALUES('824','043c8099-c140-4ca6-9f2b-29d90b0460e1',NULL,'117',NULL,NULL,'9');
INSERT INTO `ven_user` VALUES('825','49f9dc25-b46f-4a14-ae58-dd0e72e159ea',NULL,'113',NULL,NULL,'4');
INSERT INTO `ven_user` VALUES('826','a1deb96f-0f92-47ed-8161-b517b2986c28',NULL,'113',NULL,NULL,'5');
INSERT INTO `ven_user` VALUES('827','b7740ac1-0165-4ef2-af28-ca5dd9f3bd6b',NULL,'113',NULL,NULL,'6');

