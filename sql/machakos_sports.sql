-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 23, 2026 at 12:02 PM
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
-- Database: `machakos_sports`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_dashboard_stats` ()   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM players) AS total_players,
        (SELECT COUNT(DISTINCT team) FROM players) AS total_teams,
        (SELECT COUNT(*) FROM items) AS total_items,
        (SELECT COUNT(*) FROM player_items) AS items_issued,
        (SELECT COUNT(*) FROM player_items WHERE is_returned = 0) AS outstanding_items,
        (SELECT COUNT(DISTINCT player_id) FROM player_items WHERE is_returned = 0) AS players_with_outstanding;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `username`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'admin', 'login', 'User \'admin\' logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 08:09:21'),
(2, 1, 'admin', 'item_issued', 'Issued item to player: Mary Wanjiru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 08:10:41'),
(3, 1, 'admin', 'item_returned', 'Item returned by player: Mary Wanjiru', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 08:10:45'),
(4, 1, 'admin', 'login', 'User \'admin\' logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 08:40:40'),
(5, 1, 'admin', 'item_returned', 'Item returned by player: Jane Akinyi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 08:56:17'),
(6, 1, 'admin', 'login', 'User \'admin\' logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 09:50:27'),
(7, 1, 'admin', 'item_returned', 'Item returned by player: Jane Akinyi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 09:56:56'),
(8, 1, 'admin', 'user_added', 'Added new user: staff3 (admin)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 09:58:01'),
(9, 1, 'admin', 'user_deleted', 'Deleted user ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', '2026-06-23 09:58:21');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `item_category` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0 CHECK (`quantity` >= 0),
  `description` text DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `condition` enum('New','Good','Fair','Poor') DEFAULT 'Good',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `item_name`, `item_category`, `quantity`, `description`, `serial_number`, `purchase_date`, `condition`, `created_at`, `updated_at`) VALUES
(1, 'Football (Match)', 'Balls', 11, 'Size 5 match quality football', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-23 08:56:17'),
(2, 'Football (Training)', 'Balls', 15, 'Size 5 training quality football', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-22 18:06:39'),
(3, 'Basketball', 'Balls', 9, 'Size 7 regulation basketball', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-23 09:56:56'),
(4, 'Volleyball', 'Balls', 6, 'Size 5 regulation volleyball', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-22 18:06:39'),
(5, 'Home Jersey', 'Uniforms', 25, 'Red and white home kit', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-22 18:06:39'),
(6, 'Away Jersey', 'Uniforms', 25, 'Blue and white away kit', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-22 18:06:39'),
(7, 'Shin Guards', 'Safety', 20, 'Player shin protection', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-22 18:06:39'),
(8, 'Goal Keeper Gloves', 'Safety', 6, 'Match quality gloves', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-22 18:06:39'),
(9, 'Training Cones', 'Equipment', 30, 'Set of training cones', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-23 08:10:45'),
(10, 'Practice Bibs', 'Equipment', 20, 'Training vests', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-22 18:06:39'),
(11, 'First Aid Kit', 'Safety', 3, 'Complete first aid supplies', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-23 08:10:25'),
(12, 'Whistles', 'Equipment', 10, 'Referee whistles', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-22 18:06:39'),
(13, 'Stopwatch', 'Equipment', 5, 'Digital stopwatches', NULL, NULL, 'Good', '2026-06-22 18:06:39', '2026-06-22 18:06:39');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `date_of_birth` date NOT NULL,
  `faculty` varchar(100) NOT NULL,
  `course` varchar(100) NOT NULL,
  `year_of_study` int(11) NOT NULL CHECK (`year_of_study` between 1 and 6),
  `team` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `registered_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `profile_image` varchar(255) DEFAULT 'default.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `student_id`, `full_name`, `gender`, `date_of_birth`, `faculty`, `course`, `year_of_study`, `team`, `phone`, `email`, `registration_date`, `registered_by`, `is_active`, `profile_image`) VALUES
(1, 'VD19-5803-2023', 'John Maina', 'Male', '2003-05-15', 'School of Engineering', 'Computer Science', 3, 'Football', '0712345678', 'john.maina@student.machakos.ac.ke', '2026-06-22 21:06:39', NULL, 1, 'default.png'),
(2, 'VD19-2117-2022', 'Jane Akinyi', 'Female', '2004-07-20', 'School of Business', 'Business Management', 2, 'Basketball', '0723456789', 'jane.akinyi@student.machakos.ac.ke', '2026-06-22 21:06:39', NULL, 1, 'default.png'),
(3, 'VD19-4521-2023', 'Peter Ochieng', 'Male', '2003-03-10', 'School of Engineering', 'Electrical Engineering', 3, 'Volleyball', '0734567890', 'peter.ochieng@student.machakos.ac.ke', '2026-06-22 21:06:39', NULL, 1, 'default.png'),
(4, 'VD19-7890-2024', 'Mary Wanjiru', 'Female', '2005-09-25', 'School of Education', 'Education', 1, 'Netball', '0745678901', 'mary.wanjiru@student.machakos.ac.ke', '2026-06-22 21:06:39', NULL, 1, 'default.png'),
(5, 'VD19-3456-2022', 'James Mwangi', 'Male', '2002-11-30', 'School of Business', 'Accounting', 4, 'Rugby', '0756789012', 'james.mwangi@student.machakos.ac.ke', '2026-06-22 21:06:39', NULL, 1, 'default.png');

-- --------------------------------------------------------

--
-- Table structure for table `player_items`
--

CREATE TABLE `player_items` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `date_issued` datetime DEFAULT current_timestamp(),
  `issued_by` int(11) DEFAULT NULL,
  `is_returned` tinyint(1) DEFAULT 0,
  `date_returned` datetime DEFAULT NULL,
  `return_condition` enum('Good','Fair','Poor','Damaged','Lost') DEFAULT 'Good',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `player_items`
--

INSERT INTO `player_items` (`id`, `player_id`, `item_id`, `date_issued`, `issued_by`, `is_returned`, `date_returned`, `return_condition`, `notes`) VALUES
(1, 1, 3, '2026-05-31 21:06:39', 1, 0, NULL, 'Good', NULL),
(2, 2, 3, '2026-06-17 21:06:39', 1, 1, '2026-06-23 12:56:56', 'Good', NULL),
(3, 3, 3, '2026-05-29 21:06:39', 1, 1, NULL, 'Good', NULL),
(4, 1, 1, '2026-06-01 21:06:39', 1, 0, NULL, 'Good', NULL),
(5, 2, 1, '2026-06-19 21:06:39', 1, 1, '2026-06-23 11:56:17', 'Good', NULL),
(8, 1, 11, '2026-06-23 11:10:25', 1, 0, NULL, 'Good', NULL),
(9, 4, 9, '2026-06-23 11:10:41', 1, 1, '2026-06-23 11:10:45', 'Good', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sport` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_matches`
--

CREATE TABLE `tournament_matches` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `team1_id` int(11) NOT NULL,
  `team2_id` int(11) NOT NULL,
  `match_date` datetime NOT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `team1_score` int(11) DEFAULT 0,
  `team2_score` int(11) DEFAULT 0,
  `winner_id` int(11) DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_teams`
--

CREATE TABLE `tournament_teams` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `team_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@machakos.ac.ke', 'admin', 1, NULL, '2026-06-22 18:06:39'),
(2, 'staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sports Coordinator', 'coordinator@machakos.ac.ke', 'staff', 1, NULL, '2026-06-22 18:06:39');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_item_usage`
-- (See below for the actual view)
--
CREATE TABLE `view_item_usage` (
`item_id` int(11)
,`item_name` varchar(100)
,`item_category` varchar(50)
,`current_stock` int(11)
,`total_issued` bigint(21)
,`currently_outstanding` decimal(22,0)
,`available_stock` decimal(23,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_player_clearance`
-- (See below for the actual view)
--
CREATE TABLE `view_player_clearance` (
`player_id` int(11)
,`student_id` varchar(50)
,`full_name` varchar(100)
,`team` varchar(50)
,`total_items` bigint(21)
,`outstanding_items` decimal(22,0)
,`returned_items` decimal(22,0)
,`clearance_status` varchar(8)
);

-- --------------------------------------------------------

--
-- Structure for view `view_item_usage`
--
DROP TABLE IF EXISTS `view_item_usage`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_item_usage`  AS SELECT `i`.`id` AS `item_id`, `i`.`item_name` AS `item_name`, `i`.`item_category` AS `item_category`, `i`.`quantity` AS `current_stock`, count(`pi`.`id`) AS `total_issued`, sum(case when `pi`.`is_returned` = 0 then 1 else 0 end) AS `currently_outstanding`, `i`.`quantity`- sum(case when `pi`.`is_returned` = 0 then 1 else 0 end) AS `available_stock` FROM (`items` `i` left join `player_items` `pi` on(`i`.`id` = `pi`.`item_id`)) GROUP BY `i`.`id`, `i`.`item_name`, `i`.`item_category`, `i`.`quantity` ;

-- --------------------------------------------------------

--
-- Structure for view `view_player_clearance`
--
DROP TABLE IF EXISTS `view_player_clearance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_player_clearance`  AS SELECT `p`.`id` AS `player_id`, `p`.`student_id` AS `student_id`, `p`.`full_name` AS `full_name`, `p`.`team` AS `team`, count(`pi`.`id`) AS `total_items`, sum(case when `pi`.`is_returned` = 0 then 1 else 0 end) AS `outstanding_items`, sum(case when `pi`.`is_returned` = 1 then 1 else 0 end) AS `returned_items`, CASE WHEN count(`pi`.`id`) = 0 THEN 'No Items' WHEN sum(case when `pi`.`is_returned` = 0 then 1 else 0 end) = 0 THEN 'Cleared' ELSE 'Pending' END AS `clearance_status` FROM (`players` `p` left join `player_items` `pi` on(`p`.`id` = `pi`.`player_id`)) GROUP BY `p`.`id`, `p`.`student_id`, `p`.`full_name`, `p`.`team` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_activity_log_created` (`created_at`),
  ADD KEY `idx_activity_log_user_action` (`user_id`,`action`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_name` (`item_name`),
  ADD KEY `idx_category` (`item_category`),
  ADD KEY `idx_quantity` (`quantity`),
  ADD KEY `idx_items_quantity` (`quantity`,`item_category`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_success` (`success`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_team` (`team`),
  ADD KEY `idx_faculty` (`faculty`),
  ADD KEY `idx_registered_by` (`registered_by`),
  ADD KEY `idx_players_team_status` (`team`,`is_active`);

--
-- Indexes for table `player_items`
--
ALTER TABLE `player_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_player_id` (`player_id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_is_returned` (`is_returned`),
  ADD KEY `idx_date_issued` (`date_issued`),
  ADD KEY `issued_by` (`issued_by`),
  ADD KEY `idx_player_items_returned` (`player_id`,`is_returned`),
  ADD KEY `idx_player_items_dates` (`date_issued`,`date_returned`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sport` (`sport`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `tournament_matches`
--
ALTER TABLE `tournament_matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tournament` (`tournament_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `team1_id` (`team1_id`),
  ADD KEY `team2_id` (`team2_id`);

--
-- Indexes for table `tournament_teams`
--
ALTER TABLE `tournament_teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tournament` (`tournament_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `player_items`
--
ALTER TABLE `player_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournament_matches`
--
ALTER TABLE `tournament_matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournament_teams`
--
ALTER TABLE `tournament_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `players_ibfk_1` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `player_items`
--
ALTER TABLE `player_items`
  ADD CONSTRAINT `player_items_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `player_items_ibfk_3` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD CONSTRAINT `tournaments_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tournament_matches`
--
ALTER TABLE `tournament_matches`
  ADD CONSTRAINT `tournament_matches_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_matches_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `tournament_teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_matches_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `tournament_teams` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_teams`
--
ALTER TABLE `tournament_teams`
  ADD CONSTRAINT `tournament_teams_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
