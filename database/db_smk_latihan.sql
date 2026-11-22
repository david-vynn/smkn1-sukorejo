-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2026 at 03:07 PM
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
(1, 13, 1, '0098765432', 'Andi Saputra', '2026-11-01 04:13:44'),
(2, 14, 2, '0098765433', 'Budi Santoso', '2026-11-01 04:13:44'),
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
(13, '0098765432', 'andisaputra@gmail.com', '$2y$10$887SKKkl7IKb19AjkgsGL.bONyNHeUUjbV1RRV2hyNTX.aqikfxW2', 'siswa', '2026-11-22 11:31:21'),
(14, '0098765433', 'budisantoso@gmail.com', '$2y$10$KraQx5gScMgWJWYIihE2z.Diwr8OSBf3woqxx87K4Jcnzs9phu/Pm', 'siswa', '2026-11-22 11:45:28'),
(16, 'Neni Nailatul Izzah', 'nenikasir@gmail.com', '$2y$10$lUrXD06ZiSaKZS5S.4T0qe1SfqKbH/5PFwQ9JrGdjzIpGU8mnxJCC', 'kasir', '2026-11-22 11:53:28'),
(17, 'Irma Sabira', 'irmakasir@gmail.com', '$2y$10$ADVoXix57b1lrxym9nz6SON5g4h1ygGYqQ3HvoIaI6Eg/l5nmhskS', 'kasir', '2026-11-22 11:54:05');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spp_invoices`
--
ALTER TABLE `spp_invoices`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
