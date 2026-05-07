-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2026 at 05:59 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;

SET FOREIGN_KEY_CHECKS = 0;

--
-- Database: `gymsystem`
--

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`attendance_id`, `member_id`, `class_id`, `check_in_time`, `check_out_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-04-08 06:20:00', '2026-04-08 07:25:00', 'Present', '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(2, 2, 2, '2026-04-08 08:50:00', '2026-04-08 10:05:00', 'Present', '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(3, 3, 3, '2026-04-08 17:45:00', '2026-04-08 18:17:44', 'Present', '2026-04-08 10:13:52', '2026-04-08 10:17:44'),
(4, 1, 1, '2026-04-08 18:16:04', '2026-04-08 18:20:30', 'Present', '2026-04-08 10:16:04', '2026-04-08 10:20:30'),
(5, 3, 4, '2026-04-22 02:40:28', '2026-04-30 00:38:15', 'Present', '2026-04-21 18:40:28', '2026-04-29 16:38:15'),
(6, 4, 7, '2026-04-30 02:46:10', '2026-05-03 14:23:35', 'Present', '2026-04-29 18:46:10', '2026-05-03 06:23:35'),
(7, 2, 4, '2026-05-03 13:41:48', '2026-05-03 13:42:03', 'Present', '2026-05-03 05:41:48', '2026-05-03 05:42:03'),
(8, 1, 4, '2026-05-03 14:23:55', '2026-05-03 14:24:04', 'Present', '2026-05-03 06:23:55', '2026-05-03 06:24:04'),
(9, 4, 4, '2026-05-03 14:24:09', '2026-05-03 14:24:34', 'Present', '2026-05-03 06:24:09', '2026-05-03 06:24:34'),
(10, 3, 4, '2026-05-03 14:24:45', NULL, 'Present', '2026-05-03 06:24:45', '2026-05-03 06:24:45'),
(11, 4, 4, '2026-05-03 14:24:51', '2026-05-03 14:26:35', 'Present', '2026-05-03 06:24:51', '2026-05-03 06:26:35'),
(12, 2, 4, '2026-05-03 14:24:56', NULL, 'Present', '2026-05-03 06:24:56', '2026-05-03 06:24:56'),
(13, 1, 4, '2026-05-03 14:25:02', NULL, 'Present', '2026-05-03 06:25:02', '2026-05-03 06:25:02'),
(14, 4, 4, '2026-05-04 07:09:51', '2026-05-04 11:53:13', 'Present', '2026-05-03 23:09:51', '2026-05-04 03:53:13');

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `member_id`, `class_id`, `status`, `created_at`) VALUES
(1, 1, 1, 'Booked', '2026-04-06 10:13:52'),
(2, 2, 2, 'Completed', '2026-04-06 10:13:52'),
(3, 3, 3, 'Booked', '2026-04-06 10:13:52'),
(4, 4, 1, 'Booked', '2026-04-09 12:10:20'),
(5, 4, 3, 'Booked', '2026-04-09 12:12:00'),
(6, 4, 2, 'Booked', '2026-04-09 12:13:20'),
(7, 4, 4, 'Cancelled', '2026-04-16 02:42:00'),
(8, 4, 5, 'Booked', '2026-04-22 14:14:58'),
(9, 4, 6, 'Cancelled', '2026-04-30 00:35:41'),
(10, 4, 7, 'Booked', '2026-04-30 02:40:03');

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `class_name`, `schedule_time`, `max_slots`, `created_at`, `updated_at`) VALUES
(1, 'Yoga Flow', '2026-04-08 06:30:00', 16, '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(2, 'Spin Class', '2026-04-08 09:00:00', 20, '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(3, 'Boxing Fundamentals', '2026-04-08 18:00:00', 12, '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(4, 'mgawhite', '2026-10-10 08:00:00', 1, '2026-04-15 18:41:00', '2026-04-15 18:41:00'),
(5, 'Mearl', '2026-05-21 15:50:00', 10, '2026-04-21 18:38:42', '2026-04-21 18:38:42'),
(6, 'class', '2026-05-09 08:34:00', 10, '2026-04-29 16:35:00', '2026-04-29 16:35:00'),
(7, 'try', '2026-04-30 10:39:00', 1, '2026-04-29 18:39:33', '2026-04-29 18:39:33'),
(8, 'muscle', '2026-05-05 14:27:00', 10, '2026-05-03 22:28:03', '2026-05-03 22:28:03'),
(9, 'sheeshhh', '2026-05-04 15:10:00', 2, '2026-05-03 23:10:15', '2026-05-03 23:10:15'),
(10, 'MATULOGCLASS', '2026-05-04 19:53:00', 10, '2026-05-04 03:53:40', '2026-05-04 03:53:40');

--
-- Dumping data for table `class_equipment`
--

INSERT INTO `class_equipment` (`cl_equpment_id`, `class_id`, `equipment_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(2, 2, 2, '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(3, 3, 3, '2026-04-08 10:13:52', '2026-04-08 10:13:52');

--
-- Dumping data for table `class_trainer`
--

INSERT INTO `class_trainer` (`trainer_id`, `class_id`, `staff_id`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(2, 2, 2, '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(3, 3, 3, '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(4, 5, 1, '2026-04-22 06:04:10', '2026-04-22 06:04:10'),
(5, 5, 2, '2026-04-22 06:09:15', '2026-04-22 06:09:15'),
(6, 4, 2, '2026-04-22 06:11:08', '2026-04-22 06:11:08'),
(7, 6, 2, '2026-04-29 16:36:31', '2026-04-29 16:36:31'),
(8, 7, 1, '2026-04-29 18:40:23', '2026-04-29 18:40:23'),
(9, 8, 2, '2026-05-03 22:28:13', '2026-05-03 22:28:13'),
(10, 9, 1, '2026-05-03 23:10:23', '2026-05-03 23:10:23'),
(11, 10, 3, '2026-05-04 03:53:54', '2026-05-04 03:53:54');

--
-- Dumping data for table `equipments`
--

INSERT INTO `equipments` (`equipment_id`, `name`, `quantity`, `status`, `condition_status`, `last_maintenance_date`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Treadmill Pro X1', 6, 'Available', 'Good', '2026-03-24', 'Cardio treadmill for general gym use.', '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(2, 'Spin Bike Elite', 12, '', 'Good', '2026-03-14', 'Indoor cycling bikes used in spin classes.', '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(3, 'Boxing Pad Set', 8, 'Maintenance', 'Under Repair', '2026-02-27', 'Focus mitts and pads for boxing drills.', '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(4, 'Jhonny', 1, 'Available', 'Good', NULL, 'pampawalag sakit', '2026-05-03 04:47:01', '2026-05-03 04:47:01'),
(5, 'Jhonny', 1, 'Available', 'Good', NULL, 'pampawalag sakit', '2026-05-03 04:47:07', '2026-05-03 04:47:07'),
(6, 'hii', 1, 'Available', 'Good', NULL, 'pampawalag sakit', '2026-05-03 05:02:43', '2026-05-03 05:02:43'),
(7, 'meeee', 1, 'Maintenance', 'Good', NULL, 'pampawalag sakit', '2026-05-03 05:07:26', '2026-05-03 05:07:26'),
(8, 'meeeeeeeeeeeeeeeeee', 1, 'Under Repair', 'Good', NULL, 'pampawalag sakit', '2026-05-03 05:23:50', '2026-05-03 05:23:50'),
(9, 'HEHEHE', 1, 'Available', 'Good', NULL, 'pampawalag sakit', '2026-05-04 03:55:07', '2026-05-04 03:55:07');

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `membership_plan_id`, `user_id`, `phone`, `membership_type`, `join_date`, `expiry_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 6, '09170000011', 'Premium', '2025-12-08', '2027-03-08', 'Active', '2026-04-08 10:13:51', '2026-04-08 10:13:51'),
(2, 1, 7, '09170000012', 'Basic', '2026-02-08', '2027-03-08', 'Active', '2026-04-08 10:13:51', '2026-04-20 05:59:48'),
(3, 3, 8, '09170000013', 'VIP', '2026-03-08', '2027-03-08', 'Active', '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(4, 3, 9, '09123456789', 'VIP', '2026-04-08', '2027-01-08', 'Active', '2026-04-08 10:42:08', '2026-05-03 06:04:47');

--
-- Dumping data for table `membership_plans`
--

INSERT INTO `membership_plans` (`mem_plan_id`, `name`, `price`, `duration_months`, `description`, `features`, `created_at`, `updated_at`) VALUES
(1, 'Basic', 1500.00, 1, 'Gym floor access for everyday workouts.', '[\"Open gym access\",\"Standard locker use\",\"1 class booking at a time\"]', '2026-04-28 03:04:13', '2026-04-28 03:04:13'),
(2, 'Premium', 2500.00, 1, 'Best balance for regular training and classes.', '[\"Unlimited gym access\",\"Priority class booking\",\"Progress tracking support\"]', '2026-04-28 03:04:13', '2026-04-28 03:04:13'),
(3, 'VIP', 3000.00, 1, 'Full-featured access for members who train often.', '[\"All Premium benefits\",\"Top booking priority\",\"Member-first support\"]', '2026-04-28 03:04:13', '2026-04-28 03:04:13');

--

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `member_id`, `requested_membership_plan_id`, `amount`, `payment_date`, `payment_method`, `reference_number`, `gcash_number`, `gcash_image_path`, `requested_membership_type`, `reviewed_at`, `reviewed_by_user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 2500.00, '2026-04-03 18:13:52', 'GCash', NULL, NULL, NULL, NULL, NULL, NULL, 'Paid', '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(2, 2, NULL, 1500.00, '2026-04-06 18:13:52', 'Cash', NULL, NULL, NULL, NULL, '2026-04-20 05:59:48', 1, 'Paid', '2026-04-08 10:13:52', '2026-04-20 05:59:48'),
(3, 3, NULL, 3000.00, '2026-04-07 18:13:52', 'Card', NULL, NULL, NULL, NULL, NULL, NULL, 'Failed', '2026-04-08 10:13:52', '2026-04-08 10:13:52'),
(4, 4, NULL, 2500.00, '2026-04-09 12:17:55', 'Card', NULL, NULL, NULL, NULL, NULL, NULL, 'Paid', '2026-04-09 04:17:55', '2026-04-09 04:17:55'),
(5, 4, 2, 2500.00, '2026-04-13 14:06:13', 'GCash', NULL, NULL, NULL, 'Premium', '2026-04-16 18:45:44', 1, 'Paid', '2026-04-13 06:06:13', '2026-04-16 18:45:44'),
(6, 4, 3, 3000.00, '2026-04-17 15:41:18', 'GCash', 'GCASH-REF-12345', '09069420667', 'payments/gcash/7DPOWg2Pq7MXt92bhFQO1z3Mwqd0Fs4dMUe71WOx.jpg', 'VIP', '2026-04-20 05:58:45', 1, 'Failed', '2026-04-17 07:41:18', '2026-04-20 05:58:45'),
(7, 4, 3, 3000.00, '2026-04-20 14:04:28', 'GCash', 'GCASH-ref-12345', '09999999999', 'payments/gcash/EtGXnLWvgFCLmhzhZ6CBeookPLmQiOglRnXYhNNl.jpg', 'VIP', '2026-04-20 06:04:42', 1, 'Paid', '2026-04-20 06:04:28', '2026-04-20 06:04:42'),
(8, 4, 2, 2500.00, '2026-04-23 02:19:34', 'GCash', 'GCASH-REF-12345', '099999999', 'payments/gcash/jmuHluewu6AEzXOWQxlydr8Ds2PCbEs1jrTOgavy.jpg', 'Premium', '2026-04-22 18:20:14', 1, 'Paid', '2026-04-22 18:19:34', '2026-04-22 18:20:14'),
(9, 4, 3, 3000.00, '2026-04-28 11:08:31', 'GCash', 'GCASH-REF-12345', '09069420667', 'payments/gcash/wre1V7booZU8hDfmzCNQMrxwV2Jw4JGuOPS5pY6m.jpg', 'VIP', '2026-04-28 03:08:56', 1, 'Paid', '2026-04-28 03:08:31', '2026-04-28 03:08:56'),
(10, 4, 2, 2500.00, '2026-04-30 00:40:00', 'GCash', 'GCASH-REF-12345', '09069420667', 'payments/gcash/NMWpv07dnHtkpQYdQzJtCEKKAfqMw4iLzebcaShn.jpg', 'Premium', '2026-04-29 16:40:40', 1, 'Paid', '2026-04-29 16:40:00', '2026-04-29 16:40:40'),
(11, 4, 3, 3000.00, '2026-05-03 14:04:27', 'GCash', 'GCASH-REF-12345', '09069420667', 'payments/gcash/XNAWz8Ja2ZHDtFBDNDW2zZjHyhoNQ5uNL5ymKFMc.png', 'VIP', '2026-05-03 06:04:47', 1, 'Paid', '2026-05-03 06:04:27', '2026-05-03 06:04:47');

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `user_id`, `role`, `specialization`, `created_at`, `updated_at`) VALUES
(1, 2, 'Receptionist', 'Front Desk', '2026-04-08 09:48:56', '2026-04-08 09:48:56'),
(2, 4, 'Trainer', 'Yoga and Pilates', '2026-04-08 10:13:50', '2026-04-08 10:13:50'),
(3, 5, 'Trainer', 'Strength and Boxing', '2026-04-08 10:13:51', '2026-04-08 10:13:51');

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `role`, `status`, `email_verified_at`, `password`, `remember_token`, `last_visit_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'System Admin', 'johnnlazar321@gmail.com', '09171234567', 'Admin', 'Active', NULL, '$2y$12$0kQ3EXRT651.B6swovnOWu9pG5Myd0hkmY3wqSdRwCV1ql9JLVRqq', NULL, '2026-05-06 07:30:45', '2026-04-08 05:57:57', '2026-05-06 07:30:45', NULL),
(2, 'Lazar Jhonn', 'lazarjhonn@gmail.com', '09123456789', 'Staff', 'Active', NULL, '$2y$12$zubBjqAOmLfRGGyW0co9BOGnYuT9rWlwQ6A8BKdOYS0gPiSStM4Dq', NULL, '2026-05-04 03:52:21', '2026-04-08 09:48:56', '2026-05-04 03:52:21', NULL),
(3, 'Demo Admin', 'admin.demo@wedumbell.test', '09170000001', 'Admin', 'Active', NULL, '$2y$12$uJDR1dwsIaIBX/Oh7OL/AeGaBNnOHUQ51SyPKdsVUyZp8ckQyOVJ2', NULL, '2026-04-08 09:13:50', '2026-04-08 10:13:50', '2026-04-08 10:13:50', NULL),
(4, 'Emma Thompson', 'coach.emma@wedumbell.test', '09170000002', 'Staff', 'Active', NULL, '$2y$12$QjLOsCHcw/E8NyBpMlJcHOm7WQkvbYcL.v/A/bgGm0IDSubrdcP5e', NULL, '2026-04-08 08:30:50', '2026-04-08 10:13:50', '2026-04-08 10:13:50', NULL),
(5, 'James Parker', 'coach.james@wedumbell.test', '09170000003', 'Staff', 'Active', NULL, '$2y$12$315DiOugNSWqeQEOB9vw5OYhtmgibVd74focygB.cyOToQG2K0cVu', NULL, '2026-04-08 08:42:51', '2026-04-08 10:13:51', '2026-04-08 10:13:51', NULL),
(6, 'Maria Santosss', 'maria.santos@wedumbell.test', '09170000011', 'Member', 'Active', NULL, '$2y$12$zLT4xg9R/sXPnCnucFgIUu.VOINExbamADnCyQ.Ge47pnQ4hneQeu', NULL, '2026-04-08 07:21:51', '2026-04-08 10:13:51', '2026-05-04 05:02:31', NULL),
(7, 'Kevin Reyes', 'kevin.reyes@wedumbell.test', '09170000012', 'Member', 'Active', NULL, '$2y$12$kUvdF3HhXz13o/phKGl5Ju99.E.VsQlD5XyF7MG51FpjgMgDQHUO.', NULL, '2026-04-08 05:15:51', '2026-04-08 10:13:51', '2026-04-08 10:13:51', NULL),
(8, 'Anna Dela Cruz', 'anna.dela-cruz@wedumbell.test', '09170000013', 'Member', 'Active', NULL, '$2y$12$PjgLJbG.o3DaK0vmgtLfBejGsj4JXB5MHYpZmJTNq1CbQYEyRPrsa', NULL, '2026-04-08 08:26:52', '2026-04-08 10:13:52', '2026-04-08 10:13:52', NULL),
(9, 'Johnn Lazar', 'crislazar64@gmail.com', '09123456789', 'Member', 'Active', NULL, '$2y$12$lwdnrZGCwIzp7Rras/3XxuCgkYP542BzqULgFRm5u7dpDXjpuGpkm', NULL, '2026-05-06 07:54:13', '2026-04-08 10:42:08', '2026-05-06 07:54:13', NULL);
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
