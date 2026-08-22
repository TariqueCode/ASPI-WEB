-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 16, 2026 at 11:51 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pachanda_aspi`
--

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `id` int(11) NOT NULL,
  `student_name` varchar(150) DEFAULT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `board` varchar(50) DEFAULT NULL,
  `roll` varchar(50) DEFAULT NULL,
  `registration` varchar(50) DEFAULT NULL,
  `ssc_gpa` varchar(10) DEFAULT NULL,
  `course_type` varchar(50) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `admission_type` varchar(20) DEFAULT 'diploma',
  `verified` tinyint(1) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT NULL,
  `ssc_status` varchar(20) DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_rate_limit`
--

CREATE TABLE `api_rate_limit` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `request_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `content_key` varchar(50) NOT NULL,
  `content_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `category_bn` varchar(50) DEFAULT NULL,
  `category_en` varchar(50) DEFAULT NULL,
  `title_bn` varchar(255) DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `description_bn` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `content_bn` longtext DEFAULT NULL,
  `content_en` longtext DEFAULT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `type` enum('event','news','gallery') DEFAULT 'event',
  `showInMarquee` tinyint(1) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `question_bn` varchar(255) DEFAULT NULL,
  `question_en` varchar(255) DEFAULT NULL,
  `answer_bn` text DEFAULT NULL,
  `answer_en` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_attachments`
--

CREATE TABLE `gallery_attachments` (
  `id` int(11) NOT NULL,
  `gallery_id` int(11) NOT NULL,
  `file_url` varchar(255) NOT NULL,
  `title_bn` varchar(255) DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `type` enum('image','pdf','video') DEFAULT 'image',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_items`
--

CREATE TABLE `gallery_items` (
  `id` int(11) NOT NULL,
  `title_bn` varchar(255) DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `file_url` varchar(255) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `name_bn` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `designation_bn` varchar(100) DEFAULT NULL,
  `designation_en` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `message_bn` text DEFAULT NULL,
  `message_en` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `date_bn` varchar(50) DEFAULT NULL,
  `date_en` varchar(50) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sub_category_id` int(11) DEFAULT NULL,
  `title_bn` varchar(255) DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `isNew` tinyint(1) DEFAULT 0,
  `showInMarquee` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notice_categories`
--

CREATE TABLE `notice_categories` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `name_bn` varchar(100) NOT NULL,
  `name_en` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notice_categories`
--

INSERT INTO `notice_categories` (`id`, `parent_id`, `name_bn`, `name_en`, `slug`, `status`, `sort_order`) VALUES
(1, 0, 'সাধারণ', 'General', 'general', 1, 0),
(2, 0, 'ডিপ্লোমা', 'Diploma', 'diploma', 1, 0),
(3, 0, 'NSDA', 'NSDA', 'nsda', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `quotes`
--

CREATE TABLE `quotes` (
  `id` int(11) NOT NULL,
  `name_bn` varchar(100) DEFAULT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `designation_bn` varchar(100) DEFAULT NULL,
  `designation_en` varchar(100) DEFAULT NULL,
  `quote_bn` text DEFAULT NULL,
  `quote_en` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_json` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_json`) VALUES
('address', 'দক্ষিণ হাশিমপুর (জামিরজুরী রাস্তার মাথা), দোহাজারী, চন্দনাইশ, চট্টগ্রাম', NULL),
('address_en', 'South Hashimpur (Jamirjuri Road Head), Dohazari, Chandanaish, Chattogram', NULL),
('admissionNotice', '', NULL),
('admissionOpen', '1', NULL),
('custom_font', '', NULL),
('diploma_admission', '1', NULL),
('email', 'ctgaspi@gmail.com', NULL),
('font_size', '16', NULL),
('institution_name_en', 'Ashab Siraj Polytechnic Institute', NULL),
('logo', 'assets/images/ASPI-Logo.png', NULL),
('marquee_speed', '30', NULL),
('master_admission', '1', NULL),
('nsda_admission', '1', NULL),
('phone', '+৮৮০ ১৮৪৭-৩১০৩১০', NULL),
('popup_animation_delay', '3000', NULL),
('popup_enabled', '0', NULL),
('popup_images', '[\"assets\\/images\\/ASPI-Logo.png\"]', NULL),
('principal_img', '', NULL),
('principal_msg', 'কারিগরি শিক্ষায় শিক্ষিত জাতিই পারে দেশের প্রকৃত উন্নয়ন সাধন করতে।', NULL),
('scraper_settings', NULL, '{\r\n    \"institution\": {\r\n        \"name\": \"আসহাব সিরাজ পলিটেকনিক ইনস্টিটিউট\",\r\n        \"short_name\": \"দক্ষিণ হাশিমপুর (জামিরজুরী রাস্তার মাথা), দোহাজারী, চন্দনাইশ, চট্টগ্রাম\",\r\n        \"logo\": \"assets/images/ASPI-Logo.png\",\r\n        \"logo_size\": 80\r\n    },\r\n    \"colors\": {\r\n        \"primary\": \"#1f2937\",\r\n        \"secondary\": \"#3b82f6\",\r\n        \"accent\": \"#10b981\",\r\n        \"success\": \"#22c55e\",\r\n        \"danger\": \"#ef4444\"\r\n    },\r\n    \"eligibility\": {\r\n        \"min_year\": 2022,\r\n        \"max_year\": 2026,\r\n        \"min_gpa\": 2.00,\r\n        \"gpa_operator\": \">=\",\r\n        \"admission_active\": true\r\n    },\r\n    \"pdf\": {\r\n        \"show_logo\": true,\r\n        \"show_subjects\": true,\r\n        \"show_qr\": false\r\n    }\r\n}');

-- --------------------------------------------------------

--
-- Table structure for table `social_links`
--

CREATE TABLE `social_links` (
  `id` int(11) NOT NULL,
  `platform_name` varchar(50) DEFAULT NULL,
  `platform_name_bn` varchar(50) DEFAULT NULL,
  `icon_class` varchar(50) DEFAULT NULL,
  `icon_image` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `social_links`
--

INSERT INTO `social_links` (`id`, `platform_name`, `platform_name_bn`, `icon_class`, `icon_image`, `url`, `color`, `sort_order`, `status`, `created_at`) VALUES
(1, 'Facebook', 'ফেসবুক', 'fa-brands fa-facebook-f', NULL, 'https://www.facebook.com/ashabsirajpolytechnicinstitute', '#1877f2', 1, 1, '2026-08-15 20:00:05'),
(2, 'YouTube', 'ইউটিউব', 'fa-brands fa-youtube', NULL, 'https://www.youtube.com/@CtgASPI', '#ff0000', 2, 1, '2026-08-15 20:00:05'),
(3, 'Instagram', 'ইন্সটাগ্রাম', 'fa-brands fa-instagram', NULL, 'https://www.instagram.com/ctgaspi', '#e4405f', 3, 1, '2026-08-15 20:00:05'),
(4, 'WhatsApp', 'হোয়াটসঅ্যাপ', 'fa-brands fa-whatsapp', NULL, 'https://wa.me/+8801847310310', '#25d366', 4, 1, '2026-08-15 20:00:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','editor','viewer') NOT NULL DEFAULT 'editor',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'Tarique', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Muhammad Saiful Islam', 'tariquebn@gmail.com', 'admin', 'active', NULL, '2026-08-15 20:00:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admission_type` (`admission_type`),
  ADD KEY `status` (`status`),
  ADD KEY `course_type` (`course_type`);

--
-- Indexes for table `api_rate_limit`
--
ALTER TABLE `api_rate_limit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_action_time` (`ip_address`,`action`,`request_time`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`content_key`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery_attachments`
--
ALTER TABLE `gallery_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gallery_id` (`gallery_id`);

--
-- Indexes for table `gallery_items`
--
ALTER TABLE `gallery_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_time` (`ip_address`,`attempt_time`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notice_categories`
--
ALTER TABLE `notice_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `social_links`
--
ALTER TABLE `social_links`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_rate_limit`
--
ALTER TABLE `api_rate_limit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery_attachments`
--
ALTER TABLE `gallery_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `gallery_items`
--
ALTER TABLE `gallery_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notice_categories`
--
ALTER TABLE `notice_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- ============================================================
-- ASPI DASHBOARD COMPATIBILITY TABLES
-- These tables are required by the current admin dashboard/API.
-- CREATE IF NOT EXISTS keeps existing data safe.
-- ============================================================

CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_bn` varchar(150) DEFAULT NULL,
  `name_en` varchar(150) DEFAULT NULL,
  `deg_bn` varchar(150) DEFAULT NULL,
  `deg_en` varchar(150) DEFAULT NULL,
  `dept_bn` varchar(150) DEFAULT NULL,
  `dept_en` varchar(150) DEFAULT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_teachers_status` (`status`),
  KEY `idx_teachers_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `title_bn` varchar(255) DEFAULT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `level_bn` varchar(150) DEFAULT NULL,
  `level_en` varchar(150) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_courses_type` (`type`),
  KEY `idx_courses_status` (`status`),
  KEY `idx_courses_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
