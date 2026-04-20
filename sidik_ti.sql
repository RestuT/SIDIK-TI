-- phpMyAdmin SQL Dump
-- version 5.2.1
-- Host: 127.0.0.1
-- Generation Time: Mar 11, 2026

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sidik_ti`
--
CREATE DATABASE IF NOT EXISTS `sidik_ti` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sidik_ti`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `role` enum('user','admin','technician','staff','head') NOT NULL DEFAULT 'user',
  `two_fa_code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`username`, `password`, `full_name`, `department`, `jabatan`, `role`) VALUES
('admin', '$2y$10$e.w2M3hA/iJmJ5vI2H/wQ.X6d9x0i1I5N7x9C6N2d3a1I5N7x9C6N', 'Administrator', 'IT', 'Manager', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_dept` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_dept` (`nama_dept`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`nama_dept`) VALUES
('IT / Kominfo'),
('Bagian Keuangan'),
('Bagian Umum'),
('BKPSDM');

-- --------------------------------------------------------

--
-- Table structure for table `budget_config`
--

CREATE TABLE `budget_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fiscal_year` int(4) NOT NULL,
  `department` varchar(100) NOT NULL,
  `total_limit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `used_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fiscal_year_dept` (`fiscal_year`,`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `satuan` varchar(50) NOT NULL,
  `min_stock` int(11) NOT NULL DEFAULT 0,
  `price_reference` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `procurement_templates`
--

CREATE TABLE `procurement_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `specification` text NOT NULL,
  `base_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('Pengadaan','Maintenance') NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `urgency` varchar(50) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('Menunggu','Proses','Selesai','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `pic_id` int(11) DEFAULT NULL,
  `admin_reasoning` text DEFAULT NULL,
  `appeal_reason` text DEFAULT NULL,
  `is_appealed` tinyint(1) NOT NULL DEFAULT 0,
  `estimasi` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `pic_id` (`pic_id`),
  CONSTRAINT `fk_submissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_submissions_pic` FOREIGN KEY (`pic_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `asset_assignments`
--

CREATE TABLE `asset_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `assigned_at` date NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_assets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
