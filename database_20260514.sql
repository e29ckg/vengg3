-- phpMyAdmin SQL Dump
-- version 5.2.3
-- Host: db
-- Generation Time: May 28, 2026 at 04:43 AM
-- Server version: 8.0.43
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vengg_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `agency_settings`
--

DROP TABLE IF EXISTS `agency_settings`;
CREATE TABLE `agency_settings` (
  `id` int NOT NULL,
  `agency_name` varchar(255) NOT NULL COMMENT 'ชื่อหน่วยงาน/ศาล',
  `directors` text COMMENT 'ข้อมูลผู้บริหาร (ตัวจริงและตัวสำรอง JSON)',
  `admins` text COMMENT 'ข้อมูลผู้อำนวยการ (ตัวจริงและตัวสำรอง JSON)',
  `finances` text COMMENT 'ข้อมูลการเงิน (ตัวจริงและตัวสำรอง JSON)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `agency_settings` (`id`, `agency_name`, `directors`, `admins`, `finances`) VALUES
(1, 'ศาล...', '[{\"name\":\"นาย...\",\"position\":\"ผู้พิพากษาหัวหน้าศาล...\",\"is_active\":true},{\"name\":\"นาย...\",\"position\":\"ผู้พิพากษาหัวหน้าคณะชั้นต้... ทำการแทน ผู้พิพากษาหัวหน้าศาล...\",\"is_active\":true}]', '[{\"name\":\"นางสาว...\",\"position\":\"ผู้อำนวยการสำนัก...\",\"is_active\":true},{\"name\":\"นางสาว...\",\"position\":\"นิติกรชำนาญการพิเศษรักษาราชการแทน ผู้อำนวยการสำนัก...\",\"is_active\":false}]', '[{\"name\":\"นางสาว...\",\"position\":\"นักวิชาการเงินและบัญชี...\",\"is_active\":true}]');

-- --------------------------------------------------------

--
-- Table structure for table `google_service_settings`
--

DROP TABLE IF EXISTS `google_service_settings`;
CREATE TABLE `google_service_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `google_service_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('google_calendar_id', '', '2026-05-18 06:34:05'),
('google_service_account', '', '2026-05-18 06:33:30');

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

DROP TABLE IF EXISTS `profile`;
CREATE TABLE `profile` (
  `user_id` varchar(255) DEFAULT NULL,
  `id_card` varchar(255) DEFAULT NULL,
  `prefix_name` varchar(25) DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999',
  `birthday` date DEFAULT NULL,
  `bloodtype` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `bank_comment` varchar(200) DEFAULT NULL,
  `status` smallint DEFAULT '10',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `st` smallint DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `profile` (`user_id`, `id_card`, `prefix_name`, `first_name`, `last_name`, `srt`, `birthday`, `bloodtype`, `position`, `department`, `address`, `phone`, `bank_account`, `bank_comment`, `status`, `created_at`, `updated_at`, `st`, `avatar`) VALUES
('1', NULL, 'นาย', 'ผู้ดูแลระบ', 'ทดสอบ', 1, NULL, NULL, 'นักวิชาการคอมพิวเตอร์ปฏิบัติการ', 'กลุ่มงานช่วยอำนวยการ', NULL, '1123344', '123', '12311', 10, '2023-12-14 09:54:13', '2024-05-16 06:58:08', 1, 'user_1_1778175011.png');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int NOT NULL,
  `user_id` varchar(36) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'เช่น LOGIN, CREATE, UPDATE, DELETE',
  `module` varchar(50) NOT NULL COMMENT 'เช่น USER, SCHEDULE, SWAP, SETTING',
  `description` text NOT NULL,
  `old_data` json DEFAULT NULL,
  `new_data` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `system_name` varchar(255) DEFAULT 'ระบบบริหารจัดการเวรนอกเวลาทำการ',
  `allow_swap` tinyint(1) DEFAULT '1' COMMENT '1 = อนุญาตให้แลกเวร, 0 = ปิดระบบแลกเวร',
  `advance_swap_days` int DEFAULT '3' COMMENT 'ต้องขอแลกเวรล่วงหน้ากี่วัน',
  `maintenance_mode` tinyint(1) DEFAULT '0' COMMENT '1 = ปิดปรับปรุงระบบ',
  `allow_retroactive_swap` tinyint(1) DEFAULT '0' COMMENT '1 = เปลี่ยนย้อนหลังได้, 0 = ไม่ได้',
  `check_24h_consecutive` tinyint(1) DEFAULT '1' COMMENT '1 = เปิดระบบแจ้งเตือนเวร 24 ชม.',
  `user_options` text COMMENT 'เก็บ JSON ของ คำนำหน้า, ตำแหน่ง, กลุ่มงาน',
  `compact_schedule_view` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=แบบเดิม(2 บรรทัด), 1=แบบกะทัดรัด(บรรทัดเดียว)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`id`, `system_name`, `allow_swap`, `advance_swap_days`, `maintenance_mode`, `allow_retroactive_swap`, `check_24h_consecutive`, `user_options`, `compact_schedule_view`) VALUES
(1, 'ระบบบริหารจัดการเวรนอกเวลาทำการ', 1, 0, 0, 0, 1, '{\"prefixes\":[\"นาย\",\"นาง\",\"นางสาว\"],\"positions\":[\"ผู้พิพากษา\",\"นิติกร\",\"เจ้าพนักงานศาลยุติธรรม\",\"เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ\",\"นิติกรชำนาญการพิเศษ\",\"ผู้พิพากษาหัวหน้าคณะชั้นต้นในศาล...\",\"ผู้พิพากษาหัวหน้าศาล...\",\"ผู้อำนวยการสำนัก...\",\"นักจิตวิทยาชำนาญการ\",\"เจ้าพนักงานศาลยุติธรรมชำนาญการ\",\"เจ้าพนักงานศาลยุติธรรมปฏิบัติการ\",\"นักวิชาการเงินและบัญชีปฏิบัติการ\",\"นักจิตวิทยาปฏิบัติการ\",\"นักวิชาการพัสดุปฏิบัติการ\",\"เจ้าพนักงานตำรวจศาลปฏิบัติการ\",\"เจ้าหน้าที่ศาลยุติธรรมชำนาญงาน\",\"เจ้าพนักงานการเงินและบัญชีปฏิบัติงาน\",\"เจ้าหน้าที่ศาลยุติธรรมปฏิบัติงาน\",\"พนักงานขับรถยนต์\",\"นักวิชาการคอมพิวเตอร์ปฏิบัติการ\"],\"departments\":[\"ผู้พิพากษา\",\"กลุ่มงานปริการประชาชนและประชาสัมพันธ์\",\"กลุ่มงานไกล่เกลี่ยและประนอมข้อพิพาท\",\"กลุ่มงานคดี\",\"กลุ่มงานช่วยพิจารณาคดี\",\"กลุ่มงานช่วยอำนวยการ\",\"กลุ่มงานเจ้าพนักงานตำรวจศาล\",\"กลุ่มงานคลัง\"]}', 1);

-- --------------------------------------------------------

--
-- Table structure for table `telegram_notify_times`
--

DROP TABLE IF EXISTS `telegram_notify_times`;
CREATE TABLE `telegram_notify_times` (
  `id` int NOT NULL,
  `send_time` time NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `notify_day` tinyint(1) DEFAULT '0' COMMENT '0: Today, 1: Tomorrow'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `telegram_notify_times` (`id`, `send_time`, `status`, `notify_day`) VALUES
(48, '19:30:00', 1, 1),
(49, '07:00:00', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `telegram_settings`
--

DROP TABLE IF EXISTS `telegram_settings`;
CREATE TABLE `telegram_settings` (
  `id` int NOT NULL,
  `bot_token` varchar(255) NOT NULL,
  `chat_id` varchar(100) NOT NULL,
  `notify_confirmed` tinyint(1) DEFAULT '1',
  `notify_change_request` tinyint(1) DEFAULT '1',
  `notify_approval` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `telegram_settings` (`id`, `bot_token`, `chat_id`, `notify_confirmed`, `notify_change_request`, `notify_approval`) VALUES
(1, '', '', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` varchar(36) NOT NULL,
  `username` varchar(255) NOT NULL,
  `auth_key` varchar(32) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` smallint NOT NULL DEFAULT '1',
  `status` smallint NOT NULL DEFAULT '10',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `user` (`id`, `username`, `auth_key`, `password_hash`, `password_reset_token`, `email`, `role`, `status`, `is_deleted`, `created_at`, `updated_at`) VALUES
('1', 'admin', '4562f557e01d833ff58402663551226c', '$2y$10$cagJUbgpXBGOcPaLgqXauep2t4utkj0MrtQOorkjPukwGLkLARXkK', NULL, NULL, 9, 10, 0, '2023-12-14 09:54:13', '2024-04-27 18:13:27');

-- --------------------------------------------------------

--
-- Table structure for table `ven_change`
--

DROP TABLE IF EXISTS `ven_change`;
CREATE TABLE `ven_change` (
  `id` int NOT NULL,
  `change_no` varchar(20) DEFAULT NULL,
  `s1_id` int NOT NULL COMMENT 'รหัสตารางเวรของผู้ขอ (ven_schedule.id)',
  `user1_id` varchar(36) NOT NULL COMMENT 'รหัสผู้ขอแลก',
  `s2_id` int DEFAULT NULL COMMENT 'รหัสตารางเวรของเพื่อน (ven_schedule.id)',
  `user2_id` varchar(36) NOT NULL COMMENT 'รหัสเพื่อนที่ถูกขอแลก',
  `status` int DEFAULT '0' COMMENT '0=รออนุมัติ, 1=ยอมรับแล้ว, 2=ปฏิเสธ',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_swap` tinyint(1) DEFAULT '0' COMMENT '0=ยกเวรให้ปกติ, 1=สลับเวรกัน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ven_com`
--

DROP TABLE IF EXISTS `ven_com`;
CREATE TABLE `ven_com` (
  `id` int NOT NULL,
  `com_num` varchar(255) DEFAULT NULL,
  `com_date` varchar(255) DEFAULT NULL,
  `ven_month` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `ven_name_id` int DEFAULT NULL,
  `ven_com_days` varchar(255) NOT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `ref` varchar(255) DEFAULT NULL,
  `create_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ven_name`
--

DROP TABLE IF EXISTS `ven_name`;
CREATE TABLE `ven_name` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_full` text,
  `dn` varchar(255) DEFAULT NULL,
  `word` varchar(255) DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=ปกติ, 0=ลบ (Soft Delete)',
  `google_calendar_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ven_name` (`id`, `name`, `name_full`, `dn`, `word`, `srt`, `status`, `google_calendar_id`) VALUES
(24, 'เวรเปิดทำการศาลนอกเวลาราชการ (เวรตรวจสอบการจับ)', 'ให้ข้าราชการฝ่ายตุลาการศาลยุติธรรม พนักงานราชการศาลยุติธรรม และลูกจ้างในศาลเยาวชนและครอบครัวกลาง อยู่ปฏิบัติหน้าที่โดยเปิดทำการศาลนอกเวลาราชการในวันหยุดราชการ', 'กลางวัน(08.30-16.30)', 'ven_report_24_1756973949.docx', 0, 1, 'ddddxx'),
(27, 'หมายจับ - ค้น (กลางคืน 16.30-08.30 น.)', 'ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 16.30 – 08.30 นาฬิกา ', 'กลางคืน(16.30-08.30)', NULL, 2, 1, NULL),
(28, 'เวรปฏิบัติงานในวันหยุดราชการตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 8.30-16.30 น.', 'ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 08.30 – 16.30 นาฬิกา ตามโครงการเปิดทำการศาลนอกเวลาราชการเพื่อเร่งรัดการพิจารณาพิพากษาคดี หรือเพื่ออำนวยความสะดวกแก่ประชาชน ประจำปีงบประมาณ พ.ศ. ๒๕๖๗ ', 'กลางวัน(08.30-16.30)', NULL, 1, 0, NULL),
(36, 'เวรเร่งรัด', 'เวรเร่งรัด', 'nightCourt(16.30-20.00)', NULL, 4, 0, NULL),
(39, 'ผู้ตรวจ', 'ผู้ตรวจ', 'กลางคืน(16.30-08.30)', NULL, 3, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ven_name_sub`
--

DROP TABLE IF EXISTS `ven_name_sub`;
CREATE TABLE `ven_name_sub` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `ven_name_id` int NOT NULL,
  `price` int DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=ปกติ, 0=ลบ (Soft Delete)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ven_name_sub` (`id`, `name`, `ven_name_id`, `price`, `color`, `srt`, `status`) VALUES
(113, 'ผู้พิพากษา', 27, 1500, 'Brown', 1, 1),
(115, 'ผู้พิพากษา', 28, 3000, 'Brown', 1, 0),
(116, 'รับฟ้อง+ปชส ', 28, 1500, 'Brown', 2, 0),
(117, 'ผอ./แทน', 24, 1500, '#944cd6', 2, 1),
(118, 'จนท', 24, 1500, '#a86edd', 3, 1),
(119, 'งานหมาย', 24, 1500, 'BlueViolet', 4, 0),
(120, 'งานประชาสัมพันธ์', 24, 1500, 'BlueViolet', 5, 0),
(121, 'งานการเงิน', 24, 1500, 'BlueViolet', 6, 0),
(123, 'จนท', 27, 1200, '#b65454', 2, 1),
(124, 'การเงิน+ปล่อยตัวชั่วคราว', 27, 1000, 'Green', 2, 0),
(125, 'หน้าบัลลังก์', 27, 1000, 'Green', 3, 0),
(128, 'การเงิน+ปล่อยตัวชั่วคราว ', 28, 1500, 'Brown', 3, 0),
(129, 'หน้าบัลลังก์', 28, 1500, 'Brown', 4, 0),
(130, 'ผู้พิพากษา', 24, 3000, '#5774e5', 1, 1),
(133, 'ผู้พิพากษาสมทบ', 29, 1100, 'Magenta', 0, 0),
(148, 'ผู้ตรวจ', 39, 0, '#eadcb3', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ven_schedule`
--

DROP TABLE IF EXISTS `ven_schedule`;
CREATE TABLE `ven_schedule` (
  `id` int NOT NULL,
  `ven_date` date NOT NULL COMMENT 'วันที่ปฏิบัติหน้าที่ (YYYY-MM-DD)',
  `ven_com_id` int NOT NULL COMMENT 'อ้างอิงรหัสคำสั่งจากตาราง ven_com',
  `ven_name_sub_id` int NOT NULL COMMENT 'อ้างอิงรหัสหน้าที่ย่อยจากตาราง ven_name_sub',
  `user_id` varchar(36) NOT NULL COMMENT 'อ้างอิงรหัสพนักงานจากตาราง user',
  `status` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `google_event_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ven_time`
--

DROP TABLE IF EXISTS `ven_time`;
CREATE TABLE `ven_time` (
  `id` int NOT NULL,
  `name_th` varchar(50) NOT NULL COMMENT 'ชื่อเรียก เช่น กลางวัน',
  `time_period` varchar(50) NOT NULL COMMENT 'ช่วงเวลา เช่น 08.30-16.30',
  `srt` int NOT NULL DEFAULT '99' COMMENT 'ลำดับการแสดงผล'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ven_time` (`id`, `name_th`, `time_period`, `srt`) VALUES
(1, 'กลางวัน', '08.30-16.30', 1),
(2, 'กลางคืน', '16.30-08.30', 2),
(3, 'nightCourt', '16.30-20.00', 3);

-- --------------------------------------------------------

--
-- Table structure for table `ven_user`
--

DROP TABLE IF EXISTS `ven_user`;
CREATE TABLE `ven_user` (
  `id` int NOT NULL,
  `user_id` varchar(36) DEFAULT NULL,
  `order_num` int DEFAULT NULL,
  `ven_name_sub_id` int DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999' COMMENT 'ลำดับคิวการเข้าเวร'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

ALTER TABLE `agency_settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `google_service_settings`
  ADD PRIMARY KEY (`setting_key`);

ALTER TABLE `profile`
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `user_id_2` (`user_id`);

ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `telegram_notify_times`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `telegram_settings`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `password_reset_token` (`password_reset_token`);

ALTER TABLE `ven_change`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ven_com`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ven_name`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ven_name_sub`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ven_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ven_date` (`ven_date`),
  ADD KEY `idx_ven_com` (`ven_com_id`),
  ADD KEY `idx_ven_sub` (`ven_name_sub_id`),
  ADD KEY `idx_user` (`user_id`);

ALTER TABLE `ven_time`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ven_user`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `agency_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `system_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `telegram_notify_times`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

ALTER TABLE `telegram_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `ven_change`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

ALTER TABLE `ven_com`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1772680495;

ALTER TABLE `ven_name`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

ALTER TABLE `ven_name_sub`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

ALTER TABLE `ven_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1993;

ALTER TABLE `ven_time`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `ven_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=893;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;