-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 20, 2026 at 03:59 PM
-- Server version: 5.6.41
-- PHP Version: 5.5.38

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eduvision`
--

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('manual','article') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `user_id`, `title`, `content`, `file_path`, `type`, `created_at`) VALUES
(2, 4, 'salom', 'jsndkjsn', 'uploads/files/1768908812_Sintaksis__So_z_birikmasi__So_z_birikmasida_bog_lanish.pdf', 'article', '2026-01-20 11:33:32');

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contracts`
--

INSERT INTO `contracts` (`id`, `title`, `file_path`, `created_at`) VALUES
(1, 'hujjat', 'uploads/files/1768895555_Sintaksis__So_z_birikmasi__So_z_birikmasida_bog_lanish.pdf', '2026-01-20 07:52:35');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `oraliq` int(11) DEFAULT '0',
  `mustaqil` int(11) DEFAULT '0',
  `yakuniy` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `oraliq`, `mustaqil`, `yakuniy`, `created_at`) VALUES
(1, 2, 1, 10, 30, 50, '2026-01-20 09:27:32');

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `created_at`) VALUES
(1, '22.144', '2026-01-20 07:17:20'),
(2, '22.02', '2026-01-20 09:54:58');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text,
  `link` varchar(255) DEFAULT '#',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 09:27:32'),
(2, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 09:27:33'),
(3, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 09:27:34'),
(4, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 09:27:35'),
(5, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 09:27:45'),
(6, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 09:27:46'),
(7, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 09:27:53'),
(8, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 09:29:31'),
(9, 4, 'Hisobingiz tasdiqlandi! ????', 'Sizning hisobingiz admin tomonidan tasdiqlandi. Endi barcha imkoniyatlardan foydalanishingiz mumkin.', 'dashboard.php', 1, '2026-01-20 09:35:37'),
(10, 4, 'Yangi Biriktirish!', 'Sizga yangi fan (Psixalogiya) va guruh (22.144) biriktirildi.', 'grades.php', 1, '2026-01-20 09:35:55'),
(11, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 09:38:17'),
(12, 1, 'Baholash Jarayoni ✍️', 'Nortoy tomonidan fan fanidan baholar qo\'yildi.', 'subjects.php', 1, '2026-01-20 09:38:17'),
(13, 2, 'Guruhga yangi vazifa!', 'Guruhingizga yangi vazifa yuklandi: topshiriq', 'tasks.php', 1, '2026-01-20 10:01:13'),
(14, 4, 'Vazifa topshirildi! ✅', 'Azamjon Nazirov \'topshiriq\' vazifasini topshirdi.', 'tasks.php', 1, '2026-01-20 10:04:05'),
(15, 1, 'Vazifa topshirildi! ✅', 'Azamjon Nazirov \'topshiriq\' vazifasini topshirdi.', 'tasks.php', 1, '2026-01-20 10:04:05'),
(16, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 10:21:27'),
(17, 1, 'Baholash Jarayoni ✍️', 'Nortoy tomonidan fan fanidan baholar qo\'yildi.', 'subjects.php', 1, '2026-01-20 10:21:27'),
(18, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 10:21:28'),
(19, 1, 'Baholash Jarayoni ✍️', 'Nortoy tomonidan fan fanidan baholar qo\'yildi.', 'subjects.php', 1, '2026-01-20 10:21:28'),
(20, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 10:21:29'),
(21, 1, 'Baholash Jarayoni ✍️', 'Nortoy tomonidan fan fanidan baholar qo\'yildi.', 'subjects.php', 1, '2026-01-20 10:21:29'),
(22, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 10:35:05'),
(23, 1, 'Baholash Jarayoni ✍️', 'Nortoy tomonidan fan fanidan baholar qo\'yildi.', 'subjects.php', 1, '2026-01-20 10:35:05'),
(24, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 10:39:42'),
(25, 1, 'Baholash Jarayoni ✍️', 'Nortoy tomonidan fan fanidan baholar qo\'yildi.', 'subjects.php', 1, '2026-01-20 10:39:42'),
(26, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 10:39:46'),
(27, 1, 'Baholash Jarayoni ✍️', 'Nortoy tomonidan fan fanidan baholar qo\'yildi.', 'subjects.php', 1, '2026-01-20 10:39:46'),
(28, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 10:40:56'),
(29, 1, 'Baholash Jarayoni ✍️', 'Nortoy tomonidan fan fanidan baholar qo\'yildi.', 'subjects.php', 1, '2026-01-20 10:40:56'),
(30, 2, 'Yangi Baho!', 'Sizga yangi baho qo\'yildi yoki yangilandi. Tekshirib ko\'ring.', 'grades.php', 1, '2026-01-20 10:41:06'),
(31, 1, 'Baholash Jarayoni ✍️', 'Nortoy tomonidan fan fanidan baholar qo\'yildi.', 'subjects.php', 1, '2026-01-20 10:41:06'),
(32, 2, 'Vazifa Baxolandi! ⭐', '\'topshiriq\' vazifangiz baxolandi. Ball: 25', 'tasks.php', 1, '2026-01-20 10:56:03'),
(33, 2, 'Vazifa Baxolandi! ⭐', '\'topshiriq\' vazifangiz baxolandi. Ball: 27', 'tasks.php', 1, '2026-01-20 10:57:38'),
(34, 2, 'Vazifa Baxolandi! ⭐', '\'topshiriq\' vazifangiz baxolandi. Ball: 30', 'tasks.php', 1, '2026-01-20 10:58:36'),
(35, 4, 'Loyiha Tasdiqlandi!', '\'Aqilli soat\' loyihangiz admin tomonidan tasdiqlandi.', 'projects.php', 1, '2026-01-20 11:03:12'),
(36, 4, 'Loyiha Tasdiqlandi!', '\'Aqilli soat\' loyihangiz admin tomonidan tasdiqlandi.', 'projects.php', 1, '2026-01-20 11:03:21'),
(37, 4, 'Loyiha Tasdiqlandi!', '\'Aqilli soat\' loyihangiz admin tomonidan tasdiqlandi.', 'projects.php', 1, '2026-01-20 11:03:28'),
(38, 2, 'Yangi Video! ????', 'Yangi video yuklandi: yangi yil', 'videos.php', 1, '2026-01-20 11:29:46'),
(39, 2, 'Yangi Maqola! ????', 'O\'qituvchi yangi material qo\'shdi: salom', 'articles.php', 1, '2026-01-20 11:31:28'),
(40, 1, 'Yangi Maqola/Qo\'llanma ✍️', 'Nortoy yangi material yukladi: salom', 'articles.php', 0, '2026-01-20 11:31:28'),
(41, 2, 'Yangi Maqola! ????', 'O\'qituvchi yangi material qo\'shdi: salom', 'articles.php', 1, '2026-01-20 11:33:32'),
(42, 1, 'Yangi Maqola/Qo\'llanma ✍️', 'Nortoy yangi material yukladi: salom', 'articles.php', 0, '2026-01-20 11:33:32');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved') COLLATE utf8mb4_unicode_ci DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `description`, `image_path`, `user_id`, `status`, `created_at`) VALUES
(1, 'Aqilli soat', 'elektron tasma orqali ishlaydi', 'uploads/images/1768906823_Skrinshot_2026-01-20_160016_png', 4, 'approved', '2026-01-20 11:00:23');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `day_of_week` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `group_id`, `day_of_week`, `start_time`, `end_time`, `subject_id`, `teacher_id`, `room`) VALUES
(1, 1, 'Monday', '15:00:00', '16:20:00', 1, 4, '100-xona');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`) VALUES
(1, 'Psixalogiya');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `score` int(11) DEFAULT NULL,
  `teacher_comment` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `task_id`, `user_id`, `file_path`, `comment`, `created_at`, `score`, `teacher_comment`) VALUES
(1, 1, 2, 'uploads/files/1768903445_sub_masala_19.docx', 'dsdm,s', '2026-01-20 10:04:05', 30, '.');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('text','image','file') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `max_attempts` int(11) DEFAULT '1',
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `type`, `file_path`, `deadline`, `created_by`, `created_at`, `max_attempts`, `subject_id`) VALUES
(1, 'topshiriq', 'bajar', 'file', 'uploads/files/1768903273_toliq_statistik_hisob_x.xlsx', '2026-01-29 23:59:00', 4, '2026-01-20 10:01:13', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_assignments`
--

INSERT INTO `task_assignments` (`id`, `task_id`, `user_id`, `group_id`) VALUES
(1, 1, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_assignments`
--

INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `subject_id`, `group_id`) VALUES
(2, 4, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','student','teacher') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `status`) VALUES
(1, 'Super Admin', 'admin@eduvision.uz', '$2y$10$60DaSPIQP16TvgzUjSBfGuDH/B2t17b7sKw1MFSlHv5rBPHv1JQAe', 'admin', '2026-01-20 07:03:50', 'active'),
(2, 'Azamjon Nazirov', 'azamjonnazirov@gmail.com', '$2y$10$qG7FXHxfBSfuG5CVrhHDu.w0b9o5cB2U0CTNSK5tUxHM2nQamcoaO', 'student', '2026-01-20 07:05:52', 'active'),
(4, 'Nortoy', 'teacher@gmail.com', '$2y$10$HxKTasyYIranInD0PT8p9uW6TcWl6Vol6OdbCZSQMUum7AsPFCSXy', 'teacher', '2026-01-20 09:33:36', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE `user_groups` (
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_groups`
--

INSERT INTO `user_groups` (`user_id`, `group_id`) VALUES
(2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('game','skill') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `title`, `description`, `file_path`, `type`, `created_at`) VALUES
(1, 'yangi yil', 'hdkjsdkjsdb', 'uploads/videos/1768908586_AQM3icOmD7a052FdrTF9GPjYImaaPCkNfKMR7kcajlvyxykIMr2p89wC_SY0YtcxOO7Hl5p7.mp4', 'game', '2026-01-20 11:29:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `fk_message_group` (`group_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tasks_creator` (`created_by`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`user_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_group` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD CONSTRAINT `teacher_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_groups`
--
ALTER TABLE `user_groups`
  ADD CONSTRAINT `user_groups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
