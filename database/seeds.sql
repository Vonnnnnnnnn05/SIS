-- ==============================================================
-- Student Information System (SIS) Initial Seed Data
-- ==============================================================

USE `sis_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Seed Roles
TRUNCATE TABLE `roles`;
INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'Administrator', 'Full institutional administration and system oversight'),
(2, 'Registrar', 'Student records, registration, and enrollment management'),
(3, 'Student', 'Personal profile and enrollment record access');

-- 2. Seed Initial Users
-- Passwords:
-- admin -> Admin@123
-- registrar -> Registrar@123
-- 2026-00001 -> Student@2026
TRUNCATE TABLE `users`;
INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role_id`, `status`, `must_change_password`, `created_at`) VALUES
(1, 'admin', 'admin@sis.local', '$2y$10$LNogKiekiM23jyQ0xhzWtuP8U5tN2STfqylsH3mzThe8Ds7oQFvni', 1, 'Active', 0, NOW()),
(2, 'registrar', 'registrar@sis.local', '$2y$10$h9jRjShj5PsAEFqwS9O.meFDN0BjQKV/qreCDwtbPP.f4G9C1oMMe', 2, 'Active', 0, NOW()),
(3, '2026-00001', 'juan.delacruz@sis.local', '$2y$10$qcaDmMrKPekz/Tg6aP/GaeRn8i9Be1842cVECGaw1Y1Q1oBZQxuEy', 3, 'Active', 0, NOW());

-- 3. Seed Degree Programs
TRUNCATE TABLE `programs`;
INSERT INTO `programs` (`program_id`, `program_code`, `program_name`, `department`, `status`) VALUES
(1, 'BSCS', 'Bachelor of Science in Computer Science', 'College of Computer Studies', 'Active'),
(2, 'BSIT', 'Bachelor of Science in Information Technology', 'College of Computer Studies', 'Active'),
(3, 'BSBA', 'Bachelor of Science in Business Administration', 'College of Business and Accountancy', 'Active'),
(4, 'BEED', 'Bachelor of Elementary Education', 'College of Education', 'Active'),
(5, 'BSN', 'Bachelor of Science in Nursing', 'College of Nursing', 'Active');

-- 4. Seed Academic Years
TRUNCATE TABLE `academic_years`;
INSERT INTO `academic_years` (`ay_id`, `year_code`, `start_year`, `end_year`, `is_current`) VALUES
(1, '2025-2026', 2025, 2026, 0),
(2, '2026-2027', 2026, 2027, 1);

-- 5. Seed Semesters
TRUNCATE TABLE `semesters`;
INSERT INTO `semesters` (`semester_id`, `ay_id`, `semester_term`, `is_active`, `start_date`, `end_date`) VALUES
(1, 2, '1st Semester', 1, '2026-08-15', '2026-12-20'),
(2, 2, '2nd Semester', 0, '2027-01-10', '2027-05-30'),
(3, 2, 'Summer', 0, '2027-06-15', '2027-07-30');

-- 6. Seed Initial Student
TRUNCATE TABLE `students`;
INSERT INTO `students` (`student_id`, `student_number`, `user_id`, `first_name`, `middle_name`, `last_name`, `date_of_birth`, `gender`, `civil_status`, `email`, `contact_number`, `home_address`, `emergency_contact_name`, `emergency_contact_number`, `program_id`, `current_status`) VALUES
(1, '2026-00001', 3, 'Juan', 'Santos', 'Dela Cruz', '2005-06-15', 'Male', 'Single', 'juan.delacruz@sis.local', '09171234567', '123 University Avenue, Sampaloc, Manila', 'Maria Dela Cruz', '09187654321', 1, 'Active');

-- 7. Seed Initial Enrollment
TRUNCATE TABLE `enrollments`;
INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `semester_id`, `program_id`, `year_level`, `enrollment_date`, `enrollment_status`, `remarks`, `created_by`) VALUES
(1, 1, 1, 1, '1st Year', '2026-08-16', 'Enrolled', 'Regular 1st Year enrollment', 2);

-- 8. Seed Initial System Log
TRUNCATE TABLE `system_logs`;
INSERT INTO `system_logs` (`user_id`, `role`, `action_type`, `module`, `target_id`, `details`, `ip_address`, `user_agent`) VALUES
(1, 'Administrator', 'SEED', 'SYSTEM', '1', 'System baseline seeded successfully', '127.0.0.1', 'CLI-Provisioner');

SET FOREIGN_KEY_CHECKS = 1;
