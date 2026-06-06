-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2026 at 08:36 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ddss`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_activity_logs`
--

CREATE TABLE `admin_activity_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `admin_username` varchar(50) NOT NULL,
  `activity` text NOT NULL,
  `target_user` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `admin_dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `admin_dashboard_stats` (
`metric` varchar(23)
,`value` decimal(32,0)
,`category` varchar(7)
);

-- --------------------------------------------------------

--
-- Table structure for table `cohort_trends`
--

CREATE TABLE `cohort_trends` (
  `id` int(11) NOT NULL,
  `prediction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `trends_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`trends_data`)),
  `intervention_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`intervention_summary`)),
  `total_students` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cohort_trends`
--

INSERT INTO `cohort_trends` (`id`, `prediction_date`, `trends_data`, `intervention_summary`, `total_students`, `created_at`) VALUES
(98, '2026-05-15 06:19:10', '{\"total_students\": 70, \"risk_level_changes\": {\"escalating\": 44, \"improving\": 3, \"stable\": 23, \"escalating_percentage\": 62.857142857142854, \"improving_percentage\": 4.285714285714286}, \"risk_distribution\": {\"current_semester\": {\"high_risk\": 16, \"medium_risk\": 40, \"low_risk\": 14}, \"next_semester\": {\"high_risk\": 46, \"medium_risk\": 24, \"low_risk\": 0}}, \"intervention_urgency\": {\"critical\": 46, \"high\": 20, \"medium\": 4, \"low\": 0}, \"average_probability_change\": 47.00314285714286, \"students_needing_immediate_intervention\": 66}', '{\"critical_count\": 46, \"high_priority_count\": 20, \"total_at_risk\": 66}', 70, '2026-05-15 06:19:10');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `tuition` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delete_status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`id`, `course_name`, `description`, `tuition`, `created_at`, `delete_status`) VALUES
(1, 'BSCS', 'Study of computation and programming', 50000.00, '2025-05-12 13:18:56', 0),
(2, 'BSIT', 'Focuses on information systems and networks', 48000.00, '2025-05-12 13:18:56', 0),
(3, 'BSBA', 'Covers management and business strategies', 45000.00, '2025-05-12 13:18:56', 0),
(4, 'BSA', 'Focuses on accounting principles and practices', 47000.00, '2025-05-12 13:18:56', 0),
(5, 'BSECE', 'Covers electronics, communication systems, and circuits', 52000.00, '2025-05-12 13:18:56', 0),
(6, 'BSN', 'Prepares students for nursing and healthcare services', 55000.00, '2025-05-12 13:18:56', 0),
(7, 'BSPsych', 'Focuses on psychological theories and human behavior', 46000.00, '2025-05-12 13:18:56', 0),
(8, 'BSHRM', 'Covers hospitality and restaurant management', 48000.00, '2025-05-12 13:18:56', 0),
(9, 'BSCE', 'Focuses on civil engineering principles and construction', 53000.00, '2025-05-12 13:18:56', 0),
(10, 'BSEE', 'Covers electrical systems and power engineering', 52000.00, '2025-05-12 13:18:56', 0),
(11, 'BSME', 'Focuses on mechanical engineering design and systems', 51000.00, '2025-05-12 13:18:56', 0),
(12, 'BSArch', 'Covers architecture design and urban planning', 54000.00, '2025-05-12 13:18:56', 0),
(13, 'BSEd', 'Prepares students for teaching and education fields', 42000.00, '2025-05-12 13:18:56', 0),
(14, 'BSAIS', 'Focuses on accounting information systems', 47000.00, '2025-05-12 13:18:56', 0),
(15, 'BSPH', 'Prepares students for public health services', 50000.00, '2025-05-12 13:18:56', 0),
(16, 'BSMT', 'qwerty', 0.00, '2025-05-14 11:31:44', 0);

-- --------------------------------------------------------

--
-- Table structure for table `fees_transaction`
--

CREATE TABLE `fees_transaction` (
  `id` int(255) NOT NULL,
  `stdid` varchar(255) NOT NULL,
  `paid` int(255) NOT NULL,
  `submitdate` datetime NOT NULL,
  `transcation_remark` text NOT NULL,
  `course` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fees_transaction`
--

INSERT INTO `fees_transaction` (`id`, `stdid`, `paid`, `submitdate`, `transcation_remark`, `course`, `created_at`) VALUES
(76, '45', 0, '2025-05-15 00:00:00', '', '', '2025-07-07 08:18:44'),
(77, '46', 20000, '2025-05-15 00:00:00', 'dp', '', '2025-07-07 08:18:44'),
(78, '46', 5000, '2025-05-14 00:00:00', 'paid', 'BSIT', '2025-07-07 08:18:44'),
(79, '5', 1000, '2025-05-14 00:00:00', 'paid', 'BS Computer Science', '2025-07-07 08:18:44');

-- --------------------------------------------------------

--
-- Table structure for table `prediction_requests`
--

CREATE TABLE `prediction_requests` (
  `id` int(11) NOT NULL,
  `request_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `records_count` int(11) DEFAULT NULL,
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `prediction_started_at` timestamp NULL DEFAULT NULL,
  `prediction_completed_at` timestamp NULL DEFAULT NULL,
  `predictions_generated` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prediction_requests`
--

INSERT INTO `prediction_requests` (`id`, `request_timestamp`, `year`, `semester`, `table_name`, `records_count`, `uploaded_by_user_id`, `uploaded_by_username`, `status`, `prediction_started_at`, `prediction_completed_at`, `predictions_generated`, `error_message`) VALUES
(48, '2026-05-15 06:18:58', 2020, '1', 'student_2020_sem_1', 70, 7, 'testuser1', 'completed', '2026-05-15 06:19:01', '2026-05-15 06:19:10', 70, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `id` int(255) NOT NULL,
  `StudentID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `emailid` varchar(255) NOT NULL,
  `sname` varchar(255) NOT NULL,
  `joindate` datetime NOT NULL,
  `about` text NOT NULL,
  `contact` varchar(255) NOT NULL,
  `fees` int(255) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `balance` int(255) NOT NULL,
  `delete_status` enum('0','1') NOT NULL DEFAULT '0',
  `course` varchar(100) NOT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`id`, `StudentID`, `emailid`, `sname`, `joindate`, `about`, `contact`, `fees`, `year`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `created_at`) VALUES
(5, 'OLFU2025-002', 'christinemoore@gmail.com', 'Christine Moore', '2020-02-14 00:00:00', 'Demo About Text', '7566969650', 3660, 4, 2660, '0', 'BS Computer Science', NULL, NULL, '2025-07-07 08:19:42'),
(10, 'OLFU2025-003', 'leomaxwell@gmail.com', 'Leo Maxwell', '2021-01-14 00:00:00', 'new enrollment', '7563690002', 5120, 4, 620, '0', 'BS Computer Science', NULL, NULL, '2025-07-07 08:19:42'),
(11, 'OLFU2025-004', 'arandrew@gmail.com', 'Andrew Arnette', '2021-03-26 00:00:00', 'new enrollment', '3520120006', 5200, 4, 1600, '0', 'BS Computer Science', NULL, NULL, '2025-07-07 08:19:42'),
(12, 'OLFU2025-005', 'jonathan@gmail.com', 'Jonathan Odell', '2019-10-11 00:00:00', 'old enrollment', '4230001205', 6900, 4, 3000, '0', 'BS Computer Science', NULL, NULL, '2025-07-07 08:19:42'),
(13, 'OLFU2025-006', 'benjamin@gmail.com', 'Benjamin L . Russell', '2021-04-01 00:00:00', 'new enroll', '9012568500', 3600, 3, 2600, '0', 'BSIT', NULL, NULL, '2025-07-07 08:19:42'),
(14, 'OLFU2025-007', 'kathrynmc@gmail.com', 'Kathryn McKeehan', '2021-04-01 00:00:00', 'new student from central branch', '9751250006', 5000, 4, 2500, '0', 'BSIT', NULL, NULL, '2025-07-07 08:19:42'),
(15, 'OLFU2025-008', 'davidandersn@gmail.com', 'David Anderson', '2018-04-01 00:00:00', 'std from woodcreek branch', '7412036660', 7900, 4, 5800, '0', 'BSIT', NULL, NULL, '2025-07-07 08:19:42'),
(16, 'OLFU2025-009', 'joannnt@gmail.com', 'Joann TSaylor', '2019-04-06 00:00:00', 'std from riverview branch', '9031480360', 6100, 4, 3200, '0', 'Nursing', NULL, NULL, '2025-07-07 08:19:42'),
(17, 'OLFU2025-010', 'kevinrogers@gmail.com', 'Kevin Rogers', '2021-04-18 00:00:00', 'fresh enrollment', '9031476969', 5500, 4, 0, '0', 'Nursing', NULL, NULL, '2025-07-07 08:19:42'),
(18, 'OLFU2025-011', 'chavez.ly@gmail.com', 'Lyle Chavez', '2021-01-03 00:00:00', 'central student', '8520696976', 3600, 3, 100, '0', 'Nursing', NULL, NULL, '2025-07-07 08:19:42'),
(20, 'OLFU2025-012', 'none@dem.com', 'Demo', '2021-04-01 00:00:00', 'none', '785555555', 0, 1, 0, '0', 'BS Computer Science', NULL, NULL, '2025-07-07 08:19:42'),
(21, 'OLFU2025-013', 'marcellak@gmail.com', 'Marcella Keyes', '2021-04-04 00:00:00', 'fresh enrollment', '7456000020', 4900, 4, 2900, '0', 'BSIT', NULL, NULL, '2025-07-07 08:19:42'),
(22, 'OLFU2025-014', 'george@gmail.com', 'George Russell', '2021-02-21 00:00:00', 'none', '2004568500', 4900, 4, 0, '0', 'Nursing', NULL, NULL, '2025-07-07 08:19:42'),
(23, 'OLFU2025-015', 'willwilliams55@gmail.com', 'Will Williams', '2021-04-04 00:00:00', 'none', '8621245000', 12000, 4, 7000, '0', 'BSIT', NULL, NULL, '2025-07-07 08:19:42'),
(24, 'OLFU2025-016', 'staceyelisw@gmail.com', 'Stacey Ellsworth', '2021-03-29 00:00:00', 'new enrollment', '6570002549', 7900, 4, 0, '0', 'BS Computer Science', NULL, NULL, '2025-07-07 08:19:42'),
(46, 'OLFU2025-001', 'john.christian.azores2016@gmail.com', 'john christian azores', '2025-05-15 00:00:00', '', '9568839281', 58000, 3, 33000, '0', 'BSIT', 0, 0, '2025-07-07 08:19:42');

-- --------------------------------------------------------

--
-- Table structure for table `student_2020_sem_1`
--

CREATE TABLE `student_2020_sem_1` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2020_sem_1`
--

INSERT INTO `student_2020_sem_1` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(1, 'OLFU2020-001', 'Jason Reed', 'jasonreed8472@gmail.com', NULL, 'new enrollment', '9174823591', 40000, 2, '1', 10000, 0, 'MLS', 87, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(2, 'OLFU2020-002', 'Sarah Miles', 'sarahmiles2290@gmail.com', NULL, 'new enrollment', '9207638452', 35000, 2, '1', 10800, 0, 'MLS', 92, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(3, 'OLFU2020-003', 'Michael Knox', 'michaelknox9913@gmail.com', NULL, 'old enrollment', '9381509274', 38000, 3, '1', 15200, 0, 'MLS', 78, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(4, 'OLFU2020-004', 'Emily Turner', 'emilyturner4023@gmail.com', NULL, 'new enroll', '9456293108', 35000, 2, '1', 9800, 0, 'MLS', 85, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(5, 'OLFU2020-005', 'Brandon Cole', 'brandoncole6731@gmail.com', NULL, 'new student', '9512087349', 48000, 2, '1', 18600, 0, 'BS CE', 91, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(6, 'OLFU2020-006', 'Ashley Bennett', 'ashleybennett2156@gmail.com', NULL, 'new enrollment', '9764150923', 46000, 2, '1', 17800, 0, 'BSA', 76, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(7, 'OLFU2020-007', 'Robert Fields', 'robertfields3257@gmail.com', NULL, 'new enrollment', '9895276104', 40000, 4, '1', 14500, 0, 'MLS', 89, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(8, 'OLFU2020-008', 'Megan Harris', 'meganharris9142@gmail.com', NULL, 'old enrollment', '9913058427', 44000, 3, '1', 16200, 0, 'BS PHARMA', 83, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(9, 'OLFU2020-009', 'David Long', 'davidlong3580@gmail.com', NULL, 'new enroll', '9394782051', 50000, 1, '1', 5500, 0, 'BSITM TTO', 95, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(10, 'OLFU2020-010', 'Laura Wells', 'laurawells7821@gmail.com', NULL, 'new student', '9186327094', 39000, 2, '1', 14200, 0, 'MLS', 74, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(11, 'OLFU2020-011', 'Kyle Edwards', 'kyleedwards4675@gmail.com', NULL, 'new student', '9289471036', 41000, 2, '1', 7800, 0, 'BSN', 88, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(12, 'OLFU2020-012', 'Tiffany Nguyen', 'tiffanynguyen3042@gmail.com', NULL, 'new enrollment', '9472658319', 43000, 3, '1', 9200, 0, 'MLS', 82, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(13, 'OLFU2020-013', 'Jonathan Scott', 'jonathanscott1803@gmail.com', NULL, 'new enrollment', '9508496723', 47000, 2, '1', 17900, 0, 'MLS', 90, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(14, 'OLFU2020-014', 'Rachel Green', 'rachelgreen9024@gmail.com', NULL, 'old enrollment', '9663125708', 45000, 4, '1', 16500, 0, 'BS MEDTECH', 77, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(15, 'OLFU2020-015', 'Matthew Hayes', 'matthewhayes6739@gmail.com', NULL, 'new enroll', '9708495312', 42000, 4, '1', 8900, 0, 'BS CE', 86, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(16, 'OLFU2020-016', 'Olivia Dixon', 'oliviadixon2019@gmail.com', NULL, 'new student', '9812375946', 38000, 4, '1', 13800, 0, 'BSN', 93, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(17, 'OLFU2020-017', 'Alex Morgan', 'alexmorgan4286@gmail.com', NULL, 'old enrollment', '9084726531', 49000, 2, '1', 11600, 0, 'MLS', 79, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(18, 'OLFU2020-018', 'Natalie Brown', 'nataliebrown1074@gmail.com', NULL, 'new enroll', '9291564802', 36000, 3, '1', 12800, 0, 'BS CE', 84, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(19, 'OLFU2020-019', 'Jacob Watts', 'jacobwatts5018@gmail.com', NULL, 'new student', '9498213057', 44000, 3, '1', 16200, 0, 'BSN', 88, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(20, 'OLFU2020-020', 'Samantha Cook', 'samanthacook7385@gmail.com', NULL, 'new enrollment', '9984172603', 40000, 2, '1', 14800, 0, 'BS HACLO', 75, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(21, 'OLFU2020-021', 'Daniel Allen', 'danielallen9204@gmail.com', NULL, 'new enrollment', '9175321498', 46000, 2, '1', 10900, 0, 'BS HACLO', 91, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(22, 'OLFU2020-022', 'Chelsea Rogers', 'chelsearogers3451@gmail.com', NULL, 'old enrollment', '9206845371', 41000, 1, '1', 15600, 0, 'BS HACLO', 87, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(23, 'OLFU2020-023', 'Nicholas Price', 'nicholasprice7149@gmail.com', NULL, 'new enroll', '9382596814', 30000, 1, '1', 4200, 0, 'BSN', 94, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(24, 'OLFU2020-024', 'Heather Lopez', 'heatherlopez3602@gmail.com', NULL, 'new student', '9457038209', 43000, 2, '1', 16800, 0, 'BSIT', 81, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(25, 'OLFU2020-025', 'Brian Wood', 'brianwood8763@gmail.com', NULL, 'old enrollment', '9513147962', 25000, 3, '1', 3200, 0, 'HUMSS', 85, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(26, 'OLFU2020-026', 'Andrea Wilson', 'andreawilson5026@gmail.com', NULL, 'new enroll', '9765289035', 37000, 4, '1', 10500, 0, 'BS CE', 78, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(27, 'OLFU2020-027', 'Steven Martin', 'stevenmartin6840@gmail.com', NULL, 'new student', '9896342178', 45000, 3, '1', 17200, 0, 'BSA', 92, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(28, 'OLFU2020-028', 'Hannah Kelly', 'hannahkelly2134@gmail.com', NULL, 'old enrollment', '9917423501', 42000, 4, '1', 15900, 0, 'BS ITM TTO', 86, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(29, 'OLFU2020-029', 'Tyler Hill', 'tylerhill9762@gmail.com', NULL, 'new enroll', '9395894613', 38000, 2, '1', 11400, 0, 'MLS', 74, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(30, 'OLFU2020-030', 'Victoria Morris', 'victoriamorris3947@gmail.com', NULL, 'new student', '9187456024', 47000, 2, '1', 17500, 0, 'BSN', 89, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(31, 'OLFU2020-031', 'Austin James', 'austinjames1409@gmail.com', NULL, 'old enrollment', '9288567135', 39000, 2, '1', 10800, 0, 'MLS', 83, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(32, 'OLFU2020-032', 'Kayla Patel', 'kaylapatel2176@gmail.com', NULL, 'new enroll', '9479368246', 44000, 2, '1', 16800, 0, 'BSN', 95, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(33, 'OLFU2020-033', 'Justin Brooks', 'justinbrooks8021@gmail.com', NULL, 'new student', '9500479357', 40000, 2, '1', 14700, 0, 'MLS', 80, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(34, 'OLFU2020-034', 'Madison Ward', 'madisonward2743@gmail.com', NULL, 'old enrollment', '9661580468', 46000, 1, '1', 17100, 0, 'BSN', 87, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(35, 'OLFU2020-035', 'Joshua Ross', 'joshuaross4589@gmail.com', NULL, 'new enroll', '9702691579', 48000, 1, '1', 18400, 0, 'BS PHARMA', 76, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(36, 'OLFU2020-036', 'Amber Campbell', 'ambercampbell6742@gmail.com', NULL, 'new student', '9813702680', 43000, 1, '1', 16000, 0, 'BS PSYCH', 90, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(37, 'OLFU2020-037', 'Christopher Young', 'christopheryoung1093@gmail.com', NULL, 'new enroll', '9084813791', 41000, 2, '1', 15200, 0, 'BS BIO', 84, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(38, 'OLFU2020-038', 'Kelsey Mitchell', 'kelseymitchell7351@gmail.com', NULL, 'new student', '9294814902', 35000, 2, '1', 9600, 0, 'BSN', 88, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(39, 'OLFU2020-039', 'Aaron Murphy', 'aaronmurphy2480@gmail.com', NULL, 'old enrollment', '9495926013', 36000, 2, '1', 10200, 0, 'MLS', 82, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(40, 'OLFU2020-040', 'Danielle Foster', 'daniellefoster6247@gmail.com', NULL, 'new enroll', '9987037124', 37000, 4, '1', 11200, 0, 'BS CS', 93, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(41, 'OLFU2020-041', 'Ryan Stevens', 'ryanstevens5832@gmail.com', NULL, 'new student', '9176148235', 45000, 3, '1', 16800, 0, 'MLS', 79, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(42, 'OLFU2020-042', 'Melissa Cooper', 'melissacooper8139@gmail.com', NULL, 'old enrollment', '9207959346', 38000, 2, '1', 10600, 0, 'BSN', 85, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(43, 'OLFU2020-043', 'Kevin Carter', 'kevincarter9407@gmail.com', NULL, 'new enroll', '9389060457', 52000, 2, '1', 5800, 0, 'BSITM TTO', 91, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(44, 'OLFU2020-044', 'Brittany Reed', 'brittanyreed1294@gmail.com', NULL, 'new student', '9450171568', 42000, 2, '1', 9400, 0, 'BSN', 77, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(45, 'OLFU2020-045', 'Joseph Flores', 'josephflores6721@gmail.com', NULL, 'new enroll', '9511282679', 47000, 2, '1', 17600, 0, 'BS PSYCH', 86, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(46, 'OLFU2020-046', 'Courtney Price', 'courtneyprice3086@gmail.com', NULL, 'new student', '9762393780', 49000, 1, '1', 8200, 0, 'BS PSYCH', 94, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(47, 'OLFU2020-047', 'Ben Sanders', 'bensanders1250@gmail.com', NULL, 'old enrollment', '9893404891', 41000, 1, '1', 15100, 0, 'BS HACLO', 81, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(48, 'OLFU2020-048', 'Emma Richards', 'emmarichards7815@gmail.com', NULL, 'new enroll', '9914515902', 55000, 3, '1', 7200, 0, 'BSITM TTO', 88, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(49, 'OLFU2020-049', 'Jeremy Griffin', 'jeremygriffin3492@gmail.com', NULL, 'new student', '9395626013', 35000, 2, '1', 9800, 0, 'BS CE', 75, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(50, 'OLFU2020-050', 'Kaitlyn Perry', 'kaitlynperry9216@gmail.com', NULL, 'new enroll', '9186737124', 44000, 4, '1', 16400, 0, 'BS HACLO', 92, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(51, 'OLFU2020-051', 'Mark Ward', 'markward8554@gmail.com', NULL, 'new student', '9287848235', 39000, 2, '1', 10300, 0, 'BS CE', 87, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(52, 'OLFU2020-052', 'Haley Ross', 'haleyross2907@gmail.com', NULL, 'old enrollment', '9478959346', 46000, 3, '1', 11800, 0, 'MLS', 83, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(53, 'OLFU2020-053', 'Shawn Jenkins', 'shawnjenkins3189@gmail.com', NULL, 'old enrollment', '9500060457', 43000, 2, '1', 7900, 0, 'BS CE', 95, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(54, 'OLFU2020-054', 'Alyssa Rivera', 'alyssarivera7538@gmail.com', NULL, 'new enroll', '9661171568', 36000, 4, '1', 10400, 0, 'MLS', 78, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(55, 'OLFU2020-055', 'Jordan Peters', 'jordanpeters6012@gmail.com', NULL, 'new student', '9702282679', 38000, 2, '1', 11200, 0, 'MLS', 89, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(56, 'OLFU2020-056', 'Marissa Jenkins', 'marissajenkins4385@gmail.com', NULL, 'new enroll', '9813393780', 40000, 2, '1', 7600, 0, 'MLS', 74, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(57, 'OLFU2020-057', 'Trevor Murray', 'trevormurray9603@gmail.com', NULL, 'new student', '9084504891', 48000, 3, '1', 18000, 0, 'MLS', 86, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(58, 'OLFU2020-058', 'Kristen Barnes', 'kristenbarnes8125@gmail.com', NULL, 'new student', '9295615902', 45000, 2, '1', 16700, 0, 'BSIHM', 90, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(59, 'OLFU2020-059', 'Cody Owens', 'codyowens4879@gmail.com', NULL, 'new enroll', '9496726013', 42000, 4, '1', 15400, 0, 'BSECE', 84, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(60, 'OLFU2020-060', 'Sabrina Wheeler', 'sabrinawheeler3459@gmail.com', NULL, 'new student', '9987837124', 44000, 4, '1', 16100, 0, 'MLS', 82, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(61, 'OLFU2020-061', 'Colton Hudson', 'coltonhudson2943@gmail.com', NULL, 'new student', '9177948235', 41000, 3, '1', 14900, 0, 'BSIT', 93, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(62, 'OLFU2020-062', 'Ciara Wells', 'ciarawells1840@gmail.com', NULL, 'new student', '9209059346', 46000, 2, '1', 11600, 0, 'BS PSYCH', 79, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(63, 'OLFU2020-063', 'Blake Graham', 'blakegraham7324@gmail.com', NULL, 'new enroll', '9380160457', 39000, 2, '1', 10500, 0, 'BS CE', 85, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(64, 'OLFU2020-064', 'Angela Taylor', 'angelataylor9130@gmail.com', NULL, 'new student', '9451271568', 50000, 4, '1', 5800, 0, 'MLS', 91, 1, '2026-05-15 06:18:58', 7, 'testuser1'),
(65, 'OLFU2020-065', 'Dylan Cook', 'dylancook2519@gmail.com', NULL, 'new student', '9512382679', 47000, 3, '1', 17400, 0, 'BS PHARMA', 77, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(66, 'OLFU2020-066', 'Lauren Steele', 'laurensteele6210@gmail.com', NULL, 'new student', '9763493780', 43000, 3, '1', 15800, 0, 'BSA', 88, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(67, 'OLFU2020-067', 'Nathan Ramirez', 'nathanramirez3802@gmail.com', NULL, 'new enroll', '9894504891', 40000, 3, '1', 14600, 0, 'BS IHM HACLO', 94, 2, '2026-05-15 06:18:58', 7, 'testuser1'),
(68, 'OLFU2020-068', 'Katie Mendoza', 'katiemendoza8953@gmail.com', NULL, 'new student', '9915615902', 42000, 2, '1', 15200, 0, 'BSN', 81, 3, '2026-05-15 06:18:58', 7, 'testuser1'),
(69, 'OLFU2020-069', 'Zachary Hart', 'zacharyhart2048@gmail.com', NULL, 'new student', '9396726013', 41000, 1, '1', 7600, 0, 'BS CE', 87, 4, '2026-05-15 06:18:58', 7, 'testuser1'),
(70, 'OLFU2020-070', 'Chelsea Luna', 'chelsealuna3709@gmail.com', NULL, 'new student', '9187837124', 44000, 2, '1', 8400, 0, 'BS CE', 76, 1, '2026-05-15 06:18:58', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2020_sem_2`
--

CREATE TABLE `student_2020_sem_2` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2020_sem_2`
--

INSERT INTO `student_2020_sem_2` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(71, 'OLFU2020-071', 'Lucas Fleming', 'lucasfleming1732@gmail.com', NULL, 'new enroll', '9288948235', 36000, 3, '2', 10200, 0, 'BS CE', 92, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(72, 'OLFU2020-072', 'Brooke Hunter', 'brookehunter8340@gmail.com', NULL, 'new student', '9479059346', 28000, 2, '2', 3400, 0, 'BS CE', 85, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(73, 'OLFU2020-073', 'Ethan Barrett', 'ethanbarrett5914@gmail.com', NULL, 'new enroll', '9500160457', 37000, 4, '2', 10800, 0, 'MLS', 83, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(74, 'OLFU2020-074', 'Molly Burke', 'mollyburke2908@gmail.com', NULL, 'new enroll', '9661271568', 35000, 2, '2', 9600, 0, 'MEDTECH', 95, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(75, 'OLFU2020-075', 'Gavin Sutton', 'gavinsutton4730@gmail.com', NULL, 'new student', '9702382679', 45000, 4, '2', 16900, 0, 'MEDTECH', 80, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(76, 'OLFU2020-076', 'Rebecca Craig', 'rebeccacraig1485@gmail.com', NULL, 'new student', '9813493780', 48000, 3, '2', 17800, 0, 'ABM', 89, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(77, 'OLFU2020-077', 'Sean Rodriguez', 'seanrodriguez9610@gmail.com', NULL, 'new student', '9084504891', 46000, 4, '2', 11200, 0, 'BS CS', 86, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(78, 'OLFU2020-078', 'Taylor Pittman', 'taylorpittman3489@gmail.com', NULL, 'new enroll', '9295615902', 39000, 2, '2', 10600, 0, 'BS ECE', 74, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(79, 'OLFU2020-079', 'Tracy Owen', 'tracyowen5223@gmail.com', NULL, 'new student', '9496726013', 44000, 2, '2', 16200, 0, 'BSN', 90, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(80, 'OLFU2020-080', 'Jared Neal', 'jaredneal7001@gmail.com', NULL, 'new student', '9987837124', 42000, 2, '2', 15400, 0, 'BSHACLO', 84, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(81, 'OLFU2020-081', 'Diane Watts', 'dianewatts6342@gmail.com', NULL, 'new student', '9177948235', 40000, 4, '2', 14700, 0, 'BSBA MKTG', 88, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(82, 'OLFU2020-082', 'Joel Brady', 'joelbrady9426@gmail.com', NULL, 'new enroll', '9209059346', 43000, 3, '2', 15900, 0, 'MEDTECH', 82, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(83, 'OLFU2020-083', 'Nina Fuller', 'ninafuller2836@gmail.com', NULL, 'new student', '9380160457', 38000, 2, '2', 11000, 0, 'BSN', 93, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(84, 'OLFU2020-084', 'Keith Anderson', 'keithanderson2308@gmail.com', NULL, 'new student', '9451271568', 47000, 2, '2', 17200, 0, 'BS HACLO', 79, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(85, 'OLFU2020-085', 'Miranda Dunn', 'mirandadunn4215@gmail.com', NULL, 'new student', '9512382679', 41000, 2, '2', 7800, 0, 'BS HACLO', 91, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(86, 'OLFU2020-086', 'Devin Holmes', 'devinholmes7381@gmail.com', NULL, 'new student', '9763493780', 45000, 2, '2', 11500, 0, 'BSCS', 77, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(87, 'OLFU2020-087', 'Shannon Hale', 'shannonhale3607@gmail.com', NULL, 'new enroll', '9894504891', 49000, 4, '2', 18200, 0, 'MLS', 85, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(88, 'OLFU2020-088', 'Landon Boyd', 'landonboyd8193@gmail.com', NULL, 'new student', '9915615902', 44000, 2, '2', 8200, 0, 'BSITM TTO', 94, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(89, 'OLFU2020-089', 'Gabrielle Ball', 'gabrielleball2617@gmail.com', NULL, 'new student', '9396726013', 35000, 2, '2', 9800, 0, 'BSN', 81, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(90, 'OLFU2020-090', 'Bradley Fox', 'bradleyfox4890@gmail.com', NULL, 'new student', '9187837124', 41000, 4, '2', 7600, 0, 'BS HACLO', 87, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(91, 'OLFU2020-091', 'Allison Lawson', 'allisonlawson3957@gmail.com', NULL, 'new enroll', '9288948235', 37000, 4, '2', 10200, 0, 'BS CE', 75, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(92, 'OLFU2020-092', 'Mitchell Cole', 'mitchellcole8042@gmail.com', NULL, 'new student', '9479059346', 36000, 2, '2', 10400, 0, 'BS CS', 92, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(93, 'OLFU2020-093', 'Paige Francis', 'paigefrancis1328@gmail.com', NULL, 'new enroll', '9500160457', 38000, 2, '2', 11000, 0, 'BS CE', 86, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(94, 'OLFU2020-094', 'Clayton Ellis', 'claytonellis7325@gmail.com', NULL, 'new student', '9661271568', 43000, 2, '2', 15800, 0, 'BS PHARMA', 83, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(95, 'OLFU2020-095', 'Karla Carpenter', 'karlacarpenter6017@gmail.com', NULL, 'new student', '9702382679', 40000, 3, '2', 14600, 0, 'BSCE', 95, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(96, 'OLFU2020-096', 'Brent Shaw', 'brentshaw5213@gmail.com', NULL, 'new student', '9813493780', 39000, 4, '2', 10300, 0, 'BS CE', 78, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(97, 'OLFU2020-097', 'Vanessa Bowers', 'vanessabowers9248@gmail.com', NULL, 'new enroll', '9084504891', 42000, 3, '2', 15400, 0, 'MLS', 89, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(98, 'OLFU2020-098', 'Derrick Ross', 'derrickross3746@gmail.com', NULL, 'new student', '9295615902', 44000, 2, '2', 8000, 0, 'BSN', 74, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(99, 'OLFU2020-099', 'Autumn Floyd', 'autumnfloyd1059@gmail.com', NULL, 'new student', '9496726013', 46000, 2, '2', 11200, 0, 'MEDTECH', 90, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(100, 'OLFU2020-100', 'Leonard Farmer', 'leonardfarmer2604@gmail.com', NULL, 'new student', '9987837124', 47000, 2, '2', 17100, 0, 'MLS', 84, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(101, 'OLFU2020-101', 'Karen Glover', 'karenglover5702@gmail.com', NULL, 'new student', '9177948235', 45000, 3, '2', 16700, 0, 'BS ECE', 88, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(102, 'OLFU2020-102', 'Grant Spencer', 'grantspencer8729@gmail.com', NULL, 'new enroll', '9209059346', 41000, 3, '2', 7800, 0, 'BS CE', 82, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(103, 'OLFU2020-103', 'Felicia Cannon', 'feliciacannon4137@gmail.com', NULL, 'new student', '9380160457', 43000, 2, '2', 15900, 0, 'BSN', 93, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(104, 'OLFU2020-104', 'Zane Hammond', 'zanehammond7823@gmail.com', NULL, 'new student', '9451271568', 38000, 1, '2', 11000, 0, 'BS PSYCH', 79, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(105, 'OLFU2020-105', 'Lisa Knox', 'lisaknox1540@gmail.com', NULL, 'new student', '9512382679', 40000, 1, '2', 7200, 0, 'BSN', 85, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(106, 'OLFU2020-106', 'Casey Bates', 'caseybates9906@gmail.com', NULL, 'new enroll', '9763493780', 42000, 1, '2', 15400, 0, 'BSN', 91, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(107, 'OLFU2020-107', 'Isabella Burton', 'isabellaburton2087@gmail.com', NULL, 'new student', '9894504891', 44000, 1, '2', 8000, 0, 'MLS', 77, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(108, 'OLFU2020-108', 'Donald Summers', 'donaldsummers3841@gmail.com', NULL, 'new student', '9915615902', 39000, 1, '2', 10600, 0, 'BSA', 86, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(109, 'OLFU2020-109', 'Sydney McCoy', 'sydneymccoy7435@gmail.com', NULL, 'new enroll', '9396726013', 46000, 2, '2', 11200, 0, 'BSIT', 94, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(110, 'OLFU2020-110', 'Adrianna Chavez', 'adriannachavez3902@gmail.com', NULL, 'new student', '9187837124', 45000, 2, '2', 16700, 0, 'BSITM TTO', 81, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(111, 'OLFU2020-111', 'Julian Watkins', 'julianwatkins1609@gmail.com', NULL, 'new student', '9288948235', 48000, 3, '2', 17800, 0, 'BSA', 87, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(112, 'OLFU2020-112', 'Corinne Phelps', 'corinnephelps5728@gmail.com', NULL, 'new student', '9479059346', 26000, 2, '2', 2800, 0, 'MLS', 76, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(113, 'OLFU2020-113', 'Omar Fowler', 'omarfowler3145@gmail.com', NULL, 'new enroll', '9500160457', 47000, 2, '2', 17200, 0, 'BS CE', 92, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(114, 'OLFU2020-114', 'Naomi Johnston', 'naomijohnston8762@gmail.com', NULL, 'new student', '9661271568', 44000, 2, '2', 8000, 0, 'BS CE', 85, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(115, 'OLFU2020-115', 'Luke Watson', 'lukewatson6284@gmail.com', NULL, 'new student', '9702382679', 38000, 2, '2', 11000, 0, 'BSA', 83, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(116, 'OLFU2020-116', 'Hector Benson', 'hectorbenson9701@gmail.com', NULL, 'new enroll', '9813493780', 40000, 2, '2', 7600, 0, 'BS IT', 95, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(117, 'OLFU2020-117', 'Gianna Berry', 'giannaberry7829@gmail.com', NULL, 'new student', '9084504891', 42000, 3, '2', 15200, 0, 'BS HACLO', 80, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(118, 'OLFU2020-118', 'Bruce Harmon', 'bruceharmon6173@gmail.com', NULL, 'new enroll', '9295615902', 37000, 3, '2', 10400, 0, 'BS CE', 89, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(119, 'OLFU2020-119', 'Angela McBride', 'angelamcbride2045@gmail.com', NULL, 'new student', '9496726013', 41000, 2, '2', 7600, 0, 'MEDTECH', 86, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(120, 'OLFU2020-120', 'Brayden Rice', 'braydenrice9408@gmail.com', NULL, 'new student', '9987837124', 39000, 4, '2', 10300, 0, 'BS TOURISM', 74, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(121, 'OLFU2020-121', 'Catherine Sharp', 'catherinesharp7621@gmail.com', NULL, 'new student', '0917?794?8235', 35000, 2, '2', 6800, 0, 'MLS', 90, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(122, 'OLFU2020-122', 'Tobias O Brien', 'tobiasobrien3169@gmail.com', NULL, 'new student', '0920?905?9346', 43000, 2, '2', 15800, 0, 'BC CS', 84, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(123, 'OLFU2020-123', 'Desiree Klein', 'desireeklein9057@gmail.com', NULL, 'new student', '0938?016?0457', 25000, 2, '2', 2800, 0, 'BS CE', 88, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(124, 'OLFU2020-124', 'Elliott Holland', 'elliottholland1240@gmail.com', NULL, 'new student', '0945?127?1568', 45000, 4, '2', 16700, 0, 'MEDTECH', 82, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(125, 'OLFU2020-125', 'Renee Quinn', 'reneequinn4785@gmail.com', NULL, 'new student', '0951?238?2679', 44000, 3, '2', 8000, 0, 'BS CE', 93, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(126, 'OLFU2020-126', 'Frankie Wolfe', 'frankiewolfe6184@gmail.com', NULL, 'new student', '0976?349?3780', 38000, 3, '2', 11000, 0, 'BS CA', 79, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(127, 'OLFU2020-127', 'Rosario Morrow', 'rosariomorrow1346@gmail.com', NULL, 'new enroll', '0989?450?4891', 40000, 3, '2', 7200, 0, 'MLS', 91, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(128, 'OLFU2020-128', 'Julian Delgado', 'juliandelgado9582@gmail.com', NULL, 'new student', '0991?561?5902', 42000, 3, '2', 15400, 0, 'MLS', 77, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(129, 'OLFU2020-129', 'Melody Ballard', 'melodyballard2071@gmail.com', NULL, 'new student', '0939?672?6013', 39000, 2, '2', 10300, 0, 'BSN', 85, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(130, 'OLFU2020-130', 'Damien Rivas', 'damienrivas4630@gmail.com', NULL, 'new enroll', '0918?783?7124', 41000, 3, '2', 7600, 0, 'BS IT', 94, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(131, 'OLFU2020-131', 'Valerie Rowe', 'valerierowe8743@gmail.com', NULL, 'new enroll', '0928?894?8235', 43000, 2, '2', 15900, 0, 'MLS', 81, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(132, 'OLFU2020-132', 'Andrew Gill', 'andrewgill4109@gmail.com', NULL, 'new student', '0947?905?9346', 39000, 3, '2', 2800, 0, 'MLS', 87, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(133, 'OLFU2020-133', 'Kaylee Davidson', 'kayleedavidson2365@gmail.com', NULL, 'new student', '0950?016?0457', 42000, 3, '2', 17200, 0, 'BSN', 75, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(134, 'OLFU2020-134', 'Xavier Collins', 'xaviercollins3045@gmail.com', NULL, 'new student', '0966?127?1568', 44000, 3, '2', 8000, 0, 'BS CA', 92, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(135, 'OLFU2020-135', 'Claire Waters', 'clairewaters9207@gmail.com', NULL, 'new student', '0970?238?2679', 46000, 4, '2', 11000, 0, 'BS PT', 86, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(136, 'OLFU2020-136', 'Tristan Sharma', 'tristansharma7821@gmail.com', NULL, 'new enroll', '0981?349?3780', 47000, 4, '2', 7600, 0, 'BS PHARMA', 83, 3, '2025-11-16 13:15:15', 7, 'testuser1'),
(137, 'OLFU2020-137', 'Maria Santos', 'maria.santos2024@gmail.com', NULL, 'new student', '0908?450?4891', 45000, 4, '2', 15200, 0, 'MLS', 95, 4, '2025-11-16 13:15:15', 7, 'testuser1'),
(138, 'OLFU2020-138', 'John Michael Cruz', 'johnmichael.cruz@gmail.com', NULL, 'new student', '0929?561?5902', 41000, 2, '2', 10400, 0, 'BSN', 78, 1, '2025-11-16 13:15:15', 7, 'testuser1'),
(139, 'OLFU2020-139', 'Patricia Reyes', 'patricia.reyes99@gmail.com', NULL, 'new student', '0949?672?6013', 43000, 1, '2', 7600, 0, 'BS IT', 89, 2, '2025-11-16 13:15:15', 7, 'testuser1'),
(140, 'OLFU2020-140', 'Carlos Antonio Garcia', 'carlos.garcia2023@gmail.com', NULL, 'new student', '0998?783?7124', 38000, 2, '2', 2470, 0, 'BS CS', 88, 2, '2025-11-16 13:15:15', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2021_sem_1`
--

CREATE TABLE `student_2021_sem_1` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2021_sem_1`
--

INSERT INTO `student_2021_sem_1` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(1, 'OLFU2021-141', 'Maria Santos', 'mariasantos@fatima.edu.ph', NULL, 'new student', '9171234567', 65000, 1, '1', 45250, 0, 'BSN', 82, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(2, 'OLFU2021-142', 'Juan Dela Cruz', 'juandelacruz@fatima.edu.ph', NULL, 'new enroll', '9182345678', 75000, 2, '1', 52800, 0, 'BS PHARMA', 89, 1, '2025-11-16 13:08:34', 7, 'testuser1'),
(3, 'OLFU2021-143', 'Ana Reyes', 'anareyes@fatima.edu.ph', NULL, 'new student', '9193456789', 58000, 3, '1', 38900, 0, 'BS PT', 79, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(4, 'OLFU2021-144', 'Carlos Mendoza', 'carlosmendoza@fatima.edu.ph', NULL, 'new student', '9204567890', 62500, 4, '1', 41750, 0, 'BS HRM', 91, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(5, 'OLFU2021-145', 'Sofia Garcia', 'sofiagarcia@fatima.edu.ph', NULL, 'new student', '9215678901', 28000, 3, '1', 15600, 0, 'BS CRIM', 85, 2, '2025-11-16 13:08:34', 7, 'testuser1'),
(6, 'OLFU2021-146', 'Miguel Torres', 'migueltorres@fatima.edu.ph', NULL, 'new enroll', '9226789012', 68500, 4, '1', 48300, 0, 'BS MEDTECH', 88, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(7, 'OLFU2021-147', 'Isabella Flores', 'isabellaflores@fatima.edu.ph', NULL, 'new student', '9237890123', 67200, 2, '1', 46850, 0, 'MEDTECH', 93, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(8, 'OLFU2021-148', 'Diego Morales', 'diegomorales@fatima.edu.ph', NULL, 'new student', '9248901234', 59300, 2, '1', 39200, 0, 'BST', 80, 1, '2025-11-16 13:08:34', 7, 'testuser1'),
(9, 'OLFU2021-149', 'Carmen Villanueva', 'carmenvillanueva@fatima.edu.ph', NULL, 'new student', '9259012345', 42000, 2, '1', 28450, 0, 'MLS', 84, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(10, 'OLFU2021-150', 'Roberto Castillo', 'robertocastillo@fatima.edu.ph', NULL, 'new enroll', '9260123456', 64800, 2, '1', 44700, 0, 'CRIM', 87, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(11, 'OLFU2021-151', 'Luz Hernandez', 'luzhernandez@fatima.edu.ph', NULL, 'new student', '9271234567', 73500, 2, '1', 51350, 0, 'BSPYSCH', 92, 2, '2025-11-16 13:08:34', 7, 'testuser1'),
(12, 'OLFU2021-152', 'Ramon Gutierrez', 'ramongutierrez@fatima.edu.ph', NULL, 'new student', '9282345678', 63200, 1, '1', 42600, 0, 'CBA', 78, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(13, 'OLFU2021-153', 'Elena Rodriguez', 'elenarodriguez@fatima.edu.ph', NULL, 'new student', '9293456789', 15500, 1, '1', 8750, 0, 'BSCE', 86, 1, '2025-11-16 13:08:34', 7, 'testuser1'),
(14, 'OLFU2021-154', 'Jose Martinez', 'josemartinez@fatima.edu.ph', NULL, 'new enroll', '9304567890', 22800, 1, '1', 12400, 0, 'BSSE', 90, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(15, 'OLFU2021-155', 'Cristina Lopez', 'cristinalopez@fatima.edu.ph', NULL, 'new student', '9315678901', 68900, 2, '1', 47900, 0, 'BSEN', 81, 2, '2025-11-16 13:08:34', 7, 'testuser1'),
(16, 'OLFU2021-156', 'Fernando Gonzalez', 'fernandogonzalez@fatima.edu.ph', NULL, 'new enroll', '9326789012', 38500, 2, '1', 25300, 0, 'BS IT', 95, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(17, 'OLFU2021-157', 'Patricia Perez', 'patriciaperez@fatima.edu.ph', NULL, 'new enroll', '9337890123', 57200, 1, '1', 38150, 0, 'BS CA', 83, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(18, 'OLFU2021-158', 'Antonio Sanchez', 'antoniosanchez@fatima.edu.ph', NULL, 'new student', '9348901234', 32600, 2, '1', 19850, 0, 'BSCMM', 88, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(19, 'OLFU2021-159', 'Rosario Ramirez', 'rosarioramirez@fatima.edu.ph', NULL, 'new student', '9359012345', 35800, 3, '1', 21600, 0, 'BS BIO', 85, 1, '2025-11-16 13:08:34', 7, 'testuser1'),
(20, 'OLFU2021-160', 'Eduardo Cruz', 'eduardocruz@fatima.edu.ph', NULL, 'new student', '9360123456', 62800, 4, '1', 43250, 0, 'MT', 79, 2, '2025-11-16 13:08:34', 7, 'testuser1'),
(21, 'OLFU2021-161', 'Margarita Jimenez', 'margaritajimenez@fatima.edu.ph', NULL, 'new enroll', '9371234567', 60200, 3, '1', 40800, 0, 'BSTIMTIO', 91, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(22, 'OLFU2021-162', 'Francisco Ruiz', 'franciscoruiz@fatima.edu.ph', NULL, 'new student', '9382345678', 56800, 4, '1', 37950, 0, 'HACLO', 84, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(23, 'OLFU2021-163', 'Teresa Diaz', 'teresadiaz@fatima.edu.ph', NULL, 'new student', '9393456789', 64500, 2, '1', 44500, 0, 'BSC', 87, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(24, 'OLFU2021-164', 'Gabriel Moreno', 'gabrielmoreno@fatima.edu.ph', NULL, 'new student', '9174567890', 71200, 2, '1', 49650, 0, 'BSIHM', 82, 1, '2025-11-16 13:08:34', 7, 'testuser1'),
(25, 'OLFU2021-165', 'Dolores Munoz', 'doloresmunoz@fatima.edu.ph', NULL, 'new enroll', '9185678901', 74800, 2, '1', 52100, 0, 'BS PSYCH', 94, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(26, 'OLFU2021-166', 'Manuel Alvarez', 'manuelalvarez@fatima.edu.ph', NULL, 'new student', '9196789012', 66800, 2, '1', 46300, 0, 'CHIMHMD', 89, 2, '2025-11-16 13:08:34', 7, 'testuser1'),
(27, 'OLFU2021-167', 'Esperanza Romero', 'esperanzaromero@fatima.edu.ph', NULL, 'new student', '9207890123', 69500, 2, '1', 48750, 0, 'BS HACLO', 86, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(28, 'OLFU2021-168', 'Pedro Navarro', 'pedronavarro@fatima.edu.ph', NULL, 'new student', '9218901234', 72000, 1, '1', 50200, 0, 'EDUC', 80, 1, '2025-11-16 13:08:34', 7, 'testuser1'),
(29, 'OLFU2021-169', 'Concepcion Torres', 'concepciontorres@fatima.edu.ph', NULL, 'new student', '9229012345', 53200, 1, '1', 35600, 0, 'BSCSI', 88, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(30, 'OLFU2021-170', 'Alejandro Dominguez', 'alejandrodominguez@fatima.edu.ph', NULL, 'new enroll', '9230123456', 68200, 1, '1', 47850, 0, 'ASSOC IN COMPSCIE', 92, 2, '2025-11-16 13:08:34', 7, 'testuser1'),
(31, 'OLFU2021-171', 'Pilar Gil', 'pilargil@fatima.edu.ph', NULL, 'new student', '9241234567', 74500, 2, '1', 51900, 0, 'BSCS', 85, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(32, 'OLFU2021-172', 'Enrique Vargas', 'enriquevargas@fatima.edu.ph', NULL, 'new student', '9252345678', 65800, 2, '1', 45400, 0, 'BSEE', 83, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(33, 'OLFU2021-173', 'Amparo Ortega', 'amparoortega@fatima.edu.ph', NULL, 'new student', '9263456789', 61500, 1, '1', 41200, 0, 'BSITMTTO', 90, 1, '2025-11-16 13:08:34', 7, 'testuser1'),
(34, 'OLFU2021-174', 'Sergio Ramos', 'sergioramos@fatima.edu.ph', NULL, 'new enroll', '9274567890', 70800, 2, '1', 49300, 0, 'BMLS', 79, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(35, 'OLFU2021-175', 'Remedios Castro', 'remedioscastro@fatima.edu.ph', NULL, 'new student', '9285678901', 63500, 3, '1', 42750, 0, 'BS REFCO', 87, 2, '2025-11-16 13:08:34', 7, 'testuser1'),
(36, 'OLFU2021-176', 'Arturo Rubio', 'arturorubio@fatima.edu.ph', NULL, 'new enroll', '9296789012', 44200, 4, '1', 29650, 0, 'BSA', 91, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(37, 'OLFU2021-177', 'Soledad Martin', 'soledadmartin@fatima.edu.ph', NULL, 'new student', '9307890123', 76200, 3, '1', 53200, 0, 'BS IHMTTO', 84, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(38, 'OLFU2021-178', 'Alfredo Blanco', 'alfredoblanco@fatima.edu.ph', NULL, 'new student', '9318901234', 31200, 4, '1', 18900, 0, 'BS MM', 95, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(39, 'OLFU2021-179', 'Encarnacion Vega', 'encarnacionvega@fatima.edu.ph', NULL, 'new student', '9329012345', 72500, 2, '1', 50750, 0, 'BSCE', 81, 1, '2025-11-16 13:08:34', 7, 'testuser1'),
(40, 'OLFU2021-180', 'Ricardo Fuentes', 'ricardofuentes@fatima.edu.ph', NULL, 'new enroll', '9330123456', 27800, 2, '1', 16400, 0, 'PHARMA', 86, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(41, 'OLFU2021-181', 'Milagros Soto', 'milagrossoto@fatima.edu.ph', NULL, 'new student', '9341234567', 25600, 2, '1', 14850, 0, 'BSN', 89, 2.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(42, 'OLFU2021-182', 'Emilio Delgado', 'emiliodelgado@fatima.edu.ph', NULL, 'new student', '9352345678', 69800, 2, '1', 48600, 0, 'BSA', 78, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(43, 'OLFU2021-183', 'Asuncion Medina', 'asuncionmedina@fatima.edu.ph', NULL, 'new enroll', '9363456789', 66200, 2, '1', 45950, 0, 'MEDTECH', 92, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(44, 'OLFU2021-184', 'Jaime Aguilar', 'jaimeaguilar@fatima.edu.ph', NULL, 'new student', '9374567890', 54800, 1, '1', 36800, 0, 'BS BIO', 85, 2.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(45, 'OLFU2021-185', 'Perpetua Prieto', 'perpetuaprieto@fatima.edu.ph', NULL, 'new student', '9385678901', 58500, 1, '1', 39450, 0, 'BSSE', 88, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(46, 'OLFU2021-186', 'Vicente Ortiz', 'vicenteortiz@fatima.edu.ph', NULL, 'new student', '9396789012', 75200, 1, '1', 52350, 0, 'BSITMTTO', 83, 1.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(47, 'OLFU2021-187', 'Visitacion Pascual', 'visitacionpascual@fatima.edu.ph', NULL, 'new enroll', '9177890123', 67500, 2, '1', 46700, 0, 'BS HRM', 90, 1, '2025-11-16 13:08:34', 7, 'testuser1'),
(48, 'OLFU2021-188', 'Teodoro Herrera', 'teodoroherrera@fatima.edu.ph', NULL, 'new student', '9188901234', 73200, 2, '1', 51150, 0, 'MLS', 87, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(49, 'OLFU2021-189', 'Purificacion Leon', 'purificacionleon@fatima.edu.ph', NULL, 'new student', '9199012345', 64900, 1, '1', 44900, 0, 'BS EE', 82, 2.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(50, 'OLFU2021-190', 'Esteban Iglesias', 'estebaniglesias@fatima.edu.ph', NULL, 'new student', '9200123456', 60500, 2, '1', 40300, 0, 'BS CS', 94, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(51, 'OLFU2021-191', 'Nieves Fernandez', 'nievesfernandez@fatima.edu.ph', NULL, 'new enroll', '9211234567', 58200, 3, '1', 38750, 0, 'BS PHARMA', 86, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(52, 'OLFU2021-192', 'Ignacio Mendez', 'ignaciomendez@fatima.edu.ph', NULL, 'new student', '9222345678', 23500, 4, '1', 13200, 0, 'BS PSYCH', 91, 2.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(53, 'OLFU2021-193', 'Natividad Calvo', 'natividadcalvo@fatima.edu.ph', NULL, 'new student', '9233456789', 71500, 3, '1', 49800, 0, 'BS IT', 84, 4, '2025-11-16 13:08:34', 7, 'testuser1'),
(54, 'OLFU2021-194', 'Marcelo Santos', 'marcelosantos@fatima.edu.ph', NULL, 'new student', '9244567890', 55800, 4, '1', 37400, 0, 'BS HACLO', 80, 1.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(55, 'OLFU2021-195', 'Angeles Morales', 'angelesmorales@fatima.edu.ph', NULL, 'new enroll', '9255678901', 72800, 2, '1', 50600, 0, 'BS A', 89, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(56, 'OLFU2021-196', 'Gonzalo Pena', 'gonzalopena@fatima.edu.ph', NULL, 'new student', '9266789012', 20500, 2, '1', 11950, 0, 'BCCS', 93, 2.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(57, 'OLFU2021-197', 'Presentacion Campos', 'presentacioncampos@fatima.edu.ph', NULL, 'new enroll', '9277890123', 68000, 2, '1', 47200, 0, 'BS CE1ST YR', 85, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(58, 'OLFU2021-198', 'Bernardo Suarez', 'bernardosuarez@fatima.edu.ph', NULL, 'new enroll', '9288901234', 63800, 2, '1', 43650, 0, 'BSITM', 87, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(59, 'OLFU2021-199', 'Remedios Guerrero', 'remediosguerrero@fatima.edu.ph', NULL, 'new student', '9299012345', 53500, 2, '1', 36100, 0, 'BS BSMM', 79, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(60, 'OLFU2021-200', 'Leopoldo Mendoza', 'leopoldomendoza@fatima.edu.ph', NULL, 'new student', '9300123456', 70200, 1, '1', 48950, 0, 'BS IHMHACLO', 91, 2.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(61, 'OLFU2021-201', 'Consolacion Cano', 'consolacioncano@fatima.edu.ph', NULL, 'new student', '9311234567', 65500, 1, '1', 45300, 0, 'BS IHMCA', 88, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(62, 'OLFU2021-202', 'Eugenio Cortes', 'eugeniocortes@fatima.edu.ph', NULL, 'new enroll', '9322345678', 75800, 1, '1', 52750, 0, 'BS HMTTO', 82, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(63, 'OLFU2021-203', 'Milagros Silva', 'milagrossilva@fatima.edu.ph', NULL, 'new student', '9333456789', 70500, 2, '1', 49100, 0, 'BS CE', 86, 2.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(64, 'OLFU2021-204', 'Aurelio Mora', 'aureliomora@fatima.edu.ph', NULL, 'new student', '9344567890', 64200, 2, '1', 44850, 0, 'BS CE', 90, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(65, 'OLFU2021-205', 'Caridad Lozano', 'caridadlozano@fatima.edu.ph', NULL, 'new student', '9355678901', 61800, 1, '1', 41950, 0, 'BSITM TTO', 83, 2.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(66, 'OLFU2021-206', 'Crisanto Guerrero', 'crisantoguerrero@fatima.edu.ph', NULL, 'new enroll', '9366789012', 29500, 2, '1', 17650, 0, 'BS IT', 95, 5, '2025-11-16 13:08:34', 7, 'testuser1'),
(67, 'OLFU2021-207', 'Salvacion Marquez', 'salvacionmarquez@fatima.edu.ph', NULL, 'new student', '9377890123', 75500, 3, '1', 52400, 0, 'BS HACLO', 84, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(68, 'OLFU2021-208', 'Luciano Romero', 'lucianoromero@fatima.edu.ph', NULL, 'new student', '9388901234', 32000, 4, '1', 19300, 0, 'BS CE', 89, 3, '2025-11-16 13:08:34', 7, 'testuser1'),
(69, 'OLFU2021-209', 'Trinidad Velasco', 'trinidadvelasco@fatima.edu.ph', NULL, 'new student', '9399012345', 66500, 3, '1', 46150, 0, 'BS CE', 87, 2.5, '2025-11-16 13:08:34', 7, 'testuser1'),
(70, 'OLFU2021-210', 'Florencio Cruz', 'florenciocruz@fatima.edu.ph', NULL, 'new student', '9170123456', 62500, 4, '1', 42800, 0, 'BSITM', 81, 5, '2025-11-16 13:08:34', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2021_sem_2`
--

CREATE TABLE `student_2021_sem_2` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2021_sem_2`
--

INSERT INTO `student_2021_sem_2` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(71, 'OLFU2021-211', 'Esperanza Leon', 'esperanzaleon@fatima.edu.ph', NULL, 'new enroll', '9181234567', 57500, 2, '2', 38500, 0, 'MLS', 92, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(72, 'OLFU2021-212', 'Casimiro Herrero', 'casimiroherrero@fatima.edu.ph', NULL, 'new student', '9192345678', 63900, 2, '2', 43950, 0, 'BC CS', 85, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(73, 'OLFU2021-213', 'Purificacion Nieto', 'purificacionnieto@fatima.edu.ph', NULL, 'new student', '9203456789', 68500, 2, '2', 47600, 0, 'MLS', 88, 4, '2025-11-16 13:23:14', 7, 'testuser1'),
(74, 'OLFU2021-214', 'Benito Castillo', 'benitocastillo@fatima.edu.ph', NULL, 'new student', '9214567890', 71800, 2, '2', 50050, 0, 'BS A', 86, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(75, 'OLFU2021-215', 'Concepcion Jimenez', 'concepcionjimenez@fatima.edu.ph', NULL, 'new enroll', '9225678901', 26800, 2, '2', 15850, 0, 'BS IT', 90, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(76, 'OLFU2021-216', 'Modesto Caballero', 'modestocaballero@fatima.edu.ph', NULL, 'new student', '9236789012', 74200, 1, '2', 51700, 0, 'BS CE', 79, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(77, 'OLFU2021-217', 'Virtudes Gallego', 'virtudesgallego@fatima.edu.ph', NULL, 'new enroll', '9247890123', 33500, 1, '2', 20400, 0, 'BS CS', 93, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(78, 'OLFU2021-218', 'Primitivo Flores', 'primitivoflores@fatima.edu.ph', NULL, 'new student', '9258901234', 71000, 1, '2', 49450, 0, 'BS HACLO', 84, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(79, 'OLFU2021-219', 'Encarnacion Carrasco', 'encarnacioncarrasco@fatima.edu.ph', NULL, 'new student', '9269012345', 66200, 2, '2', 45750, 0, 'BS CE1ST YR', 87, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(80, 'OLFU2021-220', 'Saturnino Reyes', 'saturninoreyes@fatima.edu.ph', NULL, 'new student', '9270123456', 60800, 2, '2', 40600, 0, 'BSITM', 91, 5, '2025-11-16 13:23:14', 7, 'testuser1'),
(81, 'OLFU2021-221', 'Ascension Franco', 'ascensionfranco@fatima.edu.ph', NULL, 'new enroll', '9281234567', 24200, 1, '2', 14200, 0, 'MLS', 82, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(82, 'OLFU2021-222', 'Policarpo Benito', 'policarpobenito@fatima.edu.ph', NULL, 'new student', '9292345678', 76500, 2, '2', 53050, 0, 'BC CS', 88, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(83, 'OLFU2021-223', 'Remedios Santos', 'remediossantos@fatima.edu.ph', NULL, 'new student', '9303456789', 28200, 3, '2', 16900, 0, 'MLS', 85, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(84, 'OLFU2021-224', 'Herminio Molina', 'herminiomolina@fatima.edu.ph', NULL, 'new enroll', '9314567890', 73500, 4, '2', 51250, 0, 'BS A', 95, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(85, 'OLFU2021-225', 'Purificacion Torres', 'purificaciontorres@fatima.edu.ph', NULL, 'new student', '9325678901', 70200, 3, '2', 48800, 0, 'BS IT', 89, 5, '2025-11-16 13:23:14', 7, 'testuser1'),
(86, 'OLFU2021-226', 'Abundio Moreno', 'abundiomoreno@fatima.edu.ph', NULL, 'new student', '9336789012', 56200, 4, '2', 37650, 0, 'BS HACLO', 83, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(87, 'OLFU2021-227', 'Concepcion Vazquez', 'concepcionvazquez@fatima.edu.ph', NULL, 'new student', '9347890123', 21800, 2, '2', 12750, 0, 'BSN', 86, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(88, 'OLFU2021-228', 'Patricio Ramos', 'patricioramos@fatima.edu.ph', NULL, 'new enroll', '9358901234', 36500, 2, '2', 22100, 0, 'BS HACLO', 80, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(89, 'OLFU2021-229', 'Esperanza Santana', 'esperanzasantana@fatima.edu.ph', NULL, 'new student', '9369012345', 76000, 2, '2', 52900, 0, 'MLS', 92, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(90, 'OLFU2021-230', 'Aniceto Pascual', 'anicetopascual@fatima.edu.ph', NULL, 'new student', '9370123456', 30800, 2, '2', 18550, 0, 'BS HACLO', 87, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(91, 'OLFU2021-231', 'Visitacion Marin', 'visitacionmarin@fatima.edu.ph', NULL, 'new student', '9381234567', 72200, 2, '2', 50300, 0, 'BS HACLO', 84, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(92, 'OLFU2021-232', 'Feliciano Gonzalez', 'felicianogonzalez@fatima.edu.ph', NULL, 'new enroll', '9392345678', 63500, 1, '2', 44200, 0, 'MLS', 91, 5, '2025-11-16 13:23:14', 7, 'testuser1'),
(93, 'OLFU2021-233', 'Presentacion Delgado', 'presentaciondelgado@fatima.edu.ph', NULL, 'new student', '9173456789', 17200, 1, '2', 9800, 0, 'BS BS MM', 88, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(94, 'OLFU2021-234', 'Venancio Herrera', 'venancioherrera@fatima.edu.ph', NULL, 'new student', '9184567890', 61200, 1, '2', 41400, 0, 'BSN', 79, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(95, 'OLFU2021-235', 'Consolacion Medina', 'consolacionmedina@fatima.edu.ph', NULL, 'new student', '9195678901', 67800, 2, '2', 47050, 0, 'BSN', 86, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(96, 'OLFU2021-236', 'Dionisio Ruiz', 'dionisioruiz@fatima.edu.ph', NULL, 'new enroll', '9206789012', 71500, 2, '2', 49700, 0, 'BS IT', 90, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(97, 'OLFU2021-237', 'Milagros Ortega', 'milagrosortega@fatima.edu.ph', NULL, 'new student', '9217890123', 76800, 1, '2', 53400, 0, 'MLS', 85, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(98, 'OLFU2021-238', 'Anastasio Gil', 'anastasiogil@fatima.edu.ph', NULL, 'new enroll', '9228901234', 67800, 2, '2', 46950, 0, 'BS IHM HACLO', 83, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(99, 'OLFU2021-239', 'Encarnacion Vargas', 'encarnacionvargas@fatima.edu.ph', NULL, 'new enroll', '9239012345', 39800, 3, '2', 25750, 0, 'BS CE', 94, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(100, 'OLFU2021-240', 'Gumersindo Dominguez', 'gumersindodominguez@fatima.edu.ph', NULL, 'new student', '9240123456', 74000, 4, '2', 51450, 0, 'BSN', 87, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(101, 'OLFU2021-241', 'Remedios Navarro', 'remediosnavarro@fatima.edu.ph', NULL, 'new student', '9251234567', 59200, 3, '2', 39800, 0, 'BSN', 82, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(102, 'OLFU2021-242', 'Crescencio Torres', 'crescenciotorres@fatima.edu.ph', NULL, 'new student', '9262345678', 62800, 4, '2', 42350, 0, 'BSN', 91, 5, '2025-11-16 13:23:14', 7, 'testuser1'),
(103, 'OLFU2021-243', 'Purificacion Romero', 'purificacionromero@fatima.edu.ph', NULL, 'new enroll', '9273456789', 65200, 2, '2', 45100, 0, 'MLS', 89, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(104, 'OLFU2021-244', 'Silverio Alvarez', 'silverioalvarez@fatima.edu.ph', NULL, 'new student', '9284567890', 57800, 2, '2', 38650, 0, 'MLS', 85, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(105, 'OLFU2021-245', 'Concepcion Munoz', 'concepcionmunoz@fatima.edu.ph', NULL, 'new student', '9295678901', 69500, 2, '2', 48200, 0, 'MLS', 88, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(106, 'OLFU2021-246', 'Fulgencio Moreno', 'fulgenciomoreno@fatima.edu.ph', NULL, 'new student', '9306789012', 30200, 2, '2', 17950, 0, 'BS A', 80, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(107, 'OLFU2021-247', 'Esperanza Diaz', 'esperanzadiaz@fatima.edu.ph', NULL, 'new enroll', '9317890123', 73200, 2, '2', 50850, 0, 'BS ITM TTO', 93, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(108, 'OLFU2021-248', 'Evaristo Ruiz', 'evaristoruiz@fatima.edu.ph', NULL, 'new student', '9328901234', 63200, 1, '2', 43400, 0, 'BS ITM TTO', 86, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(109, 'OLFU2021-249', 'Visitacion Jimenez', 'visitacionjimenez@fatima.edu.ph', NULL, 'new student', '9339012345', 67200, 1, '2', 46600, 0, 'BS ITM TTO', 84, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(110, 'OLFU2021-250', 'Macario Ramirez', 'macarioramirez@fatima.edu.ph', NULL, 'new student', '9340123456', 75000, 1, '2', 52150, 0, 'BS CS', 90, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(111, 'OLFU2021-251', 'Consolacion Sanchez', 'consolacionsanchez@fatima.edu.ph', NULL, 'new student', '9351234567', 64500, 2, '2', 44750, 0, 'BSN', 79, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(112, 'OLFU2021-252', 'Higinio Perez', 'higinioperez@fatima.edu.ph', NULL, 'new enroll', '9362345678', 61200, 2, '2', 40950, 0, 'BSN', 95, 5, '2025-11-16 13:23:14', 7, 'testuser1'),
(113, 'OLFU2021-253', 'Remedios Gonzalez', 'remediosgonzalez@fatima.edu.ph', NULL, 'new student', '9373456789', 19800, 1, '2', 11650, 0, 'BSN', 87, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(114, 'OLFU2021-254', 'Anacleto Lopez', 'anacletoopez@fatima.edu.ph', NULL, 'new student', '9384567890', 71200, 2, '2', 49550, 0, 'BS PHARMA', 85, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(115, 'OLFU2021-255', 'Purificacion Martinez', 'purificacionmartinez@fatima.edu.ph', NULL, 'new student', '9395678901', 35200, 3, '2', 21800, 0, 'BS PSYCH', 91, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(116, 'OLFU2021-256', 'Aquilino Rodriguez', 'aquilinorodriguez@fatima.edu.ph', NULL, 'new enroll', '9176789012', 33500, 4, '2', 20250, 0, 'MLS', 82, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(117, 'OLFU2021-257', 'Concepcion Hernandez', 'concepcionhernandez@fatima.edu.ph', NULL, 'new student', '9187890123', 68500, 3, '2', 47450, 0, 'BS CE', 89, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(118, 'OLFU2021-258', 'Epitacio Gutierrez', 'epitaciogutierrez@fatima.edu.ph', NULL, 'new enroll', '9198901234', 74500, 4, '2', 51800, 0, 'BSN', 88, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(119, 'OLFU2021-259', 'Esperanza Castillo', 'esperanzacastillo@fatima.edu.ph', NULL, 'new student', '9209012345', 54500, 2, '2', 36550, 0, 'BS IHM CA', 84, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(120, 'OLFU2021-260', 'Nemesio Villanueva', 'nemesiovillanueva@fatima.edu.ph', NULL, 'new student', '9210123456', 18500, 2, '2', 10400, 0, 'BS PSYCH', 86, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(121, 'OLFU2021-261', 'Visitacion Morales', 'visitacionmorales@fatima.edu.ph', NULL, 'new student', '9221234567', 23800, 2, '2', 13850, 0, 'BSN', 83, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(122, 'OLFU2021-262', 'Demetrio Flores', 'demetrioflores@fatima.edu.ph', NULL, 'new enroll', '9232345678', 66200, 2, '2', 45650, 0, 'MLS', 92, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(123, 'OLFU2021-263', 'Remedios Torres', 'remediostorres@fatima.edu.ph', NULL, 'new student', '9243456789', 69800, 2, '2', 48500, 0, 'BS CE', 80, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(124, 'OLFU2021-264', 'Benigno Garcia', 'benignogarcia@fatima.edu.ph', NULL, 'new student', '9254567890', 62200, 1, '2', 42150, 0, 'BSN', 90, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(125, 'OLFU2021-265', 'Consolacion Mendoza', 'consolacionmendoza@fatima.edu.ph', NULL, 'new enroll', '9265678901', 77200, 1, '2', 53700, 0, 'BS PSYCH', 87, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(126, 'OLFU2021-266', 'Leoncio Reyes', 'leoncioreyes@fatima.edu.ph', NULL, 'new student', '9276789012', 59500, 1, '2', 39950, 0, 'BS HM TTO', 85, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(127, 'OLFU2021-267', 'Purificacion Santos', 'purificacionsantos@fatima.edu.ph', NULL, 'new student', '9287890123', 64200, 2, '2', 44400, 0, 'BSN', 94, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(128, 'OLFU2021-268', 'Eulogio Cruz', 'eulogiocruz@fatima.edu.ph', NULL, 'new student', '9298901234', 73200, 2, '2', 50950, 0, 'BSN', 89, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(129, 'OLFU2021-269', 'Esperanza Dela Cruz', 'esperanzadelacruz@fatima.edu.ph', NULL, 'new enroll', '9309012345', 66800, 1, '2', 46250, 0, 'MLS', 91, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(130, 'OLFU2021-270', 'Nicasio Mendez', 'nicasiomendez@fatima.edu.ph', NULL, 'new student', '9310123456', 61500, 2, '2', 41700, 0, 'BS PSYCH', 84, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(131, 'OLFU2021-271', 'Visitacion Fernandez', 'visitacionfernandez@fatima.edu.ph', NULL, 'new enroll', '9321234567', 27800, 3, '2', 16550, 0, 'BS PSYCH', 88, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(132, 'OLFU2021-272', 'Primitivo Iglesias', 'primitivoiglesias@fatima.edu.ph', NULL, 'new student', '9332345678', 75800, 4, '2', 52600, 0, 'BS CE', 86, 2.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(133, 'OLFU2021-273', 'Remedios Leon', 'remediosleon@fatima.edu.ph', NULL, 'new student', '9343456789', 56500, 3, '2', 37850, 0, 'MLS', 79, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(134, 'OLFU2021-274', 'Telesforo Herrera', 'telesforoherrera@fatima.edu.ph', NULL, 'new student', '9354567890', 70800, 4, '2', 49200, 0, 'BS CE', 93, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(135, 'OLFU2021-275', 'Consolacion Pascual', 'consolacionpascual@fatima.edu.ph', NULL, 'new enroll', '9365678901', 37500, 2, '2', 23450, 0, 'BS PHARMA', 85, 3, '2025-11-16 13:23:14', 7, 'testuser1'),
(136, 'OLFU2021-276', 'Inocencio Ortiz', 'inocencioortiz@fatima.edu.ph', NULL, 'new student', '9376789012', 26200, 2, '2', 15300, 0, 'BS CE', 82, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(137, 'OLFU2021-277', 'Purificacion Prieto', 'purificacionprieto@fatima.edu.ph', NULL, 'new student', '9387890123', 66500, 2, '2', 45850, 0, 'BS PT', 90, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(138, 'OLFU2021-278', 'Saturnino Aguilar', 'saturninoaguilar@fatima.edu.ph', NULL, 'new enroll', '9398901234', 62500, 2, '2', 43100, 0, 'BS PHARMA', 87, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(139, 'OLFU2021-279', 'Esperanza Medina', 'esperanzamedina@fatima.edu.ph', NULL, 'new student', '9179012345', 68800, 2, '2', 47750, 0, 'BS IT', 95, 1.5, '2025-11-16 13:23:14', 7, 'testuser1'),
(140, 'OLFU2021-280', 'Melquiades Soto', 'melquiadessoto@fatima.edu.ph', NULL, 'new student', '9180123456', 23879, 1, '2', 250, 0, 'BS PT', 91, 3, '2025-11-16 13:23:14', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2022_sem_1`
--

CREATE TABLE `student_2022_sem_1` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2022_sem_1`
--

INSERT INTO `student_2022_sem_1` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(1, 'OLFU2022-281', 'Alexis Mendoza', 'alexismendoza@student.fatima.edu.ph', NULL, 'new student', '9171234567', 35200, 2, '1', 30000, 0, 'BS BIO', 91, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(2, 'OLFU2022-282', 'Marco Soriano', 'marcosoriano@student.fatima.edu.ph', NULL, 'new student', '9181234568', 34800, 1, '1', 33000, 0, 'BSN', 82, 2.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(3, 'OLFU2022-283', 'Jasmine Dela Cruz', 'jasminedelacruz@student.fatima.edu.ph', NULL, 'new enroll', '9191234569', 36700, 2, '1', 35500, 0, 'BS CE', 89, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(4, 'OLFU2022-284', 'Ryan Garcia', 'ryangarcia@student.fatima.edu.ph', NULL, 'new student', '9201234570', 33200, 2, '1', 6000, 0, 'BS HACLO', 88, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(5, 'OLFU2022-285', 'Trisha Lorenzo', 'trishalorenzo@student.fatima.edu.ph', NULL, 'new student', '9211234571', 37500, 3, '1', 34500, 0, 'BS CE', 84, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(6, 'OLFU2022-286', 'Edward Reyes', 'edwardreyes@student.fatima.edu.ph', NULL, 'new student', '9221234572', 36400, 2, '1', 34800, 0, 'BS IT', 86, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(7, 'OLFU2022-287', 'Angelica Ramos', 'angelicaramos@student.fatima.edu.ph', NULL, 'new enroll', '9231234573', 35900, 4, '1', 32000, 0, 'BS PSYCH', 83, 2.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(8, 'OLFU2022-288', 'Justin Martinez', 'justinmartinez@student.fatima.edu.ph', NULL, 'new student', '9251234574', 35300, 3, '1', 32700, 0, 'BSN', 92, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(9, 'OLFU2022-289', 'Kristine Santos', 'kristinesantos@student.fatima.edu.ph', NULL, 'new student', '9271234575', 37000, 4, '1', 34200, 0, 'BSBA MM', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(10, 'OLFU2022-290', 'Daniel Torres', 'danieltorres@student.fatima.edu.ph', NULL, 'new enroll', '9281234576', 36000, 2, '1', 33800, 0, 'BSN', 90, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(11, 'OLFU2022-291', 'Samantha Villanueva', 'samanthavillanueva@student.fatima.edu.ph', NULL, 'new student', '9291234577', 34400, 2, '1', 7000, 0, 'BSN', 87, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(12, 'OLFU2022-292', 'Joshua Fernandez', 'joshuafernandez@student.fatima.edu.ph', NULL, 'new student', '9301234578', 39800, 2, '1', 38700, 0, 'BSN', 79, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(13, 'OLFU2022-293', 'Alexa Navarro', 'alexanavarro@student.fatima.edu.ph', NULL, 'new student', '9321234579', 34000, 2, '1', 8000, 0, 'BSN', 86, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(14, 'OLFU2022-294', 'Carl Bautista', 'carlbautista@student.fatima.edu.ph', NULL, 'new enroll', '9331234580', 35500, 2, '1', 33400, 0, 'STEM', 90, 2.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(15, 'OLFU2022-295', 'Nicole Morales', 'nicolemorales@student.fatima.edu.ph', NULL, 'new student', '9351234581', 35000, 1, '1', 33600, 0, 'BS ITMTTO', 85, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(16, 'OLFU2022-296', 'Kenneth Monteverde', 'kennethmonteverde@student.fatima.edu.ph', NULL, 'new student', '9391234582', 33700, 2, '1', 5000, 0, 'MLS', 83, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(17, 'OLFU2022-297', 'Maryanne Oliveros', 'maryanneoliveros@student.fatima.edu.ph', NULL, 'new enroll', '9431234583', 34100, 1, '1', 6600, 0, 'MLS', 94, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(18, 'OLFU2022-298', 'Nathan Abella', 'nathanabella@student.fatima.edu.ph', NULL, 'new student', '9451234584', 33400, 3, '1', 5300, 0, 'BS ITMTTO', 87, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(19, 'OLFU2022-299', 'Bianca Cruz', 'biancacruz@student.fatima.edu.ph', NULL, 'new student', '9471234585', 37200, 4, '1', 35300, 0, 'BS PSYCH', 82, 2.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(20, 'OLFU2022-300', 'Francis Del Rosario', 'francisdelrosario@student.fatima.edu.ph', NULL, 'new student', '9481234586', 38000, 3, '1', 35900, 0, 'BSN', 91, 5, '2025-11-18 06:35:39', 7, 'testuser1'),
(21, 'OLFU2022-301', 'Pauline Gomez', 'paulinegomez@student.fatima.edu.ph', NULL, 'new student', '9501234587', 36800, 4, '1', 34500, 0, 'BS PHARMA', 89, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(22, 'OLFU2022-302', 'Angelo Castillo', 'angelocastillo@student.fatima.edu.ph', NULL, 'new enroll', '9511234588', 36500, 2, '1', 34000, 0, 'BS PSYCH', 85, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(23, 'OLFU2022-303', 'Camille Trinidad', 'camilletrinidad@student.fatima.edu.ph', NULL, 'new student', '9531234589', 33200, 2, '1', 4900, 0, 'BS ITMTTO', 88, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(24, 'OLFU2022-304', 'Leonardo Perez', 'leonardoperez@student.fatima.edu.ph', NULL, 'new student', '9551234590', 35600, 2, '1', 33200, 0, 'BS PSYCH', 89, 5, '2025-11-18 06:35:39', 7, 'testuser1'),
(25, 'OLFU2022-305', 'Siena Borja', 'sienaborja@student.fatima.edu.ph', NULL, 'new enroll', '9561234591', 36200, 2, '1', 34100, 0, 'BSN', 83, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(26, 'OLFU2022-306', 'Miguel Lim', 'miguellim@student.fatima.edu.ph', NULL, 'new student', '9571234592', 35400, 2, '1', 31000, 0, 'BS PHARMA', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(27, 'OLFU2022-307', 'Fiona Silva', 'fionasilva@student.fatima.edu.ph', NULL, 'new student', '9601234593', 36000, 1, '1', 33700, 0, 'BS PSYCH', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(28, 'OLFU2022-308', 'Jared Valencia', 'jaredvalencia@student.fatima.edu.ph', NULL, 'new student', '9611234594', 33900, 1, '1', 6400, 0, 'BS IT', 92, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(29, 'OLFU2022-309', 'Melanie Baluyot', 'melaniebaluyot@student.fatima.edu.ph', NULL, 'new enroll', '9631234595', 35000, 3, '1', 33000, 0, 'MLS', 87, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(30, 'OLFU2022-310', 'Rico Manalo', 'ricomanalo@student.fatima.edu.ph', NULL, 'new student', '9651234596', 38500, 4, '1', 37000, 0, 'BS CE', 94, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(31, 'OLFU2022-311', 'Arlene Vargas', 'arlenevargas@student.fatima.edu.ph', NULL, 'new enroll', '9661234597', 33000, 2, '1', 4100, 0, 'BS ITMTTO', 87, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(32, 'OLFU2022-312', 'Trevor Miranda', 'trevormiranda@student.fatima.edu.ph', NULL, 'new student', '9671234598', 35800, 2, '1', 34400, 0, 'BS IHMHACLO', 82, 2.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(33, 'OLFU2022-313', 'Charlene Garzon', 'charlenegarzon@student.fatima.edu.ph', NULL, 'new student', '9681234599', 34700, 2, '1', 33900, 0, 'BS HACLO', 91, 5, '2025-11-18 06:35:39', 7, 'testuser1'),
(34, 'OLFU2022-314', 'Dominic Abad', 'dominicabad@student.fatima.edu.ph', NULL, 'new student', '9701234600', 33400, 2, '1', 6500, 0, 'BSN', 89, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(35, 'OLFU2022-315', 'Veronica Padilla', 'veronicapadilla@student.fatima.edu.ph', NULL, 'new enroll', '9751234601', 34900, 2, '1', 32700, 0, 'BS PHARMA', 85, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(36, 'OLFU2022-316', 'Julius Salazar', 'juliussalazar@student.fatima.edu.ph', NULL, 'new enroll', '9771234602', 34000, 4, '1', 8800, 0, 'MLS', 83, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(37, 'OLFU2022-317', 'Tanya Mercado', 'tanyamercado@student.fatima.edu.ph', NULL, 'new student', '9791234603', 37900, 2, '1', 35500, 0, 'BS CE', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(38, 'OLFU2022-318', 'Ian Carreon', 'iancarreon@student.fatima.edu.ph', NULL, 'new student', '9811234604', 39500, 2, '1', 38600, 0, 'BSN', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(39, 'OLFU2022-319', 'Roxanne Delos Reyes', 'roxannedelosreyes@student.fatima.edu.ph', NULL, 'new enroll', '9851234605', 41000, 2, '1', 39500, 0, 'BS CS', 92, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(40, 'OLFU2022-320', 'Patrick Castro', 'patrickcastro@student.fatima.edu.ph', NULL, 'new student', '9871234606', 37000, 2, '1', 33000, 0, 'BS HACLO', 92, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(41, 'OLFU2022-321', 'Clarisse Lagrimas', 'clarisselagrimas@student.fatima.edu.ph', NULL, 'new student', '9891234607', 33200, 2, '1', 4800, 0, 'BS PSYCH', 83, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(42, 'OLFU2022-322', 'Elijah Nieves', 'elijahnieves@student.fatima.edu.ph', NULL, 'new enroll', '9921234608', 33600, 4, '1', 6600, 0, 'BS PHARMA', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(43, 'OLFU2022-323', 'Janelle Quintana', 'janellequintana@student.fatima.edu.ph', NULL, 'new student', '9951234609', 33700, 2, '1', 5900, 0, 'BS CE', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(44, 'OLFU2022-324', 'Victor Rosales', 'victorrosales@student.fatima.edu.ph', NULL, 'new student', '9971234610', 34000, 2, '1', 5300, 0, 'BSN', 92, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(45, 'OLFU2022-325', 'Maria Sarmiento', 'mariasarmiento@student.fatima.edu.ph', NULL, 'new student', '9981234611', 34500, 2, '1', 9000, 0, 'BSA', 83, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(46, 'OLFU2022-326', 'Gabriel Santiago', 'gabrielsantiago@student.fatima.edu.ph', NULL, 'new student', '9991234612', 35900, 2, '1', 34500, 0, 'BS CE', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(47, 'OLFU2022-327', 'Desiree Banlaoi', 'desireebanlaoi@student.fatima.edu.ph', NULL, 'new enroll', '9182345678', 36000, 2, '1', 34600, 0, 'BSN', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(48, 'OLFU2022-328', 'Christian Agustin', 'christianagustin@student.fatima.edu.ph', NULL, 'new enroll', '9192345679', 36800, 1, '1', 36200, 0, 'BSN', 92, 3, '2025-11-18 06:35:39', 7, 'testuser1'),
(49, 'OLFU2022-329', 'Eunice Magtoto', 'eunicemagtoto@student.fatima.edu.ph', NULL, 'new student', '9202345680', 36500, 1, '1', 35000, 0, 'BS HACLO', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(50, 'OLFU2022-330', 'Alfred Cunanan', 'alfredcunanan@student.fatima.edu.ph', NULL, 'new student', '9212345681', 35200, 3, '1', 33000, 0, 'BSA', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(51, 'OLFU2022-331', 'Yvette Tamayo', 'yvettetamayo@student.fatima.edu.ph', NULL, 'new enroll', '9222345682', 35400, 4, '1', 34000, 0, 'BSA', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(52, 'OLFU2022-332', 'Derick Ojeda', 'derickojeda@student.fatima.edu.ph', NULL, 'new student', '9232345683', 36600, 2, '1', 35900, 0, 'BS CS', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(53, 'OLFU2022-333', 'Valerie Dela O', 'valeriedelao@student.fatima.edu.ph', NULL, 'new student', '9252345684', 35000, 2, '1', 33400, 0, 'BS CE', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(54, 'OLFU2022-334', 'Adrian Pangilinan', 'adrianpangilinan@student.fatima.edu.ph', NULL, 'new enroll', '9272345685', 33800, 2, '1', 5700, 0, 'BSN', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(55, 'OLFU2022-335', 'Sophie Hidalgo', 'sophiehidalgo@student.fatima.edu.ph', NULL, 'new enroll', '9282345686', 35700, 1, '1', 34300, 0, 'BSN', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(56, 'OLFU2022-336', 'Zachary Magsino', 'zacharymagsino@student.fatima.edu.ph', NULL, 'new student', '9292345687', 34000, 1, '1', 6200, 0, 'BS CE', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(57, 'OLFU2022-337', 'Iris Limsiaco', 'irislimsiaco@student.fatima.edu.ph', NULL, 'new student', '9302345688', 37100, 3, '1', 35100, 0, 'BS HACLO', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(58, 'OLFU2022-338', 'Dennis Robles', 'dennisrobles@student.fatima.edu.ph', NULL, 'new enroll', '9322345689', 37600, 4, '1', 35800, 0, 'BSA', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(59, 'OLFU2022-339', 'Patricia Samonte', 'patriciasamonte@student.fatima.edu.ph', NULL, 'new student', '9332345690', 38500, 2, '1', 36400, 0, 'BSA', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(60, 'OLFU2022-340', 'Kyle Manalang', 'kylemanalang@student.fatima.edu.ph', NULL, 'new student', '9352345691', 36200, 2, '1', 34400, 0, 'BS IT', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(61, 'OLFU2022-341', 'Lauren Zamora', 'laurenzamora@student.fatima.edu.ph', NULL, 'new enroll', '9392345692', 36900, 2, '1', 33600, 0, 'BS CS', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(62, 'OLFU2022-342', 'Darren Obando', 'darrenobando@student.fatima.edu.ph', NULL, 'new enroll', '9432345693', 33400, 1, '1', 5300, 0, 'STEM', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(63, 'OLFU2022-343', 'Alyssa Jovellanos', 'alyssajovellanos@student.fatima.edu.ph', NULL, 'new student', '9452345694', 39000, 1, '1', 37200, 0, 'BS IT', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(64, 'OLFU2022-344', 'Steven De Guzman', 'stevendeguzman@student.fatima.edu.ph', NULL, 'new student', '9472345695', 35400, 3, '1', 34000, 0, 'BS PSYCH', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(65, 'OLFU2022-345', 'Rebecca Madrigal', 'rebeccamadrigal@student.fatima.edu.ph', NULL, 'new enroll', '9482345696', 35100, 4, '1', 31500, 0, 'BS IHM', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(66, 'OLFU2022-346', 'Edison Yap', 'edisonyap@student.fatima.edu.ph', NULL, 'new student', '9502345697', 37800, 2, '1', 35500, 0, 'PSYCH', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(67, 'OLFU2022-347', 'Mikaela Roque', 'mikaelaroque@student.fatima.edu.ph', NULL, 'new student', '9512345698', 33700, 2, '1', 6700, 0, 'BS PHARMA', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(68, 'OLFU2022-348', 'Brent Quiros', 'brentquiros@student.fatima.edu.ph', NULL, 'new student', '9532345699', 33000, 2, '1', 5000, 0, 'BS PHARMA', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(69, 'OLFU2022-349', 'Cristine Pena', 'cristinepena@student.fatima.edu.ph', NULL, 'new student', '9552345700', 38400, 2, '1', 34400, 0, 'BS PHARMA', 86, 1.5, '2025-11-18 06:35:39', 7, 'testuser1'),
(70, 'OLFU2022-350', 'Leo Alingalan', 'leoalingalan@student.fatima.edu.ph', NULL, 'new student', '9562345701', 33400, 1, '1', 4800, 0, 'BSN', 80, 1.5, '2025-11-18 06:35:39', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2022_sem_2`
--

CREATE TABLE `student_2022_sem_2` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2022_sem_2`
--

INSERT INTO `student_2022_sem_2` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(71, 'OLFU2022-351', 'Denise Coronel', 'denisecoronel@student.fatima.edu.ph', NULL, 'new enroll', '9572345702', 36500, 1, '2', 35000, 0, 'BS ACC', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(72, 'OLFU2022-352', 'Tyler Valmorida', 'tylervalmorida@student.fatima.edu.ph', NULL, 'new student', '9602345703', 36000, 3, '2', 34600, 0, 'BSN', 86, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(73, 'OLFU2022-353', 'Nina Beltran', 'ninabeltran@student.fatima.edu.ph', NULL, 'new student', '9612345704', 35000, 3, '2', 32800, 0, 'BSA', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(74, 'OLFU2022-354', 'Kevin Uy', 'kevinuy@student.fatima.edu.ph', NULL, 'new enroll', '9632345705', 42000, 2, '2', 41500, 0, 'PSYCH', 86, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(75, 'OLFU2022-355', 'Isabelle Vergara', 'isabellevergara@student.fatima.edu.ph', NULL, 'new enroll', '9652345706', 33800, 2, '2', 5300, 0, 'BS PHARMA', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(76, 'OLFU2022-356', 'Bryan Magbanua', 'bryanmagbanua@student.fatima.edu.ph', NULL, 'new student', '9662345707', 33600, 1, '2', 4500, 0, 'BS PHARMA', 86, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(77, 'OLFU2022-357', 'Andrea Panlilio', 'andreapanlilio@student.fatima.edu.ph', NULL, 'new student', '9672345708', 37900, 1, '2', 35700, 0, 'BS PHARMA', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(78, 'OLFU2022-358', 'Lorenzo Borromeo', 'lorenzoborromeo@student.fatima.edu.ph', NULL, 'new student', '9682345709', 34200, 3, '2', 4900, 0, 'BSN', 86, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(79, 'OLFU2022-359', 'Jamie Rivera', 'jamierivera@student.fatima.edu.ph', NULL, 'new enroll', '9702345710', 40000, 2, '2', 38900, 0, 'BS ACC', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(80, 'OLFU2022-360', 'Ramon Esguerra', 'ramonesguerra@student.fatima.edu.ph', NULL, 'new student', '9752345711', 34800, 2, '2', 4700, 0, 'BSN', 86, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(81, 'OLFU2022-361', 'Clarity Soberano', 'claritysoberano@student.fatima.edu.ph', NULL, 'new student', '9772345712', 34100, 1, '2', 5200, 0, 'BSA', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(82, 'OLFU2022-362', 'Elmer Lazaro', 'elmerlazaro@student.fatima.edu.ph', NULL, 'new enroll', '9792345713', 35900, 1, '2', 31000, 0, 'BSN', 86, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(83, 'OLFU2022-363', 'Hazel Mendoza', 'hazelmendoza@student.fatima.edu.ph', NULL, 'new enroll', '9812345714', 34400, 3, '2', 4300, 0, 'BS PHARMA', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(84, 'OLFU2022-364', 'Andrei Canlas', 'andreicanlas@student.fatima.edu.ph', NULL, 'new student', '9852345715', 35200, 2, '2', 6700, 0, 'BS SE', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(85, 'OLFU2022-365', 'Maureen Dela Cruz', 'maureendelacruz@student.fatima.edu.ph', NULL, 'new student', '9872345716', 34600, 2, '2', 5600, 0, 'BS CS', 90, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(86, 'OLFU2022-366', 'Jacob Banaag', 'jacobbanaag@student.fatima.edu.ph', NULL, 'new student', '9892345717', 39000, 1, '2', 37300, 0, 'BS CE', 87, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(87, 'OLFU2022-367', 'Joanna Rebong', 'joannarebong@student.fatima.edu.ph', NULL, 'new enroll', '9922345718', 36000, 1, '2', 33000, 0, 'BSN', 79, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(88, 'OLFU2022-368', 'Cesar Valle', 'cesarvalle@student.fatima.edu.ph', NULL, 'new enroll', '9952345719', 33300, 3, '2', 4700, 0, 'BS PHARMA', 86, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(89, 'OLFU2022-369', 'Angelique Cayanan', 'angeliquecayanan@student.fatima.edu.ph', NULL, 'new student', '9972345720', 33500, 2, '2', 6800, 0, 'BS SE', 90, 2.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(90, 'OLFU2022-370', 'Tristan Batungbakal', 'tristanbatungbakal@student.fatima.edu.ph', NULL, 'new student', '9982345721', 37800, 2, '2', 39500, 0, 'BS CS', 85, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(91, 'OLFU2022-371', 'Liza Rivero', 'lizarivero@student.fatima.edu.ph', NULL, 'new enroll', '9992345722', 34200, 2, '2', 7400, 0, 'BS CE', 83, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(92, 'OLFU2022-372', 'Ronald Ferrer', 'ronaldferrer@student.fatima.edu.ph', NULL, 'new student', '9173456789', 34800, 1, '2', 32700, 0, 'BS ITMTTO', 94, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(93, 'OLFU2022-373', 'Cherisse Malabanan', 'cherissemalabanan@student.fatima.edu.ph', NULL, 'new student', '9183456780', 36500, 1, '2', 33700, 0, 'BS ITMTTO', 87, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(94, 'OLFU2022-374', 'Gerald Macaraig', 'geraldmacaraig@student.fatima.edu.ph', NULL, 'new student', '9193456781', 33900, 3, '2', 4800, 0, 'BSN', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(95, 'OLFU2022-375', 'Trixie Villamor', 'trixievillamor@student.fatima.edu.ph', NULL, 'new enroll', '9203456782', 35000, 2, '2', 6300, 0, 'BS CE', 90, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(96, 'OLFU2022-376', 'Paolo Orozco', 'paoloorozco@student.fatima.edu.ph', NULL, 'new enroll', '9213456783', 41200, 1, '2', 39300, 0, 'BSN', 87, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(97, 'OLFU2022-377', 'Katrina Mallari', 'katrinamallari@student.fatima.edu.ph', NULL, 'new student', '9223456784', 36100, 1, '2', 32400, 0, 'BS CE', 79, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(98, 'OLFU2022-378', 'Enrico Panganiban', 'enricopanganiban@student.fatima.edu.ph', NULL, 'new student', '9233456785', 34200, 3, '2', 4700, 0, 'BSA', 86, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(99, 'OLFU2022-379', 'Marlene Quintos', 'marlenequintos@student.fatima.edu.ph', NULL, 'new enroll', '9253456786', 35400, 2, '2', 5600, 0, 'BSA', 90, 2.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(100, 'OLFU2022-380', 'Vincent Orense', 'vincentorense@student.fatima.edu.ph', NULL, 'new student', '9273456787', 33600, 2, '2', 4500, 0, 'BS TM', 85, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(101, 'OLFU2022-381', 'Helen Sese', 'helensese@student.fatima.edu.ph', NULL, 'new student', '9283456788', 37000, 1, '2', 36000, 0, 'BS HACLO', 83, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(102, 'OLFU2022-382', 'Ian Dela Paz', 'iandelapaz@student.fatima.edu.ph', NULL, 'new student', '9293456789', 36000, 1, '2', 34500, 0, 'BSA', 94, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(103, 'OLFU2022-383', 'Sasha Robledo', 'sasharobledo@student.fatima.edu.ph', NULL, 'new enroll', '9303456780', 35500, 3, '2', 33700, 0, 'BSA', 87, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(104, 'OLFU2022-384', 'Gino Fuentebella', 'ginofuentebella@student.fatima.edu.ph', NULL, 'new enroll', '9323456781', 33900, 2, '2', 4900, 0, 'BS TM', 80, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(105, 'OLFU2022-385', 'Roxan Diaz', 'roxandiaz@student.fatima.edu.ph', NULL, 'new student', '9333456782', 34500, 2, '2', 32800, 0, 'BS HACLO', 90, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(106, 'OLFU2022-386', 'Louie Simbulan', 'louiesimbulan@student.fatima.edu.ph', NULL, 'new student', '9353456783', 34900, 1, '2', 8100, 0, 'MLS', 87, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(107, 'OLFU2022-387', 'Ariana Poblete', 'arianapoblete@student.fatima.edu.ph', NULL, 'new student', '9393456784', 35200, 1, '2', 7900, 0, 'BS IT', 79, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(108, 'OLFU2022-388', 'Jefferson Tuazon', 'jeffersontuazon@student.fatima.edu.ph', NULL, 'new enroll', '9433456785', 36300, 3, '2', 7000, 0, 'BS BA', 86, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(109, 'OLFU2022-389', 'Yanna Carino', 'yannacarino@student.fatima.edu.ph', NULL, 'new enroll', '9453456786', 33100, 2, '2', 5200, 0, 'PHARMA', 90, 2.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(110, 'OLFU2022-390', 'Melvin Galang', 'melvingalang@student.fatima.edu.ph', NULL, 'new student', '9473456787', 33600, 2, '2', 5000, 0, 'BS CE', 85, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(111, 'OLFU2022-391', 'Nadine Bellido', 'nadinebellido@student.fatima.edu.ph', NULL, 'new student', '9483456788', 40000, 1, '2', 38500, 0, 'BS CE', 83, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(112, 'OLFU2022-392', 'Caleb Romero', 'calebromero@student.fatima.edu.ph', NULL, 'new enroll', '9503456789', 37000, 1, '2', 35800, 0, 'BS CRIM', 94, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(113, 'OLFU2022-393', 'Julia Feliciano', 'juliafeliciano@student.fatima.edu.ph', NULL, 'new enroll', '9513456780', 34800, 3, '2', 4900, 0, 'BS CE', 87, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(114, 'OLFU2022-394', 'Seth Maniego', 'sethmaniego@student.fatima.edu.ph', NULL, 'new student', '9533456781', 33500, 2, '2', 6100, 0, 'BSN', 87, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(115, 'OLFU2022-395', 'Daisy Laus', 'daisylaus@student.fatima.edu.ph', NULL, 'new student', '9553456782', 34500, 2, '2', 6800, 0, 'MLS', 90, 2.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(116, 'OLFU2022-396', 'John Paul Bacani', 'johnpaulbacani@student.fatima.edu.ph', NULL, 'new student', '9563456783', 36900, 3, '2', 8900, 0, 'BS IT', 90, 2.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(117, 'OLFU2022-397', 'Regina Santillan', 'reginasantillan@student.fatima.edu.ph', NULL, 'new enroll', '9573456784', 36000, 2, '2', 6300, 0, 'BS BA', 85, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(118, 'OLFU2022-398', 'Enzo Banayo', 'enzobanayo@student.fatima.edu.ph', NULL, 'new enroll', '9603456785', 35800, 2, '2', 37100, 0, 'PHARMA', 83, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(119, 'OLFU2022-399', 'Maika Regala', 'maikaregala@student.fatima.edu.ph', NULL, 'new student', '9613456786', 34000, 3, '2', 5700, 0, 'BS CE', 94, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(120, 'OLFU2022-400', 'Trey Padua', 'treypadua@student.fatima.edu.ph', NULL, 'new student', '9633456787', 35900, 2, '2', 31500, 0, 'BS CE', 87, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(121, 'OLFU2022-401', 'Camila Osorio', 'camilaosorio@student.fatima.edu.ph', NULL, 'new enroll', '9653456788', 34400, 2, '2', 9900, 0, 'BS CRIM', 86, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(122, 'OLFU2022-402', 'Randy Sunga', 'randysunga@student.fatima.edu.ph', NULL, 'new enroll', '9663456789', 35000, 2, '2', 9300, 0, 'BS CE', 90, 2.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(123, 'OLFU2022-403', 'Karla Calingasan', 'karlacalingasan@student.fatima.edu.ph', NULL, 'new student', '9673456780', 37500, 3, '2', 36800, 0, 'BSN', 85, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(124, 'OLFU2022-404', 'Noel Mara', 'noelmara@student.fatima.edu.ph', NULL, 'new enroll', '9683456781', 35500, 2, '2', 38500, 0, 'BSN', 83, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(125, 'OLFU2022-405', 'Princess Agbayani', 'princessagbayani@student.fatima.edu.ph', NULL, 'new student', '9703456782', 36000, 2, '2', 35800, 0, 'BSN', 86, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(126, 'OLFU2022-406', 'Liam Benitez', 'liambenitez@student.fatima.edu.ph', NULL, 'new student', '9753456783', 34600, 2, '2', 4900, 0, 'BSN', 90, 2.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(127, 'OLFU2022-407', 'Eira Macapagal', 'eiramacapagal@student.fatima.edu.ph', NULL, 'new enroll', '9773456784', 35000, 2, '2', 6100, 0, 'BS CE', 85, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(128, 'OLFU2022-408', 'Louisse Delgado', 'louissedelgado@student.fatima.edu.ph', NULL, 'new enroll', '9793456785', 40000, 2, '2', 6800, 0, 'BS IT', 83, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(129, 'OLFU2022-409', 'Marvin Fabian', 'marvinfabian@student.fatima.edu.ph', NULL, 'new student', '9813456786', 37000, 2, '2', 8900, 0, 'BSN', 91, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(130, 'OLFU2022-410', 'Venice Balangit', 'venicebalangit@student.fatima.edu.ph', NULL, 'new student', '9853456787', 34800, 2, '2', 6300, 0, 'BS CE', 84, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(131, 'OLFU2022-411', 'Jonas Yambao', 'jonasyambao@student.fatima.edu.ph', NULL, 'new enroll', '9873456788', 33500, 3, '2', 37100, 0, 'BS IT', 88, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(132, 'OLFU2022-412', 'Cherie Basilio', 'cheriebasilio@student.fatima.edu.ph', NULL, 'new student', '9893456789', 34500, 2, '2', 5700, 0, 'BSN', 86, 2.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(133, 'OLFU2022-413', 'Matteo Carillo', 'matteocarillo@student.fatima.edu.ph', NULL, 'new student', '9923456780', 36900, 2, '2', 31500, 0, 'BSN', 79, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(134, 'OLFU2022-414', 'Selene Tabios', 'selenetabios@student.fatima.edu.ph', NULL, 'new enroll', '9953456781', 36000, 2, '2', 104, 0, 'BS CE', 93, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(135, 'OLFU2022-415', 'Rick Perez', 'rickperez@student.fatima.edu.ph', NULL, 'new enroll', '9973456782', 35800, 2, '2', 49500, 0, 'BS TM', 85, 3, '2025-11-18 06:43:04', 7, 'testuser1'),
(136, 'OLFU2022-416', 'Michiko Tiu', 'michikotiu@student.fatima.edu.ph', NULL, 'new enroll', '9983456783', 34000, 2, '2', 17800, 0, 'BSN', 82, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(137, 'OLFU2022-417', 'Arthur Baculi', 'arthurbaculi@student.fatima.edu.ph', NULL, 'new student', '9993456784', 35900, 2, '2', 94, 0, 'BS CE', 90, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(138, 'OLFU2022-418', 'Divine Leonor', 'divineleonor@student.fatima.edu.ph', NULL, 'new student', '9174567890', 35500, 2, '2', 41800, 0, 'BS TM', 87, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(139, 'OLFU2022-419', 'Jason Tagle', 'jasontagle@student.fatima.edu.ph', NULL, 'new enroll', '9184567891', 36000, 2, '2', 24100, 0, 'BSN', 95, 1.5, '2025-11-18 06:43:04', 7, 'testuser1'),
(140, 'OLFU2022-420', 'Ralph Enisimo', 'ralphenisimo@student.fatima.edu.ph', NULL, 'new enroll', '9853456783', 35500, 1, '2', 2500, 0, 'BS CA', 91, 3, '2025-11-18 06:43:04', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2023_sem_1`
--

CREATE TABLE `student_2023_sem_1` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2023_sem_1`
--

INSERT INTO `student_2023_sem_1` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(1, 'OLFU2023-421', 'Kimberly Tan', 'kimberlytan@student.fatima.edu.ph', NULL, 'new enrollment', '0917-234-5678', 52450, 2, '1', 32450, 0, 'BSN', 80, 1, '2025-11-16 13:10:45', 7, 'testuser1'),
(2, 'OLFU2023-422', 'Joshua Lim', 'joshualim@student.fatima.edu.ph', NULL, 'new enrollment', '0927-123-4567', 45680, 2, '1', 45680, 0, 'BSN', 84, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(3, 'OLFU2023-423', 'Princess Wong', 'princesswong@student.fatima.edu.ph', NULL, 'old enrollment', '0936-876-5432', 38750, 2, '1', 28750, 0, 'BS PHARMA', 87, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(4, 'OLFU2023-424', 'Michael Sy', 'michaelsy@student.fatima.edu.ph', NULL, 'new enroll', '0995-345-6789', 51200, 3, '1', 51200, 0, 'BS PT', 92, 2, '2025-11-16 13:10:45', 7, 'testuser1'),
(5, 'OLFU2023-425', 'Angelica Ong', 'angelicaong@student.fatima.edu.ph', NULL, 'new student from central branch', '0917-765-4321', 49825, 1, '1', 39825, 0, 'BSN', 78, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(6, 'OLFU2023-426', 'Christian Lee', 'christianlee@student.fatima.edu.ph', NULL, 'std from woodcreek branch', '0927-456-7890', 53100, 2, '1', 43100, 0, 'BS HRM', 86, 1, '2025-11-16 13:10:45', 7, 'testuser1'),
(7, 'OLFU2023-427', 'Stephanie Chua', 'stephaniechua@student.fatima.edu.ph', NULL, 'std from riverview branch', '0936-901-2345', 36975, 2, '1', 26975, 0, 'BSN', 90, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(8, 'OLFU2023-428', 'Kenneth Go', 'kennethgo@student.fatima.edu.ph', NULL, 'fresh enrollment', '0995-210-9876', 56340, 2, '1', 56340, 0, 'BS CRIM', 81, 2, '2025-11-16 13:10:45', 7, 'testuser1'),
(9, 'OLFU2023-429', 'Michelle Tiu', 'michelletiu@student.fatima.edu.ph', NULL, 'central student', '0917-567-8901', 47650, 3, '1', 37650, 0, 'BSN', 95, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(10, 'OLFU2023-430', 'Ryan Chan', 'ryanchan@student.fatima.edu.ph', NULL, 'none', '0927-890-1234', 59880, 3, '1', 49880, 0, 'BS MEDTECH', 83, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(11, 'OLFU2023-431', 'Jasmine Yang', 'jasmineyang@student.fatima.edu.ph', NULL, 'fresh enrollment', '0936-123-4567', 41225, 3, '1', 31225, 0, 'MEDTECH', 88, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(12, 'OLFU2023-432', 'Bryan Liu', 'bryanliu@student.fatima.edu.ph', NULL, 'none', '0995-678-9012', 54760, 2, '1', 54760, 0, 'BST', 85, 1, '2025-11-16 13:10:45', 7, 'testuser1'),
(13, 'OLFU2023-433', 'Nicole Wu', 'nicolewu@student.fatima.edu.ph', NULL, 'none', '0917-345-6789', 51390, 3, '1', 41390, 0, 'BSN', 79, 2, '2025-11-16 13:10:45', 7, 'testuser1'),
(14, 'OLFU2023-434', 'Daniel Ho', 'danielho@student.fatima.edu.ph', NULL, 'new enrollment', '0927-654-3210', 38450, 4, '1', 28450, 0, 'MLS', 91, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(15, 'OLFU2023-435', 'Sarah Chen', 'sarahchen@student.fatima.edu.ph', NULL, 'new enrollment', '0936-098-7654', 57820, 2, '1', 47820, 0, 'CRIM', 84, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(16, 'OLFU2023-436', 'Kevin Ng', 'kevinng@student.fatima.edu.ph', NULL, 'new enrollment', '0995-432-1098', 45670, 2, '1', 35670, 0, 'BSN', 87, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(17, 'OLFU2023-437', 'Alyssa Huang', 'alyssahuang@student.fatima.edu.ph', NULL, 'old enrollment', '0917-876-5432', 52910, 2, '1', 52910, 0, 'CRIM', 82, 1, '2025-11-16 13:10:45', 7, 'testuser1'),
(18, 'OLFU2023-438', 'Jerome Cheng', 'jeromecheng@student.fatima.edu.ph', NULL, 'new enroll', '0927-901-2345', 39680, 2, '1', 29680, 0, 'PHARMA', 94, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(19, 'OLFU2023-439', 'Alexandra Guo', 'alexandraguo@student.fatima.edu.ph', NULL, 'new student from central branch', '0936-543-2109', 54530, 2, '1', 44530, 0, 'BSN', 89, 2, '2025-11-16 13:10:45', 7, 'testuser1'),
(20, 'OLFU2023-440', 'Marcus Yap', 'marcusyap@student.fatima.edu.ph', NULL, 'std from woodcreek branch', '0995-890-1234', 48760, 2, '1', 38760, 0, 'BSPYSCH', 86, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(21, 'OLFU2023-441', 'Bianca Villa', 'biancavilla@student.fatima.edu.ph', NULL, 'std from riverview branch', '0917-012-3456', 36180, 2, '1', 26180, 0, 'BSHRM', 80, 1, '2025-11-16 13:10:45', 7, 'testuser1'),
(22, 'OLFU2023-442', 'Johann Cruz', 'johanncruz@student.fatima.edu.ph', NULL, 'fresh enrollment', '0927-345-6789', 50240, 2, '1', 50240, 0, 'BSN', 88, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(23, 'OLFU2023-443', 'Samantha Santos', 'samanthasantos@student.fatima.edu.ph', NULL, 'central student', '0936-789-0123', 52750, 2, '1', 42750, 0, 'MLS', 92, 2, '2025-11-16 13:10:45', 7, 'testuser1'),
(24, 'OLFU2023-444', 'Lorenzo Garcia', 'lorenzogarcia@student.fatima.edu.ph', NULL, 'none', '0995-109-8765', 43890, 3, '1', 33890, 0, 'BSN', 85, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(25, 'OLFU2023-445', 'Patricia Reyes', 'patriciareyes@student.fatima.edu.ph', NULL, 'fresh enrollment', '0917-432-1098', 58120, 2, '1', 58120, 0, 'PHARMA', 83, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(26, 'OLFU2023-446', 'Gabriel Torres', 'gabrieltorres@student.fatima.edu.ph', NULL, 'none', '0927-210-9876', 46450, 2, '1', 36450, 0, 'PHARMA', 90, 1, '2025-11-16 13:10:45', 7, 'testuser1'),
(27, 'OLFU2023-447', 'Catherine Lopez', 'catherinelopez@student.fatima.edu.ph', NULL, 'none', '0936-678-9012', 58670, 4, '1', 48670, 0, 'MEDTECH', 79, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(28, 'OLFU2023-448', 'Sebastian Flores', 'sebastianflores@student.fatima.edu.ph', NULL, 'new enrollment', '0995-098-7654', 40820, 2, '1', 30820, 0, 'MEDTECH', 87, 2, '2025-11-16 13:10:45', 7, 'testuser1'),
(29, 'OLFU2023-449', 'Christine Rivera', 'christinerivera@student.fatima.edu.ph', NULL, 'central student', '0918-123-4567', 55390, 1, '1', 55390, 0, 'BSN', 86, 2.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(30, 'OLFU2023-450', 'Nathaniel Morales', 'nathanielmorales@student.fatima.edu.ph', NULL, 'none', '0928-987-6543', 50180, 2, '1', 40180, 0, 'BSN', 90, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(31, 'OLFU2023-451', 'Janine Aquino', 'janineaquino@student.fatima.edu.ph', NULL, 'fresh enrollment', '0949-567-8901', 56950, 1, '1', 46950, 0, 'BSN', 83, 2.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(32, 'OLFU2023-452', 'Raphael Dela Rosa', 'raphaeldelarosa@student.fatima.edu.ph', NULL, 'none', '0998-234-5678', 37340, 1, '1', 27340, 0, 'CBA', 95, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(33, 'OLFU2023-453', 'Marissa Valdez', 'marissavaldez@student.fatima.edu.ph', NULL, 'none', '0918-765-4321', 53680, 1, '1', 53680, 0, 'BST', 84, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(34, 'OLFU2023-454', 'Emmanuel Mendoza', 'emmanuelmendoza@student.fatima.edu.ph', NULL, 'new enrollment', '0928-456-7890', 45920, 2, '1', 35920, 0, 'BSCE', 89, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(35, 'OLFU2023-455', 'Denise Hernandez', 'denisehernandez@student.fatima.edu.ph', NULL, 'new enrollment', '0949-901-2345', 53580, 3, '1', 43580, 0, 'PHARMA', 87, 2.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(36, 'OLFU2023-456', 'Anthony Jimenez', 'anthonyjimenez@student.fatima.edu.ph', NULL, 'new enrollment', '0998-012-3456', 41760, 4, '1', 31760, 0, 'BSN', 81, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(37, 'OLFU2023-457', 'Jennifer Ramos', 'jenniferramos@student.fatima.edu.ph', NULL, 'old enrollment', '0918-567-8901', 59240, 5, '1', 49240, 0, 'BSSE', 92, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(38, 'OLFU2023-458', 'Carlo Aguilar', 'carloaguilar@student.fatima.edu.ph', NULL, 'new enroll', '0928-890-1234', 48450, 2, '1', 38450, 0, 'BSSE', 85, 2.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(39, 'OLFU2023-459', 'Melody Castro', 'melodycastro@student.fatima.edu.ph', NULL, 'new student from central branch', '0949-123-4567', 36890, 3, '1', 26890, 0, 'BSN', 88, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(40, 'OLFU2023-460', 'Miguel Vargas', 'miguelvargas@student.fatima.edu.ph', NULL, 'central student', '0998-678-9012', 57430, 1, '1', 57430, 0, 'BSHRM', 86, 1.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(41, 'OLFU2023-461', 'Rochelle Gutierrez', 'rochellegutierrez@student.fatima.edu.ph', NULL, 'none', '0918-345-6789', 51680, 1, '1', 41680, 0, 'BSHRM', 90, 1.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(42, 'OLFU2023-462', 'Alessandro Martinez', 'alessandromartinez@student.fatima.edu.ph', NULL, 'fresh enrollment', '0928-654-3210', 44250, 1, '1', 34250, 0, 'BSN', 79, 1.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(43, 'OLFU2023-463', 'Clarisse Silva', 'clarissesilva@student.fatima.edu.ph', NULL, 'none', '0949-098-7654', 57590, 1, '1', 47590, 0, 'BSCE', 93, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(44, 'OLFU2023-464', 'Ferdinand Ortiz', 'ferdinandortiz@student.fatima.edu.ph', NULL, 'none', '0998-432-1098', 49170, 4, '1', 39170, 0, 'MEDTECH', 84, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(45, 'OLFU2023-465', 'Vanessa Romero', 'vanessaromero@student.fatima.edu.ph', NULL, 'new enrollment', '0918-876-5432', 52340, 5, '1', 52340, 0, 'BSN', 87, 2.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(46, 'OLFU2023-466', 'Giovanni Herrera', 'giovanniherrera@student.fatima.edu.ph', NULL, 'new enrollment', '0928-901-2345', 38920, 2, '1', 28920, 0, 'BSN', 91, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(47, 'OLFU2023-467', 'Jessica Medina', 'jessicamedina@student.fatima.edu.ph', NULL, 'new enrollment', '0949-543-2109', 55780, 3, '1', 45780, 0, 'BS PSYCH', 82, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(48, 'OLFU2023-468', 'Francesco Navarro', 'francesconavarro@student.fatima.edu.ph', NULL, 'old enrollment', '0998-890-1234', 47650, 1, '1', 37650, 0, 'BEED', 88, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(49, 'OLFU2023-469', 'Meredith Iglesias', 'meredithiglesias@student.fatima.edu.ph', NULL, 'new enroll', '0918-012-3456', 50860, 1, '1', 50860, 0, 'MLS', 85, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(50, 'OLFU2023-470', 'Leonardo Castillo', 'leonardocastillo@student.fatima.edu.ph', NULL, 'new student from central branch', '0928-345-6789', 42740, 3, '1', 32740, 0, 'BSITM', 95, 2.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(51, 'OLFU2023-471', 'Angelique Moreno', 'angeliquemoreno@student.fatima.edu.ph', NULL, 'fresh enrollment', '0949-789-0123', 56120, 1, '1', 56120, 0, 'BSN', 89, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(52, 'OLFU2023-472', 'Domenico Guerrero', 'domenicoguerrero@student.fatima.edu.ph', NULL, 'none', '0998-109-8765', 50390, 1, '1', 40390, 0, 'BSN', 83, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(53, 'OLFU2023-473', 'Cassandra Delgado', 'cassandradelgado@student.fatima.edu.ph', NULL, 'none', '0918-432-1098', 54670, 1, '1', 44670, 0, 'PHARMA', 86, 1.5, '2025-11-16 13:10:45', 7, 'testuser1'),
(54, 'OLFU2023-474', 'Ricardo Molina', 'ricardomolina@student.fatima.edu.ph', NULL, 'new enrollment', '0917-180-1182', 39580, 1, '1', 29580, 0, 'BSN', 88, 2, '2025-11-16 13:10:45', 7, 'testuser1'),
(55, 'OLFU2023-475', 'Genevieve Campos', 'genevievecampos@student.fatima.edu.ph', NULL, 'new enrollment', '0918-183-4185', 58930, 4, '1', 48930, 0, 'BSN', 82, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(56, 'OLFU2023-476', 'Valentino Lozano', 'valentinolozano@student.fatima.edu.ph', NULL, 'new enrollment', '0927-186-7188', 45210, 5, '1', 35210, 0, 'MLS', 89, 1, '2025-11-16 13:10:45', 7, 'testuser1'),
(57, 'OLFU2023-477', 'Francine Cortez', 'francinecortez@student.fatima.edu.ph', NULL, 'old enrollment', '0928-189-1191', 53480, 2, '1', 53480, 0, 'BSN', 79, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(58, 'OLFU2023-478', 'Salvatore Mendez', 'salvatoremendez@student.fatima.edu.ph', NULL, 'new enroll', '0936-192-3194', 41450, 1, '1', 31450, 0, 'BSN', 91, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(59, 'OLFU2023-479', 'Bernadette Espinoza', 'bernadetteespinoza@student.fatima.edu.ph', NULL, 'new student from central branch', '0949-195-6197', 52790, 3, '1', 42790, 0, 'BSEN', 85, 2, '2025-11-16 13:10:45', 7, 'testuser1'),
(60, 'OLFU2023-480', 'Antonio Nunez', 'antonionunez@student.fatima.edu.ph', NULL, 'central student', '0995-198-9200', 48670, 1, '1', 38670, 0, 'BSN', 88, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(61, 'OLFU2023-481', 'Katherine Sandoval', 'katherinesandoval@student.fatima.edu.ph', NULL, 'none', '0998-201-2203', 36560, 1, '1', 26560, 0, 'BSN', 93, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(62, 'OLFU2023-482', 'Lorenzo Cabrera', 'lorenzocabrera@student.fatima.edu.ph', NULL, 'fresh enrollment', '0991-204-5206', 51920, 1, '1', 51920, 0, 'BS PHARMA', 80, 1, '2025-11-16 13:10:45', 7, 'testuser1'),
(63, 'OLFU2023-483', 'Stephanie Fuentes', 'stephaniefuentes@student.fatima.edu.ph', NULL, 'none', '0917-207-8209', 49830, 1, '1', 39830, 0, 'BS PT', 84, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(64, 'OLFU2023-484', 'Alessandro Contreras', 'alessandrocontreras@student.fatima.edu.ph', NULL, 'none', '0918-210-1212', 57150, 4, '1', 47150, 0, 'BSN', 87, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(65, 'OLFU2023-485', 'Natasha Leon', 'natashaleon@student.fatima.edu.ph', NULL, 'fresh enrollment', '0927-213-4215', 43680, 1, '1', 33680, 0, 'BS HRM', 79, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(66, 'OLFU2023-486', 'Giovanni Alfonso', 'giovannialfonso@student.fatima.edu.ph', NULL, 'none', '0928-216-7218', 55740, 1, '1', 55740, 0, 'BSN', 91, 4, '2025-11-16 13:10:45', 7, 'testuser1'),
(67, 'OLFU2023-487', 'Arabella Blanco', 'arabellablanco@student.fatima.edu.ph', NULL, 'none', '0936-219-1221', 46920, 4, '1', 36920, 0, 'BS CRIM', 85, 2, '2025-11-16 13:10:45', 7, 'testuser1'),
(68, 'OLFU2023-488', 'Francesco Villa', 'francescovilla@student.fatima.edu.ph', NULL, 'new enrollment', '0949-222-3224', 59580, 3, '1', 49580, 0, 'BSN', 88, 5, '2025-11-16 13:10:45', 7, 'testuser1'),
(69, 'OLFU2023-489', 'Guinevere Soto', 'guineveresoto@student.fatima.edu.ph', NULL, 'new enrollment', '0995-225-6227', 40240, 1, '1', 30240, 0, 'BS MEDTECH', 93, 3, '2025-11-16 13:10:45', 7, 'testuser1'),
(70, 'OLFU2023-490', 'Leonardo Pena', 'leonardopena@student.fatima.edu.ph', NULL, 'new enrollment', '0998-228-9230', 54360, 1, '1', 54360, 0, 'MEDTECH', 80, 1, '2025-11-16 13:10:45', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2023_sem_2`
--

CREATE TABLE `student_2023_sem_2` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2023_sem_2`
--

INSERT INTO `student_2023_sem_2` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(71, 'OLFU2023-491', 'Seraphina Cano', 'seraphinacano@student.fatima.edu.ph', NULL, 'old enrollment', '0991-231-2233', 51750, 1, '2', 41750, 0, 'BST', 84, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(72, 'OLFU2023-492', 'Maximilian Marin', 'maximilianmarin@student.fatima.edu.ph', NULL, 'new enroll', '0917-234-5236', 38630, 1, '2', 28630, 0, 'BSN', 87, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(73, 'OLFU2023-493', 'Evangeline Ibarra', 'evangelineibarra@student.fatima.edu.ph', NULL, 'new student from central branch', '0918-237-8239', 56890, 4, '2', 46890, 0, 'MLS', 78, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(74, 'OLFU2023-494', 'Christopher Ferrer', 'christopherferrer@student.fatima.edu.ph', NULL, 'central student', '0927-240-1242', 47420, 5, '2', 37420, 0, 'CRIM', 86, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(75, 'OLFU2023-495', 'Francesca Duran', 'francescaduran@student.fatima.edu.ph', NULL, 'none', '0928-243-4245', 52670, 2, '2', 52670, 0, 'BSN', 90, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(76, 'OLFU2023-496', 'Sebastian Pastor', 'sebastianpastor@student.fatima.edu.ph', NULL, 'fresh enrollment', '0936-246-7248', 44580, 1, '2', 34580, 0, 'CRIM', 81, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(77, 'OLFU2023-497', 'Anastasia Bautista', 'anastasiabautista@student.fatima.edu.ph', NULL, 'none', '0949-249-1251', 58290, 4, '2', 48290, 0, 'PHARMA', 95, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(78, 'OLFU2023-498', 'Alessandro Salazar', 'alessandrosalazar@student.fatima.edu.ph', NULL, 'none', '0995-252-3254', 49660, 1, '2', 39660, 0, 'BSN', 83, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(79, 'OLFU2023-499', 'Penelope Valencia', 'penelopevalencia@student.fatima.edu.ph', NULL, 'new enrollment', '0998-255-6257', 36720, 1, '2', 26720, 0, 'BSPYSCH', 88, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(80, 'OLFU2023-500', 'Giovanni Gallego', 'giovannigallego@student.fatima.edu.ph', NULL, 'old enrollment', '0991-258-9260', 57180, 4, '2', 57180, 0, 'BSHRM', 85, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(81, 'OLFU2023-501', 'Cordelia Rubio', 'cordeliarubio@student.fatima.edu.ph', NULL, 'new enroll', '0917-261-2263', 50830, 3, '2', 40830, 0, 'BSN', 79, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(82, 'OLFU2023-502', 'Francesco Prieto', 'francescoprieto@student.fatima.edu.ph', NULL, 'new student from central branch', '0918-264-5266', 54950, 1, '2', 44950, 0, 'MLS', 91, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(83, 'OLFU2023-503', 'Serenity Serrano', 'serenityserrano@student.fatima.edu.ph', NULL, 'central student', '0927-267-8269', 41370, 1, '2', 31370, 0, 'BSN', 84, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(84, 'OLFU2023-504', 'Leonardo Vidal', 'leonardovidal@student.fatima.edu.ph', NULL, 'none', '0928-270-1272', 53620, 1, '2', 53620, 0, 'PHARMA', 87, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(85, 'OLFU2023-505', 'Harmony Mora', 'harmonymora@student.fatima.edu.ph', NULL, 'fresh enrollment', '0936-273-4275', 45480, 2, '2', 35480, 0, 'PHARMA', 82, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(86, 'OLFU2023-506', 'Domenico Fernandez', 'domenicofernandez@student.fatima.edu.ph', NULL, 'none', '0949-276-7278', 59740, 4, '2', 49740, 0, 'MEDTECH', 89, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(87, 'OLFU2023-507', 'Melodia Martin', 'melodiamartin@student.fatima.edu.ph', NULL, 'none', '0995-279-1281', 48290, 1, '2', 38290, 0, 'MEDTECH', 79, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(88, 'OLFU2023-508', 'Valentino Rodriguez', 'valentinorodriguez@student.fatima.edu.ph', NULL, 'new enrollment', '0998-282-3284', 57560, 1, '2', 47560, 0, 'BSN', 91, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(89, 'OLFU2023-509', 'Esperanza Gonzalez', 'esperanzagonzalez@student.fatima.edu.ph', NULL, 'old enrollment', '0991-285-6287', 42910, 4, '2', 32910, 0, 'BSN', 85, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(90, 'OLFU2023-510', 'Alessandro Perez', 'alessandroperez@student.fatima.edu.ph', NULL, 'new enroll', '0917-288-9290', 55430, 3, '2', 55430, 0, 'BSN', 88, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(91, 'OLFU2023-511', 'Seraphine Torres', 'seraphinetorres@student.fatima.edu.ph', NULL, 'new student from central branch', '0918-291-2293', 46180, 1, '2', 36180, 0, 'CBA', 93, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(92, 'OLFU2023-512', 'Giovanni Flores', 'giovanniflores@student.fatima.edu.ph', NULL, 'central student', '0927-294-5296', 53870, 1, '2', 43870, 0, 'BST', 80, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(93, 'OLFU2023-513', 'Celestine Rivera', 'celestinerivera@student.fatima.edu.ph', NULL, 'none', '0928-297-8299', 39450, 1, '2', 29450, 0, 'BSCE', 84, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(94, 'OLFU2023-514', 'Francesco Morales', 'francescomorales@student.fatima.edu.ph', NULL, 'fresh enrollment', '0936-300-1302', 51690, 3, '2', 51690, 0, 'PHARMA', 87, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(95, 'OLFU2023-515', 'Evangeline Jimenez', 'evangelinejimenez@student.fatima.edu.ph', NULL, 'none', '0949-303-4305', 50520, 1, '2', 40520, 0, 'BSN', 79, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(96, 'OLFU2023-516', 'Leonardo Ruiz', 'leonardoruiz@student.fatima.edu.ph', NULL, 'none', '0995-306-7308', 56320, 1, '2', 46320, 0, 'BSSE', 91, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(97, 'OLFU2023-517', 'Anastasia Vargas', 'anastasiavargas@student.fatima.edu.ph', NULL, 'new enrollment', '0998-309-1311', 37890, 1, '2', 27890, 0, 'BSSE', 90, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(98, 'OLFU2023-518', 'Domenico Castillo', 'domenicocastillo@student.fatima.edu.ph', NULL, 'old enrollment', '0991-312-3314', 54980, 1, '2', 54980, 0, 'BSN', 81, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(99, 'OLFU2023-519', 'Penelope Ortiz', 'penelopeortiz@student.fatima.edu.ph', NULL, 'new enroll', '0917-315-6317', 49240, 4, '2', 39240, 0, 'BSHRM', 95, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(100, 'OLFU2023-520', 'Giovanni Ramos', 'giovanniramos@student.fatima.edu.ph', NULL, 'new student from central branch', '0918-318-9320', 58670, 1, '2', 48670, 0, 'BSHRM', 83, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(101, 'OLFU2023-521', 'Cordelia Medina', 'cordeliamedina@student.fatima.edu.ph', NULL, 'central student', '0927-321-2323', 43150, 1, '2', 33150, 0, 'BSN', 88, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(102, 'OLFU2023-522', 'Francesco Aguilar', 'francescoaguilar@student.fatima.edu.ph', NULL, 'none', '0928-324-5326', 56820, 4, '2', 56820, 0, 'BSCE', 85, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(103, 'OLFU2023-523', 'Serenity Mendoza', 'serenitymendoza@student.fatima.edu.ph', NULL, 'fresh enrollment', '0936-327-8329', 47940, 3, '2', 37940, 0, 'MEDTECH', 79, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(104, 'OLFU2023-524', 'Leonardo Vega', 'leonardovega@student.fatima.edu.ph', NULL, 'none', '0949-330-1332', 55280, 1, '2', 45280, 0, 'BSN', 91, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(105, 'OLFU2023-525', 'Harmony Reyes', 'harmonyreyes@student.fatima.edu.ph', NULL, 'none', '0995-333-4335', 40680, 1, '2', 30680, 0, 'BSN', 84, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(106, 'OLFU2023-526', 'Alessandro Gutierrez', 'alessandrogutierrez@student.fatima.edu.ph', NULL, 'new enroll', '0998-336-7338', 52550, 1, '2', 52550, 0, 'BS PSYCH', 87, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(107, 'OLFU2023-527', 'Melodia Silva', 'melodiasilva@student.fatima.edu.ph', NULL, 'new student from central branch', '0991-339-1341', 51370, 2, '2', 41370, 0, 'BEED', 82, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(108, 'OLFU2023-528', 'Valentino Castro', 'valentinocastro@student.fatima.edu.ph', NULL, 'central student', '0917-342-3344', 38760, 4, '2', 28760, 0, 'MLS', 91, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(109, 'OLFU2023-529', 'Esperanza Romero', 'esperanzaromero@student.fatima.edu.ph', NULL, 'none', '0918-345-6347', 59890, 1, '2', 49890, 0, 'BSITM', 90, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(110, 'OLFU2023-530', 'Giovanni Herrera', 'giovanniherrera@student.fatima.edu.ph', NULL, 'fresh enrollment', '0927-348-9350', 45620, 1, '2', 35620, 0, 'BSN', 81, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(111, 'OLFU2023-531', 'Seraphine Moreno', 'seraphinemoreno@student.fatima.edu.ph', NULL, 'fresh enrollment', '0928-351-2353', 53240, 4, '2', 53240, 0, 'BSN', 95, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(112, 'OLFU2023-532', 'Francesco Iglesias', 'francescoiglesias@student.fatima.edu.ph', NULL, 'new enroll', '0936-354-5356', 42480, 3, '2', 32480, 0, 'PHARMA', 83, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(113, 'OLFU2023-533', 'Celestine Navarro', 'celestinenavarro@student.fatima.edu.ph', NULL, 'new student from central branch', '0949-357-8359', 57950, 1, '2', 47950, 0, 'BSN', 88, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(114, 'OLFU2023-534', 'Leonardo Delgado', 'leonardodelgado@student.fatima.edu.ph', NULL, 'central student', '0995-360-1362', 48810, 1, '2', 38810, 0, 'BSN', 85, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(115, 'OLFU2023-535', 'Evangeline Molina', 'evangelinemolina@student.fatima.edu.ph', NULL, 'none', '0998-363-4365', 36340, 1, '2', 26340, 0, 'MLS', 79, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(116, 'OLFU2023-536', 'Domenico Campos', 'domenicocampos@student.fatima.edu.ph', NULL, 'new enroll', '0991-366-7368', 55670, 3, '2', 55670, 0, 'BSN', 91, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(117, 'OLFU2023-537', 'Anastasia Guerrero', 'anastasiaguerrero@student.fatima.edu.ph', NULL, 'new student from central branch', '0917-369-1371', 50920, 1, '2', 40920, 0, 'BSN', 84, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(118, 'OLFU2023-538', 'Giovanni Lozano', 'giovannilozano@student.fatima.edu.ph', NULL, 'central student', '0918-372-3374', 54180, 1, '2', 44180, 0, 'BSEN', 87, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(119, 'OLFU2023-539', 'Penelope Cortez', 'penelopecortez@student.fatima.edu.ph', NULL, 'none', '0927-375-6377', 41590, 1, '2', 31590, 0, 'BS CE', 83, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(120, 'OLFU2023-540', 'Francesco Valdez', 'francescovaldez@student.fatima.edu.ph', NULL, 'fresh enrollment', '0928-378-9380', 51340, 1, '2', 51340, 0, 'BS IT', 88, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(121, 'OLFU2023-541', 'Cordelia Espinoza', 'cordeliaespinoza@student.fatima.edu.ph', NULL, 'central student', '0936-381-2383', 47250, 4, '2', 37250, 0, 'BS CA', 85, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(122, 'OLFU2023-542', 'Leonardo Nunez', 'leonardonunez@student.fatima.edu.ph', NULL, 'none', '0949-384-5386', 58760, 1, '2', 48760, 0, 'BSCMM', 79, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(123, 'OLFU2023-543', 'Serenity Sandoval', 'serenitysandoval@student.fatima.edu.ph', NULL, 'fresh enrollment', '0995-387-8389', 44870, 1, '2', 34870, 0, 'BS BIO', 91, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(124, 'OLFU2023-544', 'Alessandro Cabrera', 'alessandrocabrera@student.fatima.edu.ph', NULL, 'new enroll', '0998-390-1392', 56490, 4, '2', 56490, 0, 'MT', 84, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(125, 'OLFU2023-545', 'Harmony Fuentes', 'harmonyfuentes@student.fatima.edu.ph', NULL, 'new student from central branch', '0991-393-4395', 49630, 3, '2', 39630, 0, 'BSTIM-TIO', 87, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(126, 'OLFU2023-546', 'Valentino Contreras', 'valentinocontreras@student.fatima.edu.ph', NULL, 'central student', '0917-396-7398', 56120, 1, '2', 46120, 0, 'HACLO', 82, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(127, 'OLFU2023-547', 'Melodia Leon', 'melodialeon@student.fatima.edu.ph', NULL, 'new enroll', '0918-399-1401', 39780, 3, '2', 29780, 0, 'BSC', 94, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(128, 'OLFU2023-548', 'Giovanni Alfonso', 'giovannialfonso@student.fatima.edu.ph', NULL, 'new student from central branch', '0927-402-3404', 53860, 1, '2', 53860, 0, 'BSIHM', 89, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(129, 'OLFU2023-549', 'Esperanza Blanco', 'esperanzablanco@student.fatima.edu.ph', NULL, 'central student', '0928-405-6407', 45940, 1, '2', 35940, 0, 'CHIM-HMD', 86, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(130, 'OLFU2023-550', 'Francesco Villa', 'francescovilla@student.fatima.edu.ph', NULL, 'none', '0936-408-9410', 52470, 1, '2', 42470, 0, 'BS HACLO', 80, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(131, 'OLFU2023-551', 'Seraphine Soto', 'seraphinesoto@student.fatima.edu.ph', NULL, 'fresh enrollment', '0949-411-2413', 40150, 1, '2', 30150, 0, 'EDUC', 88, 4, '2025-11-16 13:18:51', 7, 'testuser1'),
(132, 'OLFU2023-552', 'Leonardo Pena', 'leonardopena@student.fatima.edu.ph', NULL, 'new enroll', '0995-414-5416', 59680, 4, '2', 49680, 0, 'BSCSI', 92, 2, '2025-11-16 13:18:51', 7, 'testuser1'),
(133, 'OLFU2023-553', 'Celestine Cano', 'celestinecano@student.fatima.edu.ph', NULL, 'new student from central branch', '0998-417-8419', 48520, 1, '2', 38520, 0, 'BS ACC', 85, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(134, 'OLFU2023-554', 'Domenico Marin', 'domenicomarin@student.fatima.edu.ph', NULL, 'central student', '0991-420-1422', 37640, 3, '2', 27640, 0, 'BSN', 83, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(135, 'OLFU2023-555', 'Evangeline Ibarra', 'evangelineibarra@student.fatima.edu.ph', NULL, 'none', '0917-423-4425', 54920, 1, '2', 30150, 0, 'BS A', 81, 1, '2025-11-16 13:18:51', 7, 'testuser1'),
(136, 'OLFU2023-556', 'Alessandro Ferrer', 'alessandroferrer@student.fatima.edu.ph', NULL, 'fresh enrollment', '0918-426-7428', 46780, 1, '2', 49680, 0, 'BSN', 86, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(137, 'OLFU2023-557', 'Anastasia Duran', 'anastasiaduran@student.fatima.edu.ph', NULL, 'new enroll', '0927-429-1431', 57250, 1, '2', 38520, 0, 'BS PHARMA', 89, 2.5, '2025-11-16 13:18:51', 7, 'testuser1'),
(138, 'OLFU2023-558', 'Giovanni Pastor', 'giovannipastor@student.fatima.edu.ph', NULL, 'new student from central branch', '0928-432-3434', 43590, 1, '2', 27640, 0, 'BS SE', 78, 5, '2025-11-16 13:18:51', 7, 'testuser1'),
(139, 'OLFU2023-559', 'Penelope Bautista', 'penelopebautista@student.fatima.edu.ph', NULL, 'central student', '0917-588-9590', 55340, 4, '2', 27640, 0, 'BS CS', 92, 3, '2025-11-16 13:18:51', 7, 'testuser1'),
(140, 'OLFU2023-560', 'Francesco Salazar', 'francescosalazar@student.fatima.edu.ph', NULL, 'none', '0918-591-2593', 49180, 2, '2', 30150, 0, 'BS CE', 85, 2.5, '2025-11-16 13:18:51', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2024_sem_1`
--

CREATE TABLE `student_2024_sem_1` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2024_sem_1`
--

INSERT INTO `student_2024_sem_1` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(1, 'OLFU2024-561', 'Alexander Martinez', 'alexander.martinez@student.fatima.edu.ph', NULL, 'new student from central branch', '9211663788', 67754, 4, '1', 49681, 0, 'PSYCH', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(2, 'OLFU2024-562', 'Benjamin Rodriguez', 'benjamin.rodriguez@student.fatima.edu.ph', NULL, 'central student', '9677699282', 42905, 1, '1', 31147, 0, 'BS PHARMA', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(3, 'OLFU2024-563', 'Christopher Johnson', 'christopher.johnson@student.fatima.edu.ph', NULL, 'none', '9067893791', 31657, 1, '1', 30236, 0, 'BS PHARMA', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(4, 'OLFU2024-564', 'Daniel Garcia', 'daniel.garcia@student.fatima.edu.ph', NULL, 'fresh enrollment', '9221086047', 45567, 4, '1', 29305, 0, 'BS PHARMA', 94, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(5, 'OLFU2024-565', 'Edward Thompson', 'edward.thompson@student.fatima.edu.ph', NULL, 'none', '9724858723', 42209, 3, '1', 38851, 0, 'BSN', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(6, 'OLFU2024-566', 'Frederick Wilson', 'frederick.wilson@student.fatima.edu.ph', NULL, 'none', '9189541875', 63388, 1, '1', 46374, 0, 'BS ACC', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(7, 'OLFU2024-567', 'Gabriel Anderson', 'gabriel.anderson@student.fatima.edu.ph', NULL, 'new enrollment', '9012047847', 61041, 3, '1', 53188, 0, 'BSN', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(8, 'OLFU2024-568', 'Harrison Taylor', 'harrison.taylor@student.fatima.edu.ph', NULL, 'old enrollment', '9166995170', 61722, 1, '1', 47035, 0, 'BSA', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(9, 'OLFU2024-569', 'Isaac Brown', 'isaac.brown@student.fatima.edu.ph', NULL, 'new enroll', '9135025875', 59678, 1, '1', 55557, 0, 'BSN', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(10, 'OLFU2024-570', 'Jonathan Davis', 'jonathan.davis@student.fatima.edu.ph', NULL, 'new student from central branch', '9792030251', 56986, 1, '1', 54631, 0, 'BS PHARMA', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(11, 'OLFU2024-571', 'Kevin Miller', 'kevin.miller@student.fatima.edu.ph', NULL, 'central student', '9879579551', 50213, 1, '1', 30036, 0, 'BS SE', 85, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(12, 'OLFU2024-572', 'Leonardo Lopez', 'leonardo.lopez@student.fatima.edu.ph', NULL, 'none', '9057148276', 45658, 4, '1', 37488, 0, 'BS CS', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(13, 'OLFU2024-573', 'Matthew White', 'matthew.white@student.fatima.edu.ph', NULL, 'fresh enrollment', '9784363121', 49060, 1, '1', 46607, 0, 'BS CE', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(14, 'OLFU2024-574', 'Nicholas Harris', 'nicholas.harris@student.fatima.edu.ph', NULL, 'none', '9942565068', 66686, 3, '1', 66268, 0, 'BSN', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(15, 'OLFU2024-575', 'Oliver Martin', 'oliver.martin@student.fatima.edu.ph', NULL, 'none', '9401746908', 64624, 1, '1', 51866, 0, 'BS PHARMA', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(16, 'OLFU2024-576', 'Patrick Clark', 'patrick.clark@student.fatima.edu.ph', NULL, 'new enroll', '9675266604', 64419, 1, '1', 33580, 0, 'BS SE', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(17, 'OLFU2024-577', 'Quinton Lewis', 'quinton.lewis@student.fatima.edu.ph', NULL, 'new student from central branch', '9023549618', 64247, 1, '1', 61560, 0, 'BS CS', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(18, 'OLFU2024-578', 'Robert Walker', 'robert.walker@student.fatima.edu.ph', NULL, 'fresh enrollment', '9881218770', 54238, 1, '1', 49909, 0, 'BS CE', 94, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(19, 'OLFU2024-579', 'Samuel Hall', 'samuel.hall@student.fatima.edu.ph', NULL, 'fresh enrollment', '9318118230', 35237, 4, '1', 30122, 0, 'BS ITMTTO', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(20, 'OLFU2024-580', 'Theodore Young', 'theodore.young@student.fatima.edu.ph', NULL, 'new enroll', '9621905862', 41839, 2, '1', 36534, 0, 'BS ITMTTO', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(21, 'OLFU2024-581', 'Vincent King', 'vincent.king@student.fatima.edu.ph', NULL, 'new student from central branch', '9629876002', 45533, 1, '1', 34729, 0, 'BSN', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(22, 'OLFU2024-582', 'William Wright', 'william.wright@student.fatima.edu.ph', NULL, 'central student', '9018013739', 54033, 1, '1', 50252, 0, 'BS CE', 94, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(23, 'OLFU2024-583', 'Xavier Green', 'xavier.green@student.fatima.edu.ph', NULL, 'none', '9343768629', 35123, 1, '1', 32044, 0, 'BSN', 85, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(24, 'OLFU2024-584', 'Zachary Adams', 'zachary.adams@student.fatima.edu.ph', NULL, 'new enroll', '9902470552', 60046, 2, '1', 51933, 0, 'BS CE', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(25, 'OLFU2024-585', 'Adrian Baker', 'adrian.baker@student.fatima.edu.ph', NULL, 'new student from central branch', '9478809849', 40457, 4, '1', 29215, 0, 'BSA', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(26, 'OLFU2024-586', 'Blake Nelson', 'blake.nelson@student.fatima.edu.ph', NULL, 'central student', '9060349411', 38431, 1, '1', 34627, 0, 'BSA', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(27, 'OLFU2024-587', 'Carlos Rivera', 'carlos.rivera@student.fatima.edu.ph', NULL, 'none', '9851026140', 64331, 1, '1', 61006, 0, 'BS TM', 85, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(28, 'OLFU2024-588', 'Diego Carter', 'diego.carter@student.fatima.edu.ph', NULL, 'fresh enrollment', '9385348702', 45238, 4, '1', 41866, 0, 'BS HACLO', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(29, 'OLFU2024-589', 'Ethan Mitchell', 'ethan.mitchell@student.fatima.edu.ph', NULL, 'central student', '9038175477', 52326, 3, '1', 49773, 0, 'BSA', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(30, 'OLFU2024-590', 'Felix Turner', 'felix.turner@student.fatima.edu.ph', NULL, 'none', '9379472851', 43420, 1, '1', 39588, 0, 'BSA', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(31, 'OLFU2024-591', 'George Parker', 'george.parker@student.fatima.edu.ph', NULL, 'fresh enrollment', '9827303890', 42565, 1, '1', 35567, 0, 'BS CE', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(32, 'OLFU2024-592', 'Henry Cooper', 'henry.cooper@student.fatima.edu.ph', NULL, 'new enroll', '9604881209', 58932, 1, '1', 44356, 0, 'BS CRIM', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(33, 'OLFU2024-593', 'Ivan Reed', 'ivan.reed@student.fatima.edu.ph', NULL, 'new student from central branch', '9955165730', 30987, 3, '1', 29543, 0, 'BS CE', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(34, 'OLFU2024-594', 'James Cook', 'james.cook@student.fatima.edu.ph', NULL, 'central student', '9286173728', 66159, 1, '1', 43587, 0, 'BSN', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(35, 'OLFU2024-595', 'Kyle Morgan', 'kyle.morgan@student.fatima.edu.ph', NULL, 'new enroll', '9707015750', 37215, 1, '1', 33453, 0, 'MLS', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(36, 'OLFU2024-596', 'Abigail Santos', 'abigail.santos@student.fatima.edu.ph', NULL, 'new student from central branch', '9259588526', 64058, 1, '1', 48221, 0, 'BS IT', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(37, 'OLFU2024-597', 'Beatrice Flores', 'beatrice.flores@student.fatima.edu.ph', NULL, 'central student', '9013601963', 50463, 1, '1', 49769, 0, 'BS BA', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(38, 'OLFU2024-598', 'Catherine Hughes', 'catherine.hughes@student.fatima.edu.ph', NULL, 'central student', '9576950649', 67218, 3, '1', 54022, 0, 'PHARMA', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(39, 'OLFU2024-599', 'Diana Powell', 'diana.powell@student.fatima.edu.ph', NULL, 'none', '9152549465', 59770, 1, '1', 54010, 0, 'BS CE', 85, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(40, 'OLFU2024-600', 'Elizabeth Torres', 'elizabeth.torres@student.fatima.edu.ph', NULL, 'fresh enrollment', '9495699101', 32354, 1, '1', 31127, 0, 'BS CE', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(41, 'OLFU2024-601', 'Francesca Bell', 'francesca.bell@student.fatima.edu.ph', NULL, 'new enroll', '9704049382', 62157, 1, '1', 30236, 0, 'BS CRIM', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(42, 'OLFU2024-602', 'Grace Murphy', 'grace.murphy@student.fatima.edu.ph', NULL, 'new student from central branch', '9913042144', 62122, 3, '1', 53196, 0, 'BS CE', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(43, 'OLFU2024-603', 'Hannah Rivera', 'hannah.rivera@student.fatima.edu.ph', NULL, 'central student', '9056961315', 60415, 1, '1', 47312, 0, 'BSN', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(44, 'OLFU2024-604', 'Isabella Cruz', 'isabella.cruz@student.fatima.edu.ph', NULL, 'new enroll', '9523285965', 51124, 1, '1', 32252, 0, 'BSN', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(45, 'OLFU2024-605', 'Jessica Morales', 'jessica.morales@student.fatima.edu.ph', NULL, 'new student from central branch', '9274197732', 47117, 1, '1', 45166, 0, 'BSN', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(46, 'OLFU2024-606', 'Katherine Gutierrez', 'katherine.gutierrez@student.fatima.edu.ph', NULL, 'central student', '9976104088', 57250, 1, '1', 30132, 0, 'BSN', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(47, 'OLFU2024-607', 'Lillian Ortiz', 'lillian.ortiz@student.fatima.edu.ph', NULL, 'none', '9441507455', 49437, 4, '1', 31128, 0, 'BS CE', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(48, 'OLFU2024-608', 'Margaret Ramirez', 'margaret.ramirez@student.fatima.edu.ph', NULL, 'fresh enrollment', '9777021032', 51562, 1, '1', 45463, 0, 'BS IT', 94, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(49, 'OLFU2024-609', 'Natalie Castillo', 'natalie.castillo@student.fatima.edu.ph', NULL, 'new enroll', '9898122451', 54349, 1, '1', 43992, 0, 'BSN', 85, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(50, 'OLFU2024-610', 'Olivia Vargas', 'olivia.vargas@student.fatima.edu.ph', NULL, 'new student from central branch', '9857354624', 51580, 4, '1', 39954, 0, 'BS CE', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(51, 'OLFU2024-611', 'Patricia Herrera', 'patricia.herrera@student.fatima.edu.ph', NULL, 'central student', '9649424249', 58576, 1, '1', 41342, 0, 'BS IT', 85, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(52, 'OLFU2024-612', 'Rachel Mendoza', 'rachel.mendoza@student.fatima.edu.ph', NULL, 'none', '9079805228', 51445, 1, '1', 47037, 0, 'BSN', 94, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(53, 'OLFU2024-613', 'Sophia Jimenez', 'sophia.jimenez@student.fatima.edu.ph', NULL, 'fresh enrollment', '9631220349', 57186, 4, '1', 51644, 0, 'BSN', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(54, 'OLFU2024-614', 'Teresa Ruiz', 'teresa.ruiz@student.fatima.edu.ph', NULL, 'new enroll', '9169765551', 53321, 3, '1', 44162, 0, 'BS CE', 94, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(55, 'OLFU2024-615', 'Victoria Gonzalez', 'victoria.gonzalez@student.fatima.edu.ph', NULL, 'new student from central branch', '9466054609', 66262, 1, '1', 58524, 0, 'BS TM', 85, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(56, 'OLFU2024-616', 'Amanda Foster', 'amanda.foster@student.fatima.edu.ph', NULL, 'central student', '9581490946', 39842, 4, '1', 34834, 0, 'BSN', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(57, 'OLFU2024-617', 'Brenda Coleman', 'brenda.coleman@student.fatima.edu.ph', NULL, 'none', '9235863615', 62614, 1, '1', 33841, 0, 'BS CE', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(58, 'OLFU2024-618', 'Carmen Diaz', 'carmen.diaz@student.fatima.edu.ph', NULL, 'central student', '9139022561', 32431, 1, '1', 31022, 0, 'BS TM', 94, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(59, 'OLFU2024-619', 'Deborah Price', 'deborah.price@student.fatima.edu.ph', NULL, 'none', '9614357208', 39246, 4, '1', 31664, 0, 'BS IT', 85, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(60, 'OLFU2024-620', 'Emily Ross', 'emily.ross@student.fatima.edu.ph', NULL, 'new enroll', '9569118046', 46096, 3, '1', 30176, 0, 'BSN', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(61, 'OLFU2024-621', 'Faith Brooks', 'faith.brooks@student.fatima.edu.ph', NULL, 'new student from central branch', '9381373144', 51324, 1, '1', 31581, 0, 'BSN', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(62, 'OLFU2024-622', 'Gloria Wood', 'gloria.wood@student.fatima.edu.ph', NULL, 'central student', '9129502447', 52096, 4, '1', 34270, 0, 'BS CE', 94, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(63, 'OLFU2024-623', 'Helen Gray', 'helen.gray@student.fatima.edu.ph', NULL, 'none', '9849057655', 37460, 1, '1', 36194, 0, 'BS TM', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(64, 'OLFU2024-624', 'Irene James', 'irene.james@student.fatima.edu.ph', NULL, 'fresh enrollment', '9269705478', 32304, 1, '1', 31698, 0, 'BSN', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(65, 'OLFU2024-625', 'Julia Peterson', 'julia.peterson@student.fatima.edu.ph', NULL, 'new enroll', '9259974685', 29982, 4, '1', 29419, 0, 'BS CE', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(66, 'OLFU2024-626', 'Karen Bailey', 'karen.bailey@student.fatima.edu.ph', NULL, 'new student from central branch', '9651593014', 58976, 3, '1', 50123, 0, 'BS TM', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1'),
(67, 'OLFU2024-627', 'Laura Cox', 'laura.cox@student.fatima.edu.ph', NULL, 'central student', '9801829577', 47850, 1, '1', 36289, 0, 'BS IT', 85, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(68, 'OLFU2024-628', 'Monica Ward', 'monica.ward@student.fatima.edu.ph', NULL, 'new enroll', '9418259426', 64210, 4, '1', 51345, 0, 'BSN', 94, 2, '2025-11-16 13:11:52', 7, 'testuser1'),
(69, 'OLFU2024-629', 'Nicole Butler', 'nicole.butler@student.fatima.edu.ph', NULL, 'new student from central branch', '9284113173', 32560, 1, '1', 29780, 0, 'BSN', 85, 3, '2025-11-16 13:11:52', 7, 'testuser1'),
(70, 'OLFU2024-630', 'Patricia Stewart', 'patricia.stewart@student.fatima.edu.ph', NULL, 'new student from central branch', '9098293365', 32431, 1, '1', 31022, 0, 'BS CE', 94, 1, '2025-11-16 13:11:52', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2024_sem_2`
--

CREATE TABLE `student_2024_sem_2` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2024_sem_2`
--

INSERT INTO `student_2024_sem_2` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(1, 'OLFU2024-631', 'Alexander Thompson', 'alexander.thompson@student.fatima.edu.ph', NULL, 'fresh enrollment', '9123456789', 54000, 4, '2', 12500, 0, 'BSITM', 85, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(2, 'OLFU2024-632', 'Maria Rodriguez', 'maria.rodriguez@student.fatima.edu.ph', NULL, 'none', '9234567810', 67500, 3, '2', 10000, 0, 'MLS', 90, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(3, 'OLFU2024-633', 'James Chen', 'james.chen@student.fatima.edu.ph', NULL, 'none', '9345678921', 34200, 1, '2', 5000, 0, 'BC CS', 84, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(4, 'OLFU2024-634', 'Sofia Andersson', 'sofia.andersson@student.fatima.edu.ph', NULL, 'new enroll', '9456789032', 39000, 4, '2', 3000, 0, 'MLS', 93, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(5, 'OLFU2024-635', 'David Kim', 'david.kim@student.fatima.edu.ph', NULL, 'new student from central branch', '9567890143', 61000, 1, '2', 8500, 0, 'BS A', 87, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(6, 'OLFU2024-636', 'Isabella Martinez', 'isabella.martinez@student.fatima.edu.ph', NULL, 'fresh enrollment', '9678901254', 73200, 1, '2', 12000, 0, 'BS IT', 95, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(7, 'OLFU2024-637', 'Michael Connor', 'michael.oconnor@student.fatima.edu.ph', NULL, 'fresh enrollment', '9789012365', 48000, 4, '2', 2000, 0, 'BS HACLO', 86, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(8, 'OLFU2024-638', 'Priya Patel', 'priya.patel@student.fatima.edu.ph', NULL, 'new enroll', '9890123476', 35000, 3, '2', 1500, 0, 'BSN', 88, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(9, 'OLFU2024-639', 'Robert Johnson', 'robert.johnson@student.fatima.edu.ph', NULL, 'new student from central branch', '9901234587', 77000, 1, '2', 9000, 0, 'BS HACLO', 97, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(10, 'OLFU2024-640', 'Amara Singh', 'amara.singh@student.fatima.edu.ph', NULL, 'central student', '9012345698', 69000, 4, '2', 4500, 0, 'MLS', 91, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(11, 'OLFU2024-641', 'Christopher Lee', 'christopher.lee@student.fatima.edu.ph', NULL, 'none', '9123456709', 45000, 1, '2', 1200, 0, 'BS HACLO', 84, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(12, 'OLFU2024-642', 'Elena Volkov', 'elena.volkov@student.fatima.edu.ph', NULL, 'new enroll', '9234567811', 64000, 1, '2', 4000, 0, 'BS HACLO', 92, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(13, 'OLFU2024-643', 'Daniel Brown', 'daniel.brown@student.fatima.edu.ph', NULL, 'new student from central branch', '9345678922', 52000, 4, '2', 5000, 0, 'MLS', 94, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(14, 'OLFU2024-644', 'Zara Al-Rashid', 'zara.alrashid@student.fatima.edu.ph', NULL, 'central student', '9456789033', 37000, 3, '2', 1700, 0, 'BS BS MM', 85, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(15, 'OLFU2024-645', 'Thomas Wilson', 'thomas.wilson@student.fatima.edu.ph', NULL, 'fresh enrollment', '9567890144', 68000, 1, '2', 2000, 0, 'BSN', 89, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(16, 'OLFU2024-646', 'Yuki Tanaka', 'yuki.tanaka@student.fatima.edu.ph', NULL, 'central student', '9678901255', 56000, 4, '2', 1500, 0, 'BSN', 86, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(17, 'OLFU2024-647', 'Matthew Davis', 'matthew.davis@student.fatima.edu.ph', NULL, 'none', '9789012366', 33500, 1, '2', 1000, 0, 'BS IT', 96, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(18, 'OLFU2024-648', 'Fatima Hassan', 'fatima.hassan@student.fatima.edu.ph', NULL, 'fresh enrollment', '9890123477', 74000, 1, '2', 2300, 0, 'MLS', 87, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(19, 'OLFU2024-649', 'Andrew Miller', 'andrew.miller@student.fatima.edu.ph', NULL, 'new enroll', '9901234588', 59000, 4, '2', 3500, 0, 'BS IHM HACLO', 90, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(20, 'OLFU2024-650', 'Lucia Rossi', 'lucia.rossi@student.fatima.edu.ph', NULL, 'new student from central branch', '9012345699', 72000, 3, '2', 10000, 0, 'BS CE', 84, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(21, 'OLFU2024-651', 'Benjamin Garcia', 'benjamin.garcia@student.fatima.edu.ph', NULL, 'central student', '9123456710', 46000, 1, '2', 800, 0, 'BSN', 95, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(22, 'OLFU2024-652', 'Noor Ahmed', 'noor.ahmed@student.fatima.edu.ph', NULL, 'new enroll', '9234567812', 78000, 4, '2', 16000, 0, 'BSN', 93, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(23, 'OLFU2024-653', 'Joshua Taylor', 'joshua.taylor@student.fatima.edu.ph', NULL, 'new student from central branch', '9345678923', 39000, 1, '2', 2200, 0, 'BS PHARMA', 97, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(24, 'OLFU2024-654', 'Aisha Williams', 'aisha.williams@student.fatima.edu.ph', NULL, 'new enroll', '9456789034', 71500, 1, '2', 3000, 0, 'BS PHARMA', 88, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(25, 'OLFU2024-655', 'Ryan Anderson', 'ryan.anderson@student.fatima.edu.ph', NULL, 'new student from central branch', '9567890145', 50000, 4, '2', 7500, 0, 'BSN', 92, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(26, 'OLFU2024-656', 'Keiko Yamamoto', 'keiko.yamamoto@student.fatima.edu.ph', NULL, 'central student', '9678901256', 60000, 3, '2', 9400, 0, 'BS ACC', 86, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(27, 'OLFU2024-657', 'Nathan White', 'nathan.white@student.fatima.edu.ph', NULL, 'new enroll', '9789012367', 75000, 1, '2', 11000, 0, 'BSN', 91, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(28, 'OLFU2024-658', 'Carmen Delgado', 'carmen.delgado@student.fatima.edu.ph', NULL, 'new student from central branch', '9890123478', 34000, 4, '2', 2000, 0, 'BSA', 89, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(29, 'OLFU2024-659', 'Justin Moore', 'justin.moore@student.fatima.edu.ph', NULL, 'central student', '9901234589', 47000, 1, '2', 1500, 0, 'BSN', 90, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(30, 'OLFU2024-660', 'Indira Sharma', 'indira.sharma@student.fatima.edu.ph', NULL, 'none', '9012345600', 66000, 1, '2', 3500, 0, 'BS PHARMA', 87, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(31, 'OLFU2024-661', 'Kevin Jackson', 'kevin.jackson@student.fatima.edu.ph', NULL, 'fresh enrollment', '9123456711', 70000, 4, '2', 9800, 0, 'BS SE', 94, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(32, 'OLFU2024-662', 'Olga Petrov', 'olga.petrov@student.fatima.edu.ph', NULL, 'central student', '9234567813', 35500, 3, '2', 1200, 0, 'BS CS', 85, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(33, 'OLFU2024-663', 'Tyler Martin', 'tyler.martin@student.fatima.edu.ph', NULL, 'none', '9345678924', 62500, 1, '2', 8500, 0, 'BS CE', 96, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(34, 'OLFU2024-664', 'Aaliyah Cooper', 'aaliyah.cooper@student.fatima.edu.ph', NULL, 'fresh enrollment', '9456789035', 41500, 4, '2', 3000, 0, 'BSN', 84, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(35, 'OLFU2024-665', 'Brandon Thompson', 'brandon.thompson@student.fatima.edu.ph', NULL, 'new enroll', '9567890146', 67000, 1, '2', 4200, 0, 'BS PHARMA', 95, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(36, 'OLFU2024-666', 'Mei Zhang', 'mei.zhang@student.fatima.edu.ph', NULL, 'new student from central branch', '9678901257', 33000, 1, '2', 1000, 0, 'BS SE', 86, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(37, 'OLFU2024-667', 'Samuel Harris', 'samuel.harris@student.fatima.edu.ph', NULL, 'central student', '9789012368', 58000, 3, '2', 8000, 0, 'BS CS', 92, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(38, 'OLFU2024-668', 'Nadia Okafor', 'nadia.okafor@student.fatima.edu.ph', NULL, 'new enroll', '9890123479', 53000, 1, '2', 2500, 0, 'BS CE', 89, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(39, 'OLFU2024-669', 'Jacob Clark', 'jacob.clark@student.fatima.edu.ph', NULL, 'central student', '9901234590', 74500, 4, '2', 6700, 0, 'BS ITMTTO', 90, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(40, 'OLFU2024-670', 'Sienna Murphy', 'sienna.murphy@student.fatima.edu.ph', NULL, 'none', '9012345601', 42000, 1, '2', 1400, 0, 'BS ITMTTO', 93, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(41, 'OLFU2024-671', 'Anthony Lewis', 'anthony.lewis@student.fatima.edu.ph', NULL, 'fresh enrollment', '9123456712', 36000, 1, '2', 900, 0, 'BS CE', 84, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(42, 'OLFU2024-672', 'Ling Wu', 'ling.wu@student.fatima.edu.ph', NULL, 'new enroll', '9234567814', 68500, 4, '2', 5500, 0, 'BSA', 88, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(43, 'OLFU2024-673', 'Noah Walker', 'noah.walker@student.fatima.edu.ph', NULL, 'new student from central branch', '9345678925', 41000, 3, '2', 1800, 0, 'BSA', 96, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(44, 'OLFU2024-674', 'Valentina Santos', 'valentina.santos@student.fatima.edu.ph', NULL, 'central student', '9456789036', 49000, 1, '2', 2700, 0, 'BS TM', 85, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(45, 'OLFU2024-675', 'Ethan Young', 'ethan.young@student.fatima.edu.ph', NULL, 'new enroll', '9567890147', 77000, 4, '2', 7000, 0, 'BS HACLO', 94, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(46, 'OLFU2024-676', 'Rashida Khan', 'rashida.khan@student.fatima.edu.ph', NULL, 'fresh enrollment', '9678901258', 37500, 3, '2', 1200, 0, 'BSA', 91, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(47, 'OLFU2024-677', 'Lucas Allen', 'lucas.allen@student.fatima.edu.ph', NULL, 'central student', '9789012369', 55000, 1, '2', 4500, 0, 'BSA', 87, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(48, 'OLFU2024-678', 'Bianca Ferrari', 'bianca.ferrari@student.fatima.edu.ph', NULL, 'none', '9890123480', 70000, 4, '2', 8200, 0, 'BS CE', 95, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(49, 'OLFU2024-679', 'Isaac King', 'isaac.king@student.fatima.edu.ph', NULL, 'fresh enrollment', '9901234591', 43000, 1, '2', 3000, 0, 'BS CRIM', 97, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(50, 'OLFU2024-680', 'Sakura Nakamura', 'sakura.nakamura@student.fatima.edu.ph', NULL, 'new enroll', '9012345602', 39500, 1, '2', 1800, 0, 'BS CE', 89, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(51, 'OLFU2024-681', 'Gabriel Wright', 'gabriel.wright@student.fatima.edu.ph', NULL, 'new student from central branch', '9123456713', 54000, 4, '2', 4000, 0, 'BSN', 86, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(52, 'OLFU2024-682', 'Zoe Campbell', 'zoe.campbell@student.fatima.edu.ph', NULL, 'central student', '9234567815', 33500, 3, '2', 1300, 0, 'MLS', 84, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(53, 'OLFU2024-683', 'Caleb Green', 'caleb.green@student.fatima.edu.ph', NULL, 'fresh enrollment', '9345678926', 66500, 1, '2', 7000, 0, 'BS IT', 93, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(54, 'OLFU2024-684', 'Amina Diallo', 'amina.diallo@student.fatima.edu.ph', NULL, 'central student', '9456789037', 52000, 4, '2', 4500, 0, 'BS ITMTTO', 90, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(55, 'OLFU2024-685', 'Owen Baker', 'owen.baker@student.fatima.edu.ph', NULL, 'none', '9567890148', 34500, 3, '2', 1600, 0, 'BSN', 92, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(56, 'OLFU2024-686', 'Freya Nielsen', 'freya.nielsen@student.fatima.edu.ph', NULL, 'fresh enrollment', '9678901259', 62000, 1, '2', 2000, 0, 'BS CE', 88, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(57, 'OLFU2024-687', 'Henry Adams', 'henry.adams@student.fatima.edu.ph', NULL, 'new enroll', '9789012370', 78000, 4, '2', 6500, 0, 'BSN', 85, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(58, 'OLFU2024-688', 'Camila Vargas', 'camila.vargas@student.fatima.edu.ph', NULL, 'fresh enrollment', '9890123481', 40000, 3, '2', 2100, 0, 'BS CE', 94, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(59, 'OLFU2024-689', 'Ian Nelson', 'ian.nelson@student.fatima.edu.ph', NULL, 'central student', '9901234592', 51500, 1, '2', 2900, 0, 'BSA', 96, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(60, 'OLFU2024-690', 'Ravi Gupta', 'ravi.gupta@student.fatima.edu.ph', NULL, 'none', '9012345603', 46000, 4, '2', 1700, 0, 'BSA', 91, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(61, 'OLFU2024-691', 'Jack Mitchell', 'jack.mitchell@student.fatima.edu.ph', NULL, 'fresh enrollment', '9123456714', 35000, 1, '2', 2800, 0, 'BS TM', 89, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(62, 'OLFU2024-692', 'Layla Ozkan', 'layla.ozkan@student.fatima.edu.ph', NULL, 'new enroll', '9234567816', 71000, 1, '2', 3000, 0, 'BS HACLO', 84, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(63, 'OLFU2024-693', 'Leo Roberts', 'leo.roberts@student.fatima.edu.ph', NULL, 'fresh enrollment', '9345678927', 63000, 4, '2', 7000, 0, 'BS CE', 87, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(64, 'OLFU2024-694', 'Sasha Ivanova', 'sasha.ivanova@student.fatima.edu.ph', NULL, 'central student', '9456789038', 57000, 3, '2', 6200, 0, 'BS CRIM', 90, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(65, 'OLFU2024-695', 'Miles Turner', 'miles.turner@student.fatima.edu.ph', NULL, 'none', '9567890149', 56000, 3, '2', 4500, 0, 'BS CE', 95, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(66, 'OLFU2024-696', 'Aria Kowalski', 'aria.kowalski@student.fatima.edu.ph', NULL, 'fresh enrollment', '9678901260', 48000, 1, '2', 3000, 0, 'BSN', 88, 2, '2025-11-16 13:13:57', 7, 'testuser1'),
(67, 'OLFU2024-697', 'Finn Phillips', 'finn.phillips@student.fatima.edu.ph', NULL, 'new enroll', '9789012371', 39000, 4, '2', 1200, 0, 'MLS', 97, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(68, 'OLFU2024-698', 'Emilia Reyes', 'emilia.reyes@student.fatima.edu.ph', NULL, 'fresh enrollment', '9890123482', 60000, 3, '2', 5600, 0, 'BS IT', 92, 3, '2025-11-16 13:13:57', 7, 'testuser1'),
(69, 'OLFU2024-699', 'Cole Evans', 'cole.evans@student.fatima.edu.ph', NULL, 'new enroll', '9901234593', 52000, 1, '2', 4600, 0, 'BS ITMTTO', 93, 1, '2025-11-16 13:13:57', 7, 'testuser1'),
(70, 'OLFU2024-700', 'Tara OBrien', 'tara.obrien@student.fatima.edu.ph', NULL, 'fresh enrollment', '9012345604', 34000, 4, '2', 2000, 0, 'BSN', 86, 1, '2025-11-16 13:13:57', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_2025_sem_1`
--

CREATE TABLE `student_2025_sem_1` (
  `id` int(11) NOT NULL,
  `StudentID` varchar(255) DEFAULT NULL,
  `sname` varchar(100) DEFAULT NULL,
  `emailid` varchar(100) DEFAULT NULL,
  `joindate` datetime DEFAULT NULL,
  `about` text DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `fees` float DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `balance` float DEFAULT NULL,
  `delete_status` tinyint(4) DEFAULT 0,
  `course` varchar(100) DEFAULT NULL,
  `Attendance` float DEFAULT NULL,
  `GPA` float DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_2025_sem_1`
--

INSERT INTO `student_2025_sem_1` (`id`, `StudentID`, `sname`, `emailid`, `joindate`, `about`, `contact`, `fees`, `year`, `semester`, `balance`, `delete_status`, `course`, `Attendance`, `GPA`, `upload_date`, `uploaded_by_user_id`, `uploaded_by_username`) VALUES
(1, 'OLFU2025-701', 'Ethan Cruz', 'ethancruz@student.fatima.edu.ph', NULL, 'none', '9123456789', 62000, 3, '1', 34000, 0, 'BS ACC', 82, 3.27, '2025-11-18 05:06:32', 7, 'testuser1'),
(2, 'OLFU2025-702', 'Sophia Reyes', 'sophiareyes@student.fatima.edu.ph', NULL, 'new enroll', '9156784321', 60000, 1, '1', 40000, 0, 'BSN', 91, 1.89, '2025-11-18 05:06:32', 7, 'testuser1'),
(3, 'OLFU2025-703', 'Liam Santos', 'liamsantos@student.fatima.edu.ph', NULL, 'new student from central branch', '9178901234', 72000, 4, '1', 52600, 0, 'BSA', 78, 2.45, '2025-11-18 05:06:32', 7, 'testuser1'),
(4, 'OLFU2025-704', 'Isabella Dela Cruz', 'isabelladelacruz@student.fatima.edu.ph', NULL, 'fresh enrollment', '9194567890', 70000, 2, '1', 25440, 0, 'BSN', 94, 4.12, '2025-11-18 05:06:32', 7, 'testuser1'),
(5, 'OLFU2025-705', 'Noah Garcia', 'noahgarcia@student.fatima.edu.ph', NULL, 'fresh enrollment', '9212345678', 65000, 2, '1', 20000, 0, 'BS PHARMA', 80, 1.76, '2025-11-18 05:06:32', 7, 'testuser1'),
(6, 'OLFU2025-706', 'Mia Torres', 'miatorres@student.fatima.edu.ph', NULL, 'new enroll', '9238765432', 40000, 3, '1', 50000, 0, 'BS SE', 77, 3.54, '2025-11-18 05:06:32', 7, 'testuser1'),
(7, 'OLFU2025-707', 'Lucas Mendoza', 'lucasmendoza@student.fatima.edu.ph', NULL, 'new student from central branch', '9253456789', 39000, 1, '1', 19000, 0, 'BS CS', 85, 2.98, '2025-11-18 05:06:32', 7, 'testuser1'),
(8, 'OLFU2025-708', 'Ava Ramirez', 'avaramirez@student.fatima.edu.ph', NULL, 'central student', '9275678901', 40000, 4, '1', 4000, 0, 'BS CE', 90, 1.34, '2025-11-18 05:06:32', 7, 'testuser1'),
(9, 'OLFU2025-709', 'Elijah Villanueva', 'elijahvillanueva@student.fatima.edu.ph', NULL, 'none', '9289012345', 60000, 1, '1', 48000, 0, 'BSN', 83, 4.67, '2025-11-18 05:06:32', 7, 'testuser1'),
(10, 'OLFU2025-710', 'Camila Fernandez', 'camilafernandez@student.fatima.edu.ph', NULL, 'new enroll', '9296781234', 65000, 2, '1', 20900, 0, 'BS PHARMA', 88, 2.11, '2025-11-18 05:06:32', 7, 'testuser1'),
(11, 'OLFU2025-711', 'Mateo Lopez', 'mateolopez@student.fatima.edu.ph', NULL, 'new student from central branch', '9304567891', 40000, 3, '1', 21222, 0, 'BS SE', 79, 3.88, '2025-11-18 05:06:32', 7, 'testuser1'),
(12, 'OLFU2025-712', 'Aria Navarro', 'arianavarro@student.fatima.edu.ph', NULL, 'central student', '9312345670', 45000, 4, '1', 32111, 0, 'BS CS', 93, 1.57, '2025-11-18 05:06:32', 7, 'testuser1'),
(13, 'OLFU2025-713', 'Daniel Gonzales', 'danielgonzales@student.fatima.edu.ph', NULL, 'fresh enrollment', '9329876543', 60000, 2, '1', 32000, 0, 'BS CE', 86, 2.69, '2025-11-18 05:06:32', 7, 'testuser1'),
(14, 'OLFU2025-714', 'Chloe Aquino', 'chloeaquino@student.fatima.edu.ph', NULL, 'central student', '9338765123', 38000, 1, '1', 12000, 0, 'BS ITMTTO', 92, 4.45, '2025-11-18 05:06:32', 7, 'testuser1'),
(15, 'OLFU2025-715', 'James Bautista', 'jamesbautista@student.fatima.edu.ph', NULL, 'none', '9347654987', 55000, 3, '1', 9000, 0, 'BS HACLO', 84, 3.02, '2025-11-18 05:06:32', 7, 'testuser1'),
(16, 'OLFU2025-716', 'Harper Salazar', 'harpersalazar@student.fatima.edu.ph', NULL, 'fresh enrollment', '9353214567', 72000, 4, '1', 23689, 0, 'BSA', 89, 1.23, '2025-11-18 05:06:32', 7, 'testuser1'),
(17, 'OLFU2025-717', 'Benjamin Ramos', 'benjaminramos@student.fatima.edu.ph', NULL, 'new enroll', '9366789054', 72000, 4, '1', 24000, 0, 'BSA', 87, 2.87, '2025-11-18 05:06:32', 7, 'testuser1'),
(18, 'OLFU2025-718', 'Amelia Castillo', 'ameliacastillo@student.fatima.edu.ph', NULL, 'new student from central branch', '9378904321', 60000, 2, '1', 45000, 0, 'BS CE', 81, 4.09, '2025-11-18 05:06:32', 7, 'testuser1'),
(19, 'OLFU2025-719', 'Alexander Domingo', 'alexanderdomingo@student.fatima.edu.ph', NULL, 'central student', '9384567892', 40000, 1, '1', 10000, 0, 'BS CRIM', 77, 1.65, '2025-11-18 05:06:32', 7, 'testuser1'),
(20, 'OLFU2025-720', 'Scarlett Jimenez', 'scarlettjimenez@student.fatima.edu.ph', NULL, 'new enroll', '9392345678', 70000, 3, '1', 15000, 0, 'BS CE', 82, 3.76, '2025-11-18 05:06:32', 7, 'testuser1'),
(21, 'OLFU2025-721', 'Michael Alvarez', 'michaelalvarez@student.fatima.edu.ph', NULL, 'new student from central branch', '9409876541', 60000, 1, '1', 10000, 0, 'BSN', 88, 2.34, '2025-11-18 05:06:32', 7, 'testuser1'),
(22, 'OLFU2025-722', 'Victoria Morales', 'victoriamorales@student.fatima.edu.ph', NULL, 'new enroll', '9418765430', 40000, 4, '1', 5500, 0, 'MLS', 90, 4.56, '2025-11-18 05:06:32', 7, 'testuser1'),
(23, 'OLFU2025-723', 'Jacob Estrada', 'jacobestrada@student.fatima.edu.ph', NULL, 'new enroll', '9427654329', 47000, 2, '1', 32000, 0, 'BS IT', 79, 1.92, '2025-11-18 05:06:32', 7, 'testuser1'),
(24, 'OLFU2025-724', 'Penelope Abad', 'penelopeabad@student.fatima.edu.ph', NULL, 'new student from central branch', '9436543218', 54000, 3, '1', 6000, 0, 'BS ITMTTO', 94, 3.41, '2025-11-18 05:06:32', 7, 'testuser1'),
(25, 'OLFU2025-725', 'David Rivera', 'davidrivera@student.fatima.edu.ph', NULL, 'central student', '9445432107', 60000, 1, '1', 10500, 0, 'BSN', 85, 2.77, '2025-11-18 05:06:32', 7, 'testuser1');

-- --------------------------------------------------------

--
-- Table structure for table `student_predictions`
--

CREATE TABLE `student_predictions` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course` varchar(50) DEFAULT NULL,
  `current_year` int(11) DEFAULT NULL,
  `source_table` varchar(100) DEFAULT NULL,
  `prediction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `current_semester_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`current_semester_data`)),
  `next_semester_prediction` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`next_semester_prediction`)),
  `risk_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_analysis`)),
  `interventions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`interventions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_predictions`
--

INSERT INTO `student_predictions` (`id`, `student_id`, `course`, `current_year`, `source_table`, `prediction_date`, `current_semester_data`, `next_semester_prediction`, `risk_analysis`, `interventions`, `created_at`) VALUES
(3786, 'OLFU2020-001', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 87.0, \"gpa\": 4.0, \"balance\": 10000.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26.51}', '{\"predicted_attendance\": 86.15, \"predicted_gpa\": 4.92, \"predicted_balance\": 43586.46, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 91.89}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 65.38, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\", \"admin_action\": \"Schedule academic intervention.\"}]', '2026-05-15 06:19:10'),
(3787, 'OLFU2020-002', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 92.0, \"gpa\": 1.0, \"balance\": 10800.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 15.84}', '{\"predicted_attendance\": 99.69, \"predicted_gpa\": 1.0, \"predicted_balance\": 56882.0, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 58.97}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 43.13, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Good attendance record\", \"solution\": \"Maintain good attendance.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\"}]', '2026-05-15 06:19:10'),
(3788, 'OLFU2020-003', 'MLS', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 78.0, \"gpa\": 3.0, \"balance\": 15200.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 66.8}', '{\"predicted_attendance\": 73.02, \"predicted_gpa\": 3.25, \"predicted_balance\": 62685.46, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Stable\", \"probability_change\": 28.2, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Desired program is not available.\", \"solution\": \"Explore similar programs or transfer options.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3789, 'OLFU2020-004', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 85.0, \"gpa\": 2.0, \"balance\": 9800.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 28.88}', '{\"predicted_attendance\": 70.83, \"predicted_gpa\": 2.38, \"predicted_balance\": 36571.11, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 66.12, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3790, 'OLFU2020-005', 'BS CE', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 91.0, \"gpa\": 3.0, \"balance\": 18600.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 69.91}', '{\"predicted_attendance\": 85.27, \"predicted_gpa\": 2.73, \"predicted_balance\": 56604.46, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 65}', '{\"risk_change\": \"Improving (1 level down)\", \"probability_change\": -4.91, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Health issues affecting attendance and performance.\", \"solution\": \"Consult school health services.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3791, 'OLFU2020-006', 'BSA', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 76.0, \"gpa\": 1.0, \"balance\": 17800.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 67.33}', '{\"predicted_attendance\": 73.37, \"predicted_gpa\": 1.21, \"predicted_balance\": 114497.12, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Stable\", \"probability_change\": 27.67, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Desired program is not available.\", \"solution\": \"Explore similar programs or transfer options.\"}]', '2026-05-15 06:19:10'),
(3792, 'OLFU2020-007', 'MLS', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 89.0, \"gpa\": 4.0, \"balance\": 14500.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 67.49}', '{\"predicted_attendance\": 80.23, \"predicted_gpa\": 3.67, \"predicted_balance\": 43310.43, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Stable\", \"probability_change\": 27.51, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3793, 'OLFU2020-008', 'BS PHARMA', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 83.0, \"gpa\": 2.0, \"balance\": 16200.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 27.51}', '{\"predicted_attendance\": 80.86, \"predicted_gpa\": 1.85, \"predicted_balance\": 54277.25, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 65}', '{\"risk_change\": \"Stable\", \"probability_change\": 37.49, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\"}]', '2026-05-15 06:19:10'),
(3794, 'OLFU2020-009', 'BSITM TTO', 1, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 95.0, \"gpa\": 2.0, \"balance\": 5500.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 15.04}', '{\"predicted_attendance\": 78.66, \"predicted_gpa\": 2.13, \"predicted_balance\": 45422.68, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 59.01}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 43.97, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Health issues affecting attendance and performance.\", \"solution\": \"Consult school health services.\"}]', '2026-05-15 06:19:10'),
(3795, 'OLFU2020-010', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 74.0, \"gpa\": 3.0, \"balance\": 14200.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 66.9}', '{\"predicted_attendance\": 69.32, \"predicted_gpa\": 2.84, \"predicted_balance\": 61036.53, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Stable\", \"probability_change\": 28.1, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Health issues affecting attendance and performance.\", \"solution\": \"Consult school health services.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3796, 'OLFU2020-011', 'BSN', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 88.0, \"gpa\": 4.0, \"balance\": 7800.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 84.07, \"predicted_gpa\": 4.14, \"predicted_balance\": 66392.15, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 94.67}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 68.67, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\", \"admin_action\": \"Schedule academic intervention.\"}]', '2026-05-15 06:19:10'),
(3797, 'OLFU2020-012', 'MLS', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 82.0, \"gpa\": 1.0, \"balance\": 9200.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 29.08}', '{\"predicted_attendance\": 77.62, \"predicted_gpa\": 1.0, \"predicted_balance\": 57636.3, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 65}', '{\"risk_change\": \"Stable\", \"probability_change\": 35.92, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\"}]', '2026-05-15 06:19:10'),
(3798, 'OLFU2020-013', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 90.0, \"gpa\": 2.0, \"balance\": 17900.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 28.51}', '{\"predicted_attendance\": 81.87, \"predicted_gpa\": 2.32, \"predicted_balance\": 39901.2, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 53.79}', '{\"risk_change\": \"Stable\", \"probability_change\": 25.28, \"intervention_urgency\": \"MEDIUM\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3799, 'OLFU2020-014', 'BS MEDTECH', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 77.0, \"gpa\": 2.0, \"balance\": 16500.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 70.03}', '{\"predicted_attendance\": 55.44, \"predicted_gpa\": 1.48, \"predicted_balance\": 45973.7, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Stable\", \"probability_change\": 24.97, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\"}]', '2026-05-15 06:19:10'),
(3800, 'OLFU2020-015', 'BS CE', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 86.0, \"gpa\": 3.0, \"balance\": 8900.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 71.7, \"predicted_gpa\": 3.23, \"predicted_balance\": 41731.4, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 93.95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 67.95, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3801, 'OLFU2020-016', 'BSN', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 93.0, \"gpa\": 1.0, \"balance\": 13800.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 13.53}', '{\"predicted_attendance\": 77.89, \"predicted_gpa\": 1.0, \"predicted_balance\": 82635.23, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 54.41}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 40.88, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\"}]', '2026-05-15 06:19:10'),
(3802, 'OLFU2020-017', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 79.0, \"gpa\": 4.0, \"balance\": 11600.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 31.67}', '{\"predicted_attendance\": 71.52, \"predicted_gpa\": 4.7, \"predicted_balance\": 36188.09, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 63.33, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\", \"admin_action\": \"Schedule academic intervention.\"}]', '2026-05-15 06:19:10'),
(3803, 'OLFU2020-018', 'BS CE', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 84.0, \"gpa\": 3.0, \"balance\": 12800.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 66}', '{\"predicted_attendance\": 78.02, \"predicted_gpa\": 2.3, \"predicted_balance\": 73568.8, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 61.03}', '{\"risk_change\": \"Improving (1 level down)\", \"probability_change\": -4.97, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Desired program is not available.\", \"solution\": \"Explore similar programs or transfer options.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3804, 'OLFU2020-019', 'BSN', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 88.0, \"gpa\": 2.0, \"balance\": 16200.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 19.87}', '{\"predicted_attendance\": 78.37, \"predicted_gpa\": 2.25, \"predicted_balance\": 72667.74, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 52.88}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 33.01, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Health issues affecting attendance and performance.\", \"solution\": \"Consult school health services.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3805, 'OLFU2020-020', 'BS HACLO', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 75.0, \"gpa\": 3.0, \"balance\": 14800.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 66}', '{\"predicted_attendance\": 71.59, \"predicted_gpa\": 3.51, \"predicted_balance\": 41262.7, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 93.66}', '{\"risk_change\": \"Stable\", \"probability_change\": 27.66, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3806, 'OLFU2020-021', 'BS HACLO', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 91.0, \"gpa\": 4.0, \"balance\": 10900.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 31.38}', '{\"predicted_attendance\": 99.67, \"predicted_gpa\": 3.88, \"predicted_balance\": 53763.08, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 63.62, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Good attendance record\", \"solution\": \"Maintain good attendance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\"}]', '2026-05-15 06:19:10'),
(3807, 'OLFU2020-022', 'BS HACLO', 1, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 87.0, \"gpa\": 1.0, \"balance\": 15600.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 57.33, \"predicted_gpa\": 1.13, \"predicted_balance\": 40660.12, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 93.81}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 67.81, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\"}]', '2026-05-15 06:19:10'),
(3808, 'OLFU2020-023', 'BSN', 1, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 94.0, \"gpa\": 2.0, \"balance\": 4200.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 10.69}', '{\"predicted_attendance\": 80.88, \"predicted_gpa\": 2.3, \"predicted_balance\": 92385.44, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 62.78}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 52.09, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3809, 'OLFU2020-024', 'BSIT', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 81.0, \"gpa\": 2.0, \"balance\": 16800.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 28.68}', '{\"predicted_attendance\": 74.43, \"predicted_gpa\": 1.98, \"predicted_balance\": 80419.47, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 66.32, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Desired program is not available.\", \"solution\": \"Explore similar programs or transfer options.\"}]', '2026-05-15 06:19:10'),
(3810, 'OLFU2020-025', 'HUMSS', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 85.0, \"gpa\": 4.0, \"balance\": 3200.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 84.24, \"predicted_gpa\": 3.86, \"predicted_balance\": 44593.29, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 94.54}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 68.54, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3811, 'OLFU2020-026', 'BS CE', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 78.0, \"gpa\": 1.0, \"balance\": 10500.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 64.95, \"predicted_gpa\": 1.0, \"predicted_balance\": 36452.46, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 91.06}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 65.06, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\"}]', '2026-05-15 06:19:10'),
(3812, 'OLFU2020-027', 'BSA', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 92.0, \"gpa\": 3.0, \"balance\": 17200.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 27.69}', '{\"predicted_attendance\": 86.65, \"predicted_gpa\": 2.69, \"predicted_balance\": 98449.84, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 65}', '{\"risk_change\": \"Stable\", \"probability_change\": 37.31, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3813, 'OLFU2020-028', 'BS ITM TTO', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 86.0, \"gpa\": 2.0, \"balance\": 15900.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26.95}', '{\"predicted_attendance\": 61.46, \"predicted_gpa\": 1.7, \"predicted_balance\": 30337.91, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 90.34}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 63.39, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\"}]', '2026-05-15 06:19:10'),
(3814, 'OLFU2020-029', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 74.0, \"gpa\": 3.0, \"balance\": 11400.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 66}', '{\"predicted_attendance\": 83.86, \"predicted_gpa\": 3.46, \"predicted_balance\": 51381.9, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 91.35}', '{\"risk_change\": \"Stable\", \"probability_change\": 25.35, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3815, 'OLFU2020-030', 'BSN', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 89.0, \"gpa\": 1.0, \"balance\": 17500.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 17.74}', '{\"predicted_attendance\": 74.53, \"predicted_gpa\": 1.01, \"predicted_balance\": 76739.57, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (2 levels up)\", \"probability_change\": 77.26, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\"}]', '2026-05-15 06:19:10'),
(3816, 'OLFU2020-031', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 83.0, \"gpa\": 4.0, \"balance\": 10800.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 31.58}', '{\"predicted_attendance\": 81.78, \"predicted_gpa\": 4.51, \"predicted_balance\": 57417.77, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 63.42, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\", \"admin_action\": \"Schedule academic intervention.\"}]', '2026-05-15 06:19:10'),
(3817, 'OLFU2020-032', 'BSN', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 95.0, \"gpa\": 2.0, \"balance\": 16800.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 19.15}', '{\"predicted_attendance\": 80.65, \"predicted_gpa\": 1.84, \"predicted_balance\": 66929.17, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 50.71}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 31.56, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\"}]', '2026-05-15 06:19:10'),
(3818, 'OLFU2020-033', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 80.0, \"gpa\": 2.0, \"balance\": 14700.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 75.37, \"predicted_gpa\": 1.91, \"predicted_balance\": 60399.98, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 91.62}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 65.62, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\"}]', '2026-05-15 06:19:10'),
(3819, 'OLFU2020-034', 'BSN', 1, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 87.0, \"gpa\": 3.0, \"balance\": 17100.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 28.94}', '{\"predicted_attendance\": 85.54, \"predicted_gpa\": 2.72, \"predicted_balance\": 107551.19, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 65}', '{\"risk_change\": \"Stable\", \"probability_change\": 36.06, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3820, 'OLFU2020-035', 'BS PHARMA', 1, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 76.0, \"gpa\": 4.0, \"balance\": 18400.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 82.96}', '{\"predicted_attendance\": 67.11, \"predicted_gpa\": 4.99, \"predicted_balance\": 53836.79, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Stable\", \"probability_change\": 12.04, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\", \"admin_action\": \"Schedule academic intervention.\"}]', '2026-05-15 06:19:10'),
(3821, 'OLFU2020-036', 'BS PSYCH', 1, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 90.0, \"gpa\": 1.0, \"balance\": 16000.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 28.73}', '{\"predicted_attendance\": 62.26, \"predicted_gpa\": 1.17, \"predicted_balance\": 45468.08, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 66.27, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\"}]', '2026-05-15 06:19:10'),
(3822, 'OLFU2020-037', 'BS BIO', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 84.0, \"gpa\": 2.0, \"balance\": 15200.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 29.98}', '{\"predicted_attendance\": 76.61, \"predicted_gpa\": 1.96, \"predicted_balance\": 45647.58, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 65.02, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\"}]', '2026-05-15 06:19:10'),
(3823, 'OLFU2020-038', 'BSN', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 88.0, \"gpa\": 2.0, \"balance\": 9600.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 16.71}', '{\"predicted_attendance\": 80.06, \"predicted_gpa\": 1.56, \"predicted_balance\": 67709.33, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 51.38}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 34.67, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Desired program is not available.\", \"solution\": \"Explore similar programs or transfer options.\"}]', '2026-05-15 06:19:10'),
(3824, 'OLFU2020-039', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 82.0, \"gpa\": 3.0, \"balance\": 10200.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 28.67}', '{\"predicted_attendance\": 68.17, \"predicted_gpa\": 2.7, \"predicted_balance\": 37207.25, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 66.33, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10');
INSERT INTO `student_predictions` (`id`, `student_id`, `course`, `current_year`, `source_table`, `prediction_date`, `current_semester_data`, `next_semester_prediction`, `risk_analysis`, `interventions`, `created_at`) VALUES
(3825, 'OLFU2020-040', 'BS CS', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 93.0, \"gpa\": 1.0, \"balance\": 11200.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 17.93}', '{\"predicted_attendance\": 73.62, \"predicted_gpa\": 1.0, \"predicted_balance\": 58961.83, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (2 levels up)\", \"probability_change\": 77.07, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\"}]', '2026-05-15 06:19:10'),
(3826, 'OLFU2020-041', 'MLS', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 79.0, \"gpa\": 4.0, \"balance\": 16800.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 78.15}', '{\"predicted_attendance\": 70.93, \"predicted_gpa\": 3.98, \"predicted_balance\": 43172.5, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Stable\", \"probability_change\": 16.85, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3827, 'OLFU2020-042', 'BSN', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 85.0, \"gpa\": 3.0, \"balance\": 10600.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 28.59}', '{\"predicted_attendance\": 75.91, \"predicted_gpa\": 3.54, \"predicted_balance\": 56495.45, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 66.41, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3828, 'OLFU2020-043', 'BSITM TTO', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 91.0, \"gpa\": 2.0, \"balance\": 5800.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 17.67}', '{\"predicted_attendance\": 87.01, \"predicted_gpa\": 2.07, \"predicted_balance\": 50656.32, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 65}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 47.33, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Health issues affecting attendance and performance.\", \"solution\": \"Consult school health services.\"}]', '2026-05-15 06:19:10'),
(3829, 'OLFU2020-044', 'BSN', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 77.0, \"gpa\": 3.0, \"balance\": 9400.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 66}', '{\"predicted_attendance\": 57.6, \"predicted_gpa\": 3.08, \"predicted_balance\": 59331.29, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 92.86}', '{\"risk_change\": \"Stable\", \"probability_change\": 26.86, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3830, 'OLFU2020-045', 'BS PSYCH', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 86.0, \"gpa\": 4.0, \"balance\": 17600.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 72.42}', '{\"predicted_attendance\": 85.39, \"predicted_gpa\": 2.71, \"predicted_balance\": 52569.94, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 64.38}', '{\"risk_change\": \"Improving (1 level down)\", \"probability_change\": -8.04, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Health issues affecting attendance and performance.\", \"solution\": \"Consult school health services.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3831, 'OLFU2020-046', 'BS PSYCH', 1, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 94.0, \"gpa\": 1.0, \"balance\": 8200.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 12.36}', '{\"predicted_attendance\": 60.01, \"predicted_gpa\": 1.0, \"predicted_balance\": 28074.91, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 78.05}', '{\"risk_change\": \"Escalating (2 levels up)\", \"probability_change\": 65.69, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\"}]', '2026-05-15 06:19:10'),
(3832, 'OLFU2020-047', 'BS HACLO', 1, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 81.0, \"gpa\": 2.0, \"balance\": 15100.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 66.54, \"predicted_gpa\": 2.11, \"predicted_balance\": 38718.99, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 92.57}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 66.57, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\"}]', '2026-05-15 06:19:10'),
(3833, 'OLFU2020-048', 'BSITM TTO', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 88.0, \"gpa\": 2.0, \"balance\": 7200.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 18.95}', '{\"predicted_attendance\": 94.98, \"predicted_gpa\": 1.79, \"predicted_balance\": 57920.65, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 65}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 46.05, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Good attendance record\", \"solution\": \"Maintain good attendance.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\"}]', '2026-05-15 06:19:10'),
(3834, 'OLFU2020-049', 'BS CE', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 75.0, \"gpa\": 4.0, \"balance\": 9800.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 70.2}', '{\"predicted_attendance\": 68.47, \"predicted_gpa\": 4.42, \"predicted_balance\": 38792.27, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Stable\", \"probability_change\": 24.8, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\", \"admin_action\": \"Schedule academic intervention.\"}]', '2026-05-15 06:19:10'),
(3835, 'OLFU2020-050', 'BS HACLO', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 92.0, \"gpa\": 1.0, \"balance\": 16400.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 76.45, \"predicted_gpa\": 1.0, \"predicted_balance\": 45688.4, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 90.62}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 64.62, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\"}]', '2026-05-15 06:19:10'),
(3836, 'OLFU2020-051', 'BS CE', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 87.0, \"gpa\": 3.0, \"balance\": 10300.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 77.33, \"predicted_gpa\": 3.22, \"predicted_balance\": 43729.21, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 93.79}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 67.79, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Desired program is not available.\", \"solution\": \"Explore similar programs or transfer options.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3837, 'OLFU2020-052', 'MLS', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 83.0, \"gpa\": 2.0, \"balance\": 11800.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 66.82, \"predicted_gpa\": 2.15, \"predicted_balance\": 43774.89, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 94.68}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 68.68, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\"}]', '2026-05-15 06:19:10'),
(3838, 'OLFU2020-053', 'BS CE', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 95.0, \"gpa\": 3.0, \"balance\": 7900.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 27.74}', '{\"predicted_attendance\": 97.72, \"predicted_gpa\": 3.11, \"predicted_balance\": 54772.81, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 67.26, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Good attendance record\", \"solution\": \"Maintain good attendance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\"}]', '2026-05-15 06:19:10'),
(3839, 'OLFU2020-054', 'MLS', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 78.0, \"gpa\": 1.0, \"balance\": 10400.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 28.67}', '{\"predicted_attendance\": 70.01, \"predicted_gpa\": 1.0, \"predicted_balance\": 47074.2, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 66.33, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\"}]', '2026-05-15 06:19:10'),
(3840, 'OLFU2020-055', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 89.0, \"gpa\": 4.0, \"balance\": 11200.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 27.04}', '{\"predicted_attendance\": 87.93, \"predicted_gpa\": 4.88, \"predicted_balance\": 63286.18, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 92.3}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 65.26, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Good attendance record\", \"solution\": \"Maintain good attendance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic intervention.\"}, {\"reason\": \"Desired program is not available.\", \"solution\": \"Explore similar programs or transfer options.\"}]', '2026-05-15 06:19:10'),
(3841, 'OLFU2020-056', 'MLS', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 74.0, \"gpa\": 2.0, \"balance\": 7600.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 86.56, \"predicted_gpa\": 1.8, \"predicted_balance\": 57735.24, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 62.41}', '{\"risk_change\": \"Stable\", \"probability_change\": 36.41, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\"}]', '2026-05-15 06:19:10'),
(3842, 'OLFU2020-057', 'MLS', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 86.0, \"gpa\": 2.0, \"balance\": 18000.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26.32}', '{\"predicted_attendance\": 83.98, \"predicted_gpa\": 2.26, \"predicted_balance\": 55792.35, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 62.32}', '{\"risk_change\": \"Stable\", \"probability_change\": 36.0, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Desired program is not available.\", \"solution\": \"Explore similar programs or transfer options.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3843, 'OLFU2020-058', 'BSIHM', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 90.0, \"gpa\": 3.0, \"balance\": 16700.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 70.63}', '{\"predicted_attendance\": 95.52, \"predicted_gpa\": 3.3, \"predicted_balance\": 38197.93, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 94.15}', '{\"risk_change\": \"Stable\", \"probability_change\": 23.52, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Good attendance record\", \"solution\": \"Maintain good attendance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\"}]', '2026-05-15 06:19:10'),
(3844, 'OLFU2020-059', 'BSECE', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 84.0, \"gpa\": 4.0, \"balance\": 15400.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 28.78}', '{\"predicted_attendance\": 76.84, \"predicted_gpa\": 5.0, \"predicted_balance\": 81210.77, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 66.22, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\", \"admin_action\": \"Schedule academic intervention.\"}]', '2026-05-15 06:19:10'),
(3845, 'OLFU2020-060', 'MLS', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 82.0, \"gpa\": 1.0, \"balance\": 16100.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26.44}', '{\"predicted_attendance\": 72.78, \"predicted_gpa\": 1.14, \"predicted_balance\": 37386.62, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 94.44}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 68.0, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Health issues affecting attendance and performance.\", \"solution\": \"Consult school health services.\"}]', '2026-05-15 06:19:10'),
(3846, 'OLFU2020-061', 'BSIT', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 93.0, \"gpa\": 2.0, \"balance\": 14900.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 20.99}', '{\"predicted_attendance\": 89.42, \"predicted_gpa\": 2.21, \"predicted_balance\": 75597.8, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 65}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 44.01, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Good attendance record\", \"solution\": \"Maintain good attendance.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\"}, {\"reason\": \"Health issues affecting attendance and performance.\", \"solution\": \"Consult school health services.\"}]', '2026-05-15 06:19:10'),
(3847, 'OLFU2020-062', 'BS PSYCH', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 79.0, \"gpa\": 2.0, \"balance\": 11600.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 29.2}', '{\"predicted_attendance\": 77.83, \"predicted_gpa\": 2.16, \"predicted_balance\": 42954.83, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 58.8}', '{\"risk_change\": \"Stable\", \"probability_change\": 29.6, \"intervention_urgency\": \"MEDIUM\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\"}]', '2026-05-15 06:19:10'),
(3848, 'OLFU2020-063', 'BS CE', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 85.0, \"gpa\": 3.0, \"balance\": 10500.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26.11}', '{\"predicted_attendance\": 75.79, \"predicted_gpa\": 3.25, \"predicted_balance\": 54737.09, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 68.89, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3849, 'OLFU2020-064', 'MLS', 4, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 91.0, \"gpa\": 1.0, \"balance\": 5800.0, \"risk_level\": \"Low Risk\", \"dropout_probability\": 13.68}', '{\"predicted_attendance\": 89.44, \"predicted_gpa\": 1.04, \"predicted_balance\": 37940.19, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 47.85}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 34.17, \"intervention_urgency\": \"MEDIUM\"}', '[{\"reason\": \"Good attendance record\", \"solution\": \"Maintain good attendance.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\"}]', '2026-05-15 06:19:10'),
(3850, 'OLFU2020-065', 'BS PHARMA', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 77.0, \"gpa\": 4.0, \"balance\": 17400.0, \"risk_level\": \"High Risk\", \"dropout_probability\": 72.18}', '{\"predicted_attendance\": 83.27, \"predicted_gpa\": 4.11, \"predicted_balance\": 41541.64, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 90.38}', '{\"risk_change\": \"Stable\", \"probability_change\": 18.2, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Class schedule conflicts with other responsibilities.\", \"solution\": \"Adjust course schedule if possible.\", \"admin_action\": \"Schedule academic intervention.\"}]', '2026-05-15 06:19:10'),
(3851, 'OLFU2020-066', 'BSA', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 88.0, \"gpa\": 3.0, \"balance\": 15800.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 79.01, \"predicted_gpa\": 2.59, \"predicted_balance\": 59924.05, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 49.8}', '{\"risk_change\": \"Stable\", \"probability_change\": 23.8, \"intervention_urgency\": \"MEDIUM\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Moderate academic performance\", \"solution\": \"Improve study habits and review lessons regularly.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\", \"admin_action\": \"Monitor academic progress.\"}]', '2026-05-15 06:19:10'),
(3852, 'OLFU2020-067', 'BS IHM HACLO', 3, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 94.0, \"gpa\": 2.0, \"balance\": 14600.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 86.49, \"predicted_gpa\": 1.83, \"predicted_balance\": 62188.88, \"predicted_risk_level\": \"Medium Risk\", \"predicted_dropout_probability\": 63.21}', '{\"risk_change\": \"Stable\", \"probability_change\": 37.21, \"intervention_urgency\": \"HIGH\"}', '[{\"reason\": \"Difficulties in commuting to school.\", \"solution\": \"Explore better transportation options or schedule adjustments.\", \"admin_action\": \"Monitor student and schedule counseling if needed.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Assess student\'s transportation challenges.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Student has transferred to another institution.\", \"solution\": \"Request for transfer documentation.\"}]', '2026-05-15 06:19:10'),
(3853, 'OLFU2020-068', 'BSN', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 81.0, \"gpa\": 3.0, \"balance\": 15200.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 29.2}', '{\"predicted_attendance\": 70.11, \"predicted_gpa\": 3.17, \"predicted_balance\": 67386.24, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 65.8, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Poor academic performance\", \"solution\": \"Attend review classes and get tutoring.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic counseling session.\"}, {\"reason\": \"Student decided to shift to another course.\", \"solution\": \"Consult academic advisor for proper transition.\", \"admin_action\": \"Call for financial consultation with accounting.\"}]', '2026-05-15 06:19:10'),
(3854, 'OLFU2020-069', 'BS CE', 1, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 87.0, \"gpa\": 4.0, \"balance\": 7600.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 27.87}', '{\"predicted_attendance\": 100, \"predicted_gpa\": 4.75, \"predicted_balance\": 62796.78, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 95}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 67.13, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Good attendance record\", \"solution\": \"Maintain good attendance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Struggling to cope with academic requirements.\", \"solution\": \"Seek immediate academic support or tutoring.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Schedule academic intervention.\"}, {\"reason\": \"Health issues affecting attendance and performance.\", \"solution\": \"Consult school health services.\"}]', '2026-05-15 06:19:10'),
(3855, 'OLFU2020-070', 'BS CE', 2, 'student_2020_sem_1', '2026-05-15 06:19:10', '{\"attendance\": 76.0, \"gpa\": 1.0, \"balance\": 8400.0, \"risk_level\": \"Medium Risk\", \"dropout_probability\": 26}', '{\"predicted_attendance\": 72.12, \"predicted_gpa\": 1.0, \"predicted_balance\": 37946.24, \"predicted_risk_level\": \"High Risk\", \"predicted_dropout_probability\": 94.63}', '{\"risk_change\": \"Escalating (1 level up)\", \"probability_change\": 68.63, \"intervention_urgency\": \"CRITICAL\"}', '[{\"reason\": \"Student has moved to a different location.\", \"solution\": \"Consider online classes or transferring to a nearby school.\", \"admin_action\": \"Provide guidance on transfer options.\"}, {\"reason\": \"Good academic standing\", \"solution\": \"Maintain good academic performance.\", \"admin_action\": \"Schedule parent/guardian meeting for intervention.\"}, {\"reason\": \"Financial constraints affecting tuition payment.\", \"solution\": \"Apply for financial aid or payment plan.\", \"admin_action\": \"Call for financial consultation with accounting.\"}, {\"reason\": \"Personal circumstances impacting studies.\", \"solution\": \"Seek personal counseling support.\"}]', '2026-05-15 06:19:10');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_description`, `updated_by`, `updated_date`) VALUES
(1, 'max_login_attempts', '5', 'Maximum login attempts before account lockout', NULL, '2025-08-08 03:20:52'),
(2, 'session_timeout', '3600', 'Session timeout in seconds', NULL, '2025-08-08 03:20:52'),
(3, 'enable_email_notifications', '1', 'Enable email notifications for uploads', NULL, '2025-08-08 03:20:52'),
(4, 'max_upload_size', '10485760', 'Maximum upload file size in bytes (10MB)', NULL, '2025-08-08 03:20:52'),
(5, 'allowed_file_types', 'csv', 'Allowed file types for upload', NULL, '2025-08-08 03:20:52'),
(6, 'backup_retention_days', '30', 'Number of days to retain backup files', NULL, '2025-08-08 03:20:52');

-- --------------------------------------------------------

--
-- Table structure for table `upload_logs`
--

CREATE TABLE `upload_logs` (
  `log_id` int(11) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `records_processed` int(11) DEFAULT 0,
  `records_success` int(11) DEFAULT 0,
  `records_error` int(11) DEFAULT 0,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `uploaded_by_username` varchar(100) DEFAULT NULL,
  `uploaded_by_name` varchar(255) DEFAULT NULL,
  `user_ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `content_warnings` text DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `processing_time` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upload_logs`
--

INSERT INTO `upload_logs` (`log_id`, `year`, `semester`, `table_name`, `filename`, `records_processed`, `records_success`, `records_error`, `upload_date`, `status`, `error_message`, `uploaded_by_user_id`, `uploaded_by_username`, `uploaded_by_name`, `user_ip_address`, `user_agent`, `content_warnings`, `file_size`, `processing_time`) VALUES
(206, 2020, '1', 'student_2020_sem_1', 'OLFU2020 1st sem.csv', 70, 70, 0, '2026-05-15 06:18:58', 'success', NULL, 7, 'testuser1', 'staff1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user',
  `status` varchar(20) DEFAULT 'active',
  `last_logout` timestamp NULL DEFAULT NULL,
  `status_updated_date` timestamp NULL DEFAULT NULL,
  `created_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `emailid` varchar(255) NOT NULL,
  `lastlogin` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `name`, `email`, `role`, `status`, `last_logout`, `status_updated_date`, `created_date`, `last_login`, `login_attempts`, `emailid`, `lastlogin`) VALUES
(2, 'admin', '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin@system.com', 'admin', 'active', '2025-11-16 13:45:29', '2025-11-17 16:14:57', '2025-08-08 03:21:31', NULL, 0, '', '0000-00-00 00:00:00'),
(7, 'testuser1', 'e4b4efd20ada72c6f7708b0c1cc78469', 'staff1', 'staff1@gmail.com', 'user', 'inactive', '2025-11-17 16:14:47', '2025-11-16 13:45:37', '2025-08-31 10:52:28', NULL, 0, '', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_login_logs`
--

CREATE TABLE `user_login_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_status` varchar(20) DEFAULT 'success',
  `additional_info` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_session_invalidations`
--

CREATE TABLE `user_session_invalidations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `invalidated_by_admin` varchar(50) NOT NULL,
  `invalidation_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_upload_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_upload_summary` (
`user_id` int(255)
,`username` varchar(255)
,`name` varchar(255)
,`email` varchar(100)
,`status` varchar(20)
,`last_logout` timestamp
,`total_uploads` bigint(21)
,`successful_uploads` decimal(22,0)
,`failed_uploads` decimal(22,0)
,`total_records_uploaded` decimal(32,0)
,`last_upload_date` timestamp
,`first_upload_date` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `year`
--

CREATE TABLE `year` (
  `id` int(255) NOT NULL,
  `year` varchar(10) DEFAULT NULL,
  `detail` text NOT NULL,
  `delete_status` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=COMPACT;

--
-- Dumping data for table `year`
--

INSERT INTO `year` (`id`, `year`, `detail`, `delete_status`) VALUES
(1, '1', 'This is a demo text', '0'),
(2, '2', 'This is a demo text', '0'),
(3, '3', 'This is a demo text', '0'),
(4, '4', 'This is a demo text', '0');

-- --------------------------------------------------------

--
-- Structure for view `admin_dashboard_stats`
--
DROP TABLE IF EXISTS `admin_dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `admin_dashboard_stats`  AS SELECT 'Total Users' AS `metric`, count(0) AS `value`, 'users' AS `category` FROM `user`union all select 'Active Users' AS `metric`,count(0) AS `value`,'users' AS `category` from `user` where `user`.`status` = 'active' union all select 'Inactive Users' AS `metric`,count(0) AS `value`,'users' AS `category` from `user` where `user`.`status` = 'inactive' union all select 'Total Uploads' AS `metric`,count(0) AS `value`,'uploads' AS `category` from `upload_logs` union all select 'Successful Uploads' AS `metric`,count(0) AS `value`,'uploads' AS `category` from `upload_logs` where `upload_logs`.`status` = 'success' union all select 'Failed Uploads' AS `metric`,count(0) AS `value`,'uploads' AS `category` from `upload_logs` where `upload_logs`.`status` = 'failed' union all select 'Total Records Processed' AS `metric`,sum(`upload_logs`.`records_success`) AS `value`,'records' AS `category` from `upload_logs` union all select 'Unique Uploaders' AS `metric`,count(distinct `upload_logs`.`uploaded_by_username`) AS `value`,'users' AS `category` from `upload_logs` where `upload_logs`.`uploaded_by_username` is not null  ;

-- --------------------------------------------------------

--
-- Structure for view `user_upload_summary`
--
DROP TABLE IF EXISTS `user_upload_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_upload_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, `u`.`name` AS `name`, `u`.`email` AS `email`, `u`.`status` AS `status`, `u`.`last_logout` AS `last_logout`, coalesce(`ul`.`total_uploads`,0) AS `total_uploads`, coalesce(`ul`.`successful_uploads`,0) AS `successful_uploads`, coalesce(`ul`.`failed_uploads`,0) AS `failed_uploads`, coalesce(`ul`.`total_records_uploaded`,0) AS `total_records_uploaded`, `ul`.`last_upload_date` AS `last_upload_date`, `ul`.`first_upload_date` AS `first_upload_date` FROM (`user` `u` left join (select `upload_logs`.`uploaded_by_user_id` AS `uploaded_by_user_id`,`upload_logs`.`uploaded_by_username` AS `uploaded_by_username`,count(0) AS `total_uploads`,sum(case when `upload_logs`.`status` = 'success' then 1 else 0 end) AS `successful_uploads`,sum(case when `upload_logs`.`status` = 'failed' then 1 else 0 end) AS `failed_uploads`,sum(`upload_logs`.`records_success`) AS `total_records_uploaded`,max(`upload_logs`.`upload_date`) AS `last_upload_date`,min(`upload_logs`.`upload_date`) AS `first_upload_date` from `upload_logs` where `upload_logs`.`uploaded_by_user_id` is not null group by `upload_logs`.`uploaded_by_user_id`,`upload_logs`.`uploaded_by_username`) `ul` on(`u`.`id` = `ul`.`uploaded_by_user_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_admin_username` (`admin_username`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `cohort_trends`
--
ALTER TABLE `cohort_trends`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prediction_date` (`prediction_date`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fees_transaction`
--
ALTER TABLE `fees_transaction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `prediction_requests`
--
ALTER TABLE `prediction_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_timestamp` (`request_timestamp`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_2020_sem_1`
--
ALTER TABLE `student_2020_sem_1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2020_sem_2`
--
ALTER TABLE `student_2020_sem_2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2021_sem_1`
--
ALTER TABLE `student_2021_sem_1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2021_sem_2`
--
ALTER TABLE `student_2021_sem_2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2022_sem_1`
--
ALTER TABLE `student_2022_sem_1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2022_sem_2`
--
ALTER TABLE `student_2022_sem_2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2023_sem_1`
--
ALTER TABLE `student_2023_sem_1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2023_sem_2`
--
ALTER TABLE `student_2023_sem_2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2024_sem_1`
--
ALTER TABLE `student_2024_sem_1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2024_sem_2`
--
ALTER TABLE `student_2024_sem_2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_2025_sem_1`
--
ALTER TABLE `student_2025_sem_1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `StudentID` (`StudentID`),
  ADD KEY `idx_student_id` (`StudentID`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`);

--
-- Indexes for table `student_predictions`
--
ALTER TABLE `student_predictions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_source` (`student_id`,`source_table`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_prediction_date` (`prediction_date`),
  ADD KEY `idx_course` (`course`),
  ADD KEY `idx_source_table` (`source_table`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `upload_logs`
--
ALTER TABLE `upload_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_upload_date` (`upload_date`),
  ADD KEY `idx_year_semester` (`year`,`semester`),
  ADD KEY `idx_uploaded_by` (`uploaded_by_user_id`),
  ADD KEY `idx_upload_logs_user_date` (`uploaded_by_user_id`,`upload_date`),
  ADD KEY `idx_upload_logs_status_date` (`status`,`upload_date`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`status`),
  ADD KEY `idx_user_logout` (`last_logout`);

--
-- Indexes for table `user_login_logs`
--
ALTER TABLE `user_login_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_login_time` (`login_time`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `user_session_invalidations`
--
ALTER TABLE `user_session_invalidations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `year`
--
ALTER TABLE `year`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_activity_logs`
--
ALTER TABLE `admin_activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `cohort_trends`
--
ALTER TABLE `cohort_trends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `fees_transaction`
--
ALTER TABLE `fees_transaction`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `prediction_requests`
--
ALTER TABLE `prediction_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `student_2020_sem_1`
--
ALTER TABLE `student_2020_sem_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `student_2020_sem_2`
--
ALTER TABLE `student_2020_sem_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `student_2021_sem_1`
--
ALTER TABLE `student_2021_sem_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `student_2021_sem_2`
--
ALTER TABLE `student_2021_sem_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `student_2022_sem_1`
--
ALTER TABLE `student_2022_sem_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `student_2022_sem_2`
--
ALTER TABLE `student_2022_sem_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `student_2023_sem_1`
--
ALTER TABLE `student_2023_sem_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `student_2023_sem_2`
--
ALTER TABLE `student_2023_sem_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT for table `student_2024_sem_1`
--
ALTER TABLE `student_2024_sem_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `student_2024_sem_2`
--
ALTER TABLE `student_2024_sem_2`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `student_2025_sem_1`
--
ALTER TABLE `student_2025_sem_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `student_predictions`
--
ALTER TABLE `student_predictions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3856;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `upload_logs`
--
ALTER TABLE `upload_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_login_logs`
--
ALTER TABLE `user_login_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=233;

--
-- AUTO_INCREMENT for table `user_session_invalidations`
--
ALTER TABLE `user_session_invalidations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `year`
--
ALTER TABLE `year`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
