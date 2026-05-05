-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: May 05, 2026 at 05:16 PM
-- Server version: 8.0.42
-- PHP Version: 8.2.27

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

CREATE TABLE `agency_settings` (
  `id` int NOT NULL,
  `agency_name` varchar(255) NOT NULL COMMENT 'ชื่อหน่วยงาน/ศาล',
  `director_name` varchar(255) NOT NULL COMMENT 'ชื่อผู้บริหาร',
  `director_position` varchar(255) NOT NULL COMMENT 'ตำแหน่งผู้บริหาร',
  `directors` text COMMENT 'ข้อมูลผู้บริหาร (ตัวจริงและตัวสำรอง JSON)',
  `admins` text COMMENT 'ข้อมูลผู้อำนวยการ (ตัวจริงและตัวสำรอง JSON)',
  `finances` text COMMENT 'ข้อมูลการเงิน (ตัวจริงและตัวสำรอง JSON)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `agency_settings`
--

INSERT INTO `agency_settings` (`id`, `agency_name`, `director_name`, `director_position`, `directors`, `admins`, `finances`) VALUES
(1, 'ศาลจังหวัดเพชรบุรี', '(ลงชื่อ).......................................................', 'ตำแหน่งผู้บริหาร', '[{\"name\":\"นายชูเกียรติ ภานุกรอุดม\",\"position\":\"ผู้พิพากษาหัวหน้าคณะชั้นต้นในศาลเยาวชนและครอบครัวจังหวัดลพบุรี ช่วยฯ ผู้พิพากษาหัวหน้าศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์\",\"is_active\":true},{\"name\":\"นายพพพ\",\"position\":\"รอง\",\"is_active\":true},{\"name\":\"www\",\"position\":\"www\",\"is_active\":true}]', '[{\"name\":\"นางสาวศรีชมภู อุ่นจิตรพันธ์\",\"position\":\"ผู้อำนวยการสำนักงานประจำศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์\",\"is_active\":false},{\"name\":\"okpddsd\",\"position\":\"sssss\",\"is_active\":true}]', '[{\"name\":\"นางสาวพจนา เทพพิชิตสมุทร\",\"position\":\"นักวิชาการเงินและบัญชีปฏิบัติการ\",\"is_active\":true},{\"name\":\"\",\"position\":\"\",\"is_active\":true}]');

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE `profile` (
  `user_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `id_card` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `prefix_name` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999',
  `img` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
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
  `st` smallint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `profile`
--

INSERT INTO `profile` (`user_id`, `id_card`, `prefix_name`, `first_name`, `last_name`, `srt`, `img`, `birthday`, `bloodtype`, `position`, `department`, `address`, `phone`, `bank_account`, `bank_comment`, `status`, `created_at`, `updated_at`, `st`) VALUES
('1', NULL, 'นาง', 'ผู้ดูแลระบบ', 'ทดสอบ11', 1, NULL, NULL, NULL, 'ผู้พิพากษา', 'กลุ่มงานอำนวยการ', NULL, '1123344', '123', '12311', 10, '2023-12-14 09:54:13', '2024-05-16 06:58:08', 1),
('1680162049', NULL, 'นาง', 'just1', 'just1', 999, NULL, NULL, NULL, 'ผู้พิพากษา', 'ผู้พิพากษา', NULL, '1111', NULL, NULL, 10, '2025-09-02 11:14:34', '2025-09-02 11:14:34', 1),
('1680162050', NULL, 'นาง', 'just2', 'just2', 999, NULL, NULL, NULL, 'ผู้พิพากษา', 'ผู้พิพากษา', NULL, '2222', NULL, NULL, 10, '2025-09-02 11:15:07', '2025-09-02 11:15:07', 2),
('1680162051', NULL, 'นางสาว', 'just3', 'just3', 999, NULL, NULL, NULL, 'ผู้พิพากษา', 'ผู้พิพากษา', NULL, '3333', '', '', 10, '2025-09-02 11:15:44', '2025-09-02 11:15:44', 3),
('1680162052', NULL, 'นาย', 'user1', 'user1', 999, NULL, NULL, NULL, 'เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ', 'ผู้อำนวยการฯ', NULL, '0001', NULL, NULL, 10, '2025-09-02 11:17:28', '2025-09-02 11:28:48', 201),
('1680162053', NULL, 'นาย', 'user2', 'user2', 999, NULL, NULL, NULL, 'เจ้าพนักงานศาลยุติธรรม', 'กลุ่มช่วยอำนวยการ', NULL, '0002', '', '', 10, '2025-09-02 11:25:08', '2025-09-02 11:28:38', 202),
('1680162054', NULL, 'นาย', 'user3', 'user3', 999, NULL, NULL, NULL, 'เจ้าหน้าที่ศาลยุติธรรม', 'กลุ่มงานคดี', NULL, '0003', '', '', 10, '2025-09-02 11:27:09', '2025-09-02 11:28:15', 203),
('1680162055', NULL, 'นางสาว', 'user4', 'user4', 999, NULL, NULL, NULL, 'เจ้าพนักงานศาลยุติธรรมปฏิบัติการ', 'กลุ่มงานคดี', NULL, '0004', NULL, NULL, 10, '2025-09-02 11:29:32', '2025-09-02 11:29:32', 204),
('1680162056', NULL, 'นางสาว', 'user5', 'user5', 999, NULL, NULL, NULL, 'นักวิชาการคอมพิวเตอร์', 'ส่วนเทคโนโลยีสารสนเทศ', NULL, '0005', NULL, NULL, 10, '2025-09-02 11:30:11', '2025-09-02 11:30:11', 1),
('865368c8-440a-409d-bce9-1ffa28f072b0', NULL, 'ฟฟฟ', 'sss', 'sss', 999, NULL, NULL, NULL, NULL, NULL, NULL, 'ss', '', '', 10, NULL, NULL, 0),
('e9d85622-0fcc-43c3-996a-865d6cf73f16', NULL, 'นาย', 'ss', 'aa', 999, NULL, NULL, NULL, 'นักวิชาการคอมพิวเตอร์', 'ผู้พิพากษาสมทบ', NULL, '', '', '', 10, NULL, NULL, 0),
('0fe42647-50be-422a-b75b-25a792a9e79a', NULL, 'สิบตำรวจเอก', 'd', 'f', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 10, NULL, NULL, 0),
('4dcc6854-a3b2-4fae-9617-bc8fd9158255', NULL, 'นาย', 'dd', NULL, 201, NULL, NULL, NULL, 'ผู้พิพากษา', 'กลุ่มงานอำนวยการ', NULL, 'dd', NULL, NULL, 10, NULL, NULL, 1),
('59cdd87f-420b-4e4d-ad59-0d65b41da9bb', NULL, NULL, 'u1', 'u1', 202, NULL, NULL, NULL, 'พนักงานคอมพิวเตอร์', 'ผู้อำนวยการฯ', NULL, NULL, NULL, NULL, 10, NULL, NULL, 1),
('0aa9692e-1f46-4ade-b57d-93ba0856bbe5', NULL, 'ฟฟฟ', 'j01', 'j01', 2, NULL, NULL, NULL, 'ผู้พิพากษาสมทบ', 'ผู้พิพากษาสมทบ', NULL, 'ww', NULL, NULL, 10, NULL, NULL, 1),
('4df27804-e670-48a6-b66a-e279631147a9', NULL, 'นาย', 'j02', 'j02', 3, NULL, NULL, NULL, 'เจ้าพนักงานศาลยุติธรรมปฏิบัติการ', 'กลุ่มงานช่วยพิจารณาคดี', NULL, 'j02', NULL, NULL, 10, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `system_name` varchar(255) DEFAULT 'ระบบบริหารจัดการเวรนอกเวลาทำการ',
  `allow_swap` tinyint(1) DEFAULT '1' COMMENT '1 = อนุญาตให้แลกเวร, 0 = ปิดระบบแลกเวร',
  `advance_swap_days` int DEFAULT '3' COMMENT 'ต้องขอแลกเวรล่วงหน้ากี่วัน',
  `maintenance_mode` tinyint(1) DEFAULT '0' COMMENT '1 = ปิดปรับปรุงระบบ',
  `allow_retroactive_swap` tinyint(1) DEFAULT '0' COMMENT '1 = เปลี่ยนย้อนหลังได้, 0 = ไม่ได้',
  `check_24h_consecutive` tinyint(1) DEFAULT '1' COMMENT '1 = เปิดระบบแจ้งเตือนเวร 24 ชม.',
  `user_options` text COMMENT 'เก็บ JSON ของ คำนำหน้า, ตำแหน่ง, กลุ่มงาน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `system_name`, `allow_swap`, `advance_swap_days`, `maintenance_mode`, `allow_retroactive_swap`, `check_24h_consecutive`, `user_options`) VALUES
(1, 'ระบบบริหารจัดการเวรนอกเวลาทำการ', 1, 0, 0, 0, 1, '{\"prefixes\":[\"นาย\",\"นาง\",\"นางสาว\"],\"positions\":[\"ผู้พิพากษา\",\"ผู้อำนวยการฯ\",\"นิติกร\",\"เจ้าพนักงานศาลยุติธรรม\"],\"departments\":[\"กลุ่มงานอำนวยการ\",\"กลุ่มงานคลัง\",\"กลุ่มงานบริการประชาชนฯ\"]}');

-- --------------------------------------------------------

--
-- Table structure for table `telegram_notify_times`
--

CREATE TABLE `telegram_notify_times` (
  `id` int NOT NULL,
  `send_time` time NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `notify_day` tinyint(1) DEFAULT '0' COMMENT '0: Today, 1: Tomorrow'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `telegram_notify_times`
--

INSERT INTO `telegram_notify_times` (`id`, `send_time`, `status`, `notify_day`) VALUES
(34, '18:20:00', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `telegram_settings`
--

CREATE TABLE `telegram_settings` (
  `id` int NOT NULL,
  `bot_token` varchar(255) NOT NULL,
  `chat_id` varchar(100) NOT NULL,
  `notify_confirmed` tinyint(1) DEFAULT '1',
  `notify_change_request` tinyint(1) DEFAULT '1',
  `notify_approval` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `telegram_settings`
--

INSERT INTO `telegram_settings` (`id`, `bot_token`, `chat_id`, `notify_confirmed`, `notify_change_request`, `notify_approval`) VALUES
(1, 'xxxxx', 'xxxx', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

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
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `auth_key`, `password_hash`, `password_reset_token`, `email`, `role`, `status`, `is_deleted`, `created_at`, `updated_at`) VALUES
('0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 'j01', 'db5aeeff8cf69e98ba3097842ce2475b', '$2y$10$f1O59DkcjhzsktWHtiVO1eS6gY61kqDbvrXIvkG2cgWLn0QCclU.2', NULL, NULL, 1, 10, 0, '2026-05-04 14:53:45', NULL),
('0fe42647-50be-422a-b75b-25a792a9e79a', 'f', NULL, '$2y$10$40wGPYZjmKf2hp.ZoK73c.6DKL7fVRJjjCN0s9ugXsAlRwM1bOVZy', NULL, NULL, 1, 10, 1, '2026-04-27 03:40:31', NULL),
('1', 'admin', '0672bb4360bcc59ddf06442cd671cb58', '$2y$10$cagJUbgpXBGOcPaLgqXauep2t4utkj0MrtQOorkjPukwGLkLARXkK', NULL, NULL, 9, 10, 0, '2023-12-14 09:54:13', '2024-04-27 18:13:27'),
('1680162049', 'j1', NULL, '$2y$10$ZYsif1LMin6gCWhKLZZ4hObuMqc8CtHBfPQ2jf/BL4ayNDvCO4hjq', NULL, NULL, 1, 10, 1, '2025-09-02 11:14:34', '2025-09-02 11:14:34'),
('1680162050', 'j2', 'fba4f98358c1d69f6c1bb61f5b8ae347', '$2y$10$tJ71.oB19GYr7Ryg1ypS/OsOf.kNaTFePC3IqmIEHStCd8DjbI52m', NULL, NULL, 1, 10, 1, '2025-09-02 11:15:07', '2025-09-02 11:15:07'),
('1680162051', 'j3', '92ad099dbf9c051ecf6f533ed417f094', '$2y$10$p8jIYxzYWdvsMupgq063w.hnOR0HX4bMixbUX8U0KkDd5zoZ2Mdiq', NULL, NULL, 1, 10, 1, '2025-09-02 11:15:44', '2025-09-02 11:15:44'),
('1680162052', 'user1', '66a11c9ae2dedb3f09496ba30ef9d165', '$2y$10$6.xneoVTL6DkcC23fNucUe3BD5aLvD6bpjVAD/NNzeHHj4sSAuyfi', NULL, NULL, 3, 10, 1, '2025-09-02 11:17:28', '2025-09-02 11:17:28'),
('1680162053', 'user2', '52f02c44126b47c72e0245ba0c6b8720', '$2y$10$bEU30/NLOPUPlcT9R9HYWuT1VhtwuE.EIpwUvvG5sfDCCRqaWfBPK', NULL, NULL, 1, 10, 1, '2025-09-02 11:25:08', '2025-09-02 11:25:08'),
('1680162054', 'user3', 'ba80daa54384d6c835a6df0e90a243f3', '$2y$10$olBspSbuGrdMlyZSNy913.CgRPvexRCp212MQw651mVbliSdRcNtS', NULL, NULL, 1, 10, 1, '2025-09-02 11:27:09', '2025-09-02 11:27:09'),
('1680162055', 'user4', '34f8b253fac48486869256d869c588ec', '$2y$10$o5CL0z0xoGLx1wkVWmKyLOxAV7LrxyJxSYEot/FyI8rEgh4h2NhO.', NULL, NULL, 2, 10, 1, '2025-09-02 11:29:32', '2025-09-02 11:29:32'),
('1680162056', 'user5', 'ca2bcace20d7cdbd623192db3515eaaa', '$2y$10$gUsJavBCJMAk.JW10JTLmeQD5b.rSsmOj0j27T8rPhh865xZPZxp.', NULL, NULL, 1, 10, 1, '2025-09-02 11:30:11', '2025-09-02 11:30:11'),
('4dcc6854-a3b2-4fae-9617-bc8fd9158255', 'dd', NULL, '$2y$10$uI8iTmktzPpAtI4pRAz2lu3P5bc67MLjnKR9KyjyREUfR29zwkyZq', NULL, NULL, 1, 10, 0, '2026-04-27 04:04:41', NULL),
('4df27804-e670-48a6-b66a-e279631147a9', 'j02', 'ed87a034c99470a302d301ba120f370d', '$2y$10$RDCWOMX9KVCDa2J./zWBP.b8tUG5XYUmIxzp1fiPmEYG.pF66rShe', NULL, NULL, 1, 10, 0, '2026-05-04 14:54:12', NULL),
('59cdd87f-420b-4e4d-ad59-0d65b41da9bb', 'u1', '510851e2b51f0d8c32c3bf4d90f8f59a', '$2y$10$UXVyMUTRAXA9eP3eq/TtFOgOjs97j2ZKWbsUSI5wtWtxpUMS2eaDS', NULL, NULL, 1, 10, 0, '2026-05-04 14:51:17', NULL),
('865368c8-440a-409d-bce9-1ffa28f072b0', 'sss', NULL, '$2y$10$gpc2ohIJbV2E2LEC8Fa4j.ZGIIM0MgRCafBPjFRrIXsvMxRTMLOia', NULL, NULL, 1, 10, 1, '2026-04-27 03:23:49', NULL),
('e9d85622-0fcc-43c3-996a-865d6cf73f16', 'aa', NULL, '$2y$10$3srR9S5I078l6rhkBtFm9OWdNIbh01hBTHw5IcdN9lnn0X5zM0lgG', NULL, NULL, 1, 0, 1, '2026-04-27 03:29:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ven_change`
--

CREATE TABLE `ven_change` (
  `id` int NOT NULL,
  `change_no` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `s1_id` int NOT NULL COMMENT 'รหัสตารางเวรของผู้ขอ (ven_schedule.id)',
  `user1_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'รหัสผู้ขอแลก',
  `s2_id` int DEFAULT NULL COMMENT 'รหัสตารางเวรของเพื่อน (ven_schedule.id)',
  `user2_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'รหัสเพื่อนที่ถูกขอแลก',
  `status` int DEFAULT '0' COMMENT '0=รออนุมัติ, 1=ยอมรับแล้ว, 2=ปฏิเสธ',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ven_change`
--

INSERT INTO `ven_change` (`id`, `change_no`, `s1_id`, `user1_id`, `s2_id`, `user2_id`, `status`, `created_at`) VALUES
(1, NULL, 531, '1', NULL, '1680162056', 0, '2026-04-27 16:01:31'),
(5, 'VC-2604-003', 516, '1', NULL, '1680162051', 0, '2026-04-27 19:10:14'),
(6, 'VC-2604-004', 535, '1680162054', NULL, '1680162056', 2, '2026-04-27 19:26:31'),
(7, 'VC-2604-005', 533, '1', NULL, '1680162055', 1, '2026-04-28 14:57:05'),
(8, 'VC-2604-006', 511, '1', NULL, '1680162050', 1, '2026-04-28 14:58:29'),
(9, 'VC-2604-007', 533, '1680162055', NULL, '1', 1, '2026-04-28 15:07:21'),
(10, 'VC-2604-008', 554, '1680162052', NULL, '1680162054', 1, '2026-04-30 16:05:52'),
(16, 'VC-2605-002', 680, '1', NULL, '1680162050', 1, '2026-05-03 15:10:30'),
(17, 'VC-2605-003', 680, '1680162050', NULL, '1680162051', 1, '2026-05-03 15:15:38'),
(18, 'VC-2605-004', 680, '1680162051', NULL, '1', 0, '2026-05-03 15:16:57'),
(25, 'VC-2605-008', 902, '1', NULL, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-04 16:50:38'),
(28, 'VC-2605-009', 875, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', NULL, '4df27804-e670-48a6-b66a-e279631147a9', 0, '2026-05-04 17:20:46'),
(29, 'VC-2605-010', 885, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', NULL, '4df27804-e670-48a6-b66a-e279631147a9', 0, '2026-05-04 17:31:51'),
(31, 'VC-2605-011', 984, '1', NULL, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', 1, '2026-05-05 16:10:34'),
(32, 'VC-2605-012', 745, '1680162052', NULL, '4df27804-e670-48a6-b66a-e279631147a9', 0, '2026-05-05 16:11:38'),
(33, 'VC-2605-013', 984, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', NULL, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 0, '2026-05-05 16:19:50'),
(34, 'VC-2605-014', 971, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', NULL, '1', 0, '2026-05-05 17:01:08');

-- --------------------------------------------------------

--
-- Table structure for table `ven_com`
--

CREATE TABLE `ven_com` (
  `id` int NOT NULL,
  `com_num` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `com_date` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ven_month` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ven_name_id` int DEFAULT NULL,
  `ven_com_days` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `file` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ref` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `ven_com`
--

INSERT INTO `ven_com` (`id`, `com_num`, `com_date`, `ven_month`, `status`, `ven_name_id`, `ven_com_days`, `comment`, `file`, `ref`, `create_at`) VALUES
(1772680474, '2222sss', '2026-04-09', '2026-04', '0', 27, '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30', NULL, NULL, NULL, NULL),
(1772680476, '55555', '2026-05-02', '2026-05', '1', 24, '2,3,4,9,10,16,17,23,24,30,31', NULL, NULL, NULL, NULL),
(1772680477, '55556', '2026-05-02', '2026-05', '1', 27, '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ven_name`
--

CREATE TABLE `ven_name` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_full` text,
  `dn` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `word` varchar(255) DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=ปกติ, 0=ลบ (Soft Delete)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `ven_name`
--

INSERT INTO `ven_name` (`id`, `name`, `name_full`, `dn`, `word`, `srt`, `status`) VALUES
(24, 'aaเวรเปิดทำการศาลนอกเวลาราชการ (เวรตรวจสอบการจับ)', 'ให้ข้าราชการฝ่ายตุลาการศาลยุติธรรม พนักงานราชการศาลยุติธรรม และลูกจ้างในศาลเยาวชนและครอบครัวกลาง อยู่ปฏิบัติหน้าที่โดยเปิดทำการศาลนอกเวลาราชการในวันหยุดราชการaa', 'กลางวัน(08.30-16.30)', 'ven_report_24_1756973949.docx', 0, 1),
(27, 'หมายจับ - ค้น (กลางคืน 16.30-08.30 น.)', 'ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 16.30 – 08.30 นาฬิกา ', 'กลางคืน(16.30-08.30)', NULL, 2, 1),
(28, 'เวรปฏิบัติงานในวันหยุดราชการตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 8.30-16.30 น.', 'ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 08.30 – 16.30 นาฬิกา ตามโครงการเปิดทำการศาลนอกเวลาราชการเพื่อเร่งรัดการพิจารณาพิพากษาคดี หรือเพื่ออำนวยความสะดวกแก่ประชาชน ประจำปีงบประมาณ พ.ศ. ๒๕๖๗ ', 'กลางวัน(08.30-16.30)', NULL, 1, 0),
(29, 'เวรปฏิบัติงานนอกเวลาราชการในวันทำการปกติตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 16.30-8.30 น.', 'ๆๆ', 'กลางคืน(16.30-08.30)', NULL, 4, 0),
(32, 'aaaaqq', 'qqqqqq', 'nightCourt(16.30-20.00)', NULL, 6, 0),
(33, 'ฟหดด', 'ผผปก', 'กลางคืน(16.30-08.30)', NULL, 5, 0),
(34, 'aa', 'aa', 'กลางวัน(08.30-16.30)', NULL, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ven_name_sub`
--

CREATE TABLE `ven_name_sub` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `ven_name_id` int NOT NULL,
  `price` int DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `srt` int DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1' COMMENT '1=ปกติ, 0=ลบ (Soft Delete)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `ven_name_sub`
--

INSERT INTO `ven_name_sub` (`id`, `name`, `ven_name_id`, `price`, `color`, `srt`, `status`) VALUES
(113, 'ผู้พิพากษา', 27, 1500, 'Green', 0, 1),
(115, 'ผู้พิพากษา', 28, 3000, 'Brown', 1, 0),
(116, 'รับฟ้อง+ปชส ', 28, 1500, 'Brown', 2, 0),
(117, 'งานรับฟ้อง', 24, 1500, 'BlueViolet', 2, 1),
(118, 'งานหน้าบัลลังก์', 24, 1500, 'BlueViolet', 3, 1),
(119, 'งานหมาย', 24, 1500, 'BlueViolet', 4, 1),
(120, 'งานประชาสัมพันธ์', 24, 1500, 'BlueViolet', 5, 1),
(121, 'งานการเงิน', 24, 1500, 'BlueViolet', 6, 1),
(123, 'จนท', 27, 1200, 'Green', 1, 1),
(124, 'การเงิน+ปล่อยตัวชั่วคราว', 27, 1000, 'Green', 2, 0),
(125, 'หน้าบัลลังก์', 27, 1000, 'Green', 3, 0),
(128, 'การเงิน+ปล่อยตัวชั่วคราว ', 28, 1500, 'Brown', 3, 0),
(129, 'หน้าบัลลังก์', 28, 1500, 'Brown', 4, 0),
(130, 'ผู้พิพากษา', 24, 3000, 'BlueViolet', 1, 1),
(133, 'ผู้พิพากษาสมทบ', 29, 1100, 'Magenta', 0, 0),
(135, 'z^hrld', 31, 160, 'Magenta', NULL, 1),
(138, 'aa', 29, 0, 'BlueViolet', 2, 0),
(139, 'www', 32, 1000, 'Brown', 1, 0),
(140, '111', 32, 0, '#198754', 2, 0),
(141, 'หกกด', 33, 0, 'Magenta', 1, 0),
(142, 'จนท', 29, 1200, 'BlueViolet', 2, 0),
(143, 'sss', 34, 150, 'BlueViolet', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `ven_schedule`
--

CREATE TABLE `ven_schedule` (
  `id` int NOT NULL,
  `ven_date` date NOT NULL COMMENT 'วันที่ปฏิบัติหน้าที่ (YYYY-MM-DD)',
  `ven_com_id` int NOT NULL COMMENT 'อ้างอิงรหัสคำสั่งจากตาราง ven_com',
  `ven_name_sub_id` int NOT NULL COMMENT 'อ้างอิงรหัสหน้าที่ย่อยจากตาราง ven_name_sub',
  `user_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'อ้างอิงรหัสพนักงานจากตาราง user',
  `status` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ven_schedule`
--

INSERT INTO `ven_schedule` (`id`, `ven_date`, `ven_com_id`, `ven_name_sub_id`, `user_id`, `status`, `created_at`) VALUES
(685, '2026-05-01', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:00'),
(686, '2026-05-02', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:00'),
(687, '2026-05-03', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:00'),
(688, '2026-05-04', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:00'),
(689, '2026-05-05', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:00'),
(690, '2026-05-06', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:00'),
(691, '2026-05-07', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:00'),
(692, '2026-05-08', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:00'),
(693, '2026-05-09', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:00'),
(694, '2026-05-10', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:01'),
(695, '2026-05-11', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:01'),
(696, '2026-05-12', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:01'),
(697, '2026-05-13', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:01'),
(698, '2026-05-14', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:01'),
(699, '2026-05-15', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:01'),
(700, '2026-05-16', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:01'),
(701, '2026-05-17', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:01'),
(702, '2026-05-18', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:01'),
(703, '2026-05-19', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:01'),
(704, '2026-05-20', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:01'),
(705, '2026-05-21', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:01'),
(706, '2026-05-22', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:01'),
(707, '2026-05-23', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:01'),
(708, '2026-05-24', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:01'),
(709, '2026-05-25', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:01'),
(710, '2026-05-26', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:01'),
(711, '2026-05-27', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:01'),
(712, '2026-05-28', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:01'),
(713, '2026-05-29', 1772680477, 113, '1680162050', 1, '2026-05-04 07:32:01'),
(714, '2026-05-30', 1772680477, 113, '1680162051', 1, '2026-05-04 07:32:01'),
(715, '2026-05-31', 1772680477, 113, '1680162049', 1, '2026-05-04 07:32:01'),
(716, '2026-05-01', 1772680477, 123, '1', 1, '2026-05-04 07:32:12'),
(717, '2026-05-02', 1772680477, 123, '1680162052', 1, '2026-05-04 07:32:12'),
(718, '2026-05-03', 1772680477, 123, '1680162053', 1, '2026-05-04 07:32:12'),
(719, '2026-05-04', 1772680477, 123, '1680162054', 1, '2026-05-04 07:32:12'),
(720, '2026-05-05', 1772680477, 123, '1680162055', 1, '2026-05-04 07:32:12'),
(721, '2026-05-06', 1772680477, 123, '1680162056', 1, '2026-05-04 07:32:12'),
(723, '2026-05-08', 1772680477, 123, '1', 1, '2026-05-04 07:32:12'),
(724, '2026-05-09', 1772680477, 123, '1680162052', 1, '2026-05-04 07:32:12'),
(725, '2026-05-10', 1772680477, 123, '1680162053', 1, '2026-05-04 07:32:12'),
(726, '2026-05-11', 1772680477, 123, '1680162054', 1, '2026-05-04 07:32:12'),
(727, '2026-05-12', 1772680477, 123, '1680162055', 1, '2026-05-04 07:32:12'),
(728, '2026-05-13', 1772680477, 123, '1680162056', 1, '2026-05-04 07:32:12'),
(729, '2026-05-14', 1772680477, 123, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-04 07:32:12'),
(730, '2026-05-15', 1772680477, 123, '1', 1, '2026-05-04 07:32:12'),
(731, '2026-05-16', 1772680477, 123, '1680162052', 1, '2026-05-04 07:32:12'),
(732, '2026-05-17', 1772680477, 123, '1680162053', 1, '2026-05-04 07:32:12'),
(733, '2026-05-18', 1772680477, 123, '1680162054', 1, '2026-05-04 07:32:12'),
(734, '2026-05-19', 1772680477, 123, '1680162055', 1, '2026-05-04 07:32:12'),
(735, '2026-05-20', 1772680477, 123, '1680162056', 1, '2026-05-04 07:32:12'),
(736, '2026-05-21', 1772680477, 123, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-04 07:32:12'),
(737, '2026-05-22', 1772680477, 123, '1', 1, '2026-05-04 07:32:12'),
(738, '2026-05-23', 1772680477, 123, '1680162052', 1, '2026-05-04 07:32:13'),
(739, '2026-05-24', 1772680477, 123, '1680162053', 1, '2026-05-04 07:32:13'),
(740, '2026-05-25', 1772680477, 123, '1680162054', 1, '2026-05-04 07:32:13'),
(741, '2026-05-26', 1772680477, 123, '1680162055', 1, '2026-05-04 07:32:13'),
(742, '2026-05-27', 1772680477, 123, '1680162056', 1, '2026-05-04 07:32:13'),
(743, '2026-05-28', 1772680477, 123, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-04 07:32:13'),
(744, '2026-05-29', 1772680477, 123, '1', 1, '2026-05-04 07:32:13'),
(745, '2026-05-30', 1772680477, 123, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-04 07:32:13'),
(746, '2026-05-31', 1772680477, 123, '1680162053', 1, '2026-05-04 07:32:13'),
(901, '2026-05-07', 1772680477, 123, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-04 10:47:47'),
(902, '2026-05-07', 1772680477, 123, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-04 10:47:49'),
(943, '2026-05-02', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:20'),
(944, '2026-05-03', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:20'),
(945, '2026-05-04', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:20'),
(946, '2026-05-09', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:20'),
(947, '2026-05-10', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:20'),
(948, '2026-05-16', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:20'),
(949, '2026-05-17', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:20'),
(950, '2026-05-23', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:20'),
(951, '2026-05-24', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:20'),
(952, '2026-05-30', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:20'),
(953, '2026-05-31', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:20'),
(954, '2026-05-02', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:49'),
(955, '2026-05-03', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:49'),
(956, '2026-05-04', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:49'),
(957, '2026-05-09', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:49'),
(958, '2026-05-10', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:49'),
(959, '2026-05-16', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:49'),
(960, '2026-05-17', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:49'),
(961, '2026-05-23', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:49'),
(962, '2026-05-24', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:49'),
(963, '2026-05-30', 1772680476, 130, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', 1, '2026-05-05 15:35:49'),
(964, '2026-05-31', 1772680476, 130, '4df27804-e670-48a6-b66a-e279631147a9', 1, '2026-05-05 15:35:49'),
(965, '2026-05-02', 1772680476, 117, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', 1, '2026-05-05 15:36:41'),
(966, '2026-05-02', 1772680476, 117, '1', 1, '2026-05-05 15:36:41'),
(967, '2026-05-03', 1772680476, 117, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-05 15:36:41'),
(968, '2026-05-03', 1772680476, 117, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', 1, '2026-05-05 15:36:41'),
(969, '2026-05-04', 1772680476, 117, '1', 1, '2026-05-05 15:36:42'),
(970, '2026-05-04', 1772680476, 117, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-05 15:36:42'),
(971, '2026-05-09', 1772680476, 117, '1', 1, '2026-05-05 15:36:42'),
(972, '2026-05-09', 1772680476, 117, '1', 1, '2026-05-05 15:36:42'),
(973, '2026-05-10', 1772680476, 117, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-05 15:36:42'),
(974, '2026-05-10', 1772680476, 117, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', 1, '2026-05-05 15:36:42'),
(975, '2026-05-16', 1772680476, 117, '1', 1, '2026-05-05 15:36:42'),
(976, '2026-05-16', 1772680476, 117, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-05 15:36:42'),
(977, '2026-05-17', 1772680476, 117, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', 1, '2026-05-05 15:36:42'),
(978, '2026-05-17', 1772680476, 117, '1', 1, '2026-05-05 15:36:42'),
(979, '2026-05-23', 1772680476, 117, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-05 15:36:42'),
(980, '2026-05-23', 1772680476, 117, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', 1, '2026-05-05 15:36:42'),
(981, '2026-05-24', 1772680476, 117, '1', 1, '2026-05-05 15:36:42'),
(982, '2026-05-24', 1772680476, 117, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-05 15:36:42'),
(983, '2026-05-30', 1772680476, 117, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', 1, '2026-05-05 15:36:42'),
(984, '2026-05-30', 1772680476, 117, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-05 15:36:42'),
(985, '2026-05-31', 1772680476, 117, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', 1, '2026-05-05 15:36:42'),
(986, '2026-05-31', 1772680476, 117, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', 1, '2026-05-05 15:36:42');

-- --------------------------------------------------------

--
-- Table structure for table `ven_time`
--

CREATE TABLE `ven_time` (
  `id` int NOT NULL,
  `name_th` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อเรียก เช่น กลางวัน',
  `time_period` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ช่วงเวลา เช่น 08.30-16.30',
  `srt` int NOT NULL DEFAULT '99' COMMENT 'ลำดับการแสดงผล'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ven_time`
--

INSERT INTO `ven_time` (`id`, `name_th`, `time_period`, `srt`) VALUES
(1, 'กลางวัน', '08.30-16.30', 1),
(2, 'กลางคืน', '16.30-08.30', 2),
(3, 'nightCourt', '16.30-20.00', 3);

-- --------------------------------------------------------

--
-- Table structure for table `ven_user`
--

CREATE TABLE `ven_user` (
  `id` int NOT NULL,
  `user_id` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `order_num` int DEFAULT NULL,
  `ven_name_sub_id` int DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `create_at` datetime DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999' COMMENT 'ลำดับคิวการเข้าเวร'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `ven_user`
--

INSERT INTO `ven_user` (`id`, `user_id`, `order_num`, `ven_name_sub_id`, `comment`, `create_at`, `srt`) VALUES
(719, '1680162049', 1, 130, '', '2025-09-02 01:03:12', 1),
(722, '1680162049', 0, 113, '', '2025-12-16 11:12:55', 999),
(723, '1680162050', 0, 113, '', '2025-12-16 11:12:59', 999),
(724, '1680162051', 0, 113, '', '2025-12-16 11:13:03', 999),
(728, '1680162056', 1, 115, NULL, NULL, 999),
(729, '1680162053', 3, 115, NULL, NULL, 999),
(730, '1680162055', 2, 115, NULL, NULL, 999),
(731, '1680162056', NULL, 133, NULL, NULL, 999),
(732, '1680162056', NULL, 117, NULL, NULL, 999),
(733, '1680162054', NULL, 117, NULL, NULL, 999),
(734, '1680162052', NULL, 117, NULL, NULL, 999),
(735, '1680162051', NULL, 117, NULL, NULL, 999),
(755, '1', NULL, 118, NULL, NULL, 999),
(756, '1680162049', NULL, 118, NULL, NULL, 999),
(757, '1680162050', NULL, 118, NULL, NULL, 999),
(761, '1680162051', NULL, 130, NULL, NULL, 2),
(763, '1', NULL, 121, NULL, NULL, 2),
(764, '1680162049', NULL, 121, NULL, NULL, 1),
(766, '1', NULL, 123, NULL, NULL, 1),
(767, '1680162052', NULL, 123, NULL, NULL, 2),
(768, '1680162053', NULL, 123, NULL, NULL, 3),
(769, '1680162054', NULL, 123, NULL, NULL, 4),
(770, '1680162055', NULL, 123, NULL, NULL, 5),
(771, '1680162056', NULL, 123, NULL, NULL, 6),
(772, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', NULL, 123, NULL, NULL, 7),
(776, '4df27804-e670-48a6-b66a-e279631147a9', NULL, 123, NULL, NULL, 8),
(778, '0aa9692e-1f46-4ade-b57d-93ba0856bbe5', NULL, 130, NULL, NULL, 4),
(779, '4df27804-e670-48a6-b66a-e279631147a9', NULL, 130, NULL, NULL, 5),
(782, '1', NULL, 117, NULL, NULL, 5),
(786, '59cdd87f-420b-4e4d-ad59-0d65b41da9bb', NULL, 117, NULL, NULL, 3),
(787, '1', NULL, 113, NULL, NULL, 1000),
(788, '4df27804-e670-48a6-b66a-e279631147a9', NULL, 113, NULL, NULL, 1001),
(789, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', NULL, 113, NULL, NULL, 1002),
(790, '1', NULL, 143, NULL, NULL, 1),
(791, '4dcc6854-a3b2-4fae-9617-bc8fd9158255', NULL, 117, NULL, NULL, 1000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agency_settings`
--
ALTER TABLE `agency_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `profile`
--
ALTER TABLE `profile`
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `user_id_2` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `telegram_notify_times`
--
ALTER TABLE `telegram_notify_times`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `telegram_settings`
--
ALTER TABLE `telegram_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `password_reset_token` (`password_reset_token`);

--
-- Indexes for table `ven_change`
--
ALTER TABLE `ven_change`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ven_com`
--
ALTER TABLE `ven_com`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ven_name`
--
ALTER TABLE `ven_name`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ven_name_sub`
--
ALTER TABLE `ven_name_sub`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ven_schedule`
--
ALTER TABLE `ven_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ven_date` (`ven_date`),
  ADD KEY `idx_ven_com` (`ven_com_id`),
  ADD KEY `idx_ven_sub` (`ven_name_sub_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `ven_time`
--
ALTER TABLE `ven_time`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ven_user`
--
ALTER TABLE `ven_user`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agency_settings`
--
ALTER TABLE `agency_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `telegram_notify_times`
--
ALTER TABLE `telegram_notify_times`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `telegram_settings`
--
ALTER TABLE `telegram_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ven_change`
--
ALTER TABLE `ven_change`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `ven_com`
--
ALTER TABLE `ven_com`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1772680478;

--
-- AUTO_INCREMENT for table `ven_name`
--
ALTER TABLE `ven_name`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `ven_name_sub`
--
ALTER TABLE `ven_name_sub`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT for table `ven_schedule`
--
ALTER TABLE `ven_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=987;

--
-- AUTO_INCREMENT for table `ven_time`
--
ALTER TABLE `ven_time`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ven_user`
--
ALTER TABLE `ven_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=792;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
