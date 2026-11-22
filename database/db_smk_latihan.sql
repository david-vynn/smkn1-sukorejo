-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2026 at 03:31 AM
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
-- Database: `db_smk_latihan`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `name` varchar(9) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('draft','active','completed') NOT NULL DEFAULT 'draft',
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `name`, `start_date`, `end_date`, `status`, `is_current`, `created_at`) VALUES
(1, '2026/2027', '2026-07-01', '2027-06-30', 'active', 1, '2026-11-03 01:32:37');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `major_id` int(11) NOT NULL,
  `grade` enum('X','XI','XII') NOT NULL,
  `class_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `class_name` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `major_id`, `grade`, `class_number`, `class_name`) VALUES
(1, 1, 'X', 1, 'X RPL 1'),
(2, 1, 'X', 2, 'X RPL 2'),
(3, 1, 'XI', 1, 'XI RPL 1'),
(4, 1, 'XI', 2, 'XI RPL 2'),
(5, 1, 'XII', 1, 'XII RPL 1'),
(6, 1, 'XII', 2, 'XII RPL 2'),
(7, 2, 'X', 1, 'X KI 1'),
(8, 2, 'X', 2, 'X KI 2'),
(9, 2, 'X', 3, 'X KI 3'),
(10, 2, 'XI', 1, 'XI KI 1'),
(11, 2, 'XI', 2, 'XI KI 2'),
(12, 2, 'XI', 3, 'XI KI 3'),
(13, 2, 'XII', 1, 'XII KI 1'),
(14, 2, 'XII', 2, 'XII KI 2'),
(15, 2, 'XII', 3, 'XII KI 3'),
(16, 3, 'X', 1, 'X TEI 1'),
(17, 3, 'X', 2, 'X TEI 2'),
(18, 3, 'X', 3, 'X TEI 3'),
(19, 3, 'XI', 1, 'XI TEI 1'),
(20, 3, 'XI', 2, 'XI TEI 2'),
(21, 3, 'XI', 3, 'XI TEI 3'),
(22, 3, 'XII', 1, 'XII TEI 1'),
(23, 3, 'XII', 2, 'XII TEI 2'),
(24, 3, 'XII', 3, 'XII TEI 3'),
(25, 4, 'X', 1, 'X TKR 1'),
(26, 4, 'X', 2, 'X TKR 2'),
(27, 4, 'X', 3, 'X TKR 3'),
(28, 4, 'X', 4, 'X TKR 4'),
(29, 4, 'XI', 1, 'XI TKR 1'),
(30, 4, 'XI', 2, 'XI TKR 2'),
(31, 4, 'XI', 3, 'XI TKR 3'),
(32, 4, 'XI', 4, 'XI TKR 4'),
(33, 4, 'XII', 1, 'XII TKR 1'),
(34, 4, 'XII', 2, 'XII TKR 2'),
(35, 4, 'XII', 3, 'XII TKR 3'),
(36, 4, 'XII', 4, 'XII TKR 4'),
(37, 5, 'X', 1, 'X TAV 1'),
(38, 5, 'XI', 1, 'XI TAV 1'),
(39, 5, 'XII', 1, 'XII TAV 1');

-- --------------------------------------------------------

--
-- Table structure for table `majors`
--

CREATE TABLE `majors` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `majors`
--

INSERT INTO `majors` (`id`, `code`, `name`, `created_at`) VALUES
(1, 'RPL', 'Rekayasa Perangkat Lunak', '2026-11-01 03:08:41'),
(2, 'KI', 'Kimia Industri', '2026-11-01 03:08:41'),
(3, 'TEI', 'Teknik Elektronika Industri', '2026-11-01 03:08:41'),
(4, 'TKR', 'Teknik Kendaraan Ringan', '2026-11-01 03:08:41'),
(5, 'TAV', 'Teknik Audio Video', '2026-11-01 03:08:41');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_code` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `invoice_id`, `cashier_id`, `amount`, `payment_date`, `status`, `created_at`, `transaction_code`) VALUES
(3, 1, 10, 150000.00, '2026-11-04 13:52:34', 'success', '2026-11-04 06:52:34', ''),
(4, 9, 10, 150000.00, '2026-11-04 13:59:38', 'success', '2026-11-04 06:59:38', ''),
(5, 11, 10, 150000.00, '2026-11-04 14:00:30', 'success', '2026-11-04 07:00:30', ''),
(6, 13, 10, 150000.00, '2026-11-04 14:06:11', 'success', '2026-11-04 07:06:11', ''),
(7, 3, 10, 150000.00, '2026-11-04 14:13:53', 'success', '2026-11-04 07:13:53', ''),
(8, 2, 10, 150000.00, '2026-11-04 14:17:18', 'success', '2026-11-04 07:17:18', ''),
(9, 4, 10, 150000.00, '2026-11-04 14:19:10', 'success', '2026-11-04 07:19:10', ''),
(10, 10, 10, 150000.00, '2026-11-04 14:33:19', 'success', '2026-11-04 07:33:19', 'SPP-20261104-9C3639'),
(11, 12, 10, 150000.00, '2026-11-04 14:37:28', 'success', '2026-11-04 07:37:28', 'SPP-20261104-3E7A0A'),
(12, 14, 10, 150000.00, '2026-11-04 14:38:41', 'success', '2026-11-04 07:38:41', 'SPP-20261104-9025A8'),
(13, 5, 10, 150000.00, '2026-11-04 14:39:13', 'success', '2026-11-04 07:39:13', 'SPP-20261104-A421A9'),
(14, 6, 10, 150000.00, '2026-11-04 14:40:12', 'success', '2026-11-04 07:40:12', 'SPP-20261104-95FEBE'),
(15, 7, 10, 150000.00, '2026-11-04 15:00:41', 'success', '2026-11-04 08:00:41', 'SPP-20261104-AA056C');

-- --------------------------------------------------------

--
-- Table structure for table `spp_invoices`
--

CREATE TABLE `spp_invoices` (
  `ID` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL,
  `period_month` tinyint(3) UNSIGNED NOT NULL,
  `period_year` year(4) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('unpaid','overdue','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spp_invoices`
--

INSERT INTO `spp_invoices` (`ID`, `student_id`, `academic_year_id`, `period_month`, `period_year`, `due_date`, `amount`, `status`, `created_at`) VALUES
(1, 1, 1, 11, '2026', '2026-11-30', 150000.00, 'paid', '2026-11-03 22:01:07'),
(2, 1, 1, 12, '2026', '2026-12-31', 150000.00, 'paid', '2026-11-03 22:01:07'),
(3, 1, 1, 1, '2027', '2027-01-31', 150000.00, 'paid', '2026-11-03 22:01:07'),
(4, 1, 1, 2, '2027', '2027-02-28', 150000.00, 'paid', '2026-11-03 22:01:07'),
(5, 1, 1, 3, '2027', '2027-03-31', 150000.00, 'paid', '2026-11-03 22:01:07'),
(6, 1, 1, 4, '2027', '2027-04-30', 150000.00, 'paid', '2026-11-03 22:01:07'),
(7, 1, 1, 5, '2027', '2027-05-31', 150000.00, 'paid', '2026-11-03 22:01:07'),
(8, 1, 1, 6, '2027', '2027-06-30', 150000.00, 'unpaid', '2026-11-03 22:01:07'),
(9, 2, 1, 11, '2026', '2026-11-30', 150000.00, 'paid', '2026-11-03 22:01:07'),
(10, 2, 1, 12, '2026', '2026-12-31', 150000.00, 'paid', '2026-11-03 22:01:07'),
(11, 2, 1, 1, '2027', '2027-01-31', 150000.00, 'paid', '2026-11-03 22:01:07'),
(12, 2, 1, 2, '2027', '2027-02-28', 150000.00, 'paid', '2026-11-03 22:01:07'),
(13, 2, 1, 3, '2027', '2027-03-31', 150000.00, 'paid', '2026-11-03 22:01:07'),
(14, 2, 1, 4, '2027', '2027-04-30', 150000.00, 'paid', '2026-11-03 22:01:07'),
(15, 2, 1, 5, '2027', '2027-05-31', 150000.00, 'unpaid', '2026-11-03 22:01:07'),
(16, 2, 1, 6, '2027', '2027-06-30', 150000.00, 'unpaid', '2026-11-03 22:01:07'),
(17, 3, 1, 11, '2026', '2026-11-30', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(18, 3, 1, 12, '2026', '2026-12-31', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(19, 3, 1, 1, '2027', '2027-01-31', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(20, 3, 1, 2, '2027', '2027-02-28', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(21, 3, 1, 3, '2027', '2027-03-31', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(22, 3, 1, 4, '2027', '2027-04-30', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(23, 3, 1, 5, '2027', '2027-05-31', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(24, 3, 1, 6, '2027', '2027-06-30', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(25, 4, 1, 11, '2026', '2026-11-30', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(26, 4, 1, 12, '2026', '2026-12-31', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(27, 4, 1, 1, '2027', '2027-01-31', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(28, 4, 1, 2, '2027', '2027-02-28', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(29, 4, 1, 3, '2027', '2027-03-31', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(30, 4, 1, 4, '2027', '2027-04-30', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(31, 4, 1, 5, '2027', '2027-05-31', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(32, 4, 1, 6, '2027', '2027-06-30', 175000.00, 'unpaid', '2026-11-03 22:01:07'),
(33, 5, 1, 11, '2026', '2026-11-30', 200000.00, 'unpaid', '2026-11-03 22:01:07'),
(34, 5, 1, 12, '2026', '2026-12-31', 200000.00, 'unpaid', '2026-11-03 22:01:07'),
(35, 5, 1, 1, '2027', '2027-01-31', 200000.00, 'unpaid', '2026-11-03 22:01:07'),
(36, 5, 1, 2, '2027', '2027-02-28', 200000.00, 'unpaid', '2026-11-03 22:01:07'),
(37, 5, 1, 3, '2027', '2027-03-31', 200000.00, 'unpaid', '2026-11-03 22:01:07'),
(38, 5, 1, 4, '2027', '2027-04-30', 200000.00, 'unpaid', '2026-11-03 22:01:07'),
(39, 5, 1, 5, '2027', '2027-05-31', 200000.00, 'unpaid', '2026-11-03 22:01:07'),
(40, 5, 1, 6, '2027', '2027-06-30', 200000.00, 'unpaid', '2026-11-03 22:01:07');

-- --------------------------------------------------------

--
-- Table structure for table `spp_tariffs`
--

CREATE TABLE `spp_tariffs` (
  `id` int(11) NOT NULL,
  `grade` enum('X','XI','XII') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spp_tariffs`
--

INSERT INTO `spp_tariffs` (`id`, `grade`, `amount`, `created_at`) VALUES
(1, 'X', 150000.00, '2026-11-03 01:29:20'),
(2, 'XI', 175000.00, '2026-11-03 01:29:20'),
(3, 'XII', 200000.00, '2026-11-03 01:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `class_id` int(11) NOT NULL,
  `nisn` varchar(10) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `class_id`, `nisn`, `nama`, `created_at`) VALUES
(1, 3, 1, '0098765432', 'Andi Saputra', '2026-11-01 04:13:44'),
(2, 4, 2, '0098765433', 'Budi Santoso', '2026-11-01 04:13:44'),
(3, NULL, 3, '0098765434', 'Citra Lestari', '2026-11-01 04:13:44'),
(4, NULL, 19, '0098765435', 'Dimas Pratama', '2026-11-01 04:13:44'),
(5, NULL, 33, '0098765436', 'Eka Putri', '2026-11-01 04:13:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','kasir','siswa') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'David', 'davidalvinmaulana@gmail.com', '$2y$10$nk6bLBtRq1oKQCrh0xyUmeRtD8/xHFZlJxQlvMyGSZCYay1oOfMG6', 'admin', '2026-11-01 01:33:11'),
(2, 'Alvin', 'davidalvin.it@gmail.com', '$2y$10$4Yd.QqytsBGySnevyPmAN.2nxhL7UKoPV9ye0fy8gNawUgz11rPa6', 'admin', '2026-11-01 01:49:10'),
(3, '0098765432', 'andisaputra@gmail.com', '$2y$10$7gJ.iBwxRYQpATHyt.NT6O.ENlnigpuGXYRcjKocgD5WCxJoyrGAC', 'siswa', '2026-11-02 02:18:40'),
(4, '0098765433', 'budigaming@gmail.com', '$2y$10$P53NL7U06xKEpP7ZkmZZAuZep8yColIS8uWBtObZqBtPKQbKZvS6m', 'siswa', '2026-11-02 04:40:05'),
(5, 'Pak Nasir', 'nasirkasir@gmail.com', '$2y$10$PCl.OQKPNLSIuzYsswMoveG2nD/swp4ImdwqNC6b2f167vqC.TiX.', 'kasir', '2026-11-02 11:42:51'),
(7, 'Pak Bowo', 'bowokasir@gmail.com', '$2y$10$W0V223cPfsBRgJI1ST5gU.yPUhFNU7TDP1t.9sPE1ProhwJo7G7S.', 'kasir', '2026-11-02 11:47:17'),
(8, 'Pak Mujib', 'mujibkasir@gmail.com', '$2y$10$So0htmjKAlLM1l4wfsUmA.xryssJqsbT0u3F.f6NVGY7nCBtC5y5y', 'kasir', '2026-11-02 12:32:48'),
(10, 'Bu Irma', 'irmakasir@gmail.com', '$2y$10$/Gvo9rY9Vdb3bXIxNd1NmeHDt09gnNGv99LPC5G9H.blIk/wnXzc2', 'kasir', '2026-11-02 12:46:20'),
(11, 'Joko', 'wi@gmail.com', '$2y$10$QbduFnn9ik0szmMkdsEOxOdxOPK8Q.uQKm3/MUCyC9Zd2xS1pZZTq', 'kasir', '2026-11-16 02:18:44'),
(12, 'Riyan', 'riyan@gmail.com', '$2y$10$uxRasdNQoRPPgAg3Rz1l6.qO.ozl4T/PwNZedz2MrbZCG/DolrMWu', 'kasir', '2026-11-17 00:32:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_name` (`class_name`),
  ADD KEY `major_id` (`major_id`);

--
-- Indexes for table `majors`
--
ALTER TABLE `majors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `spp_invoices`
--
ALTER TABLE `spp_invoices`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `student_id` (`student_id`,`period_month`,`period_year`),
  ADD UNIQUE KEY `unique_student_invoice` (`student_id`,`academic_year_id`,`period_month`,`period_year`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `spp_tariffs`
--
ALTER TABLE `spp_tariffs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grade` (`grade`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `majors`
--
ALTER TABLE `majors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `spp_invoices`
--
ALTER TABLE `spp_invoices`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `spp_tariffs`
--
ALTER TABLE `spp_tariffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`major_id`) REFERENCES `majors` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `spp_invoices` (`ID`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `spp_invoices`
--
ALTER TABLE `spp_invoices`
  ADD CONSTRAINT `spp_invoices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `spp_invoices_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
