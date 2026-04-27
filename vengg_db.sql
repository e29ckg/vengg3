-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Apr 27, 2026 at 08:45 AM
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
-- Table structure for table `dep`
--

CREATE TABLE `dep` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `dep`
--

INSERT INTO `dep` (`id`, `name`, `created_at`) VALUES
(1, 'พนักงานคอมพิวเตอร์', '2019-08-10 17:45:49'),
(2, 'นักวิชาการคอมพิวเตอร์', '2019-08-10 17:45:49'),
(3, 'เจ้าหน้าที่ศาลยุติธรรมปฏิบัติงาน', '2019-08-10 17:45:49'),
(4, 'เจ้าหน้าที่ศาลยุติธรรมชำนาญงาน', '2019-08-10 17:45:49'),
(5, 'นักจิตวิทยาปฏิบัติการ', '2019-08-10 17:45:49'),
(6, 'พนักงานสถานที่', '2019-08-10 17:45:49'),
(7, 'พนักงานขับรถยนต์', '2019-08-10 17:45:49'),
(8, 'เจ้าหน้าที่ศาลยุติธรรม', '2019-08-10 17:45:49'),
(9, 'เจ้าพนักงานศาลยุติธรรมปฏิบัติการ', '2019-08-10 17:45:49'),
(10, 'นิติกรชำนาญการ', '2019-08-10 17:45:49'),
(11, 'เจ้าพนักงานศาลยุติธรรมชำนาญการ', '2019-08-10 17:45:49'),
(12, 'นักวิชาการเงินและบัญชีปฏิบัติการ', '2019-08-10 17:45:49'),
(13, 'เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ', '2019-08-10 17:45:49'),
(14, 'นิติกร', '2019-08-10 17:45:49'),
(15, 'ผู้อำนวยการฯ', '2019-08-10 17:45:49'),
(17, 'พนักงานขับรถยนต์(จ้างเหมา)', NULL),
(18, 'ผู้พิพากษา', NULL),
(19, 'นิติกรชำนาญการพิเศษ', NULL),
(20, 'เจ้าพนักงานการเงินและบัญชีปฏิบัติงาน', NULL),
(21, 'นักจิตวิทยาชำนาญการ', NULL),
(22, 'เจ้าพนักงานศาลยุติธรรม', NULL),
(23, 'นิติกรปฏิบัติการ', NULL),
(24, 'นักวิชาการเงินและบัญชีชำนาญการพิเศษ', NULL),
(25, 'เจ้าพนักงานตำรวจศาลปฏิบัติการ', NULL),
(28, 'ผู้พิพากษาสมทบ', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fname`
--

CREATE TABLE `fname` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `fname`
--

INSERT INTO `fname` (`id`, `name`, `created_at`) VALUES
(1, 'นาย', '2019-08-10 17:45:49'),
(2, 'นาง', '2019-08-10 17:45:50'),
(3, 'นางสาว', '2019-08-10 17:45:50'),
(4, 'พันจ่าเอก', NULL),
(5, 'พ.ต.อ.', NULL),
(6, 'พท.', NULL),
(7, 'สิบตำรวจเอก', NULL),
(8, 'หม่อมหลวงๅ', NULL),
(9, 'ฟฟฟ', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

CREATE TABLE `group` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `group`
--

INSERT INTO `group` (`id`, `name`, `created_at`) VALUES
(1, 'ผู้อำนวยการฯ', '2019-10-06 18:49:32'),
(2, 'กลุ่มช่วยอำนวยการ', '2019-10-06 18:49:32'),
(3, 'กลุ่มงานช่วยพิจารณาคดี', '2019-10-06 18:49:32'),
(4, 'กลุ่มงานคดี', '2019-10-06 18:49:32'),
(5, 'กลุ่มงานคลัง', '2019-10-06 18:49:32'),
(6, 'กลุ่มงานปริการประชาชนและประชาสัมพันธ์', '2019-10-06 18:49:32'),
(7, 'กลุ่มงานไกล่เกลี่ยและประนอมข้อพิพาท', '2019-10-06 18:49:32'),
(8, 'ผู้พิพากษา', NULL),
(9, 'ส่วนมาตรการพิเศษ', NULL),
(10, 'เจ้าพนักงานตำรวจศาล', NULL),
(11, 'ส่วนส่งเสริมและวิชาการ', NULL),
(12, 'ส่วนเทคโนโลยีสารสนเทศ', NULL),
(14, 'ผู้พิพากษาสมทบ', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `line`
--

CREATE TABLE `line` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `status` smallint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `line`
--

INSERT INTO `line` (`id`, `name`, `token`, `status`) VALUES
(1, 'admin', 'StzWTl6iwQfwKKZPqsHxLrx6Ie6g4GPiTnVaXaJzIKa ', 0),
(2, 'ven', 'StzWTl6iwQfwKKZPqsHxLrx6Ie6g4GPiTnVaXaJzIKa', 1),
(3, 'ven_admin', 'StzWTl6iwQfwKKZPqsHxLrx6Ie6g4GPiTnVaXaJzIKa', 0);

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE `profile` (
  `user_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `id_card` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `prefix_name` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `fname_id` int DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `srt` int NOT NULL DEFAULT '999',
  `img` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `bloodtype` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `dep` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `dep_id` int DEFAULT NULL,
  `workgroup` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `group_id` int DEFAULT NULL,
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

INSERT INTO `profile` (`user_id`, `id_card`, `prefix_name`, `fname_id`, `first_name`, `last_name`, `srt`, `img`, `birthday`, `bloodtype`, `dep`, `dep_id`, `workgroup`, `group_id`, `address`, `phone`, `bank_account`, `bank_comment`, `status`, `created_at`, `updated_at`, `st`) VALUES
('1', NULL, 'นาย', 1, 'ผู้ดูแลระบบ', 'ทดสอบ', 1, NULL, NULL, NULL, 'พนักงานสถานที่', 6, 'กลุ่มช่วยอำนวยการ', 2, NULL, '1', '', '', 10, '2023-12-14 09:54:13', '2024-05-16 06:58:08', 1),
('1680162049', NULL, 'นาง', 2, 'just1', 'just1', 12, NULL, NULL, NULL, 'ผู้พิพากษา', 18, 'ผู้พิพากษา', 8, NULL, '1111', NULL, NULL, 10, '2025-09-02 11:14:34', '2025-09-02 11:14:34', 1),
('1680162050', NULL, 'นาง', 2, 'just2', 'just2', 999, NULL, NULL, NULL, 'ผู้พิพากษา', 18, 'ผู้พิพากษา', 8, NULL, '2222', '', '', 10, '2025-09-02 11:15:07', '2025-09-02 11:15:07', 2),
('1680162051', NULL, 'นางสาว', 3, 'just3', 'just3', 999, NULL, NULL, NULL, 'ผู้พิพากษา', 18, 'ผู้พิพากษา', 8, NULL, '3333', '', '', 10, '2025-09-02 11:15:44', '2025-09-02 11:15:44', 3),
('1680162052', NULL, 'นาย', 1, 'user1', 'user1', 999, NULL, NULL, NULL, 'เจ้าพนักงานศาลยุติธรรมชำนาญการพิเศษ', 13, 'ผู้อำนวยการฯ', 1, NULL, '0001', '', '', 10, '2025-09-02 11:17:28', '2025-09-02 11:28:48', 201),
('1680162053', NULL, 'นาย', 1, 'user2', 'user2', 999, NULL, NULL, NULL, 'เจ้าพนักงานศาลยุติธรรม', 22, 'กลุ่มช่วยอำนวยการ', 2, NULL, '0002', '', '', 10, '2025-09-02 11:25:08', '2025-09-02 11:28:38', 202),
('1680162054', NULL, 'นาย', 1, 'user3', 'user3', 999, NULL, NULL, NULL, 'เจ้าหน้าที่ศาลยุติธรรม', 8, 'กลุ่มงานคดี', 4, NULL, '0003', '', '', 10, '2025-09-02 11:27:09', '2025-09-02 11:28:15', 203),
('1680162055', NULL, 'นางสาว', 3, 'user4', 'user4', 999, NULL, NULL, NULL, 'เจ้าพนักงานศาลยุติธรรมปฏิบัติการ', 9, 'กลุ่มงานคดี', 4, NULL, '0004', '', '', 10, '2025-09-02 11:29:32', '2025-09-02 11:29:32', 204),
('1680162056', NULL, 'นางสาว', 3, 'user5', 'user5', 999, NULL, NULL, NULL, 'นักวิชาการคอมพิวเตอร์', 2, 'ส่วนเทคโนโลยีสารสนเทศ', 12, NULL, '0005', NULL, NULL, 0, '2025-09-02 11:30:11', '2025-09-02 11:30:11', 1),
('865368c8-440a-409d-bce9-1ffa28f072b0', NULL, 'ฟฟฟ', 2, 'sss', 'sss', 999, NULL, NULL, NULL, NULL, 1, NULL, 1, NULL, 'ss', '', '', 10, NULL, NULL, 0),
('e9d85622-0fcc-43c3-996a-865d6cf73f16', NULL, 'นาย', 1, 'ss', 'aa', 999, NULL, NULL, NULL, 'นักวิชาการคอมพิวเตอร์', 1, 'ผู้พิพากษาสมทบ', 1, NULL, '', '', '', 10, NULL, NULL, 0),
('0fe42647-50be-422a-b75b-25a792a9e79a', NULL, 'สิบตำรวจเอก', NULL, 'd', 'f', 999, NULL, NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, NULL, NULL, 10, NULL, NULL, 0),
('4dcc6854-a3b2-4fae-9617-bc8fd9158255', NULL, 'นาย', NULL, 'dd', NULL, 999, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'dd', NULL, NULL, 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `sign_name`
--

CREATE TABLE `sign_name` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dep` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dep2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `dep3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `st` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sign_name`
--

INSERT INTO `sign_name` (`id`, `name`, `dep`, `dep2`, `dep3`, `role`, `st`) VALUES
(6, 'ศาลเยาวชนและครอบครัวกลาง1', 'สำนักอำนวยการประจำศาลเยาวชนและครอบครัวกลาง1', '', '', 'Court_Name', 1),
(7, 'นายเผดิม เพ็ชรกูล', 'อธิบดีผู้พิพากษาศาลเยาวชนและครอบครัวกลาง', '', '', 'Chief_Judge', 1),
(8, 'นางสาวสุดาทิพย์ อำนวยพันธ์วิไล', 'ผู้อำนวยการสำนักอำนวยการประจำศาลเยาวชนและครอบครัวกลาง', '', '', 'Director', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` varchar(36) COLLATE utf8mb3_unicode_ci NOT NULL,
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
('0fe42647-50be-422a-b75b-25a792a9e79a', 'f', NULL, '$2y$10$40wGPYZjmKf2hp.ZoK73c.6DKL7fVRJjjCN0s9ugXsAlRwM1bOVZy', NULL, NULL, 1, 0, 0, '2026-04-27 03:40:31', NULL),
('1', 'admin', 'c7e8e2154fa0bafca19c511e4c96efd3', '$2y$10$SjrUtSxJ8dcOU2cnkltWbOFNzXpCKhd0.5McR3qskS0nIVsOLZrT2', NULL, NULL, 9, 10, 0, '2023-12-14 09:54:13', '2024-04-27 18:13:27'),
('1680162049', 'j1', NULL, '$2y$10$ZYsif1LMin6gCWhKLZZ4hObuMqc8CtHBfPQ2jf/BL4ayNDvCO4hjq', NULL, NULL, 1, 10, 0, '2025-09-02 11:14:34', '2025-09-02 11:14:34'),
('1680162050', 'j2', NULL, '$2y$10$EWjmeA.i7d1ZaEZAOv3zouA3r0fBsJUu6VUaJ5WYF8WKgOLi5p8wK', NULL, NULL, 1, 10, 0, '2025-09-02 11:15:07', '2025-09-02 11:15:07'),
('1680162051', 'j3', NULL, '$2y$10$p8jIYxzYWdvsMupgq063w.hnOR0HX4bMixbUX8U0KkDd5zoZ2Mdiq', NULL, NULL, 1, 10, 0, '2025-09-02 11:15:44', '2025-09-02 11:15:44'),
('1680162052', 'user1', NULL, '$2y$10$6.xneoVTL6DkcC23fNucUe3BD5aLvD6bpjVAD/NNzeHHj4sSAuyfi', NULL, NULL, 1, 10, 0, '2025-09-02 11:17:28', '2025-09-02 11:17:28'),
('1680162053', 'user2', NULL, '$2y$10$bEU30/NLOPUPlcT9R9HYWuT1VhtwuE.EIpwUvvG5sfDCCRqaWfBPK', NULL, NULL, 1, 10, 0, '2025-09-02 11:25:08', '2025-09-02 11:25:08'),
('1680162054', 'user3', NULL, '$2y$10$olBspSbuGrdMlyZSNy913.CgRPvexRCp212MQw651mVbliSdRcNtS', NULL, NULL, 1, 10, 0, '2025-09-02 11:27:09', '2025-09-02 11:27:09'),
('1680162055', 'user4', NULL, '$2y$10$o5CL0z0xoGLx1wkVWmKyLOxAV7LrxyJxSYEot/FyI8rEgh4h2NhO.', NULL, NULL, 1, 0, 0, '2025-09-02 11:29:32', '2025-09-02 11:29:32'),
('1680162056', 'user5', NULL, '$2y$10$gUsJavBCJMAk.JW10JTLmeQD5b.rSsmOj0j27T8rPhh865xZPZxp.', NULL, NULL, 1, 0, 0, '2025-09-02 11:30:11', '2025-09-02 11:30:11'),
('4dcc6854-a3b2-4fae-9617-bc8fd9158255', 'dd', NULL, '$2y$10$uI8iTmktzPpAtI4pRAz2lu3P5bc67MLjnKR9KyjyREUfR29zwkyZq', NULL, NULL, 1, 0, 0, '2026-04-27 04:04:41', NULL),
('865368c8-440a-409d-bce9-1ffa28f072b0', 'sss', NULL, '$2y$10$gpc2ohIJbV2E2LEC8Fa4j.ZGIIM0MgRCafBPjFRrIXsvMxRTMLOia', NULL, NULL, 1, 10, 1, '2026-04-27 03:23:49', NULL),
('e9d85622-0fcc-43c3-996a-865d6cf73f16', 'aa', NULL, '$2y$10$3srR9S5I078l6rhkBtFm9OWdNIbh01hBTHw5IcdN9lnn0X5zM0lgG', NULL, NULL, 1, 0, 1, '2026-04-27 03:29:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ven`
--

CREATE TABLE `ven` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `ven_com_id` int DEFAULT NULL,
  `ven_com_idb` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ven_date` date NOT NULL,
  `ven_time` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `vn_id` int DEFAULT NULL,
  `vns_id` int DEFAULT NULL,
  `gcal_id` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ref1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ref2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `file` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `status` int DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  `create_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `ven`
--

INSERT INTO `ven` (`id`, `user_id`, `ven_com_id`, `ven_com_idb`, `ven_date`, `ven_time`, `vn_id`, `vns_id`, `gcal_id`, `ref1`, `ref2`, `file`, `comment`, `status`, `update_at`, `create_at`) VALUES
(1756793066, 1680162049, 1756793049, '1756793049', '2025-09-06', '08:30:01', 24, 130, NULL, 'Hx3lhvtqCcRZmufAbI1V', 'Hx3lhvtqCcRZmufAbI1V', NULL, NULL, 2, '2025-09-02 13:04:26', '2025-09-02 13:04:26'),
(1756793071, 1680162050, 1756793049, '1756793049', '2025-09-06', '08:30:02', 24, 130, NULL, 'dwt2IXrNHLAasWb3YVJn', 'dwt2IXrNHLAasWb3YVJn', NULL, NULL, 2, '2025-09-02 13:04:31', '2025-09-02 13:04:31'),
(1756793079, 1680162051, 1756793049, '1756793049', '2025-09-07', '08:30:01', 24, 130, NULL, 'mfxjyFDsOHV35ca9pUZY', 'mfxjyFDsOHV35ca9pUZY', NULL, NULL, 2, '2025-09-02 13:04:39', '2025-09-02 13:04:39'),
(1765857816, 1680162049, 1756793049, '1756793049', '2025-12-01', '08:30:01', 24, 130, NULL, 'AfmEQ1X4hSWbtHaFYsvR', 'AfmEQ1X4hSWbtHaFYsvR', NULL, NULL, 2, '2025-12-16 11:03:36', '2025-12-16 11:03:36'),
(1765857825, 1680162050, 1756793049, '1756793049', '2025-12-03', '08:30:02', 24, 130, NULL, 'hUtycFCNLrAvd9zGxnTJ', 'hUtycFCNLrAvd9zGxnTJ', NULL, NULL, 2, '2025-12-16 11:12:07', '2025-12-16 11:03:45'),
(1765857828, 1680162051, 1756793049, '1756793049', '2025-12-03', '08:30:01', 24, 130, NULL, 'GCoHjfirqELJbs7k1znS', 'GCoHjfirqELJbs7k1znS', NULL, NULL, 2, '2025-12-16 11:03:48', '2025-12-16 11:03:48'),
(1765858329, 1680162049, 1756793049, '1756793049', '2025-12-02', '08:30:01', 24, 130, NULL, 'swmB0uO5IfeazRrygWhL', 'swmB0uO5IfeazRrygWhL', NULL, NULL, 2, '2025-12-16 11:12:09', '2025-12-16 11:12:09'),
(1765858540, 1680162049, 1765858095, '1765858095', '2025-12-01', '16:30:02', 25, 109, NULL, 'bv6QsnH9xgTLY4ScGpVD', 'bv6QsnH9xgTLY4ScGpVD', NULL, NULL, 2, '2025-12-16 11:15:40', '2025-12-16 11:15:40'),
(1765947641, 1680162049, 1765858095, '1765858095', '2025-12-04', '16:30:01', 25, 109, NULL, '9MTNB4zFonOL2hD5PlUI', '9MTNB4zFonOL2hD5PlUI', NULL, NULL, 2, '2025-12-17 12:00:41', '2025-12-17 12:00:41'),
(1765947716, 1680162049, 1765858095, '1765858095', '2025-12-05', '16:30:01', 25, 109, NULL, 'm9HM4DuI8jGtkcOSTRwf', 'm9HM4DuI8jGtkcOSTRwf', NULL, NULL, 2, '2025-12-17 12:08:34', '2025-12-17 12:01:56'),
(1765948504, 1680162049, 1765858095, '1765858095', '2025-12-02', '16:30:02', 25, 109, NULL, 'nFJgRbN1xm5VzrjkdBl8', 'nFJgRbN1xm5VzrjkdBl8', NULL, NULL, 2, '2025-12-17 12:15:04', '2025-12-17 12:15:04'),
(1772680487, 1680162051, 1772680473, '1772680473', '2026-03-05', '16:30:01', 25, 109, NULL, 'GvIHXuhlc3pFUCg4eY7s', 'GvIHXuhlc3pFUCg4eY7s', NULL, NULL, 2, '2026-03-05 10:14:47', '2026-03-05 10:14:47'),
(1772680489, 1680162051, 1772680473, '1772680473', '2026-03-05', '16:30:02', 25, 109, NULL, 'jzZxDBV6HrXgfKFWa94Q', 'jzZxDBV6HrXgfKFWa94Q', NULL, NULL, 2, '2026-03-05 10:14:49', '2026-03-05 10:14:49'),
(1772680492, 1680162049, 1772680473, '1772680473', '2026-03-05', '16:30:03', 25, 109, NULL, 'MLy9g5YrC8TGoQvPWmZ7', 'MLy9g5YrC8TGoQvPWmZ7', NULL, NULL, 2, '2026-03-05 10:14:52', '2026-03-05 10:14:52');

-- --------------------------------------------------------

--
-- Table structure for table `ven_change`
--

CREATE TABLE `ven_change` (
  `id` int NOT NULL,
  `s1_id` int NOT NULL COMMENT 'รหัสตารางเวรของผู้ขอ (ven_schedule.id)',
  `user1_id` int NOT NULL COMMENT 'รหัสผู้ขอแลก',
  `s2_id` int NOT NULL COMMENT 'รหัสตารางเวรของเพื่อน (ven_schedule.id)',
  `user2_id` int NOT NULL COMMENT 'รหัสเพื่อนที่ถูกขอแลก',
  `status` int DEFAULT '0' COMMENT '0=รออนุมัติ, 1=ยอมรับแล้ว, 2=ปฏิเสธ',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1765858095, '4/2568', '2025-12-16', '2025-12', '1', 25, '', NULL, NULL, '16grxL3Ot4AcQmjaRZPW', NULL),
(1772680473, '1111', '2026-03-05', '2026-04', '1', 24, '4,5,11,12,18,19,25,26', NULL, NULL, '3AnblhvKL7xjcdugH6kz', NULL),
(1772680474, '2222', '2026-04-26', '2026-04', '1', 25, '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ven_name`
--

CREATE TABLE `ven_name` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_full` text,
  `DN` varchar(255) DEFAULT NULL,
  `word` varchar(255) DEFAULT NULL,
  `srt` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `ven_name`
--

INSERT INTO `ven_name` (`id`, `name`, `name_full`, `DN`, `word`, `srt`) VALUES
(24, 'เวรเปิดทำการศาลนอกเวลาราชการ (เวรตรวจสอบการจับ)', 'ให้ข้าราชการฝ่ายตุลาการศาลยุติธรรม พนักงานราชการศาลยุติธรรม และลูกจ้างในศาลเยาวชนและครอบครัวกลาง อยู่ปฏิบัติหน้าที่โดยเปิดทำการศาลนอกเวลาราชการในวันหยุดราชการ', 'กลางวัน', 'ven_report_24_1756973949.docx', 0),
(25, 'เวรปฏิบัติหน้าที่ออกหมายจับและหมายค้นนอกเวลาราชการ (เวรกลางคืน)', 'ให้ข้าราชการฝ่ายตุลาการศาลยุติธรรม พนักงานราชการศาลยุติธรรม และลูกจ้างในศาลเยาวชนและครอบครัวกลางอยู่ปฏิบัติหน้าที่ออกหมายจับและหมายค้นนอกเวลาราชการ (เวรกลางคืน) ', 'กลางคืน', NULL, 5),
(27, 'เวรปฏิบัติงานนอกเวลาราชการในวันทำการปกติตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 16.30-20.30 น.', 'ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 16.30 – 20.30 นาฬิกา ตามโครงการเปิดทำการศาลนอกเวลาราชการเพื่อเร่งรัดการพิจารณาพิพากษาคดี หรือเพื่ออำนวยความสะดวกแก่ประชาชน ประจำปีงบประมาณ พ.ศ. ๒๕๖๗ ', 'nightCourt', NULL, 3),
(28, 'เวรปฏิบัติงานในวันหยุดราชการตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 8.30-16.30 น.', 'ให้ข้าราชการตุลาการ ข้าราชการศาลยุติธรรม ลูกจ้าง และพนักงานราชการ ปฏิบัติงานในวันหยุดราชการ เวลา 08.30 – 16.30 นาฬิกา ตามโครงการเปิดทำการศาลนอกเวลาราชการเพื่อเร่งรัดการพิจารณาพิพากษาคดี หรือเพื่ออำนวยความสะดวกแก่ประชาชน ประจำปีงบประมาณ พ.ศ. ๒๕๖๗ ', 'กลางวัน', NULL, 1),
(29, 'เวรปฏิบัติงานนอกเวลาราชการในวันทำการปกติตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 16.30-20.30 น. ( ผู้พิพากษาสมทบ)', NULL, 'nightCourt', NULL, 4),
(30, 'เวรปฏิบัติงานในวันหยุดราชการตามโครงการเปิดทำการศาลนอกเวลาราชการฯ 8.30-16.30 น. (ผู้พิพากษาสมทบ)', NULL, 'กลางวัน', NULL, 6);

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
  `srt` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `ven_name_sub`
--

INSERT INTO `ven_name_sub` (`id`, `name`, `ven_name_id`, `price`, `color`, `srt`) VALUES
(109, 'ผู้พิพากษา', 25, 2500, 'Violet', 0),
(110, 'จนท', 25, 1200, 'Violet', 1),
(113, 'ผู้พิพากษา', 27, 2000, 'Green', 0),
(115, 'ผู้พิพากษา', 28, 3000, 'Brown', 1),
(116, 'รับฟ้อง+ปชส ', 28, 1500, 'Brown', 2),
(117, 'งานรับฟ้อง', 24, 1500, 'BlueViolet', 2),
(118, 'งานหน้าบัลลังก์', 24, 1500, 'BlueViolet', 3),
(119, 'งานหมาย', 24, 1500, 'BlueViolet', 4),
(120, 'งานประชาสัมพันธ์', 24, 1500, 'BlueViolet', 5),
(121, 'งานการเงิน', 24, 1500, 'BlueViolet', 6),
(123, 'รับฟ้อง+ปชส', 27, 1000, 'Green', 1),
(124, 'การเงิน+ปล่อยตัวชั่วคราว', 27, 1000, 'Green', 2),
(125, 'หน้าบัลลังก์', 27, 1000, 'Green', 3),
(128, 'การเงิน+ปล่อยตัวชั่วคราว ', 28, 1500, 'Brown', 3),
(129, 'หน้าบัลลังก์', 28, 1500, 'Brown', 4),
(130, 'ผู้พิพากษา', 24, 3000, 'BlueViolet', 1),
(133, 'ผู้พิพากษาสมทบ', 29, 1000, 'Magenta', 0),
(134, 'ผู้พิพากษาสมทบ', 30, 1000, 'DarkCyan', 0),
(135, 'z^hrld', 31, 160, 'Magenta', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ven_schedule`
--

CREATE TABLE `ven_schedule` (
  `id` int NOT NULL,
  `ven_date` date NOT NULL COMMENT 'วันที่ปฏิบัติหน้าที่ (YYYY-MM-DD)',
  `ven_com_id` int NOT NULL COMMENT 'อ้างอิงรหัสคำสั่งจากตาราง ven_com',
  `ven_name_sub_id` int NOT NULL COMMENT 'อ้างอิงรหัสหน้าที่ย่อยจากตาราง ven_name_sub',
  `user_id` int NOT NULL COMMENT 'อ้างอิงรหัสพนักงานจากตาราง user',
  `status` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ven_schedule`
--

INSERT INTO `ven_schedule` (`id`, `ven_date`, `ven_com_id`, `ven_name_sub_id`, `user_id`, `status`, `created_at`) VALUES
(322, '2026-04-04', 1772680473, 130, 1680162049, 0, '2026-04-27 08:24:13'),
(323, '2026-04-05', 1772680473, 130, 1680162050, 0, '2026-04-27 08:24:13'),
(324, '2026-04-11', 1772680473, 130, 1680162051, 0, '2026-04-27 08:24:13'),
(325, '2026-04-12', 1772680473, 130, 1680162049, 0, '2026-04-27 08:24:13'),
(326, '2026-04-18', 1772680473, 130, 1680162050, 0, '2026-04-27 08:24:13'),
(327, '2026-04-19', 1772680473, 130, 1680162051, 0, '2026-04-27 08:24:13'),
(328, '2026-04-25', 1772680473, 130, 1680162049, 0, '2026-04-27 08:24:13'),
(329, '2026-04-26', 1772680473, 130, 1680162050, 0, '2026-04-27 08:24:13'),
(330, '2026-04-01', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:48'),
(331, '2026-04-02', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:48'),
(332, '2026-04-03', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:48'),
(333, '2026-04-04', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:48'),
(334, '2026-04-05', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:48'),
(335, '2026-04-06', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:48'),
(336, '2026-04-07', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:48'),
(337, '2026-04-08', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:48'),
(338, '2026-04-09', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:48'),
(339, '2026-04-10', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:48'),
(340, '2026-04-11', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:48'),
(341, '2026-04-12', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:48'),
(342, '2026-04-13', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:48'),
(343, '2026-04-14', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:48'),
(344, '2026-04-15', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:48'),
(345, '2026-04-16', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:48'),
(346, '2026-04-17', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:48'),
(347, '2026-04-18', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:48'),
(348, '2026-04-19', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:48'),
(349, '2026-04-20', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:48'),
(350, '2026-04-21', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:48'),
(351, '2026-04-22', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:48'),
(352, '2026-04-23', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:48'),
(353, '2026-04-24', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:48'),
(354, '2026-04-25', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:48'),
(355, '2026-04-26', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:48'),
(356, '2026-04-27', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:48'),
(357, '2026-04-28', 1772680474, 109, 1680162049, 0, '2026-04-27 08:24:49'),
(358, '2026-04-29', 1772680474, 109, 1680162050, 0, '2026-04-27 08:24:49'),
(359, '2026-04-30', 1772680474, 109, 1680162051, 0, '2026-04-27 08:24:49');

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
  `create_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Dumping data for table `ven_user`
--

INSERT INTO `ven_user` (`id`, `user_id`, `order_num`, `ven_name_sub_id`, `comment`, `create_at`) VALUES
(719, '1680162049', 1, 130, '', '2025-09-02 01:03:12'),
(720, '1680162050', 2, 130, '', '2025-09-02 01:03:16'),
(721, '1680162051', 3, 130, '', '2025-09-02 01:03:20'),
(722, '1680162049', 0, 113, '', '2025-12-16 11:12:55'),
(723, '1680162050', 0, 113, '', '2025-12-16 11:12:59'),
(724, '1680162051', 0, 113, '', '2025-12-16 11:13:03'),
(725, '1680162049', 0, 109, '', '2025-12-16 11:13:44'),
(726, '1680162050', 0, 109, '', '2025-12-16 11:13:47'),
(727, '1680162051', 0, 109, '', '2025-12-16 11:13:50'),
(728, '1680162056', 1, 115, NULL, NULL),
(729, '1680162053', 3, 115, NULL, NULL),
(730, '1680162055', 2, 115, NULL, NULL),
(731, '1680162056', NULL, 133, NULL, NULL),
(732, '1680162056', NULL, 117, NULL, NULL),
(733, '1680162054', NULL, 117, NULL, NULL),
(734, '1680162052', NULL, 117, NULL, NULL),
(735, '1680162051', NULL, 117, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dep`
--
ALTER TABLE `dep`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fname`
--
ALTER TABLE `fname`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `group`
--
ALTER TABLE `group`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `line`
--
ALTER TABLE `line`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `profile`
--
ALTER TABLE `profile`
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `user_id_2` (`user_id`);

--
-- Indexes for table `sign_name`
--
ALTER TABLE `sign_name`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `password_reset_token` (`password_reset_token`);

--
-- Indexes for table `ven`
--
ALTER TABLE `ven`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ven_date` (`ven_date`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `ven_user`
--
ALTER TABLE `ven_user`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dep`
--
ALTER TABLE `dep`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `fname`
--
ALTER TABLE `fname`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `group`
--
ALTER TABLE `group`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `line`
--
ALTER TABLE `line`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `sign_name`
--
ALTER TABLE `sign_name`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `ven`
--
ALTER TABLE `ven`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1772680493;

--
-- AUTO_INCREMENT for table `ven_change`
--
ALTER TABLE `ven_change`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ven_com`
--
ALTER TABLE `ven_com`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1772680475;

--
-- AUTO_INCREMENT for table `ven_name`
--
ALTER TABLE `ven_name`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `ven_name_sub`
--
ALTER TABLE `ven_name_sub`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `ven_schedule`
--
ALTER TABLE `ven_schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=360;

--
-- AUTO_INCREMENT for table `ven_user`
--
ALTER TABLE `ven_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=736;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
