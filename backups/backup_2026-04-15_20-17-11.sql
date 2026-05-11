-- Database Backup (Pure PHP)
-- Generated: 2026-04-15 20:17:11

SET FOREIGN_KEY_CHECKS=0;
SET TIME_ZONE='+00:00';

DROP TABLE IF EXISTS `advertisements`;
CREATE TABLE `advertisements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` text DEFAULT NULL,
  `link_url` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `media_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `total_debt` decimal(10,2) DEFAULT 0.00,
  `debt_limit` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_date` date DEFAULT curdate(),
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` varchar(100) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `leftovers`;
CREATE TABLE `leftovers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_date` date NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `qat_type_id` int(11) DEFAULT NULL,
  `unit_type` enum('weight','قبضة','قرطاس') NOT NULL DEFAULT 'weight',
  `weight_kg` decimal(10,2) NOT NULL,
  `quantity_units` int(11) NOT NULL DEFAULT 0,
  `status` enum('Pending','Dropped','Transferred_Next_Day','Sold','Auto_Momsi','Auto_Dropped','Momsi_Day_1','Momsi_Day_2','Closed') DEFAULT 'Pending',
  `decision_date` date DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `life_day` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `qat_type_id` (`qat_type_id`),
  CONSTRAINT `leftovers_ibfk_1` FOREIGN KEY (`qat_type_id`) REFERENCES `qat_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_date` date DEFAULT curdate(),
  `customer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Transfer') DEFAULT 'Cash',
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `providers`;
CREATE TABLE `providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `purchases`;
CREATE TABLE `purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_date` date NOT NULL,
  `qat_type_id` int(11) DEFAULT NULL,
  `source_weight_grams` decimal(10,2) DEFAULT 0.00,
  `received_weight_grams` decimal(10,2) DEFAULT 0.00,
  `provider_id` int(11) DEFAULT NULL,
  `expected_quantity_kg` decimal(10,2) DEFAULT 0.00,
  `vendor_name` varchar(100) DEFAULT NULL,
  `agreed_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `price_per_kilo` decimal(10,2) DEFAULT 0.00,
  `unit_type` enum('weight','قبضة','قرطاس') NOT NULL DEFAULT 'weight',
  `source_units` int(11) NOT NULL DEFAULT 0,
  `received_units` int(11) DEFAULT 0,
  `price_per_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `net_cost` decimal(10,2) GENERATED ALWAYS AS (`agreed_price` - `discount`) STORED,
  `quantity_kg` decimal(10,2) DEFAULT NULL,
  `status` enum('Fresh','Momsi','Closed') DEFAULT 'Fresh',
  `media_path` varchar(255) DEFAULT NULL,
  `is_received` tinyint(1) DEFAULT 1,
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `original_purchase_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qat_type_id` (`qat_type_id`),
  KEY `idx_purchases_date` (`purchase_date`),
  KEY `fk_original_purchase` (`original_purchase_id`),
  CONSTRAINT `fk_original_purchase` FOREIGN KEY (`original_purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`qat_type_id`) REFERENCES `qat_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `qat_deposits`;
CREATE TABLE `qat_deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deposit_date` date NOT NULL,
  `currency` enum('YER','SAR','USD') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `recipient` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `qat_types`;
CREATE TABLE `qat_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `unit_type` enum('weight','units') DEFAULT 'weight',
  `description` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `media_path` varchar(255) DEFAULT NULL,
  `price_weight` decimal(10,2) DEFAULT 0.00,
  `price_qabdah` decimal(10,2) DEFAULT 0.00,
  `price_qartas` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `qat_types` VALUES ('1', 'جمام نقوة', 'weight', NULL, '0', 'uploads/1776254750_Screenshot 2026-01-28 230630.png', '0.00', '0.00', '0.00'),
('2', 'جمام كالف', 'weight', NULL, '0', 'uploads/1771796307_WIN_20250204_08_35_49_Pro.jpg', '0.00', '0.00', '0.00'),
('3', 'جمام سمين', 'weight', NULL, '0', 'uploads/1776008820_Gemini_Generated_Image_878fw8878fw8878f.png', '0.00', '0.00', '0.00'),
('4', 'جمام قصار', 'weight', NULL, '0', NULL, '0.00', '0.00', '0.00'),
('5', 'صدور نقوة', 'weight', NULL, '0', NULL, '0.00', '0.00', '0.00'),
('6', 'صدور عادي', 'weight', NULL, '0', NULL, '0.00', '0.00', '0.00'),
('7', 'قطل', 'weight', NULL, '0', NULL, '0.00', '0.00', '0.00'),
('95', 'QA_TEST Qat KG', 'weight', '', '0', NULL, '0.00', '0.00', '0.00'),
('96', 'QA_TEST Qat Unit', 'units', '', '0', NULL, '0.00', '0.00', '0.00');

DROP TABLE IF EXISTS `refunds`;
CREATE TABLE `refunds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `refund_type` enum('Cash','Debt') NOT NULL,
  `unit_type` enum('weight','units') DEFAULT 'weight',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `weight_kg` decimal(10,3) DEFAULT 0.000,
  `quantity_units` int(11) DEFAULT 0,
  `sale_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_date` date DEFAULT curdate(),
  `due_date` date DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `qat_type_id` int(11) DEFAULT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `leftover_id` int(11) DEFAULT NULL,
  `qat_status` enum('Tari','Momsi','Leftover','Leftover1','Leftover2') DEFAULT 'Tari',
  `weight_grams` decimal(10,2) NOT NULL,
  `weight_kg` decimal(10,3) GENERATED ALWAYS AS (`weight_grams` / 1000) STORED,
  `unit_type` enum('weight','قبضة','قرطاس','units') NOT NULL DEFAULT 'weight',
  `quantity_units` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('Cash','Debt','Internal Transfer','Kuraimi Deposit','Jayb Deposit') NOT NULL,
  `transfer_sender` varchar(100) DEFAULT NULL,
  `transfer_receiver` varchar(100) DEFAULT NULL,
  `transfer_number` varchar(100) DEFAULT NULL,
  `transfer_company` varchar(100) DEFAULT NULL,
  `is_paid` tinyint(1) DEFAULT 1,
  `is_returned` tinyint(1) DEFAULT 0,
  `debt_type` enum('Daily','Monthly','Yearly','Deferred') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `qat_type_id` (`qat_type_id`),
  KEY `idx_sales_date` (`sale_date`),
  KEY `fk_sales_leftover` (`leftover_id`),
  CONSTRAINT `fk_sales_leftover` FOREIGN KEY (`leftover_id`) REFERENCES `leftovers` (`id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`qat_type_id`) REFERENCES `qat_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `staff`;
CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `daily_salary` decimal(10,2) DEFAULT 0.00,
  `withdrawal_limit` decimal(10,2) DEFAULT NULL,
  `base_salary` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `unknown_transfers`;
CREATE TABLE `unknown_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_date` date NOT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `sender_name` varchar(100) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `currency` varchar(5) DEFAULT 'YER',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','super_admin','user') NOT NULL,
  `sub_role` varchar(50) DEFAULT 'full',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('1', 'super admin', 'null', 'null', '123456', 'super_admin', 'full', '2026-01-19 22:20:42'),
('3', 'admin1', 'abdulqawi mohammed', '0772166545', '$2y$10$nXfGuul5Yo08xojUUJvLWOpfz6wf/kIqqe1m0haDVUko68f90jo3e', 'user', 'full', '2026-02-21 23:52:31'),
('4', 'abood', 'عبد القوي', '7721665', '$2y$10$jQXWWPSW7HtQZqVT4Z9dnO6kajO0xLODBOv2LGgJ3i1ZJiWPnWbmy', 'user', 'full', '2026-02-23 00:31:51'),
('5', 'Abdul', NULL, NULL, '$2y$10$QWHZUCDODeXxdsh.ydGcIeSUBatiqlEw8BL37XjKhWcCUpaOtcVz2', 'super_admin', 'reports', '2026-02-23 14:14:39'),
('6', 'Mohammed', NULL, NULL, '$2y$10$8htdjtFEYV9Ryopnbn9L9u5XI1m7MGitzJG8TYUnBWO772166545', 'super_admin', 'full', '2026-02-23 14:14:39'),
('7', 'Abdullah', NULL, NULL, '$2y$10$4.8UPgvvvzK1rPXuBsLSxO07K1d8c9ZFcBFLByMiE2Q/1hZqC9XEy', 'super_admin', 'sales_debts', '2026-02-23 14:14:39'),
('8', 'Aham', NULL, NULL, '$2y$10$LQC/gZsvzoLh.uoygVIod.j4X1gdKNmx9YQghJhyF8K9uLBXc4RlG', 'super_admin', 'receiving', '2026-02-23 14:14:39'),
('9', '202310400240', 'عبد القوي', 'admin', '$2y$10$6y9FA.fHNOOW67yxfDJqrOWqbv5Zsn.N5NwUea386tpm4hddFXs7.', 'user', 'full', '2026-02-27 22:31:06'),
('10', '2023104002', 'عبد القوي', 'admin', '$2y$10$Eo9GytjYblfsobZBHNH8venN/tTOK31WrWPq0I0hraHgYW8QfUgvG', 'user', 'full', '2026-02-27 22:32:52'),
('11', 'ali', 'عبد القوي', 'admin', '$2y$10$KqRzLNxGspTw/63AsyNCl.lOKGGa79gHwPnLp9RSkTeoABAwpC2mO', 'user', 'full', '2026-02-27 22:33:54'),
('12', 'ad', 'عبد القوي', 'test', '$2y$10$MsoGM/z62ATebE2YGKCmg.BRu3QKVRK3/pv9iV6DGgUC4hC0eVhJq', 'user', 'full', '2026-03-02 13:48:29'),
('14', 'four', NULL, NULL, '$2y$10$H/cgwxYUh2P3yOH36aAdH.WzTzVZBawG7RHdjDJ8Hn0rpHaK7xDAC', 'user', 'full', '2026-03-04 17:10:45'),
('15', 'three', NULL, NULL, '$2y$10$H/cgwxYUh2P3yOH36aAdH.WzTzVZBawG7RHdjDJ8Hn0rpHaK7xDAC', 'user', 'full', '2026-03-04 17:10:45'),
('16', 'two', NULL, NULL, '$2y$10$H/cgwxYUh2P3yOH36aAdH.WzTzVZBawG7RHdjDJ8Hn0rpHaK7xDAC', 'user', 'full', '2026-03-04 17:10:45'),
('17', 'one', NULL, NULL, '$2y$10$H/cgwxYUh2P3yOH36aAdH.WzTzVZBawG7RHdjDJ8Hn0rpHaK7xDAC', 'user', 'full', '2026-03-04 17:10:45'),
('19', 'test_sourcing_admin', NULL, NULL, '$2y$10$lzAb7mowXYO64YLBj4Uk9ODIeyR.hHmSByj6jp2k6oKZDQJEZhvUm', 'admin', 'full', '2026-03-07 12:11:03'),
('21', 'super', 'عبد القوي', '0772166545', '$2y$10$zWqyjEI/1VOvs88GCR7Q7u0WeSzk733CCzWqSdg1MTBB1VtPp5sue', 'super_admin', 'verifier', '2026-03-07 12:22:29'),
('22', 'sales', 'عبد القوي', '0772166545', '$2y$10$8htdjtFEYV9Ryopnbn9L9u5XI1m7MGitzJG8TYUnBWOuW5OBi5pZS', 'super_admin', 'seller', '2026-03-07 12:25:00'),
('23', 'accountant', 'عبد القوي', '0772166545', '$2y$10$tqEmBeZU.0HPglSELrd2pecXVjZea6/1rwzP7z.YuHDPifSLhojaa', 'super_admin', 'accountant', '2026-03-07 12:32:27'),
('24', 'partner', 'عبد القوي', '0772166545', '$2y$10$fwTIGz3oVCcpWloStSEsxO5R5B6trKGCorx03jnxwhjOcHXDTH.8m', 'super_admin', 'partner', '2026-03-07 12:37:07'),
('25', 'test', 'test', '772166545', '$2y$10$6sBEYOlEmwgO/zH91dfFveoY6O6fjK4xiRM1D1IQdsT7shlZ7r3Ym', 'super_admin', 'partner', '2026-03-07 12:41:51'),
('26', 'moha', 'mohammed', '772166545', '$2y$10$mMPFKu.yGPxa2OADERU1relcz/RXESHbFSuvPvGK2ditEcaDQ.n2e', 'super_admin', 'full', '2026-03-07 16:49:04'),
('27', '', NULL, NULL, '', 'super_admin', 'full', '2026-03-08 15:17:33'),
('31', 'admin', 'admin', NULL, '$2y$10$Q6eryJl/c7NpXLr5dey9j.6s2vCWCw9.5hc6zZZLEV9UMKdWg.1Wa', 'admin', 'full', '2026-03-08 22:21:26'),
('32', 'superadmin', 'superadmin', NULL, '$2y$10$VmbDnpsvfPT5iF2WcVg2jexJn/5Ub7sxKqG70QExxaHXlbNtOJqk2', 'super_admin', 'full', '2026-03-08 22:21:26'),
('33', 'est', 'test', '0772166545', '$2y$10$nlu2UNdumm8JbdSZM.zdMuljC2nhzGdxHAL.k2J9jjHTy2TlpnJzm', 'super_admin', 'verifier', '2026-03-09 20:08:27');

SET FOREIGN_KEY_CHECKS=1;
