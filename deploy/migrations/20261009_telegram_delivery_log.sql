-- Safe to run on an existing XAMPP database. Do not import database.sql over live data.
CREATE TABLE IF NOT EXISTS `telegram_delivery_log` (
  `send_date` date NOT NULL,
  `send_time` time NOT NULL,
  `notify_day` tinyint NOT NULL,
  `target_date` date NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'started',
  `message_count` int NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`send_date`, `send_time`, `notify_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
