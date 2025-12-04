-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 03:24 AM
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
-- Database: `zppsu_admission`
--

-- --------------------------------------------------------

--
-- Table structure for table `bulk_reschedule_log`
--

CREATE TABLE `bulk_reschedule_log` (
  `id` int(11) NOT NULL,
  `old_date` date NOT NULL,
  `new_date` date NOT NULL,
  `campus` varchar(100) DEFAULT NULL,
  `time_slot` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `total_affected` int(11) DEFAULT 0,
  `success_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bulk_reschedule_log`
--

INSERT INTO `bulk_reschedule_log` (`id`, `old_date`, `new_date`, `campus`, `time_slot`, `reason`, `performed_by`, `total_affected`, `success_count`, `created_at`) VALUES
(1, '2025-12-06', '2025-12-07', 'ZPPSU MAIN', 'Morning (8AM-12PM)', 'Earthquake', 3, 1, 1, '2025-12-04 01:18:31');

-- --------------------------------------------------------

--
-- Table structure for table `document_uploads`
--

CREATE TABLE `document_uploads` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `document_type` enum('Photo','Birth Certificate','Report Card','Good Moral','Other') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incoming_sms`
--

CREATE TABLE `incoming_sms` (
  `id` int(11) NOT NULL,
  `sender` varchar(32) NOT NULL,
  `message` text NOT NULL,
  `received_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_code` varchar(64) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `years` int(11) NOT NULL,
  `degree_type` enum('Certificate','Associate','Diploma','Bachelor','Senior High') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_name`, `program_code`, `college`, `years`, `degree_type`) VALUES
(1, 'Certificate in Computer Hardware Servicing', 'CHS', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(2, 'Certificate in Electrical Installation and Maintenance', 'EIM', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(3, 'Certificate in Plumbing', 'PLUMBING', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(4, 'Certificate in Automotive Servicing', 'AUTO-SVC', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(5, 'Certificate in Welding and Fabrication', 'WELD-FAB', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(6, 'Certificate in Food and Beverage Services', 'FBS', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(7, 'Certificate in Housekeeping', 'HSK', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(8, 'Certificate in Cookery', 'COOKERY', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(9, 'Certificate in Dressmaking', 'DRESSMAKING', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(10, 'Certificate in Cosmetology', 'COSMETOLOGY', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Certificate'),
(11, 'Two-Year Associate in Industrial Technology - Automotive Technology', 'AIT-AUTO', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Associate'),
(12, 'Two-Year Associate in Industrial Technology - Food Technology', 'AIT-FOOD', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Associate'),
(13, 'Two-Year Associate in Industrial Technology - Garments Textile and Technology', 'AIT-GTT', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Associate'),
(14, 'Two-Year Associate in Industrial Technology - Electronics Technology', 'AIT-ELEXT', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Associate'),
(15, 'Two-Year Associate in Industrial Technology - Electrical Technology', 'AIT-ELECT', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Associate'),
(16, 'Two-Year Associate in Industrial Technology - Refrigeration and Air Conditioning Technology', 'AIT-RACT', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Associate'),
(17, 'Two-Year Associate in Industrial Technology - Architectural Drafting Technology', 'TTEC-ADT', 'Institute of Technical Education (ITE) - Two-Year', 2, 'Associate'),
(18, 'Bachelor of Science in Hospitality Management', 'BSHM', 'School of Business Administration (SBA)', 3, 'Diploma'),
(19, 'Bachelor of Science in Entrepreneurship', 'BS ENTREP', 'School of Business Administration (SBA)', 3, 'Diploma'),
(20, 'Automotive Engineering Technology', 'DT-AET', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(21, 'Information Technology', 'DT-IT', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(22, 'Electrical Engineering Technology', 'DT-EET', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(23, 'Electronics and Communication Technology', 'DT-ECT', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(24, 'Hospitality Management Technology', 'DT-HMT', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(25, 'Civil Engineering Technology', 'DT-CET', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(26, 'Food Production and Services Management Technology', 'DT-FPSMT', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(27, 'Mechanical Engineering Technology', 'DT-MET', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(28, 'Garments, Fashion and Design Technology', 'DT-GFDT', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(29, 'Trade Industrial Technical Education - Welding and Fabrication Technology', 'TITE-WAFT', 'Institute of Technical Education (ITE) - Three-Year', 3, 'Diploma'),
(30, 'Bachelor of Fine Arts - Industrial Design', 'BFA-ID', 'College of Arts, Humanities and Social Sciences (CAHSS)', 4, 'Bachelor'),
(31, 'Bachelor of Science in Development Communication', 'BS DEVCOM', 'College of Arts, Humanities and Social Sciences (CAHSS)', 4, 'Bachelor'),
(32, 'Batsilyer sa Sining ng Filipino', 'BA-FIL', 'College of Arts, Humanities and Social Sciences (CAHSS)', 4, 'Bachelor'),
(33, 'Bachelor of Science in Information Technology', 'BS INFOTECH', 'College of Information and Computing Sciences (CISC)', 4, 'Bachelor'),
(34, 'Bachelor of Science in Information System', 'BS INFO SYS', 'College of Information and Computing Sciences (CISC)', 4, 'Bachelor'),
(35, 'Bachelor of Industrial Technology major in Computer Technology', 'BINDTECH-COMPTECH', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(36, 'Bachelor of Industrial Technology major in Electrical Technology', 'BINDTECH-ET', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(37, 'Bachelor of Industrial Technology major in Electronics Technology', 'BINDTECH-ELEXT', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(38, 'Bachelor of Industrial Technology major in Mechanical Technology', 'BINDTECH-MECHANICAL', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(39, 'Bachelor of Industrial Technology major in Automotive Technology', 'BINDTECH-AT', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(40, 'Bachelor of Industrial Technology major in Heating, Ventilating and Air Conditioning Technology', 'BINDTECH-HVAC', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(41, 'Bachelor of Industrial Technology major in Culinary Technology', 'BINDTECH-CULINARY TECH', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(42, 'Bachelor of Industrial Technology major in Construction Technology', 'BINDTECH-CONTRUCTION', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(43, 'Bachelor of Industrial Technology major in Apparel and Fashion Technology', 'BINDTECH-AFT', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(44, 'Bachelor of Industrial Technology major in Power Plant Engineering Technology', 'BINDTECH-PPET', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(45, 'Bachelor of Industrial Technology major in Architectural Drafting Technology', 'BINDTECH-ADT', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(46, 'Bachelor of Industrial Technology major in Mechatronics Technology', 'BINDTECH-MECHATRONICS', 'College of Engineering Technology (CET)', 4, 'Bachelor'),
(47, 'Bachelor of Elementary Education', 'BEED', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(48, 'Bachelor of Secondary Education - English', 'BSED-ENGLISH', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(49, 'Bachelor of Secondary Education - Mathematics', 'BSED-MATH', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(50, 'Bachelor of Technology Livelihood Education - Home Economics', 'BTLED-HE', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(51, 'Bachelor of Technology Livelihood Education - Industrial Arts', 'BTLED-IA', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(52, 'Bachelor of Technology Livelihood Education - Information and Communications Technology', 'BTLED-ICT', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(53, 'Bachelor of Technical-Vocational Teacher Education - Automotive Technology', 'BTVTED-AT', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(54, 'Bachelor of Technical-Vocational Teacher Education - Civil and Construction Technology', 'BTVTED-CCT', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(55, 'Bachelor of Technical-Vocational Teacher Education - Drafting Technology', 'BTVTED-DT', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(56, 'Bachelor of Technical-Vocational Teacher Education - Electrical Technology', 'BTVTED-ELECT', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(57, 'Bachelor of Technical-Vocational Teacher Education - Electronics Technology', 'BTVTED-ELEXT', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(58, 'Bachelor of Technical-Vocational Teacher Education - Food Service Management', 'BTVTED-FSM', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(59, 'Bachelor of Technical-Vocational Teacher Education - Garments, Fashion and Design', 'BTVTED-GFD', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(60, 'Bachelor of Technical-Vocational Teacher Education - Mechanical Technology', 'BTVTED-MT', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(61, 'Bachelor of Technical-Vocational Teacher Education - Welding and Fabrication Technology', 'BTVTED-WAFT', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(62, 'Bachelor of Technical-Vocational Teacher Education - Heating, Ventilating and Air-Conditioning Technology', 'BTVTED-HVAC', 'College of Teacher Education (CTE)', 4, 'Bachelor'),
(63, 'Bachelor of Science in Marine Engineering', 'BS MAR-E', 'College of Maritime Education (CME)', 4, 'Bachelor'),
(64, 'Bachelor of Physical Education', 'BPED', 'College of Physical Education and Sports (CPES)', 4, 'Bachelor'),
(65, 'Bachelor of Science in Exercise and Sports Sciences major in Fitness and Sports Coaching', 'BSESS-FSC', 'College of Physical Education and Sports (CPES)', 4, 'Bachelor'),
(66, 'Bachelor of Science in Exercise and Sports Sciences major in Fitness and Sports Management', 'BSESS-FSM', 'College of Physical Education and Sports (CPES)', 4, 'Bachelor');

-- --------------------------------------------------------

--
-- Table structure for table `reschedule_history`
--

CREATE TABLE `reschedule_history` (
  `id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `old_date` date NOT NULL,
  `new_date` date NOT NULL,
  `old_time_slot` varchar(50) DEFAULT NULL,
  `new_time_slot` varchar(50) DEFAULT NULL,
  `old_room` varchar(50) DEFAULT NULL,
  `new_room` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `rescheduled_by` int(11) DEFAULT NULL,
  `rescheduled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reschedule_history`
--

INSERT INTO `reschedule_history` (`id`, `schedule_id`, `old_date`, `new_date`, `old_time_slot`, `new_time_slot`, `old_room`, `new_room`, `reason`, `rescheduled_by`, `rescheduled_at`) VALUES
(1, 5, '2025-12-06', '2025-12-07', 'Morning (8AM-12PM)', 'Morning (8AM-12PM)', 'Room 101', NULL, 'Earthquake', 3, '2025-12-04 01:18:27');

-- --------------------------------------------------------

--
-- Table structure for table `room_assignments`
--

CREATE TABLE `room_assignments` (
  `id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `campus` varchar(100) NOT NULL,
  `capacity` int(11) DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_assignments`
--

INSERT INTO `room_assignments` (`id`, `room_number`, `campus`, `capacity`, `is_active`, `created_at`) VALUES
(1, 'Room 101', 'ZPPSU MAIN', 30, 1, '2025-11-18 10:01:31'),
(2, 'Room 102', 'ZPPSU MAIN', 30, 1, '2025-11-18 10:01:31'),
(3, 'Room 103', 'ZPPSU MAIN', 30, 1, '2025-11-18 10:01:31'),
(4, 'Room 201', 'ZPPSU MAIN', 35, 1, '2025-11-18 10:01:31'),
(5, 'Room 202', 'ZPPSU MAIN', 35, 1, '2025-11-18 10:01:31'),
(6, 'Room 101', 'Gregorio Campus (Vitali)', 25, 1, '2025-11-18 10:01:31'),
(7, 'Room 102', 'Gregorio Campus (Vitali)', 25, 1, '2025-11-18 10:01:31'),
(8, 'Room 101', 'ZPPSU Campus (Kabasalan)', 25, 1, '2025-11-18 10:01:31'),
(9, 'Room 102', 'ZPPSU Campus (Kabasalan)', 25, 1, '2025-11-18 10:01:31'),
(10, 'Room 101', 'Anna Banquial Campus (Malangas)', 25, 1, '2025-11-18 10:01:31'),
(11, 'Room 102', 'Anna Banquial Campus (Malangas)', 25, 1, '2025-11-18 10:01:31'),
(12, 'Room 101', 'Timuay Tubod M. Mandi Campus (Siay)', 25, 1, '2025-11-18 10:01:31'),
(13, 'Room 102', 'Timuay Tubod M. Mandi Campus (Siay)', 25, 1, '2025-11-18 10:01:31'),
(14, 'Room 101', 'ZPPSU Campus (Bayog)', 25, 1, '2025-11-18 10:01:31'),
(15, 'Room 102', 'ZPPSU Campus (Bayog)', 25, 1, '2025-11-18 10:01:31');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_admission`
--

CREATE TABLE `schedule_admission` (
  `id` int(11) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `given_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `age` int(11) NOT NULL,
  `dob` date NOT NULL,
  `address` varchar(255) NOT NULL,
  `application_type` varchar(50) NOT NULL,
  `classification` varchar(100) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `school_campus` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `date_scheduled` date NOT NULL,
  `time_slot` enum('Morning (8AM-12PM)','Afternoon (1PM-5PM)') DEFAULT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `reference_number` varchar(20) NOT NULL,
  `lrn` varchar(20) DEFAULT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `photo` varchar(255) NOT NULL,
  `document` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Pending' CHECK (`status` in ('Pending','Approved','Rejected')),
  `exam_result` enum('Pass','Fail','Pending') DEFAULT 'Pending',
  `exam_remarks` text DEFAULT NULL,
  `exam_score` decimal(5,2) DEFAULT NULL,
  `admission_slip_generated` tinyint(1) DEFAULT 0,
  `admission_slip_path` varchar(255) DEFAULT NULL,
  `last_sms_sent` timestamp NULL DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL,
  `qr_code_path` varchar(255) DEFAULT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `reschedule_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedule_admission`
--

INSERT INTO `schedule_admission` (`id`, `surname`, `given_name`, `middle_name`, `gender`, `age`, `dob`, `address`, `application_type`, `classification`, `grade_level`, `school_campus`, `email`, `phone`, `date_scheduled`, `time_slot`, `room_number`, `reference_number`, `lrn`, `previous_school`, `photo`, `document`, `created_at`, `status`, `exam_result`, `exam_remarks`, `exam_score`, `admission_slip_generated`, `admission_slip_path`, `last_sms_sent`, `reminder_sent`, `updated_at`, `qr_code_path`, `qr_token`, `reschedule_count`) VALUES
(5, 'student', 'student', 'P', 'Male', 21, '2004-12-04', 'SAN ROQUE, FELECIANO STREET', 'New Student', 'Bachelor of Industrial Technology major in Mechanical Technology (BINDTECH-MECHANICAL)', '1st Year', 'ZPPSU MAIN', 'gg@gmail.com', '+639971545203', '2025-12-07', 'Morning (8AM-12PM)', NULL, '359-186-1902', '132432432432', 'SAD', 'attendance-qr-mipaowtm.png', '', '2025-12-04 01:12:14', 'Approved', 'Pending', NULL, NULL, 0, NULL, NULL, 0, NULL, 'uploads/qrcodes/qr_359-186-1902_1764811133.png', '0e6a4aa04e8e11df215723cdd16741d140d04eb76fd8cd0e7ecc07b3de790b55', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sms_log`
--

CREATE TABLE `sms_log` (
  `id` int(11) NOT NULL,
  `classification` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message_type` enum('Approval','Rejection','Reminder','Other') NOT NULL,
  `message_content` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_log`
--

INSERT INTO `sms_log` (`id`, `classification`, `phone`, `message_type`, `message_content`, `sent_at`) VALUES
(1, 'Bachelor of Industrial Technology major in Mechanical Technology (BINDTECH-MECHANICAL)', '+639971545203', 'Approval', 'Auto-approval notification', '2025-12-04 01:12:16');

-- --------------------------------------------------------

--
-- Table structure for table `system_info`
--

CREATE TABLE `system_info` (
  `id` int(30) NOT NULL,
  `meta_field` text NOT NULL,
  `meta_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_info`
--

INSERT INTO `system_info` (`id`, `meta_field`, `meta_value`) VALUES
(1, 'name', 'ZPPSU ADMISSION TEST'),
(6, 'short_name', 'ZPPSU_ADTEST'),
(11, 'logo', 'uploads\\zppsu1.png\"'),
(13, 'user_avatar', 'logo icon.png\r\n'),
(14, 'cover', 'getThemePhoto.jpg'),
(15, 'content', 'Array');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_log`
--

CREATE TABLE `teacher_log` (
  `id` int(11) NOT NULL,
  `teacher_name` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `log_date` date NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_log`
--

INSERT INTO `teacher_log` (`id`, `teacher_name`, `department`, `subject`, `log_date`, `remarks`, `created_at`) VALUES
(1, 'Juan Dela Cruz', 'Math', 'Algebra', '2025-08-15', 'Present', '2025-08-15 13:47:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(50) NOT NULL,
  `firstname` varchar(250) NOT NULL,
  `middlename` text DEFAULT NULL,
  `lastname` varchar(250) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `lrn` varchar(20) DEFAULT NULL,
  `reference_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `role` tinyint(1) NOT NULL DEFAULT 2 COMMENT '1=Admin, 2=Staff, 3=Student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `middlename`, `lastname`, `username`, `password`, `phone`, `lrn`, `reference_number`, `email`, `course`, `year_level`, `avatar`, `last_login`, `date_added`, `date_updated`, `role`) VALUES
(3, 'admin', '', 'Account', 'admin', '21232f297a57a5a743894a0e4a801fc3', '09475610267', '', NULL, 'gh@gmail.com', NULL, NULL, '', NULL, '2025-08-11 23:22:18', '2025-09-15 23:31:09', 1),
(5, 'Teacher', '', 'Teacher', 'teacher', '21232f297a57a5a743894a0e4a801fc3', '0916619331', '', NULL, 'tefraef@gmail.com', NULL, NULL, '', NULL, '2025-09-01 16:48:42', '2025-09-16 10:56:12', 2),
(10, 'student', 'P', 'student', 'student', '21232f297a57a5a743894a0e4a801fc3', '+639971545203', '132432432432', '359-186-1902', 'gg@gmail.com', '', '', NULL, NULL, '2025-09-10 16:01:22', '2025-10-19 13:42:46', 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bulk_reschedule_log`
--
ALTER TABLE `bulk_reschedule_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dates` (`old_date`,`new_date`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `document_uploads`
--
ALTER TABLE `document_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_id` (`schedule_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_name_years` (`program_name`,`years`);

--
-- Indexes for table `reschedule_history`
--
ALTER TABLE `reschedule_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_id` (`schedule_id`);

--
-- Indexes for table `room_assignments`
--
ALTER TABLE `room_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_room_campus` (`room_number`,`campus`),
  ADD KEY `idx_campus` (`campus`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `schedule_admission`
--
ALTER TABLE `schedule_admission`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_admission_date_status` (`date_scheduled`,`status`),
  ADD KEY `idx_schedule_admission_campus` (`school_campus`),
  ADD KEY `idx_schedule_admission_reference` (`reference_number`),
  ADD KEY `idx_schedule_admission_lrn` (`lrn`),
  ADD KEY `idx_schedule_admission_phone` (`phone`),
  ADD KEY `idx_schedule_admission_email` (`email`),
  ADD KEY `idx_schedule_admission_name` (`surname`,`given_name`);

--
-- Indexes for table `sms_log`
--
ALTER TABLE `sms_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_classification_phone` (`classification`,`phone`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bulk_reschedule_log`
--
ALTER TABLE `bulk_reschedule_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `document_uploads`
--
ALTER TABLE `document_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `reschedule_history`
--
ALTER TABLE `reschedule_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `room_assignments`
--
ALTER TABLE `room_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `schedule_admission`
--
ALTER TABLE `schedule_admission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sms_log`
--
ALTER TABLE `sms_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bulk_reschedule_log`
--
ALTER TABLE `bulk_reschedule_log`
  ADD CONSTRAINT `bulk_reschedule_log_ibfk_1` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `document_uploads`
--
ALTER TABLE `document_uploads`
  ADD CONSTRAINT `fk_document_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule_admission` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reschedule_history`
--
ALTER TABLE `reschedule_history`
  ADD CONSTRAINT `fk_reschedule_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedule_admission` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
