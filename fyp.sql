-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 30, 2026 at 09:37 AM
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
-- Database: `fyp`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `posted_on` datetime NOT NULL DEFAULT current_timestamp(),
  `last_edited` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attachment_url` varchar(200) DEFAULT NULL,
  `posted_by` varchar(20) NOT NULL,
  `visibility` enum('ALL','STUDENTS_ONLY','STAFF_ONLY') NOT NULL DEFAULT 'ALL'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `posted_on`, `last_edited`, `attachment_url`, `posted_by`, `visibility`) VALUES
(1, 'BLI01 Approval Meeting', 'To students under my supervision, please come to my office this week for consultation on the BLI01 Form. See you...', '2025-12-11 17:20:29', '2025-12-11 17:20:29', NULL, 'FKI0010', 'STUDENTS_ONLY'),
(2, 'BLI01 Consulation', 'Dear lecturers, take note that you are encouraged to hold a consultation with your students for the BLI01 Form approval. All the best!', '2025-12-11 17:24:15', '2025-12-11 17:24:15', NULL, 'admin1', 'STAFF_ONLY'),
(4, 'BLI01 Form Submission', 'Dear students, \r\nRegarding BLI-01. Due date to submit your BLI-01 is on the 11th December. Email to fcili@ums.edu.my \r\nTitle “Request for LI letter”.', '2025-12-11 17:28:50', '2025-12-11 17:28:50', NULL, 'admin1', 'STUDENTS_ONLY'),
(7, 'BLI-02', 'Fill up BLI-02', '2026-01-08 11:26:39', '2026-01-08 11:26:39', NULL, 'FKI0010', 'ALL');

-- --------------------------------------------------------

--
-- Table structure for table `bli01_form`
--

CREATE TABLE `bli01_form` (
  `submission_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `submitted_on` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bli01_form`
--

INSERT INTO `bli01_form` (`submission_id`, `student_id`, `submitted_on`) VALUES
(2, 'BI22110033', '2025-11-12 13:45:45');

-- --------------------------------------------------------

--
-- Table structure for table `career_readiness_score`
--

CREATE TABLE `career_readiness_score` (
  `crs_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `generated_on` datetime NOT NULL DEFAULT current_timestamp(),
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `career_readiness_score`
--

INSERT INTO `career_readiness_score` (`crs_id`, `student_id`, `score`, `generated_on`, `last_updated`) VALUES
(1, 'BI22110033', 87.00, '2025-11-13 16:52:31', '2025-11-13 16:52:31');

-- --------------------------------------------------------

--
-- Table structure for table `certification`
--

CREATE TABLE `certification` (
  `cert_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `cert_name` varchar(100) NOT NULL,
  `issuer` varchar(100) DEFAULT NULL,
  `cert_url` varchar(255) DEFAULT NULL,
  `date_obtained` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certification`
--

INSERT INTO `certification` (`cert_id`, `student_id`, `cert_name`, `issuer`, `cert_url`, `date_obtained`) VALUES
(4, 'BI22110033', 'HCIA', 'Huawei Academy', '', '2024-11-21');

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `company_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `nature` enum('GOVERNMENT','PRIVATE','MULTINATIONAL','OTHERS') NOT NULL,
  `address` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `website_link` varchar(100) DEFAULT NULL,
  `logo_url` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `average_rating` decimal(3,2) DEFAULT NULL,
  `focus_area` enum('SOFTWARE DEVELOPMENT','NETWORK & INFRASTRUCTURE','DATA SCIENCE','UI/UX','CYBERSECURITY','BUSINESS IT','ARTIFICIAL INTELLIGENCE','WEB DEVELOPMENT','MOBILE APP DEVELOPMENT','CLOUD COMPUTING','IOT','DIGITAL MARKETING','GAME DEVELOPMENT','OTHERS') NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `created_by` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`company_id`, `company_name`, `nature`, `address`, `email`, `phone`, `website_link`, `logo_url`, `description`, `average_rating`, `focus_area`, `city`, `state`, `created_by`, `status`) VALUES
(1, 'CodeCrafters Inc.', 'PRIVATE', '123 Jalan Teknologi, Kuala Lumpur', 'hr@codecrafters.com', '+603-1234-5678', 'https://www.codecrafters.com', 'uploads/company_1.jpg', 'A software development company specializing in web and mobile applications.', 2.00, 'SOFTWARE DEVELOPMENT', 'Kuala Lumpur', 'Wilayah Persekutuan', 'admin1', 'approved'),
(2, 'ByteWave Technologies', 'PRIVATE', '45 Cyberjaya Street, Cyberjaya', 'careers@bytewave.com', '+603-8765-4321', 'https://www.bytewave.com', 'uploads/company_2.jpg', 'ByteWave Technologies is a Cyberjaya-based company specializing in innovative software and IT solutions for businesses.', 3.00, 'BUSINESS IT', 'Cyberjaya', 'Selangor', 'admin1', 'approved'),
(3, 'AppFusion Labs', 'PRIVATE', '78 Penang Tech Park, Penang', 'contact@appfusion.com', '+604-2345-6789', 'https://www.appfusion.com', 'uploads/company_3.jpg', 'Develops innovative mobile and web apps with a focus on UX/UI.', 4.20, '', 'Penang', 'Penang', 'admin1', 'approved'),
(5, 'Innovatech Solutions', 'PRIVATE', 'Lot 12, Cyberjaya Tech Park, Selangor', 'contact@innovatech.my', '03-8899 4567', 'https://www.innovatech.my', 'uploads/company_5.jpg', 'Innovatech Solutions focuses on developing modern web and mobile solutions for businesses and startups.', 3.50, 'SOFTWARE DEVELOPMENT', 'Cyberjaya', 'Selangor', 'admin1', 'approved'),
(6, 'Borneo Marine Systems', 'PRIVATE', 'Block 3, Jalan Coastal, Kota Kinabalu', 'info@borneomarine.com', '088-765432', 'https://www.borneomarine.com', 'uploads/company_6.jpg', 'Borneo Marine Systems provides software and IoT integration for the aquaculture and maritime industry.', 0.00, '', 'Kota Kinabalu', 'Sabah', 'admin1', 'approved'),
(7, 'DataVista Analytics', 'MULTINATIONAL', 'Level 8, Menara Hap Seng, Kuala Lumpur', 'hr@datavista.com', '03-2288 9900', 'https://www.datavista.com', 'uploads/company_7.jpg', 'DataVista is a data-driven company specializing in predictive analytics and AI-powered business intelligence.', 0.00, '', 'Kuala Lumpur', 'Wilayah Persekutuan', 'admin1', 'approved'),
(8, 'GreenTech Automation', 'PRIVATE', 'Lot 7, Jalan Telipok, Industrial Park, Tuaran', 'hello@greentechauto.my', '088-908877', 'https://www.greentechauto.my', 'uploads/company_8.jpg', 'GreenTech Automation focuses on sustainable automation systems and smart agriculture solutions.', 4.20, '', 'Tuaran', 'Sabah', 'admin1', 'approved'),
(9, 'Google Malaysia', 'MULTINATIONAL', 'Level 20, Axiata Tower, Kuala Lumpur Sentral', 'info@google.com', '+603-2264-6000', 'https://about.google/intl/en_my/', 'uploads/company_9.jpg', 'Google Malaysia focuses on cloud computing, software engineering, and AI solutions for local markets.', 0.00, 'CLOUD COMPUTING', 'Kuala Lumpur', 'Wilayah Persekutuan', 'admin1', 'approved'),
(10, 'Microsoft Malaysia', 'MULTINATIONAL', 'Level 26, Menara Shell, KL Sentral', 'contact@microsoft.com', '+603-2179-6000', 'https://www.microsoft.com/en-my', 'uploads/company_10.jpg', 'Microsoft Malaysia provides enterprise software, AI, and developer platform solutions.', 0.00, 'SOFTWARE DEVELOPMENT', 'Kuala Lumpur', 'Wilayah Persekutuan', 'admin1', 'approved'),
(11, 'Intel Malaysia', 'MULTINATIONAL', 'Bayan Lepas Free Industrial Zone, Penang', 'hr@intel.com', '+604-630-6000', 'https://www.intel.com.my', 'uploads/company_11.jpg', 'Intel Malaysia engages in hardware-software integration, embedded systems, and R&D software engineering.', 4.60, '', 'Bayan Lepas', 'Penang', 'admin1', 'approved'),
(12, 'Dell Technologies Malaysia', 'MULTINATIONAL', 'Plot 76, Bukit Tengah Industrial Park, Penang', 'hr@dell.com', '+604-613-9888', 'https://www.dell.com.my', 'uploads/company_12.jpg', 'Dell Malaysia focuses on software for cloud infrastructure, cybersecurity, and systems support.', 4.80, '', 'Bukit Tengah', 'Penang', 'admin1', 'approved'),
(13, 'Petronas Digital', 'GOVERNMENT', 'Tower 1, Petronas Twin Towers, Kuala Lumpur', 'careers@petronas.com', '+603-2331-4989', 'https://www.petronas.com/digital', 'uploads/company_13.jpg', 'Petronas Digital develops software solutions for data analytics, automation, and AI in the energy sector.', 5.00, '', 'Kuala Lumpur', 'Wilayah Persekutuan', 'admin1', 'approved'),
(14, 'MAMPU', 'GOVERNMENT', 'Prime Minister’s Department, Putrajaya', 'info@mampu.gov.my', '+603-8000-8000', 'https://www.mampu.gov.my', 'uploads/company_14.jpg', 'The Malaysian Administrative Modernisation and Management Planning Unit focuses on government digitalisation and system development.', 0.00, '', 'Putrajaya', 'Wilayah Persekutuan', 'admin1', 'approved'),
(15, 'TM Research & Development', 'GOVERNMENT', 'TM Innovation Centre, Cyberjaya', 'info@tmrnd.com.my', '+603-8318-1000', 'https://www.tmrnd.com.my', 'uploads/company_15.png', 'Telekom Malaysia’s R&D division developing software innovations in 5G, IoT, and cloud technology.', 0.00, '', 'Cyberjaya', 'Selangor', 'admin1', 'approved'),
(16, 'Shopee Malaysia', 'MULTINATIONAL', 'Level 25, Southpoint Tower, Mid Valley City', 'careers@shopee.com', '+603-2775-9888', 'https://shopee.com.my', 'uploads/company_16.jpg', 'Shopee Malaysia offers software engineering internships in backend systems, app development, and data analytics.', 0.00, '', 'Kuala Lumpur', 'Wilayah Persekutuan', 'admin1', 'approved'),
(17, 'Grab Malaysia', 'MULTINATIONAL', 'Level 38, Integra Tower, The Intermark, Kuala Lumpur', 'info.my@grab.com', '+603-2718-8999', 'https://www.grab.com/my', 'uploads/company_17.jpg', 'Grab Malaysia provides opportunities in mobile app development, backend systems, and AI integration.', 0.00, '', 'Kuala Lumpur', 'Wilayah Persekutuan', 'admin1', 'approved'),
(18, 'Fusionex Group', 'PRIVATE', '7 Jalan Kerinchi, Bangsar South, Kuala Lumpur', 'hr@fusionexgiant.com', '+603-2242-3188', 'https://www.fusionexgroup.com', 'uploads/company_18.jpg', 'Fusionex is a Malaysian data technology company specialising in big data analytics and software integration.', 0.00, '', 'Kuala Lumpur', 'Wilayah Persekutuan', 'admin1', 'approved'),
(19, 'Aerodyne Group', 'PRIVATE', 'Cyberjaya Innovation Hub, Cyberjaya', 'info@aerodyne.group', '+603-8687-1888', 'https://www.aerodyne.group', 'uploads/company_19.png', 'Aerodyne develops drone software and AI solutions for industrial applications.', 0.00, '', 'Cyberjaya', 'Selangor', 'admin1', 'approved'),
(20, 'Carsome Malaysia', 'PRIVATE', 'Lot 7, Jalan Tandang, Petaling Jaya', 'careers@carsome.my', '+603-2771-7555', 'https://www.carsome.my', 'uploads/company_20.png', 'Carsome builds software platforms for used car marketplaces and logistics optimisation.', 0.00, 'WEB DEVELOPMENT', 'Petaling Jaya', 'Selangor', 'admin1', 'approved'),
(21, 'Vitrox Corporation', 'PRIVATE', 'Penang Science Park, Simpang Ampat', 'hr@vitrox.com', '+604-240-5888', 'https://www.vitrox.com', 'uploads/company_21.png', 'Vitrox develops machine vision inspection systems with embedded software control.', 0.00, '', 'Simpang Ampat', 'Penang', 'admin1', 'approved'),
(22, 'Mesiniaga Berhad', 'PRIVATE', 'Subang Business Park, Subang Jaya', 'info@mesiniaga.com.my', '+603-7955-2228', 'https://www.mesiniaga.com.my', 'uploads/company_22.png', 'Mesiniaga provides IT solutions, enterprise software development, and managed services.', 0.00, '', 'Subang Jaya', 'Selangor', 'admin1', 'approved'),
(23, 'Schlumberger (M) Sdn Bhd', 'MULTINATIONAL', 'Level 37, Menara Bank Pembangunan Plaza, Kuala Lumpur', '', '', '', 'uploads/company_logos/logo_693bd60c30969.jpg', 'SLB (formerly Schlumberger) is a massive global technology company that provides services, digital solutions, and integrated project management to the energy industry.', NULL, '', 'Kuala Lumpur', 'Kuala Lumpur', 'admin1', 'approved'),
(24, 'Razer Inc. Malaysia', 'MULTINATIONAL', 'The Vertical Corporate Tower B, Bangsar South,  Kuala Lumpur', '', '', '', 'uploads/company_logos/logo_693c146f903cc.jpeg', 'Razer is a global tech company that designs, develops, and sells high-performance gaming hardware, software, and services  for gamers, creating an integrated ecosystem for PCs, consoles, and mobile. \r\n', 4.00, '', 'Kuala Lumpur', 'Kuala Lumpur', 'admin1', 'approved'),
(25, 'ExxonMobil Malaysia', 'MULTINATIONAL', 'Menara ExxonMobil, No. 3, Jalan Bangsar.', '', '', '', 'uploads/company_logos/logo_693ff5bc440eb.png', 'ExxonMobil Malaysia operates in oil and gas exploration, refining, and marketing petroleum products across Malaysia, contributing to energy and industrial sectors.', NULL, 'OTHERS', 'Kuala Lumpur', 'Kuala Lumpur', 'FKI0010', 'approved'),
(27, 'Honda Malaysia', 'MULTINATIONAL', 'Petaling Jaya, Selangor.', '', '', '', 'uploads/company_logos/logo_693ff8a61d333.png', 'Honda Malaysia is a leading automobile and motorcycle manufacturer in Malaysia, known for producing cars, motorcycles, and providing after-sales services.', NULL, 'OTHERS', 'Petaling Jaya', 'Selangor', 'FKI0010', 'approved'),
(28, 'Robert Bosch (M) Sdn Bhd', 'MULTINATIONAL', 'Lot 6, Bayan Lepas Free Industrial Zone Phase 1, Pulau Pinang.', '', '', '', 'uploads/company_logos/logo_693ff32328be1.png', 'Bosch is a leading German multinational engineering and technology company, founded by Robert Bosch in 1886, known globally for its innovation in mobility, industrial tech, consumer goods, and energy/building solutions. ', NULL, 'OTHERS', 'Bayan Lepas', 'Pulau Pinang', 'admin1', 'approved'),
(29, 'Openwave Computing (M) Sdn Bhd', 'MULTINATIONAL', 'The Gardens South Tower, Mid Valley City.', '', '', '', 'uploads/company_logos/logo_693ff6e803c5e.jpg', 'Openwave Computing Malaysia is a software and IT solutions provider specializing in enterprise business applications, software development, and digital transformation services for local and multinational clients. They help organizations improve operational efficiency through customized software solutions and technology consulting.', NULL, 'SOFTWARE DEVELOPMENT', 'Kuala Lumpur', 'Kuala Lumpur', 'BI22110033', 'approved'),
(30, 'P Cloud Sdn Bhd', 'PRIVATE', 'SOHO West 188, Jalan Wan Alwi.', '', '', 'https://www.pcloud.com.my', 'uploads/company_logos/logo_693ff83a75ea5.png', 'P Cloud Sdn Bhd is a business technology and software development company founded in Kuching, Sarawak, specializing in custom software solutions, mobile and web application development, IoT integration, AI chatbots, ERP/CRM systems, and enterprise systems to help businesses transform digitally and streamline operations.', NULL, 'SOFTWARE DEVELOPMENT', 'Kuching', 'Sarawak', 'BI22110033', 'pending'),
(32, 'ABC SDN BHD', 'PRIVATE', 'UMS', '', '', '', 'uploads/company_32.jpg', 'ICT-based company focusing on developing, implementing, or supporting technologies such as software, hardware, networks, and digital platforms.', 4.60, 'SOFTWARE DEVELOPMENT', 'Kota Kinabalu', 'Sabah', 'BI22110033', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `company_evaluation`
--

CREATE TABLE `company_evaluation` (
  `evaluation_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `evaluator_id` varchar(20) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `evaluated_on` datetime NOT NULL DEFAULT current_timestamp(),
  `last_reviewed_on` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_evaluation`
--

INSERT INTO `company_evaluation` (`evaluation_id`, `company_id`, `evaluator_id`, `score`, `remarks`, `comment`, `evaluated_on`, `last_reviewed_on`) VALUES
(1, 1, 'FKI0010', 4.00, 'Supervision: 4, Learning: 4, Communication: 3, Environment: 4, Relevance: 5', 'Good startup company for students to intern.', '2025-11-13 15:20:27', '2025-11-13 15:20:27'),
(2, 5, 'FKI0010', 4.40, 'Supervision: 5, Learning: 5, Communication: 3, Environment: 4, Relevance: 5', 'Good company for student to gain experience in the industry.', '2025-11-13 15:24:04', '2025-11-13 15:24:04'),
(3, 3, 'FKI0010', 4.20, 'Supervision: 3, Learning: 4, Communication: 4, Environment: 5, Relevance: 5', '', '2025-11-13 15:37:55', '2025-11-13 15:37:55'),
(4, 2, 'FKI0010', 3.00, 'Supervision: 3, Learning: 3, Communication: 3, Environment: 3, Relevance: 3', '', '2025-11-13 16:12:24', '2025-11-13 16:12:24'),
(5, 11, 'FKI0010', 4.60, 'Supervision: 5, Learning: 5, Communication: 4, Environment: 4, Relevance: 5', '', '2025-11-13 16:12:39', '2025-11-13 16:12:39'),
(6, 13, 'FKI0010', 5.00, 'Supervision: 5, Learning: 5, Communication: 5, Environment: 5, Relevance: 5', 'very good company for internship!', '2025-11-25 12:44:48', '2025-11-25 12:44:48'),
(7, 24, 'FKI0010', 4.00, 'Supervision: 4, Learning: 5, Communication: 3, Environment: 5, Relevance: 3', '', '2025-12-12 21:19:16', '2025-12-12 21:19:16'),
(8, 12, 'FKI0010', 4.80, 'Supervision: 5, Learning: 5, Communication: 5, Environment: 5, Relevance: 4', 'Very good company for students to intern', '2025-12-12 21:20:30', '2025-12-12 21:20:30'),
(9, 1, 'FKI0010', 0.00, 'Supervision: 0, Learning: 0, Communication: 0, Environment: 0, Relevance: 0', '', '2025-12-22 21:26:18', '2025-12-22 21:26:18'),
(10, 32, 'FKI0010', 4.60, 'Supervision: 5, Learning: 4, Communication: 5, Environment: 4, Relevance: 5', 'ok', '2026-01-06 13:15:07', '2026-01-06 13:15:07'),
(11, 5, 'FKI0010', 2.60, 'Supervision: 5, Learning: 1, Communication: 1, Environment: 5, Relevance: 1', 'scam', '2026-01-15 14:07:10', '2026-01-15 14:07:10');

-- --------------------------------------------------------

--
-- Table structure for table `extracurricular`
--

CREATE TABLE `extracurricular` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `activity` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `extracurricular`
--

INSERT INTO `extracurricular` (`id`, `student_id`, `activity`, `description`) VALUES
(9, 'BI22110033', 'Pet Therapy 2.0', 'Unit Hadiah dan Tajaan'),
(10, 'BI22110033', 'Technology Festival (Tech-Fest)', 'Unit Aktiviti'),
(11, 'BI22110033', 'Minggu Suai Mesra Kali Ke-30', 'Unit Teknikal dan Siaraya');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `company_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `submitted_on` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `student_id`, `company_id`, `rating`, `comment`, `submitted_on`, `is_visible`, `is_anonymous`) VALUES
(7, 'BI22110033', 13, 4, 'Had a wonderful experience here!', '2026-01-05 14:01:18', 1, 1),
(8, 'BI22110033', 32, 4, 'Good company', '2026-01-06 13:12:21', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `internship_experience`
--

CREATE TABLE `internship_experience` (
  `experience_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reflection` text DEFAULT NULL,
  `internship_cert_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `internship_experience`
--

INSERT INTO `internship_experience` (`experience_id`, `student_id`, `company_id`, `position`, `start_date`, `end_date`, `reflection`, `internship_cert_url`) VALUES
(2, 'BI22110990', 12, 'Data Analyst Intern', '2025-07-11', '2025-12-11', '', ''),
(3, 'BI22110033', 32, 'Junior Software Developer', '2026-01-06', '2026-05-06', 'Good company for internship', '');

-- --------------------------------------------------------

--
-- Table structure for table `leadership_role`
--

CREATE TABLE `leadership_role` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `role_title` varchar(100) NOT NULL,
  `organization` varchar(100) NOT NULL,
  `year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leadership_role`
--

INSERT INTO `leadership_role` (`id`, `student_id`, `role_title`, `organization`, `year`) VALUES
(4, 'BI22110033', 'Vice President', 'PMFKI', 2023);

-- --------------------------------------------------------

--
-- Table structure for table `program`
--

CREATE TABLE `program` (
  `program_code` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program`
--

INSERT INTO `program` (`program_code`, `program_name`) VALUES
(1001, 'Software Engineering'),
(1002, 'Network Engineering'),
(1003, 'Multimedia Technology'),
(1004, 'Business Computing'),
(1005, 'Data Science');

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
  `project_id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `project_title` varchar(100) NOT NULL,
  `project_link` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project`
--

INSERT INTO `project` (`project_id`, `student_id`, `project_title`, `project_link`, `description`) VALUES
(4, 'BI22110033', 'FKI Industrial Training Company Information and Ranking System with Career Readiness Score', '', 'Final Year Project');

-- --------------------------------------------------------

--
-- Table structure for table `selected_company`
--

CREATE TABLE `selected_company` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `approval_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `selected_company`
--

INSERT INTO `selected_company` (`id`, `submission_id`, `company_id`, `approval_status`) VALUES
(2, 2, 9, 'APPROVED'),
(3, 2, 10, 'APPROVED'),
(4, 2, 18, 'APPROVED'),
(5, 2, 19, 'PENDING'),
(6, 2, 20, 'REJECTED'),
(7, 2, 8, 'REJECTED'),
(8, 2, 11, 'APPROVED'),
(9, 2, 21, 'PENDING'),
(11, 2, 16, 'REJECTED'),
(12, 2, 13, 'APPROVED'),
(13, 2, 12, 'PENDING');

-- --------------------------------------------------------

--
-- Table structure for table `skill`
--

CREATE TABLE `skill` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `skill_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skill`
--

INSERT INTO `skill` (`id`, `student_id`, `skill_id`) VALUES
(53, 'BI22110033', 66),
(54, 'BI22110033', 9),
(55, 'BI22110033', 19),
(56, 'BI22110033', 5),
(57, 'BI22110033', 10);

-- --------------------------------------------------------

--
-- Table structure for table `skill_master`
--

CREATE TABLE `skill_master` (
  `skill_id` int(11) NOT NULL,
  `skill_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skill_master`
--

INSERT INTO `skill_master` (`skill_id`, `skill_name`) VALUES
(1, 'Python'),
(2, 'Java'),
(3, 'C++'),
(4, 'C#'),
(5, 'JavaScript'),
(6, 'TypeScript'),
(7, 'SQL'),
(8, 'NoSQL'),
(9, 'HTML'),
(10, 'CSS'),
(11, 'React'),
(12, 'Angular'),
(13, 'Vue.js'),
(14, 'Node.js'),
(15, 'Express.js'),
(16, 'Django'),
(17, 'Flask'),
(18, 'Ruby on Rails'),
(19, 'PHP'),
(20, 'Laravel'),
(21, 'Git'),
(22, 'GitHub'),
(23, 'GitLab'),
(24, 'Bitbucket'),
(25, 'RESTful APIs'),
(26, 'GraphQL'),
(27, 'Docker'),
(28, 'Kubernetes'),
(29, 'AWS'),
(30, 'Azure'),
(31, 'Google Cloud Platform'),
(32, 'Machine Learning'),
(33, 'Deep Learning'),
(34, 'TensorFlow'),
(35, 'PyTorch'),
(36, 'Scikit-learn'),
(37, 'Pandas'),
(38, 'NumPy'),
(39, 'Matplotlib'),
(40, 'Seaborn'),
(41, 'Data Analysis'),
(42, 'Data Visualization'),
(43, 'Big Data'),
(44, 'Hadoop'),
(45, 'Spark'),
(46, 'ETL'),
(47, 'Cybersecurity'),
(48, 'Penetration Testing'),
(49, 'Network Security'),
(50, 'Cloud Security'),
(51, 'Agile'),
(52, 'Scrum'),
(53, 'Kanban'),
(54, 'Project Management'),
(55, 'DevOps'),
(56, 'CI/CD'),
(57, 'Microservices'),
(58, 'REST API Development'),
(59, 'Mobile App Development'),
(60, 'Android Development'),
(61, 'iOS Development'),
(62, 'Swift'),
(63, 'Kotlin'),
(64, 'Flutter'),
(65, 'React Native'),
(66, 'UI/UX Design'),
(67, 'Figma'),
(68, 'Adobe XD'),
(69, 'Web Design'),
(70, 'Software Testing'),
(71, 'Unit Testing'),
(72, 'Integration Testing'),
(73, 'Selenium'),
(74, 'Jenkins'),
(75, 'Ansible'),
(76, 'Terraform'),
(77, 'Linux'),
(78, 'Windows Server'),
(79, 'VMware'),
(80, 'Virtualization'),
(81, 'Blockchain'),
(82, 'Cryptography'),
(83, 'IoT'),
(84, 'Edge Computing'),
(85, 'Computer Networks'),
(86, 'TCP/IP'),
(87, 'Routing and Switching'),
(88, 'Database Management'),
(89, 'Data Warehousing'),
(90, 'Business Intelligence'),
(91, 'Natural Language Processing'),
(92, 'Computer Vision'),
(93, 'Robotics'),
(94, 'AR/VR Development'),
(95, 'Game Development'),
(96, 'Unity'),
(97, 'Unreal Engine'),
(98, 'Problem Solving'),
(99, 'Algorithm Design'),
(100, 'Data Structures'),
(101, 'Communication'),
(102, 'Teamwork'),
(103, 'Collaboration'),
(104, 'Leadership'),
(105, 'Time Management'),
(106, 'Critical Thinking'),
(107, 'Adaptability'),
(108, 'Creativity'),
(109, 'Decision Making'),
(110, 'Attention to Detail'),
(111, 'Conflict Resolution'),
(112, 'Emotional Intelligence'),
(113, 'Negotiation'),
(114, 'Presentation Skills'),
(115, 'Analytical Thinking'),
(116, 'Work Ethic'),
(117, 'Interpersonal Skills'),
(118, 'Stress Management'),
(119, 'Flexibility'),
(120, 'Mentoring'),
(121, 'Networking'),
(122, 'Customer Service'),
(123, 'Business Acumen'),
(124, 'Project Planning'),
(125, 'Strategic Thinking'),
(126, 'Research Skills'),
(127, 'Innovation'),
(128, 'Self-Motivation'),
(129, 'Empathy'),
(130, 'Active Listening'),
(131, 'Organizational Skills'),
(132, 'Resourcefulness'),
(133, 'Collaboration Tools (Slack, Teams, etc.)'),
(134, 'Documentation Skills'),
(135, 'Learning Agility'),
(136, 'Persuasion'),
(137, 'AWS Lambda'),
(138, 'Google Firebase'),
(139, 'ElasticSearch'),
(140, 'Redis'),
(141, 'MongoDB'),
(142, 'PostgreSQL'),
(143, 'MySQL'),
(144, 'Oracle DB'),
(145, 'CI/CD Pipelines'),
(146, 'Serverless Architecture'),
(147, 'Graph Databases'),
(148, 'Neo4j'),
(149, 'Data Mining'),
(150, 'Data Modeling'),
(151, 'Business Intelligence Tools'),
(152, 'Power BI'),
(153, 'Tableau'),
(154, 'QlikView'),
(155, 'ERP Systems'),
(156, 'SAP'),
(157, 'CRM Systems'),
(158, 'Salesforce'),
(159, 'Linux Shell Scripting'),
(160, 'Bash'),
(161, 'PowerShell'),
(162, 'Networking Protocols'),
(163, 'HTTP/HTTPS'),
(164, 'FTP/SFTP'),
(165, 'SMTP'),
(166, 'SNMP'),
(167, 'Load Balancing'),
(168, 'Caching Techniques'),
(169, 'Performance Tuning'),
(170, 'Database Indexing'),
(171, 'SQL Optimization'),
(172, 'API Security'),
(173, 'OAuth'),
(174, 'JWT'),
(175, 'OpenID Connect'),
(176, 'Pen Testing Tools'),
(177, 'Wireshark'),
(178, 'Metasploit'),
(179, 'Malware Analysis'),
(180, 'Incident Response'),
(181, 'Disaster Recovery'),
(182, 'Cloud Migration'),
(183, 'Container Orchestration'),
(184, 'Prometheus'),
(185, 'Grafana'),
(186, 'Elastic Stack'),
(187, 'Web Accessibility'),
(188, 'SEO Basics'),
(189, 'Responsive Design'),
(190, 'Cross-browser Testing'),
(191, 'Mobile Optimization'),
(192, 'Progressive Web Apps'),
(193, 'GraphQL APIs'),
(194, 'REST API Security'),
(195, 'API Rate Limiting'),
(196, 'Caching Strategies'),
(197, 'Server Management'),
(198, 'System Architecture Design'),
(199, 'Software Architecture Patterns'),
(200, 'MVC'),
(201, 'MVVM'),
(202, 'Design Patterns'),
(203, 'Event-Driven Architecture'),
(204, 'Message Queues'),
(205, 'RabbitMQ'),
(206, 'Kafka'),
(207, 'Functional Programming'),
(208, 'Object-Oriented Programming'),
(209, 'Test-Driven Development'),
(210, 'Behavior-Driven Development'),
(211, 'Version Control Best Practices'),
(212, 'Code Review Practices'),
(213, 'Debugging'),
(214, 'Profiling'),
(215, 'Refactoring'),
(216, 'Legacy System Maintenance'),
(217, 'Ethical Hacking'),
(218, 'Digital Forensics'),
(219, 'Cloud Security Standards'),
(220, 'ISO 27001'),
(221, 'GDPR Compliance'),
(222, 'Shariah-compliant IT Systems'),
(223, 'FinTech Systems'),
(224, 'Payment Gateways'),
(225, 'Cryptocurrency Platforms'),
(226, 'Smart Contracts'),
(227, 'Augmented Reality'),
(228, 'Virtual Reality'),
(229, 'Mixed Reality'),
(230, 'Unity 3D'),
(231, 'Unreal Engine Blueprints'),
(232, 'Shader Programming'),
(233, 'Computer Graphics'),
(234, 'OpenGL'),
(235, 'DirectX'),
(236, 'Vulkan'),
(237, '3D Modeling'),
(238, 'Blender'),
(239, 'Maya'),
(240, 'Cinema 4D'),
(241, 'Animation Principles'),
(242, 'Game AI'),
(243, 'Physics Engines'),
(244, 'Networking Concepts for Multiplayer Games'),
(245, 'Cloud Gaming'),
(246, 'Mobile Game Monetization'),
(247, 'DevSecOps'),
(248, 'Security Testing'),
(249, 'Pen Testing Methodologies');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` varchar(20) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `program_code` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `profile_picture`, `program_code`) VALUES
('FKI0010', 'uploads/69424ad3c6281_nabil.jpg', 1001),
('FKI1039', 'uploads/default_profile.png', 1002),
('FKI2025', 'uploads/6915a15a45119_2837b008f10a177a4173b7f7e50a23ed.jpg', 1001);

-- --------------------------------------------------------

--
-- Table structure for table `staff_role`
--

CREATE TABLE `staff_role` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(20) DEFAULT NULL,
  `role` enum('LECTURER','SUPERVISOR','COORDINATOR') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_role`
--

INSERT INTO `staff_role` (`id`, `staff_id`, `role`) VALUES
(1, 'FKI0010', 'SUPERVISOR'),
(19, 'FKI2025', 'COORDINATOR'),
(23, 'FKI1039', 'SUPERVISOR');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` varchar(20) NOT NULL,
  `program_code` int(11) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `ic_passport_no` varchar(30) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `graduation_year` int(11) DEFAULT NULL,
  `internship_status` enum('LOOKING','ONGOING','COMPLETED') DEFAULT NULL,
  `portfolio_link` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `supervisor_id` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `program_code`, `contact_number`, `ic_passport_no`, `cgpa`, `graduation_year`, `internship_status`, `portfolio_link`, `profile_picture`, `about`, `supervisor_id`) VALUES
('BI22110033', 1001, '0106629311', '030321134662', 3.69, 2026, 'COMPLETED', 'https://www.linkedin.com/in/whiltierna-vincent-69a775253?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=ios_app', 'img/1761719094_6901b336844bd.jpg', 'An aspiring software engineer currently in the third year of my Computer Science (Software Engineering) programme. I am especially interested in full-stack development, database management, computer graphics, and UI/UX design.', 'FKI0010'),
('BI22110990', 1005, NULL, NULL, NULL, NULL, 'COMPLETED', NULL, 'img/default_profile.png', NULL, 'FKI1039'),
('BI23110505', 1005, NULL, NULL, NULL, 2027, 'LOOKING', NULL, 'img/1763022627_691597235803e.jpg', NULL, NULL),
('BI23111998', 1002, NULL, NULL, NULL, 2027, 'LOOKING', NULL, 'img/1763024434_69159e32db7da.jpg', NULL, 'FKI0010');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('STUDENT','STAFF','ADMIN') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `name`, `email`, `password`, `role`) VALUES
('admin1', 'Admin', 'admin1@email.com', '$2y$10$4Uwl0hrDxo6mZE9rX4LAMe3vfoMZxvXlRt/Cu1XFzc1q2doofVc0q', 'ADMIN'),
('BI22110033', 'Whiltierna Vincent', 'whiltierna@email.com', '$2y$10$ZZ4qkVwxR08vc2p7IxQPB.8rb6tx0NMCMmOMrDt3xOSCelSglBAoq', 'STUDENT'),
('BI22110990', 'Wena', 'wena1@email.com', '$2y$10$lcaA.pw8RSSRnLSZ5NgGyugujBGRrCag1AZ73PZXarVoKuL7cLZ9i', 'STUDENT'),
('BI23110505', 'Sabrina Dahlia', 'sabrinad@email.com', '$2y$10$hZv.3ej4ttmgHDqPP22YOeEz94QjOHyisKL1KcZHj/R.jTFwsXWEy', 'STUDENT'),
('BI23111998', 'Omar Apollo', 'omar@email.com', '$2y$10$T/P75o.ZugdjPMQDBSmL9O9FPb3sfIM3TJMOkYIVhBM6W/ipXnqQ2', 'STUDENT'),
('FKI0010', 'Nabil Syahmi', 'nabilsyahmi@email.com', '$2y$10$xUBU4nKYSyaWBgjAap9U1e0asuykAXXY5j6Lg7RXOzPyM3ACEVZUC', 'STAFF'),
('FKI1039', 'Khairun Ahmad', 'khai@email.com', '$2y$10$Ts/9Erx95szegsCktDf9peM0AQ.QloSJtNuP.H4.sR2slfXFYr/hq', 'STAFF'),
('FKI2025', 'Gerard Rayner', 'grayner@email.com', '$2y$10$VLGsCmbFpR4S4Ge1RGMcGe2odj6YJo1pY3hActsvNT1Fjr9YuLA5K', 'STAFF');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `fk_posted_by` (`posted_by`);

--
-- Indexes for table `bli01_form`
--
ALTER TABLE `bli01_form`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `fk_student` (`student_id`);

--
-- Indexes for table `career_readiness_score`
--
ALTER TABLE `career_readiness_score`
  ADD PRIMARY KEY (`crs_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `certification`
--
ALTER TABLE `certification`
  ADD PRIMARY KEY (`cert_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`company_id`),
  ADD KEY `fk_created_by` (`created_by`);

--
-- Indexes for table `company_evaluation`
--
ALTER TABLE `company_evaluation`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `evaluator_id` (`evaluator_id`);

--
-- Indexes for table `extracurricular`
--
ALTER TABLE `extracurricular`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `fk_feedback_student` (`student_id`),
  ADD KEY `fk_feedback_company` (`company_id`);

--
-- Indexes for table `internship_experience`
--
ALTER TABLE `internship_experience`
  ADD PRIMARY KEY (`experience_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `leadership_role`
--
ALTER TABLE `leadership_role`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `program`
--
ALTER TABLE `program`
  ADD PRIMARY KEY (`program_code`);

--
-- Indexes for table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `selected_company`
--
ALTER TABLE `selected_company`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submission_id` (`submission_id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `skill`
--
ALTER TABLE `skill`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `skill_id` (`skill_id`);

--
-- Indexes for table `skill_master`
--
ALTER TABLE `skill_master`
  ADD PRIMARY KEY (`skill_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `program_code` (`program_code`);

--
-- Indexes for table `staff_role`
--
ALTER TABLE `staff_role`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_staff_id` (`staff_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `program_code` (`program_code`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `password` (`password`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bli01_form`
--
ALTER TABLE `bli01_form`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `career_readiness_score`
--
ALTER TABLE `career_readiness_score`
  MODIFY `crs_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `certification`
--
ALTER TABLE `certification`
  MODIFY `cert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `company_evaluation`
--
ALTER TABLE `company_evaluation`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `extracurricular`
--
ALTER TABLE `extracurricular`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `internship_experience`
--
ALTER TABLE `internship_experience`
  MODIFY `experience_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `leadership_role`
--
ALTER TABLE `leadership_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `project`
--
ALTER TABLE `project`
  MODIFY `project_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `selected_company`
--
ALTER TABLE `selected_company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `skill`
--
ALTER TABLE `skill`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `skill_master`
--
ALTER TABLE `skill_master`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

--
-- AUTO_INCREMENT for table `staff_role`
--
ALTER TABLE `staff_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bli01_form`
--
ALTER TABLE `bli01_form`
  ADD CONSTRAINT `fk_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `career_readiness_score`
--
ALTER TABLE `career_readiness_score`
  ADD CONSTRAINT `career_readiness_score_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `certification`
--
ALTER TABLE `certification`
  ADD CONSTRAINT `certification_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `company`
--
ALTER TABLE `company`
  ADD CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `company_evaluation`
--
ALTER TABLE `company_evaluation`
  ADD CONSTRAINT `company_evaluation_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_evaluation_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE;

--
-- Constraints for table `extracurricular`
--
ALTER TABLE `extracurricular`
  ADD CONSTRAINT `extracurricular_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feedback_student` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `internship_experience`
--
ALTER TABLE `internship_experience`
  ADD CONSTRAINT `internship_experience_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `internship_experience_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`);

--
-- Constraints for table `leadership_role`
--
ALTER TABLE `leadership_role`
  ADD CONSTRAINT `leadership_role_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `project_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `selected_company`
--
ALTER TABLE `selected_company`
  ADD CONSTRAINT `selected_company_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `bli01_form` (`submission_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `selected_company_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE CASCADE;

--
-- Constraints for table `skill`
--
ALTER TABLE `skill`
  ADD CONSTRAINT `skill_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `skill_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skill_master` (`skill_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`program_code`) REFERENCES `program` (`program_code`);

--
-- Constraints for table `staff_role`
--
ALTER TABLE `staff_role`
  ADD CONSTRAINT `staff_role_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `student_ibfk_2` FOREIGN KEY (`program_code`) REFERENCES `program` (`program_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
