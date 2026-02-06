-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 06, 2026 at 10:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gym_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `check_in_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `member_id`, `check_in_time`, `created_at`) VALUES
(1, 9, '14:02:04', '2026-01-09 04:02:04');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `membership_type` enum('single','double','walk-in','annual') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired') NOT NULL DEFAULT 'active',
  `total_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `user_id`, `name`, `email`, `phone`, `gender`, `address`, `membership_type`, `start_date`, `end_date`, `status`, `total_paid`, `created_at`) VALUES
(1, NULL, 'Rachel Animwaa Asiamah', 'rachelasiamah922@gmail.com', '0507017355', 'female', 'Ablekuma Abase Road', 'single', '2025-10-26', '2025-11-25', 'expired', 350.00, '2025-10-28 22:54:50'),
(2, NULL, 'Mike Black', 'mike01@gmail.com', '0500549683', 'male', 'Tabora High Tension', 'double', '2025-10-28', '2025-12-27', 'expired', 550.00, '2025-10-28 23:49:45'),
(8, NULL, 'Daniel Adom', 'danny123@gmail.com', '0123094857', 'male', 'SCC', 'double', '2025-12-12', '2026-02-10', 'active', 900.00, '2025-11-05 14:54:49'),
(9, NULL, 'Prince Agyei', 'princeagyei123@gmail.com', '0244682239', 'male', 'Dansoman', 'single', '2025-11-08', '2025-12-08', 'expired', 350.00, '2025-11-06 15:42:16');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `plan` enum('single','double','walk-in','annual') NOT NULL,
  `amount` decimal(10,2) NOT NULL COMMENT 'base plan cost',
  `registration_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(12,2) GENERATED ALWAYS AS (`amount` + `registration_fee`) STORED,
  `payment_date` datetime DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `member_id`, `plan`, `amount`, `registration_fee`, `payment_date`, `note`) VALUES
(1, 1, 'single', 250.00, 100.00, '2025-10-28 22:54:50', NULL),
(2, 2, 'double', 450.00, 100.00, '2025-10-28 23:49:45', NULL),
(12, 8, 'single', 250.00, 100.00, '2025-11-05 14:54:49', NULL),
(14, 9, 'single', 250.00, 100.00, '2025-11-06 15:42:16', NULL),
(16, 8, 'double', 450.00, 100.00, '2025-12-12 13:44:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `plan_key` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `plan_key`, `title`, `duration_days`, `price`, `created_at`) VALUES
(1, 'single', 'Single (1 month)', 30, 250.00, '2025-10-28 22:25:19'),
(2, 'double', 'Double (2 months)', 60, 450.00, '2025-10-28 22:25:19'),
(3, 'walk-in', 'Walk-in (1 day)', 1, 20.00, '2025-10-28 22:25:19'),
(4, 'annual', 'Annual (12 months)', 365, 2500.00, '2025-10-28 22:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','member') NOT NULL DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Super Admin', 'admin@example.com', '$2y$10$Gly3dmD7prWoNPCiXbekPuWB2KoSTYcyF8VtrVVTGTNdzI3qd4lwq', 'admin', '2025-10-28 22:32:39'),
(2, 'Mike Black', 'mike01@gmail.com', '$2y$10$L6GiugXkG0F33G/.FBiuW.mEQBL3MqZ.9mO7Ztqcdqjv85bTX0PzS', 'member', '2025-10-28 22:35:04');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_total_revenue`
-- (See below for the actual view)
--
CREATE TABLE `v_total_revenue` (
`total_revenue` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_total_revenue`
--
DROP TABLE IF EXISTS `v_total_revenue`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_total_revenue`  AS SELECT sum(`payments`.`total_paid`) AS `total_revenue` FROM `payments` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_member` (`member_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_members_email` (`email`),
  ADD KEY `idx_members_status` (`status`),
  ADD KEY `idx_members_start_date` (`start_date`),
  ADD KEY `fk_members_user` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_member` (`member_id`),
  ADD KEY `idx_payments_date` (`payment_date`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_key` (`plan_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_attendance_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
